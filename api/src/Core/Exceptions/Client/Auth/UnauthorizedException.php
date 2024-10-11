<?php

namespace App\Core\Exceptions\Client\Auth;

use App\Core\HTTP\HTTPResponse;

/**
 * Exception générique entrainant une réponse 401 Unauthorized.
 */
class UnauthorizedException extends AuthException
{
    private const DEFAULT_MESSAGE = "Authentification nécessaire";
    private const HTTP_STATUS = HTTPResponse::HTTP_UNAUTHORIZED_401;

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($message, self::HTTP_STATUS);
    }
}
