<?php

namespace App\DTO\PDF;

use App\Core\Component\ColorConverter;
use App\Core\Component\Collection;
use App\Core\Component\DateUtils;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Config\AgencyDepartment;
use App\Entity\ThirdParty;

class BulkPDF extends PlanningPDF
{
    /**
     * Génère un PDF vrac.
     * 
     * @param ThirdParty                  $supplier         Infos sur le fournisseur.
     * @param Collection<BulkAppointment> $appointments     RDVs à inclure dans le PDF.
     * @param \DateTimeInterface          $startDate        Date de début des RDV.
     * @param \DateTimeInterface          $endDate          Date de fin des RDV.
     * @param AgencyDepartment            $agencyDepartment Infos sur l'agence.
     */
    public function __construct(
        protected ThirdParty $supplier,
        protected Collection $appointments,
        protected \DateTimeInterface $startDate,
        protected \DateTimeInterface $endDate,
        protected AgencyDepartment $agencyDepartment
    ) {
        parent::__construct($supplier, $agencyDepartment);

        $this->generatePDF();
    }

    protected function generatePDF(): void
    {
        $this->AliasNbPages();
        $this->AddPage();

        /**
         * Vérification de la présence de rendez-vous
         * S'il y a des RDV sur la période, peuplement des lignes
         * Sinon, affichage de "Aucun RDV"
         */
        if (count($this->appointments) > 0) // Peuplement des lignes
        {
            /** @var ?\DateTimeInterface */
            $previousDate = null;

            foreach ($this->appointments as $appointment) {
                $formattedTime =
                    $appointment->getTime()
                    ? DateUtils::format(DateUtils::ISO_TIME, $appointment->getTime())
                    : "";

                if ($appointment->getDate() != $previousDate) {
                    $formattedDate = DateUtils::format(DateUtils::DATE_FULL, $appointment->getDate());
                    $this->AddDate($formattedDate);
                }

                $this->AddLine(
                    $formattedTime,
                    $appointment->getProduct()->getName(),
                    $appointment->getProduct()->getColor(),
                    $appointment->getQuality()->getName(),
                    $appointment->getQuality()->getColor(),
                    $appointment->getCustomer()->getShortName(),
                    $appointment->getCustomer()->getCity(),
                    $appointment->getCarrier()?->getShortName() ?? "",
                    $appointment->getOrderNumber()
                );

                $previousDate = $appointment->getDate();
            }
        } else {
            // Affichage de "Aucun RDV"
            $this->AucunRDV($this->startDate, $this->endDate);
        }
    }

    /**
     * Ajout d'une ligne de date.
     * 
     * @param string $date Date mise en forme.
     */
    private function AddDate(string $date): void
    {
        $this->SetFont('RobotoB', '', 12);
        $this->SetTextColor(88, 200, 95);
        $this->Cell(0, 10, $date, 0, 1);
    }

    /**
     * Ajout d'une ligne RDV.
     * 
     * @param string $time 
     * @param string $productName 
     * @param string $productColor 
     * @param string $qualityName 
     * @param string $qualityColor 
     * @param string $quantite 
     * @param string $unite 
     * @param string $clientName 
     * @param string $clientCity 
     * @param string $transportName 
     * @param string $orderNumber 
     */
    private function AddLine(
        ?string $time,
        ?string $productName,
        ?string $productColor,
        ?string $qualityName,
        ?string $qualityColor,
        ?string $clientName,
        ?string $clientCity,
        ?string $transportName,
        string $orderNumber
    ): void {
        $this->SetFont('Roboto', '', 10);
        // Heure
        [$r, $g, $b] = explode(',', ColorConverter::hexToRgb('#D91FFA'));
        $this->SetTextColor($r, $g, $b);
        $this->Cell(15, 6, $time);
        // Produit
        [$r, $g, $b] = explode(',', ColorConverter::hexToRgb($productColor));
        $this->SetTextColor($r, $g, $b);
        $this->Cell(20, 6, $productName);
        // Qualité
        if ($qualityColor) {
            [$r, $g, $b] = explode(',', ColorConverter::hexToRgb($qualityColor));
            $this->SetTextColor($r, $g, $b);
        }
        $this->Cell(20, 6, $qualityName);
        // Client
        $this->SetTextColor(0, 0, 0);
        $this->Cell(70, 6, $clientName . ' ' . $clientCity);
        // Transporteur
        $this->Cell(30, 6, $transportName);
        // Numéro commande
        $this->Cell(30, 6, $orderNumber, 0, 1);
        $this->Cell(0, 4, '', 0, 1); // Espace avant le prochain rdv pour la lisibilité
    }

    /**
     * Affichage d'un message "Aucun RDV".
     * 
     * @param \DateTimeImmutable $startDate Date de début.
     * @param \DateTimeImmutable $endDate   Date de fin.
     */
    private function AucunRDV(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): void
    {
        $this->SetFont('Roboto', '', 12);
        $this->SetTextColor(0, 0, 0);
        $formattedStartDate = DateUtils::format(DateUtils::DATE_FULL, $startDate);
        $formattedEndDate = DateUtils::format(DateUtils::DATE_FULL, $endDate);
        $this->Cell(0, 30, "Aucun rendez-vous du $formattedStartDate au $formattedEndDate", 0, 0, 'C');
    }
}
