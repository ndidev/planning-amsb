<?php

namespace App\Models\Utils;

use App\Models\Model;

class PaysModel extends Model
{
  private $redis_ns = "pays";

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

      $pays = $this->mysql->query($statement)->fetchAll();

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

    $requete = $this->mysql->prepare($statement);
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

    $requete = $this->mysql->prepare($statement);

    $this->mysql->beginTransaction();
    $requete->execute([
      'iso' => $input["iso"],
      'nom' => $input["nom"]
    ]);

    $last_id = $this->mysql->lastInsertId();
    $this->mysql->commit();

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

    $requete = $this->mysql->prepare($statement);
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
    $requete = $this->mysql->prepare("DELETE FROM utils_pays WHERE iso = :iso");
    $succes = $requete->execute(["iso" => $iso]);

    $this->redis->del($this->redis_ns);

    return $succes;
  }
}
