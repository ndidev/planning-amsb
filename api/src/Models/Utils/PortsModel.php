<?php

namespace App\Models\Utils;

use App\Models\Model;

class PortsModel extends Model
{
  private $redis_ns = "ports";

  /**
   * Récupère tous les ports.
   * 
   * @return array Tous les ports récupérés.
   */
  public function readAll(): array
  {
    // Redis
    $ports = json_decode($this->redis->get($this->redis_ns));

    if (!$ports) {
      $statement = "SELECT * FROM utils_ports ORDER BY SUBSTRING(locode, 1, 2), nom";

      $ports = $this->mysql->query($statement)->fetchAll();

      $this->redis->set($this->redis_ns, json_encode($ports));
    }

    $donnees = $ports;

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

    $requete = $this->mysql->prepare($statement);
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

    $requete = $this->mysql->prepare($statement);

    $this->mysql->beginTransaction();
    $requete->execute([
      'locode' => $input["locode"],
      'nom' => $input["nom"]
    ]);

    $last_id = $this->mysql->lastInsertId();
    $this->mysql->commit();

    $this->redis->del($this->redis_ns);

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

    $requete = $this->mysql->prepare($statement);
    $requete->execute([
      'nom' => $input["nom"],
      'locode' => $locode
    ]);

    $this->redis->del($this->redis_ns);

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
    $requete = $this->mysql->prepare("DELETE FROM utils_ports WHERE locode = :locode");
    $succes = $requete->execute(["locode" => $locode]);

    $this->redis->del($this->redis_ns);

    return $succes;
  }
}
