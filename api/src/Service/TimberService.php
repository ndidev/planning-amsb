<?php

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\Exceptions\Server\ServerException;
use App\DTO\Filter\TimberFilterDTO;
use App\DTO\SupplierWithUniqueDeliveryNoteNumber;
use App\DTO\TimberRegistryEntryDTO;
use App\DTO\TimberStatsDTO;
use App\DTO\TimberTransportSuggestionsDTO;
use App\Entity\ThirdParty;
use App\Entity\Timber\TimberAppointment;
use App\Repository\TimberAppointmentRepository;
use DateTimeInterface;
use PDOException;

/**
 * @phpstan-type TimberAppointmentArray array{
 *                                        id?: int,
 *                                        attente?: bool,
 *                                        date_rdv?: string,
 *                                        heure_arrivee?: string,
 *                                        heure_depart?: string,
 *                                        fournisseur?: int,
 *                                        chargement?: int,
 *                                        livraison?: int,
 *                                        client?: int,
 *                                        transporteur?: int,
 *                                        affreteur?: int,
 *                                        commande_prete?: bool,
 *                                        confirmation_affretement?: bool,
 *                                        numero_bl?: string,
 *                                        commentaire_public?: string,
 *                                        commentaire_cache?: string,
 *                                      }
 * 
 * @phpstan-type TimberRegistryEntryArray array{
 *                                          date_rdv?: string,
 *                                          fournisseur?: string,
 *                                          chargement_nom?: string,
 *                                          chargement_ville?: string,
 *                                          chargement_pays?: string,
 *                                          livraison_nom?: string,
 *                                          livraison_cp?: string,
 *                                          livraison_ville?: string,
 *                                          livraison_pays?: string,
 *                                          numero_bl?: string,
 *                                          transporteur?: string,
 *                                        }
 */
final class TimberService
{
    private TimberAppointmentRepository $timberAppointmentRepository;
    private ThirdPartyService $thirdPartyService;

    public function __construct()
    {
        $this->timberAppointmentRepository = new TimberAppointmentRepository($this);
        $this->thirdPartyService = new ThirdPartyService();
    }

    /**
     * Crée un RDV bois à partir des données brutes de la base de données.
     * 
     * @param array $rawData Données brutes du RDV bois.
     * 
     * @phpstan-param TimberAppointmentArray $rawData
     * 
     * @return TimberAppointment RDV bois créé.
     */
    public function makeTimberAppointmentFromDatabase(array $rawData): TimberAppointment
    {
        $appointment = (new TimberAppointment())
            ->setId($rawData["id"] ?? null)
            ->setOnHold($rawData["attente"] ?? false)
            ->setDate($rawData["date_rdv"] ?? null)
            ->setArrivalTime($rawData["heure_arrivee"] ?? null)
            ->setDepartureTime($rawData["heure_depart"] ?? null)
            ->setSupplier($this->thirdPartyService->getThirdParty($rawData["fournisseur"] ?? null))
            ->setLoadingPlace($this->thirdPartyService->getThirdParty($rawData["chargement"] ?? null))
            ->setDeliveryPlace($this->thirdPartyService->getThirdParty($rawData["livraison"] ?? null))
            ->setCustomer($this->thirdPartyService->getThirdParty($rawData["client"] ?? null))
            ->setCarrier($this->thirdPartyService->getThirdParty($rawData["transporteur"] ?? null))
            ->setTransportBroker($this->thirdPartyService->getThirdParty($rawData["affreteur"] ?? null))
            ->setReady($rawData["commande_prete"] ?? false)
            ->setCharteringConfirmationSent($rawData["confirmation_affretement"] ?? false)
            ->setDeliveryNoteNumber($rawData["numero_bl"] ?? "")
            ->setPublicComment($rawData["commentaire_public"] ?? "")
            ->setPrivateComment($rawData["commentaire_cache"] ?? "");

        return $appointment;
    }

