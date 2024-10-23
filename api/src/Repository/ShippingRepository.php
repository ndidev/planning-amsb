<?php

// Path: api/src/Repository/ShippingRespository.php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Component\ETAConverter;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;
use App\Service\ShippingService;

final class ShippingRepository extends Repository
{
    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function exists(int $id): bool
    {
        return $this->mysql->exists("consignation_planning", $id);
    }

    /**
     * Récupère toutes les escales consignation.
     * 
     * @return Collection<ShippingCall> Toutes les escale récupérées
     */
    public function fetchAllCalls(array $filter = []): Collection
    {
        $yesterday = (new \DateTime())->sub(new \DateInterval("P1D"))->format("Y-m-d");

        if (array_key_exists("archives", $filter)) {
            $callsStatement =
                "SELECT
                    id,
                    navire,
                    voyage,
                    armateur,
                    eta_date,
                    eta_heure,
                    nor_date,
                    nor_heure,
                    pob_date,
                    pob_heure,
                    etb_date,
                    etb_heure,
                    ops_date,
                    ops_heure,
                    etc_date,
                    etc_heure,
                    etd_date,
                    etd_heure,
                    te_arrivee,
                    te_depart,
                    last_port,
                    next_port,
                    call_port,
                    quai,
                    commentaire
                FROM consignation_planning
                WHERE etd_date <= '$yesterday'
                ORDER BY
                    -eta_date ASC,
                    eta_heure,
                    -etb_date ASC,
                    etb_heure";
        } else {
            $callsStatement =
                "SELECT
                    id,
                    navire,
                    voyage,
                    armateur,
                    eta_date,
                    eta_heure,
                    nor_date,
                    nor_heure,
                    pob_date,
                    pob_heure,
                    etb_date,
                    etb_heure,
                    ops_date,
                    ops_heure,
                    etc_date,
                    etc_heure,
                    etd_date,
                    etd_heure,
                    te_arrivee,
                    te_depart,
                    last_port,
                    next_port,
                    call_port,
                    quai,
                    commentaire
                FROM consignation_planning
                WHERE etd_date >= '$yesterday'
                OR etd_date IS NULL
                ORDER BY
                    -eta_date DESC,
                    eta_heure,
                    -etb_date DESC,
                    etb_heure";
        }

        // Escales
        $callsRequest = $this->mysql->query($callsStatement);
        $callsRaw = $callsRequest->fetchAll();

        $cargoesRaw = [];

        if (count($callsRaw) > 0) {
            $callsIds = array_map(fn($call) => $call["id"], $callsRaw);
            $cargoesStatement =
                "SELECT
                id,
                escale_id,
                IFNULL(marchandise, '') as marchandise,
                IFNULL(client, '') as client,
                operation,
                environ,
                tonnage_bl,
                cubage_bl,
                nombre_bl,
                tonnage_outturn,
                cubage_outturn,
                nombre_outturn
            FROM consignation_escales_marchandises
            WHERE escale_id IN (" . implode(",", $callsIds) . ")";
            $cargoesRequest = $this->mysql->query($cargoesStatement);
            $cargoesRaw = $cargoesRequest->fetchAll();
        }

        $shippingService = new ShippingService();

        $calls = array_map(function ($callRaw) use ($shippingService, $cargoesRaw) {
            $call = $shippingService->makeShippingCallFromDatabase($callRaw);

            // Rétablir heure ETA
            $call->setEtaTime(ETAConverter::toLetters($call->getEtaTime()));

            $filteredCargoesRaw = array_values(
                array_filter(
                    $cargoesRaw,
                    fn($cargo) => $cargo["escale_id"] === $call->getId()
                )
            );

            $cargoes = array_map(
                fn(array $cargoRaw) => $shippingService->makeShippingCallCargoFromDatabase($cargoRaw),
                $filteredCargoesRaw
            );

            $call->setCargoes($cargoes);

            return $call;
        }, $callsRaw);

        return new Collection($calls);
    }

