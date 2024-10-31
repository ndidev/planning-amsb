<?php

// Path: api/src/Service/PdfService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\Component\Module;
use App\Core\Component\PdfEmail;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\Logger\ErrorLogger;
use App\DTO\PDF\BulkPDF;
use App\DTO\PDF\PlanningPDF;
use App\DTO\PDF\TimberPDF;
use App\Entity\Config\AgencyDepartment;
use App\Entity\Config\PdfConfig;
use App\Entity\ThirdParty;
use App\Repository\BulkAppointmentRepository;
use App\Repository\PdfConfigRepository;
use App\Repository\TimberAppointmentRepository;
use PDOException;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * @phpstan-type PdfConfigArray array{
 *                                id?: int,
 *                                module?: string,
 *                                fournisseur?: int,
 *                                envoi_auto?: bool|int,
 *                                liste_emails?: string|string[],
 *                                jours_avant?: int,
 *                                jours_apres?: int,
 *                              }
 */
final class PdfService
{
    private PdfConfigRepository $pdfConfigRepository;

    public function __construct()
    {
        $this->pdfConfigRepository = new PdfConfigRepository();
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
     * @param array $rawData 
     * 
     * @phpstan-param PdfConfigArray $rawData
     * 
     * @return PdfConfig 
     */
    public function makeConfigFromForm(array $rawData): PdfConfig
    {
        $thirdPartyService = new ThirdPartyService();

        $config = (new PdfConfig())
            ->setModule($rawData['module'] ?? null)
            ->setSupplier($thirdPartyService->getThirdParty($rawData['fournisseur'] ?? null))
            ->setAutoSend($rawData['envoi_auto'] ?? false)
            ->setEmails($rawData['liste_emails'] ?? [])
            ->setDaysBefore($rawData['jours_avant'] ?? 0)
            ->setDaysAfter($rawData['jours_apres'] ?? 0);

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
     * @param int   $id      
     * @param array $rawData 
     *
     * @phpstan-param PdfConfigArray $rawData
     *  
     * @return PdfConfig 
     */
    public function updateConfig(int $id, array $rawData): PdfConfig
    {
        $config = $this->makeConfigFromForm($rawData)->setId($id);

        return $this->pdfConfigRepository->updateConfig($config);
    }

    /**
     * Creates a PDF configuration.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param PdfConfigArray $rawData
     * 
     * @return PdfConfig 
     */
    public function createConfig(array $rawData): PdfConfig
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

        $module = $config->getModule();

        // Récupération données du service de l'agence.
        $agencyInfo = (new AgencyService())->getDepartment("transit");

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
        $bulkRepository = new BulkAppointmentRepository();

        $appointments = $bulkRepository->getPdfAppointments($supplier, $startDate, $endDate);

        return new BulkPDF($supplier, $appointments, $startDate, $endDate, $agencyInfo);
    }

    private function generateTimberPdf(
        ThirdParty $supplier,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        AgencyDepartment $agencyInfo,
    ): TimberPDF {
        $timberRepository = new TimberAppointmentRepository();

        $appointments = $timberRepository->getPdfAppointments($supplier, $startDate, $endDate);

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
     *           module: string,
     *           fournisseur: int,
     *           statut: 'succes'|'echec',
     *           message: string|null,
     *           adresses: array{
     *                       from:string,
     *                       to:string[],
     *                       cc:string[],
     *                       bcc:string[]
     *                     }|null,
     *         } Résultat de l'envoi.
     */
    public function sendPdfByEmail(
        int $configId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $config = $this->getConfig($configId);

        if (!$config) {
            throw new NotFoundException("Configuration PDF non trouvée");
        }

        $pdf = $this->generatePDF($configId, $startDate, $endDate);

        // Récupération données du service de l'agence.
        $agencyInfo = (new AgencyService())->getDepartment("transit");

        $resultat = [
            "module" => $config->getModule(),
            "fournisseur" => $config->getSupplier()->getId(),
            "statut" => "echec",
            "message" => null,
            "adresses" => null,
        ];

        try {
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

            $resultat["statut"] = "succes";
            $resultat["message"] = "Le PDF a été envoyé avec succès.";
            $resultat["adresses"] = $mail->getAllAddresses();
        } catch (PHPMailerException $e) {
            $resultat["message"] = "Erreur : " . mb_convert_encoding($mail->ErrorInfo, 'UTF-8');
            ErrorLogger::log($e);
        } catch (\Exception $e) {
            $resultat["message"] = "Erreur : " . $e->getMessage();
            ErrorLogger::log($e);
        } finally {
            unset($mail);

            return $resultat;
        }
    }
}
