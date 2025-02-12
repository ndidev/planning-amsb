<?php

// Path: api/src/Service/UserService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Array\Environment;
use App\Core\Auth\UserAuthenticator;
use App\Core\Auth\UserRoles;
use App\Core\Exceptions\Client\Auth\UnauthorizedException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\ClientException;
use App\Core\HTTP\HTTPRequestBody;
use App\DTO\CurrentUserFormDTO;
use App\DTO\CurrentUserInfoDTO;
use App\Entity\User;
use App\Repository\UserRepository;

/**
 * @phpstan-import-type UserAccountArray from \App\Entity\User
 */
final class UserService
{
    private UserRepository $userRepository;

    public function __construct(private ?User $currentUser = null)
    {
        $this->userRepository = new UserRepository($this);
    }

    /**
     * Crée un objet User à partir des données brutes de la base de données.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param UserAccountArray $rawData
     * 
     * @return User 
     */
    public function makeUserAccountFromDatabase(array $rawData): User
    {
        $rawDataAH = new ArrayHandler($rawData);

        $user = new User();
        $user->uid = $rawDataAH->getString('uid');
        $user->login = $rawDataAH->getString('login');
        $user->name = $rawDataAH->getString('nom');
        $user->canLogin = $rawDataAH->getBool('can_login');
        $user->roles = $rawDataAH->getString('roles', '{}');
        $user->status = $rawDataAH->getString('statut');
        $user->lastLogin = $rawDataAH->getDatetime('last_connection');
        $user->comments = $rawDataAH->getString('commentaire');
        $user->history = $rawDataAH->getString('historique');

        return $user;
    }

    /**
     * Crée un objet User à partir des données d'un formulaire.
     * 
     * @param HTTPRequestBody $requestBody 
     * 
     * @return User 
     */
    public function makeUserAccountFromForm(HTTPRequestBody $requestBody): User
    {
        $user = new User();
        $user->uid = $requestBody->getString('uid');
        $user->login = $requestBody->getString('login');
        $user->name = $requestBody->getString('nom');
        $user->roles = $requestBody->getArray('roles', []);
        $user->status = $requestBody->getString('statut');
        $user->comments = $requestBody->getString('commentaire');

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

        $currentUserFormDTO = new CurrentUserFormDTO();
        $currentUserFormDTO->uid = $this->currentUser->uid;
        $currentUserFormDTO->name = $requestBody->getString('nom', $this->currentUser->name);
        $currentUserFormDTO->password = $requestBody->getString('password', null);

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
     * @return User[]
     */
    public function getUserAccounts(bool $canLoginOnly = true): array
    {
        $users = $this->userRepository->fetchAllUsers();

        if ($canLoginOnly) {
            $users = array_values(array_filter($users, fn($user) => $user->canLogin));
        }

        return $users;
    }

    /**
     * Récupère un utilisateur.
     * 
     * @param string $uid Identifiant de l'utilisateur.
     */
    public function getUserAccount(string $uid): ?User
    {
        return $this->userRepository->fetchUserByUid($uid);
    }

    /**
     * Crée un utilisateur.
     * 
     * @param HTTPRequestBody $input
     * 
     * @return User 
     */
    public function createUserAccount(HTTPRequestBody $input): User
    {
        $user = $this->makeUserAccountFromForm($input);

        if (!$user->login) {
            throw new BadRequestException("Le login est obligatoire.");
        }

        if (!$user->name) {
            throw new BadRequestException("Le nom est obligatoire.");
        }

        return $this->userRepository->createUser($user, $this->currentUser->name ?? '');
    }

    /**
     * Met à jour un utilisateur.
     * 
     * @param string $uid 
     * @param HTTPRequestBody $input 
     * 
     * @return User 
     */
    public function updateUserAccount(string $uid, HTTPRequestBody $input): User
    {
        $user = $this->makeUserAccountFromForm($input);
        $user->uid = $uid;

        // Conservation du rôle admin : un utilisateur ne peut pas changer lui-même son statut admin
        if ($uid === $this->currentUser?->uid) {
            $user->roles->admin = (int) $this->currentUser->isAdmin;
        }

        return $this->userRepository->updateUser($user, $this->currentUser->name ?? '');
    }

    public function deleteUserAccount(string $uid, string $adminName): void
    {
        $this->userRepository->deleteUser($uid, $adminName);

        new UserAuthenticator()->clearSessions($uid);
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
            "statut" => $this->currentUser->status,
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

        $minPasswordLength = Environment::getInt('AUTH_LONGUEUR_MINI_PASSWORD');

        if (
            $currentUserDTO->password
            && \strlen($currentUserDTO->password) < $minPasswordLength
        ) {
            throw new ClientException("Le mot de passe doit contenir au moins {$minPasswordLength} caractères.");
        }

        $updatedUser = $this->userRepository->updateCurrentUser($currentUserDTO);

        return new CurrentUserInfoDTO($updatedUser);
    }
}
