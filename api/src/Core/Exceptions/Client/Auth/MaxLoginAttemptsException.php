<?php

// Path: api/src/Core/Exceptions/Client/Auth/MaxLoginAttemptsException.php

declare(strict_types=1);

namespace App\Core\Exceptions\Client\Auth;

/**
 * Exception lancée lorsque le nombre de tentatives de connexions a été atteint.
 */
class MaxLoginAttemptsException extends AccountLockedException
{
    private const DEFAULT_MESSAGE = "Le compte est bloqué : nombre de tentatives de connexions dépassé";

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($message);
    }
}
