<?php

// Path: api/src/Core/Component/Modules.php

namespace App\Core\Component;

enum Module: string
{
    case BULK = "vrac";
    case CHARTERING = "chartering";
    case CONFIG = "config";
    case SHIPPING = "consignation";
    case TIMBER = "bois";
    case THIRD_PARTY = "tiers";
    case USER = "user";
}
