<?php

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\ServerException;
use App\DTO\TimberRegistryEntryDTO;
use App\Entity\ThirdParty;
use App\Entity\Timber\TimberAppointment;
use App\Repository\TimberAppointmentRepository;

class TimberService
{
    private TimberAppointmentRepository $timberAppointmentRepository;

    public function __construct()
    {
        $this->timberAppointmentRepository = new TimberAppointmentRepository();
    }

    public function makeTimberAppointmentFromDatabase(array $rawData): TimberAppointment
    {
        $thirdPartyService = new ThirdPartyService();

        $appointment = (new TimberAppointment())
            ->setId($rawData["id"] ?? null)
            ->setOnHold($rawData["attente"] ?? false)
            ->setDate($rawData["date_rdv"] ?? null)
            ->setArrivalTime($rawData["heure_arrivee"] ?? null)
            ->setDepartureTime($rawData["heure_depart"] ?? null)
            ->setSupplier($thirdPartyService->getThirdParty($rawData["fournisseur"] ?? null))
            ->setLoadingPlace($thirdPartyService->getThirdParty($rawData["chargement"] ?? null))
            ->setDeliveryPlace($thirdPartyService->getThirdParty($rawData["livraison"] ?? null))
            ->setCustomer($thirdPartyService->getThirdParty($rawData["client"] ?? null))
            ->setCarrier($thirdPartyService->getThirdParty($rawData["transporteur"] ?? null))
            ->setTransportBroker($thirdPartyService->getThirdParty($rawData["affreteur"] ?? null))
            ->setReady($rawData["commande_prete"] ?? false)
            ->setCharteringConfirmationSent($rawData["confirmation_affretement"] ?? false)
            ->setDeliveryNoteNumber($rawData["numero_bl"] ?? "")
            ->setPublicComment($rawData["commentaire_public"] ?? "")
            ->setPrivateComment($rawData["commentaire_cache"] ?? "");

        return $appointment;
    }

    public function makeTimberAppointmentFromForm(array $rawData): TimberAppointment
    {
        $thirdPartyService = new ThirdPartyService();

        $appointment = (new TimberAppointment())
            ->setId($rawData["id"] ?? null)
            ->setOnHold($rawData["attente"] ?? false)
            ->setDate($rawData["date_rdv"] ?? null)
            ->setArrivalTime($rawData["heure_arrivee"] ?? null)
            ->setDepartureTime($rawData["heure_depart"] ?? null)
            ->setSupplier($thirdPartyService->getThirdParty($rawData["fournisseur"] ?? null))
            ->setLoadingPlace($thirdPartyService->getThirdParty($rawData["chargement"] ?? null))
            ->setDeliveryPlace($thirdPartyService->getThirdParty($rawData["livraison"] ?? null))
            ->setCustomer($thirdPartyService->getThirdParty($rawData["client"] ?? null))
            ->setCarrier($thirdPartyService->getThirdParty($rawData["transporteur"] ?? null))
            ->setTransportBroker($thirdPartyService->getThirdParty($rawData["affreteur"] ?? null))
            ->setReady($rawData["commande_prete"] ?? false)
            ->setCharteringConfirmationSent($rawData["confirmation_affretement"] ?? false)
            ->setDeliveryNoteNumber($rawData["numero_bl"] ?? "")
            ->setPublicComment($rawData["commentaire_public"] ?? "")
            ->setPrivateComment($rawData["commentaire_cache"] ?? "");

        return $appointment;
    }

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
     * @param array $query Paramètres de recherche.
     * 
     * @return Collection<TimberAppointment> Tous les RDV récupérés.
     */
    public function getAppointments(array $query): Collection
    {
        return $this->timberAppointmentRepository->getAppointments($query);
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
        return $this->timberAppointmentRepository->getAppointment($id);
    }

    /**
     * Crée un RDV bois.
     * 
     * @param array $input Eléments du RDV à créer
     * 
     * @return TimberAppointment Rendez-vous créé
     */
    public function createAppointment(array $input): TimberAppointment
    {
        $rdv = $this->makeTimberAppointmentFromForm($input);

        return $this->timberAppointmentRepository->createAppointment($rdv);
    }

