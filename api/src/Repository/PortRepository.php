<?php

// Path: api/src/Repository/PortRepository.php

namespace App\Repository;

use App\Entity\Port;
use App\Service\PortService;

class PortRepository extends Repository
{
    private $redisNamespace = "ports";

    /**
     * Récupère tous les ports.
     * 
     * @return Port[] Tous les ports récupérés.
     */
    public function fetchAll(): array
    {
        // Redis
        $portsRaw = json_decode($this->redis->get($this->redisNamespace), true);

        if (!$portsRaw) {
            $statement = "SELECT * FROM utils_ports ORDER BY SUBSTRING(locode, 1, 2), nom";

            $portsRaw = $this->mysql->query($statement)->fetchAll();

            $this->redis->set($this->redisNamespace, json_encode($portsRaw));
        }

        $portService = new PortService();

        $ports = array_map(fn (array $portRaw) => $portService->makePort($portRaw), $portsRaw);

        return $ports;
    }

    /**
     * Récupère un port.
     * 
     * @param string $locode UNLOCODE du port à récupérer
     * 
     * @return ?Port Port récupéré
     */
    public function fetchByLocode(string $locode): ?Port
    {
        $statement = "SELECT * FROM utils_ports WHERE locode = :locode";

        $request = $this->mysql->prepare($statement);
        $request->execute(["locode" => $locode]);
        $portRaw = $request->fetch();

        if (!$portRaw) return null;

        $portService = new PortService();

        $port = $portService->makePort($portRaw);

        return $port;
    }
}
