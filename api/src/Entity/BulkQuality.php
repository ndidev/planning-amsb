<?php

namespace App\Entity;

use App\Core\Interfaces\Arrayable;

class BulkQuality implements Arrayable
{
    private ?int $id;
    private string $name;
    private string $color;

    public function __construct(array $rawData = [])
    {
        $this->setId($rawData["id"] ?? null);
        $this->setName($rawData["nom"] ?? "");
        $this->setColor($rawData["couleur"] ?? "");
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

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "nom" => $this->getName(),
            "couleur" => $this->getColor(),
        ];
    }
}
