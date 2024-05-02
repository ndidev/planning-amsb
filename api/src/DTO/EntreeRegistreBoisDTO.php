<?php

namespace App\DTO;

use App\Core\DateUtils;

class EntreeRegistreBoisDTO
{
    private string $dateRdv;
    private ?string $fournisseur = null;
    private ?string $chargementNom = null;
    private ?string $chargementVille = null;
    private ?string $chargementPays = null;
    private ?string $livraisonNom = null;
    private ?string $livraisonCp = null;
    private ?string $livraisonVille = null;
    private ?string $livraisonPays = null;
    private string $numeroBl = "";
    private ?string $transporteur = null;

    public function setDateRdv(string $dateRdv): static
    {
        $this->dateRdv = $dateRdv;

        return $this;
    }

    public function getDateRdv(): string
    {
        return date('d/m/Y', strtotime($this->dateRdv));
    }

    public function getMois(): string
    {
        return DateUtils::format("LLLL", new \DateTime($this->dateRdv));
    }

    public function setFournisseur(?string $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getFournisseur(): string
    {
        return (string) $this->fournisseur;
    }

    public function setChargementNom(?string $chargementNom): static
    {
        $this->chargementNom = $chargementNom;

        return $this;
    }

    public function getChargementNom(): string
    {
        return (string) $this->chargementNom;
    }

    public function setChargementVille(?string $chargementVille): static
    {
        $this->chargementVille = $chargementVille;

        return $this;
    }

    public function getChargementVille(): string
    {
        return (string) $this->chargementVille;
    }

    public function setChargementPays(?string $chargementPays): static
    {
        $this->chargementPays = $chargementPays;

        return $this;
    }

    public function getChargementPays(): string
    {
        return (string) $this->chargementPays;
    }

    public function setLivraisonNom(?string $livraisonNom): static
    {
        $this->livraisonNom = $livraisonNom;

        return $this;
    }

    public function getLivraisonNom(): string
    {
        return (string) $this->livraisonNom;
    }

    public function setLivraisonCp(?string $livraisonCp): static
    {
        $this->livraisonCp = $livraisonCp;

        return $this;
    }

    public function getLivraisonCp(): string
    {
        return (string) $this->livraisonCp;
    }

    public function setLivraisonVille(?string $livraisonVille): static
    {
        $this->livraisonVille = $livraisonVille;

        return $this;
    }

    public function getLivraisonVille(): string
    {
        return (string) $this->livraisonVille;
    }

    public function setLivraisonPays(?string $livraisonPays): static
    {
        $this->livraisonPays = $livraisonPays;

        return $this;
    }

    public function getLivraisonPays(): string
    {
        return (string) $this->livraisonPays;
    }

    public function setNumeroBl(string $numeroBl): static
    {
        $this->numeroBl = $numeroBl;

        return $this;
    }

    public function getNumeroBl(): string
    {
        return (string) $this->numeroBl;
    }

    public function setTransporteur(?string $transporteur): static
    {
        $this->transporteur = $transporteur;

        return $this;
    }

    public function getTransporteur(): string
    {
        return (string) $this->transporteur;
    }

    public function getChargement(): string
    {
        if ($this->chargementNom === "AMSB") {
            return "AMSB";
        } else {
            return $this->chargementNom
                . ' '
                . $this->chargementVille
                . (strtolower($this->chargementPays) == 'france'
                    ? ""
                    : " ({$this->chargementPays})");
        }
    }

    public function getLivraison(): string
    {
        if ($this->livraisonNom) {
            if (strtolower($this->livraisonPays) === 'france') {
                $livraison_departement = " " . substr($this->livraisonCp, 0, 2);
                $this->livraisonPays = "";
            } else {
                $livraison_departement = "";
                $this->livraisonPays = " ({$this->livraisonPays})";
            }

            return $this->livraisonNom
                . $livraison_departement
                . ' '
                . $this->livraisonVille
                . $this->livraisonPays;
        } else {
            return "Pas de lieu de livraison renseigné";
        }
    }
}
