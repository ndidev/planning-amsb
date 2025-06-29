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
use App\Entity\ThirdParty\ThirdParty;

/**
 * @phpstan-type CharterArray array{
 *                              id?: int,
 *                              statut?: int,
 *                              lc_debut?: string,
 *                              lc_fin?: string,
 *                              cp_date?: string,
 *                              navire?: string,
 *                              affreteur?: int,
 *                              armateur?: int,
 *                              courtier?: int,
 *                              fret_achat?: float,
 *                              fret_vente?: float,
 *                              surestaries_achat?: float,
 *                              surestaries_vente?: float,
 *                              commentaire?: string,
 *                              archive?: bool,
 *                              legs?: CharterLegArray[]
 *                            }
 * 
 * @phpstan-import-type CharterLegArray from CharterLeg
 */
final class Charter extends AbstractEntity
{
    use IdentifierTrait;

    /** @var CharterStatus::* $status */
    public int $status = CharterStatus::PENDING {
        set(int $value) => CharterStatus::tryFrom($value);
    }

    public ?\DateTimeImmutable $laycanStart = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public ?string $sqlLaycanStart {
        get => $this->laycanStart?->format('Y-m-d');
    }

    public ?\DateTimeImmutable $laycanEnd = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public ?string $sqlLaycanEnd {
        get => $this->laycanEnd?->format('Y-m-d');
    }

    public ?\DateTimeImmutable $cpDate = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public ?string $sqlCpDate {
        get => $this->cpDate?->format('Y-m-d');
    }

    public string $vesselName = 'TBN' {
        get => $this->vesselName ?: 'TBN';
    }

    #[Required("L'affréteur est obligatoire.")]
    public ?ThirdParty $charterer = null;

    public ?ThirdParty $shipOperator = null;

    public ?ThirdParty $shipbroker = null;

    public float $freightPayed = 0.0;

    public float $freightSold = 0.0;

    public float $demurragePayed = 0.0;

    public float $demurrageSold = 0.0;

    public string $comments = '';

    public bool $isArchive = false {
        set(bool|int $value) => (bool) $value;
    }

    /**
     * @var Collection<CharterLeg>
     */
    public Collection $legs {
        /** @param CharterLeg[]|Collection<CharterLeg> $value */
        set(array|Collection $value) {
            $this->legs = $value instanceof Collection
                ? $value
                : new Collection(
                    \array_map(
                        function (CharterLeg $leg) {
                            $leg->charter = $this;
                            return $leg;
                        },
                        $value
                    )
                );
        }
    }

    public function __construct()
    {
        $this->legs = new Collection();
    }

    public function addLeg(CharterLeg $leg): static
    {
        $this->legs->add($leg);
        $leg->charter = $this;

        return $this;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "statut" => $this->status,
            "lc_debut" => $this->sqlLaycanStart,
            "lc_fin" => $this->sqlLaycanEnd,
            "cp_date" => $this->sqlCpDate,
            "navire" => $this->vesselName,
            "affreteur" => $this->charterer?->id,
            "armateur" => $this->shipOperator?->id,
            "courtier" => $this->shipbroker?->id,
            "fret_achat" => $this->freightPayed,
            "fret_vente" => $this->freightSold,
            "surestaries_achat" => $this->demurragePayed,
            "surestaries_vente" => $this->demurrageSold,
            "commentaire" => $this->comments,
            "archive" => $this->isArchive,
            "legs" => $this->legs->toArray(),
        ];
    }
}
