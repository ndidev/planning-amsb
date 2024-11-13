<?php

// Path: api/src/Repository/ShippingRespository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Component\ETAConverter;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\Filter\ShippingFilterDTO;
use App\DTO\ShippingStatsDetailsDTO;
use App\DTO\ShippingStatsSummaryDTO;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;
use App\Service\ShippingService;

/**
 * @phpstan-type ShippingCallArray array{
 *                                   id?: int,
 *                                   navire?: string,
 *                                   voyage?: string,
 *                                   armateur?: int|null,
 *                                   eta_date?: string,
 *                                   eta_heure?: string,
 *                                   nor_date?: string,
 *                                   nor_heure?: string,
 *                                   pob_date?: string,
 *                                   pob_heure?: string,
 *                                   etb_date?: string,
 *                                   etb_heure?: string,
 *                                   ops_date?: string,
 *                                   ops_heure?: string,
 *                                   etc_date?: string,
 *                                   etc_heure?: string,
 *                                   etd_date?: string,
 *                                   etd_heure?: string,
 *                                   te_arrivee?: float,
 *                                   te_depart?: float,
 *                                   last_port?: string,
 *                                   next_port?: string,
 *                                   call_port?: string,
 *                                   quai?: string,
 *                                   commentaire?: string,
 *                                   marchandises?: ShippingCallCargoArray[],
 *                                 }
 * 
 * @phpstan-type ShippingCallCargoArray array{
 *                                        id?: int,
 *                                        escale_id?: int,
 *                                        marchandise?: string,
 *                                        client?: string,
 *                                        operation?: string,
 *                                        environ?: bool,
 *                                        tonnage_bl?: float|null,
 *                                        cubage_bl?: float|null,
 *                                        nombre_bl?: int|null,
 *                                        tonnage_outturn?: float|null,
 *                                        cubage_outturn?: float|null,
 *                                        nombre_outturn?: int|null,
 *                                      }
 * 
 * @phpstan-type DraftsPerTonnage list<array{
 *                                       navire: string,
 *                                       date: string,
 *                                       te: float,
 *                                       tonnage: float,
 *                                     }>
 * 
 * @phpstan-type ShipsInOps list<array{
 *                                 navire: string,
 *                                 debut: string,
 *                                 fin: string
 *                               }>
 * 
 * @phpstan-type ShippingStatsSummaryArray list<array{
 *                                                id: int,
 *                                                date: string
 *                                              }>
 * 
 * @phpstan-type ShippingStatsDetailsArray list<array{
 *                                           id: int,
 *                                           navire: string,
 *                                           ops_date: ?string,
 *                                           etc_date: ?string,
 *                                           marchandise: string,
 *                                           client: string,
 *                                           tonnage_bl: ?float,
 *                                           tonnage_outturn: ?float,
 *                                           cubage_bl: ?float,
 *                                           cubage_outturn: ?float,
 *                                           nombre_bl: ?float,
 *                                           nombre_outturn: ?float,
 *                                         }> 
 */
