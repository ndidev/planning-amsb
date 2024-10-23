<?php

// Path: api/src/Core/Component/Modules.php

namespace App\Core\Component;

abstract class Module
{
    const BULK = "vrac";
    const CHARTERING = "chartering";
    const CONFIG = "config";
    const SHIPPING = "consignation";
    const TIMBER = "bois";
    const THIRD_PARTY = "tiers";
    const USER = "user";

    /**
     * Attempts to convert a module name to a constant.
     * 
     * @param string $temptativeModuleName
     */
    public static function tryFrom(string $temptativeModuleName): ?string
    {
        return match (strtolower($temptativeModuleName)) {
            "vrac" => self::BULK,
            "chartering" => self::CHARTERING,
            "config" => self::CONFIG,
            "consignation" => self::SHIPPING,
            "bois" => self::TIMBER,
            "tiers" => self::THIRD_PARTY,
            "user" => self::USER,
            default => null,
        };
    }
}