    /**
     * Crée un RDV bois à partir des données brutes du formulaire.
     * 
     * @param array $rawData Données brutes du RDV bois.
     * 
     * @phpstan-param TimberAppointmentArray $rawData
     * 
     * @return TimberAppointment RDV bois créé.
     */
    public function makeTimberAppointmentFromForm(array $rawData): TimberAppointment
    {
        $appointment = (new TimberAppointment())
            ->setId($rawData["id"] ?? null)
            ->setOnHold($rawData["attente"] ?? false)
            ->setDate($rawData["date_rdv"] ?? null)
            ->setArrivalTime($rawData["heure_arrivee"] ?? null)
            ->setDepartureTime($rawData["heure_depart"] ?? null)
            ->setSupplier($this->thirdPartyService->getThirdParty($rawData["fournisseur"] ?? null))
            ->setLoadingPlace($this->thirdPartyService->getThirdParty($rawData["chargement"] ?? null))
            ->setDeliveryPlace($this->thirdPartyService->getThirdParty($rawData["livraison"] ?? null))
            ->setCustomer($this->thirdPartyService->getThirdParty($rawData["client"] ?? null))
            ->setCarrier($this->thirdPartyService->getThirdParty($rawData["transporteur"] ?? null))
            ->setTransportBroker($this->thirdPartyService->getThirdParty($rawData["affreteur"] ?? null))
            ->setReady($rawData["commande_prete"] ?? false)
            ->setCharteringConfirmationSent($rawData["confirmation_affretement"] ?? false)
            ->setDeliveryNoteNumber($rawData["numero_bl"] ?? "")
            ->setPublicComment($rawData["commentaire_public"] ?? "")
            ->setPrivateComment($rawData["commentaire_cache"] ?? "");

        return $appointment;
    }

    /**
     * Crée un DTO d'entrée de registre bois à partir des données brutes.
     * 
     * @param array $rawData Données brutes de l'entrée de registre bois.
     * 
     * @phpstan-param TimberRegistryEntryArray $rawData
     * 
     * @return TimberRegistryEntryDTO 
     */
    public function makeTimberRegisterEntryDTO(array $rawData): TimberRegistryEntryDTO
    {
        $registryEntry = (new TimberRegistryEntryDTO())
            ->setDate($rawData["date_rdv"] ?? "")
            ->setSupplierName($rawData["fournisseur"] ?? "")
            ->setLoadingPlaceName($rawData["chargement_nom"] ?? "")
            ->setLoadingPlaceCity($rawData["chargement_ville"] ?? "")
            ->setLoadingPlaceCountry($rawData["chargement_pays"] ?? "")
            ->setDeliveryPlaceName($rawData["livraison_nom"] ?? "")
            ->setDeliveryPlacePostCode($rawData["livraison_cp"] ?? "")
            ->setDeliveryPlaceCity($rawData["livraison_ville"] ?? "")
            ->setDeliveryPlaceCountry($rawData["livraison_pays"] ?? "")
            ->setDeliveryNoteNumber($rawData["numero_bl"] ?? "")
            ->setTransport($rawData["transporteur"] ?? "");

        return $registryEntry;
    }

    /**
     * Vérifie si un RDV bois existe dans la base de données.
     * 
     * @param int $id Identifiant du RDV bois.
     */
    public function appointmentExists(int $id): bool
    {
        return $this->timberAppointmentRepository->appointmentExists($id);
    }

    /**
     * Récupère tous les RDV bois.
     * 
     * @param TimberFilterDTO $filter Paramètres de recherche.
     * 
     * @return Collection<TimberAppointment> Tous les RDV récupérés.
     */
    public function getAppointments(TimberFilterDTO $filter): Collection
    {
        return $this->timberAppointmentRepository->fetchAppointments($filter);
    }

    /**
     * Récupère un RDV bois.
     * 
     * @param int $id ID du RDV à récupérer
     * 
     * @return ?TimberAppointment Rendez-vous récupéré
     */
    public function getAppointment(int $id): ?TimberAppointment
    {
        return $this->timberAppointmentRepository->fetchAppointment($id);
    }

    /**
     * Crée un RDV bois.
     * 
     * @param array $input Eléments du RDV à créer
     * 
     * @phpstan-param TimberAppointmentArray $input
     * 
     * @return TimberAppointment Rendez-vous créé
     */
    public function createAppointment(array $input): TimberAppointment
    {
        $appointment = $this->makeTimberAppointmentFromForm($input);

        return $this->timberAppointmentRepository->createAppointment($appointment);
    }

    /**
     * Met à jour un RDV bois.
     * 
     * @param int   $id ID du RDV à modifier
     * @param array $input  Eléments du RDV à modifier
     * 
     * @phpstan-param TimberAppointmentArray $input
     * 
     * @return TimberAppointment RDV modifié
     */
    public function updateAppointment($id, array $input): TimberAppointment
    {
        $appointment = $this->makeTimberAppointmentFromForm($input)->setId($id);

        return $this->timberAppointmentRepository->updateAppointment($appointment);
    }

