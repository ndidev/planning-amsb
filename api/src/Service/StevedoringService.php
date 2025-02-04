<?php

// Path: api/src/Service/StevedoringService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\HTTPRequestBody;
use App\Core\Twig\Twig;
use App\DTO\CallWithoutReportDTO;
use App\DTO\Filter\StevedoringDispatchFilterDTO;
use App\DTO\Filter\StevedoringReportsFilterDataDTO;
use App\DTO\Filter\StevedoringReportsFilterDTO;
use App\DTO\Filter\StevedoringStaffFilterDTO;
use App\DTO\Filter\StevedoringTempWorkHoursFilterDTO;
use App\DTO\StevedoringDispatchDTO;
use App\DTO\StevedoringSubcontractorsDataDTO;
use App\DTO\TempWorkDispatchForDateDTO;
use App\Entity\Stevedoring\ShipReport;
use App\Entity\Stevedoring\ShipReportEquipmentEntry;
use App\Entity\Stevedoring\ShipReportStaffEntry;
use App\Entity\Stevedoring\ShipReportStorageEntry;
use App\Entity\Stevedoring\ShipReportSubcontractEntry;
use App\Entity\Stevedoring\StevedoringEquipment;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Entity\Stevedoring\TempWorkHoursEntry;
use App\Repository\StevedoringRepository;
use Ds\Map;
use Mpdf\Mpdf;

/**
 * @phpstan-import-type ShipReportEquipmentEntryArray from \App\Repository\StevedoringRepository
 * @phpstan-import-type ShipReportStaffEntryArray from \App\Repository\StevedoringRepository
 * @phpstan-import-type ShipReportSubcontractEntryArray from \App\Repository\StevedoringRepository
 * @phpstan-import-type ShippingCallCargoArray from \App\Repository\ShippingRepository
 * @phpstan-import-type ShipReportStorageEntryArray from \App\Repository\StevedoringRepository
 */
final class StevedoringService
{
    private StevedoringRepository $stevedoringRepository;

    public function __construct()
    {
        $this->stevedoringRepository = new StevedoringRepository($this);
    }

    // =====
    // Staff
    // =====

    /**
     * @param array<mixed> $rawData 
     */
    public function makeStevedoringStaffFromDatabase(array $rawData): StevedoringStaff
    {
        $rawDataAH = new ArrayHandler($rawData);

        $stevedoringStaff = new StevedoringStaff();
        $stevedoringStaff->id = $rawDataAH->getInt('id');
        $stevedoringStaff->firstname = $rawDataAH->getString('firstname');
        $stevedoringStaff->lastname = $rawDataAH->getString('lastname');
        $stevedoringStaff->phone = $rawDataAH->getString('phone');
        $stevedoringStaff->type = $rawDataAH->getString('type');
        $stevedoringStaff->tempWorkAgency = $rawDataAH->getString('temp_work_agency', null);
        $stevedoringStaff->isActive = $rawDataAH->getBool('is_active');
        $stevedoringStaff->comments = $rawDataAH->getString('comments');
        $stevedoringStaff->deletedAt = $rawDataAH->getDatetime('deleted_at');

        return $stevedoringStaff;
    }

    public function makeStevedoringStaffFromRequest(HTTPRequestBody $requestBody): StevedoringStaff
    {
        $stevedoringStaff = new StevedoringStaff();
        $stevedoringStaff->id = $requestBody->getInt('id');
        $stevedoringStaff->firstname = $requestBody->getString('firstname');
        $stevedoringStaff->lastname = $requestBody->getString('lastname');
        $stevedoringStaff->phone = $requestBody->getString('phone');
        $stevedoringStaff->type = $requestBody->getString('type');
        $stevedoringStaff->tempWorkAgency = $requestBody->getString('tempWorkAgency', null);
        $stevedoringStaff->isActive = $requestBody->getBool('isActive');
        $stevedoringStaff->comments = $requestBody->getString('comments');

        if ($stevedoringStaff->type === "cdi") {
            $stevedoringStaff->tempWorkAgency = null;
        }

        return $stevedoringStaff;
    }

    public function staffExists(int $id): bool
    {
        return $this->stevedoringRepository->staffExists($id);
    }

    /**
     * @return Collection<StevedoringStaff>
     */
    public function getAllStaff(StevedoringStaffFilterDTO $filter): Collection
    {
        return $this->stevedoringRepository->fetchAllStaff($filter);
    }

