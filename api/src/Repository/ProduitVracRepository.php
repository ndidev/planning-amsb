<?php

namespace App\Repository;

use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Vrac\ProduitVrac;
use App\Entity\Vrac\QualiteVrac;
use App\Service\VracService;

class ProduitVracRepository extends Repository
{
    /**
     * @var ProduitVrac[]
     */
    static private array $cacheProduits = [];

    /**
     * @var QualiteVrac[]
     */
    static private array $cacheQualites = [];

    /**
     * Vérifie si un produit existe dans la base de données.
     * 
     * @param int $id Identifiant du produit.
     */
    public function produitExiste(int $id): bool
    {
        return $this->mysql->exists("vrac_produits", $id);
    }

    /**
     * Vérifie si une qualité existe dans la base de données.
     * 
     * @param int $id Identifiant de la qualité.
     */
    public function qualiteExiste(int $id): bool
    {
        return $this->mysql->exists("vrac_qualites", $id);
    }

    /**
     * Récupère tous les produits vrac.
     * 
     * @return ProduitVrac[] Liste des produits vrac
     */
    public function getProduits(): array
    {
        $produitsStatement = "SELECT * FROM vrac_produits ORDER BY nom";
        $qualitiesStatement = "SELECT * FROM vrac_qualites ORDER BY nom";

        // Produits
        $produitsRequest = $this->mysql->query($produitsStatement);
        $produitsRaw = $produitsRequest->fetchAll();

        // Qualités
        $qualitiesRequest = $this->mysql->query($qualitiesStatement);
        $qualitiesRaw = $qualitiesRequest->fetchAll();

        $vracService = new VracService();

        $produits = array_map(
            function (array $produitRaw) use ($vracService, $qualitiesRaw) {
                $produit = $vracService->makeProduit($produitRaw);

                $produitQualities =
                    array_values(
                        array_filter(
                            $qualitiesRaw,
                            fn (array $quality) => $quality["produit"] === $produitRaw["id"]
                        )
                    );

                $produit->setQualites($produitQualities);

                return $produit;
            },
            $produitsRaw
        );

        static::$cacheProduits = $produits;

        return $produits;
    }

    /**
     * Récupère un produit vrac.
     * 
     * @param int $id ID du produit à récupérer
     * 
     * @return ?ProduitVrac Produit récupéré
     */
    public function getProduit(int $id): ?ProduitVrac
    {
        $produitsInCache = array_values(array_filter(
            static::$cacheProduits,
            fn (ProduitVrac $produitInCache) => $produitInCache->getId() === $id
        ));

        $produit = $produitsInCache[0] ?? null;

        if ($produit) {
            return $produit;
        }

        // Produit
        $produitStatement =
            "SELECT
                id,
                nom,
                couleur,
                unite
            FROM vrac_produits
            WHERE id = :id";

        $produitRequest = $this->mysql->prepare($produitStatement);
        $produitRequest->execute(["id" => $id]);
        $produitRaw = $produitRequest->fetch();

        if (!$produitRaw) return null;

        $vracService = new VracService();

        $produit = $vracService->makeProduit($produitRaw);

        // Qualités
        $qualites = $this->getQualites($id);

        $produit->setQualites($qualites);

        array_push(static::$cacheProduits, $produit);

        return $produit;
    }

    /**
     * Récupère les qualités d'un produit vrac.
     * 
     * @param int $produitId ID du produit.
     * 
     * @return QualiteVrac[] Qualités récupérées.
     */
    public function getQualites(int $produitId): array
    {
        $qualitesStatement =
            "SELECT
                id,
                nom,
                couleur
            FROM vrac_qualites
            WHERE produit = :produitId";

        $qualitesRequest = $this->mysql->prepare($qualitesStatement);
        $qualitesRequest->execute(["produitId" => $produitId]);
        $qualitesRaw = $qualitesRequest->fetchAll();

        $vracService = new VracService();

        $qualites = array_map(
            fn (array $qualityRaw) => $vracService->makeQualite($qualityRaw),
            $qualitesRaw
        );

        static::$cacheQualites = array_merge(static::$cacheQualites, $qualites);

        return $qualites;
    }

    /**
     * Récupère une qualité vrac.
     * 
     * @param int $id ID de la qualité à récupérer.
     * 
     * @return ?QualiteVrac Qualité récupérée.
     */
    public function getQualite(int $id): ?QualiteVrac
    {
        $qualitiesInCache = array_values(array_filter(
            static::$cacheQualites,
            fn (QualiteVrac $qualityInCache) => $qualityInCache->getId() === $id
        ));

        $quality = $qualitiesInCache[0] ?? null;

        if ($quality) {
            return $quality;
        }

        $qualityStatement =
            "SELECT
                id,
                nom,
                couleur
            FROM vrac_qualites
            WHERE id = :id";

        // Produit
        $qualityRequest = $this->mysql->prepare($qualityStatement);
        $qualityRequest->execute(["id" => $id]);
        $qualityRaw = $qualityRequest->fetch();

        if (!$qualityRaw) return null;

        $quality = new QualiteVrac($qualityRaw);

        array_push(static::$cacheQualites, $quality);

        return $quality;
    }

