<?php

namespace App\Core\Exceptions\Server;

use App\Core\Exceptions\AppException;
use App\Core\HTTP\HTTPResponse;

/**
 * Exception d'erreur serveur.
 */
class ServerException extends AppException
{
    private const DEFAULT_MESSAGE = "Erreur serveur";
    private const HTTP_STATUS = HTTPResponse::HTTP_INTERNAL_SERVER_ERROR_500;

    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        public int $httpStatus = self::HTTP_STATUS,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }
}