    /**
     * Récupère une escale consignation.
     * 
     * @param int $id ID de l'escale à récupérer
     * 
     * @return ?ShippingCall Escale récupérée
     */
    public function fetchCall($id): ?ShippingCall
    {
        $callStatement =
            "SELECT
                id,
                navire,
                voyage,
                armateur,
                eta_date,
                eta_heure,
                nor_date,
                nor_heure,
                pob_date,
                pob_heure,
                etb_date,
                etb_heure,
                ops_date,
                ops_heure,
                etc_date,
                etc_heure,
                etd_date,
                etd_heure,
                te_arrivee,
                te_depart,
                last_port,
                next_port,
                call_port,
                quai,
                commentaire
            FROM consignation_planning 
            WHERE id = :id";

        $cargoesStatement =
            "SELECT
                id,
                escale_id,
                IFNULL(marchandise, '') as marchandise,
                IFNULL(client, '') as client,
                operation,
                environ,
                tonnage_bl,
                cubage_bl,
                nombre_bl,
                tonnage_outturn,
                cubage_outturn,
                nombre_outturn
            FROM consignation_escales_marchandises
            WHERE escale_id = :id";

        // Escales
        $callRequest = $this->mysql->prepare($callStatement);
        $callRequest->execute(["id" => $id]);
        $callRaw = $callRequest->fetch();

        if (!$callRaw) return null;


        // Marchandises
        $cargoesRequest = $this->mysql->prepare($cargoesStatement);
        $cargoesRequest->execute(["id" => $id]);
        $cargoesRaw = $cargoesRequest->fetchAll();

        $callRaw["marchandises"] = $cargoesRaw;

        $shippingService = new ShippingService();

        $call = $shippingService->makeShippingCallFromDatabase($callRaw);

        return $call;
    }

