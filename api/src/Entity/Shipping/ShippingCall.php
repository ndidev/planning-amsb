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
class ShippingCall extends AbstractEntity
{
    use IdentifierTrait;

    public ?ShipReport $shipReport = null;

    public string $shipName = 'TBN' {
        get => $this->shipName ?: 'TBN';
        set => \trim($value);
    }

    public string $voyageNumber = '';

    public ?ThirdParty $shipOperator = null;

    public ?\DateTimeImmutable $etaDate = null;

    public string $etaTime = '';

    public ?\DateTimeImmutable $norDate = null;

    public string $norTime = '';

    public ?\DateTimeImmutable $pobDate = null;

    public string $pobTime = '';

    public ?\DateTimeImmutable $etbDate = null;

    public string $etbTime = '';

    public ?\DateTimeImmutable $opsDate = null;

    public string $opsTime = '';

    public ?\DateTimeImmutable $etcDate = null;

    public string $etcTime = '';

    public ?\DateTimeImmutable $etdDate = null;

    public string $etdTime = '';

    public ?float $arrivalDraft = null;

    public ?float $departureDraft = null;

    public ?Port $lastPort = null;

    public ?Port $nextPort = null;

    public string $callPort = '';

    public string $quay = '';

    public string $comment = '';

    /**
     * @var Collection<ShippingCallCargo>
     */
    public Collection $cargoes;

    /** 
     * @param ArrayHandler|ShippingCallArray|null $data 
     */
    public function __construct(ArrayHandler|array|null $data = null)
    {
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
        $this->quay = $dataAH->getString('quai');
        $this->comment = $dataAH->getString('commentaire');

        $this->cargoes = new Collection();
    }

    public function setShipName(string $shipName): static
    {
        $this->shipName = $shipName;

        return $this;
    }

    public function getShipName(): string
    {
        return $this->shipName;
    }

    public function setVoyage(string $voyage): static
    {
        $this->voyageNumber = $voyage;

        return $this;
    }

    public function getVoyage(): string
    {
        return $this->voyageNumber;
    }

    public function setShipOperator(?ThirdParty $shipOperator): static
    {
        $this->shipOperator = $shipOperator;

        return $this;
    }

    public function getShipOperator(): ?ThirdParty
    {
        return $this->shipOperator;
    }

    public function setEtaDate(\DateTimeImmutable|string|null $etaDate): static
    {
        $this->etaDate = DateUtils::makeDateTimeImmutable($etaDate);

        return $this;
    }

    public function getEtaDate(): ?\DateTimeImmutable
    {
        return $this->etaDate;
    }

    public function setEtaTime(string $etaTime): static
    {
        $this->etaTime = $etaTime;

        return $this;
    }

    public function getEtaTime(): string
    {
        return $this->etaTime;
    }

    public function setNorDate(\DateTimeImmutable|string|null $norDate): static
    {
        $this->norDate = DateUtils::makeDateTimeImmutable($norDate);

        return $this;
    }

    public function getNorDate(): ?\DateTimeImmutable
    {
        return $this->norDate;
    }

    public function setNorTime(string $norTime): static
    {
        $this->norTime = $norTime;

        return $this;
    }

    public function getNorTime(): string
    {
        return $this->norTime;
    }

    public function setPobDate(\DateTimeImmutable|string|null $pobDate): static
    {
        $this->pobDate = DateUtils::makeDateTimeImmutable($pobDate);

        return $this;
    }

    public function getPobDate(): ?\DateTimeImmutable
    {
        return $this->pobDate;
    }

    public function setPobTime(string $pobTime): static
    {
        $this->pobTime = $pobTime;

        return $this;
    }

    public function getPobTime(): string
    {
        return $this->pobTime;
    }

    public function setEtbDate(\DateTimeImmutable|string|null $etbDate): static
    {
        $this->etbDate = DateUtils::makeDateTimeImmutable($etbDate);

        return $this;
    }

    public function getEtbDate(): ?\DateTimeImmutable
    {
        return $this->etbDate;
    }

    public function setEtbTime(string $etbTime): static
    {
        $this->etbTime = $etbTime;

        return $this;
    }

    public function getEtbTime(): string
    {
        return $this->etbTime;
    }

    public function setOpsDate(\DateTimeImmutable|string|null $opsDate): static
    {
        $this->opsDate = DateUtils::makeDateTimeImmutable($opsDate);

        return $this;
    }

    public function getOpsDate(): ?\DateTimeImmutable
    {
        return $this->opsDate;
    }

    public function setOpsTime(string $opsTime): static
    {
        $this->opsTime = $opsTime;

        return $this;
    }

    public function getOpsTime(): string
    {
        return $this->opsTime;
    }

    public function setEtcDate(\DateTimeImmutable|string|null $etcDate): static
    {
        $this->etcDate = DateUtils::makeDateTimeImmutable($etcDate);

        return $this;
    }

    public function getEtcDate(): ?\DateTimeImmutable
    {
        return $this->etcDate;
    }

    public function setEtcTime(string $etcTime): static
    {
        $this->etcTime = $etcTime;

        return $this;
    }

    public function getEtcTime(): string
    {
        return $this->etcTime;
    }

    public function setEtdDate(\DateTimeImmutable|string|null $etdDate): static
    {
        $this->etdDate = DateUtils::makeDateTimeImmutable($etdDate);

        return $this;
    }

    public function getEtdDate(): ?\DateTimeImmutable
    {
        return $this->etdDate;
    }

    public function setEtdTime(string $etdTime): static
    {
        $this->etdTime = $etdTime;

        return $this;
    }

    public function getEtdTime(): string
    {
        return $this->etdTime;
    }

    public function setArrivalDraft(?float $arrivalDraft): static
    {
        $this->arrivalDraft = $arrivalDraft;

        return $this;
    }

    public function getArrivalDraft(): ?float
    {
        return $this->arrivalDraft;
    }

    public function setDepartureDraft(?float $departureDraft): static
    {
        $this->departureDraft = $departureDraft;

        return $this;
    }

    public function getDepartureDraft(): ?float
    {
        return $this->departureDraft;
    }

    public function setLastPort(?Port $lastPort): static
    {
        $this->lastPort = $lastPort;

        return $this;
    }

    public function getLastPort(): ?Port
    {
        return $this->lastPort;
    }

    public function setNextPort(?Port $nextPort): static
    {
        $this->nextPort = $nextPort;

        return $this;
    }

    public function getNextPort(): ?Port
    {
        return $this->nextPort;
    }

    public function setCallPort(string $callPort): static
    {
        $this->callPort = trim($callPort);

        return $this;
    }

    public function getCallPort(): string
    {
        return $this->callPort;
    }

    public function setQuay(?string $quay): static
    {
        $this->quay = trim((string) $quay);

        return $this;
    }

    public function getQuay(): string
    {
        return $this->quay;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param ShippingCallCargo[] $cargoes 
     */
    public function setCargoes(array $cargoes): static
    {
        $this->cargoes = new Collection(
            \array_map(
                function (ShippingCallCargo $cargo) {
                    /** @disregard P1006 */
                    $cargo->shippingCall = $this;
                    $cargo->shipReport = $this->shipReport;
                    return $cargo;
                },
                $cargoes
            )
        );

        return $this;
    }

    /**
     * @return Collection<ShippingCallCargo>
     */
    public function getCargoes(): Collection
    {
        return $this->cargoes;
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
            'quai' => $this->quay,
            'commentaire' => $this->comment,
            'marchandises' => $this->cargoes->toArray(),
        ];
    }
}
