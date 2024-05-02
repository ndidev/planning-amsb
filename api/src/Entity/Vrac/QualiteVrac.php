<?php

namespace App\Entity\Vrac;

use App\Core\Interfaces\Arrayable;

class QualiteVrac implements Arrayable
{
    private ?int $id = null;
    private string $name = "";
    private string $color = "";
    private ?ProduitVrac $produit = null;

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

    public function setProduit(ProduitVrac $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function getProduit(): ?ProduitVrac
    {
        return $this->produit;
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