    /**
     * Crée un produit vrac.
     * 
     * @param array $input Eléments du produit à créer
     * 
     * @return ProduitVrac Produit créé
     */
    public function createProduit(array $input): ProduitVrac
    {
        $produitStatement =
            "INSERT INTO vrac_produits
            VALUES(
                NULL,
                :nom,
                :couleur,
                :unite
            )";

        $qualitiesStatement =
            "INSERT INTO vrac_qualites
            VALUES(
                NULL,
                :produit,
                :nom,
                :couleur
            )";

        $produitRequest = $this->mysql->prepare($produitStatement);

        $this->mysql->beginTransaction();
        $produitRequest->execute([
            'nom' => $input["nom"],
            'couleur' => $input["couleur"],
            'unite' => $input["unite"]
        ]);
        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        // Qualités
        $qualitiesRequest = $this->mysql->prepare($qualitiesStatement);
        $qualities = $input["qualites"] ?? [];
        foreach ($qualities as $quality) {
            $qualitiesRequest->execute([
                'produit' => $lastInsertId,
                'nom' => $quality["nom"],
                'couleur' => $quality["couleur"]
            ]);
        }

        return $this->getProduit($lastInsertId);
    }

    /**
     * Met à jour un produit vrac.
     * 
     * @param int   $id     ID du produit à modifier
     * @param array $input  Eléments du produit à modifier
     * 
     * @return ProduitVrac Produit modifié
     */
    public function updateProduit($id, array $input): ProduitVrac
    {
        $produitStatement =
            "UPDATE vrac_produits
            SET
                nom = :nom,
                couleur = :couleur,
                unite = :unite
            WHERE id = :id";

        $addQualityStatement =
            "INSERT INTO vrac_qualites
            VALUES(
                NULL,
                :produit,
                :nom,
                :couleur
            )";

        $editQualityStatement =
            "UPDATE vrac_qualites
            SET
                nom = :nom,
                couleur = :couleur
            WHERE id = :id";

        $produitRequest = $this->mysql->prepare($produitStatement);
        $produitRequest->execute([
            'nom' => $input["nom"],
            'couleur' => $input["couleur"],
            'unite' => $input["unite"],
            'id' => $id
        ]);

        // QUALITÉS
        // Suppression qualités
        // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE QUALITE POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
        // Comparaison du tableau transmis par POST avec la liste existante des qualités pour le produit concerné
        $qualitiesRequest = $this->mysql->prepare("SELECT id FROM vrac_qualites WHERE produit = :produit");
        $qualitiesRequest->execute(['produit' => $id]);
        $existingQualitiesIds = [];
        while ($quality = $qualitiesRequest->fetch()) {
            $existingQualitiesIds[] = $quality['id'];
        }

        $submittedQualitiesIds = [];
        if (isset($input['qualites'])) {
            foreach ($input["qualites"] as $quality) {
                $submittedQualitiesIds[] = $quality["id"];
            }
        }
        $qualitiesIdsToBeDeleted = array_diff($existingQualitiesIds, $submittedQualitiesIds);

        $deleteQualityRequest = $this->mysql->prepare("DELETE FROM vrac_qualites WHERE id = :id");
        foreach ($qualitiesIdsToBeDeleted as $id) {
            $deleteQualityRequest->execute(['id' => $id]);
        }

        // Ajout et modification qualités
        $addQualitiesRequest = $this->mysql->prepare($addQualityStatement);
        $editQualitiesRequest = $this->mysql->prepare($editQualityStatement);
        $qualities = $input["qualites"] ?? [];
        foreach ($qualities as $quality) {
            if ($quality["id"]) {
                $editQualitiesRequest->execute([
                    "nom" => $quality["nom"],
                    "couleur" => $quality["couleur"],
                    "id" => $quality["id"]
                ]);
            } else {
                $addQualitiesRequest->execute([
                    "produit" => $id,
                    "nom" => $quality["nom"],
                    "couleur" => $quality["couleur"]
                ]);
            }
        }

        return $this->getProduit($id);
    }

    /**
     * Supprime un produit vrac.
     * 
     * @param int $id ID du produit à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteProduit(int $id): bool
    {
        $request = $this->mysql->prepare("DELETE FROM vrac_produits WHERE id = :id");
        $success = $request->execute(["id" => $id]);

        if (!$success) {
            throw new DBException("Erreur lors de la suppression");
        }

        return $success;
    }
}
