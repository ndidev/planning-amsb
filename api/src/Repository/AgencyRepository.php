<?php

// Path: api/src/Repository/AgencyRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Entity\Config\AgencyDepartment;
use App\Service\AgencyService;

/**
 * @phpstan-import-type AgencyDepartmentArray from \App\Entity\Config\AgencyDepartment
 */
final class AgencyRepository extends Repository
{
    public function __construct(private AgencyService $agencyService) {}

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param string $departmentName Identifiant du service de l'agence.
     */
    public function departmentExists(string $departmentName): bool
    {
        return $this->mysql->exists('config_agence', $departmentName, 'service');
    }

    /**
     * Récupère les services de l'agence.
     * 
     * @return Collection<AgencyDepartment> Services de l'agence.
     */
    public function fetchAllDepartments(): Collection
    {
        /** @var AgencyDepartmentArray[] */
        $departmentsRaw = $this->mysql->prepareAndExecute("SELECT * FROM config_agence")->fetchAll();

        $departments = \array_map(
            fn($departmentRaw) => $this->agencyService->makeDepartmentFromDatabase($departmentRaw),
            $departmentsRaw
        );

        return new Collection($departments);
    }

    /**
     * Récupère les données d'un service de l'agence.
     * 
     * @param string $departmentName Identifiant du service de l'agence.
     * 
     * @return ?AgencyDepartment Données du service.
     */
    public function fetchDepartment(string $departmentName): ?AgencyDepartment
    {
        /** @var ?AgencyDepartmentArray */
        $departmentRaw = $this->mysql
            ->prepareAndExecute(
                "SELECT * FROM config_agence WHERE service = :service",
                ["service" => $departmentName]
            )
            ->fetch();

        if (!\is_array($departmentRaw)) return null;

        $department = $this->agencyService->makeDepartmentFromDatabase($departmentRaw);

        return $department;
    }

    /**
     * Met à jour les données d'un service de l'agence.
     * 
     * @param AgencyDepartment $department Service de l'agence.
     * 
     * @return AgencyDepartment Données du service
     */
    public function updateDepartment(AgencyDepartment $department): AgencyDepartment
    {
        $statement =
            "UPDATE config_agence
            SET
                nom = :fullName,
                adresse_ligne_1 = :addressLine1,
                adresse_ligne_2 = :addressLine2,
                cp = :postCode,
                ville = :city,
                pays = :country,
                telephone = :phoneNumber,
                mobile = :mobileNumber,
                email = :emailAddress
            WHERE service = :service";

        $this->mysql->prepareAndExecute($statement, [
            'fullName' => $department->fullName,
            'addressLine1' => $department->addressLine1,
            'addressLine2' => $department->addressLine2,
            'postCode' => $department->postCode,
            'city' => $department->city,
            'country' => $department->country,
            'phoneNumber' => $department->phoneNumber,
            'mobileNumber' => $department->mobileNumber,
            'emailAddress' => $department->emailAddress,
            'service' => $department->service,
        ]);

        /** @var AgencyDepartment $updatedDepartment */
        $updatedDepartment = $this->fetchDepartment($department->service);

        return $updatedDepartment;
    }
}
