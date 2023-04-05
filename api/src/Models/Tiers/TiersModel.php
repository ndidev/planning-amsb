<?php

namespace Api\Models\Tiers;

use Api\Utils\BaseModel;
use Throwable;
use Exception;

class TiersModel extends BaseModel
{
  private $redis_ns = "tiers";

  public function __construct()
  {
    parent::__construct();
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
    $statement =
      "SELECT *
        FROM tiers
        ORDER BY
          nom_court,
          ville";

    $liste_tiers = $this->db->query($statement)->fetchAll();

    foreach ($liste_tiers as &$tiers) {
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
      $tiers["non_modifiable"] = (bool) $tiers["non_modifiable"];
      $tiers["lie_agence"] = (bool) $tiers["lie_agence"];
      $tiers["actif"] = (bool) $tiers["actif"];

      // Modififaction de l'adresse du logo
      if ($tiers["logo"]) {
        $tiers["logo"] = $_ENV["LOGOS_URL"] . "/" . $tiers["logo"];
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
  public function read($id, array $options = []): ?array
  {
    $statement =
      "SELECT *
        FROM tiers
        WHERE id = :id";

    $requete_tiers = $this->db->prepare($statement);
    $requete_tiers->execute(["id" => $id]);
    $tiers = $requete_tiers->fetch();

    if (!$tiers) return null;

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

    // Modififaction de l'adresse du logo
    if ($tiers["logo"]) {
      $tiers["logo"] = $_ENV["LOGOS_URL"] . "/" . $tiers["logo"];
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
      'bois_fournisseur' => (int) $input["bois_fournisseur"],
      'bois_client' => (int) $input["bois_client"],
      'bois_transporteur' => (int) $input["bois_transporteur"],
      'bois_affreteur' => (int) $input["bois_affreteur"],
      'vrac_fournisseur' => (int) $input["vrac_fournisseur"],
      'vrac_client' => (int) $input["vrac_client"],
      'vrac_transporteur' => (int) $input["vrac_transporteur"],
      'maritime_armateur' => (int) $input["maritime_armateur"],
      'maritime_affreteur' => (int) $input["maritime_affreteur"],
      'maritime_courtier' => (int) $input["maritime_courtier"],
      'non_modifiable' => (int) $input["non_modifiable"],
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
  public function update($id, array $input): array
  {
    // Enregistrement du logo dans le dossier images
    $logo = $this->enregistrerLogo($input["logo"]);

    // Si un logo a été ajouté, l'utiliser, sinon, ne pas changer
    $statement_logo = $logo !== false ? ":logo" : "logo";

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
      'bois_fournisseur' => (int) $input["bois_fournisseur"],
      'bois_client' => (int) $input["bois_client"],
      'bois_transporteur' => (int) $input["bois_transporteur"],
      'bois_affreteur' => (int) $input["bois_affreteur"],
      'vrac_fournisseur' => (int) $input["vrac_fournisseur"],
      'vrac_client' => (int) $input["vrac_client"],
      'vrac_transporteur' => (int) $input["vrac_transporteur"],
      'maritime_armateur' => (int) $input["maritime_armateur"],
      'maritime_affreteur' => (int) $input["maritime_affreteur"],
      'maritime_courtier' => (int) $input["maritime_courtier"],
      'actif' => (int) $input["actif"],
      'id' => $id,
    ];

    if ($logo !== false) {
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
  public function delete(int $id): bool
  {
    $requete = $this->db->prepare("DELETE FROM tiers WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }

  /**
   * Enregistrer un logo dans le dossier images
   * et retourne le hash du fichier.
   * 
   * @param array|string|null $fichier Données du fichier (null pour effacement du logo existant).
   * 
   * @return string|null|false Nom de fichier du logo si l'enregistrement a réussi, `false` sinon.
   */
  private function enregistrerLogo(array|string|null $fichier): string|null|false
  {
    try {
      // Conservation du fichier existant
      if (gettype($fichier) === "string") {
        return false;
      }

      // Suppression du fichier existant
      if ($fichier === null) {
        return null;
      }

      // Récupérer les données de l'image
      // $fichier["data"] = "data:{type mime};base64,{données}"
      $data = explode(",", $fichier["data"])[1];

      // Création de l'image depuis les données
      $image_string = base64_decode($data);
      $image = imagecreatefromstring(base64_decode($data));

      if (!$image) {
        throw new Exception("Logo : Erreur dans la création de l'image (imagecreatefromstring)");
      }


      // Redimensionnement
      define("MAX_HEIGHT", 500); // Hauteur maximale de l'image à enregistrer.

      [$width, $height] = getimagesizefromstring($image_string);
      $percent = min(MAX_HEIGHT / $height, 1);
      $new_width = (int) ($width * $percent);
      $image_resized = imagescale($image, $new_width);


      // Enregistrement
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
