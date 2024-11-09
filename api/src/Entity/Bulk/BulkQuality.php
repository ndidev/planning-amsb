<?php

// Path: api/src/Entity/Bulk/BulkQuality.php

declare(strict_types=1);

namespace App\Entity\Bulk;

use App\Core\Interfaces\Arrayable;
use App\Core\Traits\IdentifierTrait;

class BulkQuality implements Arrayable
{
    use IdentifierTrait;

    private string $name = "";
    private string $color = "";
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
        return $this->color;
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

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "nom" => $this->getName(),
            "couleur" => $this->getColor(),
        ];
    }
}
