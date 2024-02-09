<?php

namespace App\Entity;

use App\Core\Interfaces\Arrayable;

class ThirdParty implements Arrayable
{
    private ?int $id = null;
    private string $nom_court = "";
    private string $nom_complet = "";
    private string $adresse_ligne_1 = "";
    private string $adresse_ligne_2 = "";
    private string $cp = "";
    private string $ville = "";
    private Country $pays;
    private string $telephone = "";
    private string $commentaire = "";
    private array $roles = [
        "bois_fournisseur" => false,
        "bois_client" => false,
        "bois_transporteur" => false,
        "bois_affreteur" => false,
        "vrac_fournisseur" => false,
        "vrac_client" => false,
        "vrac_transporteur" => false,
        "maritime_armateur" => false,
        "maritime_affreteur" => false,
        "maritime_courtier" => false,
    ];
    private bool $non_modifiable = false;
    private bool $lie_agence = false;
    private ?string $logo = null;
    private bool $actif = true;

    public function __construct(array $rawData = [])
    {
        $this->setId($rawData["id"] ?? null);
        $this->setNomCourt($rawData["nom_court"] ?? "");
        $this->setNomComplet($rawData["nom_complet"] ?? "");
        $this->setAdresseLigne1($rawData["adresse_ligne_1"] ?? "");
        $this->setAdresseLigne2($rawData["adresse_ligne_2"] ?? "");
        $this->setCodePostal($rawData["cp"] ?? "");
        $this->setVille($rawData["ville"] ?? "");
        $this->setCountry((new Country())->setISO($rawData["pays"] ?? ""));
        $this->setTelephone($rawData["telephone"] ?? "");
        $this->setCommentaire($rawData["commentaire"] ?? "");
        foreach ($this->roles as $role => $value) {
            $this->setRole($role, $rawData[$role] ?? false);
        }
        $this->setNonModifiable($rawData["non_modifiable"] ?? false);
        $this->setLieAgence($rawData["lie_agence"] ?? false);
        $this->setLogo($rawData["logo"] ?? null);
        $this->setActif($rawData["actif"] ?? true);
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

    public function getNomCourt(): string
    {
        return $this->nom_court;
    }

    public function setNomCourt(string $nom_court): static
    {
        $this->nom_court = $nom_court;

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->nom_complet;
    }

    public function setNomComplet(string $nom_complet): static
    {
        $this->nom_complet = $nom_complet;

        return $this;
    }

    public function getAdresseLigne1(): string
    {
        return $this->adresse_ligne_1;
    }

    public function setAdresseLigne1(string $adresse_ligne_1): static
    {
        $this->adresse_ligne_1 = $adresse_ligne_1;

        return $this;
    }

    public function getAdresseLigne2(): string
    {
        return $this->adresse_ligne_2;
    }

    public function setAdresseLigne2(string $adresse_ligne_2): static
    {
        $this->adresse_ligne_2 = $adresse_ligne_2;

        return $this;
    }

    public function getCodePostal(): string
    {
        return $this->cp;
    }

    public function setCodePostal(string $cp): static
    {
        $this->cp = $cp;

        return $this;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getPays(): Country
    {
        return $this->pays;
    }

    public function setCountry(Country $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getCommentaire(): string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getRole(string $role): bool
    {
        return $this->roles[$role] ?? false;
    }

    public function setRole(string $role, bool|int $value): static
    {
        $this->roles[$role] = (bool) $value;

        return $this;
    }

    public function getNonModifiable(): bool
    {
        return $this->non_modifiable;
    }

    public function setNonModifiable(bool|int $non_modifiable): static
    {
        $this->non_modifiable = (bool) $non_modifiable;

        return $this;
    }

    public function getLieAgence(): bool
    {
        return $this->lie_agence;
    }

    public function setLieAgence(bool|int $lie_agence): static
    {
        $this->lie_agence = (bool) $lie_agence;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        if (is_null($logo)) {
            $this->logo = null;
        } else {
            $this->logo = $_ENV["LOGOS_URL"] . "/" . $logo;
        }

        return $this;
    }

    public function getActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool|int $actif): static
    {
        $this->actif = (bool) $actif;

        return $this;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "nom_court" => $this->nom_court,
            "nom_complet" => $this->nom_complet,
            "adresse_ligne_1" => $this->adresse_ligne_1,
            "adresse_ligne_2" => $this->adresse_ligne_2,
            "cp" => $this->cp,
            "ville" => $this->ville,
            "pays" => $this->pays->getISO(),
            "telephone" => $this->telephone,
            "commentaire" => $this->commentaire,
            "roles" => $this->roles,
            "non_modifiable" => $this->non_modifiable,
            "lie_agence" => $this->lie_agence,
            "logo" => $this->logo,
            "actif" => $this->actif,
        ];
    }
}
