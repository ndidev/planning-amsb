<?php

// Path: api/src/Service/TimberService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\HTTPRequestBody;
use App\DTO\Filter\TimberFilterDTO;
use App\DTO\SupplierWithUniqueDeliveryNoteNumber;
use App\DTO\TimberRegistryEntryDTO;
use App\DTO\TimberStatsDTO;
use App\DTO\TimberTransportSuggestionsDTO;
use App\Entity\ThirdParty;
use App\Entity\Timber\TimberAppointment;
use App\Entity\Timber\TimberDispatchItem;
use App\Repository\TimberAppointmentRepository;

/**
 * @phpstan-import-type TimberAppointmentArray from \App\Entity\Timber\TimberAppointment
 * @phpstan-import-type TimberRegistryEntryArray from \App\DTO\TimberRegistryEntryDTO
 * @phpstan-import-type TimberDispatchArray from \App\Entity\Timber\TimberDispatchItem
 */
final class TimberService
{
    private TimberAppointmentRepository $appointmentRepository;
    private ThirdPartyService $thirdPartyService;
    private StevedoringService $stevedoringService;

    public function __construct()
    {
        $this->appointmentRepository = new TimberAppointmentRepository($this);
        $this->thirdPartyService = new ThirdPartyService();
        $this->stevedoringService = new StevedoringService();
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
        $rawDataAH = new ArrayHandler($rawData);

        $appointment = (new TimberAppointment())
            ->setId($rawDataAH->getInt('id'))
            ->setOnHold($rawDataAH->getBool('attente'))
            ->setDate($rawDataAH->getDatetime('date_rdv'))
            ->setArrivalTime($rawDataAH->getDatetime('heure_arrivee'))
            ->setDepartureTime($rawDataAH->getDatetime('heure_depart'))
            ->setSupplier($this->thirdPartyService->getThirdParty($rawDataAH->getInt('fournisseur')))
            ->setLoadingPlace($this->thirdPartyService->getThirdParty($rawDataAH->getInt('chargement')))
            ->setDeliveryPlace($this->thirdPartyService->getThirdParty($rawDataAH->getInt('livraison')))
            ->setCustomer($this->thirdPartyService->getThirdParty($rawDataAH->getInt('client')))
            ->setCarrier($this->thirdPartyService->getThirdParty($rawDataAH->getInt('transporteur')))
            ->setTransportBroker($this->thirdPartyService->getThirdParty($rawDataAH->getInt('affreteur')))
            ->setReady($rawDataAH->getBool('commande_prete'))
            ->setCharteringConfirmationSent($rawDataAH->getBool('confirmation_affretement'))
            ->setDeliveryNoteNumber($rawDataAH->getString('numero_bl'))
            ->setPublicComment($rawDataAH->getString('commentaire_public'))
            ->setPrivateComment($rawDataAH->getString('commentaire_cache'));

        return $appointment;
    }

