<?php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\DateUtils;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\TimberRegistryEntryDTO;
use App\Entity\Timber\TimberAppointment;
use App\Service\TimberService;

class TimberAppointmentRepository extends Repository
{
    /**
     * @var TimberAppointment[]
     */
    static private array $cache = [];

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
     * @param array $query Paramètres de recherche.
     * 
     * @return Collection<TimberAppointment> Tous les RDV récupérés
     */
    public function getAppointments(array $query): Collection
    {
        // Filtre
        $startDate = isset($query['date_debut']) ? ($query['date_debut'] ?: date("Y-m-d")) : date("Y-m-d");
        $endDate = isset($query['date_fin']) ? ($query['date_fin'] ?: "9999-12-31") : "9999-12-31";
        $supplierFilter = trim($query['fournisseur'] ?? "", ",");
        $customerFilter = trim($query['client'] ?? "", ",");
        $loadingPlaceFilter = trim($query['chargement'] ?? "", ",");
        $deliveryPlaceFilter = trim($query['livraison'] ?? "", ",");
        $transportFilter = trim($query['transporteur'] ?? "", ",");
        $chartererFilter = trim($query['affreteur'] ?? "", ",");

        $sqlSupplierFilter = $supplierFilter === "" ? "" : " AND fournisseur IN ($supplierFilter)";
        $sqlCustomerFilter = $customerFilter === "" ? "" : " AND client IN ($customerFilter)";
        $sqlLoadingPlaceFilter = $loadingPlaceFilter === "" ? "" : " AND chargement IN ($loadingPlaceFilter)";
        $sqlDeliveryPlaceFilter = $deliveryPlaceFilter === "" ? "" : " AND livraison IN ($deliveryPlaceFilter)";
        $sqlTransportFilter = $transportFilter === "" ? "" : " AND transporteur IN ($transportFilter)";
        $sqlChartererFilter = $chartererFilter === "" ? "" : " AND affreteur IN ($chartererFilter)";

        $sqlFilter =
            $sqlSupplierFilter
            . $sqlCustomerFilter
            . $sqlLoadingPlaceFilter
            . $sqlDeliveryPlaceFilter
            . $sqlTransportFilter
            . $sqlChartererFilter;

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
                (date_rdv BETWEEN :date_debut AND :date_fin)
                OR date_rdv IS NULL
                OR attente = 1
            )
            $sqlFilter
            ORDER BY date_rdv";

        $requete = $this->mysql->prepare($statement);

        $requete->execute([
            "date_debut" => $startDate,
            "date_fin" => $endDate
        ]);

        $rdvsRaw = $requete->fetchAll();

        $timberService = new TimberService();

        $appointments = array_map(
            fn(array $appointmentRaw) => $timberService->makeTimberAppointment($appointmentRaw),
            $rdvsRaw
        );

        return new Collection($appointments);
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
        $rdvRaw = $request->fetch();

        if (!$rdvRaw) return null;

        $timberService = new TimberService();

        $rdv = $timberService->makeTimberAppointment($rdvRaw);