    public function getStaff(?int $id): ?StevedoringStaff
    {
        if ($id === null) {
            return null;
        }

        return $this->stevedoringRepository->fetchStaff($id);
    }

    public function createStaff(HTTPRequestBody $requestBody): StevedoringStaff
    {
        $stevedoringStaff = $this->makeStevedoringStaffFromRequest($requestBody);

        $stevedoringStaff->validate();

        return $this->stevedoringRepository->createStaff($stevedoringStaff);
    }

    public function updateStaff(int $id, HTTPRequestBody $requestBody): StevedoringStaff
    {
        $stevedoringStaff = $this->makeStevedoringStaffFromRequest($requestBody);
        $stevedoringStaff->id = $id;

        $stevedoringStaff->validate();

        return $this->stevedoringRepository->updateStaff($stevedoringStaff);
    }

    public function deleteStaff(int $id): void
    {
        $this->stevedoringRepository->deleteStaff($id);
    }

    // =========
    // Equipment
    // =========

    /**
     * @param array<mixed> $rawData
     */
    public function makeStevedoringEquipmentFromDatabase(array $rawData): StevedoringEquipment
    {
        $rawDataAH = new ArrayHandler($rawData);

        $stevedoringEquipment = new StevedoringEquipment();
        $stevedoringEquipment->id = $rawDataAH->getInt('id');
        $stevedoringEquipment->type = $rawDataAH->getString('type');
        $stevedoringEquipment->brand = $rawDataAH->getString('brand');
        $stevedoringEquipment->model = $rawDataAH->getString('model');
        $stevedoringEquipment->internalNumber = $rawDataAH->getString('internal_number');
        $stevedoringEquipment->serialNumber = $rawDataAH->getString('serial_number');
        $stevedoringEquipment->comments = $rawDataAH->getString('comments');
        $stevedoringEquipment->isActive = $rawDataAH->getBool('is_active');

        return $stevedoringEquipment;
    }

    public function makeStevedoringEquipmentFromRequest(HTTPRequestBody $requestBody): StevedoringEquipment
    {
        $stevedoringEquipment = new StevedoringEquipment();
        $stevedoringEquipment->id = $requestBody->getInt('id');
        $stevedoringEquipment->type = $requestBody->getString('type');
        $stevedoringEquipment->brand = $requestBody->getString('brand');
        $stevedoringEquipment->model = $requestBody->getString('model');
        $stevedoringEquipment->internalNumber = $requestBody->getString('internalNumber');
        $stevedoringEquipment->serialNumber = $requestBody->getString('serialNumber');
        $stevedoringEquipment->comments = $requestBody->getString('comments');
        $stevedoringEquipment->isActive = $requestBody->getBool('isActive');

        return $stevedoringEquipment;
    }

    public function equipmentExists(int $id): bool
    {
        return $this->stevedoringRepository->equipmentExists($id);
    }

    /**
     * @return Collection<StevedoringEquipment>
     */
    public function getAllEquipments(): Collection
    {
        return $this->stevedoringRepository->fetchAllEquipments();
    }

    public function getEquipment(?int $id): ?StevedoringEquipment
    {
        if ($id === null) {
            return null;
        }

        return $this->stevedoringRepository->fetchEquipment($id);
    }

    public function createEquipment(HTTPRequestBody $requestBody): StevedoringEquipment
    {
        $stevedoringEquipment = $this->makeStevedoringEquipmentFromRequest($requestBody);

        $stevedoringEquipment->validate();

        return $this->stevedoringRepository->createEquipment($stevedoringEquipment);
    }

    public function updateEquipment(int $id, HTTPRequestBody $requestBody): StevedoringEquipment
    {
        $stevedoringEquipment = $this->makeStevedoringEquipmentFromRequest($requestBody)->setId($id);

        $stevedoringEquipment->validate();

        return $this->stevedoringRepository->updateEquipment($stevedoringEquipment);
    }

    public function deleteEquipment(int $id): void
    {
        $this->stevedoringRepository->deleteEquipment($id);
    }

    // ========
    // Dispatch
    // ========

    public function getDispatch(StevedoringDispatchFilterDTO $filter): StevedoringDispatchDTO
    {
        return $this->stevedoringRepository->fetchDispatch($filter);
    }

    public function getTempWorkDispatchForDate(\DateTimeImmutable $date): TempWorkDispatchForDateDTO
    {
        return $this->stevedoringRepository->fetchTempWorkDispatchForDate($date);
    }

    // ===============
    // Temp work hours
    // ===============

