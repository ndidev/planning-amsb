<?php

// Path: api/src/Repository/UserRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Array\ArrayHandler;
use App\Core\Auth\AccountStatus;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\Exceptions\Server\ServerException;
use App\DTO\CurrentUserFormDTO;
use App\Entity\UserAccount;
use App\Service\UserService;

/**
 * @phpstan-type UserAccountArray array{
 *                                  uid: string,
 *                                  login: string,
 *                                  nom: string,
 *                                  can_login: int,
 *                                  roles: string,
 *                                  statut: string,
 *                                  last_connection: ?string,
 *                                  commentaire: string,
 *                                  historique: string,
 *                                }
 */
final class UserRepository extends Repository
{
    public function __construct(private UserService $userService) {}

    public function userExists(string $uid): bool
    {
        return $this->mysql->exists("admin_users", $uid, "uid");
    }

    /**
     * Récupère tous les comptes utilisateurs.
     * 
     * @return UserAccount[] Comptes utilisateurs.
     */
    public function fetchAllUsers(): array
    {
        $statement = "SELECT * FROM admin_users ORDER BY login";

        $userRequest = $this->mysql->query($statement);

        if (!$userRequest) {
            throw new DBException("Impossible de récupérer les utilisateurs.");
        }

        /** @phpstan-var UserAccountArray[] $usersRaw */
        $usersRaw = $userRequest->fetchAll();

        // Update Redis
        $this->redis->pipeline();
        foreach ($usersRaw as $user) {
            $this->redis->hMSet("admin:users:{$user["uid"]}", $user);
        }
        $this->redis->exec();

        $users = \array_map(
            fn(array $userRaw) => $this->userService->makeUserAccountFromDatabase($userRaw),
            $usersRaw
        );

        return $users;
    }

    /**
     * Récupère un compte utilisateur.
     * 
     * @param string $uid UID du compte à récupérer.
     * 
     * @return ?UserAccount Compte utilisateur récupéré
     */
    public function fetchUserByUid(string $uid): ?UserAccount
    {
        // Tentative Redis
        $userRaw = $this->redis->hGetAll("admin:users:{$uid}");

        // MariaDB
        if (!$userRaw) {
            $statement = "SELECT * FROM admin_users WHERE uid = :uid";

            $request = $this->mysql->prepare($statement);
            $request->execute(["uid" => $uid]);

            $userRaw = $request->fetch();

            if (!\is_array($userRaw)) {
                return null;
            }

            $userHandler = new ArrayHandler($userRaw);

            // Update Redis
            $this->redis->hMSet("admin:users:{$userHandler->getString('uid')}", $userRaw);
        }

        /** @phpstan-var UserAccountArray $userRaw */

        $user = $this->userService->makeUserAccountFromDatabase($userRaw);

        return $user;
    }

    /**
     * Crée un compte utilisateur.
     * 
     * @param UserAccount $user Eléments du compte à créer.
     * @param string $adminName Nom de l'admin créant le compte.
     * 
     * @return UserAccount Compte utilisateur créé
     */
    public function createUser(UserAccount $user, string $adminName): UserAccount
    {
        $uidsRequest = $this->mysql->query("SELECT uid FROM admin_users");

        if (!$uidsRequest) {
            throw new DBException("Impossible de récupérer les UIDs.");
        }

        /** @var string[] $uids */
        $uids = $uidsRequest->fetchAll(\PDO::FETCH_COLUMN);

        do {
            $uid = \substr(\md5(\uniqid()), 0, 8);
        } while (\in_array($uid, $uids));

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

        if (!$request) {
            throw new DBException("Impossible de préparer la requête.");
        }

        $request->execute([
            "uid" => $uid,
            "login" => \mb_substr((string) $user->getLogin(), 0, 255),
            "nom" => \mb_substr($user->getName(), 0, 255),
            "statut" => AccountStatus::PENDING,
            "roles" => \json_encode($user->getRoles()),
            "commentaire" => $user->getComments(),
            "admin" => $adminName
        ]);

        // Récupérer l'UID du nouvel utilisateur
        $uidRequest = $this->mysql->query("SELECT uid FROM admin_users WHERE login = '{$user->getLogin()}'");

        if (!$uidRequest) {
            throw new DBException("Impossible de récupérer l'UID du nouvel utilisateur.");
        }

        /** @var string|false $uid */
        $uid = $uidRequest->fetch(\PDO::FETCH_COLUMN);

        if (!$uid) {
            throw new DBException("Impossible de récupérer l'UID du nouvel utilisateur.");
        }

        $this->updateRedis($uid);

        /** @var UserAccount */
        $newUser = $this->fetchUserByUid($uid);

        return $newUser;
    }

    /**
     * Met à jour un compte utilisateur.
     * 
     * @param UserAccount   $user      Eléments du compte à modifier.
     * @param string $adminName Nom de l'admin modifiant le compte.
     * 
     * @return UserAccount Compte utilisateur modifié.
     */
    public function updateUser(UserAccount $user, string $adminName): UserAccount
    {
        $uid = $user->getUid();

        if (!$uid) {
            throw new ServerException("Impossible de mettre à jour un utilisateur sans UID.");
        }

        $userCurrentInfo = $this->fetchUserByUid($uid);

        if (!$userCurrentInfo) {
            throw new ServerException("Impossible de mettre à jour un utilisateur inexistant.");
        }

        $currentStatus = $userCurrentInfo->getStatus();

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
                "login" => \mb_substr($user->getLogin(), 0, 255),
                "nom" => \mb_substr($user->getName(), 0, 255),
                "commentaire" => $user->getComments(),
                "roles" => \json_encode($user->getRoles()),
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
                "statut" => AccountStatus::PENDING,
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
                "statut" => AccountStatus::INACTIVE,
                "admin" => $adminName,
                "uid" => $uid,
            ]);

            $this->clearSessions($uid);
        }

        $this->updateRedis($uid);

        /** @var UserAccount */
        $updatedUser = $this->fetchUserByUid($uid);

        return $updatedUser;
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
            "statut" => AccountStatus::DELETED,
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
    private function updateRedis(string $uid): void
    {
        $userRequest = $this->mysql->query("SELECT * FROM admin_users WHERE uid = '{$uid}'");

        if (!$userRequest) {
            throw new DBException("Impossible de récupérer les informations de l'utilisateur.");
        }

        $user = $userRequest->fetch();

        if (!\is_array($user)) {
            throw new DBException("Impossible de récupérer les informations de l'utilisateur.");
        }

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
            if ($batch) $sessions = \array_merge($sessions, $batch);
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

    public function updateCurrentUser(CurrentUserFormDTO $user): UserAccount
    {
        $uid = $user->getUid();

        $usernameStatement = "UPDATE `admin_users` SET nom = :nom WHERE `uid` = :uid";

        $passwordStatement = "UPDATE `admin_users` SET `password` = :password WHERE `uid` = :uid";

        $usernameRequest = $this->mysql->prepare($usernameStatement);
        $usernameRequest->execute([
            "nom" => \mb_substr($user->getName(), 0, 255),
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

        /** @var UserAccount */
        $updatedUser = $this->fetchUserByUid($uid);

        return $updatedUser;
    }
}
