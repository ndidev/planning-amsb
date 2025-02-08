<?php

// Path: api/src/Core/Exceptions/Server/DB/DBException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Server\DB;

use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\HTTPResponse;

/**
 * Exception générique de base de données.
 */
class DBException extends ServerException
{
    private const DEFAULT_MESSAGE = "Erreur générique de la base de données";
    private const HTTP_STATUS = HTTPResponse::HTTP_INTERNAL_SERVER_ERROR_500;

    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        public int $httpStatus = self::HTTP_STATUS,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }
}
