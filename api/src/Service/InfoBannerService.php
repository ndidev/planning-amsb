<?php

// Path: api/src/Service/InfoBannerService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\Config\InfoBannerLine;
use App\Repository\InfoBannerRepository;

/**
 * @phpstan-type InfoBannerArray array{
 *                                 id?: int,
 *                                 module?: string,
 *                                 pc?: bool,
 *                                 tv?: bool,
 *                                 message?: string,
 *                                 couleur?: string,
 *                               }
 */
final class InfoBannerService
{
    private InfoBannerRepository $infoBannerRepository;

    public function __construct()
    {
        $this->infoBannerRepository = new InfoBannerRepository();
    }

    /**
     * Creates an info banner line from database data.
     * 
     * @param array $rawData Raw data from the database.
     * 
     * @phpstan-param InfoBannerArray $rawData
     * 
     * @return InfoBannerLine 
     */
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

    /**
     * Creates an info banner line from form data.
     * 
     * @param array $rawData Raw data from the form.
     * 
     * @phpstan-param InfoBannerArray $rawData
     * 
     * @return InfoBannerLine 
     */
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
     * @return Collection<InfoBannerLine> Lignes du bandeau d'infos.
     */
    public function getAllLines(): Collection
    {
        return $this->infoBannerRepository->fetchAllLines();
    }

    public function getLine(int $id): ?InfoBannerLine
    {
        return $this->infoBannerRepository->fetchLine($id);
    }

    /**
     * Creates an info banner line.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param InfoBannerArray $rawData
     * 
     * @return InfoBannerLine 
     */
    public function createLine(array $rawData): InfoBannerLine
    {
        $line = $this->makeLineFromForm($rawData);

        return $this->infoBannerRepository->createLine($line);
    }

    /**
     * Updates an info banner line.
     * 
     * @param int   $id      Line ID.
     * @param array $rawData Raw data from the form.
     * 
     * @phpstan-param InfoBannerArray $rawData
     * 
     * @return InfoBannerLine 
     */
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
