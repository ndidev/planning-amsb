<?php

// Path: api/src/Service/UserService.php

namespace App\Service;

use App\Core\Auth\User as AuthUser;
use App\Core\Component\SSEHandler;
use App\Core\Exceptions\Client\ClientException;
use App\DTO\CurrentUserFormDTO;
use App\DTO\CurrentUserInfoDTO;
use App\Entity\User;
use App\Repository\UserRepository;

/**
 * @phpstan-type UserArray array{
 *                           uid?: string,
 *                           login?: string,
 *                           nom?: string,
 *                           can_login?: bool,
 *                           roles?: string|array<string, int>,
 *                           statut?: string,
 *                           last_connection?: string,
 *                           commentaire?: string,
 *                           historique?: string,
 *                         }
 * 
 * @phpstan-import-type UserRolesObject from \App\Entity\User
 */
final class UserService
{
    private UserRepository $userRepository;

    public function __construct(
        private ?SSEHandler $sse = null,
        private ?AuthUser $currentUser = null,
    ) {
        $this->userRepository = new UserRepository();
    }

    /**
     * Crée un objet User à partir des données brutes de la base de données.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param UserArray $rawData
     * 
     * @return User 
     */
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

    /**
     * Crée un objet User à partir des données d'un formulaire.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param UserArray $rawData
     * 
     * @return User 
     */
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
     * Crée un DTO pour la mise à jour des informations de l'utilisateur courant.
     * 
     * @param array{nom?: string, password?: string} $rawData 
     * 
     * @return CurrentUserFormDTO 
     */
    public function makeCurrentUserDTO(array $rawData): CurrentUserFormDTO
    {
        $currentUserDTO = (new CurrentUserFormDTO())
            ->setUid($this->currentUser->uid)
            ->setName($rawData["nom"] ?? $this->currentUser->name)
            ->setPassword($rawData["password"] ?? null);

        return $currentUserDTO;
    }

    public function userExists(string $uid): bool
    {
        return $this->userRepository->userExists($uid);
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
     */
    public function getUser(string $uid): ?User
    {
        return $this->userRepository->fetchUserByUid($uid);
    }

    /**
     * Crée un utilisateur.
     * 
     * @param array $input
     * @param string $adminName
     * 
     * @phpstan-param UserArray $input
     * 
     * @return User 
     */
    public function createUser(array $input, string $adminName): User
    {
        $user = $this->makeUserFromForm($input);

        return $this->userRepository->createUser($user, $adminName);
    }

    /**
     * Met à jour un utilisateur.
     * 
     * @param string $uid 
     * @param array $input 
     * @param AuthUser $admin 
     * 
     * @phpstan-param UserArray $input
     * 
     * @return User 
     */
    public function updateUser(string $uid, array $input, AuthUser $admin): User
    {
        $user = $this->makeUserFromForm($input)->setUid($uid);

        // Conservation du rôle admin : un admin ne peut pas changer lui-même son statut admin
        if ($uid === $admin->uid) {
            $user->getRoles()->admin = (int) ($admin->isAdmin ?? 0);
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

    /**
     * Récupère les informations de l'utilisateur courant.
     * 
     * @return null|array{
     *                uid: string,
     *                login: string,
     *                nom: string,
     *                roles: UserRolesObject,
     *                statut: string,
     *              }
     */
    public function getCurrentUserInfo(): ?array
    {
        if (!$this->currentUser) {
            return null;
        }

        return [
            "uid" => $this->currentUser->uid,
            "login" => $this->currentUser->login,
            "nom" => $this->currentUser->name,
            "roles" => $this->currentUser->roles,
            "statut" => $this->currentUser->status,
        ];
    }

    /**
     * Met à jour les informations de l'utilisateur courant.
     * 
     * @param array{nom?: string, password?: string} $input
     * 
     * @return CurrentUserInfoDTO
     */
    public function updateCurrentUser(array $input): CurrentUserInfoDTO
    {
        $currentUserDTO = $this->makeCurrentUserDTO($input);

        if (
            $currentUserDTO->getPassword()
            && strlen($currentUserDTO->getPassword()) < $_ENV["AUTH_LONGUEUR_MINI_PASSWORD"]
        ) {
            throw new ClientException("Le mot de passe doit contenir au moins {$_ENV["AUTH_LONGUEUR_MINI_PASSWORD"]} caractères.");
        }

        $updatedUser = $this->userRepository->updateCurrentUser($currentUserDTO);

        return new CurrentUserInfoDTO($updatedUser);
    }
}
