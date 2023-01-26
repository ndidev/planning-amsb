<?php

namespace Api\Models\Bois;

use Api\Utils\DatabaseConnector as DB;

class ConfirmationAffretementModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère un état de confirmation d'affrètement RDV bois.
   * 
   * @param int $id ID du RDV à récupérer
   * 
   * @return array Rendez-vous récupéré
   */
  public function read($id)
  {
    $statement =
      "SELECT
        id,
        confirmation_affretement
      FROM bois_planning
      WHERE id = :id";

    $requete = $this->db->prepare($statement);
    $requete->execute(["id" => $id]);
    $rdv = $requete->fetch();

    if (!$rdv) {
      return;
    }

    // Rétablissement des types INT
    array_walk_recursive($rdv, function (&$value, $key) {
      $value = match ($key) {
        "id", "confirmation_affretement" => (int) $value,
        default => $value,
      };
    });

    $donnees = $rdv;

    return $donnees;
  }


  /**
   * Met à jour un état de confirmation d'affrètement RDV bois.
   * 
   * @param int   $id     ID du RDV à modifier
   * @param array $input  Eléments du RDV à modifier
   * 
   * @return array RDV modifié
   */
  public function update($id, array $input)
  {
    $statement = "UPDATE bois_planning
      SET
        confirmation_affretement = :confirmation_affretement
      WHERE id = :id";

    $requete = $this->db->prepare($statement);
    $requete->execute([
      'confirmation_affretement' => $input["envoye"],
      'id' => $id,
    ]);

    return $this->read($id);
  }
}
