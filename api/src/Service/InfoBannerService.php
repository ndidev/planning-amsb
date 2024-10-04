<?php

// Path: api/src/Service/InfoBannerService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\Config\InfoBannerLine;
use App\Repository\InfoBannerRepository;

class InfoBannerService
{
    private InfoBannerRepository $infoBannerRepository;

    public function __construct()
    {
        $this->infoBannerRepository = new InfoBannerRepository();
    }

    public function makeLineFromDatabase(array $rawData): InfoBannerLine
    {
        $line = (new InfoBannerLine())
            ->setId($rawData['id'])
            ->setModule($rawData['module'])
            ->setPc($rawData['pc'])
            ->setTv($rawData['tv'])
            ->setMessage($rawData['message'])
            ->setColor($rawData['couleur']);

        return $line;
    }

    public function makeLineFromForm(array $rawData): InfoBannerLine
    {
        $line = (new InfoBannerLine())
            ->setModule($rawData['module'])
            ->setPc($rawData['pc'])
            ->setTv($rawData['tv'])
            ->setMessage($rawData['message'])
            ->setColor($rawData['couleur']);

        return $line;
    }

    public function lineExists(int $id): bool
    {
        return $this->infoBannerRepository->lineExists($id);
    }

    /**
     * Récupère toutes les lignes du bandeau d'infos.
     * 
     * @param array $filter 
     * 
     * @return Collection<InfoBannerLine> Lignes du bandeau d'infos.
     */
    public function getAllLines(array $filter): Collection
    {
        return $this->infoBannerRepository->fetchAllLines($filter);
    }

    public function getLine(int $id): ?InfoBannerLine
    {
        return $this->infoBannerRepository->fetchLine($id);
    }

    public function createLine(array $rawData): InfoBannerLine
    {
        $line = $this->makeLineFromForm($rawData);

        return $this->infoBannerRepository->createLine($line);
    }

    public function updateLine(int $id, array $rawData): InfoBannerLine
    {
        $line = $this->makeLineFromForm($rawData)->setId($id);

        return $this->infoBannerRepository->updateLine($line);
    }

    public function deleteLine(int $id): void
    {
        $this->infoBannerRepository->deleteLine($id);
    }
}
