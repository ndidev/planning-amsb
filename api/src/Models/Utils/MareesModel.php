<?php

namespace Api\Models\Utils;

use Api\Utils\DatabaseConnector as DB;

class MareesModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère toutes les marées.
   * 
   * @return array Toutes les marées récupérées.
   */
  public function readAll(array $filtre = []): array
  {
    $debut = $filtre["debut"] ?? "0001-01-01";
    $fin = $filtre["fin"] ?? "9999-12-31";
    $annees = isset($filtre["annees"]);

    $statement_full =
      "SELECT *
        FROM marees m
        WHERE m.date BETWEEN :debut AND :fin";

    $statement_annees =
      "SELECT DISTINCT SUBSTRING(date, 1, 4) AS annee
        FROM `utils_marees_shom` m
        WHERE m.date BETWEEN :debut AND :fin";

    $statement = $annees ? $statement_annees : $statement_full;

    $requete = $this->db->prepare($statement);
    $requete->execute([
      "debut" => $debut,
      "fin" => $fin
    ]);
    $donnees = $requete->fetchAll();

    if (!$annees) {
      for ($i = 0; $i < count($donnees); $i++) {
        $donnees[$i]["heure"] = substr($donnees[$i]["heure"], 0, -3);
        $donnees[$i]["te_cesson"] = (float) $donnees[$i]["te_cesson"];
        $donnees[$i]["te_bassin"] = (float) $donnees[$i]["te_bassin"];
      }
    }

    if ($annees) {
      for ($i = 0; $i < count($donnees); $i++) {
        $donnees[$i] = $donnees[$i]["annee"];
      }
    }

    return $donnees;
  }

  /**
   * Récupère les marées pour une année.
   * 
   * @return array Toutes les marées récupérées.
   */
  public function read(int $annee): array
  {
    $statement =
      "SELECT *
        FROM marees m
        WHERE SUBSTRING(date, 1, 4) = :annee";

    $requete = $this->db->prepare($statement);
    $requete->execute([
      "annee" => $annee,
    ]);
    $donnees = $requete->fetchAll();

    for ($i = 0; $i < count($donnees); $i++) {
      $donnees[$i]["heure"] = substr($donnees[$i]["heure"], 0, -3);
      $donnees[$i]["te_cesson"] = (float) $donnees[$i]["te_cesson"];
      $donnees[$i]["te_bassin"] = (float) $donnees[$i]["te_bassin"];
    }

    return $donnees;
  }

  /**
   * Ajoute des marées pour une année.
   */
  public function create(array $marees)
  {
    $statement = "INSERT INTO utils_marees_shom
      VALUES(
        :date,
        :heure,
        :hauteur
      )
    ";

    $requete = $this->db->prepare($statement);

    $this->db->beginTransaction();
    foreach ($marees as [$date, $heure, $hauteur]) {
      $requete->execute([
        "date" => $date,
        "heure" => $heure,
        "hauteur" => $hauteur
      ]);
    }
    $this->db->commit();
  }

  /**
   * Supprime les marrées pour une année.
   * 
   * @param int $annee Année pour laquelle supprimer les marées.
   * 
   * @return bool `true` si succès, `false` sinon
   */
  public function delete(int $annee): bool
  {
    $requete = $this->db->prepare("DELETE FROM utils_marees_shom WHERE SUBSTRING(date, 1, 4) = :annee");
    $succes = $requete->execute(["annee" => $annee]);

    return $succes;
  }
}
