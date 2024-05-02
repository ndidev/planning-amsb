<?php

namespace App\Entity\Vrac;

use App\Core\Interfaces\Arrayable;
use App\Entity\Tiers;
use App\Service\VracService;
use App\Service\TiersService;

class RdvVrac implements Arrayable
{
    private ?int $id = null;
    private ?\DateTimeImmutable $date = null;
    private ?\DateTimeImmutable $heure = null;
    private ?ProduitVrac $produit = null;
    private ?QualiteVrac $qualite = null;
    private ?QuantiteVrac $quantite = null;
    private bool $commandePrete = false;
    private ?Tiers $fournisseur = null;
    private ?Tiers $client = null;
    private ?Tiers $transporteur = null;
    private string $numeroCommande = "";
    private string $commentaire = "";

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDate(\DateTimeImmutable|string $date): static
    {
        if (is_string($date)) {
            $this->date = new \DateTimeImmutable($date);
        } else {
            $this->date = $date;
        }

        return $this;
    }

    public function getDate(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->date->format("Y-m-d");
        } else {
            return $this->date;
        }
    }

    public function setHeure(\DateTimeImmutable|string|null $heure): static
    {
        if (is_null($heure)) {
            $this->heure = null;
        } else if (is_string($heure)) {
            $this->heure = new \DateTimeImmutable($heure);
        } else {
            $this->heure = $heure;
        }

        return $this;
    }

    public function getHeure(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->heure?->format("H:i");
        } else {
            return $this->heure;
        }
    }

    public function setProduit(ProduitVrac|int $produit): static
    {
        if (is_int($produit)) {
            $this->produit = (new VracService())->getProduit($produit);
        } else {
            $this->produit = $produit;
        }

        return $this;
    }

    public function getProduit(): ?ProduitVrac
    {
        return $this->produit;
    }

    public function setQualite(QualiteVrac|int|null $qualite): static
    {
        if (is_null($qualite)) {
            $this->qualite = null;
        } else if (is_int($qualite)) {
            $this->qualite = (new VracService())->getQuality($qualite);
        } else {
            $this->qualite = $qualite;
        }

        return $this;
    }

    public function getQualite(): ?QualiteVrac
    {
        return $this->qualite;
    }

    public function setQuantite(int $value, bool $max): static
    {
        if (!isset($this->quantite)) {
            $this->quantite = new QuantiteVrac($value, $max);
        } else {
            $this->quantite->setValue($value)->setMax($max);
        }

        return $this;
    }

    public function getQuantite(): ?QuantiteVrac
    {
        return $this->quantite;
    }

    public function setCommandePrete(bool|int $commandePrete): static
    {
        $this->commandePrete = (bool) $commandePrete;

        return $this;
    }

    public function getCommandePrete(): bool
    {
        return $this->commandePrete;
    }

    public function setFournisseur(Tiers|int $supplier): static
    {
        if (is_int($supplier)) {
            $this->fournisseur = (new TiersService())->getTiers($supplier);
        } else {
            $this->fournisseur = $supplier;
        }

        return $this;
    }

    public function getFournisseur(): ?Tiers
    {
        return $this->fournisseur;
    }

    public function setClient(Tiers|int $client): static
    {
        if (is_int($client)) {
            $this->client = (new TiersService())->getTiers($client);
        } else {
            $this->client = $client;
        }

        return $this;
    }

    public function getClient(): ?Tiers
    {
        return $this->client;
    }

    public function setTransporteur(Tiers|int|null $transporteur): static
    {
        if (is_null($transporteur)) {
            $this->transporteur = null;
        } else if (is_int($transporteur)) {
            $this->transporteur = (new TiersService())->getTiers($transporteur);
        } else {
            $this->transporteur = $transporteur;
        }

        return $this;
    }

    public function getTransporteur(): ?Tiers
    {
        return $this->transporteur;
    }

    public function setNumeroCommande(string $orderNUmber): static
    {
        $this->numeroCommande = $orderNUmber;

        return $this;
    }

    public function getNumeroCommande(): string
    {
        return $this->numeroCommande;
    }

    public function setCommentaire(string $comments): static
    {
        $this->commentaire = $comments;

        return $this;
    }

    public function getCommentaire(): string
    {
        return $this->commentaire;
    }

    public function toArray(): array
    {
        return  [
            "id" => $this->getId(),
            "date_rdv" => $this->getDate()->format("Y-m-d"),
            "heure" => $this->getHeure()?->format("H:i"),
            "produit" => $this->getProduit()->getId(),
            "qualite" => $this->getQualite()?->getId(),
            "quantite" => $this->getQuantite()->getValue(),
            "max" => $this->getQuantite()->isMax(),
            "commande_prete" => $this->getCommandePrete(),
            "fournisseur" => $this->getFournisseur()->getId(),
            "client" => $this->getClient()->getId(),
            "transporteur" => $this->getTransporteur()?->getId(),
            "num_commande" => $this->getNumeroCommande(),
            "commentaire" => $this->getCommentaire(),
        ];
    }
}
