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
    private string $name = "";

    #[Required("La couleur est obligatoire.")]
    private string $color = self::DEFAULT_COLOR;

    private ?BulkProduct $product = null;

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color ?: self::DEFAULT_COLOR;
    }

    public function setProduct(BulkProduct $produit): static
    {
        $this->product = $produit;

        return $this;
    }

    public function getProduct(): ?BulkProduct
    {
        return $this->product;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "nom" => $this->getName(),
            "couleur" => $this->getColor(),
        ];
    }
}
