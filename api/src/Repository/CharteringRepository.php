<?php

// Path: api/src/Repository/CharteringRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\Filter\CharteringFilterDTO;
use App\Entity\Chartering\Charter;
use App\Entity\Chartering\CharterLeg;
use App\Service\CharteringService;

/**
 * @phpstan-import-type CharterArray from \App\Entity\Chartering\Charter
 * @phpstan-import-type CharterLegArray from \App\Entity\Chartering\CharterLeg
 */
final class CharteringRepository extends Repository
{
    public function __construct(private CharteringService $charteringService) {}

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function charterExists(int $id): bool
    {
        return $this->mysql->exists('chartering_registre', $id);
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
        $sqlFilter = $filter->getSqlFilter();

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
            $chartersIds = \array_map(fn(array $charter) => $charter["id"] ?? null, $chartersRaw);
            $legsStatement = "SELECT * FROM chartering_detail WHERE charter IN (" . \implode(",", $chartersIds) . ")";
            $legsRequest = $this->mysql->query($legsStatement);

            if (!$legsRequest) {
                throw new DBException("Impossible de récupérer les détails des affrètements.");
            }

            /** @phpstan-var CharterLegArray[] $legsRaw */
            $legsRaw = $legsRequest->fetchAll();
        }

        $charters = \array_map(
            function (array $charterRaw) use ($legsRaw) {
                $charter = $this->charteringService->makeCharterFromDatabase($charterRaw);

                $filteredLegsRaw = \array_values(
                    \array_filter(
                        $legsRaw,
                        fn(array $legRaw) => ($legRaw["charter"] ?? null) === $charter->id
                    )
                );

                $charter->legs = \array_map(
                    fn($legRaw) => $this->charteringService->makeCharterLegFromDatabase($legRaw),
                    $filteredLegsRaw
                );

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

        if (!\is_array($charterRaw)) return null;

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
            SET
                statut = :status,
                -- Laycan
                lc_debut = :laycanStart,
                lc_fin = :laycanEnd,
                -- C/P
                cp_date = :cpDate,
                -- Navire
                navire = :vesselName,
                -- Tiers
                affreteur = :chartererId,
                armateur = :shipOperatorId,
                courtier = :shipbrokerId,
                -- Montants
                fret_achat = :freightPayed,
                fret_vente = :freightSold,
                surestaries_achat = :demurragePayed,
                surestaries_vente = :demurrageSold,
                -- Divers
                commentaire = :comments,
                archive = :isArchive";

        $legsStatement =
            "INSERT INTO chartering_detail
            SET
                charter = :charterId,
                bl_date = :blDate,
                pol = :pol,
                pod = :pod,
                marchandise = :commodity,
                quantite = :quantity,
                commentaire = :comments";

        try {
            $this->mysql->beginTransaction();

            $this->mysql->prepareAndExecute($charterStatement, [
                'status' => $charter->status,
                // Laycan
                'laycanStart' => $charter->sqlLaycanStart,
                'laycanEnd' => $charter->sqlLaycanEnd,
                // C/P
                'cpDate' => $charter->sqlCpDate,
                // Navire
                'vesselName' => $charter->vesselName,
                // Tiers
                'chartererId' => $charter->charterer?->id,
                'shipOperatorId' => $charter->shipOperator?->id,
                'shipbrokerId' => $charter->shipbroker?->id,
                // Montants
                'freightPayed' => $charter->freightPayed,
                'freightSold' => $charter->freightSold,
                'demurragePayed' => $charter->demurragePayed,
                'demurrageSold' => $charter->demurrageSold,
                // Divers
                'comments' => $charter->comments,
                'isArchive' => (int) $charter->isArchive,
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            // Détails
            $this->mysql->prepareAndExecute(
                $legsStatement,
                $charter->legs->map(
                    function ($leg) use ($lastInsertId) {
                        return [
                            'charterId' => $lastInsertId,
                            'blDate' => $leg->sqlBlDate,
                            'pol' => $leg->pol?->locode,
                            'pod' => $leg->pod?->locode,
                            'commodity' => $leg->commodity,
                            'quantity' => $leg->quantity,
                            'comments' => $leg->comments,
                        ];
                    }
                )
            );

            $this->mysql->commit();
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();
            throw new DBException("Erreur lors de la création de l'affrètement", previous: $e);
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
                statut = :status,
                -- Laycan
                lc_debut = :laycanStart,
                lc_fin = :laycanEnd,
                -- C/P
                cp_date = :cpDate,
                -- Navire
                navire = :vesselName,
                -- Tiers
                affreteur = :chartererId,
                armateur = :shipOperatorId,
                courtier = :shipbrokerId,
                -- Montants
                fret_achat = :freightPayed,
                fret_vente = :freightSold,
                surestaries_achat = :demurragePayed,
                surestaries_vente = :demurrageSold,
                -- Divers
                commentaire = :comments,
                archive = :isArchive
            WHERE id = :id";

        $legStatement =
            "INSERT INTO chartering_detail
            SET
                id = :id,
                charter = :charterId,
                bl_date = :blDate,
                pol = :pol,
                pod = :pod,
                marchandise = :commodity,
                quantite = :quantity,
                commentaire = :comments
            ON DUPLICATE KEY UPDATE
                bl_date = :blDate,
                pol = :pol,
                pod = :pod,
                marchandise = :commodity,
                quantite = :quantity,
                commentaire = :comments
            ";

        try {
            $this->mysql->beginTransaction();

            $this->mysql->prepareAndExecute($charterStatement, [
                'status' => $charter->status,
                // Laycan
                'laycanStart' => $charter->sqlLaycanStart,
                'laycanEnd' => $charter->sqlLaycanEnd,
                // C/P
                'cpDate' => $charter->sqlCpDate,
                // Navire
                'vesselName' => $charter->vesselName,
                // Tiers
                'chartererId' => $charter->charterer?->id,
                'shipOperatorId' => $charter->shipOperator?->id,
                'shipbrokerId' => $charter->shipbroker?->id,
                // Montants
                'freightPayed' => $charter->freightPayed,
                'freightSold' => $charter->freightSold,
                'demurragePayed' => $charter->demurragePayed,
                'demurrageSold' => $charter->demurrageSold,
                // Divers
                'comments' => $charter->comments,
                'isArchive' => (int) $charter->isArchive,
                'id' => $charter->id,
            ]);

            // DETAILS
            // Suppression details
            // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE detail POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
            // Comparaison du tableau transmis par POST avec la liste existante des details pour le produit concerné
            $existingLegsIds = $this->mysql
                ->prepareAndExecute(
                    "SELECT id FROM chartering_detail WHERE charter = :charterId",
                    ['charterId' => $charter->id]
                )
                ->fetchAll(\PDO::FETCH_COLUMN, 0);

            $submittedLegsIds = \array_map(fn(CharterLeg $leg) => $leg->id, $charter->legs->asArray());
            $legsIdsToBeDeleted = \array_diff($existingLegsIds, $submittedLegsIds);

            if (!empty($legsIdsToBeDeleted)) {
                $this->mysql->exec("DELETE FROM chartering_detail WHERE id IN (" . \implode(",", $legsIdsToBeDeleted) . ")");
            }

            // Ajout et modification details
            $this->mysql->prepareAndExecute(
                $legStatement,
                $charter->legs->map(
                    function ($leg) {
                        return [
                            'id' => $leg->id,
                            'charterId' => $leg->charter?->id,
                            'blDate' => $leg->sqlBlDate,
                            'pol' => $leg->pol?->locode,
                            'pod' => $leg->pod?->locode,
                            'commodity' => $leg->commodity,
                            'quantity' => $leg->quantity,
                            'comments' => $leg->comments,
                        ];
                    }
                )
            );

            $this->mysql->commit();
        } catch (\PDOException $e) {
            $this->mysql->rollbackIfNeeded();
            throw new DBException("Erreur lors de la mise à jour de l'affrètement", previous: $e);
        }

        /** @var int */
        $id = $charter->id;

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
