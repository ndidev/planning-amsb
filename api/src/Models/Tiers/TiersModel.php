<?php

namespace Api\Models\Tiers;

use Api\Utils\DatabaseConnector as DB;
use Throwable;
use Exception;

class TiersModel
{
  private $db;

  public function __construct()
  {
    $this->db = (new DB)->getConnection();
  }

  /**
   * Récupère tous les tiers.
   * 
   * @param array $options Options de récupération
   * 
   * @return array Liste des tiers
   */
  public function readAll(array $options = []): array
  {
    /**
     * Parse options
     */

    // Inclure le nombre de RDV par tiers
    $nombre_rdv = $options["nombre_rdv"] ?? NULL;
    $nombre_rdv = match ($nombre_rdv) {
      "true", "1" => TRUE,
      "false", "0" => FALSE,
      default => FALSE
    };

    // Ne sélectionner que les tiers actifs
    $actifs = $options["actifs"] ?? NULL;
    $actifs = match ($actifs) {
      "true", "1" => TRUE,
      "false", "0" => FALSE,
      default => FALSE
    };

    // Inclure les tiers non modifiables
    $non_modifiables = $options["non_modifiables"] ?? NULL;
    $non_modifiables = match ($non_modifiables) {
      "true", "1" => TRUE,
      "false", "0" => FALSE,
      default => TRUE
    };

    /**
     * Requêtes
     */

    // Requête par défaut : tout prendre
    $statement_full =
      "SELECT *
        FROM tiers
        ORDER BY
          nom_court,
          ville";

    $statement_tiers = $statement_full;

    // Statement Awesomeplete
    $statement_awesomplete =
      "SELECT
          id,
          nom_court,
          ville,
          bois_fournisseur,
          bois_client,
          bois_transporteur,
          bois_affreteur,
          vrac_fournisseur,
          vrac_client,
          vrac_transporteur,
          maritime_armateur,
          maritime_affreteur,
          maritime_courtier
        FROM tiers
        ORDER BY
          nom_court,
          ville";

    if (($options["format"] ?? NULL) === "awesomplete") {
      $statement_tiers = $statement_awesomplete;
    }

    // Nombre de RDV par tiers
    $statement_nombre_rdv =
      "SELECT 
        t.id,
        (
          (SELECT COUNT(v.id)
            FROM vrac_planning v
            WHERE t.id IN (
              v.client,
              v.transporteur,
              v.fournisseur
            )
          )
          +
          (SELECT COUNT(b.id)
            FROM bois_planning b
            WHERE t.id IN (
              b.client,
              b.chargement,
              b.livraison,
              b.transporteur,
              b.affreteur,
              b.fournisseur
            )
          )
          +
          (SELECT COUNT(c.id)
            FROM consignation_planning c
            WHERE t.id IN (
              c.armateur
            )
          )
          +
          (SELECT COUNT(ch.id)
            FROM chartering_registre ch
            WHERE t.id IN (
              ch.armateur,
              ch.affreteur,
              ch.courtier
            )
          )
        ) AS nombre_rdv
      FROM tiers t
      ";

    $liste_tiers = $this->db->query($statement_tiers)->fetchAll();

    foreach ($liste_tiers as &$tiers) {
      // Filtre sur les tiers actifs
      if ($actifs === true && (bool) $tiers["actif"] === false) {
        unset($tiers);
        continue;
      }

      // Filtre sur les tiers non modifiables
      if ($non_modifiables === false && (bool) $tiers["non_modifiable"] === true) {
        unset($tiers);
        continue;
      }

      // Changement TINYINT en booléen
      $tiers["id"] = (int) $tiers["id"];
      $tiers["bois_fournisseur"] = (bool) $tiers["bois_fournisseur"];
      $tiers["bois_client"] = (bool) $tiers["bois_client"];
      $tiers["bois_transporteur"] = (bool) $tiers["bois_transporteur"];
      $tiers["bois_affreteur"] = (bool) $tiers["bois_affreteur"];
      $tiers["vrac_fournisseur"] = (bool) $tiers["vrac_fournisseur"];
      $tiers["vrac_client"] = (bool) $tiers["vrac_client"];
      $tiers["vrac_transporteur"] = (bool) $tiers["vrac_transporteur"];
      $tiers["maritime_affreteur"] = (bool) $tiers["maritime_affreteur"];
      $tiers["maritime_armateur"] = (bool) $tiers["maritime_armateur"];
      $tiers["maritime_courtier"] = (bool) $tiers["maritime_courtier"];

      // Modififaction de l'adresse du logo
      if ($tiers["logo"]) {
        $tiers["logo"] = $_ENV["LOGOS_URL"] . "/" . $tiers["logo"];
      }

      if ($statement_tiers === $statement_full) {
        $tiers["non_modifiable"] = (bool) $tiers["non_modifiable"];
        $tiers["lie_agence"] = (bool) $tiers["lie_agence"];
        $tiers["actif"] = (bool) $tiers["actif"];
      }
    }

    if ($nombre_rdv) {
      // Récupération du nombre de RDV par tiers
      $liste_nombre_rdv = $this->db->query($statement_nombre_rdv)->fetchAll();

      // Remplacement des clés génériques (0, 1, etc..) par l'id du tiers
      $liste_nombre_rdv_avec_cles = [];
      for ($i = 0; $i < count($liste_nombre_rdv); $i++) {
        $liste_nombre_rdv_avec_cles[$liste_nombre_rdv[$i]["id"]] = $liste_nombre_rdv[$i];
      }

      // Ajout du nombre de RDV pour chaque tiers
      foreach ($liste_tiers as &$tiers) {
        $tiers["nombre_rdv"] = (int) $liste_nombre_rdv_avec_cles[$tiers["id"]]["nombre_rdv"];
      }
    }

    $donnees = $liste_tiers;

    return $donnees;
  }

