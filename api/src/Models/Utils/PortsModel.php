<?php

namespace App\Models\Utils;

use App\Models\Model;
use App\Entity\Port;

class PortsModel extends Model
{
    private $redis_ns = "ports";

    /**
     * Récupère tous les ports.
     * 
     * @return array<int, \App\Entity\Port> Tous les ports récupérés.
     */
    public function readAll(): array
    {
        // Redis
        $portsRaw = json_decode($this->redis->get($this->redis_ns), true);

        if (!$portsRaw) {
            $statement = "SELECT * FROM utils_ports ORDER BY SUBSTRING(locode, 1, 2), nom";

            $portsRaw = $this->mysql->query($statement)->fetchAll();

            $this->redis->set($this->redis_ns, json_encode($portsRaw));
        }

        $listePorts = array_map(fn (array $portRaw) => new Port($portRaw), $portsRaw);

        return $listePorts;
    }

    /**
     * Récupère un port.
     * 
     * @param string $locode UNLOCODE du port à récupérer
     * 
     * @return ?Port Port récupéré
     */
    public function read($locode): ?Port
    {
        $statement = "SELECT *
      FROM utils_ports
      WHERE locode = :locode";

        $requete = $this->mysql->prepare($statement);
        $requete->execute(["locode" => $locode]);
        $portRaw = $requete->fetch();

        if (!$portRaw) return null;

        $port = new Port($portRaw);

        return $port;
    }

    /**
     * Crée un port.
     * 
     * @param array $input Eléments du port à créer
     * 
     * @return Port Port créé
     */
    public function create(array $input): Port
    {
        $statement = "INSERT INTO utils_ports
      VALUES(
        :locode,
        :nom
      )";

        $requete = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        $requete->execute([
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
     * @return Port Port modifié
     */
    public function update($locode, array $input): Port
    {
        $statement = "UPDATE utils_ports
      SET nom = :nom
      WHERE locode = :locode";

        $requete = $this->mysql->prepare($statement);
        $requete->execute([
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
        $requete = $this->mysql->prepare("DELETE FROM utils_ports WHERE locode = :locode");
        $succes = $requete->execute(["locode" => $locode]);

        $this->redis->del($this->redis_ns);

        return $succes;
    }
}
