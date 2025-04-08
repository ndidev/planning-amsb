<?php

// Path: api/src/Schedule/Tasks/SendCustomerPlanningByEmail.php

declare(strict_types=1);

namespace App\Scheduler\Tasks;

use App\Core\Component\DateUtils;
use App\Core\Exceptions\Server\ServerException;
use App\Core\Logger\ErrorLogger;
use App\Entity\Config\PdfConfig;
use App\Scheduler\Task;
use App\Service\PdfConfigService;

final class SendCustomerPlanningByEmail extends Task
{

    private PdfConfigService $pdfService;

    private string $report = '';

    private readonly \DateTimeImmutable $TODAY;

    public function __construct(string $name = 'send_customer_planning_by_email')
    {
        parent::__construct($name);

        $this->pdfService = new PdfConfigService();

        $this->TODAY = new \DateTimeImmutable();
    }

    public function execute(): void
    {

        // Affichage de la date en première ligne du rapport
        $this->report = PHP_EOL . DateUtils::format(DateUtils::DATETIME_FULL, $this->TODAY) . PHP_EOL;

        // Récupération des configurations PDF
        $configs = $this->pdfService->getAllConfigs();

        /**
         * Pour chaque config :  
         * - vérification de l'envoi auto actif
         * - envoi du PDF par e-mail
         */
        foreach ($configs as $config) {
            $this->handleConfig($config);
        }

        $this->logOutput = $this->report;
    }

    private function handleConfig(PdfConfig $config): void
    {
        // Si l'envoi_auto n'est pas activé pour le client, l'envoi n'a pas lieu
        if (false === $config->autoSend) {
            return;
        }

        try {
            /**
             * Calcul des dates si la page est appelée automatiquement
             * (ex : si elle est appelée par CronJob)
             */
            $startDate = DateUtils::getPreviousWorkingDay($this->TODAY, $config->daysBefore);
            $endDate = DateUtils::getNextWorkingDay($this->TODAY, $config->daysAfter);
            $formattedStartDate = DateUtils::format(DateUtils::DATE_FULL, $startDate);
            $formattedEndDate = DateUtils::format(DateUtils::DATE_FULL, $endDate);

            $configId = $config->id;

            if (!$configId) {
                throw new ServerException("Erreur : l'identifiant de la configuration n'a pas été trouvé");
            }

            // Envoi du PDF
            $sendingResult = $this->pdfService->sendPdfByEmail(
                $configId,
                $startDate,
                $endDate
            );

            // Mise à jour du rapport
            $this->report .= "• {$config->module}/{$config->supplier?->id} ({$config->supplier?->shortName}) : succès" . PHP_EOL;
            $this->report .= "  Dates : du {$formattedStartDate} au {$formattedEndDate}" . PHP_EOL;
            $this->report .= "  Adresses : " . PHP_EOL;
            $this->report .= "    From : " . $sendingResult["adresses"]["from"] . PHP_EOL;
            $this->report .= "    To : " . PHP_EOL;
            foreach ($sendingResult["adresses"]["to"] as $address) {
                $this->report .= "      $address" . PHP_EOL;
            }
            $this->report .= "    Cc : " . PHP_EOL;
            foreach ($sendingResult["adresses"]["cc"] as $address) {
                $this->report .= "      $address" . PHP_EOL;
            }
            $this->report .= "    Bcc : " . PHP_EOL;
            foreach ($sendingResult["adresses"]["bcc"] as $address) {
                $this->report .= "      $address" . PHP_EOL;
            }
        } catch (\Exception $e) {
            $this->report .= "• {$config->module}/{$config->supplier?->id} ({$config->supplier?->shortName}) : échec" . PHP_EOL;
            $this->report .= "  Erreur : {$e->getMessage()}" . PHP_EOL;
            ErrorLogger::log($e);
        }
    }
}
