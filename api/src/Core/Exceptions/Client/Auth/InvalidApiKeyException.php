<?php

// Path: api/src/Core/Exceptions/Client/Auth/InvalidApiKeyException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Client\Auth;

/**
 * Exception lancée lorsque la clé d'API n'est pas valide.
 */
class InvalidApiKeyException extends UnauthorizedException
{
    private const DEFAULT_MESSAGE = "La clé d'API n'existe pas ou n'est plus active";

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($message);
    }
}
