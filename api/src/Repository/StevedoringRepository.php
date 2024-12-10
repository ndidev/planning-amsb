<?php

// Path: api/src/Repository/StevedoringRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\Filter\StevedoringDispatchFilterDTO;
use App\DTO\StevedoringDispatchDTO;
use App\Entity\Stevedoring\StevedoringEquipment;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Service\StevedoringService;

final class StevedoringRepository extends Repository
{
    public function __construct(private StevedoringService $stevedoringService)
    {
        parent::__construct();
    }

    // =====
    // Staff
    // =====

    public function staffExists(int $id): bool
    {
        $statement = "SELECT deleted_at FROM stevedoring_staff WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute(['id' => $id]);

        $response = $request->fetch(\PDO::FETCH_NUM);

        if (!is_array($response)) {
            return false;
        }

        if (null !== $response[0]) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<StevedoringStaff>
     */
    public function fetchAllStaff(): Collection
    {
        $staffStatement =
            "SELECT *
            FROM stevedoring_staff
            ORDER BY lastname ASC, firstname ASC";

        $staffRequest = $this->mysql->query($staffStatement);

        if (!$staffRequest) {
            throw new DBException("Impossible de récupérer le personnel de manutention.");
        }

        /** @var array<array<mixed>> */
        $staffRaw = $staffRequest->fetchAll();

        $allStaff = array_map(
            fn($staff) => $this->stevedoringService->makeStevedoringStaffFromDatabase($staff),
            $staffRaw
        );

        return new Collection($allStaff);
    }

    public function fetchStaff(int $id): ?StevedoringStaff
    {
        $staffStatement = "SELECT * FROM stevedoring_staff WHERE id = :id";

        $staffRequest = $this->mysql->prepare($staffStatement);

        if (!$staffRequest) {
            throw new DBException("Impossible de récupérer le personnel de manutention.");
        }

        $staffRequest->execute(['id' => $id]);

        $staffRaw = $staffRequest->fetch();

        if (!\is_array($staffRaw)) {
            return null;
        }

        $staff = $this->stevedoringService->makeStevedoringStaffFromDatabase($staffRaw);

        return $staff;
    }

    public function createStaff(StevedoringStaff $staff): StevedoringStaff
    {
        $statement =
            "INSERT INTO stevedoring_staff
            SET
                firstname = :firstname,
                lastname = :lastname,
                phone = :phone,
                type = :type,
                temp_work_agency = :tempWorkAgency,
                is_active = :isActive,
                comments = :comments";

        $request = $this->mysql->prepare($statement);

        if (!$request) {
            throw new DBException("Impossible de créer le personnel de manutention.");
        }

        try {
            $this->mysql->beginTransaction();

            $request->execute([
                'firstname' => $staff->getFirstname(),
                'lastname' => $staff->getLastname(),
                'phone' => $staff->getPhone(),
                'type' => $staff->getType(),
                'tempWorkAgency' => $staff->getTempWorkAgency(),
                'isActive' => (int) $staff->isActive(),
                'comments' => $staff->getComments(),
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            $this->mysql->commit();
        } catch (\PDOException $e) {
            $this->mysql->rollBack();
            throw new DBException("Impossible de créer le personnel de manutention.", previous: $e);
        }

        /** @var StevedoringStaff */
        $createdStaff = $this->fetchStaff($lastInsertId);

        return $createdStaff;
    }

    public function updateStaff(StevedoringStaff $staff): StevedoringStaff
    {
        $statement =
            "UPDATE stevedoring_staff
            SET
                firstname = :firstname,
                lastname = :lastname,
                phone = :phone,
                type = :type,
                temp_work_agency = :tempWorkAgency,
                is_active = :isActive,
                comments = :comments
            WHERE
                id = :id";

        $request = $this->mysql->prepare($statement);

        if (!$request) {
            throw new DBException("Impossible de mettre à jour le personnel de manutention.");
        }

        try {
            $request->execute([
                'firstname' => $staff->getFirstname(),
                'lastname' => $staff->getLastname(),
                'phone' => $staff->getPhone(),
                'type' => $staff->getType(),
                'tempWorkAgency' => $staff->getTempWorkAgency(),
                'isActive' => (int) $staff->isActive(),
                'comments' => $staff->getComments(),
                'id' => $staff->getId(),
            ]);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de mettre à jour le personnel de manutention.", previous: $e);
        }

        return $staff;
    }

    public function deleteStaff(int $id): void
    {
        $statement =
            "UPDATE stevedoring_staff
            SET
                firstname = '',
                lastname = '',
                phone = '',
                is_active = 0,
                comments = '',
                deleted_at = NOW()
            WHERE
                id = :id";

        $request = $this->mysql->prepare($statement);

        if (!$request) {
            throw new DBException("Impossible de supprimer le personnel de manutention.");
        }

        try {
            $request->execute(['id' => $id]);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de supprimer le personnel de manutention.", previous: $e);
        }
    }

    // =========
    // Equipment
    // =========

    public function equipmentExists(int $id): bool
    {
        return $this->mysql->exists("stevedoring_equipments", $id);
    }

    /**
     * 
     * @return Collection<StevedoringEquipment>
     * @throws DBException 
     */
    public function fetchAllEquipments(): Collection
    {
        $equipmentStatement =
            "SELECT *
             FROM stevedoring_equipments
             ORDER BY brand ASC, model ASC, internal_number ASC";

        $equipmentRequest = $this->mysql->query($equipmentStatement);

        if (!$equipmentRequest) {
            throw new DBException("Impossible de récupérer les équipements de manutention.");
        }

        /** @var array<array<mixed>> */
        $equipmentRaw = $equipmentRequest->fetchAll();

        $allEquipment = array_map(
            fn($equipment) => $this->stevedoringService->makeStevedoringEquipmentFromDatabase($equipment),
            $equipmentRaw
        );

        return new Collection($allEquipment);
    }

    public function fetchEquipment(int $id): ?StevedoringEquipment
    {
        $equipmentStatement =
            "SELECT *
             FROM stevedoring_equipments
             WHERE id = :id";

        $equipmentRequest = $this->mysql->prepare($equipmentStatement);

        if (!$equipmentRequest) {
            throw new DBException("Impossible de récupérer l'équipement de manutention.");
        }

        $equipmentRequest->execute(['id' => $id]);

        $equipmentRaw = $equipmentRequest->fetch();

        if (!\is_array($equipmentRaw)) {
            return null;
        }

        $equipment = $this->stevedoringService->makeStevedoringEquipmentFromDatabase($equipmentRaw);

        return $equipment;
    }

    public function createEquipment(StevedoringEquipment $equipment): StevedoringEquipment
    {
        $statement =
            "INSERT INTO stevedoring_equipments
            SET
                type = :type,
                brand = :brand,
                model = :model,
                internal_number = :internalNumber,
                serial_number = :serialNumber,
                comments = :comments,
                is_active = :isActive";

        $request = $this->mysql->prepare($statement);

        if (!$request) {
            throw new DBException("Impossible de créer l'équipement de manutention.");
        }

        try {
            $this->mysql->beginTransaction();

            $request->execute([
                'type' => $equipment->getType(),
                'brand' => $equipment->getBrand(),
                'model' => $equipment->getModel(),
                'internalNumber' => $equipment->getInternalNumber(),
                'serialNumber' => $equipment->getSerialNumber(),
                'comments' => $equipment->getComments(),
                'isActive' => (int) $equipment->isActive(),
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            $this->mysql->commit();
        } catch (\PDOException $e) {
            $this->mysql->rollBack();
            throw new DBException("Impossible de créer l'équipement de manutention.", previous: $e);
        }

        /** @var StevedoringEquipment */
        $createdEquipment = $this->fetchEquipment($lastInsertId);

        return $createdEquipment;
    }

    public function updateEquipment(StevedoringEquipment $equipment): StevedoringEquipment
    {
        $statement =
            "UPDATE stevedoring_equipments
            SET
                type = :type,
                brand = :brand,
                model = :model,
                internal_number = :internalNumber,
                serial_number = :serialNumber,
                comments = :comments,
                is_active = :isActive
            WHERE
                id = :id";

        $request = $this->mysql->prepare($statement);

        if (!$request) {
            throw new DBException("Impossible de mettre à jour l'équipement de manutention.");
        }

        try {
            $request->execute([
                'type' => $equipment->getType(),
                'brand' => $equipment->getBrand(),
                'model' => $equipment->getModel(),
                'internalNumber' => $equipment->getInternalNumber(),
                'serialNumber' => $equipment->getSerialNumber(),
                'comments' => $equipment->getComments(),
                'isActive' => (int) $equipment->isActive(),
                'id' => $equipment->getId(),
            ]);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de mettre à jour l'équipement de manutention.", previous: $e);
        }

        return $equipment;
    }

    public function deleteEquipment(int $id): void
    {
        $tasksCount = $this->fetchTasksCountForEquipment($id);
        if ($tasksCount > 0) {
            throw new DBException("Impossible de supprimer l'équipement de manutention car il est utilisé dans des tâches.");
        }

        try {
            $deleteStatement = "DELETE FROM stevedoring_equipments WHERE id = :id";
            $deleteRequest = $this->mysql->prepare($deleteStatement);
            $deleteRequest->execute(['id' => $id]);
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la suppression.", previous: $e);
        }
    }

    // ========
    // Dispatch
    // ========

    public function fetchDispatch(StevedoringDispatchFilterDTO $filter): StevedoringDispatchDTO
    {
        $sqlFilter = $filter->getSqlStaffFilter();


        // Bulk

        $bulkDispatchStatement =
            "SELECT
                dispatch.date,
                CONCAT(staff.lastname, ' ', staff.firstname) as `staffName`,
                staff.type as `staffContractType`,
                staff.temp_work_agency as `staffTempWorkAgency`,
                dispatch.remarks as `remarks`,
                p.nom as `productName`,
                q.nom as `qualityName`
            FROM stevedoring_bulk_dispatch dispatch
            INNER JOIN vrac_planning pl ON pl.id = dispatch.appointment_id
            INNER JOIN vrac_produits p ON pl.produit = p.id
            INNER JOIN vrac_qualites q ON pl.qualite = q.id
            INNER JOIN stevedoring_staff staff ON dispatch.staff_id = staff.id
            WHERE dispatch.date BETWEEN :startDate AND :endDate
            $sqlFilter
            ORDER BY
                dispatch.date ASC,
                FIELD(staff.type, 'mensuel', 'interim'),
                staff.lastname ASC,
                staff.firstname ASC
            ";

        $bulkDispatchRequest = $this->mysql->prepare($bulkDispatchStatement);

        $bulkDispatchRequest->execute([
            "startDate" => $filter->getSqlStartDate(),
            "endDate" => $filter->getSqlEndDate(),
        ]);

        /** 
         * @var array{
         *        date: string,
         *        staffName: string,
         *        staffContractType: 'mensuel'|'interim',
         *        staffTempWorkAgency: string,
         *        remarks: string,
         *        productName: string,
         *        qualityName: string
         *      }[]
         */
        $bulkData = $bulkDispatchRequest->fetchAll();

        // Timber

        $timberDispatchStatement =
            "SELECT
                dispatch.date,
                CONCAT(staff.lastname, ' ', staff.firstname) as `staffName`,
                staff.type as `staffContractType`,
                staff.temp_work_agency as `staffTempWorkAgency`,
                dispatch.remarks as `remarks`
            FROM stevedoring_timber_dispatch dispatch
            -- INNER JOIN bois_planning pl ON pl.id = dispatch.appointment_id
            INNER JOIN stevedoring_staff staff ON dispatch.staff_id = staff.id
            WHERE dispatch.date BETWEEN :startDate AND :endDate
            $sqlFilter
            ORDER BY
                dispatch.date ASC,
                FIELD(staff.type, 'mensuel', 'interim'),
                staff.lastname ASC,
                staff.firstname ASC
            ";

        $timberDispatchRequest = $this->mysql->prepare($timberDispatchStatement);

        $timberDispatchRequest->execute([
            "startDate" => $filter->getSqlStartDate(),
            "endDate" => $filter->getSqlEndDate(),
        ]);

        /** 
         * @var array{
         *        date: string,
         *        staffName: string,
         *        staffContractType: 'mensuel'|'interim',
         *        staffTempWorkAgency: string,
         *        remarks: string,
         *      }[]
         */
        $timberData = $timberDispatchRequest->fetchAll();


        $dispatchDTO = new StevedoringDispatchDTO(bulkData: $bulkData, timberData: $timberData);

        return $dispatchDTO;
    }

    // =====
    // Tasks
    // =====

    public function taskExists(int $id): bool
    {
        return $this->mysql->exists("stevedoring_tasks", $id);
    }

    public function fetchTasksCountForEquipment(int $id): int
    {
        // TODO: implement

        return 0;
    }
}
