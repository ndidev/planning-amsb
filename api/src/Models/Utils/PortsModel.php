<?php

namespace Api\Models\Utils;

use Api\Utils\DatabaseConnector as DB;

class PortsModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère tous les ports.
   * 
   * @return array Tous les ports récupérés.
   */
  public function readAll()
  {
    $statement = "SELECT * FROM utils_ports ORDER BY SUBSTRING(locode, 1, 2), nom";

    $donnees = $this->db->query($statement)->fetchAll();

    return $donnees;
  }

  /**
   * Récupère un port.
   * 
   * @param string $locode UNLOCODE du port à récupérer
   * 
   * @return array Port récupéré
   */
  public function read($locode)
  {
    $statement = "SELECT *
      FROM utils_ports
      WHERE locode = :locode";

    $requete = $this->db->prepare($statement);
    $requete->execute(["locode" => $locode]);
    $port = $requete->fetch();


    $donnees = $port;

    return $donnees;
  }

  /**
   * Crée un port.
   * 
   * @param array $input Eléments du port à créer
   * 
   * @return array Port créé
   */
  public function create(array $input)
  {
    $statement = "INSERT INTO utils_ports
      VALUES(
        :locode,
        :nom
      )";

    $requete = $this->db->prepare($statement);

    $this->db->beginTransaction();
    $requete->execute([
      'locode' => $input["locode"],
      'nom' => $input["nom"]
    ]);

    $last_id = $this->db->lastInsertId();
    $this->db->commit();

    return $this->read($last_id);
  }

  /**
   * Met à jour un port.
   * 
   * @param string $locode UNLOCODE du port à modifier
   * @param array  $input  Eléments du port à modifier
   * 
   * @return array Port modifié
   */
  public function update($locode, array $input)
  {
    $statement = "UPDATE utils_ports
      SET nom = :nom
      WHERE locode = :locode";

    $requete = $this->db->prepare($statement);
    $requete->execute([
      'nom' => $input["nom"],
      'locode' => $locode
    ]);

    return $this->read($locode);
  }

  /**
   * Supprime un port.
   * 
   * @param string $locode UNLOCODE du port à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete($locode)
  {
    $requete = $this->db->prepare("DELETE FROM utils_ports WHERE locode = :locode");
    $succes = $requete->execute(["locode" => $locode]);

    return $succes;
  }
}
