<?php

namespace App\Models\Chartering;

use App\Models\Model;

class CharterModel extends Model
{
    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function exists(int $id)
    {
        return $this->mysql->exists("chartering_registre", $id);
    }

    /**
     * Récupère tous les affrètements maritimes.
     * 
     * @param array $filter
     * 
     * @return array Tous les affrètements récupérés
     */
    public function readAll(array $filter = []): array
    {
        // Filtre
        $startDate = isset($filter["date_debut"]) ? $filter['date_debut'] : "0001-01-01";
        $endDate = isset($filter["date_fin"]) ? $filter['date_fin'] : "9999-12-31";
        $statusFilter = $filter["statut"] ?? "";
        $chartererFilter = trim($filter['affreteur'] ?? "", ",");
        $ownerFilter = trim($filter['armateur'] ?? "", ",");
        $brokerFilter = trim($filter['courtier'] ?? "", ",");

        $sqlStatusFilter = $statusFilter === "" ? "" : " AND statut IN ($statusFilter)";
        $sqlChartererFilter = $chartererFilter === "" ? "" : " AND affreteur IN ($chartererFilter)";
        $sqlOwnerFilter = $ownerFilter === "" ? "" : " AND armateur IN ($ownerFilter)";
        $sqlBrokerFilter = $brokerFilter === "" ? "" : " AND courtier IN ($brokerFilter)";

        $sqlFilter =
            $sqlChartererFilter
            . $sqlOwnerFilter
            . $sqlBrokerFilter
            . $sqlStatusFilter;

        $archiveFilter = (int) array_key_exists("archives", $filter);

        $statement_charters =
            "SELECT
                id,
                statut,
                -- Laycan
                lc_debut,
                lc_fin,
                -- C/P
                cp_date,
                -- Navire
                navire,
                -- Tiers
                affreteur,
                armateur,
                courtier,
                -- Montants
                fret_achat,
                fret_vente,
                surestaries_achat,
                surestaries_vente,
                -- Divers
                commentaire,
                archive
            FROM chartering_registre
            WHERE archive = $archiveFilter
            AND (lc_debut <= :date_fin OR lc_debut IS NULL)
            AND (lc_fin >= :date_debut OR lc_fin IS NULL)
            $sqlFilter
            ORDER BY " . ($archiveFilter ? "-lc_debut ASC, -lc_fin ASC" : "-lc_debut DESC, -lc_fin DESC");

        $legsStatement = "SELECT * FROM chartering_detail WHERE charter = :id";

        // Charters
        $chartersRequest = $this->mysql->prepare($statement_charters);
        $chartersRequest->execute([
            "date_debut" => $startDate,
            "date_fin" => $endDate
        ]);
        $charters = $chartersRequest->fetchAll();

        $legsRequest = $this->mysql->prepare($legsStatement);

        foreach ($charters as &$charter) {
            $charter["archive"] = (bool) $charter["archive"];
            $charter["affreteur"] = (int) $charter["affreteur"] ?: NULL;
            $charter["armateur"] = (int) $charter["armateur"] ?: NULL;
            $charter["courtier"] = (int) $charter["courtier"] ?: NULL;
            $charter["fret_achat"] = (float) $charter["fret_achat"];
            $charter["fret_vente"] = (float) $charter["fret_vente"];
            $charter["surestaries_achat"] = (float) $charter["surestaries_achat"];
            $charter["surestaries_vente"] = (float) $charter["surestaries_vente"];

            // Détails
            $legsRequest->execute(["id" => $charter["id"]]);
            $legs = $legsRequest->fetchAll();

            $charter["legs"] = $legs;
        }

        return $charters;
    }

