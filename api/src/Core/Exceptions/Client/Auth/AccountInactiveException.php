<?php

// Path: api/src/Core/Exceptions/Client/Auth/AccountInactiveException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Client\Auth;

use App\Core\Auth\AccountStatus;

/**
 * Exception lancée lorsque le compte de l'utilisateur est suspendu/désactivé.
 */
class AccountInactiveException extends AccountStatusException
{
    private const DEFAULT_MESSAGE = "Le compte est désactivé";

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($this->getStatus(), $message);
    }

    #[\Override]
    public function getStatus(): string
    {
        return AccountStatus::INACTIVE;
    }
}
