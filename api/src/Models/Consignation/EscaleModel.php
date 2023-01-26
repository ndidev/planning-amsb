<?php

namespace Api\Models\Consignation;

use Api\Utils\DatabaseConnector as DB;
use Api\Utils\DateUtils;
use DateInterval;
use DateTime;

class EscaleModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère toutes les escales consignation.
   * 
   * @return array Toutes les escale récupérés
   */
  public function readAll(array $filtre = [])
  {

    $hier = (new DateTime())->sub(new DateInterval("P1D"))->format("Y-m-d");

    if (array_key_exists("archives", $filtre)) {
      $statement_escales =
        "SELECT
            p.id,
            p.navire,
            p.voyage,
            p.armateur,
            a.nom_court AS armateur_nom,
            p.eta_date,
            p.eta_heure,
            p.nor_date,
            p.nor_heure,
            p.pob_date,
            p.pob_heure,
            p.etb_date,
            p.etb_heure,
            p.ops_date,
            p.ops_heure,
            p.etc_date,
            p.etc_heure,
            p.etd_date,
            p.etd_heure,
            p.te_arrivee,
            p.te_depart,
            p.last_port,
            p.next_port,
            p.call_port,
            p.quai,
            p.commentaire
          FROM consignation_planning p
          LEFT JOIN tiers a ON p.armateur = a.id
          WHERE etd_date <= '$hier'
          ORDER BY
            -eta_date ASC,
            eta_heure,
            -etb_date ASC,
            etb_heure";
    } else {
      $statement_escales =
        "SELECT
            p.id,
            p.navire,
            p.voyage,
            p.armateur,
            a.nom_court AS armateur_nom,
            p.eta_date,
            p.eta_heure,
            p.nor_date,
            p.nor_heure,
            p.pob_date,
            p.pob_heure,
            p.etb_date,
            p.etb_heure,
            p.ops_date,
            p.ops_heure,
            p.etc_date,
            p.etc_heure,
            p.etd_date,
            p.etd_heure,
            p.te_arrivee,
            p.te_depart,
            p.last_port,
            p.next_port,
            p.call_port,
            p.quai,
            p.commentaire
          FROM consignation_planning p
          LEFT JOIN tiers a ON p.armateur = a.id
          WHERE etd_date >= '$hier'
          OR etd_date IS NULL
          ORDER BY
            -eta_date DESC,
            eta_heure,
            -etb_date DESC,
            etb_heure";
    }

    $statement_marchandises =
      "SELECT
          m.id,
          m.escale_id,
          m.marchandise,
          m.client,
          c.nom_court AS client_nom,
          m.operation,
          m.environ,
          m.tonnage_bl,
          m.cubage_bl,
          m.nombre_bl,
          m.tonnage_outturn,
          m.cubage_outturn,
          m.nombre_outturn
        FROM consignation_escales_marchandises m
        LEFT JOIN tiers c ON c.id = m.client
        WHERE m.escale_id = :id";

    $statement_ports =
      "SELECT nom_affichage
        FROM utils_ports
        WHERE locode = :locode";

    // Escales
    $requete_escales = $this->db->query($statement_escales);
    $escales = $requete_escales->fetchAll();

    $requete_marchandises = $this->db->prepare($statement_marchandises);
    $requete_ports = $this->db->prepare($statement_ports);

    foreach ($escales as &$escale) {
      $id = $escale["id"];

      // ETA
      $escale["eta_heure"] = eta_vers_lettres($escale["eta_heure"]);

      // TE
      $escale["te_arrivee"] = $escale["te_arrivee"] !== NULL ? (float) $escale["te_arrivee"] : NULL;
      $escale["te_depart"] = $escale["te_depart"] !== NULL ? (float) $escale["te_depart"] : NULL;

      // Ports
      $escale["last_port_nom"] = NULL;
      $escale["next_port_nom"] = NULL;

      if ($escale["last_port"]) {
        $requete_ports->execute(["locode" => $escale["last_port"]]);
        $escale["last_port_nom"] = $requete_ports->fetch()["nom_affichage"] ?? NULL;
      }

      if ($escale["next_port"]) {
        $requete_ports->execute(["locode" => $escale["next_port"]]);
        $escale["next_port_nom"] = $requete_ports->fetch()["nom_affichage"] ?? NULL;
      }

      // Marchandises
      $requete_marchandises->execute(["id" => $id]);
      $marchandises = $requete_marchandises->fetchAll();
      foreach ($marchandises as &$marchandise) {
        $marchandise["environ"] = (bool) $marchandise["environ"];
        $marchandise["tonnage_bl"] = $marchandise["tonnage_bl"] !== NULL ? (float) $marchandise["tonnage_bl"] : NULL;
        $marchandise["cubage_bl"] = $marchandise["cubage_bl"] !== NULL ? (float) $marchandise["cubage_bl"] : NULL;
        $marchandise["nombre_bl"] = $marchandise["nombre_bl"] !== NULL ? (int) $marchandise["nombre_bl"] : NULL;
        $marchandise["tonnage_outturn"] = $marchandise["tonnage_outturn"] !== NULL ? (float) $marchandise["tonnage_outturn"] : NULL;
        $marchandise["cubage_outturn"] = $marchandise["cubage_outturn"] !== NULL ? (float) $marchandise["cubage_outturn"] : NULL;
        $marchandise["nombre_outturn"] = $marchandise["nombre_outturn"] !== NULL ? (int) $marchandise["nombre_outturn"] : NULL;
      }

      $escale["marchandises"] = $marchandises;
    }


    $donnees = $escales;

    return $donnees;
  }

  /**
   * Récupère une escale consignation.
   * 
   * @param int $id ID de l'escale à récupérer
   * 
   * @return array Rendez-vous récupéré
   */
  public function read($id)
  {
    $statement_escale =
      "SELECT
          p.id,
          p.navire,
          p.voyage,
          p.armateur,
          a.nom_court AS armateur_nom,
          p.eta_date,
          p.eta_heure,
          p.nor_date,
          p.nor_heure,
          p.pob_date,
          p.pob_heure,
          p.etb_date,
          p.etb_heure,
          p.ops_date,
          p.ops_heure,
          p.etc_date,
          p.etc_heure,
          p.etd_date,
          p.etd_heure,
          p.te_arrivee,
          p.te_depart,
          p.last_port,
          p.next_port,
          p.call_port,
          p.quai,
          p.commentaire
        FROM consignation_planning p
        LEFT JOIN tiers a ON p.armateur = a.id
        WHERE p.id = :id";

    $statement_marchandises =
      "SELECT
          m.id,
          m.escale_id,
          m.marchandise,
          m.client,
          c.nom_court AS client_nom,
          m.operation,
          m.environ,
          m.tonnage_bl,
          m.cubage_bl,
          m.nombre_bl,
          m.tonnage_outturn,
          m.cubage_outturn,
          m.nombre_outturn
        FROM consignation_escales_marchandises m
        LEFT JOIN tiers c ON c.id = m.client
        WHERE m.escale_id = :id";

    $statement_ports =
      "SELECT nom_affichage
        FROM utils_ports
        WHERE locode = :locode";

    // Escales
    $requete_escale = $this->db->prepare($statement_escale);
    $requete_escale->execute(["id" => $id]);
    $escale = $requete_escale->fetch();

    // ETA
    $escale["eta_heure"] = eta_vers_lettres($escale["eta_heure"]);

    // TE
    $escale["te_arrivee"] = $escale["te_arrivee"] !== NULL ? (float) $escale["te_arrivee"] : NULL;
    $escale["te_depart"] = $escale["te_depart"] !== NULL ? (float) $escale["te_depart"] : NULL;


    // Ports
    $requete_ports = $this->db->prepare($statement_ports);

    $escale["last_port_nom"] = NULL;
    $escale["next_port_nom"] = NULL;

    if ($escale["last_port"]) {
      $requete_ports->execute(["locode" => $escale["last_port"]]);
      $escale["last_port_nom"] = $requete_ports->fetch()["nom_affichage"] ?? NULL;
    }

    if ($escale["next_port"]) {
      $requete_ports->execute(["locode" => $escale["next_port"]]);
      $escale["next_port_nom"] = $requete_ports->fetch()["nom_affichage"] ?? NULL;
    }


    // Marchandises
    $requete_marchandises = $this->db->prepare($statement_marchandises);
    $requete_marchandises->execute(["id" => $id]);
    $marchandises = $requete_marchandises->fetchAll();

    if ($escale) {
      foreach ($marchandises as &$marchandise) {
        $marchandise["environ"] = (bool) $marchandise["environ"];
        $marchandise["tonnage_bl"] = $marchandise["tonnage_bl"] !== NULL ? (float) $marchandise["tonnage_bl"] : NULL;
        $marchandise["cubage_bl"] = $marchandise["cubage_bl"] !== NULL ? (float) $marchandise["cubage_bl"] : NULL;
        $marchandise["nombre_bl"] = $marchandise["nombre_bl"] !== NULL ? (int) $marchandise["nombre_bl"] : NULL;
        $marchandise["tonnage_outturn"] = $marchandise["tonnage_outturn"] !== NULL ? (float) $marchandise["tonnage_outturn"] : NULL;
        $marchandise["cubage_outturn"] = $marchandise["cubage_outturn"] !== NULL ? (float) $marchandise["cubage_outturn"] : NULL;
        $marchandise["nombre_outturn"] = $marchandise["nombre_outturn"] !== NULL ? (int) $marchandise["nombre_outturn"] : NULL;
      }

      $escale["marchandises"] = $marchandises;
    }


    $donnees = $escale;

    return $donnees;
  }

  /**
   * Crée une escale consignation.
   * 
   * @param array $input Eléments de l'escale à créer
   * 
   * @return array Rendez-vous créé
   */
  public function create(array $input)
  {
    // Champs dates et TE
    $input["eta_date"] = $input["eta_date"] ?: NULL;
    $input["nor_date"] = $input["nor_date"] ?: NULL;
    $input["pob_date"] = $input["pob_date"] ?: NULL;
    $input["etb_date"] = $input["etb_date"] ?: NULL;
    $input["ops_date"] = $input["ops_date"] ?: NULL;
    $input["etc_date"] = $input["etc_date"] ?: NULL;
    $input["etd_date"] = $input["etd_date"] ?: NULL;
    $input["te_arrivee"] = $input["te_arrivee"] === "" ? NULL : $input["te_arrivee"];
    $input["te_depart"] = $input["te_depart"] === "" ? NULL : $input["te_depart"];

    $statement_escale =
      "INSERT INTO consignation_planning
        VALUES(
          NULL,
          :navire,
          :voyage,
          :armateur,
          :eta_date,
          :eta_heure,
          :nor_date,
          :nor_heure,
          :pob_date,
          :pob_heure,
          :etb_date,
          :etb_heure,
          :ops_date,
          :ops_heure,
          :etc_date,
          :etc_heure,
          :etd_date,
          :etd_heure,
          :te_arrivee,
          :te_depart,
          :last_port,
          :next_port,
          :call_port,
          :quai,
          :commentaire
        )";

    $statement_marchandises =
      "INSERT INTO consignation_escales_marchandises
        VALUES(
          NULL,
          :escale_id,
          :marchandise,
          :client,
          :operation,
          :environ,
          :tonnage_bl,
          :cubage_bl,
          :nombre_bl,
          :tonnage_outturn,
          :cubage_outturn,
          :nombre_outturn
        )";

    $requete = $this->db->prepare($statement_escale);

    $this->db->beginTransaction();
    $requete->execute([
      'navire' => $input["navire"] ?: "TBN",
      'voyage' => $input["voyage"],
      'armateur' => $input["armateur"] ?: NULL,
      'eta_date' => $input["eta_date"],
      'eta_heure' => eta_vers_chiffres($input["eta_heure"]),
      'nor_date' => $input["nor_date"],
      'nor_heure' => $input["nor_heure"],
      'pob_date' => $input["pob_date"],
      'pob_heure' => $input["pob_heure"],
      'etb_date' => $input["etb_date"],
      'etb_heure' => $input["etb_heure"],
      'ops_date' => $input["ops_date"],
      'ops_heure' => $input["ops_heure"],
      'etc_date' => $input["etc_date"],
      'etc_heure' => $input["etc_heure"],
      'etd_date' => $input["etd_date"],
      'etd_heure' => $input["etd_heure"],
      'te_arrivee' => $input["te_arrivee"],
      'te_depart' => $input["te_depart"],
      'last_port' => $input["last_port"],
      'next_port' => $input["next_port"],
      'call_port' => $input["call_port"],
      'quai' => $input["quai"],
      'commentaire' => $input["commentaire"],
    ]);

    $last_id = $this->db->lastInsertId();
    $this->db->commit();

    // Marchandises
    $requete_marchandises = $this->db->prepare($statement_marchandises);
    $marchandises = $input["marchandises"] ?? [];
    foreach ($marchandises as $marchandise) {
      $requete_marchandises->execute([
        'escale_id' => $last_id,
        'marchandise' => $marchandise["marchandise"],
        'client' => $marchandise["client"],
        'operation' => $marchandise["operation"],
        'environ' => $marchandise["environ"] ? 1 : 0,
        'tonnage_bl' => $marchandise["tonnage_bl"],
        'cubage_bl' => $marchandise["cubage_bl"],
        'nombre_bl' => $marchandise["nombre_bl"],
        'tonnage_outturn' => $marchandise["tonnage_outturn"],
        'cubage_outturn' => $marchandise["cubage_outturn"],
        'nombre_outturn' => $marchandise["nombre_outturn"],
      ]);
    }

    return $this->read($last_id);
  }

  /**
   * Met à jour une escale consignation.
   * 
   * @param int   $id ID de l'escale à modifier
   * @param array $input     Eléments de l'escale à modifier
   * 
   * @return array escale modifié
   */
  public function update($id, array $input)
  {
    // Champs dates et TE
    $input["eta_date"] = $input["eta_date"] ?: NULL;
    $input["nor_date"] = $input["nor_date"] ?: NULL;
    $input["pob_date"] = $input["pob_date"] ?: NULL;
    $input["etb_date"] = $input["etb_date"] ?: NULL;
    $input["ops_date"] = $input["ops_date"] ?: NULL;
    $input["etc_date"] = $input["etc_date"] ?: NULL;
    $input["etd_date"] = $input["etd_date"] ?: NULL;
    $input["te_arrivee"] = $input["te_arrivee"] === "" ? NULL : $input["te_arrivee"];
    $input["te_depart"] = $input["te_depart"] === "" ? NULL : $input["te_depart"];

    $statement_escale =
      "UPDATE consignation_planning
        SET
          navire = :navire,
          voyage = :voyage,
          armateur = :armateur,
          eta_date = :eta_date,
          eta_heure = :eta_heure,
          nor_date = :nor_date,
          nor_heure = :nor_heure,
          pob_date = :pob_date,
          pob_heure = :pob_heure,
          etb_date = :etb_date,
          etb_heure = :etb_heure,
          ops_date = :ops_date,
          ops_heure = :ops_heure,
          etc_date = :etc_date,
          etc_heure = :etc_heure,
          etd_date = :etd_date,
          etd_heure = :etd_heure,
          te_arrivee = :te_arrivee,
          te_depart = :te_depart,
          last_port = :last_port,
          next_port = :next_port,
          call_port = :call_port,
          quai = :quai,
          commentaire = :commentaire
        WHERE id = :id";

    $statement_marchandises_ajout =
      "INSERT INTO consignation_escales_marchandises
          VALUES(
            NULL,
            :escale_id,
            :marchandise,
            :client,
            :operation,
            :environ,
            :tonnage_bl,
            :cubage_bl,
            :nombre_bl,
            :tonnage_outturn,
            :cubage_outturn,
            :nombre_outturn
          )";

    $statement_marchandises_modif =
      "UPDATE consignation_escales_marchandises
        SET
          marchandise = :marchandise,
          client = :client,
          operation = :operation,
          environ = :environ,
          tonnage_bl = :tonnage_bl,
          cubage_bl = :cubage_bl,
          nombre_bl = :nombre_bl,
          tonnage_outturn = :tonnage_outturn,
          cubage_outturn = :cubage_outturn,
          nombre_outturn = :nombre_outturn
        WHERE id = :id";

    $requete = $this->db->prepare($statement_escale);
    $requete->execute([
      'navire' => $input["navire"] ?: "TBN",
      'voyage' => $input["voyage"],
      'armateur' => $input["armateur"] ?: NULL,
      'eta_date' => $input["eta_date"],
      'eta_heure' => eta_vers_chiffres($input["eta_heure"]),
      'nor_date' => $input["nor_date"],
      'nor_heure' => $input["nor_heure"],
      'pob_date' => $input["pob_date"],
      'pob_heure' => $input["pob_heure"],
      'etb_date' => $input["etb_date"],
      'etb_heure' => $input["etb_heure"],
      'ops_date' => $input["ops_date"],
      'ops_heure' => $input["ops_heure"],
      'etc_date' => $input["etc_date"],
      'etc_heure' => $input["etc_heure"],
      'etd_date' => $input["etd_date"],
      'etd_heure' => $input["etd_heure"],
      'te_arrivee' => $input["te_arrivee"],
      'te_depart' => $input["te_depart"],
      'last_port' => $input["last_port"],
      'next_port' => $input["next_port"],
      'call_port' => $input["call_port"],
      'quai' => $input["quai"],
      'commentaire' => $input["commentaire"],
      'id' => $id
    ]);

    // MARCHANDISES
    // Suppression marchandises
    // !! SUPPRESSION A LAISSER *AVANT* L'AJOUT DE marchandise POUR EVITER SUPPRESSION IMMEDIATE APRES AJOUT !!
    // Comparaison du tableau transmis par POST avec la liste existante des marchandises pour le produit concerné
    $requete_marchandises = $this->db->prepare(
      "SELECT id
          FROM consignation_escales_marchandises
          WHERE escale_id = :escale_id"
    );
    $requete_marchandises->execute(['escale_id' => $id]);
    $ids_marchandises_existantes = [];
    while ($marchandise = $requete_marchandises->fetch()) {
      $ids_marchandises_existantes[] = $marchandise['id'];
    }

    $ids_marchandises_transmises = [];
    if (isset($input['marchandises'])) {
      foreach ($input["marchandises"] as $marchandise) {
        $ids_marchandises_transmises[] = $marchandise["id"];
      }
    }
    $ids_marchandises_a_supprimer = array_diff($ids_marchandises_existantes, $ids_marchandises_transmises);

    $requete_supprimer = $this->db->prepare(
      "DELETE FROM consignation_escales_marchandises
          WHERE id = :id"
    );
    foreach ($ids_marchandises_a_supprimer as $id_suppr) {
      $requete_supprimer->execute(['id' => $id_suppr]);
    }

    // Ajout et modification marchandises
    $requete_marchandises_ajout = $this->db->prepare($statement_marchandises_ajout);
    $requete_marchandises_modif = $this->db->prepare($statement_marchandises_modif);
    $marchandises = $input["marchandises"] ?? [];
    foreach ($marchandises as $marchandise) {
      if ($marchandise["id"]) {
        $requete_marchandises_modif->execute([
          'marchandise' => $marchandise["marchandise"],
          'client' => $marchandise["client"],
          'operation' => $marchandise["operation"],
          'environ' => $marchandise["environ"] ? 1 : 0,
          'tonnage_bl' => $marchandise["tonnage_bl"],
          'cubage_bl' => $marchandise["cubage_bl"],
          'nombre_bl' => $marchandise["nombre_bl"],
          'tonnage_outturn' => $marchandise["tonnage_outturn"],
          'cubage_outturn' => $marchandise["cubage_outturn"],
          'nombre_outturn' => $marchandise["nombre_outturn"],
          'id' => $marchandise["id"]
        ]);
      } else {
        $requete_marchandises_ajout->execute([
          'escale_id' => $id,
          'marchandise' => $marchandise["marchandise"],
          'client' => $marchandise["client"],
          'operation' => $marchandise["operation"],
          'environ' => $marchandise["environ"] ? 1 : 0,
          'tonnage_bl' => $marchandise["tonnage_bl"],
          'cubage_bl' => $marchandise["cubage_bl"],
          'nombre_bl' => $marchandise["nombre_bl"],
          'tonnage_outturn' => $marchandise["tonnage_outturn"],
          'cubage_outturn' => $marchandise["cubage_outturn"],
          'nombre_outturn' => $marchandise["nombre_outturn"]
        ]);
      }
    }

    return $this->read($id);
  }

  /**
   * Supprime une escale consignation.
   * 
   * @param int $id ID de l'escale à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(int $id)
  {
    $requete = $this->db->prepare("DELETE FROM consignation_planning WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