    /**
     * Met à jour certaines proriétés d'un RDV bois.
     * 
     * @param int     $appointmentId                id du RDV à modifier.
     * @param ?bool   $isOrderReady                 Commande prête.
     * @param ?bool   $isCharteringConfirmationSent Confirmation d'affrètement envoyée.
     * @param ?string $deliveryNoteNumber           Numéro de BL.
     * @param bool    $setArrivalTime               Heure d'arrivée.
     * @param bool    $setDepartureTime             Heure de départ.
     * 
     * @return TimberAppointment RDV modifié.
     * 
     * @throws ClientException Si l'identifiant du RDV n'est pas fourni.
     */
    public function patchAppointment(
        int $appointmentId,
        ?bool $isOrderReady = null,
        ?bool $isCharteringConfirmationSent = null,
        ?string $deliveryNoteNumber = null,
        bool $setArrivalTime = false,
        bool $setDepartureTime = false,
    ): TimberAppointment {
        $appointment = $this->getAppointment($appointmentId);

        if (!$appointment) {
            throw new NotFoundException("Le RDV bois n'existe pas.");
        }

        if (!is_null($isOrderReady)) {
            $appointment = $this->timberAppointmentRepository->setOrderReady(
                $appointmentId,
                $isOrderReady
            );
        }

        if (!is_null($isCharteringConfirmationSent)) {
            $appointment = $this->timberAppointmentRepository->setCharteringConfirmationSent(
                $appointmentId,
                $isCharteringConfirmationSent
            );
        }

        if (!is_null($deliveryNoteNumber)) {
            $supplier = $appointment->getSupplier();
            $supplierId = $supplier?->getId();

            // Check if the delivery note number is available
            if (
                $deliveryNoteNumber !== "" &&
                !$this->isDeliveryNoteNumberAvailable($deliveryNoteNumber, $supplierId, $appointmentId)
            ) {
                throw new ClientException(
                    "Le numéro de BL {$deliveryNoteNumber} est déjà utilisé pour {$supplier?->getShortName()}."
                );
            }

            $appointment = $this->timberAppointmentRepository->setDeliveryNoteNumber(
                $appointmentId,
                $deliveryNoteNumber
            );
        }

        if ($setArrivalTime) {
            $arrivalTime = new \DateTimeImmutable();

            $appointment = $this->timberAppointmentRepository->setArrivalTime(
                $appointmentId,
                $arrivalTime
            );

            // If the supplier has unique delivery note numbers
            // and the loading place is the agency
            // and the delivery note number is not set
            // set the next delivery note number
            $supplierId = $appointment->getSupplier()?->getId();

            if (
                $supplierId
                && $this->isSupplierWithUniqueDeliveryNoteNumbers($supplierId)
                && $appointment->getLoadingPlace()?->getId() === 1
                && $appointment->getDeliveryNoteNumber() === ""
                && $nextDeliveryNoteNumber = $this->getNextDeliveryNoteNumber($supplierId)
            ) {
                $appointment = $this->timberAppointmentRepository->setDeliveryNoteNumber(
                    $appointmentId,
                    $nextDeliveryNoteNumber
                );
            }
        }

        if ($setDepartureTime) {
            $departureTime = new \DateTimeImmutable();

            $appointment = $this->timberAppointmentRepository->setDepartureTime(
                $appointmentId,
                $departureTime
            );
        }

        return $appointment;
    }

    /**
     * Supprime un RDV bois.
     * 
     * @param int $id ID du RDV à supprimer
     * 
     * @return void
     * 
     * @throws DBException Erreur lors de la suppression.
     */
    public function deleteAppointment(int $id): void
    {
        $this->timberAppointmentRepository->deleteAppointment($id);
    }

    /**
     * Renvoie l'extrait du registre d'affrètement avec le filtre appliqué.
     * 
     * @param \DateTimeInterface $startDate Date de début du filtre.
     * @param \DateTimeInterface $endDate   Date de fin du filtre.
     * 
     * @return string Extrait du registre d'affrètement au format CSV.
     */
    public function getChateringRegister(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): string {
        $output = fopen("php://temp/maxmemory:" . (5 * 1024 * 1024), "r+");

        if (!$output) {
            throw new ServerException("Erreur création fichier CSV");
        }

        try {
            $registryEntries =
                $this
                ->timberAppointmentRepository
                ->getCharteringRegistryEntries($startDate, $endDate);

            // UTF-8 BOM
            $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
            fputs($output, $bom);

            // En-tête
            $entete = [
                "Date",
                "Mois",
                "Donneur d'ordre",
                "Marchandise",
                "Chargement",
                "Livraison",
                "Numéro BL",
                "Transporteur"
            ];
            fputcsv($output, $entete, ';', '"');

            // Lignes de RDV
            foreach ($registryEntries as $entry) {

                $ligne = [
                    $entry->getDate(),
                    $entry->getMonth(),
                    $entry->getSupplierName(),
                    "1 COMPLET DE BOIS",
                    $entry->getLoadingPlace(),
                    $entry->getDeliveryPlace(),
                    $entry->getDeliveryNoteNumber(),
                    $entry->getTransport(),
                ];

                fputcsv($output, $ligne, ';', '"');
            }

            rewind($output);

            $csv = stream_get_contents($output);

            if (!$csv) {
                throw new ServerException("Erreur écriture lignes");
            }

            return $csv;
        } catch (\Throwable $e) {
            throw new ServerException("Erreur création fichier CSV", previous: $e);
        }
    }

