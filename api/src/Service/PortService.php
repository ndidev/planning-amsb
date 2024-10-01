<?php

// Path: api/src/Service/PortService.php

namespace App\Service;

use App\Entity\Port;
use App\Repository\PortRepository;

class PortService
{
    private PortRepository $portRepository;

    public function __construct()
    {
        $this->portRepository = new PortRepository();
    }

    public function makePortFromDatabase(array $rawData): Port
    {
        return (new Port())
            ->setLocode($rawData["locode"] ?? "")
            ->setName($rawData["nom"] ?? "")
            ->setDisplayName($rawData["nom_affichage"] ?? "");
    }

    public function getPorts(): array
    {
        return $this->portRepository->fetchAll();
    }

    public function getPort(string $locode): ?Port
    {
        return $this->portRepository->fetchByLocode($locode);
    }
}