  /**
   * Récupère un tiers.
   * 
   * @param int   $id      ID du tiers à récupérer
   * @param array $options Options de récupération
   * 
   * @return array Tiers récupéré
   */
  public function read($id, array $options = []): array
  {
    /**
     * Parse options
     */

    // Inclure le nombre de RDV
    $nombre_rdv = $options["nombre_rdv"] ?? NULL;
    $nombre_rdv = match ($nombre_rdv) {
      "true", "1" => TRUE,
      default => FALSE
    };

    /**
     * Requêtes
     */

    $statement_tiers =
      "SELECT *
        FROM tiers
        WHERE id = :id";

    $statement_nombre_rdv =
      "SELECT 
        t.id,
        (
          (SELECT COUNT(v.id)
            FROM vrac_planning v
            WHERE t.id IN (
              v.client,
              v.transporteur,
              v.fournisseur
            )
          )
          +
          (SELECT COUNT(b.id)
            FROM bois_planning b
            WHERE t.id IN (
              b.client,
              b.chargement,
              b.livraison,
              b.transporteur,
              b.affreteur,
              b.fournisseur
            )
          )
          +
          (SELECT COUNT(c.id)
            FROM consignation_planning c
            WHERE t.id IN (
              c.armateur
            )
          )
          +
          (SELECT COUNT(ch.id)
            FROM chartering_registre ch
            WHERE t.id IN (
              ch.armateur,
              ch.affreteur,
              ch.courtier
            )
          )
        ) AS nombre_rdv
      FROM tiers t
      WHERE t.id = :id
      ";

    $requete_tiers = $this->db->prepare($statement_tiers);
    $requete_nombre_rdv = $this->db->prepare($statement_nombre_rdv);
    $requete_tiers->execute(["id" => $id]);
    $tiers = $requete_tiers->fetch();

    if ($tiers) {
      $tiers["id"] = (int) $tiers["id"];
      $tiers["bois_fournisseur"] = (bool) $tiers["bois_fournisseur"];
      $tiers["bois_client"] = (bool) $tiers["bois_client"];
      $tiers["bois_transporteur"] = (bool) $tiers["bois_transporteur"];
      $tiers["vrac_fournisseur"] = (bool) $tiers["vrac_fournisseur"];
      $tiers["bois_affreteur"] = (bool) $tiers["bois_affreteur"];
      $tiers["vrac_client"] = (bool) $tiers["vrac_client"];
      $tiers["vrac_transporteur"] = (bool) $tiers["vrac_transporteur"];
      $tiers["maritime_affreteur"] = (bool) $tiers["maritime_affreteur"];
      $tiers["maritime_armateur"] = (bool) $tiers["maritime_armateur"];
      $tiers["maritime_courtier"] = (bool) $tiers["maritime_courtier"];
      $tiers["non_modifiable"] = (bool) $tiers["non_modifiable"];
      $tiers["lie_agence"] = (bool) $tiers["lie_agence"];
      $tiers["actif"] = (bool) $tiers["actif"];


      if ($nombre_rdv) {
        $requete_nombre_rdv->execute(["id" => $id]);
        $nombre_rdv = $requete_nombre_rdv->fetch()["nombre_rdv"];
        $tiers["nombre_rdv"] = (int) $nombre_rdv;
      }
    }

    $donnees = $tiers;

    return $donnees;
  }

