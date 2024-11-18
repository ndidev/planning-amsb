<?php

// Path: api/src/Entity/Bulk/BulkAppointment.php

declare(strict_types=1);

namespace App\Entity\Chartering;

use App\Core\Validation\Constraints\Required;
use App\Core\Component\CharterStatus;
use App\Core\Component\Collection;
use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\ThirdParty;

class Charter extends AbstractEntity
{
    use IdentifierTrait;

    /** @phpstan-var CharterStatus::* $status */
    private int $status = CharterStatus::PENDING;

    private ?\DateTimeImmutable $laycanStart = null;

    private ?\DateTimeImmutable $laycanEnd = null;

    private ?\DateTimeImmutable $cpDate = null;

    private string $vesselName = 'TBN';

    #[Required("L'affréteur est obligatoire.")]
    private ?ThirdParty $charterer = null;

    private ?ThirdParty $shipOperator = null;

    private ?ThirdParty $shipbroker = null;

    private float $freightPayed = 0.0;

    private float $freightSold = 0.0;

    private float $demurragePayed = 0.0;

    private float $demurrageSold = 0.0;

    private string $comments = '';

    private bool $archive = false;

    /**
     * @var Collection<CharterLeg>
     */
    private Collection $legs;

    public function __construct()
    {
        $this->legs = new Collection();
    }

    public function setStatus(int $status): static
    {
        $this->status = CharterStatus::tryFrom($status);

        return $this;
    }

    /**
     * @phpstan-return CharterStatus::*
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    public function setLaycanStart(\DateTimeImmutable|string|null $date): static
    {
        $this->laycanStart = DateUtils::makeDateTimeImmutable($date);

        return $this;
    }

    public function getLaycanStart(): ?\DateTimeImmutable
    {
        return $this->laycanStart;
    }

    public function getSqlLaycanStart(): ?string
    {
        return $this->laycanStart?->format('Y-m-d');
    }

    public function setLaycanEnd(\DateTimeImmutable|string|null $date): static
    {
        $this->laycanEnd = DateUtils::makeDateTimeImmutable($date);

        return $this;
    }

    public function getLaycanEnd(): ?\DateTimeImmutable
    {
        return $this->laycanEnd;
    }

    public function getSqlLaycanEnd(): ?string
    {
        return $this->laycanEnd?->format('Y-m-d');
    }

    public function setCpDate(\DateTimeImmutable|string|null $date): static
    {
        $this->cpDate = DateUtils::makeDateTimeImmutable($date);

        return $this;
    }

    public function getCpDate(): ?\DateTimeImmutable
    {
        return $this->cpDate;
    }

    public function getSqlCpDate(): ?string
    {
        return $this->cpDate?->format('Y-m-d');
    }

    public function setVesselName(string $vesselName): static
    {
        $this->vesselName = $vesselName ?: "TBN";

        return $this;
    }

    public function getVesselName(): string
    {
        return $this->vesselName;
    }

    public function setCharterer(?ThirdParty $charterer): static
    {
        $this->charterer = $charterer;

        return $this;
    }

    public function getCharterer(): ?ThirdParty
    {
        return $this->charterer;
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

    public function setShipbroker(?ThirdParty $shipbroker): static
    {
        $this->shipbroker = $shipbroker;

        return $this;
    }

    public function getShipbroker(): ?ThirdParty
    {
        return $this->shipbroker;
    }

    public function setFreightPayed(float $freightPayed): static
    {
        $this->freightPayed = $freightPayed;

        return $this;
    }

    public function getFreightPayed(): float
    {
        return $this->freightPayed;
    }

    public function setFreightSold(float $freightSold): static
    {
        $this->freightSold = $freightSold;

        return $this;
    }

    public function getFreightSold(): float
    {
        return $this->freightSold;
    }

    public function setDemurragePayed(float $demurragePayed): static
    {
        $this->demurragePayed = $demurragePayed;

        return $this;
    }

    public function getDemurragePayed(): float
    {
        return $this->demurragePayed;
    }

    public function setDemurrageSold(float $demurrageSold): static
    {
        $this->demurrageSold = $demurrageSold;

        return $this;
    }

    public function getDemurrageSold(): float
    {
        return $this->demurrageSold;
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

    public function setArchive(int|bool $archive): static
    {
        $this->archive = (bool) $archive;

        return $this;
    }

    public function isArchive(): bool
    {
        return $this->archive;
    }

    /**
     * @param CharterLeg[] $legs 
     */
    public function setLegs(array $legs): static
    {
        $this->legs = new Collection(
            \array_map(fn(CharterLeg $leg) => $leg->setCharter($this), $legs)
        );

        return $this;
    }

    /**
     * @return Collection<CharterLeg>
     */
    public function getLegs(): Collection
    {
        return $this->legs;
    }

    public function addLeg(CharterLeg $leg): static
    {
        $this->legs->add($leg);
        $leg->setCharter($this);

        return $this;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "statut" => $this->getStatus(),
            "lc_debut" => $this->getLaycanStart()?->format('Y-m-d'),
            "lc_fin" => $this->getLaycanEnd()?->format('Y-m-d'),
            "cp_date" => $this->getCpDate()?->format('Y-m-d'),
            "navire" => $this->getVesselName(),
            "affreteur" => $this->getCharterer()?->getId(),
            "armateur" => $this->getShipOperator()?->getId(),
            "courtier" => $this->getShipbroker()?->getId(),
            "fret_achat" => $this->getFreightPayed(),
            "fret_vente" => $this->getFreightSold(),
            "surestaries_achat" => $this->getDemurragePayed(),
            "surestaries_vente" => $this->getDemurrageSold(),
            "commentaire" => $this->getComments(),
            "archive" => $this->isArchive(),
            "legs" => $this->getLegs()->toArray(),
        ];
    }
}
