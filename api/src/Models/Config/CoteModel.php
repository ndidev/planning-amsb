<?php

namespace Api\Models\Config;

use Api\Utils\DatabaseConnector as DB;

class CoteModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère toutes les côtes.
   * 
   * @return array Toutes les côtes récupérées.
   */
  public function readAll()
  {
    $statement = "SELECT * FROM config_cotes";

    $donnees = $this->db->query($statement)->fetchAll();

    for ($i = 0; $i < count($donnees); $i++) {
      $donnees[$i]["valeur"] = (float) $donnees[$i]["valeur"];
    }

    return $donnees;
  }

  /**
   * Récupère une côte.
   * 
   * @param string $cote Nom de la côte à récupérer
   * 
   * @return array Côte récupérée
   */
  public function read(string $nom_cote)
  {
    $statement = "SELECT *
      FROM config_cotes
      WHERE cote = :cote";

    $requete = $this->db->prepare($statement);
    $requete->execute(["cote" => $nom_cote]);
    $cote = $requete->fetch();

    $cote["valeur"] = (float) $cote["valeur"];

    $donnees = $cote;

    return $donnees;
  }

  /**
   * Met à jour une côte.
   * 
   * @param string $cote  Nom de la côte à modifier
   * @param array  $input Valeur de la côte à modifier
   * 
   * @return array Côte modifiée
   */
  public function update(string $cote, array $input)
  {
    $statement = "UPDATE config_cotes
      SET valeur = :valeur
      WHERE cote = :cote";

    $requete = $this->db->prepare($statement);
    $requete->execute([
      'valeur' => (float) $input["valeur"],
      'cote' => $cote
    ]);

    return $this->read($cote);
  }
}