  /**
   * Crée un tiers.
   * 
   * @param array $input Eléments du tiers à créer
   * 
   * @return array Tiers créé
   */
  public function create(array $input): array
  {
    // Enregistrement du logo dans le dossier images
    $logo = $this->enregistrerLogo($input["logo"] ?? NULL) ?: NULL;

    $statement =
      "INSERT INTO tiers
        VALUES(
          NULL,
          :nom_court,
          :nom_complet,
          :adresse_ligne_1,
          :adresse_ligne_2,
          :cp,
          :ville,
          :pays,
          :telephone,
          :commentaire,
          :bois_fournisseur,
          :bois_client,
          :bois_transporteur,
          :bois_affreteur,
          :vrac_fournisseur,
          :vrac_client,
          :vrac_transporteur,
          :maritime_armateur,
          :maritime_affreteur,
          :maritime_courtier,
          :non_modifiable,
          :lie_agence,
          :logo,
          :actif
        )";

    $requete = $this->db->prepare($statement);

    $this->db->beginTransaction();
    $requete->execute([
      'nom_court' => $input["nom_court"] ?: $input["nom_complet"],
      'nom_complet' => $input["nom_complet"],
      'adresse_ligne_1' => $input["adresse_ligne_1"],
      'adresse_ligne_2' => $input["adresse_ligne_2"],
      'cp' => $input["cp"],
      'ville' => $input["ville"],
      'pays' => $input["pays"],
      'telephone' => $input["telephone"],
      'commentaire' => $input["commentaire"],
      'bois_fournisseur' => isset($input["bois_fournisseur"]) ? 1 : 0,
      'bois_client' => isset($input["bois_client"]) ? 1 : 0,
      'bois_transporteur' => isset($input["bois_transporteur"]) ? 1 : 0,
      'bois_affreteur' => isset($input["bois_affreteur"]) ? 1 : 0,
      'vrac_fournisseur' => isset($input["vrac_fournisseur"]) ? 1 : 0,
      'vrac_client' => isset($input["vrac_client"]) ? 1 : 0,
      'vrac_transporteur' => isset($input["vrac_transporteur"]) ? 1 : 0,
      'maritime_armateur' => isset($input["maritime_armateur"]) ? 1 : 0,
      'maritime_affreteur' => isset($input["maritime_affreteur"]) ? 1 : 0,
      'maritime_courtier' => isset($input["maritime_courtier"]) ? 1 : 0,
      'non_modifiable' => isset($input["non_modifiable"]) ? 1 : 0,
      'lie_agence' => 0,
      'logo' => $logo,
      'actif' => 1,
    ]);

    $last_id = $this->db->lastInsertId();
    $this->db->commit();

    return $this->read($last_id);
  }

