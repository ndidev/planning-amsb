<?php

// Path: api/src/DTO/PDF/TimberPDF.php

declare(strict_types=1);

namespace App\DTO\PDF;

use App\Core\Component\Collection;
use App\Core\Component\DateUtils;
use App\Entity\Timber\TimberAppointment;
use App\Entity\Config\AgencyDepartment;
use App\Entity\ThirdParty;

/**
 * @phpstan-type TimberPdfAppointments array{
 *                                       attente: Collection<TimberAppointment>,
 *                                       non_attente: Collection<TimberAppointment>
 *                                     }
 */
final class TimberPDF extends PlanningPDF
{
    /**
     * Génère un PDF bois.
     * 
     * @param ThirdParty            $supplier         Infos sur le fournisseur.
     * @param TimberPdfAppointments $appointments     RDVs à inclure dans le PDF.
     * @param \DateTimeInterface    $startDate        Date de début des RDV.
     * @param \DateTimeInterface    $endDate          Date de fin des RDV.
     * @param AgencyDepartment      $agencyDepartment Infos sur l'agence.
     */
    public function __construct(
        protected ThirdParty $supplier,
        protected array $appointments,
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
        $appointmentsOnHold = $this->appointments["attente"];
        $confirmedAppointments = $this->appointments["non_attente"];

        if (count($confirmedAppointments) > 0) {
            /** @var ?\DateTimeInterface */
            $previousDate = null;

            foreach ($confirmedAppointments as $appointment) {
                $appointmentDate = $appointment->date;

                $formattedDate = $appointmentDate
                    ? DateUtils::format(DateUtils::DATE_FULL, $appointmentDate)
                    : "Pas de date";

                $loadingPlace = $appointment->loadingPlace;
                $deliveryPlace = $appointment->deliveryPlace;
                $customer = $appointment->customer;

                if (
                    $loadingPlace?->country?->iso === 'FR'
                    || $loadingPlace?->country?->iso === 'ZZ'
                ) {
                    $loadingPostCode = ' ' . \substr((string) $loadingPlace->postCode, 0, 2);
                    $loadingCountry = '';
                } else {
                    $loadingPostCode = '';
                    $loadingCountry = ' (' . $loadingPlace?->country?->iso . ')';
                }

                if (
                    $customer?->country?->iso === 'FR'
                    || $customer?->country?->iso === 'ZZ'
                ) {
                    $customerPostCode = ' ' . \substr((string) $customer->postCode, 0, 2);
                    $customerCountry = '';
                } else {
                    $customerPostCode = '';
                    $customerCountry = ' (' . $customer?->country?->iso . ')';
                }

                if (
                    $deliveryPlace?->country?->iso === 'FR'
                    || $deliveryPlace?->country?->iso === 'ZZ'
                ) {
                    $deliveryPostCode = ' ' . \substr((string) $deliveryPlace->postCode, 0, 2);
                    $deliveryCountry = '';
                } else {
                    $deliveryPostCode = '';
                    $deliveryCountry = ' (' . $deliveryPlace?->country?->iso . ')';
                }

                if ($appointment->date != $previousDate) {
                    $this->AddDate($formattedDate);
                }
                $this->AddLine(
                    $loadingPlace?->id,
                    $loadingPlace?->shortName,
                    $loadingPostCode,
                    $loadingPlace?->city,
                    $loadingCountry,
                    $customer?->id,
                    $customer?->shortName,
                    $customerPostCode,
                    $customer?->city,
                    $customerCountry,
                    $deliveryPlace?->id,
                    $deliveryPlace?->shortName,
                    $deliveryPostCode,
                    $deliveryPlace?->city,
                    $deliveryCountry,
                    $appointment->transportBroker?->isAgency,
                    $appointment->transportBroker->shortName ?? "À affréter",
                    $appointment->deliveryNoteNumber,
                    $appointment->publicComment
                );

                $previousDate = $appointment->date;
            }
        } else {
            // Affichage de "Aucun RDV"
            $this->AucunRDV($this->startDate, $this->endDate);
        }

        $this->AddHeaderAttente();

        /**
         * Vérification de la présence de rendez-vous en attente
         * S'il y a des RDV sur la période, peuplement des lignes
         * Sinon, affichage de "Aucun RDV"
         */
        if (count($appointmentsOnHold) > 0) {
            foreach ($appointmentsOnHold as $appointment) {
                $appointmentDate = $appointment->date;

                $formattedDate = $appointmentDate
                    ? DateUtils::format(DateUtils::DATE_FULL, $appointmentDate)
                    : "Pas de date";

                $loadingPlace = $appointment->loadingPlace;
                $deliveryPlace = $appointment->deliveryPlace;
                $customer = $appointment->customer;

                if (
                    $loadingPlace?->country?->iso === 'FR'
                    || $loadingPlace?->country?->iso === 'ZZ'
                ) {
                    $loadingPostCode = ' ' . \substr((string) $loadingPlace->postCode, 0, 2);
                    $loadingCountry = '';
                } else {
                    $loadingPostCode = '';
                    $loadingCountry = ' (' . $loadingPlace?->country?->iso . ')';
                }

                if (
                    $customer?->country?->iso === 'FR'
                    || $customer?->country?->iso === 'ZZ'
                ) {
                    $customerPostCode = ' ' . \substr((string) $customer->postCode, 0, 2);
                    $customerCountry = '';
                } else {
                    $customerPostCode = '';
                    $customerCountry = ' (' . $customer?->country?->iso . ')';
                }

                if (
                    $deliveryPlace?->country?->iso === 'FR'
                    || $deliveryPlace?->country?->iso === 'ZZ'
                ) {
                    $deliveryPostCode = ' ' . \substr((string) $deliveryPlace->postCode, 0, 2);
                    $deliveryCountry = '';
                } else {
                    $deliveryPostCode = '';
                    $deliveryCountry = ' (' . $deliveryPlace?->country?->iso . ')';
                }

                $this->AddLineAttente(
                    $formattedDate,
                    $loadingPlace?->id,
                    $loadingPlace?->shortName,
                    $loadingPostCode,
                    $loadingPlace?->city,
                    $loadingCountry,
                    $customer?->id,
                    $customer?->shortName,
                    $customerPostCode,
                    $customer?->city,
                    $customerCountry,
                    $deliveryPlace?->id,
                    $deliveryPlace?->shortName,
                    $deliveryPostCode,
                    $deliveryPlace?->city,
                    $deliveryCountry,
                    $appointment->publicComment
                );
            }
        } else {
            // Affichage de "Aucun RDV"
            $this->AucunRDVAttente();
        }
    }

