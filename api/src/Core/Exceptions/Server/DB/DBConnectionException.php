<?php

// Path: api/src/Core/Exceptions/Server/DB/DBConnectionException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Server\DB;

use App\Core\HTTP\HTTPResponse;

/**
 * Exception en cas d'erreur de connexion à la base de données.
 */
class DBConnectionException extends DBException
{
    private const DEFAULT_MESSAGE = "Erreur de connexion à la base de données";
    private const HTTP_STATUS = HTTPResponse::HTTP_INTERNAL_SERVER_ERROR_500;

    public function __construct(
        string $message = self::DEFAULT_MESSAGE,
        \Throwable|null $previous = null
    ) {
        parent::__construct($message, self::HTTP_STATUS, $previous);
    }
}