    /**
     * Récupère un affrètement maritime.
     * 
     * @param int $id ID de l'affrètement à récupérer
     * 
     * @return array Rendez-vous récupéré
     */
    public function read($id): ?array
    {
        $charterStatement =
            "SELECT
                id,
                statut,
                -- Laycan
                lc_debut,
                lc_fin,
                -- C/P
                cp_date,
                -- Navire
                navire,
                -- Tiers
                affreteur,
                armateur,
                courtier,
                -- Montants
                fret_achat,
                fret_vente,
                surestaries_achat,
                surestaries_vente,
                -- Divers
                commentaire,
                archive
            FROM chartering_registre
            WHERE id = :id";

        $legsStatement = "SELECT * FROM chartering_detail WHERE charter = :id";

        // Charters
        $charterRequest = $this->mysql->prepare($charterStatement);
        $charterRequest->execute(["id" => $id]);
        $charter = $charterRequest->fetch();

        if (!$charter) return null;

        // Legs
        $legsRequest = $this->mysql->prepare($legsStatement);
        $legsRequest->execute(["id" => $id]);
        $legs = $legsRequest->fetchAll();


        $charter["archive"] = (bool) $charter["archive"];
        $charter["affreteur"] = (int) $charter["affreteur"] ?: NULL;
        $charter["armateur"] = (int) $charter["armateur"] ?: NULL;
        $charter["courtier"] = (int) $charter["courtier"] ?: NULL;
        $charter["fret_achat"] = (float) $charter["fret_achat"];
        $charter["fret_vente"] = (float) $charter["fret_vente"];
        $charter["surestaries_achat"] = (float) $charter["surestaries_achat"];
        $charter["surestaries_vente"] = (float) $charter["surestaries_vente"];

        $charter["legs"] = $legs;

        return $charter;
    }