    /**
     * Ajout d'une ligne de date.
     * 
     * @param string $formattedDate Date mise en forme.
     */
    private function AddDate(string $formattedDate): void
    {
        $this->SetFont('RobotoB', '', 12);
        $this->SetTextColor(88, 200, 95);
        $this->Cell(0, 10, $formattedDate, 0, 1);
    }

    /**
     * Ajout d'une ligne RDV.
     */
    private function AddLine(
        ?int    $loadingId,
        ?string $loadingName,
        ?string $loadingPostCode,
        ?string $loadingCity,
        ?string $loadingCountry,
        ?int    $customerId,
        ?string $customerName,
        ?string $customerPostCode,
        ?string $customerCity,
        ?string $customerCountry,
        ?int    $deliveryId,
        ?string $deliveryName,
        ?string $deliveryPostCode,
        ?string $deliveryCity,
        ?string $deliveryCountry,
        ?bool   $chartererIsLinkedToAgency,
        string  $chartererName,
        string  $deliveryNoteNumber,
        string  $publicComments
    ): void {
        $this->SetFont('Roboto', '', 10);
        $this->SetTextColor(0, 0, 0);

        $this->Cell(100, 6, $customerName . $customerPostCode . ' ' . $customerCity . $customerCountry);

        if ($chartererIsLinkedToAgency == 1) {
            $this->SetTextColor(0, 0, 255);
        }
        $this->Cell(40, 6, $chartererName);

        $this->SetTextColor(0, 0, 0);
        $this->Cell(40, 6, $deliveryNoteNumber, 0, 1);

        if ((int) $loadingId !== 1 /* AMSB */) {
            $this->SetTextColor(100, 100, 100);
            $this->Cell(10, 6, "chargement " . $loadingName . $loadingPostCode . ' ' . $loadingCity . $loadingCountry, 0, 1);
            $this->SetTextColor(0, 0, 0);
            // $this->Cell(100, 6, $chargement_nom . $chargement_departement . ' ' . $chargement_ville . $chargement_pays, 0, 1);
        }

        if ($deliveryId !== $customerId) {
            $this->SetTextColor(100, 100, 100);
            $this->Cell(10, 6, "livraison " . $deliveryName . $deliveryPostCode . ' ' . $deliveryCity . $deliveryCountry, 0, 1);
            $this->SetTextColor(0, 0, 0);
            // $this->Cell(100, 6, $livraison_nom . $livraison_departement . ' ' . $livraison_ville . $livraison_pays, 0, 1);
        }

        if ($publicComments !== '') {
            $publicComments = \str_replace(" <br> ", "\n", $publicComments);
            $this->Cell(5, 6); // Décalage de 0.5cm
            $this->MultiCell(0, 6, $publicComments);
        }

        $this->Cell(0, 4, '', 0, 1); // Espace avant le prochain rdv pour la lisibilité
    }