    /**
     * Met à jour un RDV bois.
     * 
     * @param int   $id ID du RDV à modifier
     * @param array $input  Eléments du RDV à modifier
     * 
     * @return TimberAppointment RDV modifié
     */
    public function updateAppointment($id, array $input): TimberAppointment
    {
        $rdv = $this->makeTimberAppointmentFromForm($input)->setId($id);

        return $this->timberAppointmentRepository->updateAppointment($rdv);
    }

    /**
     * Met à jour certaines proriétés d'un RDV bois.
     * 
     * @param ?int   $id   id du RDV à modifier.
     * @param array $input Données à modifier.
     * 
     * @return TimberAppointment RDV modifié.
     * 
     * @throws ClientException Si l'identifiant du RDV n'est pas fourni.
     */
    public function patchAppointment(?int $id, array $input): TimberAppointment
    {
        if (!$id) {
            throw new ClientException("L'identifiant du RDV est requis pour effectuer une modification.");
        }

        if (isset($input["commande_prete"])) {
            return $this->timberAppointmentRepository->setOrderReady(
                $id,
                (bool) $input["commande_prete"]
            );
        }

        if (isset($input["confirmation_affretement"])) {
            return $this->timberAppointmentRepository->setCharteringConfirmationSent(
                $id,
                (bool) $input["confirmation_affretement"]
            );
        }

        if (isset($input["numero_bl"])) {
            return $this->timberAppointmentRepository->setDeliveryNoteNumber($id, $input);
        }

        if (isset($input["heure_arrivee"])) {
            return $this->timberAppointmentRepository->setArrivalTime($id);
        }

        if (isset($input["heure_depart"])) {
            return $this->timberAppointmentRepository->setDepartureTime($id);
        }
    }

    /**
     * Supprime un RDV bois.
     * 
     * @param int $id ID du RDV à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteAppointment(int $id): bool
    {
        return $this->timberAppointmentRepository->deleteAppointment($id);
    }

    /**
     * Renvoie l'extrait du registre d'affrètement avec le filtre appliqué.
     * 
     * @param array $filtre 
     */
    public function getChateringRegister(array $filtre): string
    {
        $output = fopen("php://temp/maxmemory:" . (5 * 1024 * 1024), "r+");

        if (!$output) {
            throw new ServerException("Erreur création fichier CSV");
        }

        try {
            $registryEntries = $this->timberAppointmentRepository->getCharteringRegister($filtre);

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

    public function getTransportSuggestions(int $loadingPlaceId, int $deliveryPlaceId): array
    {
        return $this->timberAppointmentRepository->getTransportSuggestions($loadingPlaceId, $deliveryPlaceId);
    }

    public function getStats(array $filtre): array
    {
        return $this->timberAppointmentRepository->getStats($filtre);
    }

    public function isDeliveryNoteNumberAvailable(
        string $deliveryNoteNumber,
        int $supplierId,
        ?int $appointmentId = null,
    ): bool {
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
     * @return array{supplierId: int, regexp: string} Fournisseurs avec des numéros de BL uniques.
     */
    public function getSuppliersWithUniqueDeliveryNoteNumbers(): array
    {
        return [
            292 => '\d{6}', // Stora Enso
        ];
    }

    public function isSupplierWithUniqueDeliveryNoteNumbers(int $supplierId): bool
    {
        $suppliersWithUniqueDeliveryNoteNumber = $this->getSuppliersWithUniqueDeliveryNoteNumbers();

        return array_key_exists($supplierId, $suppliersWithUniqueDeliveryNoteNumber);
    }

    public function getNextDeliveryNoteNumber(int $supplierId): ?string
    {
        if (!$this->isSupplierWithUniqueDeliveryNoteNumbers($supplierId)) {
            return null;
        }

        $lastDeliveryNoteNumber = $this->timberAppointmentRepository->getLastDeliveryNoteNumber($supplierId);

        if (!$lastDeliveryNoteNumber) {
            return null;
        }

        $nextDeliveryNoteNumber = $lastDeliveryNoteNumber + 1;

        return $nextDeliveryNoteNumber;
    }
}
