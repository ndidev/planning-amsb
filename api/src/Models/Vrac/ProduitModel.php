<?php

namespace App\Models\Vrac;

use App\Models\Model;

class ProduitModel extends Model
{
    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param int $id Identifiant de l'entrée.
     */
    public function exists(int $id)
    {
        return $this->mysql->exists("vrac_produits", $id);
    }

    /**
     * Récupère tous les produits vrac.
     * 
     * @return array Liste des produits vrac
     */
    public function readAll(): array
    {
        $productsStatement = "SELECT * FROM vrac_produits ORDER BY nom";
        $qualitiesStatement = "SELECT * FROM vrac_qualites ORDER BY nom";

        // Produits
        $productsRequest = $this->mysql->query($productsStatement);
        $products = $productsRequest->fetchAll();

        // Qualités
        $qualititesRequest = $this->mysql->query($qualitiesStatement);
        $qualities = $qualititesRequest->fetchAll();

        foreach ($products as &$product) {
            $product["qualites"] = array_values(array_filter($qualities, fn($qualite) => $qualite["produit"] === $product["id"]));
        }

        return $products;
    }

    /**
     * Récupère un produit vrac.
     * 
     * @param int $id ID du produit à récupérer
     * 
     * @return array Produit récupéré
     */
    public function read($id): ?array
    {
        $productStatement = "SELECT * FROM vrac_produits WHERE id = :id";
        $qualititesStatement = "SELECT * FROM vrac_qualites WHERE produit = :produit ORDER BY nom";

        // Produit
        $productRequest = $this->mysql->prepare($productStatement);
        $productRequest->execute(["id" => $id]);
        $product = $productRequest->fetch();

        if (!$product) return null;

        // Qualités
        $qualitiesRequest = $this->mysql->prepare($qualititesStatement);
        $qualitiesRequest->execute(["produit" => $id]);
        $product["qualites"] = $qualitiesRequest->fetchAll();

        return $product;
    }

    /**
     * Crée un produit vrac.
     * 
     * @param array $input Eléments du produit à créer
     * 
     * @return array Produit créé
     */
    public function create(array $input): array
    {
        $productStatement =
            "INSERT INTO vrac_produits
            VALUES(
                NULL,
                :nom,
                :couleur,
                :unite
            )";

        $insertQualityStatement =
            "INSERT INTO vrac_qualites
            VALUES(
                NULL,
                :produit,
                :nom,
                :couleur
            )";

        $productRequest = $this->mysql->prepare($productStatement);

        $this->mysql->beginTransaction();
        $productRequest->execute([
            'nom' => $input["nom"],
            'couleur' => $input["couleur"],
            'unite' => $input["unite"]
        ]);
        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        // Qualités
        $insertQualityRequest = $this->mysql->prepare($insertQualityStatement);
        $qualities = $input["qualites"] ?? [];
        foreach ($qualities as $quality) {
            $insertQualityRequest->execute([
                'produit' => $lastInsertId,
                'nom' => $quality["nom"],
                'couleur' => $quality["couleur"]
            ]);
        }

        return $this->read($lastInsertId);
    }

    /**
     * Met à jour un produit vrac.
     * 
     * @param int   $id     ID du produit à modifier
     * @param array $input  Eléments du produit à modifier
     * 
     * @return array Produit modifié
     */
    public function update($id, array $input): array
    {
        $productStatement =
            "UPDATE vrac_produits
            SET
                nom = :nom,
                couleur = :couleur,
                unite = :unite
            WHERE id = :id";

        $insertQualityStatement =
            "INSERT INTO vrac_qualites
            VALUES(
                NULL,
                :produit,
                :nom,
                :couleur
            )";

        $updateQualityStatement =
            "UPDATE vrac_qualites
            SET
                nom = :nom,
                couleur = :couleur
            WHERE id = :id";

        $productRequest = $this->mysql->prepare($productStatement);
        $productRequest->execute([
            'nom' => $input["nom"],
            'couleur' => $input["couleur"],
            'unite' => $input["unite"],
            'id' => $id
        ]);

        // QUALITÉS
        // Suppression qualités
        // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE QUALITE POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
        // Comparaison du tableau transmis par POST avec la liste existante des qualités pour le produit concerné
        $existingQualitiesIdsRequest = $this->mysql->prepare("SELECT id FROM vrac_qualites WHERE produit = :produit");
        $existingQualitiesIdsRequest->execute(['produit' => $id]);
        $existingQualitiesIds = $existingQualitiesIdsRequest->fetchAll(\PDO::FETCH_COLUMN);

        $submittedQualitiesIds = array_map(fn(array $qualite) => $qualite["id"], $input["qualites"] ?? []);
        $qualitiesIdsToBeDeleted = array_diff($existingQualitiesIds, $submittedQualitiesIds);

        if (count($qualitiesIdsToBeDeleted) > 0) {
            $this->mysql->exec("DELETE FROM vrac_qualites WHERE id IN (" . implode(",", $qualitiesIdsToBeDeleted) . ")");
        }

        // Ajout et modification qualités
        $insertQualityRequest = $this->mysql->prepare($insertQualityStatement);
        $updateQualityRequest = $this->mysql->prepare($updateQualityStatement);
        $qualities = $input["qualites"] ?? [];
        foreach ($qualities as $quality) {
            if ($quality["id"]) {
                $updateQualityRequest->execute([
                    "nom" => $quality["nom"],
                    "couleur" => $quality["couleur"],
                    "id" => $quality["id"]
                ]);
            } else {
                $insertQualityRequest->execute([
                    "produit" => $id,
                    "nom" => $quality["nom"],
                    "couleur" => $quality["couleur"]
                ]);
            }
        }

        return $this->read($id);
    }

    /**
     * Supprime un produit vrac.
     * 
     * @param int $id ID du produit à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function delete(int $id): bool
    {
        $deleteRequest = $this->mysql->prepare("DELETE FROM vrac_produits WHERE id = :id");
        $isDeleted = $deleteRequest->execute(["id" => $id]);

        return $isDeleted;
    }
}
