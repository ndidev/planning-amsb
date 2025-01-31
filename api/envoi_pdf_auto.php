<?php

// Path: api/envoi_pdf_auto.php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Component\DateUtils;
use App\Core\Exceptions\Server\ServerException;
use App\Core\Logger\ErrorLogger;
use App\Service\PdfConfigService;

const TODAY = new \DateTime();

// Affichage de la date en première ligne du rapport
echo PHP_EOL . DateUtils::format(DateUtils::DATETIME_FULL, TODAY) . PHP_EOL;

// Si la page est appelée automatiquement un jour non ouvré, l'envoi de mail n'a pas lieu
if (empty($_POST) && !DateUtils::isWorkingDay(TODAY)) {
    echo "Jour non ouvré, pas d'envoi" . PHP_EOL . PHP_EOL;
    return false;
}

$pdfService = new PdfConfigService();

// Récupération des configurations PDF
$configs = $pdfService->getAllConfigs();

/**
 * Pour chaque config :  
 * - vérification de l'envoi auto actif
 * - envoi du PDF par e-mail
 */
foreach ($configs as $config) {
    // Si l'envoi_auto n'est pas activé pour le client, l'envoi n'a pas lieu
    if (!$config->isAutoSend()) {
        continue;
    }

    /**
     * Rapport d'exécution du script
     * (enregistré dans `/home/webmaster/logs/envoi_pdf.log`)
     * 
     * @var string
     */
    $rapport = "";

    try {
        /**
         * Calcul des dates si la page est appelée automatiquement
         * (ex : si elle est appelée par CronJob)
         */
        $startDate = DateUtils::getPreviousWorkingDay(TODAY, $config->getDaysBefore());
        $endDate = DateUtils::getNextWorkingDay(TODAY, $config->getDaysAfter());
        $formattedStartDate = DateUtils::format(DateUtils::DATE_FULL, $startDate);
        $formattedEndDate = DateUtils::format(DateUtils::DATE_FULL, $endDate);

        $configId = $config->id;

        if (!$configId) {
            throw new ServerException("Erreur : l'identifiant de la configuration n'a pas été trouvé");
        }

        // Envoi du PDF
        $resultat = $pdfService->sendPdfByEmail(
            $configId,
            $startDate,
            $endDate
        );

        // Mise à jour du rapport
        $rapport .= "• {$config->getModule()}/{$config->getSupplier()?->id} ({$config->getSupplier()?->getShortName()}) : succès" . PHP_EOL;
        $rapport .= "  Dates : du {$formattedStartDate} au {$formattedEndDate}" . PHP_EOL;
        $rapport .= "  Adresses : " . PHP_EOL;
        $rapport .= "    From : " . $resultat["adresses"]["from"] . PHP_EOL;
        $rapport .= "    To : " . PHP_EOL;
        foreach ($resultat["adresses"]["to"] as $address) {
            $rapport .= "      $address" . PHP_EOL;
        }
        $rapport .= "    Cc : " . PHP_EOL;
        foreach ($resultat["adresses"]["cc"] as $address) {
            $rapport .= "      $address" . PHP_EOL;
        }
        $rapport .= "    Bcc : " . PHP_EOL;
        foreach ($resultat["adresses"]["bcc"] as $address) {
            $rapport .= "      $address" . PHP_EOL;
        }
    } catch (\Exception $e) {
        $rapport .= "• {$config->getModule()}/{$config->getSupplier()?->id} ({$config->getSupplier()?->getShortName()}) : échec" . PHP_EOL;
        $rapport .= "  Erreur : {$e->getMessage()}" . PHP_EOL;
        ErrorLogger::log($e);
    } finally {
        // Affichage du rapport
        echo $rapport;
    }
}
