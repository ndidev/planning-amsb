<?php

// Path: api/src/Core/Component/SseEventNames.php

declare(strict_types=1);

namespace App\Core\Component;

final class SseEventNames
{
    public const USER = "user";

    public const USER_ACCOUNT = "admin/users";
    public const ADMIN_SESSIONS = "admin/sessions";

    public const BULK_APPOINTMENT = "vrac/rdvs";
    public const BULK_PRODUCT = "vrac/produits";

    public const CHARTERING_CHARTER = "chartering/charters";

    public const CONFIG_AGENCY = "config/agence";
    public const CONFIG_CHART_DATUM = "config/cotes";
    public const CONFIG_INFO_BANNER = "config/bandeau-info";
    public const CONFIG_PDF_CONFIG = "config/pdf";
    public const CONFIG_QUICK_APPOINTMENT_ADD = "config/ajouts-rapides";

    public const SHIPPING_CALL = "consignation/escales";

    public const STEVEDORING_SHIP_REPORT = "manutention/rapports-navires";
    public const STEVEDORING_EQUIPMENT = "manutention/equipements";
    public const STEVEDORING_STAFF = "manutention/personnel";
    public const STEVEDORING_TEMP_WORK_HOURS = "manutention/heures-interimaires";

    public const THIRD_PARTY = "tiers";

    public const TIMBER_APPOINTMENT = "bois/rdvs";

    public const TIDES = "marees";
}
