<?php

// Path: api/src/Entity/Config/TimberQuickAppointmentAdd.php

declare(strict_types=1);

namespace App\Entity\Config;

use App\Core\Validation\Constraints\Required;
use App\Core\Component\Module;
use App\Entity\ThirdParty;

/**
 * @phpstan-type TimberQuickAppointmentAddArray array{
 *                                                id: int|null,
 *                                                fournisseur: int,
 *                                                transporteur: int,
 *                                                affreteur: int,
 *                                                chargement: int,
 *                                                client: int,
 *                                                livraison: int,
 *                                              }
 */
final class TimberQuickAppointmentAdd extends QuickAppointmentAdd
{
    #[Required("Le fournisseur est obligatoire.")]
    public ?ThirdParty $supplier = null;

    public ?ThirdParty $carrier = null;

    public ?ThirdParty $charterer = null;

    #[Required("Le lieu chargement est obligatoire.")]
    public ?ThirdParty $loading = null;

    #[Required("Le client est obligatoire.")]
    public ?ThirdParty $customer = null;

    public ?ThirdParty $delivery = null;

    public function __construct()
    {
        $this->module = Module::TIMBER;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "module" => $this->module,
            "fournisseur" => $this->supplier?->id,
            "transporteur" => $this->carrier?->id,
            "affreteur" => $this->charterer?->id,
            "chargement" => $this->loading?->id,
            "client" => $this->customer?->id,
            "livraison" => $this->delivery?->id,
        ];
    }
}
