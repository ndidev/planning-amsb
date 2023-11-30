<?php

namespace Api\Models\Bois;

use Api\Utils\BaseModel;
use Api\Utils\Exceptions\ClientException;

class RdvModel extends BaseModel
{
  /**
   * Vérifie si une entrée existe dans la base de données.
   * 
   * @param int $id Identifiant de l'entrée.
   */
  public function exists(int $id)
  {
    return $this->mysql->exists("tiers", $id);
  }

  /**
   * Récupère tous les RDV bois.
   * 
   * @param array $filtre Filtre qui contient...
   */
  public function readAll(array $query): array
  {
    // Filtre
    $date_debut = isset($query['date_debut']) ? ($query['date_debut'] ?: date("Y-m-d")) : date("Y-m-d");
    $date_fin = isset($query['date_fin']) ? ($query['date_fin'] ?: "9999-12-31") : "9999-12-31";
    $filtre_fournisseur = preg_replace("/,$/", "", $query['fournisseur'] ?? "");
    $filtre_client = preg_replace("/,$/", "", $query['client'] ?? "");
    $filtre_chargement = preg_replace("/,$/", "", $query['chargement'] ?? "");
    $filtre_livraison = preg_replace("/,$/", "", $query['livraison'] ?? "");
    $filtre_transporteur = preg_replace("/,$/", "", $query['transporteur'] ?? "");
    $filtre_affreteur = preg_replace("/,$/", "", $query['affreteur'] ?? "");

    $filtre_sql_fournisseur = $filtre_fournisseur === "" ? "" : " AND fournisseur IN ($filtre_fournisseur)";
    $filtre_sql_client = $filtre_client === "" ? "" : " AND client IN ($filtre_client)";
    $filtre_sql_chargement = $filtre_chargement === "" ? "" : " AND chargement IN ($filtre_chargement)";
    $filtre_sql_livraison = $filtre_livraison === "" ? "" : " AND livraison IN ($filtre_livraison)";
    $filtre_sql_transporteur = $filtre_transporteur === "" ? "" : " AND transporteur IN ($filtre_transporteur)";
    $filtre_sql_affreteur = $filtre_affreteur === "" ? "" : " AND affreteur IN ($filtre_affreteur)";

    $filtre_sql =
      $filtre_sql_fournisseur
      . $filtre_sql_client
      . $filtre_sql_chargement
      . $filtre_sql_livraison
      . $filtre_sql_transporteur
      . $filtre_sql_affreteur;

    $statement =
      "SELECT
          id,
          attente,
          date_rdv,
          heure_arrivee,
          heure_depart,
          confirmation_affretement,
          commande_prete,
          numero_bl,
          commentaire_public,
          commentaire_cache,
          client,
          chargement,
          livraison,
          affreteur,
          fournisseur,
          transporteur
        FROM bois_planning
        WHERE 
          (date_rdv BETWEEN :date_debut AND :date_fin OR date_rdv IS NULL OR attente = 1)
        $filtre_sql
        ORDER BY date_rdv";

    $requete = $this->mysql->prepare($statement);

    $requete->execute([
      "date_debut" => $date_debut,
      "date_fin" => $date_fin
    ]);

    $rdvs = $requete->fetchAll();

    // Rétablissement des types bool
    array_walk_recursive($rdvs, function (&$value, $key) {
      $value = match ($key) {
        "attente",
        "confirmation_affretement",
        "commande_prete",
        "lie_agence" => (bool) $value,
        default => $value,
      };
    });

    $donnees = $rdvs;

    return $donnees;
  }

