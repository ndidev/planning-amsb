<?php

// Path: api/src/Repository/UserRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Array\ArrayHandler;
use App\Core\Auth\AccountStatus;
use App\Core\Auth\UserAuthenticator;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\Exceptions\Server\ServerException;
use App\DTO\CurrentUserFormDTO;
use App\Entity\User;
use App\Service\UserService;
use ReflectionClass;

/**
 * @phpstan-import-type UserAccountArray from \App\Entity\User
 */
final class UserRepository extends Repository
{
    /** @var ReflectionClass<User> */
    private ReflectionClass $userReflector;

    public function __construct(private UserService $userService)
    {
        $this->userReflector = new ReflectionClass(User::class);
    }

    public function userExists(string $uid): bool
    {
        return $this->mysql->exists('admin_users', $uid, 'uid');
    }

    /**
     * Récupère tous les comptes utilisateurs.
     * 
     * @return User[] Comptes utilisateurs.
     */
    public function fetchAllUsers(): array
    {
        try {
            /** @var UserAccountArray[] */
            $usersRaw = $this->mysql
                ->prepareAndExecute(
                    "SELECT * FROM admin_users WHERE NOT statut = :deletedStatus ORDER BY login",
                    ["deletedStatus" => AccountStatus::DELETED]
                )
                ->fetchAll();

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
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les utilisateurs.", previous: $e);
        }
    }

    /**
     * Récupère un compte utilisateur.
     * 
     * @param string $uid UID du compte à récupérer.
     * @param bool $bypassCache `true` pour ignorer le cache.
     * 
     * @return ?User Compte utilisateur récupéré
     */
    public function fetchUserByUid(string $uid, bool $bypassCache = false): ?User
    {
        /** @var array<string, User> */
        static $cache = [];

        if (!$bypassCache && isset($cache[$uid])) {
            return $cache[$uid];
        }

        if (!$this->userExists($uid)) {
            return null;
        }

        /** @var User */
        $user = $this->userReflector->newLazyProxy(
            function () use ($uid) {
                // Tentative Redis
                $userRaw = $this->redis->hGetAll("admin:users:{$uid}");

                // MariaDB
                if (!$userRaw) {
                    $statement = "SELECT * FROM admin_users WHERE uid = :uid";

                    try {
                        /** @var UserAccountArray $userRaw */
                        $userRaw = $this->mysql
                            ->prepareAndExecute($statement, ["uid" => $uid])
                            ->fetch();
                    } catch (\PDOException $e) {
                        throw new DBException("Impossible de récupérer l'utilisateur.", previous: $e);
                    }

                    $userHandler = new ArrayHandler($userRaw);

                    // Update Redis
                    $this->redis->hMSet("admin:users:{$userHandler->getString('uid')}", $userRaw);
                }

                /** @var UserAccountArray $userRaw */

                return $this->userService->makeUserAccountFromDatabase($userRaw);
            }
        );

        $this->userReflector->getProperty('uid')->setRawValueWithoutLazyInitialization($user, $uid);

        $cache[$uid] = $user;

        return $user;
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

        $this->mysql->prepareAndExecute($statement, [
            "uid" => $uid,
            "login" => \mb_substr((string) $user->login, 0, 255),
            "nom" => \mb_substr($user->name, 0, 255),
            "statut" => AccountStatus::PENDING,
            "roles" => \json_encode($user->roles),
            "commentaire" => $user->comments,
            "admin" => $adminName
        ]);

        // Récupérer l'UID du nouvel utilisateur
        /** @var string|false $uid */
        $uid = $this->mysql
            ->prepareAndExecute("SELECT uid FROM admin_users WHERE login = '{$user->login}'")
            ->fetch(\PDO::FETCH_COLUMN);

        if (!$uid) {
            throw new DBException("Impossible de récupérer l'UID du nouvel utilisateur.");
        }

        $this->updateRedis($uid);

        /** @var User */
        $newUser = $this->fetchUserByUid($uid);

        return $newUser;
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
        $uid = $user->uid;

        if (!$uid) {
            throw new ServerException("Impossible de mettre à jour un utilisateur sans UID.");
        }

        $userCurrentInfo = $this->fetchUserByUid($uid);

        if (!$userCurrentInfo) {
            throw new ServerException("Impossible de mettre à jour un utilisateur inexistant.");
        }

        $currentStatus = $userCurrentInfo->status;

        $newStatus = $user->status;

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
                "login" => \mb_substr($user->login, 0, 255),
                "nom" => \mb_substr($user->name, 0, 255),
                "commentaire" => $user->comments,
                "roles" => \json_encode($user->roles),
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

            $this->mysql->prepareAndExecute($statement, [
                "statut" => AccountStatus::PENDING,
                "admin" => $adminName,
                "uid" => $uid
            ]);

            new UserAuthenticator()->clearSessions($uid);
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

            $this->mysql->prepareAndExecute($statement, [
                "statut" => AccountStatus::INACTIVE,
                "admin" => $adminName,
                "uid" => $uid,
            ]);

            new UserAuthenticator()->clearSessions($uid);
        }

        $this->updateRedis($uid);

        /** @var User */
        $updatedUser = $this->fetchUserByUid($uid, true);

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

    public function updateCurrentUser(CurrentUserFormDTO $user): User
    {
        $uid = $user->uid;

        $usernameStatement = "UPDATE `admin_users` SET nom = :nom WHERE `uid` = :uid";

        $passwordStatement = "UPDATE `admin_users` SET `password` = :password WHERE `uid` = :uid";

        $this->mysql->prepareAndExecute($usernameStatement, [
            "nom" => \mb_substr($user->name, 0, 255),
            "uid" => $uid,
        ]);

        if ($user->passwordHash !== null) {
            $passwordRequest = $this->mysql->prepare($passwordStatement);
            $passwordRequest->execute([
                "password" => $user->passwordHash,
                "uid" => $uid,
            ]);
        }

        $this->updateRedis($uid);

        /** @var User */
        $updatedUser = $this->fetchUserByUid($uid);

        return $updatedUser;
    }
}
