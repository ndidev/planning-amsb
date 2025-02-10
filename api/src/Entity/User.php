<?php

// Path: api/src/Entity/User.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Auth\AccountStatus;
use App\Core\Auth\UserRoles;
use App\Core\Component\DateUtils;
use App\Core\Component\Module;

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
class User extends AbstractEntity
{
    /**
     * UID de l'utilisateur.
     */
    public ?string $uid = null;

    /**
     * Identifiant de l'utilisateur.
     */
    public string $login = '';

    /**
     * Hash du mot de passe.
     */
    public ?string $passwordHash = null;

    /**
     * `true` si le compte peut être utilisé pour se connecter (utilisateur normal).  
     * `false` si le compte ne peut pas être utilisé pour se connecter (ex: compte "kiosque" type Raspberry Pi). 
     */
    public bool $canLogin = false;

    /**
     * Nom de l'utilisateur.
     */
    public string $name = '';

    /**
     * Nombre de tentatives de connexion échouées.
     */
    public int $loginAttempts = 0;

    /**
     * Date de la dernière connexion.
     */
    public ?\DateTimeImmutable $lastLogin = null {
        set(\DateTimeImmutable|string|null $lastLoginDatetime) => DateUtils::makeDateTimeImmutable($lastLoginDatetime);
    }

    /**
     * Statut du compte de l'utilisateur.
     * 
     * @phpstan-var AccountStatus::* $status
     */
    public string $status = AccountStatus::INACTIVE {
        /** @param string $status */
        set(string $status) {
            $statusFromEnum = AccountStatus::tryFrom($status);

            if (null === $statusFromEnum) {
                throw new \InvalidArgumentException("Statut invalide");
            }

            $this->status = $statusFromEnum;
        }
    }

    /**
     * Rôles de l'utilisateur.
     */
    public UserRoles $roles {
        /** @param UserRoles|string|array<mixed> $roles */
        set(UserRoles|string|array $roles) {
            if ($roles instanceof UserRoles) {
                $this->roles = $roles;
            } elseif (\is_string($roles)) {
                $this->roles = $this->roles->fillFromJsonString($roles);
            } else {
                $this->roles = $this->roles->fillFromArray($roles);
            }
        }
    }

    public bool $isAdmin {
        get => $this->roles->admin >= UserRoles::ACCESS;
    }

    /**
     * Commentaires sur l'utilisateur.
     */
    public string $comments = '';

    /**
     * Historique des actions effectuées par l'utilisateur.
     */
    public string $history = '';

    public function __construct(?string $uid = null)
    {
        if ($uid) {
            $this->uid = $uid;
        }

        $this->roles = new UserRoles();
    }

    public function getSqlLastLogin(): ?string
    {
        return $this->lastLogin?->format("Y-m-d H:i:s");
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une rubrique.
     * 
     * @param ?string $module Rubrique dont l'accès doit être vérifié.
     * 
     * @return bool `true` si l'utilisateur peut accéder à la rubrique, `false` sinon.
     */
    public function canAccess(?string $module): bool
    {
        // Accès à l'accueil et à l'écran individuel de modification du nom/mdp
        if ($module === null || $module === Module::USER) return true;

        return $this->roles->$module >= UserRoles::ACCESS;
    }

    /**
     * Vérifie si l'utilisateur peut éditer une rubrique.
     * 
     * @param ?string $module Rubrique dont l'accès doit être vérifié.
     * 
     * @return bool `true` si l'utilisateur peut éditer la rubrique, `false` sinon.
     */
    public function canEdit(?string $module): bool
    {
        return $this->roles->$module >= UserRoles::EDIT;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "uid" => $this->uid,
            "login" => $this->login,
            "nom" => $this->name,
            "roles" => $this->roles->toArray(),
            "statut" => $this->status,
            "commentaire" => $this->comments,
            "historique" => $this->history,
            "last_connection" => $this->getSqlLastLogin(),
        ];
    }
}
