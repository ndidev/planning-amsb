<?php

// Path: api/src/Core/Exceptions/Client/Auth/ForbiddenException.php

namespace App\Core\Exceptions\Client\Auth;

use App\Core\HTTP\HTTPResponse;

/**
 * Exception générique entrainant une réponse 403 Forbidden.
 */
class ForbiddenException extends AuthException
{
    private const DEFAULT_MESSAGE = "Accès interdit";
    private const HTTP_STATUS = HTTPResponse::HTTP_FORBIDDEN_403;

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($message, self::HTTP_STATUS);
    }
}