final class ShippingRepository extends Repository
{
    public function __construct(private ShippingService $shippingService)
    {
        parent::__construct();
    }

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
    public function fetchAllCalls(bool $archives = false): Collection
    {
        $yesterday = (new \DateTime())->sub(new \DateInterval("P1D"))->format('Y-m-d');

        if ($archives) {
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

        if (!$callsRequest) {
            throw new DBException("Impossible de récupérer les escales.");
        }

        $callsRaw = $callsRequest->fetchAll();

        $cargoesRaw = [];

        if (count($callsRaw) > 0) {
            $callsIds = \array_map(fn($call) => $call["id"], $callsRaw);
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

            if (!$cargoesRequest) {
                throw new DBException("Impossible de récupérer les marchandises.");
            }

            /** @phpstan-var ShippingCallCargoArray[] $cargoesRaw */
            $cargoesRaw = $cargoesRequest->fetchAll();
        }

        $calls = \array_map(function ($callRaw) use ($cargoesRaw) {
            $call = $this->shippingService->makeShippingCallFromDatabase($callRaw);

            // Rétablir heure ETA
            $call->setEtaTime(ETAConverter::toLetters($call->getEtaTime()));

            $filteredCargoesRaw = array_values(
                array_filter(
                    $cargoesRaw,
                    fn($cargo) => ($cargo["escale_id"] ?? null) === $call->getId()
                )
            );

            $cargoes = \array_map(
                fn($cargoRaw) => $this->shippingService->makeShippingCallCargoFromDatabase($cargoRaw),
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

        if (!\is_array($callRaw)) return null;

        /** @phpstan-var ShippingCallArray $callRaw */


        // Marchandises
        $cargoesRequest = $this->mysql->prepare($cargoesStatement);
        $cargoesRequest->execute(["id" => $id]);
        /** @phpstan-var ShippingCallCargoArray[] $cargoesRaw */
        $cargoesRaw = $cargoesRequest->fetchAll();

        $callRaw["marchandises"] = $cargoesRaw;

        $call = $this->shippingService->makeShippingCallFromDatabase($callRaw);

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
            'eta_date' => $call->getEtaDate()?->format('Y-m-d'),
            'eta_heure' => ETAConverter::toDigits($call->getEtaTime()),
            'nor_date' => $call->getNorDate()?->format('Y-m-d'),
            'nor_heure' => $call->getNorTime(),
            'pob_date' => $call->getPobDate()?->format('Y-m-d'),
            'pob_heure' => $call->getPobTime(),
            'etb_date' => $call->getEtbDate()?->format('Y-m-d'),
            'etb_heure' => $call->getEtbTime(),
            'ops_date' => $call->getOpsDate()?->format('Y-m-d'),
            'ops_heure' => $call->getOpsTime(),
            'etc_date' => $call->getEtcDate()?->format('Y-m-d'),
            'etc_heure' => $call->getEtcTime(),
            'etd_date' => $call->getEtdDate()?->format('Y-m-d'),
            'etd_heure' => $call->getEtdTime(),
            'te_arrivee' => $call->getArrivalDraft(),
            'te_depart' => $call->getDepartureDraft(),
            'last_port' => $call->getLastPort()?->getLocode() ?? '',
            'next_port' => $call->getNextPort()?->getLocode() ?? '',
            'call_port' => $call->getCallPort(),
            'quai' => $call->getQuay(),
            'commentaire' => $call->getComment(),
        ]);

        $lastInsertId = (int) $this->mysql->lastInsertId();
        $this->mysql->commit();

        // Marchandises
        $insertCargoRequest = $this->mysql->prepare($insertCargoStatement);
        $cargoes = $call->getCargoes();
        foreach ($cargoes as $cargo) {
            $insertCargoRequest->execute([
                'escale_id' => $lastInsertId,
                'marchandise' => $cargo->getCargoName(),
                'client' => $cargo->getCustomer(),
                'operation' => $cargo->getOperation(),
                'environ' => (int) $cargo->isApproximate(),
                'tonnage_bl' => $cargo->getBlTonnage(),
                'cubage_bl' => $cargo->getBlVolume(),
                'nombre_bl' => $cargo->getBlUnits(),
                'tonnage_outturn' => $cargo->getOutturnTonnage(),
                'cubage_outturn' => $cargo->getOutturnVolume(),
                'nombre_outturn' => $cargo->getOutturnUnits(),
            ]);
        }

        /** @var ShippingCall */
        $newShippingCall = $this->fetchCall($lastInsertId);

        return $newShippingCall;
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
        $id = $call->getId();

        if (!$id) {
            throw new ClientException("ID de l'escale manquant");
        }

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
            'eta_date' => $call->getEtaDate()?->format('Y-m-d'),
            'eta_heure' => ETAConverter::toDigits($call->getEtaTime()),
            'nor_date' => $call->getNorDate()?->format('Y-m-d'),
            'nor_heure' => $call->getNorTime(),
            'pob_date' => $call->getPobDate()?->format('Y-m-d'),
            'pob_heure' => $call->getPobTime(),
            'etb_date' => $call->getEtbDate()?->format('Y-m-d'),
            'etb_heure' => $call->getEtbTime(),
            'ops_date' => $call->getOpsDate()?->format('Y-m-d'),
            'ops_heure' => $call->getOpsTime(),
            'etc_date' => $call->getEtcDate()?->format('Y-m-d'),
            'etc_heure' => $call->getEtcTime(),
            'etd_date' => $call->getEtdDate()?->format('Y-m-d'),
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

        $submittedCargoesIds = \array_map(fn(ShippingCallCargo $cargo) => $cargo->getId(), $call->getCargoes()->asArray());
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
                'operation' => $cargo->getOperation(),
                'environ' => (int) $cargo->isApproximate(),
                'tonnage_bl' => $cargo->getBlTonnage(),
                'cubage_bl' => $cargo->getBlVolume(),
                'nombre_bl' => $cargo->getBlUnits(),
                'tonnage_outturn' => $cargo->getOutturnTonnage(),
                'cubage_outturn' => $cargo->getOutturnVolume(),
                'nombre_outturn' => $cargo->getOutturnUnits(),
            ]);
        }