    /**
     * @param array<mixed> $rawData 
     * 
     * @return TempWorkHoursEntry 
     */
    public function makeTempWorkHoursEntryFromDatabase(array $rawData): TempWorkHoursEntry
    {
        $rawDataAH = new ArrayHandler($rawData);

        $tempWorkHoursEntry = new TempWorkHoursEntry();
        $tempWorkHoursEntry->id = $rawDataAH->getInt('id');
        $tempWorkHoursEntry->staff = $this->getStaff($rawDataAH->getInt('staff_id'));
        $tempWorkHoursEntry->date = $rawDataAH->getDatetime('date');
        $tempWorkHoursEntry->hoursWorked = $rawDataAH->getFloat('hours_worked', 0);
        $tempWorkHoursEntry->comments = $rawDataAH->getString('comments');

        return $tempWorkHoursEntry;
    }

    public function makeTempWorkHoursEntryFromRequest(HTTPRequestBody $requestBody): TempWorkHoursEntry
    {
        $tempWorkHoursEntry = new TempWorkHoursEntry();
        $tempWorkHoursEntry->id = $requestBody->getInt('id');
        $tempWorkHoursEntry->staff = $this->getStaff($requestBody->getInt('staffId'));
        $tempWorkHoursEntry->date = $requestBody->getDatetime('date');
        $tempWorkHoursEntry->hoursWorked = $requestBody->getFloat('hoursWorked', 0);
        $tempWorkHoursEntry->comments = $requestBody->getString('comments');

        return $tempWorkHoursEntry;
    }

    public function tempWorkHoursEntryExists(int $id): bool
    {
        return $this->stevedoringRepository->tempWorkHoursEntryExists($id);
    }

    /**
     * @return Collection<TempWorkHoursEntry>
     */
    public function getAllTempWorkHoursEntries(StevedoringTempWorkHoursFilterDTO $filter): Collection
    {
        return $this->stevedoringRepository->fetchAllTempWorkHours($filter);
    }

    public function getTempWorkHoursEntry(int $id): ?TempWorkHoursEntry
    {
        return $this->stevedoringRepository->fetchTempWorkHoursEntry($id);
    }

    public function createTempWorkHours(HTTPRequestBody $requestBody): TempWorkHoursEntry
    {
        $tempWorkHoursEntry = $this->makeTempWorkHoursEntryFromRequest($requestBody);

        $tempWorkHoursEntry->validate();

        return $this->stevedoringRepository->createTempWorkHours($tempWorkHoursEntry);
    }

    public function updateTempWorkHours(int $id, HTTPRequestBody $requestBody): TempWorkHoursEntry
    {
        $tempWorkHoursEntry = $this->makeTempWorkHoursEntryFromRequest($requestBody)->setId($id);

        $tempWorkHoursEntry->validate();

        return $this->stevedoringRepository->updateTempWorkHours($tempWorkHoursEntry);
    }

    public function deleteTempWorkHours(int $id): void
    {
        $this->stevedoringRepository->deleteTempWorkHours($id);
    }

