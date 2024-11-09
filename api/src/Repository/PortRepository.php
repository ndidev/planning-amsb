<?php

// Path: api/src/Repository/PortRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Port;
use App\Service\PortService;

/**
 * @phpstan-type PortArray array{
 *                           locode?: string,
 *                           nom?: string,
 *                           nom_affichage?: string,
 *                         }
 */
final class PortRepository extends Repository
{
    private string $redisNamespace = "ports";

    public function __construct(private PortService $portService)
    {
        parent::__construct();
    }

    /**
     * Récupère tous les ports.
     * 
     * @return Collection<Port> Tous les ports récupérés.
     */
    public function fetchAllPorts(): Collection
    {
        // Redis
        $redisValue = $this->redis->get($this->redisNamespace);
        $portsRaw = is_string($redisValue) ? json_decode($redisValue, true) : null;

        if (!is_array($portsRaw)) {
            $statement = "SELECT * FROM utils_ports ORDER BY SUBSTRING(locode, 1, 2), nom";

            $portsRequest = $this->mysql->query($statement);

            if (!$portsRequest) {
                throw new DBException("Impossible de récupérer les ports.");
            }

            /** @phpstan-var PortArray[] $portsRaw */
            $portsRaw = $portsRequest->fetchAll();

            $this->redis->set($this->redisNamespace, json_encode($portsRaw));
        }

        $ports = array_map(
            fn($portRaw) => $this->portService->makePortFromDatabase($portRaw),
            $portsRaw
        );

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

        if (!is_array($portRaw)) return null;

        /** @phpstan-var PortArray $portRaw */

        $port = $this->portService->makePortFromDatabase($portRaw);

        return $port;
    }
}
