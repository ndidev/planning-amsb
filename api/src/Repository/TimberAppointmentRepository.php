<?php

// Path: api/src/Repository/TimberAppointmentRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\Filter\TimberFilterDTO;
use App\DTO\SupplierWithUniqueDeliveryNoteNumber;
use App\DTO\TimberRegistryEntryDTO;
use App\DTO\TimberStatsDTO;
use App\DTO\TimberTransportSuggestionsDTO;
use App\Entity\ThirdParty\ThirdParty;
use App\Entity\Timber\TimberAppointment;
use App\Entity\Timber\TimberDispatchItem;
use App\Service\TimberService;

/**
 * @phpstan-import-type TimberAppointmentArray from \App\Entity\Timber\TimberAppointment
 * @phpstan-import-type TimberRegistryEntryArray from \App\DTO\TimberRegistryEntryDTO
 * @phpstan-import-type TimberDispatchArray from \App\Entity\Timber\TimberDispatchItem
 * @phpstan-import-type TimberPdfAppointments from \App\DTO\PDF\TimberPDF
 */
final class TimberAppointmentRepository extends Repository
{
    public function __construct(private TimberService $timberService) {}

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function appointmentExists(int $id): bool
    {
        return $this->mysql->exists("bois_planning", $id);
    }

    /**
     * Récupère tous les RDV bois.
     * 
     * @param TimberFilterDTO $filter Paramètres de recherche.
     * 
     * @return Collection<TimberAppointment> Tous les RDV récupérés
     */
    public function fetchAppointments(TimberFilterDTO $filter): Collection
    {
        $sqlFilter = $filter->getSqlFilter();

        $statement =
            "SELECT
                id,
                attente,
                date_rdv,
                heure_arrivee,
                heure_depart,
                confirmation_affretement,
                commande_prete,
                numero_bl,
                commentaire_public,
                commentaire_cache,
                client,
                chargement,
                livraison,
                affreteur,
                fournisseur,
                transporteur
            FROM bois_planning
            WHERE 
            (
                (date_rdv BETWEEN :startDate AND :endDate)
                OR date_rdv IS NULL
                OR attente = 1
            )
            $sqlFilter
            ORDER BY date_rdv";

        $requete = $this->mysql->prepare($statement);

        $requete->execute([
            "startDate" => $filter->getSqlStartDate(),
            "endDate" => $filter->getSqlEndDate(),
        ]);

        /** @phpstan-var TimberAppointmentArray[] */
        $appointmentsRaw = $requete->fetchAll();

        $appointments = \array_map(
            fn(array $appointmentRaw) => $this->timberService->makeTimberAppointmentFromDatabase($appointmentRaw),
            $appointmentsRaw
        );

        // Get all dispatch
        foreach ($appointments as $appointment) {
            /** @var int $id */
            $id = $appointment->id;
            $dispatch = $this->fetchDispatchForAppointment($id);
            $appointment->dispatch = $dispatch;
        }

        return new Collection($appointments);
    }

    /**
     * Récupère un RDV bois.
     * 
     * @param int $id ID du RDV à récupérer
     * 
     * @return ?TimberAppointment Rendez-vous récupéré
     */
    public function fetchAppointment(int $id): ?TimberAppointment
    {
        $statement =
            "SELECT
                id,
                attente,
                date_rdv,
                heure_arrivee,
                heure_depart,
                confirmation_affretement,
                commande_prete,
                numero_bl,
                commentaire_public,
                commentaire_cache,
                client,
                chargement,
                livraison,
                affreteur,
                fournisseur,
                transporteur
            FROM bois_planning
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute(["id" => $id]);
        $appointmentRaw = $request->fetch();

        if (!\is_array($appointmentRaw)) return null;

        /** @phpstan-var TimberAppointmentArray $appointmentRaw */

        $appointment = $this->timberService->makeTimberAppointmentFromDatabase($appointmentRaw);

        // Get dispatch
        $dispatch = $this->fetchDispatchForAppointment($id);
        $appointment->dispatch = $dispatch;

        return $appointment;
    }

