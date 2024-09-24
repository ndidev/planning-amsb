<?php

namespace App\Models\Consignation;

use App\Models\Model;
use App\Core\ETAConverter;

class EscaleModel extends Model
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
     * @return array Toutes les escale récupérées
     */
    public function readAll(array $filter = []): array
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
        $callsRequest = $this->mysql->query($callsStatement);
        $calls = $callsRequest->fetchAll();

        $cargoesRequest = $this->mysql->prepare($cargoesStatement);

        foreach ($calls as &$call) {
            $id = $call["id"];

            // ETA
            $call["eta_heure"] = ETAConverter::toLetters($call["eta_heure"]);

            // TE
            $call["te_arrivee"] = $call["te_arrivee"] !== NULL ? (float) $call["te_arrivee"] : NULL;
            $call["te_depart"] = $call["te_depart"] !== NULL ? (float) $call["te_depart"] : NULL;

            // Marchandises
            $cargoesRequest->execute(["id" => $id]);
            $cargoes = $cargoesRequest->fetchAll();
            foreach ($cargoes as &$cargo) {
                $cargo["environ"] = (bool) $cargo["environ"];
                $cargo["tonnage_bl"] = $cargo["tonnage_bl"] !== NULL ? (float) $cargo["tonnage_bl"] : NULL;
                $cargo["cubage_bl"] = $cargo["cubage_bl"] !== NULL ? (float) $cargo["cubage_bl"] : NULL;
                $cargo["nombre_bl"] = $cargo["nombre_bl"] !== NULL ? (int) $cargo["nombre_bl"] : NULL;
                $cargo["tonnage_outturn"] = $cargo["tonnage_outturn"] !== NULL ? (float) $cargo["tonnage_outturn"] : NULL;
                $cargo["cubage_outturn"] = $cargo["cubage_outturn"] !== NULL ? (float) $cargo["cubage_outturn"] : NULL;
                $cargo["nombre_outturn"] = $cargo["nombre_outturn"] !== NULL ? (int) $cargo["nombre_outturn"] : NULL;
            }

            $call["marchandises"] = $cargoes;
        }

        return $calls;
    }

    /**
     * Récupère une escale consignation.
     * 
     * @param int $id ID de l'escale à récupérer
     * 
     * @return array Escale récupérée
     */
    public function read($id): ?array
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
        $call = $callRequest->fetch();

        if (!$call) return null;

        // ETA
        $call["eta_heure"] = ETAConverter::toLetters($call["eta_heure"]);

        // TE
        $call["te_arrivee"] = $call["te_arrivee"] !== NULL ? (float) $call["te_arrivee"] : NULL;
        $call["te_depart"] = $call["te_depart"] !== NULL ? (float) $call["te_depart"] : NULL;


        // Marchandises
        $cargoesRequest = $this->mysql->prepare($cargoesStatement);
        $cargoesRequest->execute(["id" => $id]);
        $cargoes = $cargoesRequest->fetchAll();

        if ($call) {
            foreach ($cargoes as &$cargo) {
                $cargo["environ"] = (bool) $cargo["environ"];
                $cargo["tonnage_bl"] = $cargo["tonnage_bl"] !== NULL ? (float) $cargo["tonnage_bl"] : NULL;
                $cargo["cubage_bl"] = $cargo["cubage_bl"] !== NULL ? (float) $cargo["cubage_bl"] : NULL;
                $cargo["nombre_bl"] = $cargo["nombre_bl"] !== NULL ? (int) $cargo["nombre_bl"] : NULL;
                $cargo["tonnage_outturn"] = $cargo["tonnage_outturn"] !== NULL ? (float) $cargo["tonnage_outturn"] : NULL;
                $cargo["cubage_outturn"] = $cargo["cubage_outturn"] !== NULL ? (float) $cargo["cubage_outturn"] : NULL;
                $cargo["nombre_outturn"] = $cargo["nombre_outturn"] !== NULL ? (int) $cargo["nombre_outturn"] : NULL;
            }

            $call["marchandises"] = $cargoes;
        }

        return $call;
    }

    /**
     * Crée une escale consignation.
     * 
     * @param array $input Eléments de l'escale à créer
     * 
     * @return array Escale créée
     */
    public function create(array $input): array
    {
        // Champs dates et TE
        $input["eta_date"] = $input["eta_date"] ?: NULL;
        $input["nor_date"] = $input["nor_date"] ?: NULL;
        $input["pob_date"] = $input["pob_date"] ?: NULL;
        $input["etb_date"] = $input["etb_date"] ?: NULL;
        $input["ops_date"] = $input["ops_date"] ?: NULL;
        $input["etc_date"] = $input["etc_date"] ?: NULL;
        $input["etd_date"] = $input["etd_date"] ?: NULL;
        $input["te_arrivee"] = $input["te_arrivee"] === "" ? NULL : $input["te_arrivee"];
        $input["te_depart"] = $input["te_depart"] === "" ? NULL : $input["te_depart"];

        $callStatement =
            "INSERT INTO consignation_planning
            VALUES(
                NULL,
                :navire,
                :voyage,
                :armateur,
                :eta_date,
                :eta_heure,
                :nor_date,
                :nor_heure,
                :pob_date,
                :pob_heure,
                :etb_date,
                :etb_heure,
                :ops_date,
                :ops_heure,
                :etc_date,
                :etc_heure,
                :etd_date,
                :etd_heure,
                :te_arrivee,
                :te_depart,
                :last_port,
                :next_port,
                :call_port,
                :quai,
                :commentaire
            )";

        $insertCargoStatement =
            "INSERT INTO consignation_escales_marchandises
            VALUES(
                NULL,
                :escale_id,
                :marchandise,
                :client,
                :operation,
                :environ,
                :tonnage_bl,
                :cubage_bl,
                :nombre_bl,
                :tonnage_outturn,
                :cubage_outturn,
                :nombre_outturn
            )";

        $callRequest = $this->mysql->prepare($callStatement);

        $this->mysql->beginTransaction();
        $callRequest->execute([
            'navire' => $input["navire"] ?: "TBN",
            'voyage' => $input["voyage"],
            'armateur' => $input["armateur"] ?: NULL,
            'eta_date' => $input["eta_date"],
            'eta_heure' => ETAConverter::toDigits($input["eta_heure"]),
            'nor_date' => $input["nor_date"],
            'nor_heure' => $input["nor_heure"],
            'pob_date' => $input["pob_date"],
            'pob_heure' => $input["pob_heure"],
            'etb_date' => $input["etb_date"],
            'etb_heure' => $input["etb_heure"],
            'ops_date' => $input["ops_date"],
            'ops_heure' => $input["ops_heure"],
            'etc_date' => $input["etc_date"],
            'etc_heure' => $input["etc_heure"],
            'etd_date' => $input["etd_date"],
            'etd_heure' => $input["etd_heure"],
            'te_arrivee' => $input["te_arrivee"],
            'te_depart' => $input["te_depart"],
            'last_port' => $input["last_port"] ?? "",
            'next_port' => $input["next_port"] ?? "",
            'call_port' => $input["call_port"],
            'quai' => $input["quai"],
            'commentaire' => $input["commentaire"],
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        // Marchandises
        $insertCargoRequest = $this->mysql->prepare($insertCargoStatement);
        $cargoes = $input["marchandises"] ?? [];
        foreach ($cargoes as $cargo) {
            $insertCargoRequest->execute([
                'escale_id' => $lastInsertId,
                'marchandise' => $cargo["marchandise"],
                'client' => $cargo["client"],
                'operation' => $cargo["operation"],
                'environ' => $cargo["environ"] ? 1 : 0,
                'tonnage_bl' => is_null($cargo["tonnage_bl"]) ? NULL : (float) $cargo["tonnage_bl"],
                'cubage_bl' => is_null($cargo["cubage_bl"]) ? NULL : (float) $cargo["cubage_bl"],
                'nombre_bl' => is_null($cargo["nombre_bl"]) ? NULL : (int) $cargo["nombre_bl"],
                'tonnage_outturn' => is_null($cargo["tonnage_outturn"]) ? NULL : (float) $cargo["tonnage_outturn"],
                'cubage_outturn' => is_null($cargo["cubage_outturn"]) ? NULL : (float) $cargo["cubage_outturn"],
                'nombre_outturn' => is_null($cargo["nombre_outturn"]) ? NULL : (int) $cargo["nombre_outturn"],
            ]);
        }

        return $this->read($lastInsertId);
    }

    /**
     * Met à jour une escale consignation.
     * 
     * @param int   $id    ID de l'escale à modifier
     * @param array $input Eléments de l'escale à modifier
     * 
     * @return array Escale modifiée
     */
    public function update($id, array $input): array
    {
        // Champs dates et TE
        $input["eta_date"] = $input["eta_date"] ?: NULL;
        $input["nor_date"] = $input["nor_date"] ?: NULL;
        $input["pob_date"] = $input["pob_date"] ?: NULL;
        $input["etb_date"] = $input["etb_date"] ?: NULL;
        $input["ops_date"] = $input["ops_date"] ?: NULL;
        $input["etc_date"] = $input["etc_date"] ?: NULL;
        $input["etd_date"] = $input["etd_date"] ?: NULL;
        $input["te_arrivee"] = $input["te_arrivee"] === "" ? NULL : $input["te_arrivee"];
        $input["te_depart"] = $input["te_depart"] === "" ? NULL : $input["te_depart"];

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

        $insertCargoStatement =
            "INSERT INTO consignation_escales_marchandises
            VALUES(
                NULL,
                :escale_id,
                :marchandise,
                :client,
                :operation,
                :environ,
                :tonnage_bl,
                :cubage_bl,
                :nombre_bl,
                :tonnage_outturn,
                :cubage_outturn,
                :nombre_outturn
            )";

        $updateCargoStatement =
            "UPDATE consignation_escales_marchandises
            SET
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
            WHERE id = :id";

        $callRequest = $this->mysql->prepare($callStatement);
        $callRequest->execute([
            'navire' => $input["navire"] ?: "TBN",
            'voyage' => $input["voyage"],
            'armateur' => $input["armateur"] ?: NULL,
            'eta_date' => $input["eta_date"],
            'eta_heure' => ETAConverter::toDigits($input["eta_heure"]),
            'nor_date' => $input["nor_date"],
            'nor_heure' => $input["nor_heure"],
            'pob_date' => $input["pob_date"],
            'pob_heure' => $input["pob_heure"],
            'etb_date' => $input["etb_date"],
            'etb_heure' => $input["etb_heure"],
            'ops_date' => $input["ops_date"],
            'ops_heure' => $input["ops_heure"],
            'etc_date' => $input["etc_date"],
            'etc_heure' => $input["etc_heure"],
            'etd_date' => $input["etd_date"],
            'etd_heure' => $input["etd_heure"],
            'te_arrivee' => $input["te_arrivee"],
            'te_depart' => $input["te_depart"],
            'last_port' => $input["last_port"] ?? "",
            'next_port' => $input["next_port"] ?? "",
            'call_port' => $input["call_port"],
            'quai' => $input["quai"],
            'commentaire' => $input["commentaire"],
            'id' => $id
        ]);

        // MARCHANDISES
        // Suppression marchandises
        // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE marchandise POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
        // Comparaison du tableau transmis par POST avec la liste existante des marchandises pour le produit concerné
        $existingCargoesIdsRequest = $this->mysql->prepare(
            "SELECT id FROM consignation_escales_marchandises WHERE escale_id = :escale_id"
        );
        $existingCargoesIdsRequest->execute(['escale_id' => $id]);
        $existingCargoesIds = $existingCargoesIdsRequest->fetchAll(\PDO::FETCH_COLUMN);

        $submittedCargoesIds = array_map(fn(array $cargo) => $cargo["id"], $input["marchandises"] ?? []);
        $cargoesIdsToBeDeleted = array_diff($existingCargoesIds, $submittedCargoesIds);

        if (count($cargoesIdsToBeDeleted) > 0) {
            $this->mysql->exec("DELETE FROM consignation_escales_marchandises WHERE id IN (" . implode(",", $cargoesIdsToBeDeleted) . ")");
        }

        // Ajout et modification marchandises
        $insertCargoRequest = $this->mysql->prepare($insertCargoStatement);
        $updateCargoRequest = $this->mysql->prepare($updateCargoStatement);
        $cargoes = $input["marchandises"] ?? [];
        foreach ($cargoes as $cargo) {
            if ((int) $cargo["id"]) {
                $updateCargoRequest->execute([
                    'marchandise' => $cargo["marchandise"],
                    'client' => $cargo["client"],
                    'operation' => $cargo["operation"],
                    'environ' => $cargo["environ"] ? 1 : 0,
                    'tonnage_bl' => is_null($cargo["tonnage_bl"]) ? NULL : (float) $cargo["tonnage_bl"],
                    'cubage_bl' => is_null($cargo["cubage_bl"]) ? NULL : (float) $cargo["cubage_bl"],
                    'nombre_bl' => is_null($cargo["nombre_bl"]) ? NULL : (int) $cargo["nombre_bl"],
                    'tonnage_outturn' => is_null($cargo["tonnage_outturn"]) ? NULL : (float) $cargo["tonnage_outturn"],
                    'cubage_outturn' => is_null($cargo["cubage_outturn"]) ? NULL : (float) $cargo["cubage_outturn"],
                    'nombre_outturn' => is_null($cargo["nombre_outturn"]) ? NULL : (int) $cargo["nombre_outturn"],
                    'id' => $cargo["id"]
                ]);
            } else {
                $insertCargoRequest->execute([
                    'escale_id' => $id,
                    'marchandise' => $cargo["marchandise"],
                    'client' => $cargo["client"],
                    'operation' => $cargo["operation"],
                    'environ' => $cargo["environ"] ? 1 : 0,
                    'tonnage_bl' => $cargo["tonnage_bl"],
                    'cubage_bl' => $cargo["cubage_bl"],
                    'nombre_bl' => $cargo["nombre_bl"],
                    'tonnage_outturn' => $cargo["tonnage_outturn"],
                    'cubage_outturn' => $cargo["cubage_outturn"],
                    'nombre_outturn' => $cargo["nombre_outturn"]
                ]);
            }
        }

        return $this->read($id);
    }

    /**
     * Supprime une escale consignation.
     * 
     * @param int $id ID de l'escale à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function delete(int $id): bool
    {
        $deleteRequest = $this->mysql->prepare("DELETE FROM consignation_planning WHERE id = :id");
        $isDeleted = $deleteRequest->execute(["id" => $id]);

        return $isDeleted;
    }
}
