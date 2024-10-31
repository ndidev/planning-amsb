<?php

// Path: api/src/Repository/UserRepository.php

namespace App\Repository;

use App\Core\Auth\AccountStatus;
use App\Core\Exceptions\Client\Auth\InvalidAccountException;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\CurrentUserFormDTO;
use App\Entity\User;
use App\Service\UserService;

final class UserRepository extends Repository
{
    public function userExists(string $uid): bool
    {
        return $this->mysql->exists("admin_users", $uid, "uid");
    }

    /**
     * Récupère tous les comptes utilisateurs.
     * 
     * @return User[] Comptes utilisateurs.
     */
    public function fetchAllUsers(): array
    {
        $statement = "SELECT * FROM admin_users ORDER BY login";

        $usersRaw = $this->mysql->query($statement)->fetchAll();

        // Update Redis
        $this->redis->pipeline();
        foreach ($usersRaw as $user) {
            $this->redis->hMSet("admin:users:{$user["uid"]}", $user);
        }
        $this->redis->exec();

        $userService = new UserService();

        $users = array_map(fn(array $userRaw) => $userService->makeUserFromDatabase($userRaw), $usersRaw);

        unset($usersRaw);

        return $users;
    }

    /**
     * Récupère un compte utilisateur.
     * 
     * @param string $uid UID du compte à récupérer.
     * 
     * @return ?User Compte utilisateur récupéré
     */
    public function fetchUserByUid(string $uid): ?User
    {
        // Tentative Redis
        $userRaw = $this->redis->hGetAll("admin:users:{$uid}");

        // MariaDB
        if (!$userRaw) {
            $statement = "SELECT * FROM admin_users WHERE uid = :uid";

            $request = $this->mysql->prepare($statement);
            $request->execute(["uid" => $uid]);

            $userRaw = $request->fetch();

            if (!$userRaw) {
                return null;
            }

            // Update Redis
            $this->redis->hMSet("admin:users:{$userRaw["uid"]}", $userRaw);
        }

        $user = (new UserService())->makeUserFromDatabase($userRaw);

        unset($userRaw);

        return $user;
    }

    /**
     * Récupère un compte utilisateur.
     * 
     * @param string $login Login du compte à récupérer.
     * 
     * @return User Compte utilisateur.
     */
    public function fetchUserByLogin(string $login): User
    {
        $request = $this->mysql->prepare("SELECT uid FROM admin_users WHERE login = :login");
        $request->execute(["login" => $login]);
        $uid = $request->fetch(\PDO::FETCH_COLUMN);

        if (!$uid) {
            throw new InvalidAccountException();
        }

        return $this->fetchUserByUid($uid);
    }

    /**
     * Crée un compte utilisateur.
     * 
     * @param User $user Eléments du compte à créer.
     * @param string $adminName Nom de l'admin créant le compte.
     * 
     * @return User Compte utilisateur créé
     */
    public function createUser(User $user, string $adminName): User
    {
        $uids = $this->mysql->query("SELECT uid FROM admin_users")->fetchAll(\PDO::FETCH_COLUMN);

        do {
            $uid = substr(md5(uniqid()), 0, 8);
        } while (in_array($uid, $uids));

        $statement =
            "INSERT INTO admin_users
            VALUES(
                :uid,
                :login,
                NULL, -- password
                1,    -- can_login
                :nom,
                NULL, -- created_at
                NULL, -- last_connection
                0,    -- login_attempts
                :statut,
                :roles,
                :commentaire,
                CONCAT('(', NOW(), ') Compte créé par ', :admin) -- historique
            )";

        $request = $this->mysql->prepare($statement);

        $request->execute([
            "uid" => $uid,
            "login" => mb_substr($user->getLogin(), 0, 255),
            "nom" => mb_substr($user->getName(), 0, 255),
            "statut" => AccountStatus::PENDING->value,
            "roles" => json_encode($user->getRoles()),
            "commentaire" => $user->getComments(),
            "admin" => $adminName
        ]);

        [$uid] = $this->mysql->query("SELECT uid FROM admin_users WHERE login = '{$user->getLogin()}'")->fetch(\PDO::FETCH_NUM);

        $this->updateRedis($uid);

        return $this->fetchUserByUid($uid);
    }

