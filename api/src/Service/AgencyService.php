<?php

// Path: api/src/Service/AgencyService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\Config\AgencyDepartment;
use App\Repository\AgencyRepository;

/**
 * @phpstan-type AgencyArray array{
 *                             service?: string,
 *                             affichage?: string,
 *                             nom?: string,
 *                             adresse_ligne_1?: string,
 *                             adresse_ligne_2?: string,
 *                             cp?: string,
 *                             ville?: string,
 *                             pays?: string,
 *                             telephone?: string,
 *                             mobile?: string,
 *                             email?: string
 *                           }
 */
final class AgencyService
{
    private AgencyRepository $agencyRepository;

    public function __construct()
    {
        $this->agencyRepository = new AgencyRepository();
    }

    /**
     * @param array $rawData
     * 
     * @phpstan-param AgencyArray $rawData
     * 
     * @return AgencyDepartment
     */
    public function makeDepartmentFromDatabase(array $rawData): AgencyDepartment
    {
        $department = (new AgencyDepartment())
            ->setService($rawData['service'] ?? '')
            ->setDisplayName($rawData['affichage'] ?? '')
            ->setFullName($rawData['nom'] ?? '')
            ->setAddressLine1($rawData['adresse_ligne_1'] ?? '')
            ->setAddressLine2($rawData['adresse_ligne_2'] ?? '')
            ->setPostCode($rawData['cp'] ?? '')
            ->setCity($rawData['ville'] ?? '')
            ->setCountry($rawData['pays'] ?? '')
            ->setPhone($rawData['telephone'] ?? '')
            ->setMobile($rawData['mobile'] ?? '')
            ->setEmail($rawData['email'] ?? '');

        return $department;
    }

    /**
     * @param array $rawData
     * 
     * @phpstan-param AgencyArray $rawData
     * 
     * @return AgencyDepartment
     */
    public function makeDepartmentFromForm(array $rawData): AgencyDepartment
    {
        $department = (new AgencyDepartment())
            ->setFullName($rawData['nom'] ?? '')
            ->setAddressLine1($rawData['adresse_ligne_1'] ?? '')
            ->setAddressLine2($rawData['adresse_ligne_2'] ?? '')
            ->setPostCode($rawData['cp'] ?? '')
            ->setCity($rawData['ville']  ?? '')
            ->setCountry($rawData['pays'] ?? '')
            ->setPhone($rawData['telephone'] ?? '')
            ->setMobile($rawData['mobile'] ?? '')
            ->setEmail($rawData['email'] ?? '');

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
     * @param string $departmentName 
     * @param array $input 
     * 
     * @phpstan-param AgencyArray $input
     * 
     * @return AgencyDepartment 
     */
    public function updateDepartment(string $departmentName, array $input): AgencyDepartment
    {
        $department = $this->makeDepartmentFromForm($input)->setService($departmentName);

        return $this->agencyRepository->updateDepartment($department);
    }
}
