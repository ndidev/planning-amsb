<?php

namespace App\Core\Exceptions\Client\Auth;

/**
 * Exception lancée lorsqu'un utilisateur essaie d'accéder
 * à une rubrique non autorisée.
 */
class AccessException extends ForbiddenException
{
    private const DEFAULT_MESSAGE = "Accès non autorisé";

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($message);
    }
}
