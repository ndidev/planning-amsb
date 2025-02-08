<?php

// Path: api/src/Core/Exceptions/Client/ValidationException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Client;

use App\Core\HTTP\HTTPResponse;

/**
 * Exception lancée lorsqu'un formulaire contient des erreurs.
 */
class ValidationException extends ClientException
{
    private const DEFAULT_MESSAGE = "Le formulaire contient des erreurs";
    private const HTTP_STATUS = HTTPResponse::HTTP_BAD_REQUEST_400;

    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        string $errors = '',
        public int $httpStatus = self::HTTP_STATUS,
        \Throwable|null $previous = null
    ) {
        if ('' !== $errors) {
            $message = $message . ' : ' . PHP_EOL . $errors;
        }

        parent::__construct($message, $httpStatus, $previous);
    }
}
