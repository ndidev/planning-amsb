<?php

namespace Api\Models\Config;

use Api\Utils\DatabaseConnector as DB;

class ModulesModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère la liste des modules de l'application.
   * 
   * @return array Tous les modules récupérés
   */
  public function read()
  {
    $requete = $this->db->query("SELECT * FROM config_modules");
    $donnees = $requete->fetchAll();

    return $donnees;
  }
}
