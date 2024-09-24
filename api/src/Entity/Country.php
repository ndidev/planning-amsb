<?php

namespace App\Entity;

class Country extends AbstractEntity
{
    private string $iso;
    private string $name;

    public function __construct(array $rawData = [])
    {
        $this->setISO($rawData["iso"] ?? "");
        $this->setName($rawData["nom"] ?? "");
    }

    public function getISO(): string
    {
        return $this->iso;
    }

    public function setISO(string $iso): static
    {
        $this->iso = $iso;

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

    public function toArray(): array
    {
        return [
            "iso" => $this->getISO(),
            "nom" => $this->getName(),
        ];
    }
}
