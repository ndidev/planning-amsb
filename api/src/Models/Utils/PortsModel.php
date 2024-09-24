<?php

namespace App\Models\Utils;

use App\Models\Model;

class PortsModel extends Model
{
    private $redis_ns = "ports";

    /**
     * Récupère tous les ports.
     * 
     * @return array Tous les ports récupérés.
     */
    public function readAll(): array
    {
        // Redis
        $ports = json_decode($this->redis->get($this->redis_ns));

        if (!$ports) {
            $statement = "SELECT * FROM utils_ports ORDER BY SUBSTRING(locode, 1, 2), nom";

            $ports = $this->mysql->query($statement)->fetchAll();

            $this->redis->set($this->redis_ns, json_encode($ports));
        }

        return $ports;
    }

    /**
     * Récupère un port.
     * 
     * @param string $locode UNLOCODE du port à récupérer
     * 
     * @return array Port récupéré
     */
    public function read($locode): ?array
    {
        $statement = "SELECT * FROM utils_ports WHERE locode = :locode";

        $request = $this->mysql->prepare($statement);
        $request->execute(["locode" => $locode]);
        $port = $request->fetch();

        if (!$port) return null;

        return $port;
    }

    /**
     * Crée un port.
     * 
     * @param array $input Eléments du port à créer
     * 
     * @return array Port créé
     */
    public function create(array $input): array
    {
        $statement = "INSERT INTO utils_ports VALUES(:locode, :nom)";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $request->execute([
            'locode' => $input["locode"],
            'nom' => $input["nom"]
        ]);

        $last_id = $this->mysql->lastInsertId();
        $this->mysql->commit();

        $this->redis->del($this->redis_ns);

        return $this->read($last_id);
    }

    /**
     * Met à jour un port.
     * 
     * @param string $locode UNLOCODE du port à modifier
     * @param array  $input  Eléments du port à modifier
     * 
     * @return array Port modifié
     */
    public function update($locode, array $input): array
    {
        $statement = "UPDATE utils_ports SET nom = :nom WHERE locode = :locode";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'nom' => $input["nom"],
            'locode' => $locode
        ]);

        $this->redis->del($this->redis_ns);

        return $this->read($locode);
    }

    /**
     * Supprime un port.
     * 
     * @param string $locode UNLOCODE du port à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function delete($locode): bool
    {
        $request = $this->mysql->prepare("DELETE FROM utils_ports WHERE locode = :locode");
        $isDeleted = $request->execute(["locode" => $locode]);

        $this->redis->del($this->redis_ns);

        return $isDeleted;
    }
}
