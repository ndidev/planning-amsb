<?php

// Path: api/src/Repository/StevedoringRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\Filter\StevedoringDispatchFilterDTO;
use App\DTO\Filter\StevedoringStaffFilterDTO;
use App\DTO\Filter\StevedoringTempWorkHoursFilterDTO;
use App\DTO\StevedoringDispatchDTO;
use App\DTO\TempWorkHoursReportDataDTO;
use App\Entity\Stevedoring\StevedoringEquipment;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Entity\Stevedoring\TempWorkHoursEntry;
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
    public function fetchAllStaff(StevedoringStaffFilterDTO $filter): Collection
    {
        $sqlFilter = $filter->getSqlFilter();

        $staffStatement =
            "SELECT *
            FROM stevedoring_staff
            WHERE 1
                $sqlFilter
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

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de créer le personnel de manutention.");
            }

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

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de mettre à jour le personnel de manutention.");
            }

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
        $sqlFilter = $filter->getSqlFilter();


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
            LEFT JOIN vrac_qualites q ON pl.qualite = q.id
            INNER JOIN stevedoring_staff staff ON dispatch.staff_id = staff.id
            WHERE dispatch.date BETWEEN :startDate AND :endDate
            $sqlFilter
            ORDER BY
                dispatch.date ASC,
                FIELD(staff.type, 'mensuel', 'interim'),
                staff.lastname ASC,
                staff.firstname ASC,
                p.nom ASC,
                q.nom ASC
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

    /**
     * @return array<int>
     */
    public function fetchTempWorkDispatchIdsForDate(\DateTimeImmutable $date): array
    {
        // Bulk

        $statement =
            "SELECT DISTINCT staff.id
            FROM stevedoring_bulk_dispatch bulkDispatch
            INNER JOIN stevedoring_staff staff ON bulkDispatch.staff_id = staff.id
            WHERE bulkDispatch.date = :date
            AND staff.type = 'interim'
            UNION
            SELECT DISTINCT staff.id
            FROM stevedoring_timber_dispatch timberDispatch
            INNER JOIN stevedoring_staff staff ON timberDispatch.staff_id = staff.id
            WHERE timberDispatch.date = :date
            AND staff.type = 'interim'
            ";

        $request = $this->mysql->prepare($statement);

        $request->execute([
            "date" => $date->format('Y-m-d'),
        ]);

        /** 
         * @var array<int>
         */
        $data = $request->fetchAll(\PDO::FETCH_COLUMN);

        return $data;
    }

    // ===============
    // Temp work hours
    // ===============

    public function tempWorkHoursEntryExists(int $id): bool
    {
        return $this->mysql->exists("stevedoring_temp_work_hours", $id);
    }

    /**
     * @return Collection<TempWorkHoursEntry>
     */
    public function fetchAllTempWorkHours(StevedoringTempWorkHoursFilterDTO $filter): Collection
    {
        $sqlFilter = $filter->getSqlFilter();

        $statement =
            "SELECT
                hours.id,
                hours.date,
                hours.staff_id,
                hours.hours_worked,
                hours.comments
            FROM stevedoring_temp_work_hours hours
            INNER JOIN stevedoring_staff staff ON hours.staff_id = staff.id
            WHERE hours.date BETWEEN :startDate AND :endDate
                $sqlFilter
            ORDER BY
                hours.date ASC,
                staff.lastname ASC,
                staff.firstname ASC";

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de récupérer les heures des intérimaires.");
            }

            $request->execute([
                'startDate' => $filter->getSqlStartDate(),
                'endDate' => $filter->getSqlEndDate(),
            ]);

            /** @var array<array<mixed>> */
            $rawData = $request->fetchAll();

            $tempWorkHours = array_map(
                fn(array $data) => $this->stevedoringService->makeTempWorkHoursEntryFromDatabase($data),
                $rawData
            );

            return new Collection($tempWorkHours);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les heures des intérimaires.", previous: $e);
        }
    }

    public function fetchTempWorkHoursEntry(int $id): ?TempWorkHoursEntry
    {
        $statement =
            "SELECT
                hours.id,
                hours.date,
                hours.staff_id,
                hours.hours_worked,
                hours.comments
            FROM stevedoring_temp_work_hours hours
            WHERE id = :id";

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de récupérer les heures de l'intérimaire.");
            }

            $request->execute(['id' => $id]);

            $rawData = $request->fetch();

            if (!\is_array($rawData)) {
                return null;
            }

            $tempWorkHoursEntry = $this->stevedoringService->makeTempWorkHoursEntryFromDatabase($rawData);

            return $tempWorkHoursEntry;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les heures de l'intérimaire.", previous: $e);
        }
    }

    /**
     * @return Collection<TempWorkHoursEntry>
     */
    public function fetchTempWorkHoursForStaff(
        int $staffId,
        StevedoringTempWorkHoursFilterDTO $filter
    ): Collection {
        $statement =
            "SELECT
                hours.id,
                hours.date,
                hours.staff_id,
                hours.hours_worked,
                hours.comments
            FROM stevedoring_temp_work_hours hours
            INNER JOIN stevedoring_staff staff ON hours.staff_id = staff.id
            WHERE
                hours.staff_id = :staffId
                AND hours.date BETWEEN :startDate AND :endDate
            ORDER BY
                hours.date ASC";

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de récupérer les heures de l'intérimaire.");
            }

            $request->execute([
                'staffId' => $staffId,
                'startDate' => $filter->getSqlStartDate(),
                'endDate' => $filter->getSqlEndDate(),
            ]);

            /** @var array<array<mixed>> */
            $rawData = $request->fetchAll();

            $tempWorkHours = array_map(
                fn(array $data) => $this->stevedoringService->makeTempWorkHoursEntryFromDatabase($data),
                $rawData
            );

            return new Collection($tempWorkHours);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les heures de l'intérimaire.", previous: $e);
        }
    }

    public function createTempWorkHours(TempWorkHoursEntry $tempWorkHoursEntry): TempWorkHoursEntry
    {
        $statement =
            "INSERT INTO stevedoring_temp_work_hours
            SET
                date = :date,
                staff_id = :staffId,
                hours_worked = :hoursWorked,
                comments = :comments";

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de créer les heures de l'intérimaire.");
            }

            $this->mysql->beginTransaction();

            $request->execute([
                'date' => $tempWorkHoursEntry->getDate()?->format('Y-m-d'),
                'staffId' => $tempWorkHoursEntry->getStaff()?->getId(),
                'hoursWorked' => $tempWorkHoursEntry->getHoursWorked(),
                'comments' => $tempWorkHoursEntry->getComments(),
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            $this->mysql->commit();

            /** @var TempWorkHoursEntry */
            $createdTempWorkHoursEntry = $this->fetchTempWorkHoursEntry($lastInsertId);

            return $createdTempWorkHoursEntry;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new ClientException("Impossible de créer les heures de l'intérimaire. Les heures pour cette date existent déjà.", previous: $e);
            }

            throw new DBException("Impossible de créer les heures de l'intérimaire.", previous: $e);
        }
    }

    public function updateTempWorkHours(TempWorkHoursEntry $tempWorkHoursEntry): TempWorkHoursEntry
    {
        $statement =
            "UPDATE stevedoring_temp_work_hours
            SET
                `date` = :date,
                staff_id = :staffId,
                hours_worked = :hoursWorked,
                comments = :comments
            WHERE
                id = :id";

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de mettre à jour les heures de l'intérimaire.");
            }

            $request->execute([
                'date' => $tempWorkHoursEntry->getDate()?->format('Y-m-d'),
                'staffId' => $tempWorkHoursEntry->getStaff()?->getId(),
                'hoursWorked' => $tempWorkHoursEntry->getHoursWorked(),
                'comments' => $tempWorkHoursEntry->getComments(),
                'id' => $tempWorkHoursEntry->getId(),
            ]);

            return $tempWorkHoursEntry;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de mettre à jour les heures de l'intérimaire.", previous: $e);
        }
    }

    public function deleteTempWorkHours(int $id): void
    {
        try {
            $deleteStatement = "DELETE FROM stevedoring_temp_work_hours WHERE id = :id";
            $deleteRequest = $this->mysql->prepare($deleteStatement);
            $deleteRequest->execute(['id' => $id]);
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la suppression.", previous: $e);
        }
    }

    public function fetchTempWorkHoursReportData(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): TempWorkHoursReportDataDTO {
        $statement =
            "SELECT
                hours.date,
                CONCAT(staff.firstname, ' ', staff.lastname) as `staffName`,
                staff.temp_work_agency as `agency`,
                hours.hours_worked as `hoursWorked`
            FROM stevedoring_temp_work_hours hours
            INNER JOIN stevedoring_staff staff ON hours.staff_id = staff.id
            WHERE hours.date BETWEEN :startDate AND :endDate
            ORDER BY
                hours.date ASC,
                staff.lastname ASC,
                staff.firstname ASC";

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de récupérer les heures de travail.");
            }

            $request->execute([
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
            ]);

            /** @var array<array<mixed>> */
            $rawData = $request->fetchAll();

            $tempWorkHoursReportDataDto = new TempWorkHoursReportDataDTO($rawData);

            return $tempWorkHoursReportDataDto;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les heures de travail.", previous: $e);
        }
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
