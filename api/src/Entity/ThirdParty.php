<?php

// Path: api/src/Entity/ThirdParty.php

namespace App\Entity;

use App\Core\Traits\IdentifierTrait;
use App\Service\CountryService;

/**
 * @phpstan-type ThirdPartyRoles array{
 *                                 bois_fournisseur: bool,
 *                                 bois_client: bool,
 *                                 bois_transporteur: bool,
 *                                 bois_affreteur: bool,
 *                                 vrac_fournisseur: bool,
 *                                 vrac_client: bool,
 *                                 vrac_transporteur: bool,
 *                                 maritime_armateur: bool,
 *                                 maritime_affreteur: bool,
 *                                 maritime_courtier: bool,
 *                               }
 */
class ThirdParty extends AbstractEntity
{
    use IdentifierTrait;

    private string $shortName = '';
    private string $fullName = '';
    private string $addressLine1 = '';
    private string $addressLine2 = '';
    private string $postCode = '';
    private string $city = '';
    private ?Country $country = null;
    private string $phone = '';
    private string $comments = '';
    /** @phpstan-var ThirdPartyRoles $roles */
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

    public function __construct() {}

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): static
    {
        $this->shortName = trim($shortName);

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = trim($fullName);

        return $this;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): static
    {
        $this->addressLine1 = trim($addressLine1);

        return $this;
    }

    public function getAddressLine2(): string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(string $addressLine2): static
    {
        $this->addressLine2 = trim($addressLine2);

        return $this;
    }

    public function getPostCode(): string
    {
        return $this->postCode;
    }

    public function setPostCode(string $postCode): static
    {
        $this->postCode = trim($postCode);

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = trim($city);

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(Country|string|null $country): static
    {
        if (is_string($country)) {
            $this->country = (new CountryService())->getCountry($country);
        } else {
            $this->country = $country;
        }

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = trim($phone);

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

    /**
     * Get the roles.
     * 
     * @return array<string, bool>
     * 
     * @phpstan-return ThirdPartyRoles
     */
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

        return $_ENV["LOGOS_URL"] . "/" . $this->logo;
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
