<?php

// Path: api/src/Repository/AgencyRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Config\AgencyDepartment;
use App\Service\AgencyService;

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
final class AgencyRepository extends Repository
{
    public function __construct(private AgencyService $agencyService)
    {
        parent::__construct();
    }

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

        if (!$request) {
            throw new DBException("Impossible de récupérer les services de l'agence.");
        }

        /** @phpstan-var AgencyDepartmentArray[] $departmentsRaw */
        $departmentsRaw = $request->fetchAll();

        $departments = array_map(
            fn(array $departmentRaw) => $this->agencyService->makeDepartmentFromDatabase($departmentRaw),
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

        if (!is_array($departmentRaw)) return null;

        /** @phpstan-var AgencyDepartmentArray $departmentRaw */

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

        /** @var AgencyDepartment $updatedDepartment */
        $updatedDepartment = $this->fetchDepartment($department->getService());

        return $updatedDepartment;
    }
}
