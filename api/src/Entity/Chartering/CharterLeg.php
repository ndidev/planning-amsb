<?php

// Path: api/src/Entity/Chartering/CharterLeg.php

declare(strict_types=1);

namespace App\Entity\Chartering;

use App\Core\Validation\Constraints\Required;
use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\Port;

/**
 * @phpstan-type CharterLegArray array{
 *                                 id?: int,
 *                                 charter?: int,
 *                                 bl_date?: string,
 *                                 pol?: string,
 *                                 pod?: string,
 *                                 marchandise?: string,
 *                                 quantite?: string,
 *                                 commentaire?: string,
 *                               }
 */
final class CharterLeg extends AbstractEntity
{
    use IdentifierTrait;

    public ?Charter $charter = null;

    public ?\DateTimeImmutable $blDate = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public ?string $sqlBlDate {
        get => $this->blDate?->format('Y-m-d');
    }

    #[Required("Le port de chargement est obligatoire.")]
    public ?Port $pol = null;

    #[Required("Le port de déchargement est obligatoire.")]
    public ?Port $pod = null;

    public string $commodity = '';

    public string $quantity = '';

    public string $comments = '';

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'charter' => $this->charter?->id,
            'bl_date' => $this->blDate?->format('Y-m-d'),
            'pol' => $this->pol?->locode,
            'pod' => $this->pod?->locode,
            'marchandise' => $this->commodity,
            'quantite' => $this->quantity,
            'commentaire' => $this->comments,
        ];
    }
}
