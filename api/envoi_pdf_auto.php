<?php

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database\MySQL;
use App\Core\DateUtils;
use App\Core\PDF\PDFUtils;

const AUJOURDHUI = new DateTime();

// Affichage de la date en première ligne du rapport
echo PHP_EOL . DateUtils::format(DateUtils::DATETIME_FULL, AUJOURDHUI) . PHP_EOL;

// Si la page est appelée automatiquement un jour non ouvré, l'envoi de mail n'a pas lieu
if (empty($_POST) && !DateUtils::checkWorkingDay(AUJOURDHUI)) {
    echo "Jour non ouvré, pas d'envoi" . PHP_EOL . PHP_EOL;
    return FALSE;
}

$mysql = new MySQL();

// Récupération des configurations PDF
$configs = $mysql->query("SELECT * FROM config_pdf")->fetchAll();

/**
 * Pour chaque config :  
 * - vérification de l'envoi auto actif
 * - envoi du PDF par e-mail
 */
foreach ($configs as $config) {
    /**
     * @var string $module
     * @var string $fournisseur
     * @var string $envoi_auto
     * @var string $liste_emails
     * @var string $jours_avant
     * @var string $jours_apres
     */
    extract($config);

    // Si l'envoi_auto n'est pas activé pour le client, l'envoi n'a pas lieu
    if (!(bool) $envoi_auto) {
        continue;
    }

    /**
     * Rapport d'exécution du script
     * (enregistré dans `/home/webmaster/logs/envoi_pdf.log`)
     * 
     * @var string
     */
    $rapport = "";

    /**
     * Calcul des dates si la page est appelée automatiquement
     * (ex : si elle est appelée par CronJob)
     */
    $date_debut = DateUtils::previousWorkingDay(AUJOURDHUI, (int) $jours_avant);
    $date_fin = DateUtils::nextWorkingDay(AUJOURDHUI, (int) $jours_apres);

    // Génération du PDF
    $pdf = PDFUtils::generatePDF(
        $module,
        (int) $fournisseur,
        $date_debut,
        $date_fin
    );

    // Envoi du PDF
    $resultat = PDFUtils::sendPDF(
        $pdf,
        $module,
        (int) $fournisseur,
        $liste_emails,
        $date_debut,
        $date_fin
    );

    // Mise à jour du rapport
    $rapport .= "• $module/$fournisseur : {$resultat['statut']}" . PHP_EOL;
    $rapport .= "  Message : {$resultat['message']}" . PHP_EOL;
    $rapport .= "  Dates : du " . DateUtils::format(DateUtils::DATE_FULL, $date_debut) . " au " . DateUtils::format(DateUtils::DATE_FULL, $date_fin) . PHP_EOL;
    if ($resultat["statut"] === "succes") {
        $rapport .= "  Adresses : " . PHP_EOL;
        $rapport .= "    From : " . $resultat["adresses"]["from"] . PHP_EOL;
        $rapport .= "    To : " . PHP_EOL;
        foreach ($resultat["adresses"]["to"] as $adresse) {
            $rapport .= "      $adresse" . PHP_EOL;
        }
        $rapport .= "    Cc : " . PHP_EOL;
        foreach ($resultat["adresses"]["cc"] as $adresse) {
            $rapport .= "      $adresse" . PHP_EOL;
        }
        $rapport .= "    Bcc : " . PHP_EOL;
        foreach ($resultat["adresses"]["bcc"] as $adresse) {
            $rapport .= "      $adresse" . PHP_EOL;
        }
    }
    if ($resultat["statut"] === "echec") {
        $rapport .= "  Erreur : " . print_r($resultat['erreur'], true) . PHP_EOL;
    }

    // Affichage du rapport
    echo $rapport;
}
