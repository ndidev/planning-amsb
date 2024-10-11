<?php

namespace App\Core\Exceptions;

use App\Core\HTTP\HTTPResponse;

/**
 * Exception générique de l'application.
 * 
 * Toutes les erreurs lancées depuis l'application doivent dériver de cette classe.
 */
abstract class AppException extends \Exception
{
    private const DEFAULT_MESSAGE = "Erreur générique de l'application";
    private const HTTP_STATUS = HTTPResponse::HTTP_INTERNAL_SERVER_ERROR_500;

    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        public int $httpStatus = self::HTTP_STATUS,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