    /**
     * Crée un RDV bois à partir des données brutes du formulaire.
     * 
     * @param HTTPRequestBody $rawData Données brutes du RDV bois.
     * 
     * @return TimberAppointment RDV bois créé.
     */
    public function makeTimberAppointmentFromForm(HTTPRequestBody $rawData): TimberAppointment
    {
        $appointment = (new TimberAppointment())
            ->setId($rawData->getInt('id'))
            ->setOnHold($rawData->getBool('attente'))
            ->setDate($rawData->getDatetime('date_rdv'))
            ->setArrivalTime($rawData->getDatetime('heure_arrivee'))
            ->setDepartureTime($rawData->getDatetime('heure_depart'))
            ->setSupplier($this->thirdPartyService->getThirdParty($rawData->getInt('fournisseur')))
            ->setLoadingPlace($this->thirdPartyService->getThirdParty($rawData->getInt('chargement')))
            ->setDeliveryPlace($this->thirdPartyService->getThirdParty($rawData->getInt('livraison')))
            ->setCustomer($this->thirdPartyService->getThirdParty($rawData->getInt('client')))
            ->setCarrier($this->thirdPartyService->getThirdParty($rawData->getInt('transporteur')))
            ->setTransportBroker($this->thirdPartyService->getThirdParty($rawData->getInt('affreteur')))
            ->setReady($rawData->getBool('commande_prete'))
            ->setCharteringConfirmationSent($rawData->getBool('confirmation_affretement'))
            ->setDeliveryNoteNumber($rawData->getString('numero_bl'))
            ->setPublicComment($rawData->getString('commentaire_public'))
            ->setPrivateComment($rawData->getString('commentaire_cache'))
            ->setDispatch(
                array_map(
                    // @phpstan-ignore argument.type
                    fn(array $dispatchRaw) => $this->makeTimberDispatchItemFromFormData(new ArrayHandler($dispatchRaw)),
                    $rawData->getArray('dispatch')
                )
            );

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
            ->setDate($rawData["date_rdv"])
            ->setSupplierName($rawData["fournisseur"] ?? "")
            ->setLoadingPlaceName($rawData["chargement_nom"] ?? "")
            ->setLoadingPlaceCity($rawData["chargement_ville"] ?? "")
            ->setLoadingPlaceCountry($rawData["chargement_pays"] ?? "")
            ->setDeliveryPlaceName($rawData["livraison_nom"] ?? "")
            ->setDeliveryPlacePostCode($rawData["livraison_cp"] ?? "")
            ->setDeliveryPlaceCity($rawData["livraison_ville"] ?? "")
            ->setDeliveryPlaceCountry($rawData["livraison_pays"] ?? "")
            ->setDeliveryNoteNumber($rawData["numero_bl"])
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
        return $this->appointmentRepository->appointmentExists($id);
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
        return $this->appointmentRepository->fetchAppointments($filter);
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
        return $this->appointmentRepository->fetchAppointment($id);
    }

    /**
     * Crée un RDV bois.
     * 
     * @param HTTPRequestBody $input Eléments du RDV à créer
     * 
     * @return TimberAppointment Rendez-vous créé
     */
    public function createAppointment(HTTPRequestBody $input): TimberAppointment
    {
        $appointment = $this->makeTimberAppointmentFromForm($input);

        $appointment->validate();

        return $this->appointmentRepository->createAppointment($appointment);
    }

    /**
     * Met à jour un RDV bois.
     * 
     * @param int             $id ID du RDV à modifier
     * @param HTTPRequestBody $input  Eléments du RDV à modifier
     * 
     * @return TimberAppointment RDV modifié
     */
    public function updateAppointment($id, HTTPRequestBody $input): TimberAppointment
    {
        $appointment = $this->makeTimberAppointmentFromForm($input)->setId($id);

        $appointment->validate();

        return $this->appointmentRepository->updateAppointment($appointment);
    }

