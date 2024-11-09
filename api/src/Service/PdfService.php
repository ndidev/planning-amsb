<?php

// Path: api/src/Service/PdfService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\Component\Module;
use App\Core\Component\PdfEmail;
use App\Core\Exceptions\AppException;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\HTTPRequestBody;
use App\Core\Logger\ErrorLogger;
use App\DTO\PDF\BulkPDF;
use App\DTO\PDF\PlanningPDF;
use App\DTO\PDF\TimberPDF;
use App\Entity\Config\AgencyDepartment;
use App\Entity\Config\PdfConfig;
use App\Entity\ThirdParty;
use App\Repository\PdfConfigRepository;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * @phpstan-import-type PdfConfigArray from \App\Repository\PdfConfigRepository
 */
final class PdfService
{
    private PdfConfigRepository $pdfConfigRepository;

    public function __construct()
    {
        $this->pdfConfigRepository = new PdfConfigRepository($this);
    }

    /**
     * Creates a PDF configuration from database data.
     * 
     * @param array $rawData Raw data from the database.
     * 
     * @phpstan-param PdfConfigArray $rawData
     * 
     * @return PdfConfig 
     */
    public function makeConfigFromDatabase(array $rawData): PdfConfig
    {
        $thirdPartyService = new ThirdPartyService();

        $config = (new PdfConfig())
            ->setId($rawData['id'] ?? null)
            ->setModule($rawData['module'] ?? null)
            ->setSupplier($thirdPartyService->getThirdParty($rawData['fournisseur'] ?? null))
            ->setAutoSend($rawData['envoi_auto'] ?? false)
            ->setEmails($rawData['liste_emails'] ?? [])
            ->setDaysBefore($rawData['jours_avant'] ?? 0)
            ->setDaysAfter($rawData['jours_apres'] ?? 0);

        return $config;
    }

    /**
     * Creates a PDF configuration from form data.
     * 
     * @param HTTPRequestBody $requestBody 
     * 
     * @return PdfConfig 
     */
    public function makeConfigFromForm(HTTPRequestBody $requestBody): PdfConfig
    {
        $thirdPartyService = new ThirdPartyService();

        $config = (new PdfConfig())
            ->setModule($requestBody->getString('module'))
            ->setSupplier($thirdPartyService->getThirdParty($requestBody->getInt('fournisseur')))
            ->setAutoSend($requestBody->getBool('envoi_auto'))
            ->setEmails($requestBody->getArray('liste_emails'))
            ->setDaysBefore($requestBody->getInt('jours_avant', 0))
            ->setDaysAfter($requestBody->getInt('jours_apres', 0));

        return $config;
    }

    public function configExists(int $id): bool
    {
        return $this->pdfConfigRepository->configExists($id);
    }

    /**
     * Récupère toutes les configurations PDF.
     * 
     * @return Collection<PdfConfig> Configurations PDF.
     */
    public function getAllConfigs(): Collection
    {
        return $this->pdfConfigRepository->fetchAllConfigs();
    }

    public function getConfig(int $id): ?PdfConfig
    {
        return $this->pdfConfigRepository->fetchConfig($id);
    }

    /**
     * Updates a PDF configuration.
     * 
     * @param int             $id      
     * @param HTTPRequestBody $rawData 
     *  
     * @return PdfConfig 
     */
    public function updateConfig(int $id, HTTPRequestBody $rawData): PdfConfig
    {
        $config = $this->makeConfigFromForm($rawData)->setId($id);

        return $this->pdfConfigRepository->updateConfig($config);
    }

    /**
     * Creates a PDF configuration.
     * 
     * @param HTTPRequestBody $rawData 
     * 
     * @return PdfConfig 
     */
    public function createConfig(HTTPRequestBody $rawData): PdfConfig
    {
        $config = $this->makeConfigFromForm($rawData);

        return $this->pdfConfigRepository->createConfig($config);
    }

    public function deleteConfig(int $id): void
    {
        $this->pdfConfigRepository->deleteConfig($id);
    }

