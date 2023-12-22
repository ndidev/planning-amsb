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
   * @param array $filtre
   * 
   * @return array Tous les affrètements récupérés
   */
  public function readAll(array $filtre = []): array
  {
    // Filtre
    $date_debut = isset($filtre["date_debut"]) ? $filtre['date_debut'] : "0001-01-01";
    $date_fin = isset($filtre["date_fin"]) ? $filtre['date_fin'] : "9999-12-31";
    $filtre_statut = $filtre["statut"] ?? "";
    $filtre_affreteur = trim($filtre['affreteur'] ?? "", ",");
    $filtre_armateur = trim($filtre['armateur'] ?? "", ",");
    $filtre_courtier = trim($filtre['courtier'] ?? "", ",");

    $filtre_sql_affreteur = $filtre_affreteur === "" ? "" : " AND affreteur IN ($filtre_affreteur)";
    $filtre_sql_armateur = $filtre_armateur === "" ? "" : " AND armateur IN ($filtre_armateur)";
    $filtre_sql_courtier = $filtre_courtier === "" ? "" : " AND courtier IN ($filtre_courtier)";
    $filtre_sql_statut = $filtre_statut === "" ? "" : " AND statut IN ($filtre_statut)";

    $filtre_sql =
      $filtre_sql_affreteur
      . $filtre_sql_armateur
      . $filtre_sql_courtier
      . $filtre_sql_statut;

    $filtre_archives = (int) array_key_exists("archives", $filtre);

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
          WHERE archive = $filtre_archives
          AND (lc_debut <= :date_fin OR lc_debut IS NULL)
          AND (lc_fin >= :date_debut OR lc_fin IS NULL)
          $filtre_sql
          ORDER BY " . ($filtre_archives ? "-lc_debut ASC, -lc_fin ASC" : "-lc_debut DESC, -lc_fin DESC");

    $statement_details =
      "SELECT *
        FROM chartering_detail
        WHERE charter = :id";

    // Charters
    $requete_charters = $this->mysql->prepare($statement_charters);
    $requete_charters->execute([
      "date_debut" => $date_debut,
      "date_fin" => $date_fin
    ]);
    $charters = $requete_charters->fetchAll();

    $requete_details = $this->mysql->prepare($statement_details);

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
      $requete_details->execute(["id" => $charter["id"]]);
      $details = $requete_details->fetchAll();

      $charter["legs"] = $details;
    }


    $donnees = $charters;

    return $donnees;
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
    $statement_charter =
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

    $statement_details =
      "SELECT *
        FROM chartering_detail
        WHERE charter = :id";

    // Charters
    $requete_charter = $this->mysql->prepare($statement_charter);
    $requete_charter->execute(["id" => $id]);
    $charter = $requete_charter->fetch();

    if (!$charter) return null;

    // Détails
    $requete_details = $this->mysql->prepare($statement_details);
    $requete_details->execute(["id" => $id]);
    $details = $requete_details->fetchAll();


    $charter["archive"] = (bool) $charter["archive"];
    $charter["affreteur"] = (int) $charter["affreteur"] ?: NULL;
    $charter["armateur"] = (int) $charter["armateur"] ?: NULL;
    $charter["courtier"] = (int) $charter["courtier"] ?: NULL;
    $charter["fret_achat"] = (float) $charter["fret_achat"];
    $charter["fret_vente"] = (float) $charter["fret_vente"];
    $charter["surestaries_achat"] = (float) $charter["surestaries_achat"];
    $charter["surestaries_vente"] = (float) $charter["surestaries_vente"];

    $charter["legs"] = $details;

    $donnees = $charter;

    return $donnees;
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

    $statement_charter =
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

    $statement_details =
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

    $requete = $this->mysql->prepare($statement_charter);

    $this->mysql->beginTransaction();
    $requete->execute([
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
      "fret_achat" => $input["fret_achat"],
      "fret_vente" => $input["fret_vente"],
      "surestaries_achat" => $input["surestaries_achat"],
      "surestaries_vente" => $input["surestaries_vente"],
      // Divers
      "commentaire" => $input["commentaire"],
      "archive" => (int) $input["archive"],
    ]);

    $last_id = $this->mysql->lastInsertId();
    $this->mysql->commit();

    // Détails
    $requete_details = $this->mysql->prepare($statement_details);
    $details = $input["legs"] ?? [];
    foreach ($details as $detail) {
      $requete_details->execute([
        "charter" => $last_id,
        "bl_date" => $detail["bl_date"],
        "marchandise" => $detail["marchandise"],
        "quantite" => $detail["quantite"],
        "pol" => $detail["pol"],
        "pod" => $detail["pod"],
        "commentaire" => $detail["commentaire"],
      ]);
    }

    return $this->read($last_id);
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

    $statement_charter =
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

    $requete = $this->mysql->prepare($statement_charter);
    $requete->execute([
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
      "fret_achat" => $input["fret_achat"],
      "fret_vente" => $input["fret_vente"],
      "surestaries_achat" => $input["surestaries_achat"],
      "surestaries_vente" => $input["surestaries_vente"],
      // Divers
      "commentaire" => $input["commentaire"],
      "archive" => (int) $input["archive"],
      'id' => $id,
    ]);

    // DETAILS
    // Suppression details
    // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE detail POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
    // Comparaison du tableau transmis par POST avec la liste existante des details pour le produit concerné
    $requete_details = $this->mysql->prepare(
      "SELECT id
          FROM chartering_detail
          WHERE charter = :charter_id"
    );
    $requete_details->execute(['charter_id' => $id]);
    $ids_details_existantes = [];
    while ($detail = $requete_details->fetch()) {
      $ids_details_existantes[] = $detail['id'];
    }

    $ids_details_transmises = [];
    if (isset($input["legs"])) {
      foreach ($input["legs"] as $detail) {
        $ids_details_transmises[] = $detail["id"];
      }
    }
    $ids_details_a_supprimer = array_diff($ids_details_existantes, $ids_details_transmises);

    $requete_supprimer = $this->mysql->prepare(
      "DELETE FROM chartering_detail
          WHERE id = :id"
    );
    foreach ($ids_details_a_supprimer as $id_suppr) {
      $requete_supprimer->execute(['id' => $id_suppr]);
    }

    // Ajout et modification details
    $requete_details_ajout = $this->mysql->prepare($statement_details_ajout);
    $requete_details_modif = $this->mysql->prepare($statement_details_modif);
    $details = $input["legs"] ?? [];
    foreach ($details as $detail) {
      if ((int) $detail["id"]) {
        $requete_details_modif->execute([
          "bl_date" => $detail["bl_date"],
          "pol" => $detail["pol"],
          "pod" => $detail["pod"],
          "marchandise" => $detail["marchandise"],
          "quantite" => $detail["quantite"],
          "commentaire" => $detail["commentaire"],
          "id" => $detail["id"],
        ]);
      } else {
        $requete_details_ajout->execute([
          'charter' => $id,
          "bl_date" => $detail["bl_date"],
          "pol" => $detail["pol"],
          "pod" => $detail["pod"],
          "marchandise" => $detail["marchandise"],
          "quantite" => $detail["quantite"],
          "commentaire" => $detail["commentaire"],
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
    $requete = $this->mysql->prepare("DELETE FROM chartering_registre WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
