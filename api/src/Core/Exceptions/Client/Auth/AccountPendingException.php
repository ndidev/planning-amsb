<?php

// Path: api/src/Core/Exceptions/Client/Auth/AccountPendingException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Client\Auth;

use App\Core\Auth\AccountStatus;

/**
 * Exception lancée lorsque le compte de l'utilisateur n'est pas encore activé.
 */
class AccountPendingException extends AccountStatusException
{
    private const DEFAULT_MESSAGE = "Le compte n'est pas activé";

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($this->getStatus(), $message);
    }

    #[\Override]
    public function getStatus(): string
    {
        return AccountStatus::PENDING;
    }
}
