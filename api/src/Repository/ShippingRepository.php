<?php

// Path: api/src/Repository/ShippingRespository.php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\ETAConverter;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Shipping\ShippingCall;
use App\Entity\Shipping\ShippingCallCargo;
use App\Service\ShippingService;

class ShippingRepository extends Repository
{
    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function exists(int $id)
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
                'operation' => $cargo->getOperation(true),
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
                'operation' => $cargo->getOperation(true),
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
}