  /**
   * Récupère un RDV bois.
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
        attente,
        date_rdv,
        heure_arrivee,
        heure_depart,
        confirmation_affretement,
        commande_prete,
        numero_bl,
        commentaire_public,
        commentaire_cache,
        client,
        chargement,
        livraison,
        affreteur,
        fournisseur,
        transporteur
      FROM bois_planning
      WHERE id = :id";

    $requete = $this->mysql->prepare($statement);
    $requete->execute(["id" => $id]);
    $rdv = $requete->fetch();

    if (!$rdv) return null;

    // Rétablissement des types bool
    array_walk_recursive($rdv, function (&$value, $key) {
      $value = match ($key) {
        "attente",
        "confirmation_affretement",
        "commande_prete",
        "lie_agence" => (bool) $value,
        default => $value,
      };
    });

    $donnees = $rdv;

    return $donnees;
  }

  /**
   * Crée un RDV bois.
   * 
   * @param array $input Eléments du RDV à créer
   * 
   * @return array Rendez-vous créé
   */
  public function create(array $input): array
  {
    $statement = "INSERT INTO bois_planning VALUES(
      NULL,
      :attente,
      :date_rdv,
      :heure_arrivee,
      :heure_depart,
      :chargement,
      :client,
      :livraison,
      :transporteur,
      :affreteur,
      :fournisseur,
      :commande_prete,
      :confirmation_affretement,
      :numero_bl,
      :commentaire_public,
      :commentaire_cache
      )";

    $requete = $this->mysql->prepare($statement);

    $this->mysql->beginTransaction();
    $requete->execute([
      'attente' => (int) $input["attente"],
      'date_rdv' => $input["date_rdv"] ?: NULL,
      'heure_arrivee' => $input["heure_arrivee"] ?: NULL,
      'heure_depart' => $input["heure_depart"] ?: NULL,
      'chargement' => $input["chargement"],
      'client' => $input["client"],
      'livraison' => $input["livraison"] ?: NULL,
      'transporteur' => $input["transporteur"] ?: NULL,
      'affreteur' => $input["affreteur"] ?: NULL,
      'fournisseur' => $input["fournisseur"],
      'commande_prete' => (int) $input["commande_prete"],
      'confirmation_affretement' => (int) $input["confirmation_affretement"],
      'numero_bl' => $input["numero_bl"],
      'commentaire_public' => $input["commentaire_public"],
      'commentaire_cache' => $input["commentaire_cache"],
    ]);

    $last_id = $this->mysql->lastInsertId();
    $this->mysql->commit();

