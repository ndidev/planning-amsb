<?php

// Path: api/src/Core/Exceptions/Client/Auth/AccountDeletedException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Client\Auth;

use App\Core\Auth\AccountStatus;

/**
 * Exception lancée lorsque le compte de l'utilisateur est supprimé.
 */
class AccountDeletedException extends AccountStatusException
{
    private const DEFAULT_MESSAGE = "Le compte est supprimé";

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($this->getStatut(), $message);
    }

    public function getStatut(): string
    {
        return AccountStatus::DELETED->value;
    }
}
