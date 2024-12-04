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
    private string $type = '';

    #[Required(message: "La marque de l'équipement est requise.")]
    private string $brand = '';

    #[Required(message: "Le modèle de l'équipement est requis.")]
    private string $model = '';

    private string $internalNumber = '';

    private string $serialNumber = '';

    private string $comments = '';

    private bool $isActive = true;

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setInternalNumber(string $internalNumber): static
    {
        $this->internalNumber = $internalNumber;

        return $this;
    }

    public function getInternalNumber(): string
    {
        return $this->internalNumber;
    }

    public function setSerialNumber(string $serialNumber): static
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    public function setComments(string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'brand' => $this->getBrand(),
            'model' => $this->getModel(),
            'internalNumber' => $this->getInternalNumber(),
            'serialNumber' => $this->getSerialNumber(),
            'comments' => $this->getComments(),
            'isActive' => $this->isActive(),
        ];
    }
}
