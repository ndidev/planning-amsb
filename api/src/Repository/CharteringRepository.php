<?php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\Filter\CharteringFilterDTO;
use App\Entity\Chartering\Charter;
use App\Entity\Chartering\CharterLeg;
use App\Service\CharteringService;

/**
 * @phpstan-type CharterArray array{
 *                              id?: int,
 *                              statut?: int,
 *                              lc_debut?: string,
 *                              lc_fin?: string,
 *                              cp_date?: string,
 *                              navire?: string,
 *                              affreteur?: int,
 *                              armateur?: int,
 *                              courtier?: int,
 *                              fret_achat?: float,
 *                              fret_vente?: float,
 *                              surestaries_achat?: float,
 *                              surestaries_vente?: float,
 *                              commentaire?: string,
 *                              archive?: bool,
 *                              legs?: CharterLegArray[]
 *                            }
 * 
 * @phpstan-type CharterLegArray array{
 *                                 id?: int,
 *                                 charter?: int,
 *                                 bl_date?: string,
 *                                 pol?: string,
 *                                 pod?: string,
 *                                 marchandise?: string,
 *                                 quantite?: string,
 *                                 commentaire?: string,
 *                               }
 */
final class CharteringRepository extends Repository
{
    public function __construct(private CharteringService $charteringService)
    {
        parent::__construct();
    }

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
     * @param CharteringFilterDTO $filter Filtre à appliquer.
     * 
     * @return Collection<Charter> Tous les affrètements récupérés.
     */
    public function fetchCharters(CharteringFilterDTO $filter): Collection
    {
        $sqlFilter =
            $filter->getSqlStatusFilter()
            . $filter->getSqlChartererFilter()
            . $filter->getSqlOwnerFilter()
            . $filter->getSqlBrokerFilter();

        $archiveFilter = (int) $filter->isArchive();

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
            AND (lc_debut <= :endDate OR lc_debut IS NULL)
            AND (lc_fin >= :startDate OR lc_fin IS NULL)
            $sqlFilter
            ORDER BY " . ($archiveFilter ? "-lc_debut ASC, -lc_fin ASC" : "-lc_debut DESC, -lc_fin DESC");


        // Charters
        $chartersRequest = $this->mysql->prepare($chartersStatement);
        $chartersRequest->execute([
            "startDate" => $filter->getSqlStartDate(),
            "endDate" => $filter->getSqlEndDate(),
        ]);

        /** @phpstan-var CharterArray[] $chartersRaw */
        $chartersRaw = $chartersRequest->fetchAll();


        $legsRaw = [];

        if (count($chartersRaw) > 0) {
            $chartersIds = array_map(fn(array $charter) => $charter["id"] ?? null, $chartersRaw);
            $legsStatement = "SELECT * FROM chartering_detail WHERE charter IN (" . implode(",", $chartersIds) . ")";
            $legsRequest = $this->mysql->query($legsStatement);

            if (!$legsRequest) {
                throw new DBException("Impossible de récupérer les détails des affrètements.");
            }

            /** @phpstan-var CharterLegArray[] $legsRaw */
            $legsRaw = $legsRequest->fetchAll();
        }

        $charters = array_map(
            function (array $charterRaw) use ($legsRaw) {
                $charter = $this->charteringService->makeCharterFromDatabase($charterRaw);

                $filteredLegsRaw = array_values(
                    array_filter(
                        $legsRaw,
                        fn(array $legRaw) => ($legRaw["charter"] ?? null) === $charter->getId()
                    )
                );

                $legs = array_map(
                    fn(array $legRaw) => $this->charteringService->makeCharterLegFromDatabase($legRaw),
                    $filteredLegsRaw
                );

                $charter->setLegs($legs);

                return $charter;
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
    public function fetchCharter(int $id): ?Charter
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

        if (!is_array($charterRaw)) return null;

        /** @phpstan-var CharterArray $charterRaw */

        // Détails
        $legsRequest = $this->mysql->prepare($legsStatement);
        $legsRequest->execute(["id" => $id]);

        /** @phpstan-var CharterLegArray[] $legsRaw */
        $legsRaw = $legsRequest->fetchAll();

        $charterRaw["legs"] = $legsRaw;

        $charter = $this->charteringService->makeCharterFromDatabase($charterRaw);

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
            "statut" => $charter->getStatus(),
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

        $lastInsertId = (int) $this->mysql->lastInsertId();
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

        /** @var Charter */
        $newCharter = $this->fetchCharter($lastInsertId);

        return $newCharter;
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
            "statut" => $charter->getStatus(),
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

        $submittedLegsIds = array_map(fn(CharterLeg $leg) => $leg->getId(), $charter->getLegs()->asArray());
        $legsIdsToBeDeleted = array_diff($existingLegsIds, $submittedLegsIds);

        if (!empty($legsIdsToBeDeleted)) {
            $deleteLegsStatement = "DELETE FROM chartering_detail WHERE id IN (" . implode(",", $legsIdsToBeDeleted) . ")";
            $this->mysql->exec($deleteLegsStatement);
        }

        // Ajout et modification details
        $insertLegRequest = $this->mysql->prepare($insertLegStatement);
        $updateLegRequest = $this->mysql->prepare($updateLegStatement);
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

        /** @var int */
        $id = $charter->getId();

        /** @var Charter */
        $updatedCharter = $this->fetchCharter($id);

        return $updatedCharter;
    }

    /**
     * Supprime un affrètement maritime.
     * 
     * @param int $id ID de l'affrètement à supprimer
     * 
     * @return void
     * 
     * @throws DBException Erreur lors de la suppression
     */
    public function deleteCharter(int $id): void
    {
        try {
            $request = $this->mysql->prepare("DELETE FROM chartering_registre WHERE id = :id");
            $success = $request->execute(["id" => $id]);

            if (!$success) {
                throw new DBException("Erreur lors de la suppression");
            }
        } catch (\PDOException $e) {
            throw new DBException("Erreur lors de la suppression", previous: $e);
        }
    }
}