    /**
     * Create a ZIP archive containing the temp work hours reports for the week of the given date.
     * 
     * @param \DateTimeImmutable $date 
     * 
     * @return string Filename of the ZIP archive.
     * 
     * @throws DBException 
     */
    public function getTempWorkHoursReports(\DateTimeImmutable $date): string
    {
        $diffToMonday = $date->format('N') - 1; // 'N' returns 1 for Monday, 7 for Sunday
        $monday = $date->sub(new \DateInterval("P{$diffToMonday}D"));
        $sunday = $monday->add(new \DateInterval('P6D'));

        $weekNumber = $date->format('W');
        $year = $weekNumber === "01" ? $sunday->format('Y') : $monday->format('Y');

        $reportDataDto = $this->stevedoringRepository->fetchTempWorkHoursReportData($monday, $sunday);

        $agencyInfo = (new AgencyService())->getDepartment('general');

        if (!\extension_loaded('zip')) {
            throw new ServerException(
                "Impossible de générer le fichier ZIP.",
                previous: new \Exception("Zip extension is not loaded.")
            );
        }

        $tmpFilename = tempnam(sys_get_temp_dir(), "temp-work-reports-");

        if (!$tmpFilename) {
            throw new ServerException(
                "Impossible de générer le fichier ZIP.",
                previous: new \Exception("Unable to create temporary file.")
            );
        }

        $zipFile = new \ZipArchive();

        if ($zipFile->open($tmpFilename, \ZipArchive::OVERWRITE) !== true) {
            throw new ServerException(
                "Impossible de générer le fichier ZIP.",
                previous: new \Exception("Unable to open ZIP archive.")
            );
        }

        $twig = new Twig();

        foreach ($reportDataDto->getData() as $tempWorkAgency => $agencyData) {
            $htmlReport = $twig->render('temp-work-report/report.html.twig', [
                'tempWorkAgency' => $tempWorkAgency,
                'startDate' => $monday,
                'endDate' => $sunday,
                'week' => $weekNumber,
                'year' => $year,
                'hoursData' => $agencyData,
            ]);

            $header = $twig->render('temp-work-report/header.html.twig', [
                'agency' => $agencyInfo,
                'tempWorkAgency' => $tempWorkAgency,
                'week' => $weekNumber,
                'year' => $year,
            ]);

            $footer = $twig->render('temp-work-report/footer.html.twig');

            $pdfReport = new Mpdf([
                'format' => 'A4',
                'orientation' => 'L',
                'tempDir' => sys_get_temp_dir(),
            ]);
            $pdfReport->SetHTMLHeader($header);
            $pdfReport->SetHTMLFooter($footer);
            $pdfReport->WriteHTML($htmlReport);
            /** @var string */
            $pdfAsString = $pdfReport->Output('', 'S');

            $zipFile->addFromString("{$tempWorkAgency} - {$weekNumber}-{$year}.pdf", $pdfAsString);
        }

        if ($zipFile->count() === 0) {
            \unlink($tmpFilename);
            throw new ClientException("Aucune donnée pour la semaine choisie.");
        }

        $zipFile->close();

        return $tmpFilename;
    }

    // ============
    // Ship reports
    // ============

    /**
     * @param array<mixed> $rawData 
     */
    public function makeShipReportFromDatabase(array $rawData): ShipReport
    {
        $rawDataAH = new ArrayHandler($rawData);

        $stevedoringShipReport = new ShipReport();
        $stevedoringShipReport->id = $rawDataAH->getInt('id');
        $stevedoringShipReport->isArchive = $rawDataAH->getBool('is_archive');
        $stevedoringShipReport->linkedShippingCall = new ShippingService()->getShippingCall($rawDataAH->getInt('linked_shipping_call_id'));
        $stevedoringShipReport->ship = $rawDataAH->getString('ship');
        $stevedoringShipReport->port = $rawDataAH->getString('port');
        $stevedoringShipReport->berth = $rawDataAH->getString('berth');
        $stevedoringShipReport->comments = $rawDataAH->getString('comments');
        $stevedoringShipReport->invoiceInstructions = $rawDataAH->getString('invoice_instructions');
        $stevedoringShipReport->startDate = $rawDataAH->getDatetime('start_date');
        $stevedoringShipReport->endDate = $rawDataAH->getDatetime('end_date');

        /** @phpstan-var ShipReportEquipmentEntryArray[] */
        $equipmentEntries = $rawDataAH->getArray('equipment_entries');

        $stevedoringShipReport->setEquipmentEntries(
            \array_map(
                fn(array $entry) => $this->makeShipReportEquipmentEntryFromDatabase($entry),
                $equipmentEntries
            )
        );

        /** @phpstan-var ShipReportStaffEntryArray[] */
        $staffEntries = $rawDataAH->getArray('staff_entries');

        $stevedoringShipReport->setStaffEntries(
            \array_map(
                fn(array $entry) => $this->makeShipReportStaffEntryFromDatabase($entry),
                $staffEntries
            )
        );

        /** @phpstan-var ShipReportSubcontractEntryArray[] */
        $subcontractEntries = $rawDataAH->getArray('subcontract_entries');

        $stevedoringShipReport->setSubcontractEntries(
            \array_map(
                fn(array $entry) => $this->makeShipReportSubcontractEntryFromDatabase($entry),
                $subcontractEntries
            )
        );

        return $stevedoringShipReport;
    }