    /**
     * Crée un affrètement maritime.
     * 
     * @param array $input Eléments de l'affrètement à créer
     * 
     * @return array Affrètement créé
     */
    public function create(array $input): array
    {
        // Champs dates
        $input["lc_debut"] = $input["lc_debut"] ?: NULL;
        $input["lc_fin"] = $input["lc_fin"] ?: NULL;
        $input["cp_date"] = $input["cp_date"] ?: NULL;

        // Champ navire vide
        $input["navire"] = $input["navire"] ?: "TBN";

        $charterStatement =
            "INSERT INTO chartering_registre
            VALUES(
                NULL,
                :statut,
                -- Laycan
                :lc_debut,
                :lc_fin,
                -- C/P
                :cp_date,
                -- Navire
                :navire,
                -- Tiers
                :affreteur,
                :armateur,
                :courtier,
                -- Montants
                :fret_achat,
                :fret_vente,
                :surestaries_achat,
                :surestaries_vente,
                -- Divers
                :commentaire,
                :archive
            )";

        $insertLegStatement =
            "INSERT INTO chartering_detail
            VALUES(
                NULL,
                :charter,
                :bl_date,
                :pol,
                :pod,
                :marchandise,
                :quantite,
                :commentaire
            )";

        $charterRequest = $this->mysql->prepare($charterStatement);

        $this->mysql->beginTransaction();
        $charterRequest->execute([
            "statut" => $input["statut"],
            // Laycan
            "lc_debut" => $input["lc_debut"],
            "lc_fin" => $input["lc_fin"],
            // C/P
            "cp_date" => $input["cp_date"],
            // Navire
            "navire" => $input["navire"],
            // Tiers
            "affreteur" => $input["affreteur"],
            "armateur" => $input["armateur"],
            "courtier" => $input["courtier"],
            // Montants
            "fret_achat" => is_null($input["fret_achat"]) ? NULL : (float) $input["fret_achat"],
            "fret_vente" => is_null($input["fret_vente"]) ? NULL : (float) $input["fret_vente"],
            "surestaries_achat" => is_null($input["surestaries_achat"]) ? NULL : (float) $input["surestaries_achat"],
            "surestaries_vente" => is_null($input["surestaries_vente"]) ? NULL : (float) $input["surestaries_vente"],
            // Divers
            "commentaire" => $input["commentaire"],
            "archive" => (int) $input["archive"],
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        // Détails
        $insertLegRequest = $this->mysql->prepare($insertLegStatement);
        $legs = $input["legs"] ?? [];
        foreach ($legs as $leg) {
            $insertLegRequest->execute([
                "charter" => $lastInsertId,
                "bl_date" => $leg["bl_date"],
                "marchandise" => $leg["marchandise"],
                "quantite" => $leg["quantite"],
                "pol" => $leg["pol"],
                "pod" => $leg["pod"],
                "commentaire" => $leg["commentaire"],
            ]);
        }

        return $this->read($lastInsertId);
    }

    /**
     * Met à jour un affrètement maritime.
     * 
     * @param int   $id     ID de l'affrètement à modifier
     * @param array $input  Eléments de l'affrètement à modifier
     * 
     * @return array Affretement modifié
     */
    public function update($id, array $input): array
    {
        // Champs dates
        $input["lc_debut"] = $input["lc_debut"] ?: NULL;
        $input["lc_fin"] = $input["lc_fin"] ?: NULL;
        $input["cp_date"] = $input["cp_date"] ?: NULL;

        // Champ navire vide
        $input["navire"] = $input["navire"] ?: "TBN";

        $charterStatement =
            "UPDATE chartering_registre
            SET
                statut = :statut,
                -- Laycan
                lc_debut = :lc_debut,
                lc_fin = :lc_fin,
                -- C/P
                cp_date = :cp_date,
                -- Navire
                navire = :navire,
                -- Tiers
                affreteur = :affreteur,
                armateur = :armateur,
                courtier = :courtier,
                -- Montants
                fret_achat = :fret_achat,
                fret_vente = :fret_vente,
                surestaries_achat = :surestaries_achat,
                surestaries_vente = :surestaries_vente,
                -- Divers
                commentaire = :commentaire,
                archive = :archive
            WHERE id = :id";

        $insertLegStatement =
            "INSERT INTO chartering_detail
            VALUES(
                NULL,
                :charter,
                :bl_date,
                :pol,
                :pod,
                :marchandise,
                :quantite,
                :commentaire
            )";

        $updateLegStatement =
            "UPDATE chartering_detail
            SET
                bl_date = :bl_date,
                pol = :pol,
                pod = :pod,
                marchandise = :marchandise,
                quantite = :quantite,
                commentaire = :commentaire
            WHERE id = :id";

        $charterRequest = $this->mysql->prepare($charterStatement);
        $charterRequest->execute([
            "statut" => $input["statut"],
            // Laycan
            "lc_debut" => $input["lc_debut"],
            "lc_fin" => $input["lc_fin"],
            // C/P
            "cp_date" => $input["cp_date"],
            // Navire
            "navire" => $input["navire"],
            // Tiers
            "affreteur" => $input["affreteur"],
            "armateur" => $input["armateur"],
            "courtier" => $input["courtier"],
            // Montants
            "fret_achat" => is_null($input["fret_achat"]) ? NULL : (float) $input["fret_achat"],
            "fret_vente" => is_null($input["fret_vente"]) ? NULL : (float) $input["fret_vente"],
            "surestaries_achat" => is_null($input["surestaries_achat"]) ? NULL : (float) $input["surestaries_achat"],
            "surestaries_vente" => is_null($input["surestaries_vente"]) ? NULL : (float) $input["surestaries_vente"],
            // Divers
            "commentaire" => $input["commentaire"],
            "archive" => (int) $input["archive"],
            'id' => $id,
        ]);

        // DETAILS
        // Suppression details
        // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE detail POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
        // Comparaison du tableau transmis par POST avec la liste existante des details pour le produit concerné
        $existingLegsIdsRequest = $this->mysql->prepare("SELECT id FROM chartering_detail WHERE charter = :charterId");
        $existingLegsIdsRequest->execute(['charterId' => $id]);
        $existingLegsIds = $existingLegsIdsRequest->fetchAll(\PDO::FETCH_COLUMN);

        $submittedLegsIds = array_map(fn(array $leg) => $leg["id"], $input["legs"] ?? []);
        $legsIdsToBeDeleted = array_diff($existingLegsIds, $submittedLegsIds);

        if (count($legsIdsToBeDeleted) > 0) {
            $this->mysql->exec("DELETE FROM chartering_detail WHERE id IN (" . implode(",", $legsIdsToBeDeleted) . ")");
        }

        // Ajout et modification details
        $insertLegRequest = $this->mysql->prepare($insertLegStatement);
        $updateLegRequest = $this->mysql->prepare($updateLegStatement);
        $legs = $input["legs"] ?? [];
        foreach ($legs as $leg) {
            if ((int) $leg["id"]) {
                $updateLegRequest->execute([
                    "bl_date" => $leg["bl_date"],
                    "pol" => $leg["pol"],
                    "pod" => $leg["pod"],
                    "marchandise" => $leg["marchandise"],
                    "quantite" => $leg["quantite"],
                    "commentaire" => $leg["commentaire"],
                    "id" => $leg["id"],
                ]);
            } else {
                $insertLegRequest->execute([
                    'charter' => $id,
                    "bl_date" => $leg["bl_date"],
                    "pol" => $leg["pol"],
                    "pod" => $leg["pod"],
                    "marchandise" => $leg["marchandise"],
                    "quantite" => $leg["quantite"],
                    "commentaire" => $leg["commentaire"],
                ]);
            }
        }

        return $this->read($id);
    }

    /**
     * Supprime un affrètement maritime.
     * 
     * @param int $id ID de l'affrètement à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function delete(int $id): bool
    {
        $request = $this->mysql->prepare("DELETE FROM chartering_registre WHERE id = :id");
        $isDeleted = $request->execute(["id" => $id]);

        return $isDeleted;
    }
}