    public function getTransportSuggestions(
        int $loadingPlaceId,
        int $deliveryPlaceId
    ): TimberTransportSuggestionsDTO {
        return $this->timberAppointmentRepository->getTransportSuggestions($loadingPlaceId, $deliveryPlaceId);
    }

    public function getStats(TimberFilterDTO $filter): TimberStatsDTO
    {
        return $this->timberAppointmentRepository->getStats($filter);
    }

    public function isDeliveryNoteNumberAvailable(
        string $deliveryNoteNumber,
        ?int $supplierId,
        ?int $appointmentId = null,
    ): bool {
        if ($deliveryNoteNumber === "") {
            return true;
        }

        if (!$supplierId) {
            return true;
        }

        if (!$this->isSupplierWithUniqueDeliveryNoteNumbers($supplierId)) {
            return true;
        }

        return $this->timberAppointmentRepository->isDeliveryNoteNumberAvailable(
            $deliveryNoteNumber,
            $supplierId,
            $appointmentId,
        );
    }

    /**
     * Récupère les fournisseurs avec des numéros de BL uniques.
     * 
     * @return SupplierWithUniqueDeliveryNoteNumber[] Fournisseurs avec des numéros de BL uniques.
     */
    public function getSuppliersWithUniqueDeliveryNoteNumbers(): array
    {
        $suppliersWithUniqueDeliveryNoteNumbers = [
            [
                // Stora Enso
                'supplierId' => 292,
                'regexp' => '\d{6}',
            ],
        ];

        $dtoArray = [];

        foreach ($suppliersWithUniqueDeliveryNoteNumbers as $info) {
            $dtoArray[$info["supplierId"]] = (new SupplierWithUniqueDeliveryNoteNumber())
                ->setId($info["supplierId"])
                ->setRegexp($info["regexp"]);
        }

        return $dtoArray;
    }

    public function getSuppliersWithUniqueDeliveryNoteNumbersDTO(int $supplierId): ?SupplierWithUniqueDeliveryNoteNumber
    {
        $suppliersWithUniqueDeliveryNoteNumbers = $this->getSuppliersWithUniqueDeliveryNoteNumbers();

        return $suppliersWithUniqueDeliveryNoteNumbers[$supplierId] ?? null;
    }

    public function isSupplierWithUniqueDeliveryNoteNumbers(int $supplierId): bool
    {
        $suppliersWithUniqueDeliveryNoteNumber = $this->getSuppliersWithUniqueDeliveryNoteNumbers();

        return array_key_exists($supplierId, $suppliersWithUniqueDeliveryNoteNumber);
    }

    public function getNextDeliveryNoteNumber(int $supplierId): ?string
    {
        $supplierDto = $this->getSuppliersWithUniqueDeliveryNoteNumbersDTO($supplierId);

        if (!$supplierDto) {
            return null;
        }

        $lastDeliveryNoteNumber = $this->timberAppointmentRepository->getLastDeliveryNoteNumber($supplierDto);

        if (!$lastDeliveryNoteNumber) {
            return null;
        }

        $nextDeliveryNoteNumber = (int) $lastDeliveryNoteNumber + 1;

        return (string) $nextDeliveryNoteNumber;
    }

    /**
     * Récupère les RDV bois pour un fournisseur.
     * 
     * @param ThirdParty $supplier 
     * @param \DateTimeInterface $startDate 
     * @param \DateTimeInterface $endDate 
     * 
     * @return array{
     *           attente: Collection<TimberAppointment>,
     *           non_attente: Collection<TimberAppointment>,
     *         }
     */
    public function getPdfAppointments(
        ThirdParty $supplier,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): array {
        return $this->timberAppointmentRepository->getPdfAppointments(
            $supplier,
            $startDate,
            $endDate
        );
    }
}
