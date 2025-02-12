<?php

// Path: api/src/Entity/ThirdParty.php

declare(strict_types=1);

namespace App\Entity;

use App\Core\Array\ArrayHandler;
use App\Core\Array\Environment;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Required;

/**
 * @phpstan-type ThirdPartyArray array{
 *                                id: int,
 *                                nom_court: string,
 *                                nom_complet: string,
 *                                adresse_ligne_1: string,
 *                                adresse_ligne_2: string,
 *                                cp: string,
 *                                ville: string,
 *                                pays: string,
 *                                telephone: string,
 *                                commentaire: string,
 *                                non_modifiable: bool,
 *                                lie_agence: bool,
 *                                roles: string|ArrayHandler,
 *                                logo: string,
 *                                actif: bool,
 *                                nombre_rdv?: int
 *                              }
 */
class ThirdParty extends AbstractEntity
{
    use IdentifierTrait;

    public string $shortName = '';

    #[Required("Le nom complet est obligatoire.")]
    public string $fullName = '';

    public string $addressLine1 = '';

    public string $addressLine2 = '';

    public string $postCode = '';

    #[Required("La ville est obligatoire.")]
    public string $city = '';

    #[Required("Le pays est obligatoire.")]
    public ?Country $country = null;

    public string $phone = '';

    public string $comments = '';

    /** @var array<string, bool> $roles */
    public array $roles = [
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

    public bool $isNonEditable = false;

    public bool $isAgency = false;

    public string|null|false $logo = null;

    public bool $isActive = true;

    public int $appointmentCount = 0;

    /**
     * @param ArrayHandler|ThirdPartyArray|null $data 
     */
    public function __construct(ArrayHandler|array|null $data = null)
    {
        if (null === $data) {
            return;
        }

        $dataAH = $data instanceof ArrayHandler ? $data : new ArrayHandler($data);

        $this->id = $dataAH->getInt('id');
        $this->shortName = $dataAH->getString('nom_court');
        $this->fullName = $dataAH->getString('nom_complet');
        $this->addressLine1 = $dataAH->getString('adresse_ligne_1');
        $this->addressLine2 = $dataAH->getString('adresse_ligne_2');
        $this->postCode = $dataAH->getString('cp');
        $this->city = $dataAH->getString('ville');
        $this->phone = $dataAH->getString('telephone');
        $this->comments = $dataAH->getString('commentaire');
        $this->isNonEditable = $dataAH->getBool('non_modifiable', false);
        $this->isAgency = $dataAH->getBool('lie_agence', false);
        $this->isActive = $dataAH->getBool('actif', true);

        /** @var string|ArrayHandler */
        $rolesArray = $dataAH->get('roles');
        if (\is_string($rolesArray)) {
            $rolesArray = \json_decode($rolesArray, true);
        }
        if (\is_array($rolesArray)) {
            $roles = new ArrayHandler($rolesArray);
        } else {
            $roles = $rolesArray;
        }
        if (!$roles instanceof ArrayHandler) {
            throw new \InvalidArgumentException("Roles must be an array or an ArrayHandler.");
        }

        foreach ($this->roles as $role => $default) {
            $this->roles[$role] = $roles->getBool($role, $default);
        }
    }

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
