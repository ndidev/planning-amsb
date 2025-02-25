<?php

// Path: api/src/Entity/Shipping/ShippingCall.php

declare(strict_types=1);

namespace App\Entity\Shipping;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\Port;
use App\Entity\Stevedoring\ShipReport;
use App\Entity\ThirdParty;

/**
 * @phpstan-import-type ShippingCallCargoArray from ShippingCallCargo
 * 
 * @phpstan-type ShippingCallArray array{
 *                                   id: int,
 *                                   stevedoringShipReportId: int|null,
 *                                   navire: string,
 *                                   voyage: string,
 *                                   armateur: int|null,
 *                                   eta_date: string|null,
 *                                   eta_heure: string,
 *                                   nor_date: string|null,
 *                                   nor_heure: string,
 *                                   pob_date: string|null,
 *                                   pob_heure: string,
 *                                   etb_date: string|null,
 *                                   etb_heure: string,
 *                                   ops_date: string|null,
 *                                   ops_heure: string,
 *                                   etc_date: string|null,
 *                                   etc_heure: string,
 *                                   etd_date: string|null,
 *                                   etd_heure: string,
 *                                   te_arrivee: float|null,
 *                                   te_depart: float|null,
 *                                   last_port: string,
 *                                   next_port: string,
 *                                   call_port: string,
 *                                   quai: string,
 *                                   commentaire: string,
 *                                   marchandises?: ShippingCallCargoArray[],
 *                                 }
 */
final class ShippingCall extends AbstractEntity
{
    use IdentifierTrait;

    public ?ShipReport $shipReport = null;

    public string $shipName = 'TBN' {
        get => $this->shipName ?: 'TBN';
    }

    public string $voyageNumber = '';

    public ?ThirdParty $shipOperator = null;

    public ?\DateTimeImmutable $etaDate = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public string $etaTime = '';

    public ?\DateTimeImmutable $norDate = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public string $norTime = '';

    public ?\DateTimeImmutable $pobDate = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public string $pobTime = '';

    public ?\DateTimeImmutable $etbDate = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public string $etbTime = '';

    public ?\DateTimeImmutable $opsDate = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public string $opsTime = '';

    public ?\DateTimeImmutable $etcDate = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public string $etcTime = '';

    public ?\DateTimeImmutable $etdDate = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public string $etdTime = '';

    public ?float $arrivalDraft = null;

    public ?float $departureDraft = null;

    public ?Port $lastPort = null;

    public ?Port $nextPort = null;

    public string $callPort = '';

    public string $berth = '';

    public string $comment = '';

    /**
     * @var Collection<ShippingCallCargo>
     */
    public Collection $cargoes {
        /** @param ShippingCallCargo[]|Collection<ShippingCallCargo> $value */
        set(array|Collection $value) {
            $this->cargoes = $value instanceof Collection
                ? $value
                : new Collection(
                    \array_map(
                        function (ShippingCallCargo $cargo) {
                            /** @disregard P1006 */
                            $cargo->shippingCall = $this;
                            $cargo->shipReport = $this->shipReport;
                            return $cargo;
                        },
                        $value
                    )
                );
        }
    }

    /** 
     * @param ArrayHandler|ShippingCallArray|null $data 
     */
    public function __construct(ArrayHandler|array|null $data = null)
    {
        $this->cargoes = new Collection();

        if (null === $data) {
            return;
        }

        $dataAH = $data instanceof ArrayHandler ? $data : new ArrayHandler($data);

        $this->id = $dataAH->getInt('id');
        $this->shipName = $dataAH->getString('navire', 'TBN');
        $this->voyageNumber = $dataAH->getString('voyage');
        $this->etaDate = $dataAH->getDatetime('eta_date');
        $this->etaTime = $dataAH->getString('eta_heure');
        $this->norDate = $dataAH->getDatetime('nor_date');
        $this->norTime = $dataAH->getString('nor_heure');
        $this->pobDate = $dataAH->getDatetime('pob_date');
        $this->pobTime = $dataAH->getString('pob_heure');
        $this->etbDate = $dataAH->getDatetime('etb_date');
        $this->etbTime = $dataAH->getString('etb_heure');
        $this->opsDate = $dataAH->getDatetime('ops_date');
        $this->opsTime = $dataAH->getString('ops_heure');
        $this->etcDate = $dataAH->getDatetime('etc_date');
        $this->etcTime = $dataAH->getString('etc_heure');
        $this->etdDate = $dataAH->getDatetime('etd_date');
        $this->etdTime = $dataAH->getString('etd_heure');
        $this->arrivalDraft = $dataAH->getFloat('te_arrivee');
        $this->departureDraft = $dataAH->getFloat('te_depart');
        $this->callPort = $dataAH->getString('call_port');
        $this->berth = $dataAH->getString('quai');
        $this->comment = $dataAH->getString('commentaire');
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'shipReportId' => $this->shipReport?->id,
            'navire' => $this->shipName,
            'voyage' => $this->voyageNumber,
            'armateur' => $this->shipOperator?->id,
            'eta_date' => $this->etaDate?->format('Y-m-d'),
            'eta_heure' => $this->etaTime,
            'nor_date' => $this->norDate?->format('Y-m-d'),
            'nor_heure' => $this->norTime,
            'pob_date' => $this->pobDate?->format('Y-m-d'),
            'pob_heure' => $this->pobTime,
            'etb_date' => $this->etbDate?->format('Y-m-d'),
            'etb_heure' => $this->etbTime,
            'ops_date' => $this->opsDate?->format('Y-m-d'),
            'ops_heure' => $this->opsTime,
            'etc_date' => $this->etcDate?->format('Y-m-d'),
            'etc_heure' => $this->etcTime,
            'etd_date' => $this->etdDate?->format('Y-m-d'),
            'etd_heure' => $this->etdTime,
            'te_arrivee' => $this->arrivalDraft,
            'te_depart' => $this->departureDraft,
            'last_port' => $this->lastPort?->locode,
            'next_port' => $this->nextPort?->locode,
            'call_port' => $this->callPort,
            'quai' => $this->berth,
            'commentaire' => $this->comment,
            'marchandises' => $this->cargoes->toArray(),
        ];
    }
}
