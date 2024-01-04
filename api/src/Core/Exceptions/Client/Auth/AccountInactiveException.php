<?php

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
        parent::__construct($this->getStatut(), $message);
    }

    public function getStatut()
    {
        return AccountStatus::INACTIVE->value;
    }
}
