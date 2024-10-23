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
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class PdfService
{
    private PdfConfigRepository $pdfConfigRepository;

    public function __construct()
    {
        $this->pdfConfigRepository = new PdfConfigRepository();
    }

    public function makeConfigFromDatabase(array $rawData): PdfConfig
    {
        $config = (new PdfConfig())
            ->setId($rawData['id'] ?? null)
            ->setModule($rawData['module'] ?? null)
            ->setSupplier($rawData['fournisseur'] ?? null)
            ->setAutoSend($rawData['envoi_auto'] ?? false)
            ->setEmails($rawData['liste_emails'] ?? [])
            ->setDaysBefore($rawData['jours_avant'] ?? 0)
            ->setDaysAfter($rawData['jours_apres'] ?? 0);

        return $config;
    }

    public function makeConfigFromForm(array $rawData): PdfConfig
    {
        $config = (new PdfConfig())
            ->setModule($rawData['module'] ?? null)
            ->setSupplier($rawData['fournisseur'] ?? null)
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

    public function updateConfig(int $id, array $rawData): PdfConfig
    {
        $config = $this->makeConfigFromForm($rawData)->setId($id);

        return $this->pdfConfigRepository->updateConfig($config);
    }

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
     * @return array Résultat de l'envoi.
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

        /**
         * @var array $resultat Résultat de l'envoi.
         */
        $resultat = [
            "module" => $config->getModule(),
            "fournisseur" => $config->getSupplier()->getId(),
            "statut" => null,
            "message" => null,
            "adresses" => null,
            "erreur" => null
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
            $resultat["statut"] = "echec";
            $resultat["message"] = "Erreur : " . mb_convert_encoding($mail->ErrorInfo, 'UTF-8');
            ErrorLogger::log($e);
        } catch (\Exception $e) {
            $resultat["statut"] = "echec";
            $resultat["message"] = "Erreur : " . $e->getMessage();
            ErrorLogger::log($e);
        } finally {
            unset($mail);

            return $resultat;
        }
    }
}
