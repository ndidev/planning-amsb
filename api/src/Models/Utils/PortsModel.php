<?php

namespace Api\Models\Utils;

use Api\Utils\BaseModel;

class PortsModel extends BaseModel
{
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Récupère tous les ports.
   * 
   * @return array Tous les ports récupérés.
   */
  public function readAll(): array
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
  public function read($locode): ?array
  {
    $statement = "SELECT *
      FROM utils_ports
      WHERE locode = :locode";

    $requete = $this->db->prepare($statement);
    $requete->execute(["locode" => $locode]);
    $port = $requete->fetch();

    if (!$port) return null;


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
  public function create(array $input): array
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
  public function update($locode, array $input): array
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
  public function delete($locode): bool
  {
    $requete = $this->db->prepare("DELETE FROM utils_ports WHERE locode = :locode");
    $succes = $requete->execute(["locode" => $locode]);

    return $succes;
  }
}
