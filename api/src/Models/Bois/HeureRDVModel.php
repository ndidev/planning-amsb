<?php

namespace Api\Models\Bois;

use Api\Utils\DatabaseConnector as DB;

class HeureRDVModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère une heure de RDV bois.
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
        heure_arrivee,
        heure_depart
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
   * Met à jour une heure de RDV bois.
   * 
   * @param int   $id     ID du RDV à modifier
   * @param array $input  Eléments du RDV à modifier
   * 
   * @return array RDV modifié
   */
  public function update($id, array $input)
  {
    $heure = date('H:i');
    $numero_bl_nouveau = '';

    // Heure
    if ($input["horloge"] === "arrivee") {
      // Numéro BL automatique (Stora Enso)
      $fournisseur_id = $input["fournisseur_id"];
      $fournisseur_nom = $input["fournisseur_nom"];
      if ($fournisseur_nom === "Stora Enso") {
        // Récupération du numéro de BL du RDV à modifier (si déjà renseigné)
        $reponse_bl_actuel = $this->db->prepare(
          "SELECT numero_bl
              FROM bois_planning
              WHERE id = :id"
        );
        $reponse_bl_actuel->execute(["id" => $id]);
        $reponse_bl_actuel = $reponse_bl_actuel->fetch();
        $numero_bl_actuel = $reponse_bl_actuel["numero_bl"];

        // Dernier numéro de BL de Stora Enso :
        // - enregistrement des 10 derniers numéros dans un tableau
        // - tri du tableau
        // - récupération du numéro le plus élevé
        // Ceci permet de prendre en compte les cas où le dernier numéro
        // renseigné n'est pas le plus haut numériquement
        // Permet aussi de prendre en compte les éventuels bons sans numéro "numérique"
        $reponse_bl_precedent = $this->db->query(
          "SELECT numero_bl
              FROM bois_planning
              WHERE fournisseur = $fournisseur_id
              AND numero_bl != ''
              ORDER BY
                date_rdv DESC,
                heure_arrivee DESC,
                numero_bl DESC
              LIMIT 10"
        )->fetchAll();

        $numeros_bl_precedents = [];

        foreach ($reponse_bl_precedent as $numero_bl) {
          // Si le dernier numéro de BL est composé (ex: "200101 + 200102")
          // alors séparation/tri de la chaîne de caractères puis récupération du numéro le plus élevé
          $matches = NULL; // Tableau pour récupérer les numéros de BL
          preg_match_all("/\d{6}/", $numero_bl["numero_bl"], $matches); // Filtre sur les numéros valides (6 chiffres)
          $matches = $matches[0]; // Extraction des résultats
          sort($matches); // Tri des numéros
          $numeros_bl_precedents[] = array_pop($matches); // Récupération du numéro le plus élevé
        }

        // Tri des 10 derniers numéros de BL puis récupération du plus élevé
        sort($numeros_bl_precedents);
        $numero_bl_precedent = array_pop($numeros_bl_precedents);

        // Calcul du nouveau numéro de BL (si possible)
        // Insertion du nouveau numéro de BL si numéro non déjà renseigné
        $numero_bl_nouveau = is_numeric($numero_bl_precedent) ? $numero_bl_precedent + 1 : '';
        if ($numero_bl_actuel === '' && $numero_bl_nouveau) {
          $requete = $this->db->prepare(
            "UPDATE bois_planning
                SET numero_bl = :numero_bl
                WHERE id = :id"
          );

          $requete->execute([
            'numero_bl' => $numero_bl_nouveau,
            'id' => $id
          ]);
        }
      }

      // Insertion de l'heure
      $requete = $this->db->prepare(
        "UPDATE bois_planning
            SET heure_arrivee = :heure
            WHERE id = :id"
      );

      $requete->execute([
        'heure' => $heure,
        'id' => $id
      ]);
    }

    if ($input['horloge'] == 'depart') {
      $requete = $this->db->prepare(
        "UPDATE bois_planning
            SET heure_depart = :heure
            WHERE id = :id"
      );

      $requete->execute([
        'heure' => $heure,
        'id' => $id
      ]);
    }


    $reponse = [
      'heure' => $heure,
      'numero_bl' => $numero_bl_nouveau
    ];

    return $reponse;
  }
}
