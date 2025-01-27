<?php

// Path: api/src/Entity/Stevedoring/ShipReport.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Component\Collection;
use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;

class ShipReport extends AbstractEntity
{
    use IdentifierTrait;

    public ?ShippingCall $linkedShippingCall = null;

    public bool $isArchive = false;

    #[Required("Le nom du navire est obligatoire.")]
    public string $ship = '';

    public string $port = '';

    public string $berth = '';

    public string $comments = '';

    public ?\DateTimeImmutable $startDate = null {
        set(\DateTimeImmutable|string|null $value) {
            $this->startDate = DateUtils::makeDateTimeImmutable($value);
        }
    }

    public ?\DateTimeImmutable $endDate = null {
        set(\DateTimeImmutable|string|null $value) {
            $this->endDate = DateUtils::makeDateTimeImmutable($value);
        }
    }

    /** @var Collection<ShipReportEquipmentEntry> */
    public private(set) Collection $equipmentEntries;

    /** @var Collection<ShipReportStaffEntry> */
    public private(set) Collection $staffEntries;

    /** @var Collection<ShipReportSubcontractEntry> */
    public private(set) Collection $subcontractEntries;

    /** @var Collection<ShippingCallCargo> */
    public private(set) Collection $cargoEntries;

    /** @var Collection<ShipReportStorageEntry> */
    public private(set) Collection $storageEntries;

    public function __construct()
    {
        $this->equipmentEntries = new Collection();
        $this->staffEntries = new Collection();
        $this->subcontractEntries = new Collection();
        $this->cargoEntries = new Collection();
        $this->storageEntries = new Collection();
    }

    /**
     * @param ShipReportEquipmentEntry[] $entries 
     */
    public function setEquipmentEntries(array $entries): static
    {
        $this->equipmentEntries = new Collection(
            \array_map(
                function (ShipReportEquipmentEntry $entry) {
                    /** @disregard P1006 */
                    $entry->report = $this;
                    return $entry;
                },
                $entries
            )
        );

        return $this;
    }

    /**
     * @param ShipReportStaffEntry[] $entries 
     */
    public function setStaffEntries(array $entries): static
    {
        $this->staffEntries = new Collection(
            \array_map(
                function (ShipReportStaffEntry $entry) {
                    /** @disregard P1006 */
                    $entry->report = $this;
                    return $entry;
                },
                $entries
            )
        );

        return $this;
    }

    /**
     * @param ShipReportSubcontractEntry[] $entries 
     */
    public function setSubcontractEntries(array $entries): static
    {
        $this->subcontractEntries = new Collection(
            \array_map(
                function (ShipReportSubcontractEntry $entry) {
                    /** @disregard P1006 */
                    $entry->report = $this;
                    return $entry;
                },
                $entries
            )
        );

        return $this;
    }

    /**
     * @param ShippingCallCargo[] $entries 
     */
    public function setCargoEntries(array $entries): static
    {
        $this->cargoEntries = new Collection(
            \array_map(
                function (ShippingCallCargo $entry) {
                    /** @disregard P1006 */
                    $entry->shipReport = $this;
                    $entry->shippingCall = $this->linkedShippingCall;
                    return $entry;
                },
                $entries
            )
        );

        return $this;
    }

    /**
     * @param ShipReportStorageEntry[] $entries 
     */
    public function setStorageEntries(array $entries): static
    {
        $this->storageEntries = new Collection(
            \array_map(
                function (ShipReportStorageEntry $entry) {
                    /** @disregard P1006 */
                    $entry->report = $this;
                    return $entry;
                },
                $entries
            )
        );

        return $this;
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
    private function getEntriesByDate(): array
    {
        /** @var array<ShipReportStaffEntry|ShipReportEquipmentEntry|ShipReportSubcontractEntry> */
        $allEntries = \array_merge(
            $this->equipmentEntries->asArray(),
            $this->staffEntries->asArray(),
            $this->subcontractEntries->asArray(),
        );

        $entriesByDate = [];

        foreach ($allEntries as $entry) {
            $date = $entry->date?->format('Y-m-d');

            if (!$date) continue;

            if (!isset($entriesByDate[$date])) {
                $entriesByDate[$date] = [
                    'cranes' => [],
                    'equipments' => [],
                    'permanentStaff' => [],
                    'tempStaff' => [],
                    'trucking' => [],
                    'otherSubcontracts' => [],
                ];
            }

            if ($entry instanceof ShipReportEquipmentEntry) {
                $typeToLower = \mb_strtolower($entry->equipment->type ?? "");
                if (\array_any(["pelle", "grue"], fn(string $match) => \str_contains($typeToLower, $match))) {
                    $entriesByDate[$date]['cranes'][] = $entry;
                } else {
                    $entriesByDate[$date]['equipments'][] = $entry;
                }
            } elseif ($entry instanceof ShipReportStaffEntry) {
                if ($entry->staff?->type === "mensuel") {
                    $entriesByDate[$date]['permanentStaff'][] = $entry;
                }
                if ($entry->staff?->type === "interim") {
                    $entriesByDate[$date]['tempStaff'][] = $entry;
                }
            } elseif ($entry instanceof ShipReportSubcontractEntry) {
                $typeToLower = \mb_strtolower($entry->type);
                if ($entry->type === "trucking") {
                    $entriesByDate[$date]['trucking'][] = $entry;
                } else {
                    $entriesByDate[$date]['otherSubcontracts'][] = $entry;
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
    private function calculateCargoTotals(): array
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
     * @return string[]
     */
    private function getCustomers(): array
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'isArchive' => $this->isArchive,
            'linkedShippingCallId' => $this->linkedShippingCall?->id,
            'ship' => $this->ship,
            'port' => $this->port,
            'berth' => $this->berth,
            'comments' => $this->comments,
            'customers' => $this->getCustomers(),
            'startDate' => $this->startDate?->format('Y-m-d'),
            'endDate' => $this->endDate?->format('Y-m-d'),
            'entriesByDate' => $this->getEntriesByDate(),
            'cargoEntries' => $this->cargoEntries->toArray(),
            'cargoTotals' => $this->calculateCargoTotals(),
            'storageEntries' => $this->storageEntries->toArray(),
        ];
    }
}