    public function makeShipReportFromRequest(HTTPRequestBody $request): ShipReport
    {
        $stevedoringShipReport = new ShipReport();
        $stevedoringShipReport->id = $request->getInt('id');
        $stevedoringShipReport->isArchive = $request->getBool('isArchive');
        $stevedoringShipReport->ship = $request->getString('ship');
        $stevedoringShipReport->port = $request->getString('port');
        $stevedoringShipReport->berth = $request->getString('berth');
        $stevedoringShipReport->comments = $request->getString('comments');
        $stevedoringShipReport->invoiceInstructions = $request->getString('invoiceInstructions');

        $linkedShippingCallId = $request->getInt('linkedShippingCallId');

        if ($linkedShippingCallId !== null) {
            $stevedoringShipReport->linkedShippingCall = new ShippingService()->getShippingCall($linkedShippingCallId);

            if (!$stevedoringShipReport->linkedShippingCall) {
                throw new BadRequestException("L'escale consignation {$linkedShippingCallId} n'existe pas.");
            }
        }

        $entriesByDate = $request->getArray('entriesByDate');

        /**
         * @var string $date
         * @phpstan-var array{
         *                 cranes: ShipReportEquipmentEntryArray[],
         *                 equipments: ShipReportEquipmentEntryArray[],
         *                 permanentStaff: ShipReportStaffEntryArray[],
         *                 tempStaff: ShipReportStaffEntryArray[],
         *                 trucking: ShipReportSubcontractEntryArray[],
         *                 otherSubcontracts: ShipReportSubcontractEntryArray[],
         *               } $entriesByType
         */
        foreach ($entriesByDate as $date => $entriesByType) {
            foreach ($entriesByType as $type => $entries) {
                foreach ($entries as $entryAsArray) {
                    if ($type === 'equipments' || $type === 'cranes') {
                        /** @phpstan-var ShipReportEquipmentEntryArray $entryAsArray */
                        $entry = $this->makeShipReportEquipmentEntryFromRequest($entryAsArray);
                        $entry->report = $stevedoringShipReport;
                        $entry->date = $date;
                        $stevedoringShipReport->equipmentEntries->add($entry);
                    } elseif ($type === 'permanentStaff' || $type === 'tempStaff') {
                        /** @phpstan-var ShipReportStaffEntryArray $entryAsArray */
                        $entry = $this->makeShipReportStaffEntryFromRequest($entryAsArray);
                        $entry->report = $stevedoringShipReport;
                        $entry->date = $date;
                        $stevedoringShipReport->staffEntries->add($entry);
                    } elseif ($type === 'trucking' || $type === 'otherSubcontracts') {
                        /** @phpstan-var ShipReportSubcontractEntryArray $entryAsArray */
                        $entry = $this->makeShipReportSubcontractEntryFromRequest($entryAsArray);
                        $entry->report = $stevedoringShipReport;
                        $entry->date = $date;
                        $entry->type = $type;
                        $stevedoringShipReport->subcontractEntries->add($entry);
                    }
                }
            }
        }

        /** @phpstan-var ShippingCallCargoArray[] */
        $cargoEntries = $request->getArray('cargoEntries');

        $stevedoringShipReport->setCargoEntries(
            \array_map(
                fn(array $entry) => new ShippingService()->makeShippingCallCargoFromRequest($entry),
                $cargoEntries
            )
        );

        /** @phpstan-var ShipReportStorageEntryArray[] */
        $storageEntries = $request->getArray('storageEntries');

        $stevedoringShipReport->setStorageEntries(
            \array_map(
                fn(array $entry) => $this->makeShipReportStorageEntryFromRequest($entry),
                $storageEntries
            )
        );

        return $stevedoringShipReport;
    }

    public function shipReportExists(int $id): bool
    {
        return $this->stevedoringRepository->shipReportExists($id);
    }

    /**
     * @return Collection<ShipReport>
     */
    public function getAllShipReports(StevedoringReportsFilterDTO $filter): Collection
    {
        return $this->stevedoringRepository->fetchAllShipReports($filter);
    }

    public function getShipReport(?int $id): ?ShipReport
    {
        if ($id === null) {
            return null;
        }

        return $this->stevedoringRepository->fetchShipReport($id);
    }

    public function createShipReport(HTTPRequestBody $requestBody): ShipReport
    {
        $stevedoringShipReport = $this->makeShipReportFromRequest($requestBody);

        $stevedoringShipReport->validate();

        return $this->stevedoringRepository->createShipReport($stevedoringShipReport);
    }

    public function updateShipReport(int $id, HTTPRequestBody $requestBody): ShipReport
    {
        $stevedoringShipReport = $this->makeShipReportFromRequest($requestBody);
        $stevedoringShipReport->id = $id;

        $stevedoringShipReport->validate();

        return $this->stevedoringRepository->updateShipReport($stevedoringShipReport);
    }

    public function deleteShipReport(int $id): void
    {
        $this->stevedoringRepository->deleteShipReport($id);
    }

    // =============================
    // Ship report equipment entries
    // =============================

