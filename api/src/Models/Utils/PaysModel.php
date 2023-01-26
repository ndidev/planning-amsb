<?php

namespace Api\Models\Utils;

use Api\Utils\DatabaseConnector as DB;

class PaysModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère tous les pays.
   * 
   * @return array Tous les pays récupérés.
   */
  public function readAll()
  {
    $statement =
      "SELECT *
        FROM utils_pays
        ORDER BY nom";

    $donnees = $this->db->query($statement)->fetchAll();

    return $donnees;
  }

  /**
   * Récupère un pays.
   * 
   * @param string $iso Code ISO du pays à récupérer
   * 
   * @return array Pays récupéré
   */
  public function read($iso)
  {
    $statement =
      "SELECT *
        FROM utils_pays
        WHERE iso = :iso";

    $requete = $this->db->prepare($statement);
    $requete->execute(["iso" => $iso]);
    $pays = $requete->fetch();


    $donnees = $pays;

    return $donnees;
  }

  /**
   * Crée un pays.
   * 
   * @param array $input Eléments du pays à créer
   * 
   * @return array Pays créé
   */
  public function create(array $input)
  {
    $statement =
      "INSERT INTO utils_pays
        VALUES(
          :iso,
          :nom
        )";

    $requete = $this->db->prepare($statement);

    $this->db->beginTransaction();
    $requete->execute([
      'iso' => $input["iso"],
      'nom' => $input["nom"]
    ]);

    $last_id = $this->db->lastInsertId();
    $this->db->commit();

    return $this->read($last_id);
  }

  /**
   * Met à jour un pays.
   * 
   * @param string $iso    Code ISO du pays à modifier
   * @param array  $input  Eléments du paus à modifier
   * 
   * @return array Pays modifié
   */
  public function update($iso, array $input)
  {
    $statement =
      "UPDATE utils_pays
        SET nom = :nom
        WHERE iso = :iso";

    $requete = $this->db->prepare($statement);
    $requete->execute([
      'nom' => $input["nom"],
      'iso' => $iso
    ]);

    return $this->read($iso);
  }

  /**
   * Supprime un pays.
   * 
   * @param string $iso Code ISO du pays à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete($iso)
  {
    $requete = $this->db->prepare("DELETE FROM utils_pays WHERE iso = :iso");
    $succes = $requete->execute(["iso" => $iso]);

    return $succes;
  }
}
