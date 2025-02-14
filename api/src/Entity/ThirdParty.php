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

    /**
     * Filename of the logo, or `null` if no logo, or `false` if the logo if left unchanged.
     */
    public string|null|false $logoFilename = null;

    public ?string $logoUrl {
        get => $this->logoFilename ? Environment::getString('LOGOS_URL') . "/" . $this->logoFilename : null;
    }

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

    public function setRole(string $role, bool|int $value): static
    {
        $this->roles[$role] = (bool) $value;

        return $this;
    }

    public function getRole(string $role): bool
    {
        return $this->roles[$role] ?? false;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "nom_court" => $this->shortName,
            "nom_complet" => $this->fullName,
            "adresse_ligne_1" => $this->addressLine1,
            "adresse_ligne_2" => $this->addressLine2,
            "cp" => $this->postCode,
            "ville" => $this->city,
            "pays" => $this->country?->iso,
            "telephone" => $this->phone,
            "commentaire" => $this->comments,
            "roles" => $this->roles,
            "non_modifiable" => $this->isNonEditable,
            "lie_agence" => $this->isAgency,
            "logo" => $this->logoUrl,
            "actif" => $this->isActive,
            "nombre_rdv" => $this->appointmentCount,
        ];
    }
}
