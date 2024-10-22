<?php

// Path: api/src/Repository/AgencyRepository.php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Entity\Config\AgencyDepartment;
use App\Service\AgencyService;

final class AgencyRepository extends Repository
{
    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param string $departmentName Identifiant du service de l'agence.
     */
    public function departmentExists(string $departmentName): bool
    {
        return $this->mysql->exists("config_agence", $departmentName, "service");
    }

    /**
     * Récupère les services de l'agence.
     * 
     * @return Collection<AgencyDepartment> Services de l'agence.
     */
    public function fetchAllDepartments(): Collection
    {
        $request = $this->mysql->query("SELECT * FROM config_agence");
        $departmentsRaw = $request->fetchAll();

        $agencyService = new AgencyService();

        $departments = array_map(
            fn(array $departmentRaw) => $agencyService->makeDepartmentFromDatabase($departmentRaw),
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
        $request = $this->mysql->prepare("SELECT * FROM config_agence WHERE service = :service");
        $request->execute(["service" => $departmentName]);
        $departmentRaw = $request->fetch();

        if (!$departmentName) return null;

        $agencyService = new AgencyService();

        $department = $agencyService->makeDepartmentFromDatabase($departmentRaw);

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
                nom = :nom,
                adresse_ligne_1 = :adresse_ligne_1,
                adresse_ligne_2 = :adresse_ligne_2,
                cp = :cp,
                ville = :ville,
                pays = :pays,
                telephone = :telephone,
                mobile = :mobile,
                email = :email
            WHERE service = :service";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            "nom" => $department->getFullName(),
            "adresse_ligne_1" => $department->getAddressLine1(),
            "adresse_ligne_2" => $department->getAddressLine2(),
            "cp" => $department->getPostCode(),
            "ville" => $department->getCity(),
            "pays" => $department->getCountry(),
            "telephone" => $department->getPhone(),
            "mobile" => $department->getMobile(),
            "email" => $department->getEmail(),
            "service" => $department->getService(),
        ]);

        return $this->fetchDepartment($department->getService());
    }
}