    return $this->read($last_id);
  }

  /**
   * Met à jour un RDV bois.
   * 
   * @param int   $id ID du RDV à modifier
   * @param array $input  Eléments du RDV à modifier
   * 
   * @return array RDV modifié
   */
  public function update(int $id, array $input): array
  {
    $statement = "UPDATE bois_planning
      SET
        attente = :attente,
        date_rdv = :date_rdv,
        heure_arrivee = :heure_arrivee,
        heure_depart = :heure_depart,
        chargement = :chargement,
        client = :client,
        livraison = :livraison,
        transporteur = :transporteur,
        affreteur = :affreteur,
        fournisseur = :fournisseur,
        commande_prete = :commande_prete,
        confirmation_affretement = :confirmation_affretement,
        numero_bl = :numero_bl,
        commentaire_public = :commentaire_public,
        commentaire_cache = :commentaire_cache
      WHERE id = :id";

    $requete = $this->mysql->prepare($statement);
    $requete->execute([
      'attente' => (int) $input["attente"],
      'date_rdv' => $input["date_rdv"] ?: NULL,
      'heure_arrivee' => $input["heure_arrivee"] ?: NULL,
      'heure_depart' => $input["heure_depart"] ?: NULL,
      'client' => $input["client"],
      'chargement' => $input["chargement"],
      'livraison' => $input["livraison"] ?: NULL,
      'transporteur' => $input["transporteur"] ?: NULL,
      'affreteur' => $input["affreteur"] ?: NULL,
      'fournisseur' => $input["fournisseur"],
      'commande_prete' => (int) $input["commande_prete"],
      'confirmation_affretement' => (int) $input["confirmation_affretement"],
      'numero_bl' => $input["numero_bl"],
      'commentaire_public' => $input["commentaire_public"],
      'commentaire_cache' => $input["commentaire_cache"],
      'id' => $id,
    ]);

    return $this->read($id);
  }

  /**
   * Met à jour certaines proriétés d'un RDV bois.
   * 
   * @param int   $id    id du RDV à modifier
   * @param array $input Données à modifier
   * 
   * @return array RDV modifié
   */
  public function patch(int $id, array $input): array
  {
    /**
     * Confirmation affrètement
     */
    if (isset($input["confirmation_affretement"])) {
      $this->mysql
        ->prepare(
          "UPDATE bois_planning
           SET confirmation_affretement = :confirmation_affretement
           WHERE id = :id"
        )
        ->execute([
          'confirmation_affretement' => (int) $input["confirmation_affretement"],
          'id' => $id,
        ]);
    }

    /**
     * Heure d'arrivée (+ numéro BL auto le cas échéant)
     */
    if (isset($input["heure_arrivee"])) {

      // Heure
      $heure = date('H:i:s');
      $this->mysql
        ->prepare("UPDATE bois_planning SET heure_arrivee = :heure WHERE id = :id")
        ->execute([
          'heure' => $heure,
          'id' => $id
        ]);


      // Numéro BL automatique (Stora Enso)      
      $current = $this->read($id);

      if (
        $current["fournisseur"] === 292 /* Stora Enso */
        && $current["chargement"] === 1 /* AMSB */
      ) {
        // Récupération du numéro de BL du RDV à modifier (si déjà renseigné)
        $reponse_bl_actuel = $this->mysql->prepare(
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
        $reponse_bl_precedent = $this->mysql->query(
          "SELECT numero_bl
              FROM bois_planning
              WHERE fournisseur = {$current["fournisseur"]}
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
          $requete = $this->mysql->prepare(
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
    }

    /**
     * Heure de départ
     */
    if (isset($input["heure_depart"])) {
      $heure = date('H:i:s');
      $this->mysql
        ->prepare("UPDATE bois_planning SET heure_depart = :heure WHERE id = :id")
        ->execute([
          'heure' => $heure,
          'id' => $id
        ]);
    }

    /**
     * Numéro de BL
     */
    if (isset($input["numero_bl"])) {
      $numero_bl = $input['numero_bl'];
      $dry_run = $input["dry_run"] ?? FALSE;

      $bl_existe = FALSE;

      // Fournisseurs dont le numéro de BL doit être unique
      $fournisseurs_bl_unique = [
        292 // Stora Enso
      ];

      $current = $this->mysql
        ->query(
          "SELECT p.fournisseur, f.nom_court AS fournisseur_nom
           FROM bois_planning p
           JOIN tiers f ON f.id = p.fournisseur
           WHERE p.id = {$id}"
        )->fetch();

      // Vérification si le numéro de BL existe déjà (pour Enso)
      if (
        array_search($current["fournisseur"], $fournisseurs_bl_unique) !== FALSE
        && $numero_bl !== ""
        && $numero_bl !== "-"
      ) {
        $requete = $this->mysql->prepare(
          "SELECT COUNT(id) AS bl_existe, id
            FROM bois_planning
            WHERE numero_bl LIKE CONCAT('%', :numero_bl, '%')
            AND fournisseur = :fournisseur
            AND NOT id = :id"
        );
        $requete->execute([
          "numero_bl" => $numero_bl,
          "fournisseur" => $current["fournisseur"],
          "id" => $id
        ]);

        $reponse_bdd = $requete->fetch();

        $bl_existe = (bool) $reponse_bdd["bl_existe"];
      }

      if (!$bl_existe && !$dry_run) {
        $this->mysql
          ->prepare(
            "UPDATE bois_planning
              SET
                numero_bl = :numero_bl
              WHERE id = :id"
          )
          ->execute([
            'numero_bl' => $numero_bl,
            'id' => $id
          ]);
      }

      // Si le numéro de BL existe déjà (pour Enso), message d'erreur
      if ($bl_existe && $id != $reponse_bdd["id"]) {
        throw new ClientException("Le numéro de BL $numero_bl existe déjà pour {$current["fournisseur_nom"]}.");
      }
    }

    /**
     * Commande prête
     */
    if (isset($input["commande_prete"])) {
      $this->mysql
        ->prepare(
          "UPDATE bois_planning
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
   * Supprime un RDV bois.
   * 
   * @param int $id ID du RDV à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(int $id): bool
  {
    $requete = $this->mysql->prepare("DELETE FROM bois_planning WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
