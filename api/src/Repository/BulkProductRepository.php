<?php

namespace App\Repository;

use App\Entity\BulkProduct;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\BulkQuality;

class BulkProductRepository extends Repository
{
    /**
     * @var array<int, \App\Entity\BulkProduct>
     */
    static private array $productsCache = [];

    /**
     * @var array<int, \App\Entity\BulkQuality>
     */
    static private array $qualitiesCache = [];

    /**
     * Vérifie si un produit existe dans la base de données.
     * 
     * @param int $id Identifiant du produit.
     */
    public function productExists(int $id): bool
    {
        return $this->mysql->exists("vrac_produits", $id);
    }

    /**
     * Vérifie si une qualité existe dans la base de données.
     * 
     * @param int $id Identifiant de la qualité.
     */
    public function qualityExists(int $id): bool
    {
        return $this->mysql->exists("vrac_qualites", $id);
    }

    /**
     * Récupère tous les produits vrac.
     * 
     * @return array<int, \App\Entity\BulkProduct> Liste des produits vrac
     */
    public function getProducts(): array
    {
        $productsStatement = "SELECT * FROM vrac_produits ORDER BY nom";
        $qualitiesStatement = "SELECT * FROM vrac_qualites ORDER BY nom";

        // Produits
        $productsRequest = $this->mysql->query($productsStatement);
        $productsRaw = $productsRequest->fetchAll();

        // Qualités
        $qualitiesRequest = $this->mysql->query($qualitiesStatement);
        $qualitiesRaw = $qualitiesRequest->fetchAll();

        $products = array_map(
            function (array $productRaw) use ($qualitiesRaw) {
                $product = new BulkProduct($productRaw);

                $productQualities =
                    array_values(
                        array_filter(
                            $qualitiesRaw,
                            fn (array $quality) => $quality["produit"] === $productRaw["id"]
                        )
                    );

                $product->setQualities($productQualities);

                return $product;
            },
            $productsRaw
        );

        static::$productsCache = $products;

        return $products;
    }

    /**
     * Récupère un produit vrac.
     * 
     * @param int $id ID du produit à récupérer
     * 
     * @return ?BulkProduct Produit récupéré
     */
    public function getProduct(int $id): ?BulkProduct
    {
        $product = array_filter(
            static::$productsCache,
            fn (BulkProduct $productInCache) => $productInCache->getId() === $id
        )[0] ?? null;

        if ($product) {
            return $product;
        }

        // Produit
        $productStatement =
            "SELECT
                id,
                nom,
                couleur,
                unite
            FROM vrac_produits
            WHERE id = :id";

        $productRequest = $this->mysql->prepare($productStatement);
        $productRequest->execute(["id" => $id]);
        $productRaw = $productRequest->fetch();

        if (!$productRaw) return null;

        $product = new BulkProduct($productRaw);

        // Qualités
        $qualitiesStatement =
            "SELECT *
            FROM vrac_qualites
            WHERE produit = :produit
            ORDER BY nom";

        $qualitiesRequest = $this->mysql->prepare($qualitiesStatement);
        $qualitiesRequest->execute(["produit" => $id]);
        $qualitiesRaw = $qualitiesRequest->fetchAll();

        $qualities = array_map(
            fn (array $qualityRaw) => new BulkQuality($qualityRaw),
            $qualitiesRaw
        );

        $product->setQualities($qualities);

        array_push(static::$productsCache, $product);
        static::$qualitiesCache = array_merge(static::$qualitiesCache, $qualities);

        return $product;
    }

    /**
     * Récupère les qualités d'un produit vrac.
     * 
     * @param int $productId ID du produit.
     * 
     * @return array<int, \App\Entity\BulkQuality> Qualités récupérées.
     */
    public function getQualities(int $productId): array
    {
        $qualitiesStatement =
            "SELECT
                id,
                nom,
                couleur
            FROM vrac_qualites
            WHERE produit = :productId";

        // Produit
        $qualitiesRequest = $this->mysql->prepare($qualitiesStatement);
        $qualitiesRequest->execute(["productId" => $productId]);
        $qualitiesRaw = $qualitiesRequest->fetchAll();

        $qualities = array_map(
            fn (array $qualityRaw) => new BulkQuality($qualityRaw),
            $qualitiesRaw
        );

        return $qualities;
    }

    /**
     * Récupère une qualité vrac.
     * 
     * @param int $id ID de la qualité à récupérer.
     * 
     * @return ?BulkQuality Qualité récupérée.
     */
    public function getQuality(int $id): ?BulkQuality
    {
        $quality = array_filter(
            static::$qualitiesCache,
            fn (BulkQuality $qualityInCache) => $qualityInCache->getId() === $id
        )[0] ?? null;

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

        $quality = new BulkQuality($qualityRaw);

        array_push(static::$qualitiesCache, $quality);

        return $quality;
    }

