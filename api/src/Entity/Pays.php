<?php

namespace App\Entity;

use App\Core\Interfaces\Arrayable;

class Pays implements Arrayable
{
    private string $iso;
    private string $nom;

    public function __construct(array $rawData = [])
    {
        $this->setISO($rawData["iso"] ?? "");
        $this->setNom($rawData["nom"] ?? "");
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

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function toArray(): array
    {
        return [
            "iso" => $this->getISO(),
            "nom" => $this->getNom(),
        ];
    }
}