    /**
     * @param array<mixed> $rawData 
     */
    public function makeShipReportEquipmentEntryFromDatabase(array $rawData): ShipReportEquipmentEntry
    {
        $rawDataAH = new ArrayHandler($rawData);

        $entry = new ShipReportEquipmentEntry();
        $entry->id = $rawDataAH->getInt('id');
        $entry->equipment = $this->getEquipment($rawDataAH->getInt('equipment_id'));
        $entry->date = $rawDataAH->getDatetime('date');
        $entry->hoursWorked = $rawDataAH->getFloat('hours_worked', 0);
        $entry->comments = $rawDataAH->getString('comments');

        return $entry;
    }

    /**
     * @phpstan-param ShipReportEquipmentEntryArray $rawData
     */
    public function makeShipReportEquipmentEntryFromRequest(array $rawData): ShipReportEquipmentEntry
    {
        $rawDataAH = new ArrayHandler($rawData);

        $entry = new ShipReportEquipmentEntry();
        $entry->id = $rawDataAH->getInt('id');
        $entry->equipment = $this->getEquipment($rawDataAH->getInt('equipmentId'));
        $entry->date = $rawDataAH->getDatetime('date');
        $entry->hoursWorked = $rawDataAH->getFloat('hoursWorked', 0);
        $entry->comments = $rawDataAH->getString('comments');

        return $entry;
    }

    // =========================
    // Ship report staff entries
    // =========================

    /**
     * @phpstan-param ShipReportStaffEntryArray $rawData 
     */
    public function makeShipReportStaffEntryFromDatabase(array $rawData): ShipReportStaffEntry
    {
        $rawDataAH = new ArrayHandler($rawData);

        $entry = new ShipReportStaffEntry();
        $entry->id = $rawDataAH->getInt('id');
        $entry->staff = $this->getStaff($rawDataAH->getInt('staff_id'));
        $entry->date = $rawDataAH->getDatetime('date');
        $entry->hoursWorked = $rawDataAH->getFloat('hours_worked', 0);
        $entry->comments = $rawDataAH->getString('comments');

        return $entry;
    }

    /**
     * @phpstan-param ShipReportStaffEntryArray $rawData 
     */
    public function makeShipReportStaffEntryFromRequest(array $rawData): ShipReportStaffEntry
    {
        $rawDataAH = new ArrayHandler($rawData);

        $entry = new ShipReportStaffEntry();
        $entry->id = $rawDataAH->getInt('id');
        $entry->staff = $this->getStaff($rawDataAH->getInt('staffId'));
        $entry->date = $rawDataAH->getDatetime('date');
        $entry->hoursWorked = $rawDataAH->getFloat('hoursWorked', 0);
        $entry->comments = $rawDataAH->getString('comments');

        return $entry;
    }

    // ===============================
    // Ship report subcontract entries
    // ===============================

    /**
     * @phpstan-param ShipReportSubcontractEntryArray $rawData 
     */
    public function makeShipReportSubcontractEntryFromDatabase(array $rawData): ShipReportSubcontractEntry
    {
        $rawDataAH = new ArrayHandler($rawData);

        $entry = new ShipReportSubcontractEntry();
        $entry->id = $rawDataAH->getInt('id');
        $entry->subcontractorName = $rawDataAH->getString('subcontractor_name');
        $entry->type = $rawDataAH->getString('type');
        $entry->date = $rawDataAH->getDatetime('date');
        $entry->hoursWorked = $rawDataAH->getFloat('hours_worked', 0);
        $entry->cost = $rawDataAH->getFloat('cost', 0);
        $entry->comments = $rawDataAH->getString('comments');

        return $entry;
    }

    /**
     * @phpstan-param ShipReportSubcontractEntryArray $rawData 
     */
    public function makeShipReportSubcontractEntryFromRequest(array $rawData): ShipReportSubcontractEntry
    {
        $rawDataAH = new ArrayHandler($rawData);

        $entry = new ShipReportSubcontractEntry();
        $entry->id = $rawDataAH->getInt('id');
        $entry->subcontractorName = $rawDataAH->getString('subcontractorName');
        $entry->type = $rawDataAH->getString('type');
        $entry->date = $rawDataAH->getDatetime('date');
        $entry->hoursWorked = $rawDataAH->getFloat('hoursWorked');
        $entry->cost = $rawDataAH->getFloat('cost');
        $entry->comments = $rawDataAH->getString('comments');

        return $entry;
    }

    // ===========================
    // Ship report storage entries
    // ===========================

