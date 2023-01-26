<?php

namespace Api\Models\Bois;

use Api\Utils\DatabaseConnector as DB;

class NumeroBLModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère un numéro BL bois.
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
        numero_bl
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
        "id" => (int) $value,
        default => $value,
      };
    });

    $donnees = $rdv;

    return $donnees;
  }


  /**
   * Met à jour un numéro BL bois.
   * 
   * @param int   $id     ID du RDV à modifier
   * @param array $input  Eléments du RDV à modifier
   * 
   * @return array RDV modifié
   */
  public function update($id, array $input)
  {
    $numero_bl = $input['numero_bl'];
    $fournisseur = $input["fournisseur"];
    $fournisseur_nom = $input["fournisseur_nom"];
    $dry_run = $input["dry_run"] ?? FALSE;

    $reponse = ["erreur" => 0]; // Réponse par défaut
    $bl_existe = FALSE;

    // Fournisseurs dont le numéro de BL doit être unique
    $fournisseurs_bl_unique = ["Stora Enso"];

    // Vérification si le numéro de BL existe déjà (pour Enso)
    if (
      array_search($fournisseur_nom, $fournisseurs_bl_unique) !== FALSE
      && $numero_bl !== ""
      && $numero_bl !== "-"
    ) {
      $requete = $this->db->prepare(
        "SELECT COUNT(id) AS bl_existe, id
            FROM bois_planning
            WHERE numero_bl LIKE CONCAT('%', :numero_bl, '%')
            AND fournisseur = :fournisseur
            AND NOT id = :id"
      );
      $requete->execute([
        "numero_bl" => $numero_bl,
        "fournisseur" => $fournisseur,
        "id" => $id
      ]);

      $reponse_bdd = $requete->fetch();

      $bl_existe = $reponse_bdd["bl_existe"];
    }

    // Si le numéro de BL existe déjà (pour Enso), message d'erreur
    if ($bl_existe && $id != $reponse_bdd["id"]) {
      $reponse = [
        "erreur" => 1,
        "message" => "Le numéro de BL $numero_bl existe déjà pour $fournisseur_nom."
      ];
    }

    if (!$bl_existe && !$dry_run) {
      $requete = $this->db->prepare(
        "UPDATE bois_planning
            SET
              numero_bl = :numero_bl
            WHERE id = :id"
      );

      $ok = $requete->execute([
        'numero_bl' => $numero_bl,
        'id' => $id
      ]);

      if ($ok) {
        $reponse = [
          "erreur" => 0,
          "message" => "Numéro de BL mis à jour"
        ];
      }
    }

    return $reponse;
  }
}
