<?php

namespace App\Models\Utils;

use App\Models\Model;

class PaysModel extends Model
{
    private $redis_ns = "pays";

    /**
     * Récupère tous les pays.
     * 
     * @return array Tous les pays récupérés.
     */
    public function readAll(): array
    {
        // Redis
        $countries = json_decode($this->redis->get($this->redis_ns));

        if (!$countries) {
            $statement = "SELECT * FROM utils_pays ORDER BY nom";

            $countries = $this->mysql->query($statement)->fetchAll();

            $this->redis->set($this->redis_ns, json_encode($countries));
        }

        return $countries;
    }

    /**
     * Récupère un pays.
     * 
     * @param string $iso Code ISO du pays à récupérer
     * 
     * @return array Pays récupéré
     */
    public function read($iso): ?array
    {
        $statement = "SELECT * FROM utils_pays WHERE iso = :iso";

        $request = $this->mysql->prepare($statement);
        $request->execute(["iso" => $iso]);
        $country = $request->fetch();

        if (!$country) return null;

        return $country;
    }

    /**
     * Crée un pays.
     * 
     * @param array $input Eléments du pays à créer
     * 
     * @return array Pays créé
     */
    public function create(array $input): array
    {
        $statement = "INSERT INTO utils_pays VALUES(:iso, :nom)";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'iso' => $input["iso"],
            'nom' => $input["nom"]
        ]);

        $lastInsertId = $this->mysql->lastInsertId();
        $this->mysql->commit();

        $this->redis->del($this->redis_ns);

        return $this->read($lastInsertId);
    }

    /**
     * Met à jour un pays.
     * 
     * @param string $iso    Code ISO du pays à modifier
     * @param array  $input  Eléments du paus à modifier
     * 
     * @return array Pays modifié
     */
    public function update($iso, array $input): array
    {
        $statement = "UPDATE utils_pays SET nom = :nom WHERE iso = :iso";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'nom' => $input["nom"],
            'iso' => $iso
        ]);

        $this->redis->del($this->redis_ns);

        return $this->read($iso);
    }

    /**
     * Supprime un pays.
     * 
     * @param string $iso Code ISO du pays à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function delete($iso): bool
    {
        $request = $this->mysql->prepare("DELETE FROM utils_pays WHERE iso = :iso");
        $isDeleted = $request->execute(["iso" => $iso]);

        $this->redis->del($this->redis_ns);

        return $isDeleted;
    }
}
