<?php

namespace Api\Models\Consignation;

use Api\Utils\BaseModel;

class ListeNaviresModel extends BaseModel
{
  /**
   * Récupère un numéro de voyage pour un navire.
   * 
   * @param array $query
   * 
   * @return array Nouveau numéro de voyage
   */
  public function readAll($query)
  {
    $date_debut = isset($query['date_debut']) ? ($query['date_debut'] ?: date("Y-m-d")) : date("Y-m-d");
    $date_fin = isset($query['date_fin']) ? ($query['date_fin'] ?: "9999-12-31") : "9999-12-31";

    $statement =
      "SELECT navire, ops_date AS debut, etc_date AS fin
        FROM consignation_planning
        WHERE ops_date <= :date_fin AND etc_date >= :date_debut";

    $requete = $this->mysql->prepare($statement);
    $requete->execute([
      "date_debut" => $date_debut,
      "date_fin" => $date_fin,
    ]);
    $navires = $requete->fetchAll();

    $donnees = $navires;

    return $donnees;
  }
}
