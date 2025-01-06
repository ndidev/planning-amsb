<?php

// Path: api/src/Service/StevedoringService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\HTTPRequestBody;
use App\DTO\Filter\StevedoringDispatchFilterDTO;
use App\DTO\Filter\StevedoringStaffFilterDTO;
use App\DTO\Filter\StevedoringTempWorkHoursFilterDTO;
use App\DTO\StevedoringDispatchDTO;
use App\Entity\Stevedoring\StevedoringEquipment;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Entity\Stevedoring\TempWorkHoursEntry;
use App\Repository\StevedoringRepository;
use Mpdf\Mpdf;

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

        $stevedoringStaff = (new StevedoringStaff())
            ->setId($rawDataAH->getInt('id'))
            ->setFirstname($rawDataAH->getString('firstname'))
            ->setLastname($rawDataAH->getString('lastname'))
            ->setPhone($rawDataAH->getString('phone'))
            ->setType($rawDataAH->getString('type'))
            ->setTempWorkAgency($rawDataAH->getString('temp_work_agency', null))
            ->setActive($rawDataAH->getBool('is_active'))
            ->setComments($rawDataAH->getString('comments'))
            ->setDeletedAt($rawDataAH->getDatetime('deleted_at'));

        return $stevedoringStaff;
    }

    public function makeStevedoringStaffFromRequest(HTTPRequestBody $requestBody): StevedoringStaff
    {
        $stevedoringStaff = (new StevedoringStaff())
            ->setId($requestBody->getInt('id'))
            ->setFirstname($requestBody->getString('firstname'))
            ->setLastname($requestBody->getString('lastname'))
            ->setPhone($requestBody->getString('phone'))
            ->setType($requestBody->getString('type'))
            ->setTempWorkAgency($requestBody->getString('tempWorkAgency', null))
            ->setActive($requestBody->getBool('isActive'))
            ->setComments($requestBody->getString('comments'));

        if ($stevedoringStaff->getType() === "cdi") {
            $stevedoringStaff->setTempWorkAgency(null);
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
        $stevedoringStaff = $this->makeStevedoringStaffFromRequest($requestBody)->setId($id);

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

        $stevedoringEquipment = (new StevedoringEquipment())
            ->setId($rawDataAH->getInt('id'))
            ->setType($rawDataAH->getString('type'))
            ->setBrand($rawDataAH->getString('brand'))
            ->setModel($rawDataAH->getString('model'))
            ->setInternalNumber($rawDataAH->getString('internal_number'))
            ->setSerialNumber($rawDataAH->getString('serial_number'))
            ->setComments($rawDataAH->getString('comments'))
            ->setActive($rawDataAH->getBool('is_active'));

        return $stevedoringEquipment;
    }

    public function makeStevedoringEquipmentFromRequest(HTTPRequestBody $requestBody): StevedoringEquipment
    {
        $stevedoringEquipment = (new StevedoringEquipment())
            ->setId($requestBody->getInt('id'))
            ->setType($requestBody->getString('type'))
            ->setBrand($requestBody->getString('brand'))
            ->setModel($requestBody->getString('model'))
            ->setInternalNumber($requestBody->getString('internalNumber'))
            ->setSerialNumber($requestBody->getString('serialNumber'))
            ->setComments($requestBody->getString('comments'))
            ->setActive($requestBody->getBool('isActive'));

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

    public function getEquipment(int $id): ?StevedoringEquipment
    {
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

    /**
     * @return array<int>
     */
    public function getTempWorkDispatchIdsForDate(\DateTimeImmutable $date): array
    {
        return $this->stevedoringRepository->fetchTempWorkDispatchIdsForDate($date);
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

        $tempWorkHoursEntry = (new TempWorkHoursEntry())
            ->setId($rawDataAH->getInt('id'))
            ->setStaff($this->getStaff($rawDataAH->getInt('staff_id')))
            ->setDate($rawDataAH->getDatetime('date'))
            ->setHoursWorked($rawDataAH->getFloat('hours_worked', 0))
            ->setComments($rawDataAH->getString('comments'));

        return $tempWorkHoursEntry;
    }

    public function makeTempWorkHoursEntryFromRequest(HTTPRequestBody $requestBody): TempWorkHoursEntry
    {
        $tempWorkHoursEntry = (new TempWorkHoursEntry())
            ->setId($requestBody->getInt('id'))
            ->setStaff($this->getStaff($requestBody->getInt('staffId')))
            ->setDate($requestBody->getDatetime('date'))
            ->setHoursWorked($requestBody->getFloat('hoursWorked', 0))
            ->setComments($requestBody->getString('comments'));

        return $tempWorkHoursEntry;
    }

    public function tempWorkHoursEntryExists(int $id): bool
    {
        return $this->stevedoringRepository->tempWorkHoursEntryExists($id);
    }

    /**
     * @return Collection<TempWorkHoursEntry>
     */
    public function getAllTempWorkHours(StevedoringTempWorkHoursFilterDTO $filter): Collection
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

        $twigLoader = new \Twig\Loader\FilesystemLoader(API . '/src/templates');
        $twig = new \Twig\Environment($twigLoader);
        $twig->addExtension(new \Twig\Extra\Intl\IntlExtension());

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
}