    /**
     * Crée un RDV bois.
     * 
     * @param TimberAppointment $appointment RDV à créer.
     * 
     * @return TimberAppointment Rendez-vous créé.
     * 
     * @throws DBException Erreur lors de la création du RDV.
     */
    public function createAppointment(TimberAppointment $appointment): TimberAppointment
    {
        $statement = "INSERT INTO bois_planning
            SET
                attente = :isOnHold,
                date_rdv = :date,
                heure_arrivee = :arrivalTime,
                heure_depart = :departureTime,
                fournisseur = :supplierId,
                chargement = :loadingPlaceId,
                client = :customerId,
                livraison = :deliveryPlaceId,
                transporteur = :carrierId,
                affreteur = :brokerId,
                commande_prete = :isReady,
                confirmation_affretement = :isCharteringConfirmationSent,
                numero_bl = :deliveryNoteNumber,
                commentaire_public = :publicComment,
                commentaire_cache = :privateComment
            ";

        try {
            $request = $this->mysql->prepare($statement);

            $this->mysql->beginTransaction();
            $request->execute([
                'isOnHold' => (int) $appointment->isOnHold,
                'date' => $appointment->sqlDate,
                'arrivalTime' => $appointment->sqlArrivalTime,
                'departureTime' => $appointment->sqlDepartureTime,
                'supplierId' => $appointment->supplier?->id,
                'loadingPlaceId' => $appointment->loadingPlace?->id,
                'customerId' => $appointment->customer?->id,
                'deliveryPlaceId' => $appointment->deliveryPlace?->id,
                'carrierId' => $appointment->carrier?->id,
                'brokerId' => $appointment->transportBroker?->id,
                'isReady' => (int) $appointment->isReady,
                'isCharteringConfirmationSent' => (int) $appointment->isCharteringConfirmationSent,
                'deliveryNoteNumber' => $appointment->deliveryNoteNumber,
                'publicComment' => $appointment->publicComment,
                'privateComment' => $appointment->privateComment,
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            $this->insertDispatchForAppointment($lastInsertId, $appointment->dispatch);

            $this->mysql->commit();

            /** @var TimberAppointment */
            $newAppointment = $this->fetchAppointment($lastInsertId);

            return $newAppointment;
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();
            throw new DBException("Erreur lors de la création du RDV", previous: $e);
        }
    }

    /**
     * Met à jour un RDV bois.
     * 
     * @param TimberAppointment $appointment RDV à modifier
     * 
     * @return TimberAppointment RDV modifié
     */
    public function updateAppointment(TimberAppointment $appointment): TimberAppointment
    {
        $statement = "UPDATE bois_planning
            SET
                attente = :isOnHold,
                date_rdv = :date,
                heure_arrivee = :arrivalTime,
                heure_depart = :departureTime,
                fournisseur = :supplierId,
                chargement = :loadingPlaceId,
                client = :customerId,
                livraison = :deliveryPlaceId,
                transporteur = :carrierId,
                affreteur = :brokerId,
                commande_prete = :isReady,
                confirmation_affretement = :isCharteringConfirmationSent,
                numero_bl = :deliveryNoteNumber,
                commentaire_public = :publicComment,
                commentaire_cache = :privateComment
            WHERE id = :id";

        try {
            $request = $this->mysql->prepare($statement);
            $request->execute([
                'isOnHold' => (int) $appointment->isOnHold,
                'date' => $appointment->sqlDate,
                'arrivalTime' => $appointment->sqlArrivalTime,
                'departureTime' => $appointment->sqlDepartureTime,
                'supplierId' => $appointment->supplier?->id,
                'loadingPlaceId' => $appointment->loadingPlace?->id,
                'customerId' => $appointment->customer?->id,
                'deliveryPlaceId' => $appointment->deliveryPlace?->id,
                'carrierId' => $appointment->carrier?->id,
                'brokerId' => $appointment->transportBroker?->id,
                'isReady' => (int) $appointment->isReady,
                'isCharteringConfirmationSent' => (int) $appointment->isCharteringConfirmationSent,
                'deliveryNoteNumber' => $appointment->deliveryNoteNumber,
                'publicComment' => $appointment->publicComment,
                'privateComment' => $appointment->privateComment,
                'id' => $appointment->id,
            ]);

            /** @var int */
            $id = $appointment->id;

            $this->deleteDispatchForAppointment($id);
            $this->insertDispatchForAppointment($id, $appointment->dispatch);

            /** @var TimberAppointment */
            $updatedAppointment = $this->fetchAppointment($id);

            return $updatedAppointment;
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la mise à jour du RDV", previous: $e);
        }
    }

    /**
     * Met à jour l'état d'attente d'un RDV.
     * 
     * @param int  $id     ID du RDV à modifier.
     * @param bool $status Statut d'attente.
     * 
     * @return TimberAppointment RDV modifié.
     */
    public function setAppointmentOnHold(int $id, bool $status): TimberAppointment
    {
        $this->mysql
            ->prepare(
                "UPDATE bois_planning
                SET attente = :attente
                WHERE id = :id"
            )
            ->execute([
                'attente' => (int) $status,
                'id' => $id,
            ]);

        /** @var TimberAppointment */
        $updatedAppointment = $this->fetchAppointment($id);

        return $updatedAppointment;
    }

    /**
     * Met à jour l'état de préparation d'une commande.
     * 
     * @param int  $id     ID du RDV à modifier.
     * @param bool $status Statut de la commande.
     * 
     * @return TimberAppointment RDV modifié.
     */
    public function setOrderReady(int $id, bool $status): TimberAppointment
    {
        $this->mysql
            ->prepare(
                "UPDATE bois_planning
                SET commande_prete = :commande_prete
                WHERE id = :id"
            )
            ->execute([
                'commande_prete' => (int) $status,
                'id' => $id,
            ]);

        /** @var TimberAppointment */
        $updatedAppointment = $this->fetchAppointment($id);

        return $updatedAppointment;
    }

    /**
     * Met à jour l'état de confirmation d'affrètement.
     * 
     * @param int  $id     ID du RDV à modifier.
     * @param bool $status Statut de la confirmation d'affrètement.
     * 
     * @return TimberAppointment RDV modifié.
     */
    public function setCharteringConfirmationSent(int $id, bool $status): TimberAppointment
    {
        $this->mysql
            ->prepare(
                "UPDATE bois_planning
                SET confirmation_affretement = :status
                WHERE id = :id"
            )
            ->execute([
                'status' => (int) $status,
                'id' => $id,
            ]);

        /** @var TimberAppointment */
        $updatedAppointment = $this->fetchAppointment($id);

        return $updatedAppointment;
    }

    /**
     * Définit l'heure d'arrivée pour un rendez-vous bois.
     * 
     * @param int                $id          ID du RDV à modifier
     * @param \DateTimeInterface $arrivalTime Heure d'arrivée.
     * 
     * @return TimberAppointment RDV modifié
     */
    public function setArrivalTime(int $id, \DateTimeInterface $arrivalTime): TimberAppointment
    {
        $this->mysql
            ->prepare("UPDATE bois_planning SET heure_arrivee = :time WHERE id = :id")
            ->execute([
                'time' => $arrivalTime->format('H:i:s'),
                'id' => $id
            ]);

        /** @var TimberAppointment */
        $updatedAppointment = $this->fetchAppointment($id);

        return $updatedAppointment;
    }

    /**
     * Définit l'heure de départ pour un rendez-vous bois.
     *
     * @param int                $id            L'ID du rendez-vous.
     * @param \DateTimeInterface $departureTime L'heure de départ.
     * 
     * @return TimberAppointment L'objet de rendez-vous mis à jour.
     */
    public function setDepartureTime(int $id, \DateTimeInterface $departureTime): TimberAppointment
    {
        $this->mysql
            ->prepare("UPDATE bois_planning SET heure_depart = :time WHERE id = :id")
            ->execute([
                'time' => $departureTime->format('H:i:s'),
                'id' => $id
            ]);

        /** @var TimberAppointment */
        $updatedAppointment = $this->fetchAppointment($id);

        return $updatedAppointment;
    }

    public function getLastDeliveryNoteNumber(
        SupplierWithUniqueDeliveryNoteNumber $supplierDto
    ): ?string {
        // Dernier numéro de BL :
        // - enregistrement des 10 derniers numéros dans un tableau
        // - tri du tableau
        // - récupération du numéro le plus élevé
        // Ceci permet de prendre en compte les cas où le dernier numéro
        // renseigné n'est pas le plus haut numériquement
        // Permet aussi de prendre en compte les éventuels bons sans numéro "numérique"
        $previousDeliveryNotesRequest = $this->mysql->prepare(
            "SELECT numero_bl
                FROM bois_planning
                WHERE fournisseur = :supplierId
                AND numero_bl != ''
                ORDER BY
                    date_rdv DESC,
                    heure_arrivee DESC,
                    numero_bl DESC
                LIMIT 10"
        );

        $previousDeliveryNotesRequest->execute(["supplierId" => $supplierDto->id]);

        /** @var string[] */
        $previousDeliveryNotesResponse = $previousDeliveryNotesRequest->fetchAll(\PDO::FETCH_COLUMN);

        /** @var string[] */
        $previousDeliveryNotesNumbers = [];

        foreach ($previousDeliveryNotesResponse as $deliveryNoteNumber) {
            // Si le dernier numéro de BL est composé (ex: "200101 + 200102")
            // alors séparation/tri de la chaîne de caractères puis récupération du numéro le plus élevé
            $matches = NULL; // Tableau pour récupérer les numéros de BL
            $regexp = $supplierDto->regexp; // Récupération de l'expression régulière
            // \preg_match_all("/\d{6}/", $deliveryNoteNumber["numero_bl"], $matches); // Filtre sur les numéros valides (6 chiffres)
            \preg_match_all("/$regexp/", $deliveryNoteNumber, $matches); // Filtre sur les numéros valides (6 chiffres)
            $matches = $matches[0]; // Extraction des résultats
            sort($matches); // Tri des numéros
            $previousDeliveryNotesNumbers[] = \array_pop($matches); // Récupération du numéro le plus élevé
        }

        // Tri des 10 derniers numéros de BL puis récupération du plus élevé
        sort($previousDeliveryNotesNumbers);
        $previousDeliveryNoteNumber = \array_pop($previousDeliveryNotesNumbers);

        return $previousDeliveryNoteNumber;
    }

    public function isDeliveryNoteNumberAvailable(
        string $deliveryNoteNumber,
        int $supplierId,
        ?int $currentAppointmentId,
    ): bool {
        $request = $this->mysql->prepare(
            "SELECT COUNT(id), id
            FROM bois_planning
            WHERE numero_bl LIKE CONCAT('%', :deliveryNoteNumber, '%')
            AND fournisseur = :supplierId"
        );

        $request->execute([
            "deliveryNoteNumber" => $deliveryNoteNumber,
            "supplierId" => $supplierId,
        ]);

        $deliveryNoteNumberIsAvailable = true;

        $deliveryNoteData = $request->fetch(\PDO::FETCH_NUM);

        if (\is_array($deliveryNoteData) && count($deliveryNoteData) === 2) {
            /** @var array{0: int, 1: int} $deliveryNoteData */
            [$deliveryNoteNumberCount, $id] = $deliveryNoteData;

            if ($deliveryNoteNumberCount > 0 && $id !== $currentAppointmentId) {
                $deliveryNoteNumberIsAvailable = false;
            }
        }

        return $deliveryNoteNumberIsAvailable;
    }

    /**
     * Définit l'heure d'arrivée pour un rendez-vous bois.
     * 
     * @param int    $id                 ID du rendez-vous.
     * @param string $deliveryNoteNumber Numéro BL.
     * 
     * @return TimberAppointment Rendez-vous mis à jour.
     */
    public function setDeliveryNoteNumber(int $id, string $deliveryNoteNumber): TimberAppointment
    {
        try {
            $this->mysql
                ->prepare(
                    "UPDATE bois_planning
                     SET numero_bl = :deliveryNoteNumber
                     WHERE id = :id"
                )
                ->execute([
                    'deliveryNoteNumber' => $deliveryNoteNumber,
                    'id' => (int) $id
                ]);

            /** @var TimberAppointment */
            $updatedAppointment = $this->fetchAppointment($id);

            return $updatedAppointment;
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la mise à jour du numéro de BL", previous: $e);
        }
    }

    /**
     * Supprime un RDV bois.
     * 
     * @param int $id ID du RDV à supprimer.
     * 
     * @return void
     * 
     * @throws DBException Erreur lors de la suppression.
     */
    public function deleteAppointment(int $id): void
    {
        try {
            $request = $this->mysql->prepare("DELETE FROM bois_planning WHERE id = :id");
            $success = $request->execute(["id" => $id]);

            if (!$success) {
                throw new DBException("Erreur lors de la suppression");
            }
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la suppression", previous: $e);
        }
    }

    /**
     * Renvoie l'extrait du registre d'affrètement avec le filtre appliqué.
     *
     * @param \DateTimeInterface $startDate Date de début du filtre.
     * @param \DateTimeInterface $endDate   Date de fin du filtre.
     * 
     * @return TimberRegistryEntryDTO[] Extrait du registre d'affrètement.
     */
    public function getCharteringRegistryEntries(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): array {
        $statement =
            "SELECT
                p.date_rdv,
                f.nom_court AS fournisseur,
                c.nom_court AS chargement_nom,
                c.ville AS chargement_ville,
                cpays.nom AS chargement_pays,
                l.nom_court AS livraison_nom,
                l.cp AS livraison_cp,
                l.ville AS livraison_ville,
                lpays.nom AS livraison_pays,
                p.numero_bl,
                t.nom_court AS transporteur
            FROM bois_planning p
            LEFT JOIN tiers AS c ON p.chargement = c.id
            LEFT JOIN tiers AS l ON p.livraison = l.id
            LEFT JOIN tiers AS a ON p.affreteur = a.id
            LEFT JOIN tiers AS f ON p.fournisseur = f.id
            LEFT JOIN tiers AS t ON p.transporteur = t.id
            LEFT JOIN utils_pays cpays ON c.pays = cpays.iso
            LEFT JOIN utils_pays lpays ON l.pays = lpays.iso
            WHERE a.lie_agence = 1
                AND (date_rdv BETWEEN :startDate AND :endDate)
                AND attente = 0
            ORDER BY
            date_rdv,
            numero_bl";

        $request = $this->mysql->prepare($statement);

        $request->execute([
            "startDate" => $startDate->format('Y-m-d'),
            "endDate" => $endDate->format('Y-m-d'),
        ]);

        /** @phpstan-var TimberRegistryEntryArray[] */
        $entriesRaw = $request->fetchAll();

        $timberService = new TimberService();

        $registryEntries = \array_map(
            fn(array $entryRaw) => $timberService->makeTimberRegisterEntryDTO($entryRaw),
            $entriesRaw
        );

        return $registryEntries;
    }

    public function fetchTransportSuggestions(
        int $loadingPlaceId,
        int $deliveryPlaceId
    ): TimberTransportSuggestionsDTO {
        // Récupérer les infos du lieu de chargement et de livraison
        $locationStatement =
            "SELECT
                id,
                SUBSTRING(cp, 1, 2) as cp,
                pays
            FROM tiers
            WHERE id = :id";

        $locationRequest = $this->mysql->prepare($locationStatement);

        $locationRequest->execute(["id" => $loadingPlaceId]);
        /**
         * @var array{id: int, cp: string, pays: string} $loadingPlaceData
         */
        $loadingPlaceData = $locationRequest->fetch();

        $locationRequest->execute(["id" => $deliveryPlaceId]);
        /**
         * @var array{id: int, cp: string, pays: string} $deliveryPlaceData
         */
        $deliveryPlaceData = $locationRequest->fetch();


        // Récupérer les transporteurs ayant fait des transports identiques ou similaires
        $transportStatement =
            "SELECT
                COUNT(id) as transports,
                transporteur_nom as nom,
                transporteur_telephone as telephone
            FROM (
                SELECT
                p.id,
                p.date_rdv,
                p.transporteur,
                t.nom_court as transporteur_nom,
                t.telephone as transporteur_telephone,
                p.chargement,
                c.nom_court as c_nom,
                SUBSTRING(c.cp, 1, 2) as c_cp,
                c.pays as c_pays,
                p.livraison,
                l.nom_court as l_nom,
                SUBSTRING(l.cp, 1, 2) as l_cp,
                l.pays as l_pays
                FROM bois_planning p
                INNER JOIN tiers t ON p.transporteur = t.id
                INNER JOIN tiers c ON p.chargement = c.id
                INNER JOIN tiers l ON p.livraison = l.id
                WHERE
                    t.actif = 1
                AND t.non_modifiable = 0
                HAVING
                    (
                        (p.chargement = :loadingPlaceId)
                    OR (:loadingPlaceCountry = 'FR' AND c_cp = :loadingPlacePostCode)
                    OR (NOT :loadingPlaceCountry = 'FR' AND c_pays = :loadingPlaceCountry)
                    )
                    AND 
                    (
                        (p.livraison = :deliveryPlaceId)
                    OR (:deliveryPlaceCountry = 'FR' AND l_cp = :deliveryPlacePostCode)
                    OR (NOT :deliveryPlaceCountry = 'FR' AND l_pays = :deliveryPlaceCountry)
                    )
            ) AS transports_corrspondants
            GROUP BY transporteur_nom
            ORDER BY transports DESC
            LIMIT 10";

        $transportRequest = $this->mysql->prepare($transportStatement);

        $transportRequest->execute([
            "loadingPlaceId" => $loadingPlaceId,
            "loadingPlacePostCode" => $loadingPlaceData["cp"],
            "loadingPlaceCountry" => $loadingPlaceData["pays"],
            "deliveryPlaceId" => $deliveryPlaceId,
            "deliveryPlacePostCode" => $deliveryPlaceData["cp"],
            "deliveryPlaceCountry" => $deliveryPlaceData["pays"],
        ]);

        /**
         * @var list<array{transports: int, nom: string, telephone: string}> $transportData
         */
        $transportData = $transportRequest->fetchAll();

        $suggestions = new TimberTransportSuggestionsDTO(
            $loadingPlaceData,
            $deliveryPlaceData,
            $transportData,
        );

        return $suggestions;
    }

    /**
     * Récupère les stats bois.
     * 
     * @param TimberFilterDTO $filter
     * 
     * @return TimberStatsDTO
     */
    public function getStats(TimberFilterDTO $filter): TimberStatsDTO
    {
        $sqlFilter = $filter->getSqlFilter();

        $appointmentsStatement =
            "SELECT date_rdv
            FROM bois_planning
            WHERE date_rdv BETWEEN :startDate AND :endDate
            AND attente = 0
            $sqlFilter";


        $appointmentsRequest = $this->mysql->prepare($appointmentsStatement);

        $appointmentsRequest->execute([
            "startDate" => $filter->getSqlStartDate(),
            "endDate" => $filter->getSqlEndDate(),
        ]);

        /** @var string[] */
        $dates = $appointmentsRequest->fetchAll(\PDO::FETCH_COLUMN);

        $stats = new TimberStatsDTO($dates);

        return $stats;
    }

    /**
     * Récupère les RDVs bois à exporter en PDF.
     * 
     * @param ThirdParty         $supplier  Fournisseur des RDVs.
     * @param \DateTimeInterface $startDate Date de début des RDVs.
     * @param \DateTimeInterface $endDate   Date de fin des RDVs.
     * 
     * @return TimberPdfAppointments RDVs bois à exporter.
     */
    public function getPdfAppointments(
        ThirdParty $supplier,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): array {
        $scheduledStatement =
            "SELECT
                pl.date_rdv,
                pl.numero_bl,
                pl.commentaire_public,
                pl.chargement,
                pl.client,
                pl.livraison,
                pl.affreteur
            FROM bois_planning pl
            LEFT JOIN tiers c ON c.id = pl.client
            WHERE date_rdv
            BETWEEN :startDate
            AND :endDate
            AND attente = 0
            AND fournisseur = :supplierId
            ORDER BY
                date_rdv,
                numero_bl,
                c.nom_court";

        $onHoldStatement =
            "SELECT
                pl.date_rdv,
                pl.commentaire_public,
                pl.chargement,
                pl.client,
                pl.livraison
            FROM bois_planning pl
            LEFT JOIN tiers c ON c.id = pl.client
            WHERE attente = 1
            AND fournisseur = :supplierId
            ORDER BY
                -date_rdv DESC,
                c.nom_court";

        $scheduledRequest = $this->mysql->prepare($scheduledStatement);
        $scheduledRequest->execute([
            "supplierId" => $supplier->id,
            "startDate" => $startDate->format('Y-m-d'),
            "endDate" => $endDate->format('Y-m-d'),
        ]);
        /** @var TimberAppointmentArray[] */
        $scheduledAppointmentsRaw = $scheduledRequest->fetchAll();

        $onHoldRequest = $this->mysql->prepare($onHoldStatement);
        $onHoldRequest->execute(["supplierId" => $supplier->id]);
        /** @var TimberAppointmentArray[] */
        $onHoldAppointmentsRaw = $onHoldRequest->fetchAll();

        $timberService = new TimberService();

        $scheduledAppointments = \array_map(
            fn(array $appointmentRaw) => $timberService->makeTimberAppointmentFromDatabase($appointmentRaw),
            $scheduledAppointmentsRaw
        );

        $onHoldAppointments = \array_map(
            fn(array $appointmentRaw) => $timberService->makeTimberAppointmentFromDatabase($appointmentRaw),
            $onHoldAppointmentsRaw
        );

        return [
            "non_attente" => new Collection($scheduledAppointments),
            "attente" => new Collection($onHoldAppointments),
        ];
    }

    // ========
    // Dispatch
    // ========

    /**
     * Fetch the dispatch for an appointment.
     * 
     * @param int $id Appointment ID.
     * 
     * @return TimberDispatchItem[]
     * 
     * @throws DBException
     */
    public function fetchDispatchForAppointment(int $id): array
    {
        $statement =
            "SELECT
                staff_id,
                `date`,
                remarks
            FROM stevedoring_timber_dispatch
            WHERE appointment_id = :id";

        try {
            /** @var TimberDispatchArray[] */
            $dispatchesRaw = $this->mysql->prepareAndExecute($statement, ["id" => $id])->fetchAll();

            $dispatches = \array_map(
                fn($dispatchRaw) => $this->timberService->makeTimberDispatchItemFromDatabase($dispatchRaw),
                $dispatchesRaw
            );

            return $dispatches;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer le dispatch pour le RDV {$id}", previous: $e);
        }
    }

    /**
     * Insert the dispatch for an appointment.
     * 
     * @param int $id Appointment ID.
     * @param TimberDispatchItem[] $dispatch 
     * 
     * @return void 
     * 
     * @throws DBException 
     */
    public function insertDispatchForAppointment(int $id, array $dispatch): void
    {
        if (\count($dispatch) === 0) return;

        $statement =
            "INSERT INTO stevedoring_timber_dispatch
             SET
                appointment_id = :id,
                `date` = :date,
                staff_id = :staffId,
                remarks = :remarks";

        try {
            $request = $this->mysql->prepare($statement);

            foreach ($dispatch as $dispatchItem) {
                $request->execute([
                    'id' => $id,
                    'staffId' => $dispatchItem->staff?->id,
                    'date' => $dispatchItem->date?->format('Y-m-d'),
                    'remarks' => $dispatchItem->remarks,
                ]);
            }
        } catch (\PDOException $e) {
            throw new DBException("Impossible d'enregistrer le dispatch", previous: $e);
        }
    }

    /**
     * Delete the dispatch for an appointment.
     * 
     * @param int $id Appointment ID.
     * 
     * @return void 
     * 
     * @throws DBException 
     */
    public function deleteDispatchForAppointment(int $id): void
    {
        $statement = "DELETE FROM stevedoring_timber_dispatch WHERE appointment_id = :id";

        try {
            $request = $this->mysql->prepare($statement);
            $request->execute(["id" => $id]);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de supprimer le dispatch du RDV {$id}", previous: $e);
        }
    }

    /**
     * Update the dispatch for an appointment.
     * 
     * @param int $id Appointment ID.
     * @param TimberDispatchItem[] $dispatch 
     * 
     * @return TimberAppointment Updated appointment. 
     * 
     * @throws DBException 
     */
    public function updateDispatchForAppointment(int $id, array $dispatch): TimberAppointment
    {
        $this->deleteDispatchForAppointment($id);
        $this->insertDispatchForAppointment($id, $dispatch);

        /** @var TimberAppointment */
        $updatedAppointment = $this->fetchAppointment($id);

        return $updatedAppointment;
    }
}
