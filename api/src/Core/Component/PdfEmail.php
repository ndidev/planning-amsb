<?php

// Path: api/src/Core/Component/PdfEmail.php

declare(strict_types=1);

namespace App\Core\Component;

use App\Core\Component\DateUtils;
use App\DTO\PDF\PlanningPDF;
use App\Entity\Config\AgencyDepartment;

class PdfEmail extends Email
{
  public function __construct(
    private PlanningPDF $pdf,
    private \DateTimeInterface $startDate,
    private \DateTimeInterface $endDate,
    private AgencyDepartment $agency,

  ) {
    parent::__construct();

    $this->addSubject();
    $this->addBody();
    $this->addPdfAttachment();
  }

  /**
   * Ajouter le sujet du message.
   */
  private function addSubject(): void
  {
    $formattedStartDate = DateUtils::format("dd MMMM yyyy", $this->startDate);
    $formattedEndDate = DateUtils::format("dd MMMM yyyy", $this->endDate);

    $this->Subject = "[{$this->agency->getCity()}] Planning du $formattedStartDate au $formattedEndDate";
  }

  /**
   * Corps du message lors de l'envoi du PDF au client.
   * 
   * Le logo est intégré lors de l'envoi grâce à une fonction d'attachement d'image.
   */
  private function addBody(): void
  {
    $formattedStartDate = DateUtils::format(DateUtils::DATE_FULL, $this->startDate);
    $formattedEndDate = DateUtils::format(DateUtils::DATE_FULL, $this->endDate);

    $telephone = $this->agency->getPhone() ? 'Tel : ' . $this->agency->getPhone() : '';

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
    <p>Veuillez trouver ci-joint le planning du $formattedStartDate au $formattedEndDate.</p>
    <p>Cordialement,</p>
  </div>
  <div class='signature'>
    <p>{$this->agency->getFullName()}</p>
    <p>{$this->agency->getAddressLine1()}</p>
    <p>{$this->agency->getAddressLine2()}</p>
    <p>{$this->agency->getPostCode()} {$this->agency->getCity()}</p>
    <p>{$telephone}</p>
    <p><a href="mailto:{$this->agency->getEmail()}" target="_blank">{$this->agency->getEmail()}</a></p>
    <p><a href="http://www.maritimekuhn.com" target="_blank">www.maritimekuhn.com</a></p>
  </div>
  <div class="logo"><img src="cid:logoimg" alt="AMSB" height="auto" width="180"></div>
</body>

</html>
HTML;

    $this->Body = $html;

    // Image appelée par Content ID (cid:logoimg) dans le corps du message
    $this->AddEmbeddedImage(API . '/images/logo_agence_combi_mini.png', 'logoimg', 'AMSB');
  }

  /**
   * Ajout du PDF en pièce jointe.
   */
  private function addPdfAttachment(): void
  {
    $this->addStringAttachment($this->pdf->stringifyPDF(), $this->Subject . '.pdf', 'base64', 'application/pdf');
  }
}
