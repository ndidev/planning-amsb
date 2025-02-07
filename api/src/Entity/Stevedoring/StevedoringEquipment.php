<?php

// Path: api/src/Entity/Stevedoring/StevedoringEquipment.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Array\ArrayHandler;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;

/**
 * @phpstan-type StevedoringEquipmentArray array{
 *                                           id: ?int,
 *                                           type: string,
 *                                           brand: string,
 *                                           model: string,
 *                                           internalNumber: string,
 *                                           serialNumber: string,
 *                                           comments: string,
 *                                           isActive: bool
 *                                         }
 */
class StevedoringEquipment extends AbstractEntity implements \Stringable
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

    public bool $isCrane {
        get => \array_any(["pelle", "grue"], fn(string $match) => \str_contains(\mb_strtolower($this->type), $match));
    }

    public string $comments = '';

    public bool $isActive = true;

    /**
     * @param ArrayHandler|StevedoringEquipmentArray|null $data
     */
    public function __construct(ArrayHandler|array|null $data = null)
    {
        if (null === $data) {
            return;
        }

        $dataAH = $data instanceof ArrayHandler ? $data : new ArrayHandler($data);

        $this->id = $dataAH->getInt('id');
        $this->type = $dataAH->getString('type');
        $this->brand = $dataAH->getString('brand');
        $this->model = $dataAH->getString('model');
        $this->internalNumber = $dataAH->getString('internalNumber');
        $this->serialNumber = $dataAH->getString('serialNumber');
        $this->comments = $dataAH->getString('comments');
        $this->isActive = $dataAH->getBool('isActive');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'brand' => $this->brand,
            'model' => $this->model,
            'internalNumber' => $this->internalNumber,
            'serialNumber' => $this->serialNumber,
            'displayName' => $this->displayName,
            'comments' => $this->comments,
            'isCrane' => $this->isCrane,
            'isActive' => $this->isActive,
        ];
    }

    public function __toString(): string
    {
        return $this->displayName;
    }
}
