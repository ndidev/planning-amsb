<?php

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
        $error_string = static::formatError($e, "string");

        \error_log(PHP_EOL . $error_string);
    }

    /**
     * Gets all the info from an Exception.
     * 
     * @param \Throwable $e     Exception.
     * @param string    $format Output format (`array` or `string`).
     * 
     * @return array|string|null Exception information or null if no exception.
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

        switch ($format) {
            case 'array':
                return $arrayError;

            case 'string':
                return $stringError;

            default:
                return $arrayError;
        }
    }
}
