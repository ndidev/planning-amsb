<?php

// Path: api/src/Entity/AgencyDepartment.php

declare(strict_types=1);

namespace App\Entity\Config;

use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;

/**
 * @phpstan-type AgencyDepartmentArray array{
 *                                       service: string,
 *                                       affichage: string,
 *                                       nom: string,
 *                                       adresse_ligne_1: string,
 *                                       adresse_ligne_2: string,
 *                                       cp: string,
 *                                       ville: string,
 *                                       pays: string,
 *                                       telephone: string,
 *                                       mobile: string,
 *                                       email: string
 *                                     }
 */
final class AgencyDepartment extends AbstractEntity
{
    public string $service = '';

    public string $displayName = '';

    #[Required("Le nom est obligatoire.")]
    public string $fullName = '';

    public string $addressLine1 = '';

    public string $addressLine2 = '';

    public string $postCode = '';

    public string $city = '';

    public string $country = '';

    public string $phoneNumber = '';

    public string $mobileNumber = '';

    public string $emailAddress = '';

    #[\Override]
    public function toArray(): array
    {
        return [
            "service" => $this->service,
            "affichage" => $this->displayName,
            "nom" => $this->fullName,
            "adresse_ligne_1" => $this->addressLine1,
            "adresse_ligne_2" => $this->addressLine2,
            "cp" => $this->postCode,
            "ville" => $this->city,
            "pays" => $this->country,
            "telephone" => $this->phoneNumber,
            "mobile" => $this->mobileNumber,
            "email" => $this->emailAddress,
        ];
    }
}
