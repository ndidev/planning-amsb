<?php

// Path: api/src/Core/Exceptions/Client/Auth/InvalidAccountException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Client\Auth;

/**
 * Exception lancée lorsque le compte utilisé n'existe pas.
 */
class InvalidAccountException extends UnauthorizedException
{
    private const DEFAULT_MESSAGE = "L'utilisateur n'existe pas";

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($message);
    }
}