    /**
     * Met à jour un compte utilisateur.
     * 
     * @param User   $user      Eléments du compte à modifier.
     * @param string $adminName Nom de l'admin modifiant le compte.
     * 
     * @return User Compte utilisateur modifié.
     */
    public function updateUser(User $user, string $adminName): User
    {
        $uid = $user->getUid();

        /** @var AccountStatus $currentStatus */
        $currentStatus = $this->fetchUserByUid($user->getUid())->getStatus();

        /** @var AccountStatus $newStatus */
        $newStatus = $user->getStatus();

        // Pas de changement de statut
        if ($currentStatus === $newStatus) {
            $statement =
                "UPDATE admin_users
                SET
                    nom = :nom,
                    login = :login,
                    roles = :roles,
                    commentaire = :commentaire
                WHERE uid = :uid";

            $request = $this->mysql->prepare($statement);
            $request->execute([
                "login" => mb_substr($user->getLogin(), 0, 255),
                "nom" => mb_substr($user->getName(), 0, 255),
                "commentaire" => $user->getComments(),
                "roles" => json_encode($user->getRoles()),
                "uid" => $uid,
            ]);
        }

        // Passage au statut "PENDING" (réinitialisation)
        if ($currentStatus !== AccountStatus::PENDING && $newStatus === AccountStatus::PENDING) {
            $statement =
                "UPDATE admin_users
                SET
                    password = NULL,
                    statut = :statut,
                    login_attempts = 0,
                    historique = CONCAT(historique, '\n', '(', NOW(), ') Compte réinitialisé par ', :admin)
                WHERE uid = :uid";

            $request = $this->mysql->prepare($statement);
            $request->execute([
                "statut" => AccountStatus::PENDING->value,
                "admin" => $adminName,
                "uid" => $uid
            ]);

            $this->clearSessions($uid);
        }

        // Passage au statut "INACTIVE" (désactivation)
        if ($currentStatus !== AccountStatus::INACTIVE && $newStatus === AccountStatus::INACTIVE) {
            $statement =
                "UPDATE admin_users
                SET
                    password = NULL,
                    statut = :statut,
                    login_attempts = 0,
                    historique = CONCAT(historique, '\n', '(', NOW(), ') Compte désactivé par ', :admin)
                WHERE uid = :uid";

            $request = $this->mysql->prepare($statement);
            $request->execute([
                "statut" => AccountStatus::INACTIVE->value,
                "admin" => $adminName,
                "uid" => $uid,
            ]);

            $this->clearSessions($uid);
        }

        $this->updateRedis($uid);

        return $this->fetchUserByUid($uid);
    }

    /**
     * Supprime un compte utilisateur.
     * 
     * @param string $uid UID du compte à supprimer.
     * @param string $adminName Nom de l'admin supprimant le compte.
     * 
     * @throws DBException En cas d'erreur de suppression.
     */
    public function deleteUser(string $uid, string $adminName): void
    {
        $statement =
            "UPDATE admin_users
            SET
                login = CONCAT(login, '_del-', DATE(NOW()), 'T', TIME(NOW())),
                password = NULL,
                statut = :statut,
                login_attempts = 0,
                historique = CONCAT(historique, '\n', '(', NOW(), ') Compte supprimé par ', :admin)
            WHERE uid = :uid";

        $request = $this->mysql->prepare($statement);

        $success = $request->execute([
            "statut" => AccountStatus::DELETED->value,
            "admin" => $adminName,
            "uid" => $uid,
        ]);

        $this->updateRedis($uid);

        if (!$success) {
            throw new DBException("Erreur lors de la suppression");
        }
    }

    /**
     * Met à jours les informations de l'utilisateur dans Redis.
     */
    public function updateRedis(string $uid): void
    {
        $user = $this->mysql
            ->query("SELECT * FROM admin_users WHERE uid = '{$uid}'")
            ->fetch();

        // Copie des infos dans Redis (hash)
        $this->redis->hMSet("admin:users:{$uid}", $user);
    }

    /**
     * Supprimer les sessions de l'utilisateur dans Redis.
     */
    public function clearSessions(string $uid): void
    {
        // Obtenir toutes les sessions en cours
        $sessions = [];
        do {
            $batch = $this->redis->scan($iterator, "admin:sessions:*");
            if ($batch) $sessions = array_merge($sessions, $batch);
        } while ($iterator);

        // Obtenir les utilisateurs pour chaque session
        $this->redis->pipeline();
        foreach ($sessions as $session) {
            $this->redis->get($session);
        }
        $uids = $this->redis->exec();

        // Combiner sessions et utilisateurs
        $sessions = array_combine($sessions, $uids);

        // Supprimer les sessions de l'utilisateur
        $this->redis->pipeline();
        foreach ($sessions as $session => $sessionUid) {
            if ($sessionUid === $uid) {
                $this->redis->del($session);
            }
        }
        $this->redis->exec();
    }

    public function updateCurrentUser(CurrentUserFormDTO $user): User
    {
        $uid = $user->getUid();

        $usernameStatement = "UPDATE `admin_users` SET nom = :nom WHERE `uid` = :uid";

        $passwordStatement = "UPDATE `admin_users` SET `password` = :password WHERE `uid` = :uid";

        $usernameRequest = $this->mysql->prepare($usernameStatement);
        $usernameRequest->execute([
            "nom" => mb_substr($user->getName(), 0, 255),
            "uid" => $uid,
        ]);

        if ($user->getPasswordHash() !== null) {
            $passwordRequest = $this->mysql->prepare($passwordStatement);
            $passwordRequest->execute([
                "password" => $user->getPasswordHash(),
                "uid" => $uid,
            ]);
        }

        $this->updateRedis($uid);

        return $this->fetchUserByUid($uid);
    }
}
