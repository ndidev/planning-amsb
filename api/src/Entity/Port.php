<?php

namespace App\Entity;

use App\Core\Interfaces\Arrayable;

class Port implements Arrayable
{
    private string $locode = "";
    private string $nom = "";

    public function __construct(array $rawData = [])
    {
        $this->setLocode($rawData["locode"] ?? "");
        $this->setNom($rawData["nom"] ?? "");
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

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getNomAffichage(): string
    {
        $nomAffichage = $this->nom . ", " . substr($this->locode, 0, 2);

        return $nomAffichage;
    }

    public function toArray(): array
    {
        return [
            "locode" => $this->getLocode(),
            "nom" => $this->getNom(),
            "nom_affichage" => $this->getNomAffichage(),
        ];
    }
}
