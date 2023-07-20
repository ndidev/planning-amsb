<?php

namespace Api\Models\Consignation;

use Api\Utils\BaseModel;

class TEModel extends BaseModel
{
  /**
   * Récupère tous les tirants d'eau du planning consignation.
   * 
   * @return array Tous les tirants d'eau récupérés
   */
  public function readAll()
  {
    $statement = "SELECT * FROM drafts_par_tonnage";

    $donnees = $this->db->query($statement)->fetchAll();

    return $donnees;
  }
}
