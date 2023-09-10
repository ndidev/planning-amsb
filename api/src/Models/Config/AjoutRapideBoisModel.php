<?php

namespace Api\Models\Config;

use Api\Utils\BaseModel;

class AjoutRapideBoisModel extends BaseModel
{
  /**
   * Récupère tous les ajouts rapides bois.
   */
  public function readAll(): array
  {

    $statement = "SELECT * FROM config_ajouts_rapides_bois";

    $ajouts_rapides = $this->mysql->query($statement)->fetchAll();

    array_walk_recursive($ajouts_rapides, function (&$value, $key) {
      $value = match ($key) {
        "id",
        "client",
        "chargement",
        "livraison",
        "affreteur",
        "fournisseur",
        "transporteur" => $value === NULL ? NULL : (int) $value,
        default => $value,
      };
    });

    $donnees = $ajouts_rapides;

    return $donnees;
  }

  /**
   * Récupère un ajout rapide bois.
   * 
   * @param int $id ID de l'ajout rapide à récupérer
   * 
   * @return array Rendez-vous rapide récupéré
   */
  public function read($id): ?array
  {
    $statement =
      "SELECT *
        FROM config_ajouts_rapides_bois
        WHERE id = :id";

    $requete = $this->mysql->prepare($statement);
    $requete->execute(["id" => $id]);

    $ligne = $requete->fetch();

    if (!$ligne) return null;

    array_walk_recursive($ligne, function (&$value, $key) {
      $value = match ($key) {
        "id",
        "client",
        "chargement",
        "livraison",
        "affreteur",
        "fournisseur",
        "transporteur" => $value === NULL ? NULL : (int) $value,
        default => $value,
      };
    });

    $donnees = $ligne;

    return $donnees;
  }

  /**
   * Crée un ajout rapide bois.
   * 
   * @param array $input Eléments de l'ajout rapide à créer
   * 
   * @return array Rendez-vous rapide créé
   */
  public function create(array $input): array
  {
    $statement =
      "INSERT INTO config_ajouts_rapides_bois VALUES(
        NULL,
        :module,
        :fournisseur,
        :transporteur,
        :affreteur,
        :chargement,
        :client,
        :livraison
      )";

    $requete = $this->mysql->prepare($statement);

    $this->mysql->beginTransaction();
    $requete->execute([
      'module' => "bois",
      'fournisseur' => $input["fournisseur"],
      'transporteur' => $input["transporteur"] ?: NULL,
      'affreteur' => $input["affreteur"] ?: NULL,
      'chargement' => $input["chargement"],
      'client' => $input["client"],
      'livraison' => $input["livraison"] ?: NULL,
    ]);

    $last_id = $this->mysql->lastInsertId();
    $this->mysql->commit();

    return $this->read($last_id);
  }

  /**
   * Met à jour un ajout rapide bois.
   * 
   * @param int   $id     ID de l'ajout rapide à modifier
   * @param array $input  Eléments de l'ajout rapide à modifier
   * 
   * @return array Ajout rapide modifié
   */
  public function update($id, array $input): array
  {
    $statement =
      "UPDATE config_ajouts_rapides_bois
        SET
          fournisseur = :fournisseur,
          transporteur = :transporteur,
          affreteur = :affreteur,
          chargement = :chargement,
          client = :client,
          livraison = :livraison
        WHERE id = :id";

    $requete = $this->mysql->prepare($statement);
    $requete->execute([
      'fournisseur' => $input["fournisseur"],
      'transporteur' => $input["transporteur"] ?: NULL,
      'affreteur' => $input["affreteur"] ?: NULL,
      'chargement' => $input["chargement"],
      'client' => $input["client"],
      'livraison' => $input["livraison"] ?: NULL,
      'id' => $id
    ]);

    return $this->read($id);
  }

  /**
   * Supprime un ajout rapide.
   * 
   * @param int $id ID e l'ajout rapide à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(int $id): bool
  {
    $requete = $this->mysql->prepare("DELETE FROM config_ajouts_rapides_bois WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
