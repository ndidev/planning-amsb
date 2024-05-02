<?php

namespace App\Entity\Bois;

use App\Core\Interfaces\Arrayable;
use App\Entity\Tiers;
use App\Service\BoisService;
use App\Service\TiersService;

class RdvBois implements Arrayable
{
    private ?int $id = null;
    private bool $attente = false;
    private ?\DateTimeImmutable $date = null;
    private ?\DateTimeImmutable $heureArrivee = null;
    private ?\DateTimeImmutable $heureDepart = null;
    private ?Tiers $fournisseur = null;
    private ?Tiers $chargement = null;
    private ?Tiers $livraison = null;
    private ?Tiers $client = null;
    private ?Tiers $transporteur = null;
    private ?Tiers $affreteur = null;
    private bool $commandePrete = false;
    private bool $confirmationAffretement = false;
    private string $numeroBL = "";
    private string $commentairePublic = "";
    private string $commentaireCache = "";

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getAttente(): bool
    {
        return $this->attente;
    }

    public function setAttente(bool|int $attente): static
    {
        $this->attente = (bool) $attente;

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

    public function setDate(\DateTimeImmutable|string|null $date): static
    {
        if (is_null($date)) {
            $this->date = null;
        } else if (is_string($date)) {
            $this->date = new \DateTimeImmutable($date);
        } else {
            $this->date = $date;
        }

        return $this;
    }

    public function getHeureArrivee(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->heureArrivee?->format("H:i");
        } else {
            return $this->heureArrivee;
        }
    }

    public function setHeureArrivee(\DateTimeImmutable|string|null $heureArrivee): static
    {
        if (is_null($heureArrivee)) {
            $this->heureArrivee = null;
        } else if (is_string($heureArrivee)) {
            $this->heureArrivee = new \DateTimeImmutable($heureArrivee);
        } else {
            $this->heureArrivee = $heureArrivee;
        }

        return $this;
    }

    public function getHeureDepart(bool $sqlFormat = false): \DateTimeImmutable|string|null
    {
        if (true === $sqlFormat) {
            return $this->heureDepart?->format("H:i");
        } else {
            return $this->heureDepart;
        }
    }

    public function setHeureDepart(\DateTimeImmutable|string|null $heureDepart): static
    {
        if (is_null($heureDepart)) {
            $this->heureDepart = null;
        } else if (is_string($heureDepart)) {
            $this->heureDepart = new \DateTimeImmutable($heureDepart);
        } else {
            $this->heureDepart = $heureDepart;
        }

        return $this;
    }

    public function getFournisseur(): ?Tiers
    {
        return $this->fournisseur;
    }

    public function setFournisseur(Tiers|int|null $fournisseur): static
    {
        if (is_null($fournisseur)) {
            $this->fournisseur = null;
        } else if (is_int($fournisseur)) {
            $this->fournisseur = (new TiersService())->getTiers($fournisseur);
        } else {
            $this->fournisseur = $fournisseur;
        }

        return $this;
    }

    public function getChargement(): ?Tiers
    {
        return $this->chargement;
    }

    public function setChargement(Tiers|int|null $chargement): static
    {
        if (is_null($chargement)) {
            $this->chargement = null;
        } else if (is_int($chargement)) {
            $this->chargement = (new TiersService())->getTiers($chargement);
        } else {
            $this->chargement = $chargement;
        }

        return $this;
    }

    public function getLivraison(): ?Tiers
    {
        return $this->livraison;
    }

    public function setLivraison(Tiers|int|null $livraison): static
    {
        if (is_null($livraison)) {
            $this->livraison = null;
        } else if (is_int($livraison)) {
            $this->livraison = (new TiersService())->getTiers($livraison);
        } else {
            $this->livraison = $livraison;
        }

        return $this;
    }

    public function getClient(): ?Tiers
    {
        return $this->client;
    }

    public function setClient(Tiers|int|null $client): static
    {
        if (is_null($client)) {
            $this->client = null;
        } else if (is_int($client)) {
            $this->client = (new TiersService())->getTiers($client);
        } else {
            $this->client = $client;
        }

        return $this;
    }

    public function getTransporteur(): ?Tiers
    {
        return $this->transporteur;
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

    public function getAffreteur(): ?Tiers
    {
        return $this->affreteur;
    }

    public function setAffreteur(Tiers|int|null $affreteur): static
    {
        if (is_null($affreteur)) {
            $this->affreteur = null;
        } else if (is_int($affreteur)) {
            $this->affreteur = (new TiersService())->getTiers($affreteur);
        } else {
            $this->affreteur = $affreteur;
        }

        return $this;
    }

    public function getCommandePrete(): bool
    {
        return $this->commandePrete;
    }

    public function setCommandePrete(bool|int $commandePrete): static
    {
        $this->commandePrete = (bool) $commandePrete;

        return $this;
    }

    public function getConfirmationAffretement(): bool
    {
        return $this->confirmationAffretement;
    }

    public function setConfirmationAffretement(bool|int $confirmationAffretement): static
    {
        $this->confirmationAffretement = (bool) $confirmationAffretement;

        return $this;
    }

    public function getNumeroBL(): string
    {
        return $this->numeroBL;
    }

    public function setNumeroBL(string $numeroBL): static
    {
        $this->numeroBL = $numeroBL;

        return $this;
    }

    public function getCommentairePublic(): string
    {
        return $this->commentairePublic;
    }

    public function setCommentairePublic(string $commentairePublic): static
    {
        $this->commentairePublic = $commentairePublic;

        return $this;
    }

    public function getCommentaireCache(): string
    {
        return $this->commentaireCache;
    }

    public function setCommentaireCache(string $commentaireCache): static
    {
        $this->commentaireCache = $commentaireCache;

        return $this;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "attente" => $this->getAttente(),
            "date_rdv" => $this->getDate(true),
            "heure_arrivee" => $this->getHeureArrivee(true),
            "heure_depart" => $this->getHeureDepart(true),
            "fournisseur" => $this->fournisseur?->getId(),
            "chargement" => $this->chargement?->getId(),
            "livraison" => $this->livraison?->getId(),
            "client" => $this->client?->getId(),
            "transporteur" => $this->transporteur?->getId(),
            "affreteur" => $this->affreteur?->getId(),
            "commande_prete" => $this->getCommandePrete(),
            "confirmation_affretement" => $this->getConfirmationAffretement(),
            "numero_bl" => $this->getNumeroBL(),
            "commentaire_public" => $this->getCommentairePublic(),
            "commentaire_cache" => $this->getCommentaireCache(),
        ];
    }
}
