<?php

// Path: api/src/Core/Auth/UserRoles.php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Interfaces\Arrayable;

/**
 * List of user roles.
 * 
 * @property self::NONE|self::ACCESS $admin
 */
final class UserRoles implements Arrayable, \JsonSerializable
{
    /**
     * None.
     *
     * The user cannot access this part of the application.
     */
    public const NONE = 0;

    /**
     * View only.
     *
     * The user can view the resources of this part of the application but cannot edit them.  
     * The user can access parts of the application of type "Access/No access".
     */
    public const ACCESS = 1;

    /**
     * Edit allowed.
     *
     * The user can view and edit the resources of this part of the application.
     */
    public const EDIT = 2;

    /** @var array<string, self::*> */
    private array $roles = [];

    public function fillFromJsonString(string $roles): self
    {
        $rolesArray = \json_decode($roles, true);

        if (!\is_array($rolesArray)) {
            throw new \InvalidArgumentException('Invalid JSON string.');
        }

        foreach ($rolesArray as $role => $value) {
            if (!\is_string($role)) continue;

            if (!\is_numeric($value)) continue;

            $safeValue = match ((int) $value) {
                self::NONE, self::ACCESS, self::EDIT => (int) $value,
                default => self::NONE,
            };

            $this->roles[$role] = $safeValue;
        }

        return $this;
    }

    /**
     * @param array<mixed> $roles 
     */
    public function fillFromArray(array $roles): self
    {
        foreach ($roles as $role => $value) {
            if (!\is_string($role)) continue;

            $intValue = \is_scalar($value) ? (int) $value : null;

            if (null === $intValue) continue;

            $safeValue = match ($intValue) {
                self::NONE, self::ACCESS, self::EDIT => $intValue,
                default => self::NONE,
            };

            $this->roles[$role] = $safeValue;
        }

        return $this;
    }

    public function __get(string $role): int
    {
        return $this->roles[$role] ?? self::NONE;
    }

    /**
     * @param string  $role 
     * @param self::* $value 
     */
    public function __set(string $role, int $value): void
    {
        $this->roles[$role] = $value;
    }

    public function toArray(): array
    {
        return $this->roles;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
