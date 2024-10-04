<?php

namespace App\Core\Exceptions\Client;

/**
 * Exception lancée lorsqu'une ressource n'est pas trouvée.
 */
class NotFoundException extends ClientException
{
    private const DEFAULT_MESSAGE = "Non trouvé";
    private const HTTP_STATUS = 404;

    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        public int $httpStatus = self::HTTP_STATUS,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }
}
