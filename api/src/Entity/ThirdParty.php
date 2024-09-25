<?php

// Path: api/src/Entity/ThirdParty.php

namespace App\Entity;

use App\Core\Traits\IdentifierTrait;
use App\Service\CountryService;

class ThirdParty extends AbstractEntity
{
    use IdentifierTrait;

    private string $shortName;
    private string $fullName;
    private string $addressLine1;
    private string $addressLine2;
    private string $postCode;
    private string $city;
    private Country $country;
    private string $phone;
    private string $comments;
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
    private bool $isNonEditable;
    private bool $isAgency;
    private ?string $logo;
    private bool $isActive;
    private int $appointmentCount = 0;

    public function __construct(array $rawData = [])
    {
        $this->setId($rawData["id"] ?? null);
        $this->setShortName($rawData["nom_court"] ?? "");
        $this->setFullName($rawData["nom_complet"] ?? "");
        $this->setAddressLine1($rawData["adresse_ligne_1"] ?? "");
        $this->setAddressLine2($rawData["adresse_ligne_2"] ?? "");
        $this->setPostCode($rawData["cp"] ?? "");
        $this->setCity($rawData["ville"] ?? "");
        $this->setCountry((new Country())->setISO($rawData["pays"] ?? ""));
        $this->setPhone($rawData["telephone"] ?? "");
        $this->setComments($rawData["commentaire"] ?? "");
        foreach ($this->roles as $role => $value) {
            $this->setRole($role, $rawData[$role] ?? false);
        }
        $this->setIsNonEditable($rawData["non_modifiable"] ?? false);
        $this->setIsAgency($rawData["lie_agence"] ?? false);
        $this->setLogo($rawData["logo"] ?? null);
        $this->setIsActive($rawData["actif"] ?? true);
        $this->setAppointmentCount($rawData["nombre_rdv"] ?? 0);
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): static
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): static
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    public function getAddressLine2(): string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(string $addressLine2): static
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    public function getPostCode(): string
    {
        return $this->postCode;
    }

    public function setPostCode(string $postCode): static
    {
        $this->postCode = $postCode;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    public function setCountry(Country|array $country): static
    {
        if (is_array($country)) {
            $country = (new CountryService())->makeCountry($country);
        }

        $this->country = $country;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function setComments(string $comments): static
    {
        $this->comments = $comments;

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

    public function isNonEditable(): bool
    {
        return $this->isNonEditable;
    }

    public function setIsNonEditable(bool|int $isNonEditable): static
    {
        $this->isNonEditable = (bool) $isNonEditable;

        return $this;
    }

    public function isAgency(): bool
    {
        return $this->isAgency;
    }

    public function setIsAgency(bool|int $isAgency): static
    {
        $this->isAgency = (bool) $isAgency;

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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool|int $isActive): static
    {
        $this->isActive = (bool) $isActive;

        return $this;
    }

    public function getAppointmentCount(): int
    {
        return $this->appointmentCount;
    }

    public function setAppointmentCount(int $appointmentCount): static
    {
        $this->appointmentCount = $appointmentCount;

        return $this;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "nom_court" => $this->getShortName(),
            "nom_complet" => $this->getFullName(),
            "adresse_ligne_1" => $this->getAddressLine1(),
            "adresse_ligne_2" => $this->getAddressLine2(),
            "cp" => $this->getPostCode(),
            "ville" => $this->getCity(),
            "pays" => $this->country->getISO(),
            "telephone" => $this->getPhone(),
            "commentaire" => $this->getComments(),
            "roles" => $this->getRoles(),
            "non_modifiable" => $this->isNonEditable(),
            "lie_agence" => $this->isAgency(),
            "logo" => $this->getLogo(),
            "actif" => $this->isActive(),
            "nombre_rdv" => $this->getAppointmentCount(),
        ];
    }
}
