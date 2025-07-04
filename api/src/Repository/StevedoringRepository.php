<?php

// Path: api/src/Repository/StevedoringRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Component\DateUtils;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\Logger\ErrorLogger;
use App\DTO\CallWithoutReportDTO;
use App\DTO\Filter\StevedoringDispatchFilterDTO;
use App\DTO\Filter\StevedoringReportsFilterDataDTO;
use App\DTO\Filter\StevedoringReportsFilterDTO;
use App\DTO\Filter\StevedoringStaffFilterDTO;
use App\DTO\Filter\StevedoringTempWorkHoursFilterDTO;
use App\DTO\IgnoredCallDTO;
use App\DTO\StevedoringDispatchDTO;
use App\DTO\StevedoringSubcontractorsDataDTO;
use App\DTO\TempWorkDispatchForDateDTO;
use App\DTO\TempWorkHoursReportDataDTO;
use App\Entity\Shipping\ShippingCallCargo;
use App\Entity\Stevedoring\ShipReport;
use App\Entity\Stevedoring\ShipReportEquipmentEntry;
use App\Entity\Stevedoring\ShipReportStaffEntry;
use App\Entity\Stevedoring\ShipReportStorageEntry;
use App\Entity\Stevedoring\ShipReportSubcontractEntry;
use App\Entity\Stevedoring\ShipSubreport;
use App\Entity\Stevedoring\StevedoringEquipment;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Entity\Stevedoring\TempWorkHoursEntry;
use App\Service\ShippingService;
use App\Service\StevedoringService;
use ReflectionClass;

/**
 * @phpstan-import-type StevedoringStaffArray from \App\Entity\Stevedoring\StevedoringStaff
 * @phpstan-import-type StevedoringEquipmentArray from \App\Entity\Stevedoring\StevedoringEquipment
 * @phpstan-import-type ShipReportArray from \App\Entity\Stevedoring\ShipReport
 * @phpstan-import-type ShipSubreportArray from \App\Entity\Stevedoring\ShipSubreport
 * @phpstan-import-type ShipReportEquipmentEntryArray from \App\Entity\Stevedoring\ShipReportEquipmentEntry
 * @phpstan-import-type ShipReportStaffEntryArray from \App\Entity\Stevedoring\ShipReportStaffEntry
 * @phpstan-import-type ShipReportSubcontractEntryArray from \App\Entity\Stevedoring\ShipReportSubcontractEntry
 * @phpstan-import-type ShipReportStorageEntryArray from \App\Entity\Stevedoring\ShipReportStorageEntry
 * @phpstan-import-type ShippingCallCargoArray from \App\Entity\Shipping\ShippingCallCargo
 * @phpstan-import-type CallWithoutReport from \App\DTO\CallWithoutReportDTO
 */
final class StevedoringRepository extends Repository
{
    /** @var ReflectionClass<StevedoringStaff> */
    private ReflectionClass $staffReflector;

    /** @var ReflectionClass<StevedoringEquipment> */
    private ReflectionClass $equipmentReflector;

    /** @var ReflectionClass<ShipReport> */
    private ReflectionClass $shipReportReflector;

    public function __construct(private StevedoringService $stevedoringService)
    {
        $this->staffReflector = new ReflectionClass(StevedoringStaff::class);
        $this->equipmentReflector = new ReflectionClass(StevedoringEquipment::class);
        $this->shipReportReflector = new ReflectionClass(ShipReport::class);
    }

    /**
     * @var array{
     *        shipReports: array<int, ShipReport>,
     *      }
     */
    private static array $cache = [
        'shipReports' => [],
    ];

    // =====
    // Staff
    // =====

    public function staffExists(int $id): bool
    {
        return $this->mysql->exists("stevedoring_staff", $id);
    }

    public function staffIsDeleted(int $id): bool
    {
        $statement = "SELECT deleted_at FROM stevedoring_staff WHERE id = :id";

        $response = $this->mysql
            ->prepareAndExecute($statement, ['id' => $id])
            ->fetch(\PDO::FETCH_NUM);

        if (!\is_array($response)) {
            throw new NotFoundException("Le personnel de manutention n'existe pas.");
        }

        $staffIsDeleted = null !== $response[0];

        return $staffIsDeleted;
    }

    /**
     * @return Collection<StevedoringStaff>
     */
    public function fetchAllStaff(StevedoringStaffFilterDTO $filter): Collection
    {
        $sqlFilter = $filter->getSqlFilter();

        $staffStatement =
            "SELECT
                id,
                firstname,
                lastname,
                phone,
                type,
                temp_work_agency as `tempWorkAgency`,
                is_active as `isActive`,
                comments,
                deleted_at as `deletedAt`
            FROM stevedoring_staff
            WHERE 1
                $sqlFilter
            ORDER BY lastname ASC, firstname ASC";

        try {
            /** @var StevedoringStaffArray[] */
            $allStaffRaw = $this->mysql->prepareAndExecute($staffStatement)->fetchAll();

            $allStaff = \array_map(
                fn($staff) => $this->stevedoringService->makeStevedoringStaffFromDatabase($staff),
                $allStaffRaw
            );

            return new Collection($allStaff);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer le personnel de manutention.", previous: $e);
        }
    }

