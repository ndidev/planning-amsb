<?php

// Path: api/src/Core/Component/CargoOperation.php

declare(strict_types=1);

namespace App\Core\Component;

abstract class CargoOperation
{
    const IMPORT = 'import';
    const EXPORT = 'export';

    /**
     * Attempts to convert a string to a constant.
     * 
     * @param ?string $temptativeOperationName
     * 
     * @phpstan-return ?self::*
     */
    public static function tryFrom(?string $temptativeOperationName): ?string
    {
        if (!$temptativeOperationName) {
            return null;
        }

        return match (\mb_strtolower($temptativeOperationName)) {
            self::IMPORT => self::IMPORT,
            self::EXPORT => self::EXPORT,
            default => null,
        };
    }
}
