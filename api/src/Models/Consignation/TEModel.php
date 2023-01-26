<?php

namespace Api\Models\Consignation;

use Api\Utils\DatabaseConnector as DB;

class TEModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

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
