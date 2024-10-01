<?php

// Path: api/src/Entity/Shipping/ShippingCallCargo.php

namespace App\Entity\Shipping;

use App\Core\Component\CargoOperation;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Service\ShippingService;

class ShippingCallCargo extends AbstractEntity
{
    use IdentifierTrait;

    private ?ShippingCall $shippingCall = null;
    private string $cargoName = '';
    private string $customer = '';
    private CargoOperation $operation = CargoOperation::IMPORT;
    private bool $approximate = false;
    private ?float $blTonnage = null;
    private ?float $blVolume = null;
    private ?int $blUnits = null;
    private ?float $outturnTonnage = null;
    private ?float $outturnVolume = null;
    private ?int $outturnUnits = null;

    public function getShippingCall(): ?ShippingCall
    {
        return $this->shippingCall;
    }

    public function setShippingCall(ShippingCall|int|null $shippingCall): static
    {
        if (is_int($shippingCall)) {
            $this->shippingCall = (new ShippingService())->getShippingCall($shippingCall);
        } else {
            $this->shippingCall = $shippingCall;
        }
        $this->shippingCall = $shippingCall;

        return $this;
    }

    public function getCargoName(): string
    {
        return $this->cargoName;
    }

    public function setCargoName(string $cargoName): static
    {
        $this->cargoName = $cargoName;

        return $this;
    }

    public function getCustomer(): string
    {
        return $this->customer;
    }

    public function setCustomer(string $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getOperation(bool $value = false): CargoOperation|string
    {
        if (true === $value) {
            return $this->operation->value;
        }

        return $this->operation;
    }

    public function setOperation(CargoOperation|string $operation): static
    {
        if (is_string($operation)) {
            $this->operation = CargoOperation::tryFrom($operation);

            if (null === $this->operation) {
                throw new \InvalidArgumentException("Opération invalide");
            }
        } else {
            $this->operation = $operation;
        }

        return $this;
    }

    public function isApproximate(): bool
    {
        return $this->approximate;
    }

    public function setApproximate(bool $approximate): static
    {
        $this->approximate = $approximate;

        return $this;
    }

    public function getBlTonnage(): ?float
    {
        return $this->blTonnage;
    }

    public function setBlTonnage(?float $blTonnage): static
    {
        $this->blTonnage = $blTonnage;

        return $this;
    }

    public function getBlVolume(): ?float
    {
        return $this->blVolume;
    }

    public function setBlVolume(?float $blVolume): static
    {
        $this->blVolume = $blVolume;

        return $this;
    }

    public function getBlUnits(): ?int
    {
        return $this->blUnits;
    }

    public function setBlUnits(?int $blUnits): static
    {
        $this->blUnits = $blUnits;

        return $this;
    }

    public function getOutturnTonnage(): ?float
    {
        return $this->outturnTonnage;
    }

    public function setOutturnTonnage(?float $outturnTonnage): static
    {
        $this->outturnTonnage = $outturnTonnage;

        return $this;
    }

    public function getOutturnVolume(): ?float
    {
        return $this->outturnVolume;
    }

    public function setOutturnVolume(?float $outturnVolume): static
    {
        $this->outturnVolume = $outturnVolume;

        return $this;
    }

    public function getOutturnUnits(): ?int
    {
        return $this->outturnUnits;
    }

    public function setOutturnUnits(?int $outturnUnits): static
    {
        $this->outturnUnits = $outturnUnits;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'escale_id' => $this->getShippingCall()?->getId(),
            'marchandise' => $this->getCargoName(),
            'client' => $this->getCustomer(),
            'operation' => $this->getOperation(true),
            'environ' => $this->isApproximate(),
            'tonnage_bl' => $this->getBlTonnage(),
            'cubage_bl' => $this->getBlVolume(),
            'nombre_bl' => $this->getBlUnits(),
            'tonnage_outturn' => $this->getOutturnTonnage(),
            'cubage_outturn' => $this->getOutturnVolume(),
            'nombre_outturn' => $this->getOutturnUnits(),
        ];
    }
}
