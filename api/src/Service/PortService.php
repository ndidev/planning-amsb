<?php

// Path: api/src/Service/PortService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Entity\Port;
use App\Repository\PortRepository;

/**
 * @phpstan-import-type PortArray from \App\Entity\Port
 */
final class PortService
{
    private PortRepository $portRepository;

    public function __construct()
    {
        $this->portRepository = new PortRepository($this);
    }

    /**
     * Creates a Port object from database data.
     * 
     * @param PortArray $rawData 
     * 
     * @return Port 
     */
    public function makePortFromDatabase(array $rawData): Port
    {
        return new Port($rawData);
    }

    public function portExists(string $locode): bool
    {
        return $this->portRepository->portExists($locode);
    }

    /**
     * Fetches all ports.
     * 
     * @return Collection<Port> All fetched ports.
     */
    public function getPorts(): Collection
    {
        return $this->portRepository->fetchAllPorts();
    }

    public function getPort(?string $locode): ?Port
    {
        /** @var array<string, Port> */
        static $cache = [];

        if ($locode === null) {
            return null;
        }

        if (!$this->portExists($locode)) {
            return null;
        }

        $reflector = new \ReflectionClass(Port::class);
        $portRepository = $this->portRepository;
        /** @var Port */
        $port = $reflector->newLazyGhost(
            function (Port $port) use ($locode, $portRepository) {
                $data = $portRepository->fetchPortByLocode($locode, true);
                $port->__construct($data);
            }
        );

        $reflector->getProperty('locode')->setRawValueWithoutLazyInitialization($port, $locode);

        $cache[$locode] = $port;

        return $port;
    }
}
