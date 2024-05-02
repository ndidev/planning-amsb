<?php

namespace App\Entity;

use App\Core\Interfaces\Arrayable;

class Tiers implements Arrayable
{
    private ?int $id;
    private string $nomCourt;
    private string $nomComplet;
    private string $adresseLigne1;
    private string $adressLigne2;
    private string $codePostal;
    private string $ville;
    private Pays $pays;
    private string $telephone;
    private string $commentaire;
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
    private bool $nonModifiable;
    private bool $estAgence;
    private ?string $logo;
    private bool $actif;
    private int $nombeRdv = 0;

    public function __construct(array $rawData = [])
    {
        $this->setId($rawData["id"] ?? null);
        $this->setNomCourt($rawData["nom_court"] ?? "");
        $this->setNomComplet($rawData["nom_complet"] ?? "");
        $this->setAdresseLigne1($rawData["adresse_ligne_1"] ?? "");
        $this->setAdresseLigne2($rawData["adresse_ligne_2"] ?? "");
        $this->setCodePostal($rawData["cp"] ?? "");
        $this->setVille($rawData["ville"] ?? "");
        $this->setPays((new Pays())->setISO($rawData["pays"] ?? ""));
        $this->setTelephone($rawData["telephone"] ?? "");
        $this->setCommentaire($rawData["commentaire"] ?? "");
        foreach ($this->roles as $role => $value) {
            $this->setRole($role, $rawData[$role] ?? false);
        }
        $this->setNonModifiable($rawData["non_modifiable"] ?? false);
        $this->setEstAgence($rawData["lie_agence"] ?? false);
        $this->setLogo($rawData["logo"] ?? null);
        $this->setActif($rawData["actif"] ?? true);
        $this->setNombreRdv($rawData["nombre_rdv"] ?? 0);
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
        return $this->nomCourt;
    }

    public function setNomCourt(string $shortName): static
    {
        $this->nomCourt = $shortName;

        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->nomComplet;
    }

    public function setNomComplet(string $fullName): static
    {
        $this->nomComplet = $fullName;

        return $this;
    }

    public function getAdresseLigne1(): string
    {
        return $this->adresseLigne1;
    }

    public function setAdresseLigne1(string $addressLine1): static
    {
        $this->adresseLigne1 = $addressLine1;

        return $this;
    }

    public function getAdresseLigne2(): string
    {
        return $this->adressLigne2;
    }

    public function setAdresseLigne2(string $addressLine2): static
    {
        $this->adressLigne2 = $addressLine2;

        return $this;
    }

    public function getCodePostal(): string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $postCode): static
    {
        $this->codePostal = $postCode;

        return $this;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $city): static
    {
        $this->ville = $city;

        return $this;
    }

    public function getCountry(): Pays
    {
        return $this->pays;
    }

    public function setPays(Pays $country): static
    {
        $this->pays = $country;

        return $this;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $phone): static
    {
        $this->telephone = $phone;

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

    public function getRoles(): array
    {
        return $this->roles;
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
        return $this->nonModifiable;
    }

    public function setNonModifiable(bool|int $nonModifiable): static
    {
        $this->nonModifiable = (bool) $nonModifiable;

        return $this;
    }

    public function getEstAgence(): bool
    {
        return $this->estAgence;
    }

    public function setEstAgence(bool|int $estAgence): static
    {
        $this->estAgence = (bool) $estAgence;

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

    public function getNombreRdv(): int
    {
        return $this->nombeRdv;
    }

    public function setNombreRdv(int $nombreRdv): static
    {
        $this->nombeRdv = $nombreRdv;

        return $this;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "nom_court" => $this->getNomCourt(),
            "nom_complet" => $this->getNomComplet(),
            "adresse_ligne_1" => $this->getAdresseLigne1(),
            "adresse_ligne_2" => $this->getAdresseLigne2(),
            "cp" => $this->getCodePostal(),
            "ville" => $this->getVille(),
            "pays" => $this->pays->getISO(),
            "telephone" => $this->getTelephone(),
            "commentaire" => $this->getCommentaire(),
            "roles" => $this->getRoles(),
            "non_modifiable" => $this->getNonModifiable(),
            "lie_agence" => $this->getEstAgence(),
            "logo" => $this->getLogo(),
            "actif" => $this->getActif(),
            "nombre_rdv" => $this->getNombreRdv(),
        ];
    }
}