        return $rdv;
    }

    /**
     * Crée un RDV bois.
     * 
     * @param TimberAppointment $rdv RDV à créer
     * 
     * @return TimberAppointment Rendez-vous créé
     */
    public function createAppointment(TimberAppointment $rdv): TimberAppointment
    {
        $statement = "INSERT INTO bois_planning
            VALUES(
                NULL,
                :attente,
                :date_rdv,
                :heure_arrivee,
                :heure_depart,
                :chargement,
                :client,
                :livraison,
                :transporteur,
                :affreteur,
                :fournisseur,
                :commande_prete,
                :confirmation_affretement,
                :numero_bl,
                :commentaire_public,
                :commentaire_cache
            )";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'attente' => (int) $rdv->isOnHold(),
            'date_rdv' => $rdv->getDate(true),
            'heure_arrivee' => $rdv->getArrivalTime(true),
            'heure_depart' => $rdv->getDepartureTime(true),
            'chargement' => $rdv->getLoadingPlace()->getId(),
            'client' => $rdv->getCustomer()->getId(),
            'livraison' => $rdv->getDeliveryPlace()->getId(),
            'transporteur' => $rdv->getTransport()?->getId(),
            'affreteur' => $rdv->getTransportBroker()?->getId(),
            'fournisseur' => $rdv->getSupplier()->getId(),
            'commande_prete' => (int) $rdv->isReady(),
            'confirmation_affretement' => (int) $rdv->getCharteringConfirmationSent(),
            'numero_bl' => $rdv->getDeliveryNoteNumber(),
            'commentaire_public' => $rdv->getPublicComment(),
            'commentaire_cache' => $rdv->getPrivateComment(),
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->getAppointment($lastInsertId);
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
                attente = :attente,
                date_rdv = :date_rdv,
                heure_arrivee = :heure_arrivee,
                heure_depart = :heure_depart,
                chargement = :chargement,
                client = :client,
                livraison = :livraison,
                transporteur = :transporteur,
                affreteur = :affreteur,
                fournisseur = :fournisseur,
                commande_prete = :commande_prete,
                confirmation_affretement = :confirmation_affretement,
                numero_bl = :numero_bl,
                commentaire_public = :commentaire_public,
                commentaire_cache = :commentaire_cache
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'attente' => (int) $appointment->isOnHold(),
            'date_rdv' => $appointment->getDate(true),
            'heure_arrivee' => $appointment->getArrivalTime(true),
            'heure_depart' => $appointment->getDepartureTime(true),
            'chargement' => $appointment->getLoadingPlace()->getId(),
            'client' => $appointment->getCustomer()->getId(),
            'livraison' => $appointment->getDeliveryPlace()->getId(),
            'transporteur' => $appointment->getTransport()?->getId(),
            'affreteur' => $appointment->getTransportBroker()?->getId(),
            'fournisseur' => $appointment->getSupplier()->getId(),
            'commande_prete' => (int) $appointment->isReady(),
            'confirmation_affretement' => (int) $appointment->getCharteringConfirmationSent(),
            'numero_bl' => $appointment->getDeliveryNoteNumber(),
            'commentaire_public' => $appointment->getPublicComment(),
            'commentaire_cache' => $appointment->getPrivateComment(),
            'id' => $appointment->getId(),
        ]);

        return $this->getAppointment($appointment->getId());
    }

    /**
     * Met à jour l'état de préparation d'une commande.
     * 
     * @param int   $id     id du RDV à modifier
     * @param array $status Statut de la commande
     * 
     * @return TimberAppointment RDV modifié
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

        return $this->getAppointment($id);
    }

    /**
     * Met à jour l'état de confirmation d'affrètement.
     * 
     * @param int   $id     id du RDV à modifier
     * @param array $status Statut de la confirmation d'affrètement
     * 
     * @return TimberAppointment RDV modifié
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

        return $this->getAppointment($id);
    }

    public function setArrivalTime(int $id): ?TimberAppointment
    {
        // Heure
        $heure = date('H:i:s');
        $this->mysql
            ->prepare("UPDATE bois_planning SET heure_arrivee = :time WHERE id = :id")
            ->execute([
                'time' => $heure,
                'id' => $id
            ]);


        // Numéro BL automatique (Stora Enso)      
        $rdv = $this->getAppointment($id);

        if (!$rdv) return null;

        if (
            $rdv->getSupplier()?->getId() === 292 /* Stora Enso */
            && $rdv->getLoadingPlace()?->getId() === 1 /* AMSB */
        ) {
            // Récupération du numéro de BL du RDV à modifier (si déjà renseigné)
            $reponse_bl_actuel = $this->mysql->prepare(
                "SELECT numero_bl
                FROM bois_planning
                WHERE id = :id"
            );
            $reponse_bl_actuel->execute(["id" => $id]);
            $reponse_bl_actuel = $reponse_bl_actuel->fetch();
            $numero_bl_actuel = $reponse_bl_actuel["numero_bl"];

            // Dernier numéro de BL de Stora Enso :
            // - enregistrement des 10 derniers numéros dans un tableau
            // - tri du tableau
            // - récupération du numéro le plus élevé
            // Ceci permet de prendre en compte les cas où le dernier numéro
            // renseigné n'est pas le plus haut numériquement
            // Permet aussi de prendre en compte les éventuels bons sans numéro "numérique"
            $reponse_bl_precedent = $this->mysql->query(
                "SELECT numero_bl
                FROM bois_planning
                WHERE fournisseur = {$rdv->getSupplier()->getId()}
                AND numero_bl != ''
                ORDER BY
                    date_rdv DESC,
                    heure_arrivee DESC,
                    numero_bl DESC
                LIMIT 10"
            )->fetchAll();

            $numeros_bl_precedents = [];

            foreach ($reponse_bl_precedent as $numero_bl) {
                // Si le dernier numéro de BL est composé (ex: "200101 + 200102")
                // alors séparation/tri de la chaîne de caractères puis récupération du numéro le plus élevé
                $matches = NULL; // Tableau pour récupérer les numéros de BL
                preg_match_all("/\d{6}/", $numero_bl["numero_bl"], $matches); // Filtre sur les numéros valides (6 chiffres)
                $matches = $matches[0]; // Extraction des résultats
                sort($matches); // Tri des numéros
                $numeros_bl_precedents[] = array_pop($matches); // Récupération du numéro le plus élevé
            }

            // Tri des 10 derniers numéros de BL puis récupération du plus élevé
            sort($numeros_bl_precedents);
            $numero_bl_precedent = array_pop($numeros_bl_precedents);

            // Calcul du nouveau numéro de BL (si possible)
            // Insertion du nouveau numéro de BL si numéro non déjà renseigné
            $numero_bl_nouveau = is_numeric($numero_bl_precedent) ? $numero_bl_precedent + 1 : '';
            if ($numero_bl_actuel === '' && $numero_bl_nouveau) {
                $request = $this->mysql->prepare(
                    "UPDATE bois_planning
            SET numero_bl = :numero_bl
            WHERE id = :id"
                );

                $request->execute([
                    'numero_bl' => $numero_bl_nouveau,
                    'id' => $id
                ]);
            }
        }

        return $this->getAppointment($id);
    }

    /**
     * Définit l'heure de départ pour un rendez-vous bois.
     *
     * @param int $id L'ID du rendez-vous.
     * 
     * @return TimberAppointment L'objet de rendez-vous mis à jour.
     */
    public function setDepartureTime(int $id): TimberAppointment
    {
        $time = date('H:i:s');
        $this->mysql
            ->prepare("UPDATE bois_planning SET heure_depart = :time WHERE id = :id")
            ->execute([
                'time' => $time,
                'id' => $id
            ]);

        return $this->getAppointment($id);
    }

    /**
     * Définit l'heure d'arrivée pour un rendez-vous bois.
     * 
     * @param null|int $id ID du rendez-vous.
     * @param array $input Données du rendez-vous.
     * 
     * @return null|TimberAppointment Rendez-vous mis à jour.
     */
    public function setDeliveryNoteNumber(?int $id, array $input): ?TimberAppointment
    {
        $deliveryNoteNumber = $input['numero_bl'];
        $supplierData = [
            "id" => $input['fournisseur'] ?? null,
            "nom" => "",
        ];
        $dryRun = $input["dry_run"] ?? false;

        // Si pas d'identifiant de RDV ni d'identifiant de fournisseur, ne rien faire
        if (!$id && !$supplierData["id"]) {
            return null;
        }

        $deliveryNoteExists = false;

        // Fournisseurs dont le numéro de BL doit être unique
        $suppliersWithUniqueDeliveryNoteNumber = [
            292 // Stora Enso
        ];

        // Si le fournisseur n'est pas dans la liste des fournisseurs dont le numéro de BL doit être unique, ne rien faire
        if ($supplierData["id"] && !in_array($supplierData["id"], $suppliersWithUniqueDeliveryNoteNumber)) {
            return null;
        }

        if ($id && !$supplierData["id"]) {
            $supplierRequest = $this->mysql
                ->prepare(
                    "SELECT p.fournisseur as id, f.nom_court AS nom
                            FROM bois_planning p
                            JOIN tiers f ON f.id = p.fournisseur
                            WHERE p.id = :id"
                );
        } else {
            $supplierRequest = $this->mysql
                ->prepare(
                    "SELECT t.id, t.nom_court AS nom
                            FROM tiers t
                            WHERE t.id = :id"
                );
        }

        $supplierRequest->execute(["id" => $supplierData["id"] ?? (int) $id]);
        $supplierData = $supplierRequest->fetch();


        // Vérifier si le numéro de BL existe déjà (pour Stora Enso)
        if (
            in_array($supplierData["id"], $suppliersWithUniqueDeliveryNoteNumber)
            && $deliveryNoteNumber !== ""
            && $deliveryNoteNumber !== "-"
        ) {
            $request = $this->mysql->prepare(
                "SELECT COUNT(id) AS bl_existe, id
                    FROM bois_planning
                    WHERE numero_bl LIKE CONCAT('%', :deliveryNoteNumber, '%')
                    AND fournisseur = :supplierId
                    AND NOT id = :id"
            );
            $request->execute([
                "deliveryNoteNumber" => $deliveryNoteNumber,
                "supplierId" => $supplierData["id"],
                "id" => (int) $id,
            ]);

            $dbResult = $request->fetch();

            $deliveryNoteExists = (bool) $dbResult["bl_existe"];
        }

        if ($id && !$deliveryNoteExists && !$dryRun) {
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
        }

        // Si le numéro de BL existe déjà (pour Stora Enso), message d'erreur
        if ($deliveryNoteExists && $id != $dbResult["id"]) {
            throw new ClientException("Le numéro de BL $deliveryNoteNumber existe déjà pour {$supplierData["nom"]}.");
        }

        return $id ? $this->getAppointment($id) : null;
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
        $request = $this->mysql->prepare("DELETE FROM bois_planning WHERE id = :id");
        $success = $request->execute(["id" => $id]);

        if (!$success) {
            throw new DBException("Erreur lors de la suppression");
        }

        return $success;
    }

    /**
     * Renvoie l'extrait du registre d'affrètement avec le filtre appliqué.
     *
     * @param array $filter 
     * 
     * @return TimberRegistryEntryDTO[] Extrait du registre d'affrètement.
     */
    public function getCharteringRegister(array $filter): array
    {
        $defaultStartDate = DateUtils::format(DateUtils::SQL_DATE, DateUtils::previousWorkingDay(new \DateTimeImmutable()));
        $defaultEndDate = date("Y-m-d");

        // Filtre
        $startDate = isset($filter['date_debut'])
            ? ($filter['date_debut'] ?: $defaultStartDate)
            : $defaultStartDate;

        $endDate = isset($filter['date_fin'])
            ? ($filter['date_fin'] ?: $defaultEndDate)
            : $defaultEndDate;

        $statement =
            "SELECT
                p.date_rdv,
                f.nom_court AS fournisseur,
                c.nom_court AS chargement_nom,
                c.ville AS chargement_ville,
                cpays.nom AS loadingPlaceCountry,
                l.nom_court AS livraison_nom,
                l.cp AS deliveryPlacePostCode,
                l.ville AS livraison_ville,
                lpays.nom AS deliveryPlaceCountry,
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
            "startDate" => $startDate,
            "endDate" => $endDate
        ]);

        $appointmentsRaw = $request->fetchAll();

        $timberService = new TimberService();

        $registryEntries = array_map(
            fn(array $rdvRaw) => $timberService->makeTimberRegisterEntryDTO($rdvRaw),
            $appointmentsRaw
        );

        return $registryEntries;
    }

    public function getTransportSuggestions(int $loadingPlaceId, int $deliveryPlaceId): array
    {
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
        $loadingPlaceData = $locationRequest->fetch();

        $locationRequest->execute(["id" => $deliveryPlaceId]);
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
                JOIN tiers t ON p.transporteur = t.id
                JOIN tiers c ON p.chargement = c.id
                JOIN tiers l ON p.livraison = l.id
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

        $requete_transporteurs = $this->mysql->prepare($transportStatement);

        $requete_transporteurs->execute([
            "loadingPlaceId" => $loadingPlaceId,
            "loadingPlacePostCode" => $loadingPlaceData["cp"],
            "loadingPlaceCountry" => $loadingPlaceData["pays"],
            "deliveryPlaceId" => $deliveryPlaceId,
            "deliveryPlacePostCode" => $deliveryPlaceData["cp"],
            "deliveryPlaceCountry" => $deliveryPlaceData["pays"],
        ]);

        $transportData = $requete_transporteurs->fetchAll();

        $suggestions = [
            "chargement" => $loadingPlaceData,
            "livraison" => $deliveryPlaceData,
            "transporteurs" => $transportData
        ];

        return $suggestions;
    }

    /**
     * Récupère les stats bois.
     * 
     * @param array $filter Filtre qui contient...
     */
    public function getStats(array $filter): array
    {
        // Filter
        $startDate = isset($filter['date_debut']) ? ($filter['date_debut'] ?: "0001-01-01") : "0001-01-01";
        $endDate = isset($filter["date_fin"]) ? ($filter['date_fin'] ?: "9999-12-31") : "9999-12-31";
        $supplierFilter = trim($filter['fournisseur'] ?? "", ",");
        $customerFilter = trim($filter['client'] ?? "", ",");
        $loadingPlaceFilter = trim($filter['chargement'] ?? "", ",");
        $deliveryPlaceFilter = trim($filter['livraison'] ?? "", ",");
        $transportFilter = trim($filter['transporteur'] ?? "", ",");
        $chartererFilter = trim($filter['affreteur'] ?? "", ",");

        $sqlSupplierFilter = $supplierFilter === "" ? "" : " AND fournisseur IN ($supplierFilter)";
        $sqlCustomerFilter = $customerFilter === "" ? "" : " AND client IN ($customerFilter)";
        $sqlLoadingPlaceFilter = $loadingPlaceFilter === "" ? "" : " AND chargement IN ($loadingPlaceFilter)";
        $sqlDeliveryPlaceFilter = $deliveryPlaceFilter === "" ? "" : " AND livraison IN ($deliveryPlaceFilter)";
        $sqlTransportFilter = $transportFilter === "" ? "" : " AND transporteur IN ($transportFilter)";
        $sqlChartererFilter = $chartererFilter === "" ? "" : " AND affreteur IN ($chartererFilter)";

        $sqlFilter =
            $sqlSupplierFilter
            . $sqlCustomerFilter
            . $sqlLoadingPlaceFilter
            . $sqlDeliveryPlaceFilter
            . $sqlTransportFilter
            . $sqlChartererFilter;

        $appointmentsStatement =
            "SELECT date_rdv as `date`
            FROM bois_planning
            WHERE date_rdv BETWEEN :startDate AND :endDate
            AND attente = 0
            $sqlFilter";


        $appointmentsRequest = $this->mysql->prepare($appointmentsStatement);

        $appointmentsRequest->execute([
            "startDate" => $startDate,
            "endDate" => $endDate
        ]);

        $appointments = $appointmentsRequest->fetchAll();

        $stats = [
            "Total" => 0,
            "Par année" => [],
        ];

        $yearTemplate = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            10 => 0,
            11 => 0,
            12 => 0,
        ];

        // Compilation du nombre de RDV par année et par mois
        foreach ($appointments as $appointment) {
            $date = explode("-", $appointment["date"]);
            $year = $date[0];
            $month = $date[1];

            if (!array_key_exists($year, $stats["Par année"])) {
                $stats["Par année"][$year] = $yearTemplate;
            };

            $stats["Total"]++;
            $stats["Par année"][$year][(int) $month]++;
        }

        return $stats;
    }
}