    /**
     * Génère un PDF client.
     * 
     * @param int                $configId  Identifiant de la configuration PDF.
     * @param \DateTimeInterface $startDate Date de début des RDV.
     * @param \DateTimeInterface $endDate   Date de fin des RDV.
     * 
     * @return PlanningPDF
     */
    public function generatePDF(
        ?int $configId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): PlanningPDF {
        if (!$configId) {
            throw new ClientException("Identifiant de configuration PDF manquant");
        }

        $config = $this->getConfig($configId);

        if (!$config) {
            throw new NotFoundException("Configuration PDF non trouvée");
        }

        $supplier = $config->getSupplier();

        if (!$supplier) {
            throw new NotFoundException("Fournisseur non trouvé");
        }

        // Récupération données du service de l'agence.
        $agencyInfo = (new AgencyService())->getDepartment("transit");

        if (!$agencyInfo) {
            throw new ServerException("Impossible de récupérer les informations de l'agence");
        }

        $module = $config->getModule();

        return match ($module) {
            Module::BULK => $this->generateBulkPdf($supplier, $startDate, $endDate, $agencyInfo),
            Module::TIMBER => $this->generateTimberPdf($supplier, $startDate, $endDate, $agencyInfo),
            default => throw new ServerException("Le module spécifié n'est pas pris en charge"),
        };
    }

    private function generateBulkPdf(
        ThirdParty $supplier,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        AgencyDepartment $agencyInfo,
    ): BulkPDF {
        $bulkService = new BulkService();

        $appointments = $bulkService->getPdfAppointments($supplier, $startDate, $endDate);

        return new BulkPDF($supplier, $appointments, $startDate, $endDate, $agencyInfo);
    }

    private function generateTimberPdf(
        ThirdParty $supplier,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        AgencyDepartment $agencyInfo,
    ): TimberPDF {
        $timberService = new TimberService();

        $appointments = $timberService->getPdfAppointments($supplier, $startDate, $endDate);

        return new TimberPDF($supplier, $appointments, $startDate, $endDate, $agencyInfo);
    }

    /**
     * Envoie un PDF par e-mail.
     * 
     * @param int                $configId  Identifiant de la configuration PDF.
     * @param \DateTimeInterface $startDate Date de début des RDV.
     * @param \DateTimeInterface $endDate   Date de fin des RDV.
     * 
     * @return array{
     *           module: Module::*,
     *           fournisseur: int,
     *           adresses: array{
     *                       from:string,
     *                       to:string[],
     *                       cc:string[],
     *                       bcc:string[]
     *                     },
     *         } Résultat de l'envoi.
     */
    public function sendPdfByEmail(
        int $configId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $result = [
            "module" => null,
            "fournisseur" => null,
            "adresses" => null,
        ];

        try {
            $config = $this->getConfig($configId);

            if (!$config) {
                throw new NotFoundException("Configuration PDF non trouvée");
            }

            $supplierId = $config->getSupplier()?->getId();

            if (!$supplierId) {
                throw new NotFoundException("Fournisseur non trouvé");
            }

            // Récupération données du service de l'agence.
            $agencyInfo = (new AgencyService())->getDepartment("transit");

            if (!$agencyInfo) {
                throw new ServerException("Impossible de récupérer les informations de l'agence");
            }

            $supportedModules = [Module::BULK, Module::TIMBER];

            $module = $config->getModule();

            if (!in_array($module, $supportedModules)) {
                throw new ServerException("Le module spécifié n'est pas pris en charge");
            }

            /** @phpstan-var Module::* $module */

            $pdf = $this->generatePDF($configId, $startDate, $endDate);

            // Création e-mail
            $mail = new PdfEmail(
                $pdf,
                $startDate,
                $endDate,
                $agencyInfo
            );

            // Adresses
            $mail->addAddresses(to: $config->getEmails());

            $mail->send();
            $mail->smtpClose();

            $result["module"] = $module;
            $result["fournisseur"] = $supplierId;
            $result["adresses"] = $mail->getAllAddresses();

            return $result;
        } catch (PHPMailerException $e) {
            throw new ServerException("Erreur d'envoi", previous: $e);
        }
    }
}
