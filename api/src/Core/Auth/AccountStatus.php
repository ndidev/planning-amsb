<?php

// Path: api/src/Core/Auth/AccountStatus.php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Exceptions\Client\Auth\AccountStatusException;

/**
 * Status of user accounts.
 */
abstract class AccountStatus
{
    /**
     * Active account.
     * 
     * The account can be used normally.
     */
    const ACTIVE = "active";

    /**
     * Account awaiting activation.
     * 
     * The account has been created but the password
     * hasn't yet been initialized by the user.
     */
    const PENDING = "pending";

    /**
     * Deactivated account.
     * 
     * The account was purposely deactivated by an administrator
     * because it is no longer used (eg: former staff who left the company).
     * 
     * Only an administrator can reactivate the account.
     */
    const INACTIVE = "inactive";

    /**
     * Locked account.
     * 
     * The account has been locked due to one of the following reasons:
     *  - the number of failed connection attempts has been reached
     * 
     * Only an administrator can unlock the account.
     */
    const LOCKED = "locked";

    /**
     * Deleted account.
     * 
     * The account has been soft-deleted.
     * 
     * It is preserved to keep the history of operations but cannot be recovered.
     */
    const DELETED = "deleted";

    /**
     * Attempts to convert a status to a constant.
     * 
     * Returns null if the status is not recognized.
     * 
     * @param ?string $temptativeStatus
     * 
     * @phpstan-return ?self::*
     */
    public static function tryFrom(?string $temptativeStatus): ?string
    {
        if (!$temptativeStatus) {
            return null;
        }

        return match (strtolower($temptativeStatus)) {
            self::ACTIVE => self::ACTIVE,
            self::PENDING => self::PENDING,
            self::INACTIVE => self::INACTIVE,
            self::LOCKED => self::LOCKED,
            self::DELETED => self::DELETED,
            default => null,
        };
    }

    /**
     * Attempts to convert a status to a constant.
     * 
     * Throws an exception if the status is not recognized.
     * 
     * @param ?string $temptativeStatus
     * 
     * @phpstan-return self::*
     * 
     * @throws AccountStatusException If the status is not recognized.
     */
    public static function from(?string $temptativeStatus): string
    {
        $status = self::tryFrom($temptativeStatus);

        if (!$status) {
            if (null === $temptativeStatus) {
                $temptativeStatus = 'null';
            }

            throw new AccountStatusException($temptativeStatus);
        }

        return $status;
    }
}
