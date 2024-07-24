<?php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Chartering\Charter;
use App\Entity\Chartering\CharterLeg;
use App\Service\CharteringService;

class CharteringRepository extends Repository
{
    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function charterExists(int $id): bool
    {
        return $this->mysql->exists("chartering_registre", $id);
    }

    /**
     * Récupère tous les affrètements maritimes.
     * 
     * @param array $filter
     * 
     * @return Collection<Charter> Tous les affrètements récupérés
     */
    public function getCharters(array $filter = []): Collection
    {
        // Filtre
        $date_debut = isset($filter["date_debut"]) ? $filter['date_debut'] : "0001-01-01";
        $date_fin = isset($filter["date_fin"]) ? $filter['date_fin'] : "9999-12-31";
        $filtre_statut = $filter["statut"] ?? "";
        $filtre_affreteur = trim($filter['affreteur'] ?? "", ",");
        $filtre_armateur = trim($filter['armateur'] ?? "", ",");
        $filtre_courtier = trim($filter['courtier'] ?? "", ",");

        $filtre_sql_affreteur = $filtre_affreteur === "" ? "" : " AND affreteur IN ($filtre_affreteur)";
        $filtre_sql_armateur = $filtre_armateur === "" ? "" : " AND armateur IN ($filtre_armateur)";
        $filtre_sql_courtier = $filtre_courtier === "" ? "" : " AND courtier IN ($filtre_courtier)";
        $filtre_sql_statut = $filtre_statut === "" ? "" : " AND statut IN ($filtre_statut)";

        $sqlFilter =
            $filtre_sql_affreteur
            . $filtre_sql_armateur
            . $filtre_sql_courtier
            . $filtre_sql_statut;

        $archiveFilter = (int) array_key_exists("archives", $filter);

        $chartersStatement =
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
        $chartersRequest = $this->mysql->prepare($chartersStatement);
        $chartersRequest->execute([
            "date_debut" => $date_debut,
            "date_fin" => $date_fin
        ]);
        $chartersRaw = $chartersRequest->fetchAll();

        $legRequest = $this->mysql->prepare($legsStatement);

        $charteringService = new CharteringService();

        $charters = array_map(
            function (array $charterRaw) use ($charteringService, $legRequest) {
                $legRequest->execute(["id" => $charterRaw["id"]]);
                $legsRaw = $legRequest->fetchAll();

                $charterRaw["legs"] = $legsRaw;

                return $charteringService->makeCharterFromDatabase($charterRaw);
            },
            $chartersRaw
        );

        return new Collection($charters);
    }

    /**
     * Récupère un affrètement maritime.
     * 
     * @param int $id ID de l'affrètement à récupérer
     * 
     * @return ?Charter Rendez-vous récupéré
     */
    public function getCharter(int $id): ?Charter
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
        $charterRaw = $charterRequest->fetch();

        if (!$charterRaw) return null;

        // Détails
        $legsRequest = $this->mysql->prepare($legsStatement);
        $legsRequest->execute(["id" => $id]);
        $legsRaw = $legsRequest->fetchAll();

        $charteringService = new CharteringService();

        $charterRaw["legs"] = $legsRaw;

        $charter = $charteringService->makeCharterFromDatabase($charterRaw);

        // $charterRaw["archive"] = (bool) $charterRaw["archive"];
        // $charterRaw["affreteur"] = (int) $charterRaw["affreteur"] ?: NULL;
        // $charterRaw["armateur"] = (int) $charterRaw["armateur"] ?: NULL;
        // $charterRaw["courtier"] = (int) $charterRaw["courtier"] ?: NULL;
        // $charterRaw["fret_achat"] = (float) $charterRaw["fret_achat"];
        // $charterRaw["fret_vente"] = (float) $charterRaw["fret_vente"];
        // $charterRaw["surestaries_achat"] = (float) $charterRaw["surestaries_achat"];
        // $charterRaw["surestaries_vente"] = (float) $charterRaw["surestaries_vente"];

