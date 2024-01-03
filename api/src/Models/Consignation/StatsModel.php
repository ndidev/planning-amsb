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
    $filtre_navire = trim(preg_replace("/([^,]+),?/", "'$1',", $filtre['navire'] ?? ""), ",");
    $filtre_armateur = trim($filtre['armateur'] ?? "", ",");
    $filtre_marchandise = trim(preg_replace("/([^,]+),?/", "'$1',", $filtre['marchandise'] ?? ""), ",");
    $filtre_client = trim(preg_replace("/([^,]+),?/", "'$1',", $filtre['client'] ?? ""), ",");
    $filtre_last_port = trim(preg_replace("/([^,]+),?/", "'$1',", $filtre['last_port'] ?? ""), ",");
    $filtre_next_port = trim(preg_replace("/([^,]+),?/", "'$1',", $filtre['next_port'] ?? ""), ",");

    $filtre_sql_navire = $filtre_navire === "" ? "" : " AND cp.navire IN ($filtre_navire)";
    $filtre_sql_marchandise = $filtre_marchandise === "" ? "" : " AND cem.marchandise IN ($filtre_marchandise)";
    $filtre_sql_armateur = $filtre_armateur === "" ? "" : " AND cp.armateur IN ($filtre_armateur)";
    $filtre_sql_client = $filtre_client === "" ? "" : " AND cem.client IN ($filtre_client)";
    $filtre_sql_last_port = $filtre_last_port === "" ? "" : " AND cp.last_port IN ($filtre_last_port)";
    $filtre_sql_next_port = $filtre_next_port === "" ? "" : " AND cp.next_port IN ($filtre_next_port)";

    $filtre_sql =
      $filtre_sql_navire
      . $filtre_sql_marchandise
      . $filtre_sql_armateur
      . $filtre_sql_client
      . $filtre_sql_last_port
      . $filtre_sql_next_port;

    $statement_escales =
      "SELECT
          cp.id,
          cp.etc_date as `date`
        FROM consignation_planning cp
        LEFT JOIN consignation_escales_marchandises cem ON cem.escale_id = cp.id
        WHERE cp.etc_date BETWEEN :date_debut AND :date_fin
        $filtre_sql
        GROUP BY cp.id";

    $requete_escales = $this->mysql->prepare($statement_escales);

    $requete_escales->execute([
      "date_debut" => $date_debut,
      "date_fin" => $date_fin
    ]);

    $escales = $requete_escales->fetchAll();

    $stats = [
      "Total" => count($escales),
      "Par année" => [],
    ];

    $modele_annee = [
      1 => ["nombre" => 0, "ids" => []],
      2 => ["nombre" => 0, "ids" => []],
      3 => ["nombre" => 0, "ids" => []],
      4 => ["nombre" => 0, "ids" => []],
      5 => ["nombre" => 0, "ids" => []],
      6 => ["nombre" => 0, "ids" => []],
      7 => ["nombre" => 0, "ids" => []],
      8 => ["nombre" => 0, "ids" => []],
      9 => ["nombre" => 0, "ids" => []],
      10 => ["nombre" => 0, "ids" => []],
      11 => ["nombre" => 0, "ids" => []],
      12 => ["nombre" => 0, "ids" => []],
    ];

    // Compilation du nombre de RDV par année et par mois
    foreach ($escales as $escale) {
      $date = explode("-", $escale["date"]);
      $annee = $date[0];
      $mois = $date[1];

      if (!array_key_exists($annee, $stats["Par année"])) {
        $stats["Par année"][$annee] = $modele_annee;
      };

      // $stats["Total"]++;
      $stats["Par année"][$annee][(int) $mois]["nombre"]++;
      $stats["Par année"][$annee][(int) $mois]["ids"][] = $escale["id"];
    }

    $donnees = $stats;

    return $donnees;
  }

  /**
   * Récupère les détails des stats consignation.
   * 
   * @param string $ids Identifiants des escales.
   */
  public function readDetails(string $ids): array
  {
    // Filtre
    $filtre_ids = trim(preg_replace("/([^,]+),?/", "'$1',", $ids ?? ""), ",");

    $statement_escales =
      "SELECT
          cp.id,
          cp.navire,
          cp.ops_date,
          cp.etc_date,
          cem.marchandise,
          cem.client,
          cem.tonnage_bl,
          cem.tonnage_outturn,
          cem.cubage_bl,
          cem.cubage_outturn,
          cem.nombre_bl,
          cem.nombre_outturn
        FROM consignation_planning cp
        LEFT JOIN consignation_escales_marchandises cem ON cem.escale_id = cp.id
        WHERE cp.id IN ($filtre_ids)
        ORDER BY cp.etc_date DESC";

    $requete_escales = $this->mysql->query($statement_escales);

    $escales = $requete_escales->fetchAll();

    $escales_groupees = [];

    // Grouper par escale
    foreach ($escales as $escale) {
      if (!array_key_exists($escale["id"], $escales_groupees)) {
        $escales_groupees[$escale["id"]] = [
          "id" => $escale["id"],
          "navire" => $escale["navire"],
          "ops_date" => $escale["ops_date"],
          "etc_date" => $escale["etc_date"],
          "marchandises" => [],
        ];
      }

      $escales_groupees[$escale["id"]]["marchandises"][] = [
        "marchandise" => $escale["marchandise"],
        "client" => $escale["client"],
        "tonnage_outturn" => $escale["tonnage_outturn"] ?: $escale["tonnage_bl"],
        "cubage_outturn" => $escale["cubage_outturn"] ?: $escale["cubage_bl"],
        "nombre_outturn" => $escale["nombre_outturn"] ?: $escale["nombre_bl"],
      ];
    }

    $donnees = array_values($escales_groupees);

    return $donnees;
  }
}
