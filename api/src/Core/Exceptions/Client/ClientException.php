<?php

namespace App\Core\Exceptions\Client;

use App\Core\Exceptions\AppException;
use App\Core\HTTP\HTTPResponse;

/**
 * Exception de mauvaise requête client.
 */
class ClientException extends AppException
{
    private const DEFAULT_MESSAGE = "Erreur de requête";
    private const HTTP_STATUS = HTTPResponse::HTTP_BAD_REQUEST_400;

    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        public int $httpStatus = self::HTTP_STATUS,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }
}
