<?php

// Path: api/src/Core/Logger/ErrorLogger.php

declare(strict_types=1);

namespace App\Core\Logger;

include_once __DIR__ . "/../Functions/array_stringify.php";

use function App\Core\Functions\array_stringify;

final class ErrorLogger
{
    /**
     * Custom error logger.
     * 
     * @param \Throwable $e Error
     */
    public static function log(\Throwable $e): void
    {
        /** @var string  $errorString */
        $errorString = static::formatError($e, "string");

        \error_log(PHP_EOL . $errorString);
    }

    /**
     * Gets all the info from an Exception.
     * 
     * @param ?\Throwable $e      Exception.
     * @param string      $format Output format (`array` or `string`).
     * 
     * @return array<string, mixed>|string|null Exception information or null if no exception.
     */
    private static function formatError(?\Throwable $e, string $format = "array"): array|string|null
    {
        if (!($e instanceof \Throwable)) {
            return null;
        }

        $arrayError = [
            "code" => $e->getCode(),
            "message" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "previous" => static::formatError($e->getPrevious()),
            "trace" => $e->getTrace()
        ];

        $stringError = array_stringify($arrayError);

        return match ($format) {
            "array" => $arrayError,
            "string" => $stringError,
            default => $arrayError,
        };
    }
}
