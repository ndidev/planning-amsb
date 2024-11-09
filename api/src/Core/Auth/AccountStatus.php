<?php

// Path: api/src/Core/Auth/AccountStatus.php

declare(strict_types=1);

namespace App\Core\Auth;

/**
 * Status of user accounts.
 */
enum AccountStatus: string
{
    /**
     * Active account.
     * 
     * The account can be used normally.
     */
    case ACTIVE = "active";

    /**
     * Account awaiting activation.
     * 
     * The account has been created but the password
     * hasn't yet been initialized by the user.
     */
    case PENDING = "pending";

    /**
     * Deactivated account.
     * 
     * The account was purposely deactivated by an administrator
     * because it is no longer used (eg: former staff who left the company).
     * 
     * Only an administrator can reactivate the account.
     */
    case INACTIVE = "inactive";

    /**
     * Locked account.
     * 
     * The account has been locked due to one of the following reasons:
     *  - the number of failed connection attempts has been reached
     * 
     * Only an administrator can unlock the account.
     */
    case LOCKED = "locked";

    /**
     * Deleted account.
     * 
     * The account has been soft-deleted.
     * 
     * It is preserved to keep the history of operations but cannot be recovered.
     */
    case DELETED = "deleted";
}
