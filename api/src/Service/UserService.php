<?php

// Path: api/src/Service/UserService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Auth\User as AuthUser;
use App\Core\Auth\UserRoles;
use App\Core\Component\SSEHandler;
use App\Core\Exceptions\Client\Auth\UnauthorizedException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\ClientException;
use App\Core\HTTP\HTTPRequestBody;
use App\DTO\CurrentUserFormDTO;
use App\DTO\CurrentUserInfoDTO;
use App\Entity\UserAccount;
use App\Repository\UserRepository;

/**
 * @phpstan-import-type UserAccountArray from \App\Repository\UserRepository
 */
final class UserService
{
    private UserRepository $userRepository;

    public function __construct(
        private ?AuthUser $currentUser = null,
        private ?SSEHandler $sse = null,
    ) {
        $this->userRepository = new UserRepository($this);
    }

    /**
     * Crée un objet User à partir des données brutes de la base de données.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param UserAccountArray $rawData
     * 
     * @return UserAccount 
     */
    public function makeUserAccountFromDatabase(array $rawData): UserAccount
    {
        $user = (new UserAccount())
            ->setUid($rawData["uid"])
            ->setLogin($rawData["login"])
            ->setName($rawData["nom"])
            ->setCanLogin((bool) $rawData["can_login"])
            ->setRoles($rawData["roles"] ?: '{}')
            ->setStatus($rawData["statut"])
            ->setLastLogin($rawData["last_connection"])
            ->setComments($rawData["commentaire"])
            ->setHistory($rawData["historique"]);

        return $user;
    }

    /**
     * Crée un objet User à partir des données d'un formulaire.
     * 
     * @param HTTPRequestBody $requestBody 
     * 
     * @return UserAccount 
     */
    public function makeUserAccountFromForm(HTTPRequestBody $requestBody): UserAccount
    {
        $user = (new UserAccount())
            ->setUid($requestBody->getString('uid'))
            ->setLogin($requestBody->getString('login'))
            ->setName($requestBody->getString('nom'))
            ->setRoles($requestBody->getArray('roles', []))
            ->setStatus($requestBody->getString('statut'))
            ->setComments($requestBody->getString('commentaire'));

        return $user;
    }

    /**
     * Crée un DTO pour la mise à jour des informations de l'utilisateur courant.
     * 
     * @param HTTPRequestBody $requestBody Contenu du corps de la requête.
     * 
     * @return CurrentUserFormDTO 
     */
    public function makeCurrentUserFormDTO(HTTPRequestBody $requestBody): CurrentUserFormDTO
    {
        if (!$this->currentUser) {
            throw new UnauthorizedException("Utilisateur non connecté.");
        }

        if (!$this->currentUser->uid) {
            throw new UnauthorizedException("Impossible de récupérer l'identifiant de l'utilisateur.");
        }

        $currentUserFormDTO = (new CurrentUserFormDTO())
            ->setUid($this->currentUser->uid)
            ->setName($requestBody->getString('nom', $this->currentUser->name))
            ->setPassword($requestBody->getString('password', null));

        return $currentUserFormDTO;
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
     * @return UserAccount[]
     */
    public function getUserAccounts(bool $canLoginOnly = true): array
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
    public function getUserAccount(string $uid): ?UserAccount
    {
        return $this->userRepository->fetchUserByUid($uid);
    }

    /**
     * Crée un utilisateur.
     * 
     * @param HTTPRequestBody $input
     * 
     * @return UserAccount 
     */
    public function createUserAccount(HTTPRequestBody $input): UserAccount
    {
        $user = $this->makeUserAccountFromForm($input);

        if (!$user->getLogin()) {
            throw new BadRequestException("Le login est obligatoire.");
        }

        if (!$user->getName()) {
            throw new BadRequestException("Le nom est obligatoire.");
        }

        return $this->userRepository->createUser($user, $this->currentUser?->name ?? '');
    }

    /**
     * Met à jour un utilisateur.
     * 
     * @param string $uid 
     * @param HTTPRequestBody $input 
     * 
     * @return UserAccount 
     */
    public function updateUserAccount(string $uid, HTTPRequestBody $input): UserAccount
    {
        $user = $this->makeUserAccountFromForm($input)->setUid($uid);

        // Conservation du rôle admin : un utilisateur ne peut pas changer lui-même son statut admin
        if ($uid === $this->currentUser?->uid) {
            $user->setAdmin($this->currentUser->isAdmin());
        }

        return $this->userRepository->updateUser($user, $this->currentUser?->name ?? '');
    }

    public function deleteUserAccount(string $uid, string $adminName): void
    {
        $this->userRepository->deleteUser($uid, $adminName);

        $this->clearSessions($uid);
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
     *                uid: string|null,
     *                login: string|null,
     *                nom: string,
     *                roles: UserRoles,
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
            "statut" => $this->currentUser->status->value,
        ];
    }

    /**
     * Met à jour les informations de l'utilisateur courant.
     * 
     * @param HTTPRequestBody $input
     * 
     * @return CurrentUserInfoDTO
     */
    public function updateCurrentUser(HTTPRequestBody $input): CurrentUserInfoDTO
    {
        $currentUserDTO = $this->makeCurrentUserFormDTO($input);

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