    /**
     * Crée un produit vrac.
     * 
     * @param array $input Eléments du produit à créer
     * 
     * @return BulkProduct Produit créé
     */
    public function createProduct(array $input): BulkProduct
    {
        $statement_produit =
            "INSERT INTO vrac_produits
            VALUES(
                NULL,
                :nom,
                :couleur,
                :unite
            )";

        $statement_qualites =
            "INSERT INTO vrac_qualites
            VALUES(
                NULL,
                :produit,
                :nom,
                :couleur
            )";

        $requete_produit = $this->mysql->prepare($statement_produit);

        $this->mysql->beginTransaction();
        $requete_produit->execute([
            'nom' => $input["nom"],
            'couleur' => $input["couleur"],
            'unite' => $input["unite"]
        ]);
        $last_id = $this->mysql->lastInsertId();
        $this->mysql->commit();

        // Qualités
        $requete_qualites = $this->mysql->prepare($statement_qualites);
        $qualites = $input["qualites"] ?? [];
        foreach ($qualites as $qualite) {
            $requete_qualites->execute([
                'produit' => $last_id,
                'nom' => $qualite["nom"],
                'couleur' => $qualite["couleur"]
            ]);
        }

        return $this->getProduct($last_id);
    }

    /**
     * Met à jour un produit vrac.
     * 
     * @param int   $id     ID du produit à modifier
     * @param array $input  Eléments du produit à modifier
     * 
     * @return BulkProduct Produit modifié
     */
    public function updateProduct($id, array $input): BulkProduct
    {
        $statement_produit =
            "UPDATE vrac_produits
            SET
                nom = :nom,
                couleur = :couleur,
                unite = :unite
            WHERE id = :id";

        $statement_qualites_ajout =
            "INSERT INTO vrac_qualites
            VALUES(
                NULL,
                :produit,
                :nom,
                :couleur
            )";

        $statement_qualites_modif =
            "UPDATE vrac_qualites
            SET
                nom = :nom,
                couleur = :couleur
            WHERE id = :id";

        $requete_produit = $this->mysql->prepare($statement_produit);
        $requete_produit->execute([
            'nom' => $input["nom"],
            'couleur' => $input["couleur"],
            'unite' => $input["unite"],
            'id' => $id
        ]);

        // QUALITÉS
        // Suppression qualités
        // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE QUALITE POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
        // Comparaison du tableau transmis par POST avec la liste existante des qualités pour le produit concerné
        $requete_qualites = $this->mysql->prepare("SELECT id FROM vrac_qualites WHERE produit = :produit");
        $requete_qualites->execute(['produit' => $id]);
        $ids_qualites_existantes = [];
        while ($qualite = $requete_qualites->fetch()) {
            $ids_qualites_existantes[] = $qualite['id'];
        }

        $ids_qualites_transmises = [];
        if (isset($input['qualites'])) {
            foreach ($input["qualites"] as $qualite) {
                $ids_qualites_transmises[] = $qualite["id"];
            }
        }
        $ids_qualites_a_supprimer = array_diff($ids_qualites_existantes, $ids_qualites_transmises);

        $requete_supprimer = $this->mysql->prepare("DELETE FROM vrac_qualites WHERE id = :id");
        foreach ($ids_qualites_a_supprimer as $id_suppr) {
            $requete_supprimer->execute(['id' => $id_suppr]);
        }

        // Ajout et modification qualités
        $requete_qualites_ajout = $this->mysql->prepare($statement_qualites_ajout);
        $requete_qualites_modif = $this->mysql->prepare($statement_qualites_modif);
        $qualites = $input["qualites"] ?? [];
        foreach ($qualites as $qualite) {
            if ($qualite["id"]) {
                $requete_qualites_modif->execute([
                    "nom" => $qualite["nom"],
                    "couleur" => $qualite["couleur"],
                    "id" => $qualite["id"]
                ]);
            } else {
                $requete_qualites_ajout->execute([
                    "produit" => $id,
                    "nom" => $qualite["nom"],
                    "couleur" => $qualite["couleur"]
                ]);
            }
        }

        return $this->getProduct($id);
    }

    /**
     * Supprime un produit vrac.
     * 
     * @param int $id ID du produit à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteProduct(int $id): bool
    {
        $requete = $this->mysql->prepare("DELETE FROM vrac_produits WHERE id = :id");
        $succes = $requete->execute(["id" => $id]);

        if (!$succes) {
            throw new DBException("Erreur lors de la suppression");
        }

        return $succes;
    }
}
