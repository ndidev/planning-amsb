<?php

namespace Api\Models\Vrac;

use Api\Utils\DatabaseConnector as DB;
use Api\Models\Vrac\QualiteModel as Qualite;

class ProduitModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère tous les produits vrac.
   * 
   * @return array Liste des produits vrac
   */
  public function readAll()
  {
    $statement_produits =
      "SELECT
        id,
        nom,
        couleur,
        unite
      FROM vrac_produits
      ORDER BY nom";

    $statement_qualites =
      "SELECT *
        FROM vrac_qualites
        WHERE produit = :produit
        ORDER BY nom";

    $donnees = [];

    // Produits
    $requete_produits = $this->db->query($statement_produits);
    $produits = $requete_produits->fetchAll();

    // Qualités
    $requete_qualites = $this->db->prepare($statement_qualites);
    foreach ($produits as $produit) {
      $requete_qualites->execute(["produit" => $produit["id"]]);
      $produit["qualites"] = $requete_qualites->fetchAll();
      array_push($donnees, $produit);
    }

    return $donnees;
  }

  /**
   * Récupère un produit vrac.
   * 
   * @param int $id ID du produit à récupérer
   * 
   * @return array Produit récupéré
   */
  public function read($id)
  {
    $statement_produit =
      "SELECT
        id,
        nom,
        couleur,
        unite
      FROM vrac_produits
      WHERE id = :id";

    $statement_qualites =
      "SELECT *
        FROM vrac_qualites
        WHERE produit = :produit
        ORDER BY nom";

    // Produit
    $requete_produit = $this->db->prepare($statement_produit);
    $requete_produit->execute(["id" => $id]);
    $produit = $requete_produit->fetch();

    // Qualités
    $requete_qualites = $this->db->prepare($statement_qualites);
    if ($produit) {
      $requete_qualites->execute(["produit" => $id]);
      $produit["qualites"] = $requete_qualites->fetchAll();
    }

    $donnees = $produit;

    return $donnees;
  }

  /**
   * Crée un produit vrac.
   * 
   * @param array $input Eléments du produit à créer
   * 
   * @return array Produit créé
   */
  public function create(array $input)
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

    $requete_produit = $this->db->prepare($statement_produit);

    $this->db->beginTransaction();
    $requete_produit->execute([
      'nom' => $input["nom"],
      'couleur' => $input["couleur"],
      'unite' => $input["unite"]
    ]);
    $last_id = $this->db->lastInsertId();
    $this->db->commit();

    // Qualités
    $requete_qualites = $this->db->prepare($statement_qualites);
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
   * @return array Produit modifié
   */
  public function update($id, array $input)
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

    $requete_produit = $this->db->prepare($statement_produit);
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
    $requete_qualites = $this->db->prepare("SELECT id FROM vrac_qualites WHERE produit = :produit");
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

    $requete_supprimer = $this->db->prepare("DELETE FROM vrac_qualites WHERE id = :id");
    foreach ($ids_qualites_a_supprimer as $id_suppr) {
      $requete_supprimer->execute(['id' => $id_suppr]);
    }

    // Ajout et modification qualités
    $requete_qualites_ajout = $this->db->prepare($statement_qualites_ajout);
    $requete_qualites_modif = $this->db->prepare($statement_qualites_modif);
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
  public function delete(int $id)
  {
    $requete = $this->db->prepare("DELETE FROM vrac_produits WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
