<?php

// Path: api/src/Entity/User.php

namespace App\Entity;

use App\Core\Auth\AccountStatus;
use App\Core\Auth\UserRoles;
use App\Core\Component\Module;

/**
 * @phpstan-type UserArray array{
 *                           uid: ?string,
 *                           login: string,
 *                           nom: string,
 *                           roles: UserRolesObject,
 *                           statut: string,
 *                           commentaire: string,
 *                           historique: string,
 *                           last_connection: ?string,
 *                         }
 * 
 * @phpstan-type UserRolesObject object{
 *                                 bois?: 0|1|2,
 *                                 vrac?: 0|1|2,
 *                                 consignation?: 0|1|2,
 *                                 chartering?: 0|1|2,
 *                                 config?: 0|1,
 *                                 tiers?: 0|1,
 *                                 admin?: 0|1,
 *                               }
 */
class UserAccount extends AbstractEntity
{
    /**
     * UID de l'utilisateur.
     */
    private ?string $uid = null;

    /**
     * Identifiant de l'utilisateur.
     */
    private string $login = '';

    /**
     * Hash du mot de passe.
     */
    private ?string $password = null;

    /**
     * `true` si le compte peut être utilisé pour se connecter (utilisateur normal).  
     * `false` si le compte ne peut pas être utilisé pour se connecter (ex: compte "kiosque" type Raspberry Pi). 
     */
    private bool $canLogin = false;

    /**
     * Nom de l'utilisateur.
     */
    private string $name = '';

    /**
     * Nombre de tentatives de connexion échouées.
     */
    private int $loginAttempts = 0;

    /**
     * Date de la dernière connexion.
     */
    private ?\DateTimeImmutable $lastLogin = null;

    /**
     * Statut du compte de l'utilisateur.
     */
    private AccountStatus $status = AccountStatus::INACTIVE;

    /**
     * Rôles de l'utilisateur.
     */
    private \stdClass $roles;

    /**
     * Commentaires sur l'utilisateur.
     */
    private string $comments = '';

    /**
     * Historique des actions effectuées par l'utilisateur.
     */
    private string $history = '';

    public function __construct(?string $uid = null)
    {
        if ($uid) {
            $this->uid = $uid;
        }
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(?string $uid): static
    {
        $this->uid = $uid;

        return $this;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function setCanLogin(bool $canLogin): static
    {
        $this->canLogin = $canLogin;

        return $this;
    }

    public function canLogin(): bool
    {
        return $this->canLogin;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLoginAttempts(): int
    {
        return $this->loginAttempts;
    }

    public function setLoginAttempts(int $loginAttempts): static
    {
        $this->loginAttempts = $loginAttempts;

        return $this;
    }

    /**
     * @param bool $sqlFormat 
     * 
     * @return \DateTimeImmutable|string|null 
     * 
     * @phpstan-return ($sqlFormat is false ? \DateTimeImmutable|null :string|null)
     */
    public function getLastLogin(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->lastLogin?->format("Y-m-d H:i:s");
        } else {
            return $this->lastLogin;
        }
    }

    public function setLastLogin(\DateTimeImmutable|string|null $lastLogin): static
    {
        if (is_string($lastLogin)) {
            $this->lastLogin = new \DateTimeImmutable($lastLogin);
        } else {
            $this->lastLogin = $lastLogin;
        }

        return $this;
    }

    public function getStatus(): AccountStatus
    {
        return $this->status;
    }

    public function setStatus(AccountStatus|string $status): static
    {
        if (is_string($status)) {
            $statusFromEnum = AccountStatus::tryFrom($status);

            if (null === $statusFromEnum) {
                throw new \InvalidArgumentException("Statut invalide");
            }

            $this->status = $statusFromEnum;
        } else {
            $this->status = $status;
        }

        return $this;
    }

    public function getRoles(): object
    {
        return $this->roles;
    }

    public function setRoles(\stdClass $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function setAdmin(bool $isAdmin): static
    {
        $this->roles->admin = (int) $isAdmin;

        return $this;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function setComments(string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function getHistory(): string
    {
        return $this->history;
    }

    public function setHistory(string $history): static
    {
        $this->history = $history;

        return $this;
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une rubrique.
     * 
     * @param ?Module::* $module Rubrique dont l'accès doit être vérifié.
     * 
     * @return bool `true` si l'utilisateur peut accéder à la rubrique, `false` sinon.
     */
    public function canAccess(?string $module): bool
    {
        // Accès à l'accueil et à l'écran individuel de modification du nom/mdp
        if ($module === null || $module === Module::USER) return true;

        return ($this->roles->$module ?? UserRoles::NONE) >= UserRoles::ACCESS;
    }

    /**
     * Vérifie si l'utilisateur peut éditer une rubrique.
     * 
     * @param ?Module::* $module Rubrique dont l'accès doit être vérifié.
     * 
     * @return bool `true` si l'utilisateur peut éditer la rubrique, `false` sinon.
     */
    public function canEdit(?string $module): bool
    {
        return ($this->roles->$module ?? UserRoles::NONE) >= UserRoles::EDIT;
    }

    /**
     * `true` si l'utilisateur est administrateur, `false` sinon.
     */
    public function isAdmin(): bool
    {
        return (bool) ($this->roles?->admin ?? false);
    }

    /**
     * @return array 
     * 
     * @phpstan-return UserArray
     */
    public function toArray(): array
    {
        return [
            "uid" => $this->getUid(),
            "login" => $this->getLogin(),
            "nom" => $this->getName(),
            "roles" => $this->getRoles(),
            "statut" => $this->getStatus()->value,
            "commentaire" => $this->getComments(),
            "historique" => $this->getHistory(),
            "last_connection" => $this->getLastLogin(true),
        ];
    }
}
