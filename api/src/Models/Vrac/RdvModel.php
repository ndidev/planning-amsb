<?php

namespace Api\Models\Vrac;

use Api\Utils\BaseModel;

class RdvModel extends BaseModel
{
  /**
   * Récupère tous les RDV vrac.
   * 
   * @return array Tous les RDV récupérés
   */
  public function readAll($query = null): array
  {
    $statement =
      "SELECT
          id,
          date_rdv,
          SUBSTRING(heure, 1, 5) AS heure,
          produit,
          qualite,
          quantite,
          max,
          commande_prete,
          fournisseur,
          client,
          transporteur,
          num_commande,
          commentaire
        FROM vrac_planning
        ORDER BY date_rdv";


    $requete = $this->mysql->query($statement);
    $rdvs = $requete->fetchAll();

    // Rétablissement des types bool
    array_walk_recursive($rdvs, function (&$value, $key) {
      $value = match ($key) {
        "max", "commande_prete" => $value = (bool) $value,
        default => $value,
      };
    });

    $donnees = $rdvs;

    return $donnees;
  }

  /**
   * Récupère un RDV vrac.
   * 
   * @param int $id ID du RDV à récupérer
   * 
   * @return array Rendez-vous récupéré
   */
  public function read($id): ?array
  {
    $statement =
      "SELECT
            id,
            date_rdv,
            SUBSTRING(heure, 1, 5) AS heure,
            produit,
            qualite,
            quantite,
            max,
            commande_prete,
            fournisseur,
            client,
            transporteur,
            num_commande,
            commentaire
          FROM vrac_planning
          WHERE id = :id";

    $requete = $this->mysql->prepare($statement);
    $requete->execute(["id" => $id]);
    $rdv = $requete->fetch();

    if (!$rdv) return null;

    // Rétablissement des types bool
    array_walk_recursive($rdv, function (&$value, $key) {
      $value = match ($key) {
        "max", "commande_prete" => $value = (bool) $value,
        default => $value,
      };
    });

    $donnees = $rdv;

    return $donnees;
  }

  /**
   * Crée un RDV vrac.
   * 
   * @param array $input Eléments du RDV à créer
   * 
   * @return array Rendez-vous créé
   */
  public function create(array $input): array
  {
    $statement = "INSERT INTO vrac_planning
    VALUES(
      NULL,
      :date_rdv,
      :heure,
      :produit,
      :qualite,
      :quantite,
      :max,
      :commande_prete,
      :fournisseur,
      :client,
      :transporteur,
      :num_commande,
      :commentaire
    )";

    $requete = $this->mysql->prepare($statement);

    $this->mysql->beginTransaction();
    $requete->execute([
      'date_rdv' => $input["date_rdv"],
      'heure' => $input["heure"] ?: NULL,
      'produit' => $input["produit"],
      'qualite' => $input["qualite"] ?? NULL,
      'quantite' => $input["quantite"],
      'max' => (int) $input["max"],
      'commande_prete' => (int) $input["commande_prete"],
      'fournisseur' => $input["fournisseur"],
      'client' => $input["client"],
      'transporteur' => $input["transporteur"] ?: NULL,
      'num_commande' => $input["num_commande"],
      'commentaire' => $input["commentaire"]
    ]);

    $last_id = $this->mysql->lastInsertId();
    $this->mysql->commit();

    return $this->read($last_id);
  }

  /**
   * Met à jour un RDV vrac.
   * 
   * @param int   $id ID du RDV à modifier
   * @param array $input  Eléments du RDV à modifier
   * 
   * @return array RDV modifié
   */
  public function update($id, array $input): array
  {
    $statement = "UPDATE vrac_planning
      SET
        date_rdv = :date_rdv,
        heure = :heure,
        produit = :produit,
        qualite = :qualite,
        quantite = :quantite,
        max = :max,
        commande_prete = :commande_prete,
				fournisseur = :fournisseur,
        client = :client,
        transporteur = :transporteur,
        num_commande = :num_commande,
        commentaire = :commentaire
      WHERE id = :id";

    $requete = $this->mysql->prepare($statement);
    $requete->execute([
      'date_rdv' => $input["date_rdv"],
      'heure' => $input["heure"] ?: NULL,
      'produit' => $input["produit"],
      'qualite' => $input["qualite"] ?? NULL,
      'quantite' => $input["quantite"],
      'max' => (int) $input["max"],
      'commande_prete' => (int) $input["commande_prete"],
      'fournisseur' => $input["fournisseur"],
      'client' => $input["client"],
      'transporteur' => $input["transporteur"] ?: NULL,
      'num_commande' => $input["num_commande"],
      'commentaire' => $input["commentaire"],
      'id' => $id
    ]);

    return $this->read($id);
  }

  /**
   * Met à jour certaines proriétés d'un RDV vrac.
   * 
   * @param int   $id    id du RDV à modifier
   * @param array $input Données à modifier
   * 
   * @return array RDV modifié
   */
  public function patch(int $id, array $input): array
  {
    /**
     * Commande prête
     */
    if (isset($input["commande_prete"])) {
      $this->mysql
        ->prepare(
          "UPDATE vrac_planning
           SET commande_prete = :commande_prete
           WHERE id = :id"
        )
        ->execute([
          'commande_prete' => (int) $input["commande_prete"],
          'id' => $id,
        ]);
    }

    return $this->read($id);
  }

  /**
   * Supprime un RDV vrac.
   * 
   * @param int $id ID du RDV à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(int $id): bool
  {
    $requete = $this->mysql->prepare("DELETE FROM vrac_planning WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
