<?php

// Path: api/src/Repository/PortRepository.php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Port;
use App\Service\PortService;

final class PortRepository extends Repository
{
    private string $redisNamespace = "ports";

    /**
     * Récupère tous les ports.
     * 
     * @return Collection<Port> Tous les ports récupérés.
     */
    public function fetchAllPorts(): Collection
    {
        // Redis
        $portsRaw = json_decode($this->redis->get($this->redisNamespace), true);

        if (!$portsRaw) {
            $statement = "SELECT * FROM utils_ports ORDER BY SUBSTRING(locode, 1, 2), nom";

            $portsRequest = $this->mysql->query($statement);

            if (!$portsRequest) {
                throw new DBException("Impossible de récupérer les ports.");
            }

            $portsRaw = $portsRequest->fetchAll();

            $this->redis->set($this->redisNamespace, json_encode($portsRaw));
        }

        $portService = new PortService();

        $ports = array_map(fn(array $portRaw) => $portService->makePortFromDatabase($portRaw), $portsRaw);

        return new Collection($ports);
    }

    /**
     * Récupère un port.
     * 
     * @param string $locode UNLOCODE du port à récupérer
     * 
     * @return ?Port Port récupéré
     */
    public function fetchPortByLocode(string $locode): ?Port
    {
        $statement = "SELECT * FROM utils_ports WHERE locode = :locode";

        $request = $this->mysql->prepare($statement);
        $request->execute(["locode" => $locode]);
        $portRaw = $request->fetch();

        if (!$portRaw) return null;

        $portService = new PortService();

        $port = $portService->makePortFromDatabase($portRaw);

        return $port;
    }
}
