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
use ReflectionClass;

/**
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
 * @phpstan-import-type ShippingCallArray from \App\Entity\Shipping\ShippingCall
 * @phpstan-import-type ShippingCallCargoArray from \App\Entity\Shipping\ShippingCallCargo
 * @phpstan-import-type ShippingStatsSummaryArray from \App\DTO\ShippingStatsSummaryDTO
 * @phpstan-import-type ShippingStatsDetailsArray from \App\DTO\ShippingStatsDetailsDTO
 */
final class ShippingRepository extends Repository
{
    /** @var ReflectionClass<ShippingCall> */
    private ReflectionClass $callReflector;

    /**
     * @var array{
     *        cargoes: array<int, ShippingCallCargo>,
     *      }
     */
    private static array $cache = [
        'cargoes' => [],
    ];

    public function __construct(private ShippingService $shippingService)
    {
        $this->callReflector = new ReflectionClass(ShippingCall::class);
    }

    // =====
    // Calls
    // =====

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function callExists(int $id): bool
    {
        return $this->mysql->exists("consignation_planning", $id);
    }

    /**
     * Récupère toutes les escales consignation.
     * 
     * @return Collection<ShippingCall> Toutes les escale récupérées
     */
    public function fetchAllCalls(ShippingFilterDTO $filter): Collection
    {
        $sqlFilter = $filter->getSqlFilter();

        $callsStatement =
            "SELECT
                cp.id,
                cp.stevedoring_ship_report_id as `shipReportId`,
                cp.navire,
                cp.voyage,
                cp.armateur,
                cp.eta_date,
                cp.eta_heure,
                cp.nor_date,
                cp.nor_heure,
                cp.pob_date,
                cp.pob_heure,
                cp.etb_date,
                cp.etb_heure,
                cp.ops_date,
                cp.ops_heure,
                cp.etc_date,
                cp.etc_heure,
                cp.etd_date,
                cp.etd_heure,
                cp.te_arrivee,
                cp.te_depart,
                cp.last_port,
                cp.next_port,
                cp.call_port,
                cp.quai,
                cp.commentaire
            FROM consignation_planning cp
            LEFT JOIN consignation_escales_marchandises cem ON cem.escale_id = cp.id
            WHERE
                    (etd_date >= :startDate OR etd_date IS NULL)
                AND (eta_date <= :endDate OR eta_date IS NULL)
                $sqlFilter
            ORDER BY
                -eta_date DESC,
                eta_heure,
                -etb_date DESC,
                etb_heure";

        // Escales
        $callsRequest = $this->mysql->prepare($callsStatement);

        if (!$callsRequest) {
            throw new DBException("Impossible de récupérer les escales.");
        }

        $callsRequest->execute([
            "startDate" => $filter->getSqlStartDate(),
            "endDate" => $filter->getSqlEndDate(),
        ]);

        /** @phpstan-var ShippingCallArray[] $callsRaw */
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
            WHERE escale_id IN (" . \implode(",", $callsIds) . ")";

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
            $call->etaTime = ETAConverter::toLetters($call->etaTime);

            $filteredCargoesRaw = \array_values(
                \array_filter(
                    $cargoesRaw,
                    fn($cargo) => ($cargo["escale_id"]) === $call->id
                )
            );

            $cargoes = \array_map(
                fn($cargoRaw) => $this->shippingService->makeShippingCallCargoFromDatabase($cargoRaw),
                $filteredCargoesRaw
            );

            $call->cargoes = $cargoes;

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
        /** @var array<int, ShippingCall> */
        static $cache = [];

        if (isset($cache[$id])) {
            return $cache[$id];
        }

        if (!$this->callExists($id)) {
            return null;
        }

        /** @var ShippingCall */
        $call = $this->callReflector->newLazyProxy(
            function () use ($id) {
                $callStatement =
                    "SELECT
                        id,
                        stevedoring_ship_report_id as `shipReportId`,
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
                /** @var ShippingCallArray */
                $callRaw = $this->mysql
                    ->prepareAndExecute($callStatement, ["id" => $id])
                    ->fetch();

                // Marchandises
                /** @var ShippingCallCargoArray[] */
                $cargoesRaw = $this->mysql
                    ->prepareAndExecute($cargoesStatement, ["id" => $id])
                    ->fetchAll();

                $callRaw["marchandises"] = $cargoesRaw;

                return $this->shippingService->makeShippingCallFromDatabase($callRaw);
            }
        );

        $this->callReflector->getProperty('id')->setRawValueWithoutLazyInitialization($call, $id);

        $cache[$id] = $call;

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
                navire = :shipName,
                voyage = :voyageNumber,
                armateur = :shipOperatorId,
                eta_date = :etaDate,
                eta_heure = :etaTime,
                nor_date = :norDate,
                nor_heure = :norTime,
                pob_date = :pobDate,
                pob_heure = :pobTime,
                etb_date = :etbDate,
                etb_heure = :etbTime,
                ops_date = :opsDate,
                ops_heure = :opsTime,
                etc_date = :etcDate,
                etc_heure = :etcTime,
                etd_date = :etdDate,
                etd_heure = :etdTime,
                te_arrivee = :arrivalDraft,
                te_depart = :departureDraft,
                last_port = :lastPort,
                next_port = :nextPort,
                call_port = :callPort,
                quai = :berth,
                commentaire = :comment";

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

        try {
            $this->mysql->beginTransaction();

            $this->mysql->prepareAndExecute($callStatement, [
                'shipName' => $call->shipName,
                'voyageNumber' => $call->voyageNumber,
                'shipOperatorId' => $call->shipOperator?->id,
                'etaDate' => $call->etaDate?->format('Y-m-d'),
                'etaTime' => ETAConverter::toDigits($call->etaTime),
                'norDate' => $call->norDate?->format('Y-m-d'),
                'norTime' => $call->norTime,
                'pobDate' => $call->pobDate?->format('Y-m-d'),
                'pobTime' => $call->pobTime,
                'etbDate' => $call->etbDate?->format('Y-m-d'),
                'etbTime' => $call->etbTime,
                'opsDate' => $call->opsDate?->format('Y-m-d'),
                'opsTime' => $call->opsTime,
                'etcDate' => $call->etcDate?->format('Y-m-d'),
                'etcTime' => $call->etcTime,
                'etdDate' => $call->etdDate?->format('Y-m-d'),
                'etdTime' => $call->etdTime,
                'arrivalDraft' => $call->arrivalDraft,
                'departureDraft' => $call->departureDraft,
                'lastPort' => $call->lastPort->locode ?? '',
                'nextPort' => $call->nextPort->locode ?? '',
                'callPort' => $call->callPort,
                'berth' => $call->berth,
                'comment' => $call->comment,
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            // Marchandises
            $this->mysql->prepareAndExecute(
                $insertCargoStatement,
                $call->cargoes->map(
                    function ($cargo) use ($lastInsertId) {
                        return [
                            'escale_id' => $lastInsertId,
                            'marchandise' => $cargo->cargoName,
                            'client' => $cargo->customer,
                            'operation' => $cargo->operation,
                            'environ' => (int) $cargo->isApproximate,
                            'tonnage_bl' => $cargo->blTonnage,
                            'cubage_bl' => $cargo->blVolume,
                            'nombre_bl' => $cargo->blUnits,
                            'tonnage_outturn' => $cargo->outturnTonnage,
                            'cubage_outturn' => $cargo->outturnVolume,
                            'nombre_outturn' => $cargo->outturnUnits,
                        ];
                    }
                )
            );

            $this->mysql->commit();
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();
            throw new DBException("Erreur lors de la création", previous: $e);
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
        $id = $call->id;

        if (!$id) {
            throw new ClientException("ID de l'escale manquant");
        }

        try {
            $this->mysql->beginTransaction();

            $callStatement =
                "UPDATE consignation_planning
            SET
                navire = :shipName,
                voyage = :voyageNumber,
                armateur = :shipOperatorId,
                eta_date = :etaDate,
                eta_heure = :etaTime,
                nor_date = :norDate,
                nor_heure = :norTime,
                pob_date = :pobDate,
                pob_heure = :pobTime,
                etb_date = :etbDate,
                etb_heure = :etbTime,
                ops_date = :opsDate,
                ops_heure = :opsTime,
                etc_date = :etcDate,
                etc_heure = :etcTime,
                etd_date = :etdDate,
                etd_heure = :etdTime,
                te_arrivee = :arrivalDraft,
                te_depart = :departureDraft,
                last_port = :lastPort,
                next_port = :nextPort,
                call_port = :callPort,
                quai = :berth,
                commentaire = :comment
            WHERE id = :id";

            $cargoStatement =
                "INSERT INTO consignation_escales_marchandises
            SET
                id = :id,
                escale_id = :escale_id,
                ship_report_id = :shipReportId,
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
                escale_id = :escale_id,
                ship_report_id = :shipReportId,
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
                'shipName' => $call->shipName,
                'voyageNumber' => $call->voyageNumber,
                'shipOperatorId' => $call->shipOperator?->id,
                'etaDate' => $call->etaDate?->format('Y-m-d'),
                'etaTime' => ETAConverter::toDigits($call->etaTime),
                'norDate' => $call->norDate?->format('Y-m-d'),
                'norTime' => $call->norTime,
                'pobDate' => $call->pobDate?->format('Y-m-d'),
                'pobTime' => $call->pobTime,
                'etbDate' => $call->etbDate?->format('Y-m-d'),
                'etbTime' => $call->etbTime,
                'opsDate' => $call->opsDate?->format('Y-m-d'),
                'opsTime' => $call->opsTime,
                'etcDate' => $call->etcDate?->format('Y-m-d'),
                'etcTime' => $call->etcTime,
                'etdDate' => $call->etdDate?->format('Y-m-d'),
                'etdTime' => $call->etdTime,
                'arrivalDraft' => $call->arrivalDraft,
                'departureDraft' => $call->departureDraft,
                'lastPort' => $call->lastPort->locode ?? '',
                'nextPort' => $call->nextPort->locode ?? '',
                'callPort' => $call->callPort,
                'berth' => $call->berth,
                'comment' => $call->comment,
                'id' => $call->id,
            ]);

            // MARCHANDISES
            // Suppression marchandises
            // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE marchandise POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
            // Comparaison du tableau transmis par POST avec la liste existante des marchandises pour le produit concerné
            $existingCargoesIdsRequest = $this->mysql->prepare(
                "SELECT id FROM consignation_escales_marchandises WHERE escale_id = :callId"
            );
            $existingCargoesIdsRequest->execute(['callId' => $call->id]);
            $existingCargoesIds = $existingCargoesIdsRequest->fetchAll(\PDO::FETCH_COLUMN);

            $submittedCargoesIds = \array_map(fn($cargo) => $cargo->id, $call->cargoes->asArray());
            $cargoesIdsToBeDeleted = \array_diff($existingCargoesIds, $submittedCargoesIds);

            if (\count($cargoesIdsToBeDeleted) > 0) {
                $this->mysql->exec("DELETE FROM consignation_escales_marchandises WHERE id IN (" . \implode(",", $cargoesIdsToBeDeleted) . ")");
            }

            // Ajout et modification marchandises
            $cargoRequest = $this->mysql->prepare($cargoStatement);
            $cargoes = $call->cargoes;
            foreach ($cargoes as $cargo) {
                $cargoRequest->execute([
                    'id' => $cargo->id,
                    'escale_id' => $call->id,
                    'shipReportId' => $cargo->shipReport?->id,
                    'marchandise' => $cargo->cargoName,
                    'client' => $cargo->customer,
                    'operation' => $cargo->operation,
                    'environ' => (int) $cargo->isApproximate,
                    'tonnage_bl' => $cargo->blTonnage,
                    'cubage_bl' => $cargo->blVolume,
                    'nombre_bl' => $cargo->blUnits,
                    'tonnage_outturn' => $cargo->outturnTonnage,
                    'cubage_outturn' => $cargo->outturnVolume,
                    'nombre_outturn' => $cargo->outturnUnits,
                ]);
            }

            $this->updateLinkedShipReport($call);

            $this->mysql->commit();
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();
            throw new DBException("Erreur lors de la mise à jour", previous: $e);
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

    private function updateLinkedShipReport(ShippingCall $shippingCall): void
    {
        if (!$shippingCall->shipReport) {
            return;
        }

        $this->mysql->prepareAndExecute(
            "UPDATE stevedoring_ship_reports
            SET
                ship = :ship,
                port = :port,
                berth = :berth
            WHERE id = :reportId",
            [
                "ship" => $shippingCall->shipName,
                "port" => $shippingCall->callPort,
                "berth" => $shippingCall->berth,
                "reportId" => $shippingCall->shipReport->id,
            ]
        );
    }

    // =======
    // Cargoes
    // =======

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function cargoEntryExists(int $id): bool
    {
        return $this->mysql->exists("consignation_escales_marchandises", $id);
    }

    public function fetchCargoEntry(int $cargoId): ?ShippingCallCargo
    {
        if (isset(static::$cache['cargoes'][$cargoId])) {
            return static::$cache['cargoes'][$cargoId];
        }

        try {
            $statement = "SELECT * FROM consignation_escales_marchandises WHERE id = :cargoId";

            /** @var ?ShippingCallCargoArray */
            $cargoRaw = $this->mysql->prepareAndExecute($statement, ["cargoId" => $cargoId])->fetch();

            if (!\is_array($cargoRaw)) return null;

            $cargo = $this->shippingService->makeShippingCallCargoFromDatabase($cargoRaw);

            static::$cache['cargoes'][$cargoId] = $cargo;

            return $cargo;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer la marchandise.", previous: $e);
        }
    }

    /**
     * @param int $callId 
     * 
     * @return Collection<ShippingCallCargo>
     */
    public function fetchCargoEntriesForCall(int $callId): Collection
    {
        $statement = "SELECT * FROM consignation_escales_marchandises WHERE escale_id = :callId";

        try {
            /** @phpstan-var ShippingCallCargoArray[] */
            $cargoesRaw = $this->mysql
                ->prepareAndExecute($statement, ["callId" => $callId])
                ->fetchAll();

            $cargoes = \array_map(
                fn($cargoRaw) => $this->shippingService->makeShippingCallCargoFromDatabase($cargoRaw),
                $cargoesRaw
            );

            return new Collection($cargoes);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les marchandises.", previous: $e);
        }
    }

    /**
     * @param int $reportId 
     * 
     * @return Collection<ShippingCallCargo>
     */
    public function fetchCargoEntriesForShipReport(int $reportId): Collection
    {
        $statement = "SELECT * FROM consignation_escales_marchandises WHERE ship_report_id = :reportId";

        try {
            /** @phpstan-var ShippingCallCargoArray[] */
            $cargoesRaw = $this->mysql
                ->prepareAndExecute($statement, ["reportId" => $reportId])
                ->fetchAll();

            $cargoes = \array_map(
                fn($cargoRaw) => $this->shippingService->makeShippingCallCargoFromDatabase($cargoRaw),
                $cargoesRaw
            );

            return new Collection($cargoes);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les marchandises.", previous: $e);
        }
    }

    // ======
    // Others
    // ======

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

        if (!\is_string($voyageNumber)) {
            $voyageNumber = '';
        }

        return $voyageNumber;
    }

    /**
     * Récupère tous les tirants d'eau du planning consignation.
     * 
     * @return DraftsPerTonnage Tous les tirants d'eau récupérés
     */
    public function fetchDraftsPerTonnage(): array
    {
        try {
            $statement = "SELECT * FROM drafts_par_tonnage";

            /** @var DraftsPerTonnage */
            $draftsPerTonnage = $this->mysql
                ->prepareAndExecute($statement)
                ->fetchAll();

            return $draftsPerTonnage;
        } catch (\PDOException $e) {
            throw new DBException("Impossible de récupérer les tirants d'eau.", previous: $e);
        }
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
        $sqlFilter = $filter->getSqlFilter();

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
        $idsAsString = \join(",", $ids);

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

        /** @var string[] */
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

        /** @var string[] */
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

        /** @var string[] */
        $customersNames = $request->fetchAll(\PDO::FETCH_COLUMN);

        return $customersNames;
    }
}
