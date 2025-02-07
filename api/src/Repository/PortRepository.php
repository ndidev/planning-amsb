<?php

// Path: api/src/Repository/PortRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Port;
use App\Service\PortService;
use ReflectionClass;

/**
 * @phpstan-import-type PortArray from \App\Entity\Port
 */
final class PortRepository extends Repository
{
    /** @var ReflectionClass<Port> */
    private ReflectionClass $reflector;

    private string $redisNamespace = "ports";

    public function __construct(private PortService $portService)
    {
        $this->reflector = new ReflectionClass(Port::class);
    }

    public function portExists(string $locode): bool
    {
        return $this->mysql->exists('utils_ports', $locode, 'locode');
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
        $portsRaw = \is_string($redisValue) ? \json_decode($redisValue, true) : null;

        if (!\is_array($portsRaw)) {
            $statement = "SELECT * FROM utils_ports ORDER BY SUBSTRING(locode, 1, 2), nom";

            $portsRequest = $this->mysql->query($statement);

            if (!$portsRequest) {
                throw new DBException("Impossible de récupérer les ports.");
            }

            $portsRaw = $portsRequest->fetchAll();

            $this->redis->set($this->redisNamespace, \json_encode($portsRaw));
        }

        /** @phpstan-var PortArray[] $portsRaw */

        $ports = \array_map(
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
        /** @var array<string, Port> */
        static $cache = [];

        if (isset($cache[$locode])) {
            return $cache[$locode];
        }

        if (!$this->portExists($locode)) {
            return null;
        }

        /** @var Port */
        $port = $this->reflector->newLazyProxy(
            function () use ($locode) {
                try {
                    $statement = "SELECT * FROM utils_ports WHERE locode = :locode";

                    /** @var PortArray */
                    $portRaw = $this->mysql
                        ->prepareAndExecute($statement, ["locode" => $locode])
                        ->fetch();

                    return $this->portService->makePortFromDatabase($portRaw);
                } catch (\PDOException $e) {
                    throw new DBException("Impossible de récupérer le port.", previous: $e);
                }
            }
        );

        $this->reflector->getProperty('locode')->setRawValueWithoutLazyInitialization($port, $locode);

        $cache[$locode] = $port;

        return $port;
    }
}
