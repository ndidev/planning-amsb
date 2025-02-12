<?php

// Path: api/src/Entity/Bulk/BulkQuality.php

declare(strict_types=1);

namespace App\Entity\Bulk;

use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;

/**
 * @phpstan-type BulkQualityArray array{
 *                                  id: int,
 *                                  produit: int,
 *                                  nom: string,
 *                                  couleur: string,
 *                                }
 */
class BulkQuality extends AbstractEntity
{
    use IdentifierTrait;

    public const DEFAULT_COLOR = "#000000";

    #[Required("Le nom est obligatoire.")]
    public string $name = '';

    #[Required("La couleur est obligatoire.")]
    public string $color = self::DEFAULT_COLOR {
        set => $value ?: self::DEFAULT_COLOR;
    }

    public ?BulkProduct $product = null;

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "nom" => $this->name,
            "couleur" => $this->color,
        ];
    }
}
