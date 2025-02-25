<?php

// Path: api/src/Core/Component/Module.php

declare(strict_types=1);

namespace App\Core\Component;

abstract class Module
{
    const BULK = 'vrac';
    const CHARTERING = 'chartering';
    const CONFIG = 'config';
    const SHIPPING = 'consignation';
    const STEVEDORING = 'manutention';
    const STEVEDORING_STAFF = 'personnel';
    const TIMBER = 'bois';
    const THIRD_PARTY = 'tiers';
    const USER = 'user';

    /**
     * Attempts to convert a module name to a constant.
     * 
     * @param ?string $temptativeModuleName
     * 
     * @phpstan-return ?self::*
     */
    public static function tryFrom(?string $temptativeModuleName): ?string
    {
        if (!$temptativeModuleName) {
            return null;
        }

        return match (\mb_strtolower($temptativeModuleName)) {
            self::BULK => self::BULK,
            self::CHARTERING => self::CHARTERING,
            self::CONFIG => self::CONFIG,
            self::SHIPPING => self::SHIPPING,
            self::STEVEDORING => self::STEVEDORING,
            self::TIMBER => self::TIMBER,
            self::THIRD_PARTY => self::THIRD_PARTY,
            self::USER => self::USER,
            default => null,
        };
    }
}