    /**
     * Crée une escale consignation.
     * 
     * @param ShippingCall $call Eléments de l'escale à créer.
     * 
     * @return ShippingCall Escale créée.
     */
    public function createCall(ShippingCall $call): ShippingCall
    {
        $callStatement =
            "INSERT INTO consignation_planning
            SET
                navire = :navire,
                voyage = :voyage,
                armateur = :armateur,
                eta_date = :eta_date,
                eta_heure = :eta_heure,
                nor_date = :nor_date,
                nor_heure = :nor_heure,
                pob_date = :pob_date,
                pob_heure = :pob_heure,
                etb_date = :etb_date,
                etb_heure = :etb_heure,
                ops_date = :ops_date,
                ops_heure = :ops_heure,
                etc_date = :etc_date,
                etc_heure = :etc_heure,
                etd_date = :etd_date,
                etd_heure = :etd_heure,
                te_arrivee = :te_arrivee,
                te_depart = :te_depart,
                last_port = :last_port,
                next_port = :next_port,
                call_port = :call_port,
                quai = :quai,
                commentaire = :commentaire";

        $insertCargoStatement =
            "INSERT INTO consignation_escales_marchandises
            SET
                escale_id = :escale_id,
                marchandise = :marchandise,
                client = :client,
                operation = :operation,
                environ = :environ,
                tonnage_bl = :tonnage_bl,
                cubage_bl = :cubage_bl,
                nombre_bl = :nombre_bl,
                tonnage_outturn = :tonnage_outturn,
                cubage_outturn = :cubage_outturn,
                nombre_outturn = :nombre_outturn";

        $callRequest = $this->mysql->prepare($callStatement);

        $this->mysql->beginTransaction();
        $callRequest->execute([
            'navire' => $call->getShipName() ?: "TBN",
            'voyage' => $call->getVoyage(),
            'armateur' => $call->getShipOperator()?->getId(),
            'eta_date' => $call->getEtaDate()?->format("Y-m-d"),
            'eta_heure' => ETAConverter::toDigits($call->getEtaTime()),
            'nor_date' => $call->getNorDate()?->format("Y-m-d"),
            'nor_heure' => $call->getNorTime(),
            'pob_date' => $call->getPobDate()?->format("Y-m-d"),
            'pob_heure' => $call->getPobTime(),
            'etb_date' => $call->getEtbDate()?->format("Y-m-d"),
            'etb_heure' => $call->getEtbTime(),
            'ops_date' => $call->getOpsDate()?->format("Y-m-d"),
            'ops_heure' => $call->getOpsTime(),
            'etc_date' => $call->getEtcDate()?->format("Y-m-d"),
            'etc_heure' => $call->getEtcTime(),
            'etd_date' => $call->getEtdDate()?->format("Y-m-d"),
            'etd_heure' => $call->getEtdTime(),
            'te_arrivee' => $call->getArrivalDraft(),
            'te_depart' => $call->getDepartureDraft(),
            'last_port' => $call->getLastPort()?->getLocode() ?? '',
            'next_port' => $call->getNextPort()?->getLocode() ?? '',
            'call_port' => $call->getCallPort(),
            'quai' => $call->getQuay(),
            'commentaire' => $call->getComment(),
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        // Marchandises
        $insertCargoRequest = $this->mysql->prepare($insertCargoStatement);
        $cargoes = $call->getCargoes();
        foreach ($cargoes as $cargo) {
            $insertCargoRequest->execute([
                'escale_id' => $lastInsertId,
                'marchandise' => $cargo->getCargoName(),
                'client' => $cargo->getCustomer(),
                'operation' => $cargo->getOperation()->value,
                'environ' => (int) $cargo->isApproximate(),
                'tonnage_bl' => $cargo->getBlTonnage(),
                'cubage_bl' => $cargo->getBlVolume(),
                'nombre_bl' => $cargo->getBlUnits(),
                'tonnage_outturn' => $cargo->getOutturnTonnage(),
                'cubage_outturn' => $cargo->getOutturnVolume(),
                'nombre_outturn' => $cargo->getOutturnUnits(),
            ]);
        }

        return $this->fetchCall($lastInsertId);
    }

    /**
     * Met à jour une escale consignation.
     * 
     * @param ShippingCall $call Escale à modifier.
     * 
     * @return ShippingCall Escale modifiée.
     */
    public function updateCall(ShippingCall $call): ShippingCall
    {
        $callStatement =
            "UPDATE consignation_planning
            SET
                navire = :navire,
                voyage = :voyage,
                armateur = :armateur,
                eta_date = :eta_date,
                eta_heure = :eta_heure,
                nor_date = :nor_date,
                nor_heure = :nor_heure,
                pob_date = :pob_date,
                pob_heure = :pob_heure,
                etb_date = :etb_date,
                etb_heure = :etb_heure,
                ops_date = :ops_date,
                ops_heure = :ops_heure,
                etc_date = :etc_date,
                etc_heure = :etc_heure,
                etd_date = :etd_date,
                etd_heure = :etd_heure,
                te_arrivee = :te_arrivee,
                te_depart = :te_depart,
                last_port = :last_port,
                next_port = :next_port,
                call_port = :call_port,
                quai = :quai,
                commentaire = :commentaire
            WHERE id = :id";

        $cargoStatement =
            "INSERT INTO consignation_escales_marchandises
            SET
                id = :id,
                escale_id = :escale_id,
                marchandise = :marchandise,
                client = :client,
                operation = :operation,
                environ = :environ,
                tonnage_bl = :tonnage_bl,
                cubage_bl = :cubage_bl,
                nombre_bl = :nombre_bl,
                tonnage_outturn = :tonnage_outturn,
                cubage_outturn = :cubage_outturn,
                nombre_outturn = :nombre_outturn
            ON DUPLICATE KEY UPDATE
                marchandise = :marchandise,
                client = :client,
                operation = :operation,
                environ = :environ,
                tonnage_bl = :tonnage_bl,
                cubage_bl = :cubage_bl,
                nombre_bl = :nombre_bl,
                tonnage_outturn = :tonnage_outturn,
                cubage_outturn = :cubage_outturn,
                nombre_outturn = :nombre_outturn";

        $callRequest = $this->mysql->prepare($callStatement);
        $callRequest->execute([
            'navire' => $call->getShipName() ?: "TBN",
            'voyage' => $call->getVoyage(),
            'armateur' => $call->getShipOperator()?->getId(),
            'eta_date' => $call->getEtaDate()?->format("Y-m-d"),
            'eta_heure' => ETAConverter::toDigits($call->getEtaTime()),
            'nor_date' => $call->getNorDate()?->format("Y-m-d"),
            'nor_heure' => $call->getNorTime(),
            'pob_date' => $call->getPobDate()?->format("Y-m-d"),
            'pob_heure' => $call->getPobTime(),
            'etb_date' => $call->getEtbDate()?->format("Y-m-d"),
            'etb_heure' => $call->getEtbTime(),
            'ops_date' => $call->getOpsDate()?->format("Y-m-d"),
            'ops_heure' => $call->getOpsTime(),
            'etc_date' => $call->getEtcDate()?->format("Y-m-d"),
            'etc_heure' => $call->getEtcTime(),
            'etd_date' => $call->getEtdDate()?->format("Y-m-d"),
            'etd_heure' => $call->getEtdTime(),
            'te_arrivee' => $call->getArrivalDraft(),
            'te_depart' => $call->getDepartureDraft(),
            'last_port' => $call->getLastPort()?->getLocode() ?? '',
            'next_port' => $call->getNextPort()?->getLocode() ?? '',
            'call_port' => $call->getCallPort(),
            'quai' => $call->getQuay(),
            'commentaire' => $call->getComment(),
            'id' => $call->getId(),
        ]);

        // MARCHANDISES
        // Suppression marchandises
        // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE marchandise POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
        // Comparaison du tableau transmis par POST avec la liste existante des marchandises pour le produit concerné
        $existingCargoesIdsRequest = $this->mysql->prepare(
            "SELECT id FROM consignation_escales_marchandises WHERE escale_id = :callId"
        );
        $existingCargoesIdsRequest->execute(['callId' => $call->getId()]);
        $existingCargoesIds = $existingCargoesIdsRequest->fetchAll(\PDO::FETCH_COLUMN);

        $submittedCargoesIds = array_map(fn(ShippingCallCargo $cargo) => $cargo->getId(), $call->getCargoes()->asArray());
        $cargoesIdsToBeDeleted = array_diff($existingCargoesIds, $submittedCargoesIds);

        if (count($cargoesIdsToBeDeleted) > 0) {
            $this->mysql->exec("DELETE FROM consignation_escales_marchandises WHERE id IN (" . implode(",", $cargoesIdsToBeDeleted) . ")");
        }

        // Ajout et modification marchandises
        $cargoRequest = $this->mysql->prepare($cargoStatement);
        $cargoes = $call->getCargoes();
        foreach ($cargoes as $cargo) {
            $cargoRequest->execute([
                'id' => $cargo->getId(),
                'escale_id' => $call->getId(),
                'marchandise' => $cargo->getCargoName(),
                'client' => $cargo->getCustomer(),
                'operation' => $cargo->getOperation()->value,
                'environ' => (int) $cargo->isApproximate(),
                'tonnage_bl' => $cargo->getBlTonnage(),
                'cubage_bl' => $cargo->getBlVolume(),
                'nombre_bl' => $cargo->getBlUnits(),
                'tonnage_outturn' => $cargo->getOutturnTonnage(),
                'cubage_outturn' => $cargo->getOutturnVolume(),
                'nombre_outturn' => $cargo->getOutturnUnits(),
            ]);
        }

        return $this->fetchCall($call->getId());
    }

    /**
     * Supprime une escale consignation.
     * 
     * @param int $id ID de l'escale à supprimer.
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteCall(int $id): bool
    {
        $request = $this->mysql->prepare("DELETE FROM consignation_planning WHERE id = :id");
        $success = $request->execute(["id" => $id]);

        if (!$success) {
            throw new DBException("Erreur lors de la suppression");
        }

        return $success;
    }

    /**
     * Récupère un numéro de voyage pour un navire.
     * 
     * @param string $shipName      Nom du navire.
     * @param int    $currentCallId ID de l'escale.
     * 
     * @return string Dernier numéro de voyage du navire.
     */
    public function fetchLastVoyageNumber(string $shipName, ?int $currentCallId): string
    {
        // Si un id d'escale est fourni, récupérer le dernier numéro de voyage
        // de l'escale précédente :
        //  - id !== id fourni
        //  - eta <= eta de l'id fourni
        //  - etc <= etc de l'id fourni (permet de gérer les escale avec backload)
        $sql = is_null($currentCallId)
            ? ""
            : " AND NOT id = '$currentCallId'
                AND eta_date <= (SELECT eta_date FROM consignation_planning WHERE id = '$currentCallId')
                AND eta_date <= COALESCE((SELECT eta_date FROM consignation_planning WHERE id = '$currentCallId'), '9999-12-31')";

        $statement =
            "SELECT voyage
            FROM consignation_planning
            WHERE navire = :navire
            $sql
            ORDER BY eta_date DESC, etc_date DESC
            LIMIT 1";

        $voyageNumberRequest = $this->mysql->prepare($statement);
        $voyageNumberRequest->execute(["navire" => $shipName]);
        $voyageNumber = $voyageNumberRequest->fetch(\PDO::FETCH_COLUMN) ?: "";

        return $voyageNumber;
    }

    /**
     * Récupère tous les tirants d'eau du planning consignation.
     * 
     * @return array Tous les tirants d'eau récupérés
     */
    public function fetchDraftsPerTonnage(): array
    {
        $statement = "SELECT * FROM drafts_par_tonnage";

        $draftsPerTonnage = $this->mysql->query($statement)->fetchAll();

        return $draftsPerTonnage;
    }

    /**
     * Récupère les stats consignation.
     * 
     * @param array $filter Filtre qui contient...
     * 
     * @return array Stats consignation.
     */
    public function fetchStatsSummary(array $filter): array
    {
        // Filtre
        $startDate = isset($filter['date_debut']) ? ($filter['date_debut'] ?: "0001-01-01") : "0001-01-01";
        $endDate = isset($filter["date_fin"]) ? ($filter['date_fin'] ?: "9999-12-31") : "9999-12-31";
        $shipFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $filter['navire'] ?? ""), ",");
        $shipOwnerFilter = trim($filter['armateur'] ?? "", ",");
        $cargoFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $filter['marchandise'] ?? ""), ",");
        $customerFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $filter['client'] ?? ""), ",");
        $lastPortFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $filter['last_port'] ?? ""), ",");
        $nextPortFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $filter['next_port'] ?? ""), ",");

        $sqlShipFilter = $shipFilter === "" ? "" : " AND cp.navire IN ($shipFilter)";
        $sqlShipOwnerFilter = $shipOwnerFilter === "" ? "" : " AND cp.armateur IN ($shipOwnerFilter)";
        $sqlCargoFilter = $cargoFilter === "" ? "" : " AND cem.marchandise IN ($cargoFilter)";
        $sqlCustomerFilter = $customerFilter === "" ? "" : " AND cem.client IN ($customerFilter)";
        $sqlLastPortFilter = $lastPortFilter === "" ? "" : " AND cp.last_port IN ($lastPortFilter)";
        $sqlNextPortFilter = $nextPortFilter === "" ? "" : " AND cp.next_port IN ($nextPortFilter)";

        $sqlFilter =
            $sqlShipFilter
            . $sqlCargoFilter
            . $sqlShipOwnerFilter
            . $sqlCustomerFilter
            . $sqlLastPortFilter
            . $sqlNextPortFilter;

        $callsStatement =
            "SELECT
                cp.id,
                cp.etc_date as `date`
            FROM consignation_planning cp
            LEFT JOIN consignation_escales_marchandises cem ON cem.escale_id = cp.id
            WHERE cp.etc_date BETWEEN :date_debut AND :date_fin
            $sqlFilter
            GROUP BY cp.id";

        $callsRequest = $this->mysql->prepare($callsStatement);

        $callsRequest->execute([
            "date_debut" => $startDate,
            "date_fin" => $endDate
        ]);

        $calls = $callsRequest->fetchAll();

        $stats = [
            "Total" => count($calls),
            "Par année" => [],
        ];

        $yearTemplate = [
            1 => ["nombre" => 0, "ids" => []],
            2 => ["nombre" => 0, "ids" => []],
            3 => ["nombre" => 0, "ids" => []],
            4 => ["nombre" => 0, "ids" => []],
            5 => ["nombre" => 0, "ids" => []],
            6 => ["nombre" => 0, "ids" => []],
            7 => ["nombre" => 0, "ids" => []],
            8 => ["nombre" => 0, "ids" => []],
            9 => ["nombre" => 0, "ids" => []],
            10 => ["nombre" => 0, "ids" => []],
            11 => ["nombre" => 0, "ids" => []],
            12 => ["nombre" => 0, "ids" => []],
        ];

        // Compilation du nombre de RDV par année et par mois
        foreach ($calls as $call) {
            $date = explode("-", $call["date"]);
            $year = $date[0];
            $month = $date[1];

            if (!array_key_exists($year, $stats["Par année"])) {
                $stats["Par année"][$year] = $yearTemplate;
            };

            // $stats["Total"]++;
            $stats["Par année"][$year][(int) $month]["nombre"]++;
            $stats["Par année"][$year][(int) $month]["ids"][] = $call["id"];
        }

        return $stats;
    }

    /**
     * Récupère les détails des stats consignation.
     * 
     * @param array $ids Identifiants des escales.
     * 
     * @return array Détails des stats.
     */
    public function fetchStatsDetails(array $ids): array
    {
        if (count($ids) === 0) {
            return [];
        }

        // Filtre
        $idsAsString = join(",", $ids);

        $callsStatement =
            "SELECT
                cp.id,
                cp.navire,
                cp.ops_date,
                cp.etc_date,
                IFNULL(cem.marchandise, '') as marchandise,
                IFNULL(cem.client, '') as client,
                cem.tonnage_bl,
                cem.tonnage_outturn,
                cem.cubage_bl,
                cem.cubage_outturn,
                cem.nombre_bl,
                cem.nombre_outturn
            FROM consignation_planning cp
            LEFT JOIN consignation_escales_marchandises cem ON cem.escale_id = cp.id
            WHERE cp.id IN ($idsAsString)
            ORDER BY cp.etc_date DESC";

        $callsRequest = $this->mysql->query($callsStatement);

        $calls = $callsRequest->fetchAll();

        $groupedCalls = [];

        // Grouper par escale
        foreach ($calls as $call) {
            if (!array_key_exists($call["id"], $groupedCalls)) {
                $groupedCalls[$call["id"]] = [
                    "id" => $call["id"],
                    "navire" => $call["navire"],
                    "ops_date" => $call["ops_date"],
                    "etc_date" => $call["etc_date"],
                    "marchandises" => [],
                ];
            }

            $groupedCalls[$call["id"]]["marchandises"][] = [
                "marchandise" => $call["marchandise"],
                "client" => $call["client"],
                "tonnage_outturn" => $call["tonnage_outturn"] ?: $call["tonnage_bl"],
                "cubage_outturn" => $call["cubage_outturn"] ?: $call["cubage_bl"],
                "nombre_outturn" => $call["nombre_outturn"] ?: $call["nombre_bl"],
            ];
        }

        return array_values($groupedCalls);
    }

    /**
     * Récupère la liste des tous les noms de navire.
     * 
     * @return string[] Liste des noms de navire.
     */
    public function fetchAllShipNames(): array
    {
        $statement =
            "SELECT DISTINCT navire
            FROM consignation_planning
            WHERE navire IS NOT NULL
            AND navire <> ''
            ORDER BY navire ASC";

        $request = $this->mysql->query($statement);

        $shipsNames = $request->fetchAll(\PDO::FETCH_COLUMN);

        return $shipsNames;
    }

    /**
     * Récupère la liste des navires en activité entre deux dates.
     * 
     * @param \DateTimeImmutable|null $startDate Date de début.
     * @param \DateTimeImmutable|null $endDate   Date de fin.
     * 
     * @return array{
     *          array{
     *            navire: string,
     *            debut: string,
     *            fin: string
     *          }
     *         } Liste des navires en activité.
     */
    public function fetchShipsInOps(
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
    ): array {
        $statement =
            "SELECT navire, ops_date AS debut, etc_date AS fin
            FROM consignation_planning
            WHERE ops_date <= :date_fin AND etc_date >= :date_debut
            ORDER BY debut";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            "date_debut" => $startDate ? $startDate->format("Y-m-d") : date("Y-m-d"),
            "date_fin" => $endDate ? $endDate->format("Y-m-d") : "9999-12-31",
        ]);
        $shipsInOps = $request->fetchAll();

        return $shipsInOps;
    }

    /**
     * Récupère la liste des marchandises utilisées en consignation.
     * 
     * @return string[] Liste des marchandises.
     */
    public function fetchAllCargoNames(): array
    {
        $statement =
            "SELECT DISTINCT marchandise
            FROM consignation_escales_marchandises
            WHERE marchandise IS NOT NULL
            AND marchandise <> ''
            ORDER BY marchandise ASC";

        $request = $this->mysql->query($statement);

        $cargoNames = $request->fetchAll(\PDO::FETCH_COLUMN);

        return $cargoNames;
    }

    /**
     * Récupère la liste des clients en consignation.
     * 
     * @return string[] Liste des clients.
     */
    public function fetchAllCustomersNames(): array
    {
        $statement =
            "SELECT DISTINCT client
            FROM consignation_escales_marchandises
            WHERE client IS NOT NULL
            AND client <> ''
            ORDER BY client ASC";

        $request = $this->mysql->query($statement);

        $customersNames = $request->fetchAll(\PDO::FETCH_COLUMN);

        return $customersNames;
    }
}
