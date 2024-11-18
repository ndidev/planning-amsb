<?php

// Path: api/src/Entity/Shipping/ShippingCall.php

declare(strict_types=1);

namespace App\Entity\Shipping;

use App\Core\Component\Collection;
use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\Port;
use App\Entity\ThirdParty;

class ShippingCall extends AbstractEntity
{
    use IdentifierTrait;

    private string $shipName = 'TBN';

    private string $voyage = '';

    private ?ThirdParty $shipOperator = null;

    private ?\DateTimeImmutable $etaDate = null;

    private string $etaTime = '';

    private ?\DateTimeImmutable $norDate = null;

    private string $norTime = '';

    private ?\DateTimeImmutable $pobDate = null;

    private string $pobTime = '';

    private ?\DateTimeImmutable $etbDate = null;

    private string $etbTime = '';

    private ?\DateTimeImmutable $opsDate = null;

    private string $opsTime = '';

    private ?\DateTimeImmutable $etcDate = null;

    private string $etcTime = '';

    private ?\DateTimeImmutable $etdDate = null;

    private string $etdTime = '';

    private ?float $arrivalDraft = null;

    private ?float $departureDraft = null;

    private ?Port $lastPort = null;

    private ?Port $nextPort = null;

    private string $callPort = '';

    private string $quay = '';

    private string $comment = '';

    /**
     * @var Collection<ShippingCallCargo>
     */
    private Collection $cargoes;

    public function __construct()
    {
        $this->cargoes = new Collection();
    }

    public function setShipName(string $shipName): static
    {
        $this->shipName = trim($shipName);

        return $this;
    }

    public function getShipName(): string
    {
        return $this->shipName;
    }

    public function setVoyage(string $voyage): static
    {
        $this->voyage = $voyage;

        return $this;
    }

    public function getVoyage(): string
    {
        return $this->voyage;
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
            \array_map(fn(ShippingCallCargo $cargo) => $cargo->setShippingCall($this), $cargoes)
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
            'id' => $this->getId(),
            'navire' => $this->getShipName(),
            'voyage' => $this->getVoyage(),
            'armateur' => $this->getShipOperator()?->getId(),
            'eta_date' => $this->getEtaDate()?->format('Y-m-d'),
            'eta_heure' => $this->getEtaTime(),
            'nor_date' => $this->getNorDate()?->format('Y-m-d'),
            'nor_heure' => $this->getNorTime(),
            'pob_date' => $this->getPobDate()?->format('Y-m-d'),
            'pob_heure' => $this->getPobTime(),
            'etb_date' => $this->getEtbDate()?->format('Y-m-d'),
            'etb_heure' => $this->getEtbTime(),
            'ops_date' => $this->getOpsDate()?->format('Y-m-d'),
            'ops_heure' => $this->getOpsTime(),
            'etc_date' => $this->getEtcDate()?->format('Y-m-d'),
            'etc_heure' => $this->getEtcTime(),
            'etd_date' => $this->getEtdDate()?->format('Y-m-d'),
            'etd_heure' => $this->getEtdTime(),
            'te_arrivee' => $this->getArrivalDraft(),
            'te_depart' => $this->getDepartureDraft(),
            'last_port' => $this->getLastPort()?->getLocode(),
            'next_port' => $this->getNextPort()?->getLocode(),
            'call_port' => $this->getCallPort(),
            'quai' => $this->getQuay(),
            'commentaire' => $this->getComment(),
            'marchandises' => $this->getCargoes()->toArray(),
        ];
    }
}
