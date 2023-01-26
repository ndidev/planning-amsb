<?php

namespace Api\Models\Vrac;

use Api\Utils\DatabaseConnector as DB;

class RdvModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère tous les RDV vrac.
   * 
   * @return array Tous les RDV récupérés
   */
  public function readAll($query = null)
  {
    $tri = $query["tri"] ?? NULL;

    switch ($tri) {
      case 'produit':
        // Tri par produit pour affichage TV
        $statement =
          "SELECT
            pl.id,
            pl.date_rdv,
            SUBSTRING(pl.heure, 1, 5) AS heure,
            p.nom AS produit_nom,
            p.couleur AS produit_couleur,
            p.unite,
            q.nom AS qualite_nom,
            q.couleur AS qualite_couleur,
            pl.quantite,
            pl.max,
            f.nom_court AS fournisseur_nom,
            c.nom_court AS client_nom,
            c.ville AS client_ville,
            t.nom_court AS transporteur_nom,
            pl.num_commande,
            pl.commentaire
          FROM vrac_planning pl
          LEFT JOIN vrac_produits p ON p.id = pl.produit
          LEFT JOIN vrac_qualites q ON q.id = pl.qualite
          LEFT JOIN tiers c ON c.id = pl.client
          LEFT JOIN tiers t ON t.id = pl.transporteur
          LEFT JOIN tiers f ON f.id = pl.fournisseur
          ORDER BY date_rdv, produit_nom, -heure DESC";
        break;

      default:
        // Tri par heure pour affichage PC
        $statement =
          "SELECT
            pl.id,
            pl.date_rdv,
            SUBSTRING(pl.heure, 1, 5) AS heure,
            p.nom AS produit_nom,
            p.couleur AS produit_couleur,
            p.unite,
            q.nom AS qualite_nom,
            q.couleur AS qualite_couleur,
            pl.quantite,
            pl.max,
            f.nom_court AS fournisseur_nom,
            c.nom_court AS client_nom,
            c.ville AS client_ville,
            t.nom_court AS transporteur_nom,
            pl.num_commande,
            pl.commentaire
          FROM vrac_planning pl
          LEFT JOIN vrac_produits p ON p.id = pl.produit
          LEFT JOIN vrac_qualites q ON q.id = pl.qualite
          LEFT JOIN tiers c ON c.id = pl.client
          LEFT JOIN tiers t ON t.id = pl.transporteur
          LEFT JOIN tiers f ON f.id = pl.fournisseur
          ORDER BY date_rdv, -heure DESC, produit_nom, qualite_nom";
        break;
    }

    $requete = $this->db->query($statement);
    $rdvs = $requete->fetchAll();

    // Rétablissement des types INT
    array_walk_recursive($rdvs, function (&$value, $key) {
      $value = match ($key) {
        "id",
        "produit",
        "qualite",
        "quantite",
        "max",
        "fournisseur",
        "client",
        "transporteur" => $value === NULL ? NULL : (int) $value,
        default => $value,
      };
    });

    $rdvs_ordonnes = $rdvs;


    /**
     * Regroupement
     */
    $groupe = $query["groupe"] ?? NULL;
    // Regroupement des RDVs par date
    if ($groupe === "date") {
      $rdvs_ordonnes = [];

      foreach ($rdvs as $rdv) {
        $rdvs_ordonnes[$rdv["date_rdv"]]["rdvs"][] = $rdv;
      }
    }


    /**
     * Navires à quai
     */
    $navires = $query["navires"] ?? NULL;

    if ($groupe === "date" && $navires) {
      $statement_navires =
        "SELECT navire
          FROM consignation_planning
          WHERE ops_date <= :date
          AND etc_date >= :date";

      $requete_navires = $this->db->prepare($statement_navires);

      foreach ($rdvs_ordonnes as $date => $rdvs) {
        $requete_navires->execute(["date" => $date]);
        $reponse_navires = $requete_navires->fetchAll();
        $rdvs_ordonnes[$date]["navires"] = [];
        foreach ($reponse_navires as $navire) {
          array_push($rdvs_ordonnes[$date]["navires"], $navire["navire"]);
        }
      }
    }

    /**
     * Marées
     */
    $marees = $query["marees"] ?? NULL;

    if ($groupe === "date" && $marees) {
      $statement_marees =
        "SELECT MAX(te_cesson) AS te
          FROM marees
          WHERE date = :date";

      $requete_marees = $this->db->prepare($statement_marees);

      foreach ($rdvs_ordonnes as $date => $rdvs) {
        $requete_marees->execute(["date" => $date]);
        $reponse_marees = $requete_marees->fetchAll();
        foreach ($reponse_marees as $maree) {
          $rdvs_ordonnes[$date]["te"] = (float) $maree["te"] ?: NULL;
        }
      }
    }


    $donnees = $rdvs_ordonnes;

    return $donnees;
  }

  /**
   * Récupère un RDV vrac.
   * 
   * @param int $id ID du RDV à récupérer
   * 
   * @return array Rendez-vous récupéré
   */
  public function read($id)
  {
    $statement =
      "SELECT
          pl.id,
          pl.date_rdv,
          pl.heure,
          pl.produit,
          pl.qualite,
          pl.quantite,
          pl.max,
          pl.fournisseur,
          f.nom_court AS fournisseur_nom,
          pl.client,
          c.nom_court AS client_nom,
          c.ville AS client_ville,
          pl.transporteur,
          t.nom_court AS transporteur_nom,
          pl.num_commande,
          pl.commentaire
        FROM vrac_planning pl
        LEFT JOIN tiers c ON c.id = pl.client
        LEFT JOIN tiers t ON t.id = pl.transporteur
        LEFT JOIN tiers f ON f.id = pl.fournisseur
        WHERE pl.id = :id";

    $requete = $this->db->prepare($statement);
    $requete->execute(["id" => $id]);
    $rdv = $requete->fetch();

    // Suppression des secondes de l'heure (hh:mm)
    if ($rdv) {
      $rdv["heure"] = substr($rdv["heure"] ?? "", 0, -3);

      // Rétablissement des types INT
      array_walk_recursive($rdv, function (&$value, $key) {
        $value = match ($key) {
          "id",
          "produit",
          "qualite",
          "quantite",
          "max",
          "fournisseur",
          "client",
          "transporteur" => $value === NULL ? NULL : (int) $value,
          default => $value,
        };
      });
    }

    $donnees = $rdv;

    return $donnees;
  }

  /**
   * Crée un RDV vrac.
   * 
   * @param array $input Eléments du RDV à créer
   * 
   * @return array Rendez-vous créé
   */
  public function create(array $input)
  {
    $statement = "INSERT INTO vrac_planning
    VALUES(
      NULL,
      :date_rdv,
      :heure,
      :produit,
      :qualite,
      :quantite,
      :max,
      :fournisseur,
      :client,
      :transporteur,
      :num_commande,
      :commentaire
    )";

    $requete = $this->db->prepare($statement);

    $this->db->beginTransaction();
    $requete->execute([
      'date_rdv' => $input["date_rdv"],
      'heure' => $input["heure"] ?: NULL,
      'produit' => $input["produit"],
      'qualite' => $input["qualite"] ?? NULL,
      'quantite' => $input["quantite"],
      'max' => isset($input["max"]) ? 1 : 0,
      'fournisseur' => $input["fournisseur"],
      'client' => $input["client"],
      'transporteur' => $input["transporteur"] ?: NULL,
      'num_commande' => $input["num_commande"],
      'commentaire' => $input["commentaire"]
    ]);

    $last_id = $this->db->lastInsertId();
    $this->db->commit();

    return $this->read($last_id);
  }

  /**
   * Met à jour un RDV vrac.
   * 
   * @param int   $id ID du RDV à modifier
   * @param array $input  Eléments du RDV à modifier
   * 
   * @return array RDV modifié
   */
  public function update($id, array $input)
  {
    $statement = "UPDATE vrac_planning
      SET
        date_rdv = :date_rdv,
        heure = :heure,
        produit = :produit,
        qualite = :qualite,
        quantite = :quantite,
        max = :max,
				fournisseur = :fournisseur,
        client = :client,
        transporteur = :transporteur,
        num_commande = :num_commande,
        commentaire = :commentaire
      WHERE id = :id";

    $requete = $this->db->prepare($statement);
    $requete->execute([
      'date_rdv' => $input["date_rdv"],
      'heure' => $input["heure"] ?: NULL,
      'produit' => $input["produit"],
      'qualite' => $input["qualite"] ?? NULL,
      'quantite' => $input["quantite"],
      'max' => isset($input["max"]) ? 1 : 0,
      'fournisseur' => $input["fournisseur"],
      'client' => $input["client"],
      'transporteur' => $input["transporteur"] ?: NULL,
      'num_commande' => $input["num_commande"],
      'commentaire' => $input["commentaire"],
      'id' => $id
    ]);

    return $this->read($id);
  }

  /**
   * Supprime un RDV vrac.
   * 
   * @param int $id ID du RDV à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(int $id)
  {
    $requete = $this->db->prepare("DELETE FROM vrac_planning WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
