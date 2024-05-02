<?php

namespace App\Entity\Vrac;

use App\Core\Interfaces\Arrayable;
use App\Service\VracService;

class ProduitVrac implements Arrayable
{
    private ?int $id = null;
    private string $name = "";
    private string $color = "";
    private string $unit = "";
    /** @var QualiteVrac[] */
    private array $qualities = [];

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

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

    public function setQualites(array $qualities): static
    {
        $this->qualities = array_map(
            function (array|QualiteVrac $quality) {
                if (is_array($quality)) {
                    $quality = (new VracService)->makeQualite($quality);
                }

                $quality->setProduit($this);

                return $quality;
            },
            $qualities
        );

        return $this;
    }

    /**
     * @return QualiteVrac[]
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
                fn (QualiteVrac $quality) => $quality->toArray(),
                $this->getQualities()
            ),
        ];
    }
}
