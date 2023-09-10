<?php

namespace Api\Models\Config;

use Api\Utils\BaseModel;

class ConfigPDFModel extends BaseModel
{
  /**
   * Récupère toutes les configurations PDF.
   * 
   * @return array Toutes les configurations PDF récupérées
   */
  public function readAll(): array
  {
    $statement =
      "SELECT
          id,
          module,
          fournisseur,
          envoi_auto,
          liste_emails,
          jours_avant,
          jours_apres
        FROM config_pdf";

    $requete = $this->mysql->query($statement);

    $configs = $requete->fetchAll();

    // Rétablissement des types INT et bool
    array_walk_recursive($configs, function (&$value, $key) {
      $value = match ($key) {
        "id", "fournisseur", "jours_avant", "jours_apres" => (int) $value,
        "envoi_auto" => (bool) $value,
        default => $value,
      };
    });

    $donnees = $configs;

    return $donnees;
  }

  /**
   * Récupère une configuration PDF.
   * 
   * @param int $id ID de la configuration à récupérer.
   * 
   * @return array Configuration récupérée.
   */
  public function read(int $id): ?array
  {
    $statement =
      "SELECT
          id,
          module,
          fournisseur,
          envoi_auto,
          liste_emails,
          jours_avant,
          jours_apres
        FROM config_pdf
        WHERE id = :id";

    $requete = $this->mysql->prepare($statement);

    $requete->execute(['id' => $id]);

    $config = $requete->fetch();

    if (!$config) return null;

    // Rétablissement des types INT et bool
    array_walk_recursive($config, function (&$value, $key) {
      $value = match ($key) {
        "id", "fournisseur", "jours_avant", "jours_apres" => (int) $value,
        "envoi_auto" => (bool) $value,
        default => $value,
      };
    });

    $donnees = $config;

    return $donnees;
  }

  /**
   * Crée une configuration PDF.
   * 
   * @param array $input Eléments de la configuration à créer.
   * 
   * @return array Configuration PDF créée.
   */
  public function create(array $input): array
  {
    $statement =
      "INSERT INTO config_pdf
        VALUES(
          NULL,
          :module,
          :fournisseur,
          :envoi_auto,
          :liste_emails,
          :jours_avant,
          :jours_apres
        )";

    $requete = $this->mysql->prepare($statement);

    $this->mysql->beginTransaction();
    $requete->execute([
      "module" => $input["module"],
      "fournisseur" => $input["fournisseur"],
      "envoi_auto" => (int) $input["envoi_auto"],
      "liste_emails" => $input["liste_emails"],
      "jours_avant" => $input["jours_avant"],
      "jours_apres" => $input["jours_apres"],
    ]);

    $last_id = $this->mysql->lastInsertId();
    $this->mysql->commit();

    return $this->read($last_id);
  }

  /**
   * Met à jour une configuration PDF.
   * 
   * @param int   $id     ID de la configuration à modifier.
   * @param array $input  Eléments de la configuation à modifier.
   * 
   * @return array Configuration PDF modifiée.
   */
  public function update(int $id, array $input): array
  {
    $statement =
      "UPDATE config_pdf
        SET
          module = :module,
          fournisseur = :fournisseur,
          envoi_auto = :envoi_auto,
          liste_emails = :liste_emails,
          jours_avant = :jours_avant,
          jours_apres = :jours_apres
        WHERE
          id = :id";

    $requete = $this->mysql->prepare($statement);
    $requete->execute([
      "module" => $input["module"],
      "fournisseur" => $input["fournisseur"],
      "envoi_auto" => (int) $input["envoi_auto"],
      "liste_emails" => $input["liste_emails"],
      "jours_avant" => $input["jours_avant"],
      "jours_apres" => $input["jours_apres"],
      "id" => $id,
    ]);

    return $this->read($id);
  }

  /**
   * Supprime une configuration PDF.
   * 
   * @param int $id ID de la configuration à supprimer.
   * 
   * @return bool TRUE si succès, FALSE si erreur
   */
  public function delete(int $id): bool
  {
    $requete = $this->mysql->prepare("DELETE FROM config_pdf WHERE id = :id");
    $succes = $requete->execute(["id" => $id]);

    return $succes;
  }
}
