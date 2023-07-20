<?php

namespace Api\Models\Config;

use Api\Utils\BaseModel;

class BandeauInfoModel extends BaseModel
{
  /**
   * Récupère toutes les lignes du bandeau d'infos.
   * 
   * @return array Lignes du bandeau d'infos
   */
  public function readAll(array $filtre): array
  {
    // Filtre
    $module = $filtre['module'] ?? "";
    $pc = $filtre['pc'] ?? "";
    $tv = $filtre['tv'] ?? "";

    $filtre_sql = "";
    $filtre_sql_module = $module === "" ? "" : "module = '$module'";
    $filtre_sql_pc = $pc === "" ? "" : "pc = $pc";
    $filtre_sql_tv = $tv === "" ? "" : "tv = $tv";

    $filtre_sql_array = [];
    foreach ([$filtre_sql_module, $filtre_sql_pc, $filtre_sql_tv] as $filtre_composante) {
      if ($filtre_composante !== "") {
        array_push($filtre_sql_array, $filtre_composante);
      }
    }
    if ($filtre_sql_array !== []) {
      $filtre_sql = "WHERE " . join(" AND ", $filtre_sql_array);
    }

    $statement = "SELECT * FROM bandeau_info $filtre_sql";

    $requete = $this->db->query($statement);
    $infos = $requete->fetchAll();

    // Rétablissement des types INT et bool
    array_walk_recursive($infos, function (&$value, $key) {
      $value = match ($key) {
        "id" => (int) $value,
        "pc", "tv" => (bool) $value,
        default => $value,
      };
    });

    $donnees = $infos;

    return $donnees;
  }

  /**
   * Récupère une ligne de bandeau d'infos bois.
   * 
   * @param int $id ID de la ligne à récupérer
   * 
   * @return array Ligne récupérée
   */
  public function read($id): ?array
  {
    $statement = "SELECT * FROM bandeau_info WHERE id = :id";

    $requete = $this->db->prepare($statement);
    $requete->execute(["id" => $id]);
    $infos = $requete->fetch();

    if (!$infos) return null;

    // Rétablissement des types INT et bool
    array_walk_recursive($infos, function (&$value, $key) {
      $value = match ($key) {
        "id" => (int) $value,
        "pc", "tv" => (bool) $value,
        default => $value,
      };
    });

    $donnees = $infos;

    return $donnees;
  }

  /**
   * Crée un client bois.
   * 
   * @param array $input Eléments du client à créer
   * 
   * @return array Ligne créée
   */
  public function create(array $input): array
  {
    $statement = "INSERT INTO bandeau_info VALUES(
      NULL,
      :module,
      :pc,
      :tv,
      :couleur,
      :message
      )";

    $requete = $this->db->prepare($statement);

    $this->db->beginTransaction();
    $requete->execute([
      'module' => $input["module"],
      'pc' => (int) $input["pc"],
      'tv' => (int) $input["tv"],
      'couleur' => $input["couleur"],
      'message' => substr($input["message"], 0, 255),
    ]);

    $last_id = $this->db->lastInsertId();
    $this->db->commit();

    return $this->read($last_id);
  }

  /**
   * Met à jour une ligne du bandeau d'informations.
   * 
   * @param int   $id     ID de la ligne à modifier
   * @param array $input  Eléments de la ligne à modifier
   * 
   * @return array Ligne modifiée
   */
  public function update($id, array $input): array
  {
    $statement = "UPDATE bandeau_info
      SET
        module = :module,
        pc = :pc,
        tv = :tv,
        couleur = :couleur,
        message = :message
      WHERE id = :id";

    $requete = $this->db->prepare($statement);
    $requete->execute([
      'module' => $input["module"],
      'pc' => (int) $input["pc"],
      'tv' => (int) $input["tv"],
      'couleur' => $input["couleur"],
      'message' => substr($input["message"], 0, 255),
      'id' => $id
    ]);

    return $this->read($id);
  }

  /**
   * Supprime une ligne du bandeau d'informations.
   * 
   * @param int $id ID de la ligne à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(int $id): bool
  {
    $requete = $this->db->prepare("DELETE FROM bandeau_info WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
