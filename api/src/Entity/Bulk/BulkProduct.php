<?php

// Path: api/src/Entity/Bulk/BulkProduct.php

declare(strict_types=1);

namespace App\Entity\Bulk;

use App\Core\Array\ArrayHandler;
use App\Core\Validation\Constraints\Required;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;

/**
 * @phpstan-type BulkProductArray array{
 *                                  id: int,
 *                                  nom: string,
 *                                  couleur: string,
 *                                  unite: string,
 *                                  qualites?: BulkQualityArray[],
 *                                }
 * 
 * @phpstan-import-type BulkQualityArray from BulkQuality
 */
class BulkProduct extends AbstractEntity
{
    use IdentifierTrait;

    public const DEFAULT_COLOR = "#000000";

    #[Required("Le nom est obligatoire.")]
    public string $name = '';

    #[Required("La couleur est obligatoire.")]
    public string $color = self::DEFAULT_COLOR {
        set => $value ?: self::DEFAULT_COLOR;
    }

    public string $unit = '';

    /** @var BulkQuality[] */
    public array $qualities = [] {
        set {
            $this->qualities = \array_map(
                function ($quality) {
                    /** @disregard P1006 */
                    $quality->product = $this;
                    return $quality;
                },
                $value
            );
        }
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "nom" => $this->name,
            "couleur" => $this->color,
            "unite" => $this->unit,
            "qualites" => \array_map(fn($quality) => $quality->toArray(), $this->qualities),
        ];
    }
}
