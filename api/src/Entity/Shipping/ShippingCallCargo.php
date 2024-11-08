<?php

// Path: api/src/Entity/Shipping/ShippingCallCargo.php

namespace App\Entity\Shipping;

use App\Core\Component\CargoOperation;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;

/**
 * Represents a cargo for a shipping call.
 * 
 * @phpstan-type ShippingCallCargoArray array{
 *                                        id: ?int,
 *                                        escale_id: ?int,
 *                                        marchandise: string,
 *                                        client: string,
 *                                        operation: CargoOperation::*,
 *                                        environ: bool,
 *                                        tonnage_bl: ?float,
 *                                        cubage_bl: ?float,
 *                                        nombre_bl: ?float,
 *                                        tonnage_outturn: ?float,
 *                                        cubage_outturn: ?float,
 *                                        nombre_outturn: ?float,
 *                                      }
 */
final class ShippingCallCargo extends AbstractEntity
{
    use IdentifierTrait;

    private ?ShippingCall $shippingCall = null;
    private string $cargoName = '';
    private string $customer = '';
    /** @phpstan-var CargoOperation::* $operation */
    private string $operation = CargoOperation::IMPORT;
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

    public function setShippingCall(?ShippingCall $shippingCall): static
    {
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

    /**
     *  @phpstan-return CargoOperation::*
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): static
    {
        $operationFromEnum = CargoOperation::tryFrom($operation);

        if (null === $operationFromEnum) {
            throw new BadRequestException("Opération invalide");
        }

        $this->operation = $operationFromEnum;

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
            'operation' => $this->getOperation(),
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