  /**
   * Met à jour un tiers.
   * 
   * @param int   $id ID du tiers à modifier
   * @param array $input  Eléments du tiers à modifier
   * 
   * @return array tiers modifié
   */
  public function update($id, array $input)
  {
    $update_logo = array_key_exists("logo", $input);

    // Enregistrement du logo dans le dossier images
    if ($update_logo) {
      $logo = $this->enregistrerLogo($input["logo"]);

      // En cas d'erreur de traitement du fichier, ne pas modifier le logo existant
      if ($logo === false) {
        $update_logo = false;
      }
    }

    // Si un logo a été ajouté, l'utiliser, sinon, ne pas changer
    $statement_logo = $update_logo ? ":logo" : "logo";

    $statement =
      "UPDATE tiers
        SET
          nom_court = :nom_court,
          nom_complet = :nom_complet,
          adresse_ligne_1 = :adresse_ligne_1,
          adresse_ligne_2 = :adresse_ligne_2,
          cp = :cp,
          ville = :ville,
          pays = :pays,
          telephone = :telephone,
          commentaire = :commentaire,
          bois_fournisseur = :bois_fournisseur,
          bois_client = :bois_client,
          bois_transporteur = :bois_transporteur,
          bois_affreteur = :bois_affreteur,
          vrac_fournisseur = :vrac_fournisseur,
          vrac_client = :vrac_client,
          vrac_transporteur = :vrac_transporteur,
          maritime_armateur = :maritime_armateur,
          maritime_affreteur = :maritime_affreteur,
          maritime_courtier = :maritime_courtier,
          logo = $statement_logo,
          actif = :actif
        WHERE id = :id";

    $requete = $this->db->prepare($statement);

    $champs = [
      'nom_court' => $input["nom_court"] ?: $input["nom_complet"],
      'nom_complet' => $input["nom_complet"],
      'adresse_ligne_1' => $input["adresse_ligne_1"],
      'adresse_ligne_2' => $input["adresse_ligne_2"],
      'cp' => $input["cp"],
      'ville' => $input["ville"],
      'pays' => $input["pays"],
      'telephone' => $input["telephone"],
      'commentaire' => $input["commentaire"],
      'bois_fournisseur' => isset($input["bois_fournisseur"]) ? 1 : 0,
      'bois_client' => isset($input["bois_client"]) ? 1 : 0,
      'bois_transporteur' => isset($input["bois_transporteur"]) ? 1 : 0,
      'bois_affreteur' => isset($input["bois_affreteur"]) ? 1 : 0,
      'vrac_fournisseur' => isset($input["vrac_fournisseur"]) ? 1 : 0,
      'vrac_client' => isset($input["vrac_client"]) ? 1 : 0,
      'vrac_transporteur' => isset($input["vrac_transporteur"]) ? 1 : 0,
      'maritime_armateur' => isset($input["maritime_armateur"]) ? 1 : 0,
      'maritime_affreteur' => isset($input["maritime_affreteur"]) ? 1 : 0,
      'maritime_courtier' => isset($input["maritime_courtier"]) ? 1 : 0,
      'actif' => isset($input["actif"]) ? 1 : 0,
      'id' => $id,
    ];

    if ($update_logo) {
      $champs["logo"] = $logo;
    }

    $requete->execute($champs);

    return $this->read($id);
  }

  /**
   * Supprime un tiers.
   * 
   * @param int $id ID du tiers à supprimer
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(int $id)
  {
    $requete = $this->db->prepare("DELETE FROM tiers WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }

  /**
   * Enregistrer un logo dans le dossier images
   * et retourne le hash du fichier.
   * 
   * @param ?array $fichier Données du fichier (null pour effacement du logo existant).
   * 
   * @return string|null|false Nom de fichier du logo si l'enregistrement a réussi, `false` sinon.
   */
  private function enregistrerLogo(?array $fichier): string|null|false
  {
    try {
      if ($fichier === null) {
        return null;
      }

      $data = $fichier["data"];

      /** Création de l'image depuis les données */
      $image_string = base64_decode($data);
      $image = imagecreatefromstring(base64_decode($data));

      if (!$image) {
        throw new Exception("Logo : Erreur dans la création de l'image (imagecreatefromstring)");
      }


      /** Redimensionnement */
      define("MAX_HEIGHT", 500); // Hauteur maximale de l'image à enregistrer.

      [$width, $height] = getimagesizefromstring($image_string);
      $percent = min(MAX_HEIGHT / $height, 1);
      $new_width = (int) ($width * $percent);
      $image_resized = imagescale($image, $new_width);


      /** Enregistrement */
      $hash = hash("md5", $data);
      $filename = $hash . ".webp";
      $filepath = LOGOS . "/$filename";
      if (imagewebp($image_resized, $filepath, 100) === false) {
        throw new Exception("Erreur dans l'enregistrement du logo (imagewebp)");
      }

      return $filename;
    } catch (Throwable $e) {
      error_logger($e);
      return false;
    }
  }
}