    public function fetchStaff(int $id): ?StevedoringStaff
    {
        /** @var array<int, StevedoringStaff> */
        static $cache = [];

        if (isset($cache[$id])) {
            return $cache[$id];
        }

        if (!$this->staffExists($id)) {
            return null;
        }

        /** @var StevedoringStaff */
        $staff = $this->staffReflector->newLazyProxy(
            function () use ($id) {
                try {
                    $staffStatement =
                        "SELECT
                            id,
                            firstname,
                            lastname,
                            phone,
                            type,
                            temp_work_agency as `tempWorkAgency`,
                            is_active as `isActive`,
                            comments,
                            deleted_at as `deletedAt`
                        FROM stevedoring_staff WHERE id = :id";

                    /** @var StevedoringStaffArray */
                    $staffRaw = $this->mysql
                        ->prepareAndExecute($staffStatement, ['id' => $id])
                        ->fetch();

                    return $this->stevedoringService->makeStevedoringStaffFromDatabase($staffRaw);
                } catch (\PDOException $e) {
                    throw new DBException("Impossible de récupérer le personnel de manutention.", previous: $e);
                }
            }
        );

        $this->staffReflector->getProperty('id')->setRawValueWithoutLazyInitialization($staff, $id);

        $cache[$id] = $staff;

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
            $this->mysql->beginTransaction();

            $this->mysql->prepareAndExecute($statement, [
                'firstname' => $staff->firstname,
                'lastname' => $staff->lastname,
                'phone' => $staff->phone,
                'type' => $staff->type,
                'tempWorkAgency' => $staff->tempWorkAgency,
                'isActive' => (int) $staff->isActive,
                'comments' => $staff->comments,
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            $this->mysql->commit();

            $staff->id = $lastInsertId;

            return $staff;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();
            throw new DBException("Impossible de créer le personnel de manutention.", previous: $e);
        }
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
            $this->mysql->prepareAndExecute($statement, [
                'firstname' => $staff->firstname,
                'lastname' => $staff->lastname,
                'phone' => $staff->phone,
                'type' => $staff->type,
                'tempWorkAgency' => $staff->tempWorkAgency,
                'isActive' => (int) $staff->isActive,
                'comments' => $staff->comments,
                'id' => $staff->id,
            ]);

            return $staff;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de mettre à jour le personnel de manutention.", previous: $e);
        }
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

        try {
            $this->mysql->prepareAndExecute($statement, ['id' => $id]);
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
            "SELECT
                id,
                type,
                brand,
                model,
                internal_number as `internalNumber`,
                serial_number as `serialNumber`,
                `year`,
                comments,
                is_active as `isActive`
             FROM stevedoring_equipments
             ORDER BY
                brand ASC,
                model ASC,
                internal_number ASC";

        try {
            /** @var StevedoringEquipmentArray[] */
            $allEquipmentsRaw = $this->mysql
                ->prepareAndExecute($equipmentStatement)
                ->fetchAll();

            $allEquipment = \array_map(
                fn($equipment) => $this->stevedoringService->makeStevedoringEquipmentFromDatabase($equipment),
                $allEquipmentsRaw
            );

            return new Collection($allEquipment);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les équipements de manutention.", previous: $e);
        }
    }

    public function fetchEquipment(int $id): ?StevedoringEquipment
    {
        /** @var array<int, StevedoringEquipment> */
        static $cache = [];

        if (isset($cache[$id])) {
            return $cache[$id];
        }
        if (!$this->equipmentExists($id)) {
            return null;
        }

        /** @var StevedoringEquipment */
        $equipment = $this->equipmentReflector->newLazyProxy(
            function () use ($id) {
                try {
                    $equipmentStatement =
                        "SELECT
                        id,
                        type,
                        brand,
                        model,
                        internal_number as `internalNumber`,
                        serial_number as `serialNumber`,
                        `year`,
                        comments,
                        is_active as `isActive`
                     FROM stevedoring_equipments
                     WHERE id = :id";

                    /** @var StevedoringEquipmentArray */
                    $equipmentRaw = $this->mysql
                        ->prepareAndExecute($equipmentStatement, ['id' => $id])
                        ->fetch();

                    return $this->stevedoringService->makeStevedoringEquipmentFromDatabase($equipmentRaw);
                } catch (\PDOException $e) {
                    throw new DBException("Impossible de récupérer l'équipement de manutention.", previous: $e);
                }
            }
        );

        $this->equipmentReflector->getProperty('id')->setRawValueWithoutLazyInitialization($equipment, $id);

        $cache[$id] = $equipment;

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
                year = :year,
                comments = :comments,
                is_active = :isActive";

        try {
            $this->mysql->beginTransaction();

            $this->mysql->prepareAndExecute($statement, [
                'type' => $equipment->type,
                'brand' => $equipment->brand,
                'model' => $equipment->model,
                'internalNumber' => $equipment->internalNumber,
                'serialNumber' => $equipment->serialNumber,
                'year' => $equipment->year,
                'comments' => $equipment->comments,
                'isActive' => (int) $equipment->isActive,
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            $equipment->id = $lastInsertId;

            $this->mysql->commit();

            return $equipment;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();
            throw new DBException("Impossible de créer l'équipement de manutention.", previous: $e);
        }
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
                year = :year,
                comments = :comments,
                is_active = :isActive
            WHERE
                id = :id";

        try {
            $this->mysql->prepareAndExecute($statement, [
                'type' => $equipment->type,
                'brand' => $equipment->brand,
                'model' => $equipment->model,
                'internalNumber' => $equipment->internalNumber,
                'serialNumber' => $equipment->serialNumber,
                'year' => $equipment->year,
                'comments' => $equipment->comments,
                'isActive' => (int) $equipment->isActive,
                'id' => $equipment->id,
            ]);

            return $equipment;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de mettre à jour l'équipement de manutention.", previous: $e);
        }
    }

    public function deleteEquipment(int $id): void
    {
        $tasksCount = $this->fetchReportsCountForEquipment($id);
        if ($tasksCount > 0) {
            throw new DBException("Impossible de supprimer l'équipement de manutention car il est utilisé dans des rapports navires.");
        }

        try {
            $deleteStatement = "DELETE FROM stevedoring_equipments WHERE id = :id";
            $this->mysql->prepareAndExecute($deleteStatement, ['id' => $id]);
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la suppression.", previous: $e);
        }
    }

    private function fetchReportsCountForEquipment(int $id): int
    {

        $statement =
            "SELECT COUNT(*)
             FROM stevedoring_ship_reports_equipments
             WHERE equipment_id = :id";

        try {
            $request = $this->mysql->prepare($statement);

            if (!$request) {
                throw new DBException("Impossible de récupérer l'historique de cet équipement.");
            }

            $request->execute(['id' => $id]);

            $count = $request->fetchColumn();

            return (int) $count;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer l'historique de cet équipement.", previous: $e);
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
                p.id as `productId`,
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
         *        productId: int,
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

    public function fetchTempWorkDispatchForDate(\DateTimeImmutable $date): TempWorkDispatchForDateDTO
    {
        $statement =
            "SELECT DISTINCT
                staff.id,
                0 as `hoursWorked`
            FROM stevedoring_bulk_dispatch bulkDispatch
            INNER JOIN stevedoring_staff staff ON bulkDispatch.staff_id = staff.id
            WHERE bulkDispatch.date = :date
            AND staff.type = 'interim'
            UNION
            SELECT DISTINCT
                staff.id,
                0 as `hoursWorked`
            FROM stevedoring_timber_dispatch timberDispatch
            INNER JOIN stevedoring_staff staff ON timberDispatch.staff_id = staff.id
            WHERE timberDispatch.date = :date
            AND staff.type = 'interim'
            UNION
            SELECT
                staff.id,
                SUM(shipReportsStaff.hours_worked) as `hoursWorked`
            FROM stevedoring_ship_reports_staff shipReportsStaff
            INNER JOIN stevedoring_staff staff ON shipReportsStaff.staff_id = staff.id
            WHERE shipReportsStaff.date = :date
            AND staff.type = 'interim'
            GROUP BY staff.id
            ";

        $request = $this->mysql->prepare($statement);

        $request->execute([
            "date" => $date->format('Y-m-d'),
        ]);

        /** 
         * @var array{id: string, hoursWorked: string}[]
         */
        $data = $request->fetchAll();

        $dispatchDTO = new TempWorkDispatchForDateDTO($data);

        return $dispatchDTO;
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
            /** @var array<array<mixed>> */
            $rawData = $this->mysql
                ->prepareAndExecute($statement, [
                    'startDate' => $filter->getSqlStartDate(),
                    'endDate' => $filter->getSqlEndDate(),
                ])
                ->fetchAll();

            $tempWorkHours = \array_map(
                function ($data) {
                    $entry = $this->stevedoringService->makeTempWorkHoursEntryFromDatabase($data);
                    $entry->details = $this->fetchDetailsForTempWorkHoursEntry($entry);
                    return $entry;
                },
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
            $rawData = $this->mysql
                ->prepareAndExecute($statement, ['id' => $id])
                ->fetch();

            if (!\is_array($rawData)) {
                return null;
            }

            $tempWorkHoursEntry = $this->stevedoringService->makeTempWorkHoursEntryFromDatabase($rawData);

            $tempWorkHoursEntry->details = \trim($this->fetchDetailsForTempWorkHoursEntry($tempWorkHoursEntry));

            return $tempWorkHoursEntry;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les heures de l'intérimaire.", previous: $e);
        }
    }

    private function fetchDetailsForTempWorkHoursEntry(TempWorkHoursEntry $entry): string
    {
        $bulkDetailsStatement =
            "SELECT
                product.nom as `product`,
                quality.nom as `quality`,
                dispatch.remarks as `remarks`
            FROM stevedoring_bulk_dispatch dispatch
            LEFT JOIN vrac_planning pl ON pl.id = dispatch.appointment_id
            LEFT JOIN vrac_produits product ON pl.produit = product.id
            LEFT JOIN vrac_qualites quality ON pl.qualite = quality.id
            WHERE dispatch.date = :date
            AND dispatch.staff_id = :staffId
            ";

        $timberDetailsStatement =
            "SELECT
                dispatch.remarks as `remarks`
            FROM stevedoring_timber_dispatch dispatch
            WHERE dispatch.date = :date
            AND dispatch.staff_id = :staffId
            ";

        $shipReportsDetailsStatement =
            "SELECT
                reports.ship as `ship`,
                dispatch.hours_hint as `hoursHint`,
                dispatch.hours_worked as `hoursWorked`,
                dispatch.comments as `comments`
            FROM stevedoring_ship_reports_staff dispatch
            LEFT JOIN stevedoring_ship_reports reports ON reports.id = dispatch.ship_report_id
            WHERE dispatch.date = :date
            AND dispatch.staff_id = :staffId
            ";

        try {
            /** @var array{product: string, quality: ?string, remarks: string}[] */
            $bulkDetailsArray = $this->mysql->prepareAndExecute(
                $bulkDetailsStatement,
                [
                    'date' => $entry->date?->format('Y-m-d'),
                    'staffId' => $entry->staff?->id,
                ]
            )->fetchAll();

            /** @var array{remarks: string}[] */
            $timberDetailsArray = $this->mysql->prepareAndExecute(
                $timberDetailsStatement,
                [
                    'date' => $entry->date?->format('Y-m-d'),
                    'staffId' => $entry->staff?->id,
                ]
            )->fetchAll();

            /** @var array{ship: string, hoursHint: string, hoursWorked: float, comments: string}[] */
            $shipReportsDetailsArray = $this->mysql->prepareAndExecute(
                $shipReportsDetailsStatement,
                [
                    'date' => $entry->date?->format('Y-m-d'),
                    'staffId' => $entry->staff?->id,
                ]
            )->fetchAll();

            $bulkDetails = \array_map(
                function ($row) {
                    return $row['product']
                        . ($row['quality'] ? " {$row['quality']}" : '')
                        . ($row['remarks'] ? " : {$row['remarks']}" : '');
                },
                $bulkDetailsArray
            );

            $timberDetails = \array_map(
                fn($row) => 'Bois' . ($row['remarks'] ? " : {$row['remarks']}" : ''),
                $timberDetailsArray
            );

            $shipReportsDetails = \array_map(
                function ($row) {
                    $formattedHoursWorked = DateUtils::stringifyTime((float) $row['hoursWorked']);

                    return $row['ship']
                        . ($row['hoursHint'] ? " : {$row['hoursHint']}" : '')
                        . " ({$formattedHoursWorked})"
                        . ($row['comments'] ? " - {$row['comments']}" : '');
                },
                $shipReportsDetailsArray
            );

            $details = \implode("\n", \array_merge($bulkDetails, $timberDetails, $shipReportsDetails));

            return $details;
        } catch (\Throwable $th) {
            ErrorLogger::log($th);
            return '';
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

            $tempWorkHours = \array_map(
                fn(array $data) => $this->stevedoringService->makeTempWorkHoursEntryFromDatabase($data),
                $rawData
            );

            return new Collection($tempWorkHours);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les heures de l'intérimaire.", previous: $e);
        }
    }

    public function createTempWorkHours(TempWorkHoursEntry $entry): TempWorkHoursEntry
    {
        $statement =
            "INSERT INTO stevedoring_temp_work_hours
            SET
                date = :date,
                staff_id = :staffId,
                hours_worked = :hoursWorked,
                comments = :comments";

        try {
            $this->mysql->beginTransaction();

            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'date' => $entry->date?->format('Y-m-d'),
                    'staffId' => $entry->staff?->id,
                    'hoursWorked' => $entry->hoursWorked,
                    'comments' => $entry->comments,
                ]
            );

            $entry->id = (int) $this->mysql->lastInsertId();

            $this->mysql->commit();

            $entry->details = $this->fetchDetailsForTempWorkHoursEntry($entry);

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            if ($e->getCode() == 23000) {
                $message = \sprintf(
                    "Impossible de créer les heures.\nLes heures de %s pour cette date existent déjà.",
                    $entry->staff?->fullname
                );
                throw new BadRequestException($message);
            }

            throw new DBException("Impossible de créer les heures de l'intérimaire.", previous: $e);
        }
    }

    public function updateTempWorkHours(TempWorkHoursEntry $entry): TempWorkHoursEntry
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
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'date' => $entry->date?->format('Y-m-d'),
                    'staffId' => $entry->staff?->id,
                    'hoursWorked' => $entry->hoursWorked,
                    'comments' => $entry->comments,
                    'id' => $entry->id,
                ]
            );

            $entry->details = $this->fetchDetailsForTempWorkHoursEntry($entry);

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            if ($e->getCode() == 23000) {
                $message = \sprintf(
                    "Impossible de mettre à jour les heures.\nLes heures de %s pour cette date existent déjà.",
                    $entry->staff?->fullname
                );
                throw new BadRequestException($message);
            }

            throw new DBException("Impossible de mettre à jour les heures de l'intérimaire.", previous: $e);
        }
    }

    public function deleteTempWorkHours(int $id): void
    {
        try {
            $this->mysql->prepareAndExecute(
                "DELETE FROM stevedoring_temp_work_hours WHERE id = :id",
                ['id' => $id]
            );
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
            /** @var array<array<mixed>> */
            $rawData = $this->mysql
                ->prepareAndExecute(
                    $statement,
                    [
                        'startDate' => $startDate->format('Y-m-d'),
                        'endDate' => $endDate->format('Y-m-d'),
                    ]
                )
                ->fetchAll();

            $tempWorkHoursReportDataDto = new TempWorkHoursReportDataDTO($rawData);

            return $tempWorkHoursReportDataDto;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les heures de travail.", previous: $e);
        }
    }

    // ============
    // Ship reports
    // ============

    public function shipReportExists(int $id): bool
    {
        return $this->mysql->exists("stevedoring_ship_reports", $id);
    }

    /**
     * 
     * @param StevedoringReportsFilterDTO $filter 
     * 
     * @return Collection<ShipReport>
     */
    public function fetchAllShipReports(StevedoringReportsFilterDTO $filter): Collection
    {
        $sqlFilter = $filter->getSqlFilter();

        $archiveFilter = (int) $filter->isArchive();

        $statement =
            "WITH dates AS (
                SELECT `date`, ship_report_id FROM stevedoring_ship_reports_equipments
                UNION
                SELECT `date`, ship_report_id FROM stevedoring_ship_reports_staff
                UNION
                SELECT `date`, ship_report_id FROM stevedoring_ship_reports_subcontracts
            )
            SELECT *
            FROM (
                SELECT
                    r.id,
                    r.is_archive as `isArchive`,
                    r.linked_shipping_call_id as `linkedShippingCallId`,
                    r.ship,
                    r.port,
                    r.berth,
                    r.comments,
                    r.invoice_instructions as `invoiceInstructions`,
                    COALESCE(
                        (SELECT MIN(`date`)
                        FROM dates
                        WHERE ship_report_id = r.id),
                        (SELECT ops_date
                        FROM consignation_planning
                        WHERE id = r.linked_shipping_call_id)
                    ) as `startDate`,
                    COALESCE(
                        (SELECT MAX(`date`)
                        FROM dates
                        WHERE ship_report_id = r.id),
                        (SELECT etc_date
                        FROM consignation_planning
                        WHERE id = r.linked_shipping_call_id)
                    ) as `endDate`
                FROM stevedoring_ship_reports r
                LEFT JOIN consignation_escales_marchandises cem ON cem.ship_report_id = r.id
                LEFT JOIN stevedoring_ship_reports_storage storage ON storage.ship_report_id = r.id
                LEFT JOIN consignation_planning cp ON cp.id = r.linked_shipping_call_id
                WHERE
                    is_archive = $archiveFilter
                    $sqlFilter
                GROUP BY
                    r.id -- Group by to avoid duplicates
            ) as main_query
            WHERE
                (`startDate` <= :endDate OR `startDate` IS NULL)
                AND
                (`endDate` >= :startDate OR `endDate` IS NULL)
            ORDER BY 
                `startDate` ASC,
                `endDate` ASC
            ";

        try {
            /** @var ShipReportArray[] */
            $reportsRaw = $this->mysql
                ->prepareAndExecute(
                    $statement,
                    [
                        'startDate' => $filter->getSqlStartDate(),
                        'endDate' => $filter->getSqlEndDate(),
                    ]
                )
                ->fetchAll();

            $shipReports = \array_map(
                fn($reportRaw) => $this->stevedoringService->makeShipReportFromDatabase($reportRaw),
                $reportsRaw
            );

            foreach ($shipReports as $report) {
                /** @var int */
                $reportId = $report->id;

                $report->cargoEntries = $this->fetchCargoEntriesForReport($report);
                $report->storageEntries = $this->fetchStorageEntriesForReport($report);
                $report->subreports = $this->fetchSubreportsForReport($report);

                static::$cache['shipReports'][$reportId] = $report;
            }


            return new Collection($shipReports);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les rapports navires.", previous: $e);
        }
    }

    public function fetchShipReport(int $reportId): ?ShipReport
    {
        if (isset(static::$cache['shipReports'][$reportId])) {
            return static::$cache['shipReports'][$reportId];
        }

        if (!$this->shipReportExists($reportId)) {
            return null;
        }

        /** @var ShipReport */
        $shipReport = $this->shipReportReflector->newLazyProxy(
            function () use ($reportId) {
                $reportStatement =
                    "SELECT
                    id,
                    is_archive as `isArchive`,
                    linked_shipping_call_id as `linkedShippingCallId`,
                    ship,
                    port,
                    berth,
                    comments,
                    invoice_instructions as `invoiceInstructions`
                 FROM stevedoring_ship_reports
                 WHERE id = :reportId";

                try {
                    /** @var ShipReportArray */
                    $reportRaw = $this->mysql
                        ->prepareAndExecute($reportStatement, ['reportId' => $reportId])
                        ->fetch();

                    $report = $this->stevedoringService->makeShipReportFromDatabase($reportRaw);

                    $report->cargoEntries = $this->fetchCargoEntriesForReport($report);
                    $report->storageEntries = $this->fetchStorageEntriesForReport($report);
                    $report->subreports = $this->fetchSubreportsForReport($report);

                    static::$cache['shipReports'][$reportId] = $report;

                    return $report;
                } catch (\PDOException $e) {
                    throw new DBException("Impossible de récupérer le rapport navire.", previous: $e);
                }
            }
        );

        $this->shipReportReflector->getProperty('id')->setRawValueWithoutLazyInitialization($shipReport, $reportId);

        static::$cache['shipReports'][$reportId] = $shipReport;

        return $shipReport;
    }

    public function createShipReport(ShipReport $report): ShipReport
    {
        try {
            $this->mysql->beginTransaction();

            $reportStatement =
                "INSERT INTO stevedoring_ship_reports
                 SET
                    is_archive = :isArchive,
                    linked_shipping_call_id = :linkedShippingCallId,
                    ship = :ship,
                    port = :port,
                    berth = :berth,
                    comments = :comments,
                    invoice_instructions = :invoiceInstructions";

            $this->mysql->prepareAndExecute(
                $reportStatement,
                [
                    'isArchive' => (int) $report->isArchive,
                    'linkedShippingCallId' => $report->linkedShippingCall?->id,
                    'ship' => $report->ship,
                    'port' => $report->port,
                    'berth' => $report->berth,
                    'comments' => $report->comments,
                    'invoiceInstructions' => $report->invoiceInstructions,
                ]
            );

            $report->id = (int) $this->mysql->lastInsertId();

            // Subreports
            foreach ($report->subreports as $subreport) {
                $this->createShipSubreport($subreport);
            }

            // Cargo entries
            if (null === $report->linkedShippingCall) {
                foreach ($report->cargoEntries as $cargoEntry) {
                    $cargoEntry->id = $this->createCargoEntry($cargoEntry)->id;
                }
            } else {
                // Delete entries that are not in the new list
                $existingIds = \array_filter(
                    $report->cargoEntries->map(fn($entry) => $entry->id),
                    fn($id) => $id !== null
                );
                $this->mysql->prepareAndExecute(
                    \count($existingIds) > 0
                        ? \sprintf(
                            "DELETE FROM consignation_escales_marchandises WHERE escale_id = :callId AND NOT id IN (%s)",
                            \join(",", $existingIds)
                        )
                        : "DELETE FROM consignation_escales_marchandises WHERE escale_id = :callId",
                    ['callId' => $report->linkedShippingCall->id]
                );

                foreach ($report->cargoEntries as $cargoEntry) {
                    if (!$cargoEntry->id) {
                        $cargoEntry->id = $this->createCargoEntry($cargoEntry)->id;
                    } else {
                        $this->updateCargoEntry($cargoEntry);
                    }
                }
            }

            // Storage entries
            // Check if there is a corresponding cargo in the cargo entries
            $cargoIds = \array_map(
                fn(ShippingCallCargo $cargo) => $cargo->id,
                $report->cargoEntries->asArray()
            );
            foreach ($report->storageEntries as $storageEntry) {
                if (!\in_array($storageEntry->cargo?->id, $cargoIds)) {
                    $message = \sprintf(
                        "Impossible de créer un stockage pour une marchandise qui n'est pas dans la liste des marchandises.\nMarchandise: %s (%s)",
                        $storageEntry->cargo?->cargoName,
                        $storageEntry->cargo?->customer
                    );
                    throw new BadRequestException($message);
                }

                $storageEntry->id = $this->createShipReportStorageEntry($storageEntry)->id;
            }

            $this->updateLinkedShippingCall($report);

            $this->mysql->commit();

            return $report;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            throw new DBException("Impossible de créer le rapport navire.", previous: $e);
        } catch (\Throwable $th) {
            $this->mysql->rollbackIfNeeded();
            throw $th;
        }
    }

    public function updateShipReport(ShipReport $report): ShipReport
    {
        try {
            $this->mysql->beginTransaction();

            $statement =
                "UPDATE stevedoring_ship_reports
                 SET
                    is_archive = :isArchive,
                    linked_shipping_call_id = :linkedShippingCallId,
                    ship = :ship,
                    port = :port,
                    berth = :berth,
                    comments = :comments,
                    invoice_instructions = :invoiceInstructions
                 WHERE
                    id = :id";

            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'isArchive' => (int) $report->isArchive,
                    'linkedShippingCallId' => $report->linkedShippingCall?->id,
                    'ship' => $report->ship,
                    'port' => $report->port,
                    'berth' => $report->berth,
                    'comments' => $report->comments,
                    'invoiceInstructions' => $report->invoiceInstructions,
                    'id' => $report->id,
                ]
            );

            // Subreports
            // Delete entries that are not in the new list
            $existingIds = \array_filter(
                $report->subreports->map(fn($subreport) => $subreport->id),
                fn($id) => $id !== null
            );
            $this->mysql->prepareAndExecute(
                \count($existingIds) > 0
                    ? \sprintf(
                        "DELETE FROM stevedoring_ship_subreports WHERE report_id = :reportId AND NOT id IN (%s)",
                        \join(",", $existingIds)
                    )
                    : "DELETE FROM stevedoring_ship_subreports WHERE report_id = :reportId",
                ['reportId' => $report->id]
            );

            foreach ($report->subreports as $subreport) {
                if (!$subreport->id) {
                    $this->createShipSubreport($subreport);
                } else {
                    $this->updateShipSubreport($subreport);
                }
            }

            // Cargo entries
            // Delete entries that are not in the new list
            $existingIds = \array_filter(
                $report->cargoEntries->map(fn($entry) => $entry->id),
                fn($id) => $id !== null
            );
            $this->mysql->prepareAndExecute(
                \count($existingIds) > 0
                    ? \sprintf(
                        "DELETE FROM consignation_escales_marchandises WHERE ship_report_id = :reportId AND NOT id IN (%s)",
                        \join(",", $existingIds)
                    )
                    : "DELETE FROM consignation_escales_marchandises WHERE ship_report_id = :reportId",
                ['reportId' => $report->id]
            );

            foreach ($report->cargoEntries as $cargoEntry) {
                if (!$cargoEntry->id) {
                    $this->createCargoEntry($cargoEntry);
                } else {
                    $this->updateCargoEntry($cargoEntry);
                }
            }

            // Storage entries
            // Delete entries that are not in the new list
            $existingIds = \array_filter(
                $report->storageEntries->map(fn($entry) => $entry->id),
                fn($id) => $id !== null
            );
            $this->mysql->prepareAndExecute(
                \count($existingIds) > 0
                    ? \sprintf(
                        "DELETE FROM stevedoring_ship_reports_storage WHERE ship_report_id = :reportId AND NOT id IN (%s)",
                        \join(",", $existingIds)
                    )
                    : "DELETE FROM stevedoring_ship_reports_storage WHERE ship_report_id = :reportId",
                ['reportId' => $report->id]
            );

            // Check if there is a corresponding cargo in the cargo entries
            $cargoIds = \array_map(
                fn(ShippingCallCargo $cargo) => $cargo->id,
                $report->cargoEntries->asArray()
            );
            foreach ($report->storageEntries as $storageEntry) {
                if (!\in_array($storageEntry->cargo?->id, $cargoIds)) {
                    $message = \sprintf(
                        "Impossible de créer un stockage pour une marchandise qui n'est pas dans la liste des marchandises.\nMarchandise: %s (%s)",
                        $storageEntry->cargo?->cargoName,
                        $storageEntry->cargo?->customer
                    );
                    throw new BadRequestException($message);
                }

                if (!$storageEntry->id) {
                    $this->createShipReportStorageEntry($storageEntry);
                } else {
                    $this->updateShipReportStorageEntry($storageEntry);
                }
            }

            $this->updateLinkedShippingCall($report);

            $this->mysql->commit();

            return $report;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            throw new DBException("Impossible de mettre à jour le rapport navire.", previous: $e);
        } catch (\Throwable $th) {
            $this->mysql->rollbackIfNeeded();
            throw $th;
        }
    }

    /**
     * 
     * @param int $id 
     * 
     * @throws DBException 
     */
    public function deleteShipReport(int $id): void
    {
        try {
            $this->mysql->prepareAndExecute(
                "DELETE FROM stevedoring_ship_reports WHERE id = :id",
                ['id' => $id]
            );
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la suppression.", previous: $e);
        }
    }

    private function updateLinkedShippingCall(ShipReport $shipReport): void
    {
        if ($shipReport->linkedShippingCall) {
            $this->mysql->prepareAndExecute(
                "UPDATE consignation_planning
             SET
                stevedoring_ship_report_id = :reportId,
                navire = :shipName,
                call_port = :port,
                quai = :berth
             WHERE
                id = :callId",
                [
                    'reportId' => $shipReport->id,
                    'shipName' => $shipReport->ship,
                    'port' => $shipReport->port,
                    'berth' => $shipReport->berth,
                    'callId' => $shipReport->linkedShippingCall->id,
                ]
            );
        } else {
            $this->mysql->prepareAndExecute(
                "UPDATE consignation_planning
                 SET
                    stevedoring_ship_report_id = NULL
                 WHERE
                    stevedoring_ship_report_id = :reportId",
                ['reportId' => $shipReport->id]
            );
        }
    }

    /**
     * @return Collection<ShipSubreport>
     */
    private function fetchSubreportsForReport(ShipReport $report): Collection
    {
        $subreportStatement =
            "SELECT
                id,
                report_id
             FROM stevedoring_ship_subreports
             WHERE report_id = :reportId";

        $cargoIdsStatement =
            "SELECT cargo_id
            FROM stevedoring_ship_subreport_cargo
            WHERE subreport_id = :subreportId";

        try {
            /** @var ShipSubreportArray[] */
            $subreportRaw = $this->mysql
                ->prepareAndExecute($subreportStatement, ['reportId' => $report->id])
                ->fetchAll();

            $subreports = \array_map(
                fn($data) => $this->stevedoringService->makeShipSubreportFromDatabase($data),
                $subreportRaw
            );

            foreach ($subreports as $subreport) {
                $cargoIds = $this->mysql
                    ->prepareAndExecute($cargoIdsStatement, ['subreportId' => $subreport->id])
                    ->fetchAll(\PDO::FETCH_COLUMN);

                $subreport->cargoEntries = $report->cargoEntries->filter(
                    fn(ShippingCallCargo $cargo) => \in_array($cargo->id, $cargoIds)
                );
                $subreport->equipmentEntries = $this->fetchEquipmentEntriesForSubreport($subreport);
                $subreport->staffEntries = $this->fetchStaffEntriesForSubreport($subreport);
                $subreport->subcontractEntries = $this->fetchSubcontractEntriesForSubreport($subreport);
            }

            return new Collection($subreports);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les sous-rapports.", previous: $e);
        }
    }

    private function createShipSubreport(ShipSubreport $subreport): ShipSubreport
    {
        $statement = "INSERT INTO stevedoring_ship_subreports SET report_id = :reportId";

        try {
            $this->mysql->prepareAndExecute($statement, ['reportId' => $subreport->shipReport->id]);

            $subreport->id = (int) $this->mysql->lastInsertId();

            // Cargo entries
            $this->mysql->prepareAndExecute(
                "INSERT INTO stevedoring_ship_subreport_cargo SET subreport_id = :subreportId, cargo_id = :cargoId",
                $subreport->cargoEntries->map(fn($cargo) => ['subreportId' => $subreport->id, 'cargoId' => $cargo->id])
            );

            // Equipment entries
            foreach ($subreport->equipmentEntries as $equipmentEntry) {
                $this->createShipReportEquipmentEntry($equipmentEntry);
            }

            // Staff entries
            foreach ($subreport->staffEntries as $staffEntry) {
                $this->createShipReportStaffEntry($staffEntry);
            }

            // Subcontract entries
            foreach ($subreport->subcontractEntries as $subcontractEntry) {
                $this->createShipReportSubcontractEntry($subcontractEntry);
            }

            return $subreport;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            throw new DBException("Impossible de créer le sous-rapport.", previous: $e);
        }
    }

    private function updateShipSubreport(ShipSubreport $subreport): ShipSubreport
    {
        try {
            // Cargo
            // Delete and re-create cargo association
            $this->mysql->prepareAndExecute(
                "DELETE FROM stevedoring_ship_subreport_cargo WHERE subreport_id = :subreportId",
                ['subreportId' => $subreport->id]
            );
            $this->mysql->prepareAndExecute(
                "INSERT INTO stevedoring_ship_subreport_cargo SET subreport_id = :subreportId, cargo_id = :cargoId",
                $subreport->cargoEntries->map(fn($cargo) => ['subreportId' => $subreport->id, 'cargoId' => $cargo->id])
            );

            // Equipment entries
            // Delete entries that are not in the new list
            $existingIds = \array_filter(
                $subreport->equipmentEntries->map(fn($entry) => $entry->id),
                fn($id) => $id !== null
            );
            $this->mysql->prepareAndExecute(
                \count($existingIds) > 0
                    ? \sprintf(
                        "DELETE FROM stevedoring_ship_reports_equipments WHERE ship_report_id = :reportId AND NOT id IN (%s)",
                        \join(",", $existingIds)
                    )
                    : "DELETE FROM stevedoring_ship_reports_equipments WHERE ship_report_id = :reportId",
                ['reportId' => $subreport->id]
            );

            foreach ($subreport->equipmentEntries as $equipmentEntry) {
                if (!$equipmentEntry->id) {
                    $equipmentEntry->id = $this->createShipReportEquipmentEntry($equipmentEntry)->id;
                } else {
                    $this->updateShipReportEquipmentEntry($equipmentEntry);
                }
            }

            // Staff entries
            // Delete entries that are not in the new list
            $existingIds = \array_filter(
                $subreport->staffEntries->map(fn($entry) => $entry->id),
                fn($id) => $id !== null
            );
            $this->mysql->prepareAndExecute(
                \count($existingIds) > 0
                    ? \sprintf(
                        "DELETE FROM stevedoring_ship_reports_staff WHERE ship_report_id = :reportId AND NOT id IN (%s)",
                        \join(",", $existingIds)
                    )
                    : "DELETE FROM stevedoring_ship_reports_staff WHERE ship_report_id = :reportId",
                ['reportId' => $subreport->id]
            );

            foreach ($subreport->staffEntries as $staffEntry) {
                if (!$staffEntry->id) {
                    $staffEntry->id = $this->createShipReportStaffEntry($staffEntry)->id;
                } else {
                    $this->updateShipReportStaffEntry($staffEntry);
                }
            }

            // Subcontract entries
            // Delete entries that are not in the new list
            $existingIds = \array_filter(
                $subreport->subcontractEntries->map(fn($entry) => $entry->id),
                fn($id) => $id !== null
            );
            $this->mysql->prepareAndExecute(
                \count($existingIds) > 0
                    ? \sprintf(
                        "DELETE FROM stevedoring_ship_reports_subcontracts WHERE ship_report_id = :reportId AND NOT id IN (%s)",
                        \join(",", $existingIds)
                    )
                    : "DELETE FROM stevedoring_ship_reports_subcontracts WHERE ship_report_id = :reportId",
                ['reportId' => $subreport->id]
            );

            foreach ($subreport->subcontractEntries as $subcontractEntry) {
                if (!$subcontractEntry->id) {
                    $subcontractEntry->id = $this->createShipReportSubcontractEntry($subcontractEntry)->id;
                } else {
                    $this->updateShipReportSubcontractEntry($subcontractEntry);
                }
            }

            return $subreport;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            throw new DBException("Impossible de mettre à jour le sous-rapport.", previous: $e);
        }
    }

    /**
     * @param ShipSubreport $subreport 
     * 
     * @return Collection<ShipReportEquipmentEntry>
     * 
     * @throws DBException 
     * @throws \RuntimeException 
     */
    private function fetchEquipmentEntriesForSubreport(ShipSubreport $subreport): Collection
    {
        $statement =
            "SELECT
                id,
                ship_report_id,
                subreport_id,
                equipment_id,
                date,
                hours_hint,
                hours_worked,
                comments
             FROM stevedoring_ship_reports_equipments
             WHERE subreport_id = :subreportId";

        try {
            /** @var ShipReportEquipmentEntryArray[] */
            $rawData = $this->mysql
                ->prepareAndExecute($statement, ['subreportId' => $subreport->id])
                ->fetchAll();

            $entries = \array_map(
                fn($data) => $this->stevedoringService->makeShipReportEquipmentEntryFromDatabase($data),
                $rawData
            );

            return new Collection($entries);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les entrées d'équipement.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipReportEquipmentEntry $entry 
     * 
     * @return ShipReportEquipmentEntry 
     * 
     * @throws \PDOException 
     * @throws BadRequestException 
     * @throws DBException 
     */
    private function createShipReportEquipmentEntry(ShipReportEquipmentEntry $entry): ShipReportEquipmentEntry
    {
        $statement =
            "INSERT INTO stevedoring_ship_reports_equipments
             SET
                ship_report_id = :reportId,
                subreport_id = :subreportId,
                equipment_id = :equipmentId,
                date = :date,
                hours_hint = :hoursHint,
                hours_worked = :hoursWorked,
                comments = :comments";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'reportId' => $entry->subreport?->shipReport->id,
                    'subreportId' => $entry->subreport?->id,
                    'equipmentId' => $entry->equipment?->id,
                    'date' => $entry->date?->format('Y-m-d'),
                    'hoursHint' => $entry->hoursHint,
                    'hoursWorked' => $entry->hoursWorked,
                    'comments' => $entry->comments,
                ]
            );

            $entry->id = (int) $this->mysql->lastInsertId();

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            if ($e->getCode() == 23000) {
                $message = \sprintf(
                    "L'équipement %s est déjà enregistré pour la date %s.",
                    $entry->equipment?->displayName,
                    $entry->date?->format('d/m/Y')
                );
                throw new BadRequestException($message, previous: $e);
            }

            throw new DBException("Impossible de créer l'entrée d'équipement.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipReportEquipmentEntry $entry 
     * 
     * @return ShipReportEquipmentEntry 
     * 
     * @throws \PDOException 
     * @throws BadRequestException 
     * @throws DBException 
     */
    private function updateShipReportEquipmentEntry(ShipReportEquipmentEntry $entry): ShipReportEquipmentEntry
    {
        $statement =
            "UPDATE stevedoring_ship_reports_equipments
             SET
                equipment_id = :equipmentId,
                date = :date,
                hours_hint = :hoursHint,
                hours_worked = :hoursWorked,
                comments = :comments
             WHERE
                id = :id";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'id' => $entry->id,
                    'equipmentId' => $entry->equipment?->id,
                    'date' => $entry->date?->format('Y-m-d'),
                    'hoursHint' => $entry->hoursHint,
                    'hoursWorked' => $entry->hoursWorked,
                    'comments' => $entry->comments,
                ]
            );

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            if ($e->getCode() == 23000) {
                $message = \sprintf(
                    "L'équipement %s est déjà enregistré pour la date %s.",
                    $entry->equipment?->displayName,
                    $entry->date?->format('d/m/Y')
                );
                throw new BadRequestException($message, previous: $e);
            }

            throw new DBException("Impossible de modifier l'entrée d'équipement.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipSubreport $subreport 
     * 
     * @return Collection<ShipReportStaffEntry>
     * 
     * @throws DBException 
     * @throws \RuntimeException 
     */
    private function fetchStaffEntriesForSubreport(ShipSubreport $subreport): Collection
    {
        $statement =
            "SELECT
                reportsStaff.id,
                reportsStaff.ship_report_id,
                reportsStaff.subreport_id,
                reportsStaff.staff_id,
                reportsStaff.date,
                reportsStaff.hours_hint,
                reportsStaff.hours_worked,
                reportsStaff.comments
             FROM stevedoring_ship_reports_staff reportsStaff
             LEFT JOIN stevedoring_staff staff ON staff.id = reportsStaff.staff_id
             WHERE subreport_id = :subreportId
             ORDER BY
                staff.lastname ASC,
                staff.firstname ASC,
                date ASC";

        try {
            /** @phpstan-var ShipReportStaffEntryArray[] */
            $rawData = $this->mysql
                ->prepareAndExecute($statement, ['subreportId' => $subreport->id])
                ->fetchAll();

            $entries = \array_map(
                fn($data) => $this->stevedoringService->makeShipReportStaffEntryFromDatabase($data),
                $rawData
            );

            return new Collection($entries);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les entrées de personnel.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipReportStaffEntry $entry 
     * 
     * @return ShipReportStaffEntry 
     * 
     * @throws \PDOException 
     * @throws BadRequestException 
     * @throws DBException 
     */
    private function createShipReportStaffEntry(ShipReportStaffEntry $entry): ShipReportStaffEntry
    {
        $statement =
            "INSERT INTO stevedoring_ship_reports_staff
             SET
                ship_report_id = :reportId,
                subreport_id = :subreportId,
                staff_id = :staffId,
                date = :date,
                hours_hint = :hoursHint,
                hours_worked = :hoursWorked,
                comments = :comments";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'reportId' => $entry->subreport?->shipReport->id,
                    'subreportId' => $entry->subreport?->id,
                    'staffId' => $entry->staff?->id,
                    'date' => $entry->date?->format('Y-m-d'),
                    'hoursHint' => $entry->hoursHint,
                    'hoursWorked' => $entry->hoursWorked,
                    'comments' => $entry->comments,
                ]
            );

            $entry->id = (int) $this->mysql->lastInsertId();

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            if ($e->getCode() == 23000) {
                $message = \sprintf(
                    "%s est déjà enregistré pour la date %s.",
                    $entry->staff?->fullname,
                    $entry->date?->format('d/m/Y')
                );
                throw new BadRequestException($message, previous: $e);
            }

            throw new DBException("Impossible de créer l'entrée de personnel.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipReportStaffEntry $entry 
     * 
     * @return ShipReportStaffEntry 
     * 
     * @throws \PDOException 
     * @throws BadRequestException 
     * @throws DBException 
     */
    private function updateShipReportStaffEntry(ShipReportStaffEntry $entry): ShipReportStaffEntry
    {
        $statement =
            "UPDATE stevedoring_ship_reports_staff
             SET
                staff_id = :staffId,
                date = :date,
                hours_hint = :hoursHint,
                hours_worked = :hoursWorked,
                comments = :comments
             WHERE
                id = :id";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'id' => $entry->id,
                    'staffId' => $entry->staff?->id,
                    'date' => $entry->date?->format('Y-m-d'),
                    'hoursHint' => $entry->hoursHint,
                    'hoursWorked' => $entry->hoursWorked,
                    'comments' => $entry->comments,
                ]
            );

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            if ($e->getCode() == 23000) {
                $message = \sprintf(
                    "%s est déjà enregistré pour la date %s.",
                    $entry->staff?->fullname,
                    $entry->date?->format('d/m/Y')
                );
                throw new BadRequestException($message, previous: $e);
            }

            throw new DBException("Impossible de modifier l'entrée de personnel.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipSubreport $subreport
     * 
     * @return Collection<ShipReportSubcontractEntry>
     *  
     * @throws DBException 
     * @throws \RuntimeException 
     */
    private function fetchSubcontractEntriesForSubreport(ShipSubreport $subreport): Collection
    {
        $statement =
            "SELECT
                id,
                ship_report_id,
                subreport_id,
                subcontractor_name,
                type,
                date,
                hours_worked,
                cost,
                comments
             FROM stevedoring_ship_reports_subcontracts
             WHERE subreport_id = :subreportId
             ORDER BY
                date ASC,
                subcontractor_name ASC";

        try {
            /** @var ShipReportSubcontractEntryArray[] */
            $rawData = $this->mysql
                ->prepareAndExecute($statement, ['subreportId' => $subreport->id])
                ->fetchAll();

            $entries = \array_map(
                fn($data) => $this->stevedoringService->makeShipReportSubcontractEntryFromDatabase($data),
                $rawData
            );

            return new Collection($entries);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les entrées de personnel.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipReportSubcontractEntry $entry 
     * 
     * @return ShipReportSubcontractEntry 
     * 
     * @throws \PDOException 
     * @throws BadRequestException 
     * @throws DBException 
     */
    private function createShipReportSubcontractEntry(ShipReportSubcontractEntry $entry): ShipReportSubcontractEntry
    {
        $statement =
            "INSERT INTO stevedoring_ship_reports_subcontracts
             SET
                ship_report_id = :reportId,
                subreport_id = :subreportId,
                subcontractor_name = :subcontractorName,
                type = :type,
                date = :date,
                hours_worked = :hoursWorked,
                cost = :cost,
                comments = :comments";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'reportId' => $entry->subreport?->shipReport->id,
                    'subreportId' => $entry->subreport?->id,
                    'subcontractorName' => $entry->subcontractorName,
                    'type' => $entry->type,
                    'date' => $entry->date?->format('Y-m-d'),
                    'hoursWorked' => $entry->hoursWorked,
                    'cost' => $entry->cost,
                    'comments' => $entry->comments,
                ]
            );

            $entry->id = (int) $this->mysql->lastInsertId();

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            if ($e->getCode() == 23000) {
                $message = \sprintf(
                    "%s est déjà enregistré pour la date %s.",
                    $entry->subcontractorName,
                    $entry->date?->format('d/m/Y')
                );
                throw new BadRequestException($message, previous: $e);
            }

            throw new DBException("Impossible de créer l'entrée de personnel.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipReportSubcontractEntry $entry 
     * 
     * @return ShipReportSubcontractEntry 
     * 
     * @throws \PDOException 
     * @throws BadRequestException 
     * @throws DBException 
     */
    private function updateShipReportSubcontractEntry(ShipReportSubcontractEntry $entry): ShipReportSubcontractEntry
    {
        $statement =
            "UPDATE stevedoring_ship_reports_subcontracts
             SET
                subcontractor_name = :subcontractorName,
                type = :type,
                date = :date,
                hours_worked = :hoursWorked,
                cost = :cost,
                comments = :comments
             WHERE
                id = :id";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'id' => $entry->id,
                    'subcontractorName' => $entry->subcontractorName,
                    'type' => $entry->type,
                    'date' => $entry->date?->format('Y-m-d'),
                    'hoursWorked' => $entry->hoursWorked,
                    'cost' => $entry->cost,
                    'comments' => $entry->comments,
                ]
            );

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            if ($e->getCode() == 23000) {
                $message = \sprintf(
                    "%s est déjà enregistré pour la date %s.",
                    $entry->subcontractorName,
                    $entry->date?->format('d/m/Y')
                );
                throw new BadRequestException($message, previous: $e);
            }

            throw new DBException("Impossible de modifier l'entrée de personnel.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipReport $report
     * 
     * @return Collection<ShippingCallCargo>
     * 
     * @throws DBException 
     * @throws \RuntimeException 
     */
    private function fetchCargoEntriesForReport(ShipReport $report): Collection
    {
        $statement =
            "SELECT id
             FROM consignation_escales_marchandises
             WHERE ship_report_id = :reportId";

        $shippingService = new ShippingService();

        try {
            /** @var int[] */
            $cargoIds = $this->mysql
                ->prepareAndExecute($statement, ['reportId' => $report->id])
                ->fetchAll(\PDO::FETCH_COLUMN);

            $entries = \array_filter(\array_map(
                fn($id) => $shippingService->getCargoEntry($id),
                $cargoIds
            ));

            return new Collection($entries);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les marchandises.", previous: $e);
        }
    }

    /**
     * 
     * @param ShippingCallCargo $entry 
     * 
     * @return ShippingCallCargo 
     * 
     * @throws \PDOException 
     * @throws DBException 
     */
    private function createCargoEntry(ShippingCallCargo $entry): ShippingCallCargo
    {
        $statement =
            "INSERT INTO consignation_escales_marchandises
             SET
                escale_id = :shippingCallId,
                ship_report_id = :reportId,
                marchandise = :cargoName,
                client = :customer,
                operation = :operation,
                tonnage_bl = :blTonnage,
                cubage_bl = :blVolume,
                nombre_bl = :blUnits,
                tonnage_outturn = :outturnTonnage,
                cubage_outturn = :outturnVolume,
                nombre_outturn = :outturnUnits";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'shippingCallId' => $entry->shippingCall?->id,
                    'reportId' => $entry->shipReport?->id,
                    'cargoName' => $entry->cargoName,
                    'customer' => $entry->customer,
                    'operation' => $entry->operation,
                    'blTonnage' => $entry->blTonnage,
                    'blVolume' => $entry->blVolume,
                    'blUnits' => $entry->blUnits,
                    'outturnTonnage' => $entry->outturnTonnage,
                    'outturnVolume' => $entry->outturnVolume,
                    'outturnUnits' => $entry->outturnUnits,
                ]
            );

            $entry->id = (int) $this->mysql->lastInsertId();

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            throw new DBException("Impossible de créer la marchandise {$entry->cargoName}.", previous: $e);
        }
    }

    /**
     * 
     * @param ShippingCallCargo $entry 
     * 
     * @return ShippingCallCargo 
     * 
     * @throws \PDOException 
     * @throws DBException 
     */
    private function updateCargoEntry(ShippingCallCargo $entry): ShippingCallCargo
    {
        $statement =
            "UPDATE consignation_escales_marchandises
             SET
                escale_id = :shippingCallId,
                ship_report_id = :reportId,
                marchandise = :cargoName,
                client = :customer,
                operation = :operation,
                tonnage_bl = :blTonnage,
                cubage_bl = :blVolume,
                nombre_bl = :blUnits,
                tonnage_outturn = :outturnTonnage,
                cubage_outturn = :outturnVolume,
                nombre_outturn = :outturnUnits
             WHERE
                id = :id";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'shippingCallId' => $entry->shippingCall?->id,
                    'reportId' => $entry->shipReport?->id,
                    'cargoName' => $entry->cargoName,
                    'customer' => $entry->customer,
                    'operation' => $entry->operation,
                    'blTonnage' => $entry->blTonnage,
                    'blVolume' => $entry->blVolume,
                    'blUnits' => $entry->blUnits,
                    'outturnTonnage' => $entry->outturnTonnage,
                    'outturnVolume' => $entry->outturnVolume,
                    'outturnUnits' => $entry->outturnUnits,
                    'id' => $entry->id,
                ]
            );

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            throw new DBException("Impossible de modifier la marchandise {$entry->cargoName}.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipReport $report
     * 
     * @return Collection<ShipReportStorageEntry>
     *  
     * @throws DBException 
     * @throws \RuntimeException 
     */
    private function fetchStorageEntriesForReport(ShipReport $report): Collection
    {
        $statement =
            "SELECT
                id,
                ship_report_id,
                cargo_id,
                storage_name,
                tonnage,
                volume,
                units,
                comments
             FROM stevedoring_ship_reports_storage
             WHERE ship_report_id = :reportId";

        try {
            /** @var ShipReportStorageEntryArray[] */
            $rawData = $this->mysql
                ->prepareAndExecute($statement, ['reportId' => $report->id])
                ->fetchAll();

            $entries = \array_map(
                fn(array $data) => $this->stevedoringService->makeShipReportStorageEntryFromDatabase($data),
                $rawData
            );

            return new Collection($entries);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les entrées de personnel.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipReportStorageEntry $entry 
     * 
     * @return ShipReportStorageEntry 
     * 
     * @throws \PDOException 
     * @throws BadRequestException 
     * @throws DBException 
     */
    private function createShipReportStorageEntry(ShipReportStorageEntry $entry): ShipReportStorageEntry
    {
        $statement =
            "INSERT INTO stevedoring_ship_reports_storage
             SET
                ship_report_id = :reportId,
                cargo_id = :cargoId,
                storage_name = :storageName,
                tonnage = :tonnage,
                volume = :volume,
                units = :units,
                comments = :comments";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'reportId' => $entry->report?->id,
                    'cargoId' => $entry->cargo?->id,
                    'storageName' => $entry->storageName,
                    'tonnage' => $entry->tonnage,
                    'volume' => $entry->volume,
                    'units' => $entry->units,
                    'comments' => $entry->comments,
                ]
            );

            $entry->id = (int) $this->mysql->lastInsertId();

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            if ($e->getCode() == 23000) {
                $message = \sprintf(
                    "La combinaison %s (%s) - %s est enregistrée plusieurs fois.",
                    $entry->cargo?->cargoName,
                    $entry->cargo?->customer,
                    $entry->storageName
                );
                throw new BadRequestException($message, previous: $e);
            }

            throw new DBException("Impossible de créer l'entrée de stockage.", previous: $e);
        }
    }

    /**
     * 
     * @param ShipReportStorageEntry $entry 
     * 
     * @return ShipReportStorageEntry 
     * 
     * @throws \PDOException 
     * @throws BadRequestException 
     * @throws DBException 
     */
    private function updateShipReportStorageEntry(ShipReportStorageEntry $entry): ShipReportStorageEntry
    {
        $statement =
            "UPDATE stevedoring_ship_reports_storage
             SET
                ship_report_id = :reportId,
                cargo_id = :cargoId,
                storage_name = :storageName,
                tonnage = :tonnage,
                volume = :volume,
                units = :units,
                comments = :comments
             WHERE
                id = :id";

        try {
            $this->mysql->prepareAndExecute(
                $statement,
                [
                    'reportId' => $entry->report?->id,
                    'cargoId' => $entry->cargo?->id,
                    'storageName' => $entry->storageName,
                    'tonnage' => $entry->tonnage,
                    'volume' => $entry->volume,
                    'units' => $entry->units,
                    'comments' => $entry->comments,
                    'id' => $entry->id,
                ]
            );

            return $entry;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();

            if ($e->getCode() == 23000) {
                $message = \sprintf(
                    "La combinaison %s (%s) - %s est enregistrée plusieurs fois.",
                    $entry->cargo?->cargoName,
                    $entry->cargo?->customer,
                    $entry->storageName
                );
                throw new BadRequestException($message, previous: $e);
            }

            throw new DBException("Impossible de modifier l'entrée de stockage.", previous: $e);
        }
    }

    // ======
    // Others
    // ======

    public function fetchShipReportsFilterData(): StevedoringReportsFilterDataDTO
    {
        try {
            // Ships
            $shipsStatement = "SELECT DISTINCT ship FROM stevedoring_ship_reports ORDER BY ship";
            /** @var string[] */
            $ships = $this->mysql
                ->prepareAndExecute($shipsStatement)
                ->fetchAll(\PDO::FETCH_COLUMN);

            // Ports
            $portsStatement = "SELECT DISTINCT port FROM stevedoring_ship_reports ORDER BY port";
            /** @var string[] */
            $ports = $this->mysql
                ->prepareAndExecute($portsStatement)
                ->fetchAll(\PDO::FETCH_COLUMN);

            // Berths
            $berthsStatement = "SELECT DISTINCT berth FROM stevedoring_ship_reports ORDER BY port";
            /** @var string[] */
            $berths = $this->mysql
                ->prepareAndExecute($berthsStatement)
                ->fetchAll(\PDO::FETCH_COLUMN);

            // Cargoes
            $cargoesStatement = "SELECT DISTINCT marchandise FROM consignation_escales_marchandises ORDER BY marchandise";
            /** @var string[] */
            $cargoes = $this->mysql
                ->prepareAndExecute($cargoesStatement)
                ->fetchAll(\PDO::FETCH_COLUMN);

            // Customers
            $customersStatement = "SELECT DISTINCT client FROM consignation_escales_marchandises ORDER BY client";
            /** @var string[] */
            $customers = $this->mysql
                ->prepareAndExecute($customersStatement)
                ->fetchAll(\PDO::FETCH_COLUMN);

            // Storage names
            $storageNamesStatement = "SELECT DISTINCT storage_name FROM stevedoring_ship_reports_storage ORDER BY storage_name";
            /** @var string[] */
            $storageNames = $this->mysql
                ->prepareAndExecute($storageNamesStatement)
                ->fetchAll();

            $filterDataDto = new StevedoringReportsFilterDataDTO(
                ships: $ships,
                ports: $ports,
                berths: $berths,
                cargoes: $cargoes,
                customers: $customers,
                storageNames: $storageNames,
            );

            return $filterDataDto;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les données de filtre.", previous: $e);
        }
    }

    /**
     * @return Collection<CallWithoutReportDTO>
     */
    public function fetchCallsWithoutReport(): Collection
    {
        try {
            $statement =
                "SELECT
                    id,
                    navire as shipName,
                    ops_date as startDate,
                    etc_date as endDate
                FROM consignation_planning cp
                WHERE
                    -- Exclude calls with reports
                    NOT EXISTS (
                        SELECT 1
                        FROM stevedoring_ship_reports report
                        WHERE report.linked_shipping_call_id = cp.id
                    )
                    -- Exclude ignored calls
                    AND NOT EXISTS (
                        SELECT 1
                        FROM stevedoring_ignored_shipping_calls sigs
                        WHERE sigs.shipping_call_id = cp.id
                    )
                    AND NOT cp.navire = 'TBN'
                    AND cp.eta_date <= CURDATE()
                ORDER BY
                    cp.eta_date ASC,
                    cp.eta_heure ASC
                ";

            /** @phpstan-var CallWithoutReport[] */
            $callSummariesRaw = $this->mysql
                ->prepareAndExecute($statement)
                ->fetchAll();

            $callDTOs = \array_map(
                fn($callSummaryRaw) => new CallWithoutReportDTO($callSummaryRaw),
                $callSummariesRaw
            );

            return new Collection($callDTOs);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer la liste des escales consignation sans rapport.", previous: $e);
        }
    }

    /**
     * @return Collection<IgnoredCallDTO>
     */
    public function fetchIgnoredShippingCalls(): Collection
    {
        $statement =
            "SELECT cp.id, cp.navire as shipName, ops_date as startDate, etc_date as endDate
            FROM consignation_planning cp
            RIGHT JOIN stevedoring_ignored_shipping_calls sigs ON cp.id = sigs.shipping_call_id";

        try {
            /** @var array{id: int, shipName: string}[] */
            $ignoredCallsRaw = $this->mysql
                ->prepareAndExecute($statement)
                ->fetchAll();

            $ignoredCallsDTOs = \array_map(
                fn(array $ignoredCallRaw) => new IgnoredCallDTO($ignoredCallRaw),
                $ignoredCallsRaw
            );

            return new Collection($ignoredCallsDTOs);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer la liste des escales ignorées.", previous: $e);
        }
    }

    public function ignoreShippingCall(int $shippingCallId): void
    {
        $statement = "INSERT INTO stevedoring_ignored_shipping_calls SET shipping_call_id = :shippingCallId";

        try {
            $this->mysql->prepareAndExecute($statement, ['shippingCallId' => $shippingCallId]);
        } catch (\PDOException $e) {
            throw new DBException("Impossible d'ignorer l'escale.", previous: $e);
        }
    }

    public function unignoreShippingCall(int $shippingCallId): void
    {
        $statement = "DELETE FROM stevedoring_ignored_shipping_calls WHERE shipping_call_id = :shippingCallId";

        try {
            $this->mysql->prepareAndExecute($statement, ['shippingCallId' => $shippingCallId]);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de rétablir l'escale.", previous: $e);
        }
    }

    public function fetchSubcontractorsData(): StevedoringSubcontractorsDataDTO
    {
        $statement =
            "SELECT DISTINCT
                subcontractor_name as `name`,
                type
             FROM stevedoring_ship_reports_subcontracts
             ORDER BY subcontractor_name, type";

        try {
            /** @var array{name: string, type: string}[] */
            $subcontractorsData = $this->mysql
                ->prepareAndExecute($statement)
                ->fetchAll();

            return new StevedoringSubcontractorsDataDTO($subcontractorsData);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les données des sous-traitants.", previous: $e);
        }
    }
}
