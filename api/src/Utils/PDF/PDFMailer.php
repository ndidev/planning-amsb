<?php

namespace Api\Utils\PDF;

use PHPMailer\PHPMailer\PHPMailer;
use Api\Utils\DateUtils;
use \DateTime;

class PDFMailer extends PHPMailer
{
  /**
   * Ajout des adresses depuis la liste entrée en paramètre
   * 
   * @param string $liste_emails Adresses e-mail des destinataires.
   * 
   * @return array Liste des adresses e-mail réellement ajoutées.
   */
  public function ajouterAdresses(string $liste_emails): array
  {
    $liste_emails = explode(PHP_EOL, $liste_emails);

    $adresses_ajoutees = [];

    if ($liste_emails) {
      foreach ($liste_emails as $adresse) {
        $adresse = trim($adresse, " \t\n\r\0\x0B-_;,");
        if (($adresse != '') && (substr($adresse, 0, 1) != '!') && (strpos($adresse, '@') == TRUE)) {
          $this->addAddress($adresse);
          array_push($adresses_ajoutees, $adresse);
        }
      }
    }

    return $adresses_ajoutees;
  }

  /**
   * Corps du message lors de l'envoi du PDF au client.
   * 
   * Le logo est intégré lors de l'envoi
   * grâce à une fonction d'attachement d'image.
   * 
   * @param DateTime $date_debut Date de début des RDV.
   * @param DateTime $date_fin   Date de fin des RDV.
   * @param array    $agence     Infos de l'agence.
   * 
   * @return string Corps HTML du message.
   */
  public function ajouterCorpsMessage(
    DateTime $date_debut,
    DateTime $date_fin,
    array $agence
  ): string {
    $date_debut_mise_en_forme = DateUtils::format(DateUtils::DATE_FULL, $date_debut);
    $date_fin_mise_en_forme = DateUtils::format(DateUtils::DATE_FULL, $date_fin);

    if ($agence["telephone"]) {
      $agence["telephone"] = 'Tel : ' . $agence["telephone"];
    }

    $html = <<<HTML
<html lang="fr" dir="ltr">

<head>
  <meta charset="utf-8">
  <style>
    .corps_message {
      font-family: Arial, sans-serif;
      font-size: 11pt;
      color: rgb(0, 0, 0);
    }

    .signature {
      font-family: Arial, sans-serif;
      font-size: 10pt;
      color: #4E4F54;
    }

    .signature p {
      margin: 0;
    }

    .logo {
      margin-top: 15px;
    }

    a:link {
      color: #1F497D;
      text-decoration: underline
    }
  </style>
</head>

<body>
  <div class='corps_message'>
    <p>Bonjour,</p>
    <p>Veuillez trouver ci-joint le planning du $date_debut_mise_en_forme au $date_fin_mise_en_forme.</p>
    <p>Cordialement,</p>
  </div>
  <div class='signature'>
    <p>{$agence["nom"]}</p>
    <p>{$agence["adresse_ligne_1"]}</p>
    <p>{$agence["adresse_ligne_2"]}</p>
    <p>{$agence["cp"]} {$agence["ville"]}</p>
    <p>{$agence["telephone"]}</p>
    <p><a href="mailto:{$agence['email']}" target="_blank">{$agence["email"]}</a></p>
    <p><a href="http://www.maritimekuhn.com" target="_blank">www.maritimekuhn.com</a></p>
  </div>
  <div class="logo"><img src="cid:logoimg" alt="AMSB" height="auto" width="180"></div>
</body>

</html>
HTML;

    return $html;
  }
}
