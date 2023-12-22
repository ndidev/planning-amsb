<?php

namespace App\Models\Consignation;

use App\Models\Model;

class StatsModel extends Model
{
  /**
   * Récupère les stats consignation.
   * 
   * @param array $filtre Filtre qui contient...
   */
  public function readAll(array $filtre): array
  {
    // Filtre
    $date_debut = isset($filtre['date_debut']) ? ($filtre['date_debut'] ?: "0001-01-01") : "0001-01-01";
    $date_fin = isset($filtre["date_fin"]) ? ($filtre['date_fin'] ?: "9999-12-31") : "9999-12-31";
    $filtre_navire = trim(preg_replace("/([\w\s]+),?/", "'$1',", $filtre['navire'] ?? ""), ",");
    $filtre_armateur = trim($filtre['armateur'] ?? "", ",");
    $filtre_last_port = trim(preg_replace("/([\w\s]+),?/", "'$1',", $filtre['last_port'] ?? ""), ",");
    $filtre_next_port = trim(preg_replace("/([\w\s]+),?/", "'$1',", $filtre['next_port'] ?? ""), ",");

    $filtre_sql_navire = $filtre_navire === "" ? "" : " AND cp.navire IN ($filtre_navire)";
    $filtre_sql_armateur = $filtre_armateur === "" ? "" : " AND cp.armateur IN ($filtre_armateur)";
    $filtre_sql_last_port = $filtre_last_port === "" ? "" : " AND cp.last_port IN ($filtre_last_port)";
    $filtre_sql_next_port = $filtre_next_port === "" ? "" : " AND cp.next_port IN ($filtre_next_port)";

    $filtre_sql =
      $filtre_sql_navire
      . $filtre_sql_armateur
      . $filtre_sql_last_port
      . $filtre_sql_next_port;

    $statement_escales =
      "SELECT
          cp.etc_date as `date`
        FROM consignation_planning cp
        WHERE cp.etc_date BETWEEN :date_debut AND :date_fin
        $filtre_sql";

    $requete_escales = $this->mysql->prepare($statement_escales);

    $requete_escales->execute([
      "date_debut" => $date_debut,
      "date_fin" => $date_fin
    ]);

    $escales = $requete_escales->fetchAll();

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

    // Compilation du nombre de RDV par année et par mois
    foreach ($escales as $rdv) {
      $date = explode("-", $rdv["date"]);
      $annee = $date[0];
      $mois = $date[1];

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