    /**
     * @phpstan-param ShipReportStorageEntryArray $rawData 
     */
    public function makeShipReportStorageEntryFromDatabase(array $rawData): ShipReportStorageEntry
    {
        $rawDataAH = new ArrayHandler($rawData);

        $entry = new ShipReportStorageEntry();
        $entry->id = $rawDataAH->getInt('id');
        $entry->cargo = new ShippingService()->getCargoEntry($rawDataAH->getInt('cargo_id'));
        $entry->storageName = $rawDataAH->getString('storage_name');
        $entry->tonnage = $rawDataAH->getFloat('tonnage', 0);
        $entry->volume = $rawDataAH->getFloat('volume', 0);
        $entry->units = $rawDataAH->getInt('units', 0);
        $entry->comments = $rawDataAH->getString('comments');

        return $entry;
    }

    /**
     * @phpstan-param ShipReportStorageEntryArray $rawData 
     */
    public function makeShipReportStorageEntryFromRequest(array $rawData): ShipReportStorageEntry
    {
        $rawDataAH = new ArrayHandler($rawData);

        $entry = new ShipReportStorageEntry();
        $entry->id = $rawDataAH->getInt('id');
        $entry->cargo = new ShippingService()->getCargoEntry($rawDataAH->getInt('cargoId'));
        $entry->storageName = $rawDataAH->getString('storageName');
        $entry->tonnage = $rawDataAH->getFloat('tonnage', 0);
        $entry->volume = $rawDataAH->getFloat('volume', 0);
        $entry->units = $rawDataAH->getInt('units', 0);
        $entry->comments = $rawDataAH->getString('comments');

        return $entry;
    }

    // ======
    // Others
    // ======

    public function getShipReportsFilterData(): StevedoringReportsFilterDataDTO
    {
        return $this->stevedoringRepository->fetchShipReportsFilterData();
    }

    /**
     * @return Collection<CallWithoutReportDTO>
     */
    public function getCallsWithoutReport(): Collection
    {
        return $this->stevedoringRepository->fetchCallsWithoutReport();
    }

    public function getSubcontractorsData(): StevedoringSubcontractorsDataDTO
    {
        return $this->stevedoringRepository->fetchSubcontractorsData();
    }

    // ===
    // PDF
    // ===

