<?php

namespace App\Models\Config;

use App\Models\Model;

class AjoutRapideBoisModel extends Model
{
    /**
     * Récupère tous les ajouts rapides bois.
     */
    public function readAll(): array
    {
        $statement = "SELECT * FROM config_ajouts_rapides_bois";

        $quickAddConfigs = $this->mysql->query($statement)->fetchAll();

        // Rétablissement des types int
        array_walk_recursive($quickAddConfigs, function (&$value, $key) {
            $value = match ($key) {
                "id",
                "client",
                "chargement",
                "livraison",
                "affreteur",
                "fournisseur",
                "transporteur" => $value === NULL ? NULL : (int) $value,
                default => $value,
            };
        });

        return $quickAddConfigs;
    }

    /**
     * Récupère un ajout rapide bois.
     * 
     * @param int $id ID de l'ajout rapide à récupérer
     * 
     * @return array Rendez-vous rapide récupéré
     */
    public function read($id): ?array
    {
        $statement = "SELECT * FROM config_ajouts_rapides_bois WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute(["id" => $id]);

        $quickAddConfig = $request->fetch();

        if (!$quickAddConfig) return null;

        // Rétablissement des types int
        array_walk_recursive($quickAddConfig, function (&$value, $key) {
            $value = match ($key) {
                "id",
                "client",
                "chargement",
                "livraison",
                "affreteur",
                "fournisseur",
                "transporteur" => $value === NULL ? NULL : (int) $value,
                default => $value,
            };
        });

        return $quickAddConfig;
    }

    /**
     * Crée un ajout rapide bois.
     * 
     * @param array $input Eléments de l'ajout rapide à créer
     * 
     * @return array Rendez-vous rapide créé
     */
    public function create(array $input): array
    {
        $statement =
            "INSERT INTO config_ajouts_rapides_bois
            VALUES(
                NULL,
                :module,
                :fournisseur,
                :transporteur,
                :affreteur,
                :chargement,
                :client,
                :livraison
            )";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'module' => "bois",
            'fournisseur' => $input["fournisseur"],
            'transporteur' => $input["transporteur"] ?: NULL,
            'affreteur' => $input["affreteur"] ?: NULL,
            'chargement' => $input["chargement"],
            'client' => $input["client"],
            'livraison' => $input["livraison"] ?: NULL,
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->read($lastInsertId);
    }

    /**
     * Met à jour un ajout rapide bois.
     * 
     * @param int   $id     ID de l'ajout rapide à modifier
     * @param array $input  Eléments de l'ajout rapide à modifier
     * 
     * @return array Ajout rapide modifié
     */
    public function update($id, array $input): array
    {
        $statement =
            "UPDATE config_ajouts_rapides_bois
            SET
                fournisseur = :fournisseur,
                transporteur = :transporteur,
                affreteur = :affreteur,
                chargement = :chargement,
                client = :client,
                livraison = :livraison
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'fournisseur' => $input["fournisseur"],
            'transporteur' => $input["transporteur"] ?: NULL,
            'affreteur' => $input["affreteur"] ?: NULL,
            'chargement' => $input["chargement"],
            'client' => $input["client"],
            'livraison' => $input["livraison"] ?: NULL,
            'id' => $id
        ]);

        return $this->read($id);
    }

    /**
     * Supprime un ajout rapide.
     * 
     * @param int $id ID e l'ajout rapide à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function delete(int $id): bool
    {
        $request = $this->mysql->prepare("DELETE FROM config_ajouts_rapides_bois WHERE id = :id");
        $isDeleted = $request->execute(["id" => $id]);

        return $isDeleted;
    }
}
