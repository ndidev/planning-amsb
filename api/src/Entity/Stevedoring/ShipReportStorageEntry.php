<?php

// Path: api/src/Entity/Stevedoring/ShipReportStorageEntry.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\PositiveOrNullNumber;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;
use App\Entity\Shipping\ShippingCallCargo;

class ShipReportStorageEntry extends AbstractEntity
{
    use IdentifierTrait;

    public ?ShipReport $report = null;

    #[Required("La marchandise est obligatoire.")]
    public ?ShippingCallCargo $cargo = null;

    #[Required("Le nom du magasin est obligatoire.")]
    public string $storageName = '';

    #[PositiveOrNullNumber("Le tonnage doit être un nombre positif ou null.")]
    public float $tonnage = 0;

    #[PositiveOrNullNumber("Le volume doit être un nombre positif ou null.")]
    public float $volume = 0;

    #[PositiveOrNullNumber("Le nombre d'unités doit être positif ou null.")]
    public int $units = 0;

    public string $comments = '';

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cargoId' => $this->cargo?->id,
            'storageName' => $this->storageName,
            'tonnage' => $this->tonnage,
            'volume' => $this->volume,
            'units' => $this->units,
            'comments' => $this->comments,
        ];
    }
}
