<?php

namespace App\Models\Config;

use App\Models\Model;

class ConfigPDFModel extends Model
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

        $request = $this->mysql->query($statement);

        $configs = $request->fetchAll();

        // Rétablissement des types INT et bool
        array_walk_recursive($configs, function (&$value, $key) {
            $value = match ($key) {
                "id", "fournisseur", "jours_avant", "jours_apres" => (int) $value,
                "envoi_auto" => (bool) $value,
                default => $value,
            };
        });

        return $configs;
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

        $request = $this->mysql->prepare($statement);

        $request->execute(['id' => $id]);

        $config = $request->fetch();

        if (!$config) return null;

        // Rétablissement des types INT et bool
        array_walk_recursive($config, function (&$value, $key) {
            $value = match ($key) {
                "id", "fournisseur", "jours_avant", "jours_apres" => (int) $value,
                "envoi_auto" => (bool) $value,
                default => $value,
            };
        });

        return $config;
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

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            "module" => $input["module"],
            "fournisseur" => $input["fournisseur"],
            "envoi_auto" => (int) $input["envoi_auto"],
            "liste_emails" => $input["liste_emails"],
            "jours_avant" => $input["jours_avant"],
            "jours_apres" => $input["jours_apres"],
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->read($lastInsertId);
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
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute([
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
        $request = $this->mysql->prepare("DELETE FROM config_pdf WHERE id = :id");
        $isDeleted = $request->execute(["id" => $id]);

        return $isDeleted;
    }
}
