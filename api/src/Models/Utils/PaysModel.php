<?php

namespace App\Models\Utils;

use App\Models\Model;
use App\Entity\Country;

class PaysModel extends Model
{
    private $redis_ns = "pays";

    /**
     * Récupère tous les pays.
     * 
     * @return array<int, \App\Entity\Country> Tous les pays récupérés.
     */
    public function readAll(): array
    {
        // Redis
        $listePaysRaw = json_decode($this->redis->get($this->redis_ns), true);

        if (!$listePaysRaw) {
            $statement = "SELECT * FROM utils_pays ORDER BY nom";

            $listePaysRaw = $this->mysql->query($statement)->fetchAll();

            $this->redis->set($this->redis_ns, json_encode($listePaysRaw));
        }

        $listePays = array_map(fn (array $paysRaw) => new Country($paysRaw), $listePaysRaw);

        return $listePays;
    }

    /**
     * Récupère un pays.
     * 
     * @param string $iso Code ISO du pays à récupérer
     * 
     * @return ?Country Pays récupéré
     */
    public function read(string $iso): ?Country
    {
        $statement = "SELECT * FROM utils_pays  WHERE iso = :iso";

        $requete = $this->mysql->prepare($statement);
        $requete->execute(["iso" => $iso]);
        $paysRaw = $requete->fetch();

        if (!$paysRaw) return null;

        $pays = new Country($paysRaw);

        return $pays;
    }

    /**
     * Crée un pays.
     * 
     * @param array $input Eléments du pays à créer
     * 
     * @return Country Pays créé
     */
    public function create(array $input): Country
    {
        $statement =
            "INSERT INTO utils_pays
        VALUES(
          :iso,
          :nom
        )";

        $requete = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $requete->execute([
            'iso' => $input["iso"],
            'nom' => $input["nom"]
        ]);

        $last_id = $this->mysql->lastInsertId();
        $this->mysql->commit();

        $this->redis->del($this->redis_ns);

        return $this->read($last_id);
    }

    /**
     * Met à jour un pays.
     * 
     * @param string $iso    Code ISO du pays à modifier
     * @param array  $input  Eléments du paus à modifier
     * 
     * @return Country Pays modifié
     */
    public function update($iso, array $input): Country
    {
        $statement =
            "UPDATE utils_pays
        SET nom = :nom
        WHERE iso = :iso";

        $requete = $this->mysql->prepare($statement);
        $requete->execute([
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
    public function delete(string $iso): bool
    {
        $requete = $this->mysql->prepare("DELETE FROM utils_pays WHERE iso = :iso");
        $succes = $requete->execute(["iso" => $iso]);

        $this->redis->del($this->redis_ns);

        return $succes;
    }
}
