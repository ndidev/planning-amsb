<?php

require_once __DIR__ . "/../bootstrap.php";

use App\Core\DateUtils;
use App\Core\PDF\PDFMailer;
use App\Core\Logger\ErrorLogger;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

$agence = [
    "nom" => "TEST",
    "adresse_ligne_1" => "Test adresse_ligne_1",
    "adresse_ligne_2" => "Test adresse_ligne_2",
    "ville" => "Test",
    "cp" => "00000",
    "telephone" => "00 00 00 00 00",
    "email" => "test@example.com",
];

$date_debut = new DateTime();
$date_fin = (new DateTime())->add(new DateInterval("P1D"));

/**
 * @var array $resultat Résultat de l'envoi.
 */
$resultat = [
    "statut" => null,
    "message" => null,
    "adresses" => null,
    "erreur" => null
];


try {
    // Création e-mail
    $mail = new PDFMailer(null, $date_debut, $date_fin, $agence, true);

    $mail->ajouterAdresses();

    $mail->send();
    $mail->smtpClose();

    $resultat["statut"] = "succes";
    $resultat["message"] = "Le PDF a été envoyé avec succès.";
    $resultat["adresses"] = $mail->getAllAddresses();
} catch (PHPMailerException $e) {
    $resultat["statut"] = "echec";
    $resultat["message"] = "Erreur : " . $mail->ErrorInfo;
    $resultat["erreur"] = $e->errorMessage();
    ErrorLogger::log($e);
} catch (\Exception $e) {
    $resultat["statut"] = "echec";
    $resultat["message"] = "Erreur : " . $mail->ErrorInfo;
    ErrorLogger::log($e);
} finally {
    unset($mail);

    $rapport = "";

    // Mise à jour du rapport
    // $rapport .= "• $module/$fournisseur : {$resultat['statut']}" . PHP_EOL;
    $rapport .= "• module/fournisseur : {$resultat['statut']}" . PHP_EOL;
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
    echo "<pre>";
    echo $rapport;
    echo "</pre>";
}
