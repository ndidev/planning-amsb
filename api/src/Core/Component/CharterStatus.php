<?php

// path: api/src/Core/Component/CharterStatus.php

namespace App\Core\Component;

enum CharterStatus: int
{
    /**
     * Plannifié (pas confirmé).
     */
    case PENDING = 0;

    /**
     * Confirmé par l'affréteur.
     */
    case CONFIRMED = 1;

    /**
     * Affrété.
     */
    case CHARTERED = 2;

    /**
     * Chargement effectué.
     */
    case LOADED = 3;

    /**
     * Voyage terminé.
     */
    case COMPLETED = 4;
}
