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

        $department = (new AgencyDepartment())
            ->setService($rawDataAH->getString('service'))
            ->setDisplayName($rawDataAH->getString('affichage'))
            ->setFullName($rawDataAH->getString('nom'))
            ->setAddressLine1($rawDataAH->getString('adresse_ligne_1'))
            ->setAddressLine2($rawDataAH->getString('adresse_ligne_2'))
            ->setPostCode($rawDataAH->getString('cp'))
            ->setCity($rawDataAH->getString('ville'))
            ->setCountry($rawDataAH->getString('pays'))
            ->setPhone($rawDataAH->getString('telephone'))
            ->setMobile($rawDataAH->getString('mobile'))
            ->setEmail($rawDataAH->getString('email'));

        return $department;
    }

    /**
     * @param HTTPRequestBody $rawData
     * 
     * @return AgencyDepartment
     */
    public function makeDepartmentFromForm(HTTPRequestBody $rawData): AgencyDepartment
    {
        $department = (new AgencyDepartment())
            ->setFullName($rawData->getString('nom'))
            ->setAddressLine1($rawData->getString('adresse_ligne_1'))
            ->setAddressLine2($rawData->getString('adresse_ligne_2'))
            ->setPostCode($rawData->getString('cp'))
            ->setCity($rawData->getString('ville'))
            ->setCountry($rawData->getString('pays'))
            ->setPhone($rawData->getString('telephone'))
            ->setMobile($rawData->getString('mobile'))
            ->setEmail($rawData->getString('email'));

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
     * 
     * @param string          $departmentName 
     * @param HTTPRequestBody $input 
     * 
     * @return AgencyDepartment 
     */
    public function updateDepartment(string $departmentName, HTTPRequestBody $input): AgencyDepartment
    {
        $department = $this->makeDepartmentFromForm($input)->setService($departmentName);

        $department->validate();

        return $this->agencyRepository->updateDepartment($department);
    }
}
