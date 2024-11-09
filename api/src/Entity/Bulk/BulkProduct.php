<?php

// Path: api/src/Entity/Bulk/BulkProduct.php

declare(strict_types=1);

namespace App\Entity\Bulk;

use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;

class BulkProduct extends AbstractEntity
{
    use IdentifierTrait;

    private string $name = "";
    private string $color = "";
    private string $unit = "";
    /** @var BulkQuality[] */
    private array $qualities = [];

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

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * @param BulkQuality[] $qualities 
     */
    public function setQualities(array $qualities): static
    {
        $this->qualities = array_map(
            function (BulkQuality $quality) {
                $quality->setProduct($this);

                return $quality;
            },
            $qualities
        );

        return $this;
    }

    /**
     * @return BulkQuality[]
     */
    public function getQualities(): array
    {
        return $this->qualities;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "nom" => $this->getName(),
            "couleur" => $this->getColor(),
            "unite" => $this->getUnit(),
            "qualites" => array_map(
                fn(BulkQuality $quality) => $quality->toArray(),
                $this->getQualities()
            ),
        ];
    }
}
