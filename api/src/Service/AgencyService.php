<?php

// Path: api/src/Service/AgencyService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\HTTP\HTTPRequestBody;
use App\Entity\Config\AgencyDepartment;
use App\Repository\AgencyRepository;

/**
 * @phpstan-import-type AgencyDepartmentArray from \App\Entity\Config\AgencyDepartment
 */
final class AgencyService
{
    private AgencyRepository $agencyRepository;

    public function __construct()
    {
        $this->agencyRepository = new AgencyRepository($this);
    }

    /**
     * @param AgencyDepartmentArray $rawData
     */
    public function makeDepartmentFromDatabase(array $rawData): AgencyDepartment
    {
        $rawDataAH = new ArrayHandler($rawData);

        $department = new AgencyDepartment();
        $department->service = $rawDataAH->getString('service');
        $department->displayName = $rawDataAH->getString('affichage');
        $department->fullName = $rawDataAH->getString('nom');
        $department->addressLine1 = $rawDataAH->getString('adresse_ligne_1');
        $department->addressLine2 = $rawDataAH->getString('adresse_ligne_2');
        $department->postCode = $rawDataAH->getString('cp');
        $department->city = $rawDataAH->getString('ville');
        $department->country = $rawDataAH->getString('pays');
        $department->phoneNumber = $rawDataAH->getString('telephone');
        $department->mobileNumber = $rawDataAH->getString('mobile');
        $department->emailAddress = $rawDataAH->getString('email');

        return $department;
    }

    public function makeDepartmentFromForm(HTTPRequestBody $rawData): AgencyDepartment
    {
        $department = new AgencyDepartment();
        $department->fullName = $rawData->getString('nom');
        $department->addressLine1 = $rawData->getString('adresse_ligne_1');
        $department->addressLine2 = $rawData->getString('adresse_ligne_2');
        $department->postCode = $rawData->getString('cp');
        $department->city = $rawData->getString('ville');
        $department->country = $rawData->getString('pays');
        $department->phoneNumber = $rawData->getString('telephone');
        $department->mobileNumber = $rawData->getString('mobile');
        $department->emailAddress = $rawData->getString('email');

        return $department;
    }

    public function departmentExists(string $departmentName): bool
    {
        return $this->agencyRepository->departmentExists($departmentName);
    }

    /**
     * @return Collection<AgencyDepartment>
     */
    public function getAllDepartments(): Collection
    {
        return $this->agencyRepository->fetchAllDepartments();
    }

    public function getDepartment(string $departmentName): ?AgencyDepartment
    {
        return $this->agencyRepository->fetchDepartment($departmentName);
    }

    /**
     * Update a department.
     */
    public function updateDepartment(string $departmentName, HTTPRequestBody $input): AgencyDepartment
    {
        $department = $this->makeDepartmentFromForm($input);
        $department->service = $departmentName;

        $department->validate();

        return $this->agencyRepository->updateDepartment($department);
    }
}
