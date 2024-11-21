<?php

// Path: api/src/Service/PortService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
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
        $rawDataAH = new ArrayHandler($rawData);

        return (new Port())
            ->setLocode($rawDataAH->getString('locode'))
            ->setName($rawDataAH->getString('nom'))
            ->setDisplayName($rawDataAH->getString('nom_affichage'));
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
        if ($locode === null) {
            return null;
        }

        return $this->portRepository->fetchPortByLocode($locode);
    }
}
