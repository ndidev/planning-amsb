<?php

namespace App\Core\Exceptions\Client\Auth;

/**
 * Exception lancée lorsqu'un utilisateur non admin essaie d'accéder
 * à un espace administrateur.
 */
class AdminException extends ForbiddenException
{
    private const DEFAULT_MESSAGE = "L'utilisateur n'est pas administrateur";

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($message);
    }
}
