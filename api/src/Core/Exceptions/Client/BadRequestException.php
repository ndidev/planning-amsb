<?php

// Path: api/src/Core/Exceptions/Client/BadRequestException.php

namespace App\Core\Exceptions\Client;

use App\Core\HTTP\HTTPResponse;

/**
 * Exception lancée lorsqu'une requête est mal formée.
 */
class BadRequestException extends ClientException
{
    private const DEFAULT_MESSAGE = "Requête mal formée";
    private const HTTP_STATUS = HTTPResponse::HTTP_BAD_REQUEST_400;

    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        public int $httpStatus = self::HTTP_STATUS,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }
}