    function AddHeaderAttente(): void
    {
        $this->SetFont('Roboto', '', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, '', 0, 1); // Espace avant la ligne pour la lisibilité
        $this->Cell(0, 10, 'Rendez-vous en attente de confirmation', 0, 1, 'C');
    }

    private function AddLineAttente(
        ?string $formattedDate,
        ?int    $loadingId,
        ?string $loadingName,
        ?string $loadingPostCode,
        ?string $loadingCity,
        ?string $loadingCountry,
        ?int    $customerId,
        ?string $customerName,
        ?string $customerPostCode,
        ?string $customerCity,
        ?string $customerCountry,
        ?int    $deliveryId,
        ?string $deliveryName,
        ?string $deliveryPostCode,
        ?string $deliveryCity,
        ?string $deliveryCountry,
        string  $publicComments
    ): void {
        $this->SetFont('Roboto', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(10, 6); // Décalage de 1cm
        $this->Cell(50, 6, $formattedDate);

        $this->Cell(100, 6, $customerName . $customerPostCode . ' ' . $customerCity . $customerCountry, 0, 1);

        if ((int) $loadingId !== 1 /* AMSB */) {
            $this->Cell(60, 6); // Décalage de 6cm
            $this->Cell(10, 6, "chargement " . $loadingName . $loadingPostCode . ' ' . $loadingCity . $loadingCountry, 0, 1);
        }

        if ($deliveryId !== $customerId) {
            $this->Cell(60, 6); // Décalage de 6cm
            $this->Cell(10, 6, "livraison " . $deliveryName . $deliveryPostCode . ' ' . $deliveryCity . $deliveryCountry, 0, 1);
            // $this->Cell(100, 6, $livraison_nom . $livraison_departement . ' ' . $livraison_ville . $livraison_pays, 0, 1);
        }

        if ($publicComments != '') {
            $publicComments = \str_replace(" <br> ", "\n", $publicComments);
            $this->Cell(60, 6); // Décalage de 6cm
            $this->MultiCell(0, 6, $publicComments);
        }

        $this->Cell(0, 4, '', 0, 1); // Espace avant le prochain rdv pour la lisibilité
    }

    /**
     * Affichage d'un message "Aucun RDV".
     * 
     * @param \DateTimeInterface $startDate Date de début.
     * @param \DateTimeInterface $endDate   Date de fin.
     */
    private function AucunRDV(\DateTimeInterface $startDate, \DateTimeInterface $endDate): void
    {
        $this->SetFont('Roboto', '', 12);
        $this->SetTextColor(0, 0, 0);
        $formattedStartDate = DateUtils::format(DateUtils::DATE_FULL, $startDate);
        $formattedEndDate = DateUtils::format(DateUtils::DATE_FULL, $endDate);
        $this->Cell(0, 30, "Aucun rendez-vous du $formattedStartDate au $formattedEndDate", 0, 1, 'C');
    }

    /**
     * Affichage d'un message "Aucun RDV en attente".
     */
    private function AucunRDVAttente(): void
    {
        $this->SetFont('Roboto', '', 12);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, 'Aucun rendez-vous en attente de confirmation', 0, 0, 'C');
    }
}
