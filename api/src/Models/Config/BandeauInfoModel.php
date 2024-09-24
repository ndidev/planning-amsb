<?php

namespace App\Models\Config;

use App\Models\Model;

class BandeauInfoModel extends Model
{
    /**
     * Récupère toutes les lignes du bandeau d'infos.
     * 
     * @return array Lignes du bandeau d'infos
     */
    public function readAll(array $filter): array
    {
        // Filtre
        $module = $filter['module'] ?? "";
        $pc = $filter['pc'] ?? "";
        $tv = $filter['tv'] ?? "";

        $sqlFilter = "";
        $sqlModuleFilter = $module === "" ? "" : "module = '$module'";
        $sqlPcFilter = $pc === "" ? "" : "pc = $pc";
        $sqlTvFilter = $tv === "" ? "" : "tv = $tv";

        $sqlFilterArray = [];
        foreach ([$sqlModuleFilter, $sqlPcFilter, $sqlTvFilter] as $filterPart) {
            if ($filterPart !== "") {
                array_push($sqlFilterArray, $filterPart);
            }
        }
        if ($sqlFilterArray !== []) {
            $sqlFilter = "WHERE " . join(" AND ", $sqlFilterArray);
        }

        $statement = "SELECT * FROM bandeau_info $sqlFilter";

        $request = $this->mysql->query($statement);
        $infos = $request->fetchAll();

        // Rétablissement des types INT et bool
        array_walk_recursive($infos, function (&$value, $key) {
            $value = match ($key) {
                "id" => (int) $value,
                "pc", "tv" => (bool) $value,
                default => $value,
            };
        });

        return $infos;
    }

    /**
     * Récupère une ligne de bandeau d'infos bois.
     * 
     * @param int $id ID de la ligne à récupérer
     * 
     * @return array Ligne récupérée
     */
    public function read($id): ?array
    {
        $statement = "SELECT * FROM bandeau_info WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute(["id" => $id]);
        $infos = $request->fetch();

        if (!$infos) return null;

        // Rétablissement des types INT et bool
        array_walk_recursive($infos, function (&$value, $key) {
            $value = match ($key) {
                "id" => (int) $value,
                "pc", "tv" => (bool) $value,
                default => $value,
            };
        });

        return $infos;
    }

    /**
     * Crée un client bois.
     * 
     * @param array $input Eléments du client à créer
     * 
     * @return array Ligne créée
     */
    public function create(array $input): array
    {
        $statement =
            "INSERT INTO bandeau_info
            VALUES(
                NULL,
                :module,
                :pc,
                :tv,
                :couleur,
                :message
            )";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'module' => $input["module"],
            'pc' => (int) $input["pc"],
            'tv' => (int) $input["tv"],
            'couleur' => $input["couleur"],
            'message' => substr($input["message"], 0, 255),
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        return $this->read($lastInsertId);
    }

    /**
     * Met à jour une ligne du bandeau d'informations.
     * 
     * @param int   $id     ID de la ligne à modifier
     * @param array $input  Eléments de la ligne à modifier
     * 
     * @return array Ligne modifiée
     */
    public function update($id, array $input): array
    {
        $statement =
            "UPDATE bandeau_info
            SET
                module = :module,
                pc = :pc,
                tv = :tv,
                couleur = :couleur,
                message = :message
            WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'module' => $input["module"],
            'pc' => (int) $input["pc"],
            'tv' => (int) $input["tv"],
            'couleur' => $input["couleur"],
            'message' => substr($input["message"], 0, 255),
            'id' => $id
        ]);

        return $this->read($id);
    }

    /**
     * Supprime une ligne du bandeau d'informations.
     * 
     * @param int $id ID de la ligne à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function delete(int $id): bool
    {
        $request = $this->mysql->prepare("DELETE FROM bandeau_info WHERE id = :id");
        $isDeleted = $request->execute(["id" => $id]);

        return $isDeleted;
    }
}
