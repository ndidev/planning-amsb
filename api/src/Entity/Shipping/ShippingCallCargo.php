<?php

// Path: api/src/Entity/Shipping/ShippingCallCargo.php

declare(strict_types=1);

namespace App\Entity\Shipping;

use App\Core\Validation\Constraints\Required;
use App\Core\Component\CargoOperation;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\Stevedoring\ShipReport;

final class ShippingCallCargo extends AbstractEntity implements \Stringable
{
    use IdentifierTrait;

    public ?ShippingCall $shippingCall = null;

    public ?ShipReport $shipReport = null;

    #[Required("Le nom de la marchandise est obligatoire.")]
    public string $cargoName = '';

    #[Required("Le client est obligatoire.")]
    public string $customer = '';

    /** @phpstan-var CargoOperation::* $operation */
    public string $operation = CargoOperation::IMPORT {
        set(string $value) {
            $operationFromEnum = CargoOperation::tryFrom($value);

            if (null === $operationFromEnum) {
                throw new BadRequestException("Opération invalide : '{$value}'");
            }

            $this->operation = $operationFromEnum;
        }
    }

    public bool $isApproximate = false;

    public ?float $blTonnage = null;

    public ?float $blVolume = null;

    public ?int $blUnits = null;

    public ?float $outturnTonnage = null;

    public ?float $outturnVolume = null;

    public ?int $outturnUnits = null;

    public ?float $tonnageDifference {
        get {
            if (null === $this->blTonnage || null === $this->outturnTonnage) {
                return null;
            }

            return $this->outturnTonnage - $this->blTonnage;
        }
    }

    public ?float $volumeDifference {
        get {
            if (null === $this->blVolume || null === $this->outturnVolume) {
                return null;
            }

            return $this->outturnVolume - $this->blVolume;
        }
    }

    public ?int $unitsDifference {
        get {
            if (null === $this->blUnits || null === $this->outturnUnits) {
                return null;
            }

            return $this->outturnUnits - $this->blUnits;
        }
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'escale_id' => $this->shippingCall?->id,
            'shipReportId' => $this->shipReport?->id,
            'cargoName' => $this->cargoName,
            'customer' => $this->customer,
            'operation' => $this->operation,
            'isApproximate' => $this->isApproximate,
            'blTonnage' => $this->blTonnage,
            'blVolume' => $this->blVolume,
            'blUnits' => $this->blUnits,
            'outturnTonnage' => $this->outturnTonnage,
            'outturnVolume' => $this->outturnVolume,
            'outturnUnits' => $this->outturnUnits,
            'tonnageDifference' => $this->tonnageDifference,
            'volumeDifference' => $this->volumeDifference,
            'unitsDifference' => $this->unitsDifference,
        ];
    }

    public function __toString(): string
    {
        return $this->cargoName;
    }
}
