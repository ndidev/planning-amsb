<?php

// Path: api/src/Core/Exceptions/Client/Auth/AccountStatusException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Client\Auth;

/**
 * Exception lancée lorsque le compte de l'utilisateur n'est pas actif.
 */
class AccountStatusException extends ForbiddenException
{
    private const DEFAULT_MESSAGE = "Le compte n'est pas actif";

    public function __construct(private string $status = "", string $message = self::DEFAULT_MESSAGE)
    {
        if ($message === self::DEFAULT_MESSAGE && $status) {
            $message = self::DEFAULT_MESSAGE . ". Statut : $status";
        }

        parent::__construct($message);
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
