<?php

// Path: api/src/Entity/ThirdParty.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Array\Environment;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Required;

class ThirdParty extends AbstractEntity
{
    use IdentifierTrait;

    private string $shortName = '';

    #[Required("Le nom complet est obligatoire.")]
    private string $fullName = '';

    private string $addressLine1 = '';

    private string $addressLine2 = '';

    private string $postCode = '';

    #[Required("La ville est obligatoire.")]
    private string $city = '';

    #[Required("Le pays est obligatoire.")]
    private ?Country $country = null;

    private string $phone = '';

    private string $comments = '';

    /** @var array<string, bool> $roles */
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

    private bool $isNonEditable = false;

    private bool $isAgency = false;

    private string|null|false $logo = null;

    private bool $isActive = true;

    private int $appointmentCount = 0;

    public function setShortName(string $shortName): static
    {
        $this->shortName = trim($shortName);

        return $this;
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = trim($fullName);

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setAddressLine1(string $addressLine1): static
    {
        $this->addressLine1 = trim($addressLine1);

        return $this;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function setAddressLine2(string $addressLine2): static
    {
        $this->addressLine2 = trim($addressLine2);

        return $this;
    }

    public function getAddressLine2(): string
    {
        return $this->addressLine2;
    }

    public function setPostCode(string $postCode): static
    {
        $this->postCode = trim($postCode);

        return $this;
    }

    public function getPostCode(): string
    {
        return $this->postCode;
    }

    public function setCity(string $city): static
    {
        $this->city = trim($city);

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCountry(?Country $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = trim($phone);

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setComments(string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    /**
     * Get the roles.
     * 
     * @return array<string, bool>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRole(string $role, bool|int $value): static
    {
        $this->roles[$role] = (bool) $value;

        return $this;
    }

    public function getRole(string $role): bool
    {
        return $this->roles[$role] ?? false;
    }

    public function setNonEditable(bool|int $isNonEditable): static
    {
        $this->isNonEditable = (bool) $isNonEditable;

        return $this;
    }

    public function isNonEditable(): bool
    {
        return $this->isNonEditable;
    }

    public function setIsAgency(bool|int $isAgency): static
    {
        $this->isAgency = (bool) $isAgency;

        return $this;
    }

    public function isAgency(): bool
    {
        return $this->isAgency;
    }

    /**
     * Set the logo.
     * 
     * @param string|null|false $logo Filename of the logo, or null if no logo, or false if the logo if left unchanged.
     */
    public function setLogo(string|null|false $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get the logo.
     * 
     * @return string|null|false Filename of the logo, or `null` if no logo, or `false` if the logo if left unchanged.
     */
    public function getLogoFilename(): string|null|false
    {
        return $this->logo;
    }

    /**
     * Get the URL of the logo.
     */
    public function getLogoURL(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        return Environment::getString('LOGOS_URL') . "/" . $this->logo;
    }

    public function setActive(bool|int $isActive): static
    {
        $this->isActive = (bool) $isActive;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setAppointmentCount(int $appointmentCount): static
    {
        $this->appointmentCount = $appointmentCount;

        return $this;
    }

    public function getAppointmentCount(): int
    {
        return $this->appointmentCount;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "nom_court" => $this->getShortName(),
            "nom_complet" => $this->getFullName(),
            "adresse_ligne_1" => $this->getAddressLine1(),
            "adresse_ligne_2" => $this->getAddressLine2(),
            "cp" => $this->getPostCode(),
            "ville" => $this->getCity(),
            "pays" => $this->getCountry()?->getISO(),
            "telephone" => $this->getPhone(),
            "commentaire" => $this->getComments(),
            "roles" => $this->getRoles(),
            "non_modifiable" => $this->isNonEditable(),
            "lie_agence" => $this->isAgency(),
            "logo" => $this->getLogoURL(),
            "actif" => $this->isActive(),
            "nombre_rdv" => $this->getAppointmentCount(),
        ];
    }
}
