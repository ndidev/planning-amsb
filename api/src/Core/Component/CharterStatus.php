<?php

// path: api/src/Core/Component/CharterStatus.php

namespace App\Core\Component;

enum CharterStatus: int
{
    case PENDING = 0;
    case CONFIRMED = 1;
    case CHARTERED = 2;
    case LOADED = 3;
    case COMPLETED = 4;

    // { value: 0, text: "Plannifié (pas confirmé)" },
    // { value: 1, text: "Confirmé par l'affréteur" },
    // { value: 2, text: "Affrété" },
    // { value: 3, text: "Chargement effectué" },
    // { value: 4, text: "Voyage terminé" },
}
