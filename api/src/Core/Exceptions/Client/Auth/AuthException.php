<?php

namespace App\Core\Exceptions\Client\Auth;

use App\Core\Exceptions\Client\ClientException;

/**
 * Exception générique d'authentification de l'utilisateur.
 */
class AuthException extends ClientException
{
    private const DEFAULT_MESSAGE = "Erreur d'authentification";
    private const HTTP_STATUS = 400;

    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        public int $http_status = self::HTTP_STATUS,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, $http_status, $previous);
    }
}
