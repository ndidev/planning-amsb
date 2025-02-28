<?php

// Path: api/src/Entity/Stevedoring/ShipSubreport.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Component\Collection;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\Shipping\ShippingCallCargo;

/**
 * @phpstan-type ShipSubreportArray array{
 *                                    id: int,
 *                                    ship_report_id: int,
 *                                  }
 */
final class ShipSubreport extends AbstractEntity
{
    use IdentifierTrait;

    public ShipReport $shipReport;

    /** @var Collection<ShippingCallCargo> */
    public Collection $cargoEntries;

    /** @var Collection<ShipReportEquipmentEntry> */
    public Collection $equipmentEntries {
        set => $value->each(function ($entry) {
            $entry->subreport = $this;
        });
    }

    /** @var Collection<ShipReportStaffEntry> */
    public Collection $staffEntries {
        set => $value->each(function ($entry) {
            $entry->subreport = $this;
        });
    }

    /** @var Collection<ShipReportSubcontractEntry> */
    public Collection $subcontractEntries {
        set => $value->each(function ($entry) {
            $entry->subreport = $this;
        });
    }

    /** @var Collection<ShipReportStorageEntry> */
    public Collection $storageEntries {
        get => $this->shipReport->storageEntries->filter(fn($entry) => $this->cargoEntries->includes($entry->cargo));
    }

    public function __construct()
    {
        $this->cargoEntries = new Collection();
        $this->equipmentEntries = new Collection();
        $this->staffEntries = new Collection();
        $this->subcontractEntries = new Collection();
    }

    /**
     * @return array<string, array{
     *                         cranes: ShipReportEquipmentEntry[],
     *                         equipments: ShipReportEquipmentEntry[],
     *                         permanentStaff: ShipReportStaffEntry[],
     *                         tempStaff: ShipReportStaffEntry[],
     *                         trucking: ShipReportSubcontractEntry[],
     *                         otherSubcontracts: ShipReportSubcontractEntry[],
     *                       }
     *         >
     */
    public function getEntriesByDate(): array
    {
        /** @var array<ShipReportStaffEntry|ShipReportEquipmentEntry|ShipReportSubcontractEntry> */
        $allEntries = \array_merge(
            $this->equipmentEntries->asArray(),
            $this->staffEntries->asArray(),
            $this->subcontractEntries->asArray(),
        );

        $entriesByDate = [];

        foreach ($allEntries as $entry) {
            if (!$entry->date) continue;

            $dateString = $entry->date->format('Y-m-d');

            if (!isset($entriesByDate[$dateString])) {
                $entriesByDate[$dateString] = [
                    'cranes' => [],
                    'equipments' => [],
                    'permanentStaff' => [],
                    'tempStaff' => [],
                    'trucking' => [],
                    'otherSubcontracts' => [],
                ];
            }

            if ($entry instanceof ShipReportEquipmentEntry) {
                if ($entry->equipment?->isCrane) {
                    $entriesByDate[$dateString]['cranes'][] = $entry;
                } else {
                    $entriesByDate[$dateString]['equipments'][] = $entry;
                }
            } elseif ($entry instanceof ShipReportStaffEntry) {
                if ($entry->staff?->type === "mensuel") {
                    $entriesByDate[$dateString]['permanentStaff'][] = $entry;
                }
                if ($entry->staff?->type === "interim") {
                    $entriesByDate[$dateString]['tempStaff'][] = $entry;
                }
            } elseif ($entry instanceof ShipReportSubcontractEntry) {
                if ($entry->type === "trucking") {
                    $entriesByDate[$dateString]['trucking'][] = $entry;
                } else {
                    $entriesByDate[$dateString]['otherSubcontracts'][] = $entry;
                }
            }
        }

        ksort($entriesByDate);

        return $entriesByDate;
    }

    /**
     * @return array{
     *           bl: array{tonnage: float, volume: float, units: int},
     *           outturn: array{tonnage: float, volume: float, units: int},
     *           difference: array{tonnage: float, volume: float, units: int},
     *         }
     */
    public function calculateCargoTotals(): array
    {
        $totals = [
            'bl' => [
                'tonnage' => \array_reduce(
                    $this->cargoEntries->asArray(),
                    fn(float $carry, ShippingCallCargo $entry) => $carry + $entry->blTonnage,
                    0
                ),
                'volume' => \array_reduce(
                    $this->cargoEntries->asArray(),
                    fn(float $carry, ShippingCallCargo $entry) => $carry + $entry->blVolume,
                    0
                ),
                'units' => \array_reduce(
                    $this->cargoEntries->asArray(),
                    fn(int $carry, ShippingCallCargo $entry) => $carry + $entry->blUnits,
                    0
                ),
            ],
            'outturn' => [
                'tonnage' => \array_reduce(
                    $this->cargoEntries->asArray(),
                    fn(float $carry, ShippingCallCargo $entry) => $carry + $entry->outturnTonnage,
                    0
                ),
                'volume' => \array_reduce(
                    $this->cargoEntries->asArray(),
                    fn(float $carry, ShippingCallCargo $entry) => $carry + $entry->outturnVolume,
                    0
                ),
                'units' => \array_reduce(
                    $this->cargoEntries->asArray(),
                    fn(int $carry, ShippingCallCargo $entry) => $carry + $entry->outturnUnits,
                    0
                ),
            ],
            'difference' => [
                'tonnage' => \array_reduce(
                    $this->cargoEntries->asArray(),
                    fn(float $carry, ShippingCallCargo $entry) => $carry + $entry->tonnageDifference,
                    0
                ),
                'volume' => \array_reduce(
                    $this->cargoEntries->asArray(),
                    fn(float $carry, ShippingCallCargo $entry) => $carry + $entry->volumeDifference,
                    0
                ),
                'units' => \array_reduce(
                    $this->cargoEntries->asArray(),
                    fn(int $carry, ShippingCallCargo $entry) => $carry + $entry->unitsDifference,
                    0
                ),
            ],
        ];

        return $totals;
    }

    /**
     * @return array{tonnage: float, volume: float, units: int}
     */
    public function calculateStorageTotals(): array
    {
        $totals = [
            'tonnage' => \array_reduce(
                $this->storageEntries->asArray(),
                fn(float $carry, ShipReportStorageEntry $entry) => $carry + $entry->tonnage,
                0
            ),
            'volume' => \array_reduce(
                $this->storageEntries->asArray(),
                fn(float $carry, ShipReportStorageEntry $entry) => $carry + $entry->volume,
                0
            ),
            'units' => \array_reduce(
                $this->storageEntries->asArray(),
                fn(int $carry, ShipReportStorageEntry $entry) => $carry + $entry->units,
                0
            ),
        ];

        return $totals;
    }

    /**
     * @return string[]
     */
    public function getCustomers(): array
    {
        $customers = \array_unique(
            \array_map(
                fn(ShippingCallCargo $entry) => $entry->customer,
                $this->cargoEntries->asArray()
            )
        );

        sort($customers);

        return $customers;
    }

    /**
     * @return string[]
     */
    public function getCargoNames(): array
    {
        $cargoList = $this->cargoEntries->map(fn($entry) => $entry->cargoName);

        return $cargoList;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'entriesByDate' => $this->getEntriesByDate(),
            'cargoIds' => $this->cargoEntries->map(fn($entry) => $entry->id),
            'cargoTotals' => $this->calculateCargoTotals(),
            'storageEntries' => $this->storageEntries->toArray(),
            'storageTotals' => $this->calculateStorageTotals(),
        ];
    }
}