        /** @var ShippingCall */
        $updatedShippingCall = $this->fetchCall($id);

        return $updatedShippingCall;
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
        /** @var string */
        $voyageNumber = $voyageNumberRequest->fetch(\PDO::FETCH_COLUMN) ?: "";

        return $voyageNumber;
    }

    /**
     * Récupère tous les tirants d'eau du planning consignation.
     * 
     * @return array Tous les tirants d'eau récupérés
     * 
     * @phpstan-return DraftsPerTonnage
     */
    public function fetchDraftsPerTonnage(): array
    {
        $statement = "SELECT * FROM drafts_par_tonnage";

        $draftsPerTonnageRequest = $this->mysql->query($statement);

        if (!$draftsPerTonnageRequest) {
            throw new DBException("Impossible de récupérer les tirants d'eau.");
        }

        $draftsPerTonnage = $draftsPerTonnageRequest->fetchAll();

        return $draftsPerTonnage;
    }

    /**
     * Récupère les stats consignation.
     * 
     * @param ShippingFilterDTO $filter
     * 
     * @return ShippingStatsSummaryDTO Stats consignation.
     */
    public function fetchStatsSummary(ShippingFilterDTO $filter): ShippingStatsSummaryDTO
    {
        $sqlFilter =
            $filter->getSqlShipFilter()
            . $filter->getSqlCargoFilter()
            . $filter->getSqlShipOwnerFilter()
            . $filter->getSqlCustomerFilter()
            . $filter->getSqlLastPortFilter()
            . $filter->getSqlNextPortFilter();

        $callsStatement =
            "SELECT
                cp.id,
                cp.etc_date as `date`
            FROM consignation_planning cp
            LEFT JOIN consignation_escales_marchandises cem ON cem.escale_id = cp.id
            WHERE cp.etc_date BETWEEN :startDate AND :endDate
            $sqlFilter
            GROUP BY cp.id -- Permet de ne pas compter plusieurs fois le même RDV si plusieurs marchandises
            ";

        $callsRequest = $this->mysql->prepare($callsStatement);

        $callsRequest->execute([
            "startDate" => $filter->getSqlStartDate(),
            "endDate" => $filter->getSqlEndDate()
        ]);

        /** @phpstan-var ShippingStatsSummaryArray $statsSummaryRaw */
        $statsSummaryRaw = $callsRequest->fetchAll();

        $statsSummary = new ShippingStatsSummaryDTO($statsSummaryRaw);

        return $statsSummary;
    }

    /**
     * Récupère les détails des stats consignation.
     * 
     * @param int[] $ids Identifiants des escales.
     * 
     * @return ShippingStatsDetailsDTO Détails des stats.
     */
    public function fetchStatsDetails(array $ids): ShippingStatsDetailsDTO
    {
        if (count($ids) === 0) {
            return new ShippingStatsDetailsDTO([]);
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

        if (!$callsRequest) {
            throw new DBException("Impossible de récupérer les détails des stats.");
        }

        /** @phpstan-var ShippingStatsDetailsArray $statsDetailsRaw */
        $statsDetailsRaw = $callsRequest->fetchAll();

        $statsDetails = new ShippingStatsDetailsDTO($statsDetailsRaw);

        return $statsDetails;
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

        if (!$request) {
            throw new DBException("Impossible de récupérer les noms de navire.");
        }

        $shipsNames = $request->fetchAll(\PDO::FETCH_COLUMN);

        return $shipsNames;
    }

    /**
     * Récupère la liste des navires en activité entre deux dates.
     * 
     * @param \DateTimeInterface $startDate Date de début.
     * @param \DateTimeInterface $endDate   Date de fin.
     * 
     * @return array Liste des navires en activité.
     * 
     * @phpstan-return ShipsInOps
     */
    public function fetchShipsInOps(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): array {
        $statement =
            "SELECT navire, ops_date AS debut, etc_date AS fin
            FROM consignation_planning
            WHERE ops_date <= :endDate AND etc_date >= :startDate
            ORDER BY debut";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            "startDate" => $startDate->format('Y-m-d'),
            "endDate" => $endDate->format('Y-m-d'),
        ]);

        /** @phpstan-var ShipsInOps */
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

        if (!$request) {
            throw new DBException("Impossible de récupérer les marchandises.");
        }

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

        if (!$request) {
            throw new DBException("Impossible de récupérer les clients.");
        }

        $customersNames = $request->fetchAll(\PDO::FETCH_COLUMN);

        return $customersNames;
    }
}
