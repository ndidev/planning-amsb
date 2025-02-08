<?php

// Path: api/src/Core/Exceptions/Client/NotFoundException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Client;

use App\Core\HTTP\HTTPResponse;

/**
 * Exception lancée lorsqu'une ressource n'est pas trouvée.
 */
class NotFoundException extends ClientException
{
    private const DEFAULT_MESSAGE = "Non trouvé";
    private const HTTP_STATUS = HTTPResponse::HTTP_NOT_FOUND_404;

    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        public int $httpStatus = self::HTTP_STATUS,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }
}
