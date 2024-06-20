<?php

namespace App\Core\PDF;

use App\Core\DateUtils;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use \tFPDF;

class PDFMailer extends PHPMailer
{
  /**
   * @param tFPDF    $pdf          PDF à envoyer.
   * @param \DateTime $date_debut   Date de début des RDV.
   * @param \DateTime $date_fin     Date de fin des RDV.
   * @param array    $agence       Informations de l'agence.
   * @param bool     $debug        Mode debug.
   */
  public function __construct(
    private ?tFPDF $pdf = null,
    private \DateTime $date_debut,
    private \DateTime $date_fin,
    private array $agence,
    private bool $debug = false
  ) {
    parent::__construct(true); // Passing `true` enables exceptions

    $this->setupSMTP();

    $this->setLanguage('fr', API . '/vendor/phpmailer/phpmailer/language/');

    $this->isHTML(true);   // Set email format to HTML
    $this->CharSet = 'UTF-8';

    $this->addSubject();
    $this->addBody();

    if ($pdf) {
      $this->addPDFAttachment();
    }
  }

  /**
   * Remplit les informations de connexion SMTP.
   */
  private function setupSMTP(): void
  {
    // Office 365 server settings
    if ($this->debug) {
      $this->SMTPDebug = SMTP::DEBUG_SERVER;                 // Enable verbose debug output
    }
    $this->isSMTP();                                       // Send using SMTP
    $this->SMTPAuth   = true;                              // Enable SMTP authentication
    $this->SMTPSecure = PDFMailer::ENCRYPTION_STARTTLS;    // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
    $this->Host       = $_ENV["MAIL_HOST"];                // Set the SMTP server to send through
    $this->Port       = $_ENV["MAIL_PORT"];                // TCP port to connect to
    $this->Username   = $_ENV["MAIL_USER"];                // SMTP username
    $this->Password   = $_ENV["MAIL_PASS"];                // SMTP password
  }

  /**
   * Ajout des adresses depuis la liste entrée en paramètre
   * 
   * @param string[] $from Adresse e-mail et nom de l'expéditeur.
   * @param string[] $to   Adresses e-mail des destinataires.
   * @param string[] $cc   Adresses e-mail en copie.
   * @param string[] $bcc  Adresses e-mail en copie cachée.
   */
  public function addAddresses(
    array $from = [
      "email" => null,
      "name" => null,
    ],
    array $to = [],
    array $cc = [],
    array $bcc = ["contact@ndi.dev"],
  ): void {
    // FROM
    $this->setFrom($from["email"] ?? $_ENV["MAIL_USER"], '=?utf-8?B?' . base64_encode($from["name"] ?? $_ENV["MAIL_FROM"]) . '?=');

    // CC
    foreach ($cc as $adress) {
      $this->addCC($adress);
    }

    // BCC
    foreach ($bcc as $adress) {
      $this->addBCC($adress);
    }

    // TO
    $catchAll = $_ENV["MAIL_CATCH_ALL"] ?? '';

    $emailAddresses = $catchAll ? explode(',', $catchAll) : $to;

    if ($emailAddresses) {
      foreach ($emailAddresses as $adress) {
        $adress = trim($adress, " \t\n\r\0\x0B-_;,");
        if (($adress != '') && (substr($adress, 0, 1) != '!') && (strpos($adress, '@') == TRUE)) {
          $this->addAddress($adress);
        }
      }
    }
  }

  /**
   * Récupère les adresses des destinataires.
   * @return array{from: string, to: string[], cc: string[], bcc: string[]}
   */
  public function getAllAddresses(): array
  {
    return [
      "from" => base64_decode(str_replace(["=?utf-8?B?", "?="], "", $this->FromName)) . " &lt;" . $this->From . "&gt;",
      "to" => array_map(fn (array $address): string => $address[0], $this->getToAddresses()),
      "cc" => array_map(fn (array $address): string => $address[0], $this->getCcAddresses()),
      "bcc" => array_map(fn (array $address): string => $address[0], $this->getBccAddresses()),
    ];
  }

  /**
   * Ajouter le sujet du message.
   */
  private function addSubject()
  {
    // Mise en forme des dates
    $date_debut_mise_en_forme = DateUtils::format("dd MMMM yyyy", $this->date_debut);
    $date_fin_mise_en_forme = DateUtils::format("dd MMMM yyyy", $this->date_fin);

    $this->Subject = "[{$this->agence['ville']}] Planning du $date_debut_mise_en_forme au $date_fin_mise_en_forme";
  }

  /**
   * Corps du message lors de l'envoi du PDF au client.
   * 
   * Le logo est intégré lors de l'envoi
   * grâce à une fonction d'attachement d'image.
   */
  private function addBody(): void
  {
    $date_debut_mise_en_forme = DateUtils::format(DateUtils::DATE_FULL, $this->date_debut);
    $date_fin_mise_en_forme = DateUtils::format(DateUtils::DATE_FULL, $this->date_fin);
    $agence = $this->agence;

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

    $this->Body = $html;
    $this->AddEmbeddedImage(API . '/images/logo_agence_combi_mini.png', 'logoimg', 'AMSB'); // Image appelée par Content ID (cid:logoimg) dans le corps du message
  }

  /**
   * Ajout du PDF en pièce jointe.
   */
  private function addPDFAttachment()
  {
    $this->addStringAttachment($this->pdf->Output('S'), $this->Subject . '.pdf', 'base64', 'application/pdf'); // Ajout du planning PDF en pièce jointe
  }
}
