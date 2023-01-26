<?php

namespace Api\Models\Config;

use Api\Utils\DatabaseConnector as DB;

class RdvRapidesBoisModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère tous les RDV rapides bois.
   */
  public function readAll()
  {

    $statement = "SELECT * FROM config_rdv_rapides_bois";

    $rdv_rapides = $this->db->query($statement)->fetchAll();

    array_walk_recursive($rdv_rapides, function (&$value, $key) {
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

    $donnees = $rdv_rapides;

    return $donnees;
  }

  /**
   * Récupère un RDV rapide bois.
   * 
   * @param int $id ID du RDV rapide à récupérer
   * 
   * @return array Rendez-vous rapide récupéré
   */
  public function read($id)
  {
    $statement =
      "SELECT *
        FROM config_rdv_rapides_bois
        WHERE id = :id";

    $requete = $this->db->prepare($statement);
    $requete->execute(["id" => $id]);

    $ligne = $requete->fetch();

    if ($ligne) {
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
    }

    $donnees = $ligne;

    return $donnees;
  }

  /**
   * Crée un RDV rapide bois.
   * 
   * @param array $input Eléments du RDV rapide à créer
   * 
   * @return array Rendez-vous rapide créé
   */
  public function create(array $input)
  {
    $statement =
      "INSERT INTO config_rdv_rapides_bois VALUES(
        NULL,
        :module,
        :fournisseur,
        :transporteur,
        :affreteur,
        :chargement,
        :client,
        :livraison
      )";

    $requete = $this->db->prepare($statement);

    $this->db->beginTransaction();
    $requete->execute([
      'module' => "bois",
      'fournisseur' => $input["fournisseur"],
      'transporteur' => $input["transporteur"] ?: NULL,
      'affreteur' => $input["affreteur"] ?: NULL,
      'chargement' => $input["chargement"],
      'client' => $input["client"],
      'livraison' => $input["livraison"] ?: NULL,
    ]);

    $last_id = $this->db->lastInsertId();
    $this->db->commit();

    return $this->read($last_id);
  }

  /**
   * Met à jour un RDV rapide bois.
   * 
   * @param int   $id ID du RDV à modifier
   * @param array $input  Eléments du RDV à modifier
   * 
   * @return array RDV modifié
   */
  public function update($id, array $input)
  {
    $statement =
      "UPDATE config_rdv_rapides_bois
        SET
          fournisseur = :fournisseur,
          transporteur = :transporteur,
          affreteur = :affreteur,
          chargement = :chargement,
          client = :client,
          livraison = :livraison
        WHERE id = :id";

    $requete = $this->db->prepare($statement);
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
   * Supprime un RDV rapide.
   * 
   * @param int $id ID du RDV rapide à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(int $id)
  {
    $requete = $this->db->prepare("DELETE FROM config_rdv_rapides_bois WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
