<?php

namespace App\Entity;

class Port extends AbstractEntity
{
    private string $locode;
    private string $name;
    private string $displayName;

    public function __construct(array $rawData = [])
    {
        $this->setLocode($rawData["locode"] ?? "");
        $this->setName($rawData["nom"] ?? "");
    }

    public function getLocode(): string
    {
        return $this->locode;
    }

    public function setLocode(string $locode): static
    {
        $this->locode = $locode;

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

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function toArray(): array
    {
        return [
            "locode" => $this->getLocode(),
            "nom" => $this->getName(),
            "nom_affichage" => $this->getDisplayName(),
        ];
    }
}
