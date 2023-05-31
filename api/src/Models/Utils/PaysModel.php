<?php

namespace Api\Models\Utils;

use Api\Utils\BaseModel;

class PaysModel extends BaseModel
{
  private $redis_ns = "pays";

  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Récupère tous les pays.
   * 
   * @return array Tous les pays récupérés.
   */
  public function readAll(): array
  {
    // Redis
    $pays = json_decode($this->redis->get($this->redis_ns));

    if (!$pays) {
      $statement =
        "SELECT *
          FROM utils_pays
          ORDER BY nom";

      $pays = $this->db->query($statement)->fetchAll();

      $this->redis->set($this->redis_ns, json_encode($pays));
    }

    $donnees = $pays;

    return $donnees;
  }

  /**
   * Récupère un pays.
   * 
   * @param string $iso Code ISO du pays à récupérer
   * 
   * @return array Pays récupéré
   */
  public function read($iso): ?array
  {
    $statement =
      "SELECT *
        FROM utils_pays
        WHERE iso = :iso";

    $requete = $this->db->prepare($statement);
    $requete->execute(["iso" => $iso]);
    $pays = $requete->fetch();

    if (!$pays) return null;


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
  public function create(array $input): array
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

    $this->redis->del($this->redis_ns);

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
  public function update($iso, array $input): array
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

    $this->redis->del($this->redis_ns);

    return $this->read($iso);
  }

  /**
   * Supprime un pays.
   * 
   * @param string $iso Code ISO du pays à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete($iso): bool
  {
    $requete = $this->db->prepare("DELETE FROM utils_pays WHERE iso = :iso");
    $succes = $requete->execute(["iso" => $iso]);

    $this->redis->del($this->redis_ns);

    return $succes;
  }
}
