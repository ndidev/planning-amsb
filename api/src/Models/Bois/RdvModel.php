<?php

namespace Api\Models\Bois;

use Api\Utils\DatabaseConnector as DB;

class RdvModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère tous les RDV bois.
   * 
   * @param array $filtre Filtre qui contient...
   */
  public function readAll(array $query)
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

    // TODO : simplifier la requête car les informations de chaque tiers sont déjà récupérées via /tiers

    $statement_non_attente =
      "SELECT
          p.id,
          p.attente,
          p.date_rdv,
          SUBSTRING(p.heure_arrivee, 1, 5) AS heure_arrivee,
          SUBSTRING(p.heure_depart, 1, 5) AS heure_depart,
          p.confirmation_affretement,
          p.numero_bl,
          p.commentaire_public,
          p.commentaire_cache,
          c.id AS client_id,
          c.nom_court AS client_nom_court,
          c.nom_complet AS client_nom_complet,
          c.adresse_ligne_1 AS client_adresse_ligne_1,
          c.adresse_ligne_2 AS client_adresse_ligne_2,
          c.cp AS client_cp,
          c.ville AS client_ville,
          c.pays AS client_pays_iso,
          cpays.nom AS client_pays,
          c.telephone AS client_telephone,
          c.commentaire AS client_commentaire,
          ch.id AS chargement_id,
          ch.nom_court AS chargement_nom_court,
          ch.nom_complet AS chargement_nom_complet,
          ch.adresse_ligne_1 AS chargement_adresse_ligne_1,
          ch.adresse_ligne_2 AS chargement_adresse_ligne_2,
          ch.cp AS chargement_cp,
          ch.ville AS chargement_ville,
          ch.pays AS chargement_pays_iso,
          chpays.nom AS chargement_pays,
          ch.telephone AS chargement_telephone,
          ch.commentaire AS chargement_commentaire,
          l.id AS livraison_id,
          l.nom_court AS livraison_nom_court,
          l.nom_complet AS livraison_nom_complet,
          l.adresse_ligne_1 AS livraison_adresse_ligne_1,
          l.adresse_ligne_2 AS livraison_adresse_ligne_2,
          l.cp AS livraison_cp,
          l.ville AS livraison_ville,
          l.pays AS livraison_pays_iso,
          lpays.nom AS livraison_pays,
          l.telephone AS livraison_telephone,
          l.commentaire AS livraison_commentaire,
          a.id AS affreteur_id,
          a.nom_court AS affreteur_nom,
          a.lie_agence AS affreteur_lie_agence,
          f.id AS fournisseur_id,
          f.nom_court AS fournisseur_nom,
          t.id AS transporteur_id,
          t.nom_court AS transporteur_nom,
          t.telephone AS transporteur_telephone
        FROM bois_planning p
        LEFT JOIN tiers AS c ON p.client = c.id
        LEFT JOIN tiers AS ch ON p.chargement = ch.id
        LEFT JOIN tiers AS l ON p.livraison = l.id
        LEFT JOIN tiers AS a ON p.affreteur = a.id
        LEFT JOIN tiers AS f ON p.fournisseur = f.id
        LEFT JOIN tiers AS t ON p.transporteur = t.id
        LEFT JOIN utils_pays chpays ON ch.pays = chpays.iso
        LEFT JOIN utils_pays cpays ON c.pays = cpays.iso
        LEFT JOIN utils_pays lpays ON l.pays = lpays.iso
        WHERE date_rdv
        BETWEEN :date_debut
        AND :date_fin
        AND attente = 0
        $filtre_sql
        ORDER BY
          date_rdv,
          -heure_arrivee DESC,
          numero_bl,
          client_nom_court";

    $statement_attente =
      "SELECT
          p.id,
          p.attente,
          p.date_rdv,
          SUBSTRING(p.heure_arrivee, 1, 5) AS heure_arrivee,
          SUBSTRING(p.heure_depart, 1, 5) AS heure_depart,
          p.confirmation_affretement,
          p.numero_bl,
          p.commentaire_public,
          p.commentaire_cache,
          c.id AS client_id,
          c.nom_court AS client_nom_court,
          c.nom_complet AS client_nom_complet,
          c.adresse_ligne_1 AS client_adresse_ligne_1,
          c.adresse_ligne_2 AS client_adresse_ligne_2,
          c.cp AS client_cp,
          c.ville AS client_ville,
          c.pays AS client_pays_iso,
          cpays.nom AS client_pays,
          c.telephone AS client_telephone,
          c.commentaire AS client_commentaire,
          ch.id AS chargement_id,
          ch.nom_court AS chargement_nom_court,
          ch.nom_complet AS chargement_nom_complet,
          ch.adresse_ligne_1 AS chargement_adresse_ligne_1,
          ch.adresse_ligne_2 AS chargement_adresse_ligne_2,
          ch.cp AS chargement_cp,
          ch.ville AS chargement_ville,
          ch.pays AS chargement_pays_iso,
          chpays.nom AS chargement_pays,
          ch.telephone AS chargement_telephone,
          ch.commentaire AS chargement_commentaire,
          l.id AS livraison_id,
          l.nom_court AS livraison_nom_court,
          l.nom_complet AS livraison_nom_complet,
          l.adresse_ligne_1 AS livraison_adresse_ligne_1,
          l.adresse_ligne_2 AS livraison_adresse_ligne_2,
          l.cp AS livraison_cp,
          l.ville AS livraison_ville,
          l.pays AS livraison_pays_iso,
          lpays.nom AS livraison_pays,
          l.telephone AS livraison_telephone,
          l.commentaire AS livraison_commentaire,
          a.id AS affreteur_id,
          a.nom_court AS affreteur_nom,
          a.lie_agence AS affreteur_lie_agence,
          f.id AS fournisseur_id,
          f.nom_court AS fournisseur_nom,
          t.id AS transporteur_id,
          t.nom_court AS transporteur_nom,
          t.telephone AS transporteur_telephone
        FROM bois_planning AS p
        LEFT JOIN tiers AS c ON p.client = c.id
        LEFT JOIN tiers AS ch ON p.chargement = ch.id
        LEFT JOIN tiers AS l ON p.livraison = l.id
        LEFT JOIN tiers AS a ON p.affreteur = a.id
        LEFT JOIN tiers AS f ON p.fournisseur = f.id
        LEFT JOIN tiers AS t ON p.transporteur = t.id
        LEFT JOIN utils_pays chpays ON ch.pays = chpays.iso
        LEFT JOIN utils_pays cpays ON c.pays = cpays.iso
        LEFT JOIN utils_pays lpays ON l.pays = lpays.iso
        WHERE attente = 1
        $filtre_sql
        ORDER BY -date_rdv DESC, client_nom_court";

    $statement_compte_non_attente =
      "SELECT
          t.date_rdv,
          t.total,
          a.attendus,
          sp.sur_parc,
          c.charges,
          tf.total_filtre,
          af.attendus_filtre,
          spf.sur_parc_filtre,
          cf.charges_filtre
        FROM (SELECT date_rdv, COUNT(id) AS total
                FROM bois_planning
                WHERE date_rdv
                BETWEEN :date_debut
                AND :date_fin
                AND attente = 0
                GROUP BY date_rdv) AS t
        LEFT JOIN (SELECT date_rdv, COUNT(id) AS attendus
                FROM bois_planning
                WHERE date_rdv
                BETWEEN :date_debut
                AND :date_fin
                AND attente = 0
                AND heure_arrivee IS NULL
                AND heure_depart IS NULL
                GROUP BY date_rdv) AS a
          ON a.date_rdv = t.date_rdv
        LEFT JOIN (SELECT date_rdv, COUNT(id) AS sur_parc
                FROM bois_planning
                WHERE date_rdv
                BETWEEN :date_debut
                AND :date_fin
                AND attente = 0
                AND NOT heure_arrivee IS NULL
                AND heure_depart IS NULL
                GROUP BY date_rdv) AS sp
          ON sp.date_rdv = t.date_rdv
        LEFT JOIN (SELECT date_rdv, COUNT(id) AS charges
                FROM bois_planning
                WHERE date_rdv
                BETWEEN :date_debut
                AND :date_fin
                AND attente = 0
                AND NOT heure_arrivee IS NULL
                AND NOT heure_depart IS NULL
                GROUP BY date_rdv) AS c
          ON c.date_rdv = t.date_rdv
          LEFT JOIN (SELECT date_rdv, COUNT(id) AS total_filtre
                FROM bois_planning
                WHERE date_rdv
                BETWEEN :date_debut
                AND :date_fin
                AND attente = 0
                $filtre_sql
                GROUP BY date_rdv) AS tf
          ON tf.date_rdv = t.date_rdv
          LEFT JOIN (SELECT date_rdv, COUNT(id) AS attendus_filtre
                FROM bois_planning
                WHERE date_rdv
                BETWEEN :date_debut
                AND :date_fin
                AND attente = 0
                AND heure_arrivee IS NULL
                AND heure_depart IS NULL
                $filtre_sql
                GROUP BY date_rdv) AS af
          ON af.date_rdv = t.date_rdv
        LEFT JOIN (SELECT date_rdv, COUNT(id) AS sur_parc_filtre
                FROM bois_planning
                WHERE date_rdv
                BETWEEN :date_debut
                AND :date_fin
                AND attente = 0
                AND NOT heure_arrivee IS NULL
                AND heure_depart IS NULL
                $filtre_sql
                GROUP BY date_rdv) AS spf
          ON spf.date_rdv = t.date_rdv
        LEFT JOIN (SELECT date_rdv, COUNT(id) AS charges_filtre
                FROM bois_planning
                WHERE date_rdv
                BETWEEN :date_debut
                AND :date_fin
                AND attente = 0
                AND NOT heure_arrivee IS NULL
                AND NOT heure_depart IS NULL
                $filtre_sql
                GROUP BY date_rdv) AS cf
          ON cf.date_rdv = t.date_rdv";

    $statement_compte_attente =
      "SELECT
          (SELECT COUNT(id)
              FROM bois_planning
              WHERE attente = 1) AS total,
          (SELECT COUNT(id)
              FROM bois_planning
              WHERE attente = 1
              $filtre_sql) AS total_filtre";

    // TODO: options pour le regroupement (comme pour le vrac)

    $requete_non_attente = $this->db->prepare($statement_non_attente);
    $requete_attente = $this->db->query($statement_attente);
    $requete_compte_non_attente = $this->db->prepare($statement_compte_non_attente);
    $requete_compte_attente = $this->db->query($statement_compte_attente);

    $requete_non_attente->execute([
      "date_debut" => $date_debut,
      "date_fin" => $date_fin
    ]);

    $requete_compte_non_attente->execute([
      "date_debut" => $date_debut,
      "date_fin" => $date_fin
    ]);

    $rdv_non_attente = $requete_non_attente->fetchAll();
    $rdv_attente = $requete_attente->fetchAll();
    $comptes_non_attente = $requete_compte_non_attente->fetchAll();
    $comptes_attente = $requete_compte_attente->fetchAll();

    $rdvs = [
      "non_attente" => $rdv_non_attente,
      "attente" => $rdv_attente
    ];

    // Réorganisation des comptes
    $compte_avec_dates = [];
    foreach ($comptes_non_attente as $compte) {
      $compte_avec_dates["{$compte['date_rdv']}"] = $compte;
    }

    // Répartition des tiers dans des sous-objets
    foreach ($rdvs as &$categorie) {
      foreach ($categorie as &$rdv) {
        foreach ([
          "fournisseur",
          "client",
          "chargement",
          "livraison",
          "affreteur",
          "transporteur"
        ] as $type) {
          $rdv[$type] = [];
          foreach ($rdv as $key => $value) {
            if (str_starts_with($key, "{$type}_")) {
              $rdv[$type][str_replace("{$type}_", "", $key)] = $value;
              unset($rdv[$key]);
            }
          }
        }
      }
      unset($rdv);
    }
    unset($categorie);

    /**
     * RDV non attente :
     *  - répartition par date
     *  - puis répartition en "rdvs" et "stats"
     * 
     * "non_attente"
     *  |-date
     *  | |-rdvs
     *  | | |-rdv
     *  | | |-rdv
     *  | | |-...
     *  | | 
     *  | |-stats
     *  |   |-total
     *  |   |-attendus
     *  |   |-sur_parc
     *  |   |-charges
     *  |
     *  |-date
     *  | |-....
     */
    $rdvs_ordonnes = [];

    foreach ($rdvs["non_attente"] as $rdv) {
      $rdvs_ordonnes[$rdv["date_rdv"]]["rdvs"][] = $rdv;

      // Création des nombres de camions par date
      if (!isset($rdvs_ordonnes[$rdv["date_rdv"]]["stats"])) {
        $rdvs_ordonnes[$rdv["date_rdv"]]["stats"]["total"] = (int) $compte_avec_dates[$rdv["date_rdv"]]["total"];
        $rdvs_ordonnes[$rdv["date_rdv"]]["stats"]["attendus"] = (int) $compte_avec_dates[$rdv["date_rdv"]]["attendus"];
        $rdvs_ordonnes[$rdv["date_rdv"]]["stats"]["sur_parc"] = (int) $compte_avec_dates[$rdv["date_rdv"]]["sur_parc"];
        $rdvs_ordonnes[$rdv["date_rdv"]]["stats"]["charges"] = (int) $compte_avec_dates[$rdv["date_rdv"]]["charges"];
        $rdvs_ordonnes[$rdv["date_rdv"]]["stats"]["total_filtre"] = (int) $compte_avec_dates[$rdv["date_rdv"]]["total_filtre"];
        $rdvs_ordonnes[$rdv["date_rdv"]]["stats"]["attendus_filtre"] = (int) $compte_avec_dates[$rdv["date_rdv"]]["attendus_filtre"];
        $rdvs_ordonnes[$rdv["date_rdv"]]["stats"]["sur_parc_filtre"] = (int) $compte_avec_dates[$rdv["date_rdv"]]["sur_parc_filtre"];
        $rdvs_ordonnes[$rdv["date_rdv"]]["stats"]["charges_filtre"] = (int) $compte_avec_dates[$rdv["date_rdv"]]["charges_filtre"];
      }
    }

    $rdvs["non_attente"] = $rdvs_ordonnes;



    /**
     * RDV en attente :
     *  - répartition en "rdvs" et "stats"
     * 
     * "attente"
     *  |-rdvs
     *  | |-rdv
     *  | |-rdv
     *  | |-...
     *  | 
     *  |-stats
     *    |-total
     */
    $rdvs_ordonnes = [
      "rdvs" => [],
      "stats" => [
        "total" => (int) $comptes_attente[0]["total"],
        "total_filtre" => (int) $comptes_attente[0]["total_filtre"]
      ]
    ];

    foreach ($rdvs["attente"] as $rdv) {
      $rdvs_ordonnes["rdvs"][] = $rdv;
    }

    $rdvs["attente"] = $rdvs_ordonnes;

    // Rétablissement des types INT
    array_walk_recursive($rdvs, function (&$value, $key) {
      $value = match ($key) {
        "id",
        "attente",
        "confirmation_affretement",
        "lie_agence" => $value === NULL ? NULL : (int) $value,
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
  public function read($id)
  {
    $statement =
      "SELECT
        p.id,
          p.attente,
          p.date_rdv,
          SUBSTRING(p.heure_arrivee, 1, 5) AS heure_arrivee,
          SUBSTRING(p.heure_depart, 1, 5) AS heure_depart,
          p.confirmation_affretement,
          p.numero_bl,
          p.commentaire_public,
          p.commentaire_cache,
          c.id AS client_id,
          c.nom_court AS client_nom_court,
          c.nom_complet AS client_nom_complet,
          c.adresse_ligne_1 AS client_adresse_ligne_1,
          c.adresse_ligne_2 AS client_adresse_ligne_2,
          c.cp AS client_cp,
          c.ville AS client_ville,
          c.pays AS client_pays,
          c.telephone AS client_telephone,
          c.commentaire AS client_commentaire,
          ch.id AS chargement_id,
          ch.nom_court AS chargement_nom_court,
          ch.nom_complet AS chargement_nom_complet,
          ch.adresse_ligne_1 AS chargement_adresse_ligne_1,
          ch.adresse_ligne_2 AS chargement_adresse_ligne_2,
          ch.cp AS chargement_cp,
          ch.ville AS chargement_ville,
          ch.pays AS chargement_pays_iso,
          ch.telephone AS chargement_telephone,
          ch.commentaire AS chargement_commentaire,
          l.id AS livraison_id,
          l.nom_court AS livraison_nom_court,
          l.nom_complet AS livraison_nom_complet,
          l.adresse_ligne_1 AS livraison_adresse_ligne_1,
          l.adresse_ligne_2 AS livraison_adresse_ligne_2,
          l.cp AS livraison_cp,
          l.ville AS livraison_ville,
          l.pays AS livraison_pays,
          l.telephone AS livraison_telephone,
          l.commentaire AS livraison_commentaire,
          a.id AS affreteur_id,
          a.nom_court AS affreteur_nom,
          a.lie_agence AS affreteur_lie_agence,
          f.id AS fournisseur_id,
          f.nom_court AS fournisseur_nom,
          t.id AS transporteur_id,
          t.nom_court AS transporteur_nom,
          t.telephone AS transporteur_telephone
      FROM bois_planning AS p
      LEFT JOIN tiers AS c ON p.client = c.id
      LEFT JOIN tiers AS ch ON p.chargement = ch.id
      LEFT JOIN tiers AS l ON p.livraison = l.id
      LEFT JOIN tiers AS a ON p.affreteur = a.id
      LEFT JOIN tiers AS f ON p.fournisseur = f.id
      LEFT JOIN tiers AS t ON p.transporteur = t.id
      WHERE p.id = :id";

    $requete = $this->db->prepare($statement);
    $requete->execute(["id" => $id]);
    $rdv = $requete->fetch();

    if (!$rdv) {
      return;
    }

    // Répartition des tiers dans des sous-objets
    foreach ([
      "fournisseur",
      "client",
      "chargement",
      "livraison",
      "affreteur",
      "transporteur"
    ] as $type) {
      $rdv[$type] = [];
      foreach ($rdv as $key => $value) {
        if (str_starts_with($key, "{$type}_")) {
          $rdv[$type][str_replace("{$type}_", "", $key)] = $value;
          unset($rdv[$key]);
        }
      }
    }

    // Rétablissement des types INT
    array_walk_recursive($rdv, function (&$value, $key) {
      $value = match ($key) {
        "id",
        "attente",
        "confirmation_affretement",
        "lie_agence" => $value === NULL ? NULL : (int) $value,
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
  public function create(array $input)
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
      :confirmation_affretement,
      :numero_bl,
      :commentaire_public,
      :commentaire_cache
      )";

    $requete = $this->db->prepare($statement);

    $this->db->beginTransaction();
    $requete->execute([
      'attente' => isset($input["attente"]) ? 1 : 0,
      'date_rdv' => isset($input["date_rdv"])
        ? ($input["date_rdv"] ?: NULL)
        : date("Y-m-d"),
      'heure_arrivee' => isset($input["heure_arrivee"])
        ? ($input["heure_arrivee"] ?: NULL)
        : date("H:i"),
      'heure_depart' => isset($input["heure_depart"])
        ? ($input["heure_depart"] ?: NULL)
        : NULL,
      'chargement' => $input["chargement"],
      'client' => $input["client"],
      'livraison' => $input["livraison"] ?: NULL,
      'transporteur' => $input["transporteur"] ?: NULL,
      'affreteur' => $input["affreteur"] ?: NULL,
      'fournisseur' => $input["fournisseur"],
      'confirmation_affretement' => isset($input["confirmation_affretement"]) ? 1 : 0,
      'numero_bl' => $input["numero_bl"] ?? "",
      'commentaire_public' => $input["commentaire_public"] ?? "",
      'commentaire_cache' => $input["commentaire_cache"] ?? ""
    ]);

    $last_id = $this->db->lastInsertId();
    $this->db->commit();

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
  public function update($id, array $input)
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
        confirmation_affretement = :confirmation_affretement,
        numero_bl = :numero_bl,
        commentaire_public = :commentaire_public,
        commentaire_cache = :commentaire_cache
      WHERE id = :id";

    $requete = $this->db->prepare($statement);
    $requete->execute([
      'attente' => isset($input["attente"]) ? 1 : 0,
      'date_rdv' => $input["date_rdv"] ?: NULL,
      'heure_arrivee' => $input["heure_arrivee"] ?: NULL,
      'heure_depart' => $input["heure_depart"] ?: NULL,
      'client' => $input["client"],
      'chargement' => $input["chargement"],
      'livraison' => $input["livraison"] ?: NULL,
      'transporteur' => $input["transporteur"] ?: NULL,
      'affreteur' => $input["affreteur"] ?: NULL,
      'fournisseur' => $input["fournisseur"],
      'confirmation_affretement' => isset($input["confirmation_affretement"]) ? 1 : 0,
      'numero_bl' => $input["numero_bl"],
      'commentaire_public' => $input["commentaire_public"],
      'commentaire_cache' => $input["commentaire_cache"],
      'id' => $id
    ]);

    return $this->read($id);
  }

  /**
   * Supprime un RDV bois.
   * 
   * @param int $id ID du RDV à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(int $id)
  {
    $requete = $this->db->prepare("DELETE FROM bois_planning WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
