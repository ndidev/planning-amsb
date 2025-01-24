<?php

// Path: api/src/Entity/Stevedoring/StevedoringEquipment.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;

class StevedoringEquipment extends AbstractEntity
{
    use IdentifierTrait;

    #[Required(message: "Le type de l'équipement est requis.")]
    public string $type = '';

    #[Required(message: "La marque de l'équipement est requise.")]
    public string $brand = '';

    #[Required(message: "Le modèle de l'équipement est requis.")]
    public string $model = '';

    public string $internalNumber = '';

    public string $displayName {
        get => $this->brand . ' ' . $this->model . ' ' . $this->internalNumber;
    }

    public string $serialNumber = '';

    public string $comments = '';

    public bool $isActive = true;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'brand' => $this->brand,
            'model' => $this->model,
            'internalNumber' => $this->internalNumber,
            'serialNumber' => $this->serialNumber,
            'comments' => $this->comments,
            'isActive' => $this->isActive,
        ];
    }
}
