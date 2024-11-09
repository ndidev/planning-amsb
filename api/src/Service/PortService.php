<?php

// Path: api/src/Service/PortService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\Port;
use App\Repository\PortRepository;

/**
 * @phpstan-import-type PortArray from \App\Repository\PortRepository
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
     * @param array $rawData 
     * 
     * @phpstan-param PortArray $rawData
     * 
     * @return Port 
     */
    public function makePortFromDatabase(array $rawData): Port
    {
        return (new Port())
            ->setLocode($rawData["locode"] ?? "")
            ->setName($rawData["nom"] ?? "")
            ->setDisplayName($rawData["nom_affichage"] ?? "");
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

    public function getPort(string $locode): ?Port
    {
        return $this->portRepository->fetchPortByLocode($locode);
    }
}
