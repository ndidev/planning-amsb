<?php

// Path: api/src/Entity/Bulk/BulkAppointment.php

namespace App\Entity\Chartering;

use App\Core\Component\CharterStatus;
use App\Core\Component\Collection;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\ThirdParty;
use App\Service\ThirdPartyService;

class Charter extends AbstractEntity
{
    use IdentifierTrait;

    private CharterStatus $status = CharterStatus::PENDING;
    private ?\DateTimeImmutable $laycanStart;
    private ?\DateTimeImmutable $laycanEnd;
    private ?\DateTimeImmutable $cpDate;
    private string $vesselName = 'TBN';
    private ?ThirdParty $charterer = null;
    private ?ThirdParty $shipOperator = null;
    private ?ThirdParty $shipbroker = null;
    private float $freightPayed = 0;
    private float $freightSold = 0;
    private float $demurragePayed = 0;
    private float $demurrageSold = 0;
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

    public function getStatus(): CharterStatus
    {
        return $this->status;
    }

    public function setStatus(CharterStatus|int $status): static
    {
        if (is_int($status)) {
            $statusFromEnum = CharterStatus::tryFrom($status);

            if (null === $statusFromEnum) {
                throw new \InvalidArgumentException("Statut invalide");
            }

            $this->status = $statusFromEnum;
        } else {
            $this->status = $status;
        }

        return $this;
    }

    /**
     * @param bool $sqlFormat 
     * 
     * @return \DateTimeImmutable|string|null 
     * 
     * @phpstan-return ($sqlFormat is false ? \DateTimeImmutable|null :string|null)
     */
    public function getLaycanStart(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->laycanStart?->format("Y-m-d");
        } else {
            return $this->laycanStart;
        }
    }

    public function setLaycanStart(\DateTimeImmutable|string|null $date): static
    {
        if (is_string($date)) {
            $this->laycanStart = new \DateTimeImmutable($date);
        } else {
            $this->laycanStart = $date;
        }

        return $this;
    }

    /**
     * @param bool $sqlFormat 
     * 
     * @return \DateTimeImmutable|string|null 
     * 
     * @phpstan-return ($sqlFormat is false ? \DateTimeImmutable|null :string|null)
     */
    public function getLaycanEnd(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->laycanEnd?->format("Y-m-d");
        } else {
            return $this->laycanEnd;
        }
    }

    public function setLaycanEnd(\DateTimeImmutable|string|null $date): static
    {
        if (is_string($date)) {
            $this->laycanEnd = new \DateTimeImmutable($date);
        } else {
            $this->laycanEnd = $date;
        }

        return $this;
    }

    /**
     * @param bool $sqlFormat 
     * 
     * @return \DateTimeImmutable|string|null 
     * 
     * @phpstan-return ($sqlFormat is false ? \DateTimeImmutable|null :string|null)
     */
    public function getCpDate(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->cpDate?->format("Y-m-d");
        } else {
            return $this->cpDate;
        }
    }

    public function setCpDate(\DateTimeImmutable|string|null $date): static
    {
        if (is_string($date)) {
            $this->cpDate = new \DateTimeImmutable($date);
        } else {
            $this->cpDate = $date;
        }

        return $this;
    }

    public function getVesselName(): string
    {
        return $this->vesselName;
    }

    public function setVesselName(string $vesselName): static
    {
        $this->vesselName = $vesselName ?: "TBN";

        return $this;
    }

    public function getCharterer(): ?ThirdParty
    {
        return $this->charterer;
    }

    public function setCharterer(?ThirdParty $charterer): static
    {
        $this->charterer = $charterer;

        return $this;
    }

    public function getShipOperator(): ?ThirdParty
    {
        return $this->shipOperator;
    }

    public function setShipOperator(?ThirdParty $shipOperator): static
    {
        $this->shipOperator = $shipOperator;

        return $this;
    }

    public function getShipbroker(): ?ThirdParty
    {
        return $this->shipbroker;
    }

    public function setShipbroker(?ThirdParty $shipbroker): static
    {
        $this->shipbroker = $shipbroker;

        return $this;
    }

    public function getFreightPayed(): float
    {
        return $this->freightPayed;
    }

    public function setFreightPayed(float $freightPayed): static
    {
        $this->freightPayed = $freightPayed;

        return $this;
    }

    public function getFreightSold(): float
    {
        return $this->freightSold;
    }

    public function setFreightSold(float $freightSold): static
    {
        $this->freightSold = $freightSold;

        return $this;
    }

    public function getDemurragePayed(): float
    {
        return $this->demurragePayed;
    }

    public function setDemurragePayed(float $demurragePayed): static
    {
        $this->demurragePayed = $demurragePayed;

        return $this;
    }

    public function getDemurrageSold(): float
    {
        return $this->demurrageSold;
    }

    public function setDemurrageSold(float $demurrageSold): static
    {
        $this->demurrageSold = $demurrageSold;

        return $this;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function setComments(string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function isArchive(): bool
    {
        return $this->archive;
    }

    public function setArchive(int|bool $archive): static
    {
        $this->archive = (bool) $archive;

        return $this;
    }

    /**
     * @return Collection<CharterLeg>
     */
    public function getLegs(): Collection
    {
        return $this->legs;
    }

    /**
     * @param CharterLeg[] $legs 
     */
    public function setLegs(array $legs): static
    {
        $this->legs = new Collection(
            array_map(fn(CharterLeg $leg) => $leg->setCharter($this), $legs)
        );

        return $this;
    }

    public function addLeg(CharterLeg $leg): static
    {
        $this->legs->add($leg);
        $leg->setCharter($this);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "statut" => $this->getStatus(),
            "lc_debut" => $this->getLaycanStart()?->format("Y-m-d"),
            "lc_fin" => $this->getLaycanEnd()?->format("Y-m-d"),
            "cp_date" => $this->getCpDate()?->format("Y-m-d"),
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
