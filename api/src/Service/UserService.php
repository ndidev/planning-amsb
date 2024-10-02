<?php

// Path: api/src/Service/UserService.php

namespace App\Service;

use App\Core\Component\SSEHandler;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Core\Auth\User as AuthUser;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(private ?SSEHandler $sse = null)
    {
        $this->userRepository = new UserRepository();
    }

    public function makeUserFromDatabase(array $rawData): User
    {
        $user = (new User())
            ->setUid($rawData["uid"] ?? null)
            ->setLogin($rawData["login"] ?? null)
            ->setName($rawData["nom"] ?? '')
            ->setCanLogin($rawData["can_login"] ?? false)
            ->setRoles(json_decode($rawData["roles"]) ?? '{}')
            ->setStatus($rawData["statut"] ?? '')
            ->setLastLogin($rawData["last_connection"] ?? null)
            ->setComments($rawData["commentaire"] ?? '')
            ->setHistory($rawData["historique"] ?? '');

        return $user;
    }

    public function makeUserFromForm(array $rawData): User
    {
        $user = (new User())
            ->setUid($rawData["uid"] ?? null)
            ->setLogin($rawData["login"] ?? null)
            ->setName($rawData["nom"] ?? '')
            ->setStatus($rawData["statut"] ?? '')
            ->setRoles(json_decode(json_encode($rawData["roles"] ?? [])))
            ->setComments($rawData["commentaire"] ?? '');

        return $user;
    }

    /**
     * Récupère la liste des utilisateurs.
     * 
     * @param bool $canLoginOnly `true` pour récupérer uniquement les utilisateurs pouvant se connecter.
     * 
     * @return User[]
     */
    public function getUsers(bool $canLoginOnly = true): array
    {
        $users = $this->userRepository->fetchAllUsers();

        if ($canLoginOnly) {
            $users = array_values(array_filter($users, fn($user) => $user->canLogin()));
        }

        return $users;
    }

    /**
     * Récupère un utilisateur.
     * 
     * @param string $uid Identifiant de l'utilisateur.
     * 
     * @return User 
     */
    public function getUser(string $uid): User
    {
        return $this->userRepository->fetchUserByUid($uid);
    }

    /**
     * Crée un utilisateur.
     * 
     * @param array $input 
     * 
     * @return User 
     */
    public function createUser(array $input, string $adminName): User
    {
        $user = $this->makeUserFromForm($input);

        return $this->userRepository->createUser($user, $adminName);
    }

    public function updateUser(string $uid, array $input, AuthUser $admin): User
    {
        $user = $this->makeUserFromForm($input)->setUid($uid);

        // Conservation du rôle admin : un admin ne peut pas changer lui-même son statut admin
        if ($uid === $admin->uid) {
            $user->getRoles()->admin = (int) $admin->isAdmin ?? 0;
        }

        return $this->userRepository->updateUser($user, $admin->name);
    }

    public function deleteUser(string $uid, string $adminName): void
    {
        $this->userRepository->deleteUser($uid, $adminName);

        $this->clearSessions($uid);
    }

    /**
     * Met à jours les informations de l'utilisateur dans Redis.
     */
    public function updateRedis(string $uid): void
    {
        $this->userRepository->updateRedis($uid);
    }

    /**
     * Supprimer les sessions de l'utilisateur dans Redis.
     */
    public function clearSessions(string $uid): void
    {
        $this->userRepository->clearSessions($uid);

        // Clôturer les connexions SSE
        $this->sse?->addEvent("admin/sessions", "close", "uid:{$uid}");
    }
}