        return $charter;
    }

    /**
     * Crée un affrètement maritime.
     * 
     * @param Charter $charter Eléments de l'affrètement à créer
     * 
     * @return Charter Affrètement créé
     */
    public function createCharter(Charter $charter): Charter
    {
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

        $legsStatement =
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
            "statut" => $charter->getStatus(true),
            // Laycan
            "lc_debut" => $charter->getLaycanStart(true),
            "lc_fin" => $charter->getLaycanEnd(true),
            // C/P
            "cp_date" => $charter->getCpDate(true),
            // Navire
            "navire" => $charter->getVesselName(),
            // Tiers
            "affreteur" => $charter->getCharterer()?->getId(),
            "armateur" => $charter->getShipOperator()?->getId(),
            "courtier" => $charter->getShipbroker()?->getId(),
            // Montants
            "fret_achat" => $charter->getFreightPayed(),
            "fret_vente" => $charter->getFreightSold(),
            "surestaries_achat" => $charter->getDemurragePayed(),
            "surestaries_vente" => $charter->getDemurrageSold(),
            // Divers
            "commentaire" => $charter->getComments(),
            "archive" => (int) $charter->isArchive(),
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        // Détails
        $legsRequest = $this->mysql->prepare($legsStatement);
        foreach ($charter->getLegs() as $leg) {
            $legsRequest->execute([
                "charter" => $lastInsertId,
                "bl_date" => $leg->getBlDate(true),
                "marchandise" => $leg->getCommodity(),
                "quantite" => $leg->getQuantity(),
                "pol" => $leg->getPod()?->getLocode(),
                "pod" => $leg->getPod()?->getLocode(),
                "commentaire" => $leg->getComments(),
            ]);
        }

        return $this->getCharter($lastInsertId);
    }

    /**
     * Met à jour un affrètement maritime.
     * 
     * @param Charter $charter  Affrètement à modifier
     * 
     * @return Charter Affretement modifié
     */
    public function updateCharter(Charter $charter): Charter
    {
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

        $statement_details_ajout =
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

        $statement_details_modif =
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
            "statut" => $charter->getStatus(true),
            // Laycan
            "lc_debut" => $charter->getLaycanStart(true),
            "lc_fin" => $charter->getLaycanEnd(true),
            // C/P
            "cp_date" => $charter->getCpDate(true),
            // Navire
            "navire" => $charter->getVesselName(),
            // Tiers
            "affreteur" => $charter->getCharterer()?->getId(),
            "armateur" => $charter->getShipOperator()?->getId(),
            "courtier" => $charter->getShipbroker()?->getId(),
            // Montants
            "fret_achat" => $charter->getFreightPayed(),
            "fret_vente" => $charter->getFreightSold(),
            "surestaries_achat" => $charter->getDemurragePayed(),
            "surestaries_vente" => $charter->getDemurrageSold(),
            // Divers
            "commentaire" => $charter->getComments(),
            "archive" => (int) $charter->isArchive(),
            'id' => $charter->getId(),
        ]);

        // DETAILS
        // Suppression details
        // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE detail POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
        // Comparaison du tableau transmis par POST avec la liste existante des details pour le produit concerné
        $legsIdsRequest = $this->mysql->prepare("SELECT id FROM chartering_detail WHERE charter = :charterId");
        $legsIdsRequest->execute(['charterId' => $charter->getId()]);
        $existingLegsIds = $legsIdsRequest->fetchAll(\PDO::FETCH_COLUMN, 0);

        $submittedLegsIds = array_map(fn (CharterLeg $leg) => $leg->getId(), $charter->getLegs()->toArray());
        $legsIdsToBeDeleted = array_diff($existingLegsIds, $submittedLegsIds);

        if (!empty($legsIdsToBeDeleted)) {
            $deleteLegsStatement = "DELETE FROM chartering_detail WHERE id IN (" . implode(",", $legsIdsToBeDeleted) . ")";
            $this->mysql->exec($deleteLegsStatement);
        }

        // Ajout et modification details
        $insertLegRequest = $this->mysql->prepare($statement_details_ajout);
        $updateLegRequest = $this->mysql->prepare($statement_details_modif);
        foreach ($charter->getLegs() as $leg) {
            if ($leg->getId()) {
                $updateLegRequest->execute([
                    "bl_date" => $leg->getBlDate(true),
                    "pol" => $leg->getPol()?->getLocode(),
                    "pod" => $leg->getPod()?->getLocode(),
                    "marchandise" => $leg->getCommodity(),
                    "quantite" => $leg->getQuantity(),
                    "commentaire" => $leg->getComments(),
                    "id" => $leg->getId(),
                ]);
            } else {
                $insertLegRequest->execute([
                    "charter" => $charter->getId(),
                    "bl_date" => $leg->getBlDate(true),
                    "pol" => $leg->getPol()?->getLocode(),
                    "pod" => $leg->getPod()?->getLocode(),
                    "marchandise" => $leg->getCommodity(),
                    "quantite" => $leg->getQuantity(),
                    "commentaire" => $leg->getComments(),
                ]);
            }
        }

        return $this->getCharter($charter->getId());
    }

    /**
     * Supprime un affrètement maritime.
     * 
     * @param int $id ID de l'affrètement à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteCharter(int $id): bool
    {
        $request = $this->mysql->prepare("DELETE FROM chartering_registre WHERE id = :id");
        $success = $request->execute(["id" => $id]);

        if (!$success) {
            throw new DBException("Erreur lors de la suppression");
        }

        return $success;
    }
}