    /**
     * Met à jour certaines proriétés d'un RDV bois.
     * 
     * @param int           $appointmentId                id du RDV à modifier.
     * @param ?bool         $isOrderReady                 Commande prête.
     * @param ?bool         $isCharteringConfirmationSent Confirmation d'affrètement envoyée.
     * @param ?string       $deliveryNoteNumber           Numéro de BL.
     * @param bool          $setArrivalTime               Heure d'arrivée.
     * @param bool          $setDepartureTime             Heure de départ.
     * @param ?array<mixed> $dispatch                     Dispatch.
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
        ?array $dispatch = null,
    ): TimberAppointment {
        $appointment = $this->getAppointment($appointmentId);

        if (!$appointment) {
            throw new NotFoundException("Le RDV bois n'existe pas.");
        }

        if (!is_null($isOrderReady)) {
            $appointment = $this->appointmentRepository->setOrderReady(
                $appointmentId,
                $isOrderReady
            );
        }

        if (!is_null($isCharteringConfirmationSent)) {
            $appointment = $this->appointmentRepository->setCharteringConfirmationSent(
                $appointmentId,
                $isCharteringConfirmationSent
            );
        }

        if (!is_null($deliveryNoteNumber)) {
            $supplier = $appointment->getSupplier();
            $supplierId = $supplier?->id;

            // Check if the delivery note number is available
            if (
                $deliveryNoteNumber !== "" &&
                !$this->isDeliveryNoteNumberAvailable($deliveryNoteNumber, $supplierId, $appointmentId)
            ) {
                throw new ClientException(
                    "Le numéro de BL {$deliveryNoteNumber} est déjà utilisé pour {$supplier?->getShortName()}."
                );
            }

            $appointment = $this->appointmentRepository->setDeliveryNoteNumber(
                $appointmentId,
                $deliveryNoteNumber
            );
        }

        if ($setArrivalTime) {
            $arrivalTime = new \DateTimeImmutable();

            $appointment = $this->appointmentRepository->setArrivalTime(
                $appointmentId,
                $arrivalTime
            );

            // If the supplier has unique delivery note numbers
            // and the loading place is the agency
            // and the delivery note number is not set
            // set the next delivery note number
            $supplierId = $appointment->getSupplier()?->id;

            if (
                $supplierId
                && $this->isSupplierWithUniqueDeliveryNoteNumbers($supplierId)
                && $appointment->getLoadingPlace()?->id === 1
                && $appointment->getDeliveryNoteNumber() === ""
                && $nextDeliveryNoteNumber = $this->getNextDeliveryNoteNumber($supplierId)
            ) {
                $appointment = $this->appointmentRepository->setDeliveryNoteNumber(
                    $appointmentId,
                    $nextDeliveryNoteNumber
                );
            }
        }

        if ($setDepartureTime) {
            $departureTime = new \DateTimeImmutable();

            $appointment = $this->appointmentRepository->setDepartureTime(
                $appointmentId,
                $departureTime
            );
        }

        if (!\is_null($dispatch)) {
            $dispatchItems = array_map(
                // @phpstan-ignore argument.type
                function (array $dispatchRaw) {
                    $dispatchItem = $this->makeTimberDispatchItemFromFormData(new ArrayHandler($dispatchRaw));
                    $dispatchItem->validate();
                    return $dispatchItem;
                },
                $dispatch
            );
            $appointment = $this->appointmentRepository->updateDispatchForAppointment(
                $appointmentId,
                $dispatchItems
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
        $this->appointmentRepository->deleteAppointment($id);
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
                ->appointmentRepository
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
        return $this->appointmentRepository->fetchTransportSuggestions($loadingPlaceId, $deliveryPlaceId);
    }

    public function getStats(TimberFilterDTO $filter): TimberStatsDTO
    {
        return $this->appointmentRepository->getStats($filter);
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

        return $this->appointmentRepository->isDeliveryNoteNumberAvailable(
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
            $dto = new SupplierWithUniqueDeliveryNoteNumber();
            $dto->id = $info['supplierId'];
            $dto->regexp = $info['regexp'];

            $dtoArray[$info['supplierId']] = $dto;
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

        $lastDeliveryNoteNumber = $this->appointmentRepository->getLastDeliveryNoteNumber($supplierDto);

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
        return $this->appointmentRepository->getPdfAppointments(
            $supplier,
            $startDate,
            $endDate
        );
    }

    // ========
    // Dispatch
    // ========

    /**
     * Creates a bulk dispatch from database data.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param TimberDispatchArray $rawData
     * 
     * @return TimberDispatchItem 
     * 
     * @throws DBException 
     */
    public function makeTimberDispatchItemFromDatabase(array $rawData): TimberDispatchItem
    {
        $rawDataAH = new ArrayHandler($rawData);

        $dispatch = (new TimberDispatchItem())
            ->setStaff($this->stevedoringService->getStaff($rawDataAH->getInt('staff_id')))
            ->setDate($rawDataAH->getDatetime('date'))
            ->setRemarks($rawDataAH->getString('remarks'));

        return $dispatch;
    }

    public function makeTimberDispatchItemFromFormData(ArrayHandler $requestBody): TimberDispatchItem
    {
        $dispatch = (new TimberDispatchItem())
            ->setStaff($this->stevedoringService->getStaff($requestBody->getInt('staffId')))
            ->setDate($requestBody->getDatetime('date'))
            ->setRemarks($requestBody->getString('remarks'));

        return $dispatch;
    }
}
