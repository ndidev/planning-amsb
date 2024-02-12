<?php

namespace App\Entity;

use App\Core\Interfaces\Arrayable;

class BulkProduct implements Arrayable
{
    private ?int $id;
    private string $name;
    private string $color;
    private string $unit;
    /** @var array<int, \App\Entity\BulkQuality> */
    private array $qualities;

    public function __construct(array $rawData = [])
    {
        $this->setId($rawData["id"] ?? null);
        $this->setName($rawData["nom"] ?? "");
        $this->setColor($rawData["couleur"] ?? "");
        $this->setUnit($rawData["unite"] ?? "");
        $this->setQualities($rawData["qualites"] ?? []);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getQualities(): array
    {
        return $this->qualities;
    }

    public function setQualities(array $qualities): static
    {
        $this->qualities = array_map(
            fn (array $qualityRaw) => new BulkQuality($qualityRaw),
            $qualities
        );

        return $this;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "nom" => $this->getName(),
            "couleur" => $this->getColor(),
            "unite" => $this->getUnit(),
            "qualites" => array_map(
                fn (BulkQuality $quality) => $quality->toArray(),
                $this->getQualities()
            ),
        ];
    }
}
