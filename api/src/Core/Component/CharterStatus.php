<?php

// path: api/src/Core/Component/CharterStatus.php

namespace App\Core\Component;

abstract class CharterStatus
{
    /**
     * Plannifié (pas confirmé).
     */
    const PENDING = 0;

    /**
     * Confirmé par l'affréteur.
     */
    const CONFIRMED = 1;

    /**
     * Affrété.
     */
    const CHARTERED = 2;

    /**
     * Chargement effectué.
     */
    const LOADED = 3;

    /**
     * Voyage terminé.
     */
    const COMPLETED = 4;

    /**
     * Attempts to convert an integer to a status constant.
     * 
     * @param int $temptativeStatus
     * 
     * @phpstan-return self::*
     */
    public static function tryFrom(int $temptativeStatus): int
    {
        return match ($temptativeStatus) {
            0 => self::PENDING,
            1 => self::CONFIRMED,
            2 => self::CHARTERED,
            3 => self::LOADED,
            4 => self::COMPLETED,
            default => self::PENDING,
        };
    }
}
