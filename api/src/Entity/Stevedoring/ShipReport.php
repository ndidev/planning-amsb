<?php

// Path: api/src/Entity/Stevedoring/ShipReport.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;

/**
 * @phpstan-type ShipReportArray array{
 *                                 id: int,
 *                                 isArchive: bool,
 *                                 linkedShippingCallId: int|null,
 *                                 ship: string,
 *                                 port: string,
 *                                 berth: string,
 *                                 comments: string,
 *                                 invoiceInstructions: string,
 *                                 startDate?: string,
 *                                 endDate?: string,
 *                               }
 */
final class ShipReport extends AbstractEntity
{
    use IdentifierTrait;

    public ?ShippingCall $linkedShippingCall = null;

    public bool $isArchive = false;

    #[Required("Le nom du navire est obligatoire.")]
    public string $ship = '';

    public string $port = '';

    public string $berth = '';

    public string $comments = '';

    public string $invoiceInstructions = '';

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

    /** @var Collection<ShipSubreport> */
    public Collection $subreports {
        set => $value->each(function ($subreport) {
            $subreport->shipReport = $this;
        });
    }

    /** @var Collection<ShippingCallCargo> */
    public Collection $cargoEntries {
        set => $value->each(function ($entry) {
            $entry->shipReport = $this;
            $entry->shippingCall = $this->linkedShippingCall;
        });
    }

    /** @var Collection<ShipReportStorageEntry> */
    public Collection $storageEntries {
        set => $value->each(function ($entry) {
            $entry->report = $this;
        });
    }

    /** 
     * @param ArrayHandler|ShipReportArray|null $data 
     */
    public function __construct(ArrayHandler|array|null $data = null)
    {
        $this->subreports = new Collection();
        $this->cargoEntries = new Collection();
        $this->storageEntries = new Collection();

        if (null === $data) {
            return;
        }

        $dataAH = $data instanceof ArrayHandler ? $data : new ArrayHandler($data);

        $this->id = $dataAH->getInt('id');
        $this->isArchive = $dataAH->getBool('isArchive');
        $this->ship = $dataAH->getString('ship');
        $this->port = $dataAH->getString('port');
        $this->berth = $dataAH->getString('berth');
        $this->comments = $dataAH->getString('comments');
        $this->invoiceInstructions = $dataAH->getString('invoiceInstructions');
        $this->startDate = $dataAH->getDatetime('startDate');
        $this->endDate = $dataAH->getDatetime('endDate');
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
            'isArchive' => $this->isArchive,
            'linkedShippingCallId' => $this->linkedShippingCall?->id,
            'ship' => $this->ship,
            'port' => $this->port,
            'berth' => $this->berth,
            'comments' => $this->comments,
            'invoiceInstructions' => $this->invoiceInstructions,
            'customers' => $this->getCustomers(),
            'startDate' => $this->startDate?->format('Y-m-d'),
            'endDate' => $this->endDate?->format('Y-m-d'),
            'subreports' => $this->subreports->toArray(),
            'cargoEntries' => $this->cargoEntries->toArray(),
            'cargoTotals' => $this->calculateCargoTotals(),
            'storageEntries' => $this->storageEntries->toArray(),
            'storageTotals' => $this->calculateStorageTotals(),
        ];
    }
}