    public function getShipReportPdf(int $id): string
    {
        $report = $this->getShipReport($id);

        if (!$report) {
            throw new NotFoundException("Le rapport navire {$id} n'existe pas.");
        }

        $days = \array_keys($report->getEntriesByDate());

        /**
         * @var array{
         *        entries: Map<StevedoringEquipment, array<string, ShipReportEquipmentEntry>>,
         *        totals: array{
         *                  byDay: array<string, array{hoursWorked: float}>,
         *                  total: array{hoursWorked: float},
         *                }
         *      }
         */
        $craneEntries = ['entries' => new Map(), 'totals' => ['byDay' => [], 'total' => ['hoursWorked' => 0.0]]];
        /**
         * @var array{
         *        entries: Map<StevedoringEquipment, array<string, ShipReportEquipmentEntry>>,
         *        totals: array{
         *                  byDay: array<string, array{hoursWorked: float}>,
         *                  total: array{hoursWorked: float},
         *                }
         *      }
         */
        $equipmentEntries = ['entries' => new Map(), 'totals' => ['byDay' => [], 'total' => ['hoursWorked' => 0.0]]];
        foreach ($report->equipmentEntries as $entry) {
            if (!$entry->equipment || !$entry->date) continue;
            $entry->equipment->isCrane
                ? $map = &$craneEntries
                : $map = &$equipmentEntries;
            $map['entries'][$entry->equipment] ??= [];
            $date = $entry->date->format('Y-m-d');
            $map['entries'][$entry->equipment][$date] = $entry;
            $map['totals']['byDay'][$date] ??= ['hoursWorked' => 0];
            $map['totals']['byDay'][$date]['hoursWorked'] += $entry->hoursWorked;
            $map['totals']['total']['hoursWorked'] += $entry->hoursWorked;
            unset($map);
        }

        /**
         * @var array{
         *        entries: Map<StevedoringStaff, array<string, ShipReportStaffEntry>>,
         *        totals: array{
         *                  byDay: array<string, array{hoursWorked: float}>,
         *                  total: array{hoursWorked: float},
         *                }
         *      }
         */
        $permanentStaffEntries = ['entries' => new Map(), 'totals' => ['byDay' => [], 'total' => ['hoursWorked' => 0.0]]];
        /**
         * @var array{
         *        entries: Map<StevedoringStaff, array<string, ShipReportStaffEntry>>,
         *        totals: array{
         *                  byDay: array<string, array{hoursWorked: float}>,
         *                  total: array{hoursWorked: float},
         *                }
         *      }
         */
        $tempStaffEntries = ['entries' => new Map(), 'totals' => ['byDay' => [], 'total' => ['hoursWorked' => 0.0]]];
        foreach ($report->staffEntries as $entry) {
            if (!$entry->staff || !$entry->date) continue;
            $entry->staff->type === 'mensuel'
                ? $map = &$permanentStaffEntries
                : $map =  &$tempStaffEntries;
            $map['entries'][$entry->staff] ??= [];
            $date = $entry->date->format('Y-m-d');
            $map['entries'][$entry->staff][$date] = $entry;
            $map['totals']['byDay'][$date] ??= ['hoursWorked' => 0];
            $map['totals']['byDay'][$date]['hoursWorked'] += $entry->hoursWorked;
            $map['totals']['total']['hoursWorked'] += $entry->hoursWorked;
            unset($map);
        }

        /**
         * @var array{
         *        entries: Map<string, array<string, ShipReportSubcontractEntry>>,
         *        totals: array{
         *                  byDay: array<string, array{hoursWorked: float, cost: float}>,
         *                  total: array{hoursWorked: float, cost: float},
         *                }
         *      }
         */
        $truckingEntries = ['entries' => new Map(), 'totals' => ['byDay' => [], 'total' => ['hoursWorked' => 0.0, 'cost' => 0.0]]];
        /**
         * @var array{
         *        entries: Map<string, array<string, ShipReportSubcontractEntry>>,
         *        totals: array{
         *                  byDay: array<string, array{hoursWorked: float, cost: float}>,
         *                  total: array{hoursWorked: float, cost: float},
         *                }
         *      }
         */
        $otherSubcontractsEntries = ['entries' => new Map(), 'totals' => ['byDay' => [], 'total' => ['hoursWorked' => 0.0, 'cost' => 0.0]]];
        foreach ($report->subcontractEntries as $entry) {
            if (!$entry->date) continue;
            $entry->type === 'trucking'
                ? $map = &$truckingEntries
                : $map = &$otherSubcontractsEntries;
            $map['entries'][$entry->subcontractorName] ??= [];
            $date = $entry->date->format('Y-m-d');
            $map['entries'][$entry->subcontractorName][$date] = $entry;
            $map['totals']['byDay'][$date] ??= ['hoursWorked' => 0, 'cost' => 0];
            $map['totals']['byDay'][$date]['hoursWorked'] += $entry->hoursWorked;
            $map['totals']['byDay'][$date]['cost'] += $entry->cost;
            $map['totals']['total']['hoursWorked'] += $entry->hoursWorked;
            $map['totals']['total']['cost'] += $entry->cost;
            unset($map);
        }

        $html = new Twig()->render('ship-report/ship-report.html.twig', [
            'shipName' => $report->ship,
            'customers' => \join(', ', $report->getCustomers()),
            'cargoNames' => \join(', ', $report->getCargoNames()),
            'comments' => $report->comments,
            'invoiceInstructions' => $report->invoiceInstructions,
            'port' => $report->port,
            'berth' => $report->berth,
            'cargoEntries' => $report->cargoEntries,
            'cargoTotals' => $report->calculateCargoTotals(),
            'storageEntries' => $report->storageEntries,
            'storageTotals' => $report->calculateStorageTotals(),
            'days' => $days,
            'craneEntries' => $craneEntries,
            'equipmentEntries' => $equipmentEntries,
            'permanentStaffEntries' => $permanentStaffEntries,
            'tempStaffEntries' => $tempStaffEntries,
            'truckingEntries' => $truckingEntries,
            'otherSubcontractsEntries' => $otherSubcontractsEntries,
        ]);

        $pdfReport = new Mpdf([
            'format' => 'A4',
            'orientation' => 'P',
            'tempDir' => sys_get_temp_dir(),
        ]);
        $pdfReport->SetTitle("Rapport navire - " . $report->ship);
        // @phpstan-ignore-next-line
        $pdfReport->imageVars['logo'] = \file_get_contents(API . '/images/logo_agence_combi_moyen.png');
        $pdfReport->WriteHTML($html);
        /** @var string */
        $pdfAsString = $pdfReport->Output('', 'S');

        // return $html;
        return $pdfAsString;
    }
}
