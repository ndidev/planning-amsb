<?php

namespace Api\Models\Bois;

use Api\Utils\BaseModel;

class StatsModel extends BaseModel
{
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Récupère les stats bois.
   * 
   * @param array $filtre Filtre qui contient...
   */
  public function readAll(array $filtre): array
  {
    // Filtre
    $date_debut = isset($filtre['date_debut']) ? ($filtre['date_debut'] ?: date("Y-m-d")) : date("Y-m-d");
    $date_fin = isset($filtre["date_fin"]) ? ($filtre['date_fin'] ?: "9999-12-31") : "9999-12-31";
    $filtre_fournisseur = preg_replace("/,$/", "", $filtre['fournisseur'] ?? "");
    $filtre_client = preg_replace("/,$/", "", $filtre['client'] ?? "");
    $filtre_chargement = preg_replace("/,$/", "", $filtre['chargement'] ?? "");
    $filtre_livraison = preg_replace("/,$/", "", $filtre['livraison'] ?? "");
    $filtre_transporteur = preg_replace("/,$/", "", $filtre['transporteur'] ?? "");
    $filtre_affreteur = preg_replace("/,$/", "", $filtre['affreteur'] ?? "");

    $filtre_sql_fournisseur = $filtre_fournisseur === "" ? "" : " AND fournisseur IN ($filtre_fournisseur)";
    $filtre_sql_client = $filtre_client === "" ? "" : " AND client IN ($filtre_client)";
    $filtre_sql_chargement = $filtre_chargement === "" ? "" : " AND chargement IN ($filtre_chargement)";
    $filtre_sql_livraison = $filtre_livraison === "" ? "" : " AND livraison IN ($filtre_livraison)";
    $filtre_sql_transporteur = $filtre_transporteur === "" ? "" : " AND transporteur IN ($filtre_transporteur)";
    $filtre_sql_affreteur = $filtre_affreteur === "" ? "" : " AND affreteur IN ($filtre_affreteur)";

    $filtre_sql =
      $filtre_sql_fournisseur
      . $filtre_sql_client
      . $filtre_sql_chargement
      . $filtre_sql_livraison
      . $filtre_sql_transporteur
      . $filtre_sql_affreteur;

    $statement_rdvs =
      "SELECT
          date_rdv
        FROM bois_planning
        WHERE date_rdv BETWEEN :date_debut AND :date_fin
        AND attente = 0
        $filtre_sql";


    $requete_rdvs = $this->db->prepare($statement_rdvs);

    $requete_rdvs->execute([
      "date_debut" => $date_debut,
      "date_fin" => $date_fin
    ]);

    $rdvs = $requete_rdvs->fetchAll();

    $stats = [
      "Total" => 0,
      "Par année" => [],
    ];

    $modele_annee = [
      1 => 0,
      2 => 0,
      3 => 0,
      4 => 0,
      5 => 0,
      6 => 0,
      7 => 0,
      8 => 0,
      9 => 0,
      10 => 0,
      11 => 0,
      12 => 0,
    ];

    foreach ($rdvs as $rdv) {
      $date_rdv = explode("-", $rdv["date_rdv"]);
      $annee = $date_rdv[0];
      $mois = $date_rdv[1];

      if (!array_key_exists($annee, $stats["Par année"])) {
        $stats["Par année"][$annee] = $modele_annee;
      };

      $stats["Total"]++;
      $stats["Par année"][$annee][(int) $mois]++;
    }

    $donnees = $stats;

    return $donnees;
  }
}
