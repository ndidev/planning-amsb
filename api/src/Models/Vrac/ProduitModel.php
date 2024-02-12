<?php

namespace App\Models\Vrac;

use App\Entity\BulkProduct;
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
     * @return array<int, \App\Entity\BulkProduct> Liste des produits vrac
     */
    public function readAll(): array
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

        return $products;
    }

    /**
     * Récupère un produit vrac.
     * 
     * @param int $id ID du produit à récupérer
     * 
     * @return ?BulkProduct Produit récupéré
     */
    public function read(int $id): ?BulkProduct
    {
        $productStatement =
            "SELECT
                id,
                nom,
                couleur,
                unite
            FROM vrac_produits
            WHERE id = :id";

        $qualitiesStatement =
            "SELECT *
            FROM vrac_qualites
            WHERE produit = :produit
            ORDER BY nom";

        // Produit
        $productRequest = $this->mysql->prepare($productStatement);
        $productRequest->execute(["id" => $id]);
        $productRaw = $productRequest->fetch();

        if (!$productRaw) return null;

        // Qualités
        $qualitiesRequest = $this->mysql->prepare($qualitiesStatement);
        $qualitiesRequest->execute(["produit" => $id]);
        $qualitiesRaw = $qualitiesRequest->fetchAll();

        $product = (new BulkProduct($productRaw))->setQualities($qualitiesRaw);

        return $product;
    }

    /**
     * Crée un produit vrac.
     * 
     * @param array $input Eléments du produit à créer
     * 
     * @return BulkProduct Produit créé
     */
    public function create(array $input): BulkProduct
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

        return $this->read($last_id);
    }

    /**
     * Met à jour un produit vrac.
     * 
     * @param int   $id     ID du produit à modifier
     * @param array $input  Eléments du produit à modifier
     * 
     * @return BulkProduct Produit modifié
     */
    public function update($id, array $input): BulkProduct
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
        $requete = $this->mysql->prepare("DELETE FROM vrac_produits WHERE id = :id");
        $succes = $requete->execute(["id" => $id]);

        return $succes;
    }
}
