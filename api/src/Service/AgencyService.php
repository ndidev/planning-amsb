<?php

// Path: api/src/Service/AgencyService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\Config\AgencyDepartment;
use App\Repository\AgencyRepository;

class AgencyService
{
    private AgencyRepository $agencyRepository;

    public function __construct()
    {
        $this->agencyRepository = new AgencyRepository();
    }

    public function makeDepartmentFromDatabase(array $rawData): AgencyDepartment
    {
        $department = (new AgencyDepartment())
            ->setService($rawData['service'])
            ->setDisplayName($rawData['affichage'])
            ->setFullName($rawData['nom'])
            ->setAddressLine1($rawData['adresse_ligne_1'])
            ->setAddressLine2($rawData['adresse_ligne_2'])
            ->setPostCode($rawData['cp'])
            ->setCity($rawData['ville'])
            ->setCountry($rawData['pays'])
            ->setPhone($rawData['telephone'])
            ->setMobile($rawData['mobile'])
            ->setEmail($rawData['email']);

        return $department;
    }

    public function makeDepartmentFromForm(array $rawData): AgencyDepartment
    {
        $department = (new AgencyDepartment())
            ->setFullName($rawData['nom'])
            ->setAddressLine1($rawData['adresse_ligne_1'])
            ->setAddressLine2($rawData['adresse_ligne_2'])
            ->setPostCode($rawData['cp'])
            ->setCity($rawData['ville'])
            ->setCountry($rawData['pays'])
            ->setPhone($rawData['telephone'])
            ->setMobile($rawData['mobile'])
            ->setEmail($rawData['email']);

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

    public function updateDepartment(string $departmentName, array $input): AgencyDepartment
    {
        $department = $this->makeDepartmentFromForm($input)->setService($departmentName);

        return $this->agencyRepository->updateDepartment($department);
    }
}
