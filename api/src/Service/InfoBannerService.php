<?php

// Path: api/src/Service/InfoBannerService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\ClientException;
use App\Core\HTTP\HTTPRequestBody;
use App\Entity\Config\InfoBannerLine;
use App\Repository\InfoBannerRepository;

/**
 * @phpstan-import-type InfoBannerLineArray from \App\Repository\InfoBannerRepository
 */
final class InfoBannerService
{
    private InfoBannerRepository $infoBannerRepository;

    public function __construct()
    {
        $this->infoBannerRepository = new InfoBannerRepository($this);
    }

    /**
     * Creates an info banner line from database data.
     * 
     * @param array $rawData Raw data from the database.
     * 
     * @phpstan-param InfoBannerLineArray $rawData
     * 
     * @return InfoBannerLine 
     */
    public function makeLineFromDatabase(array $rawData): InfoBannerLine
    {
        $module = Module::tryFrom($rawData['module']);

        $line = (new InfoBannerLine())
            ->setId($rawData['id'])
            ->setModule($module)
            ->setPc($rawData['pc'])
            ->setTv($rawData['tv'])
            ->setMessage($rawData['message'])
            ->setColor($rawData['couleur']);

        return $line;
    }

    /**
     * Creates an info banner line from form data.
     * 
     * @param HTTPRequestBody $requestBody Raw data from the form.
     * 
     * @return InfoBannerLine 
     */
    public function makeLineFromForm(HTTPRequestBody $requestBody): InfoBannerLine
    {
        $module = Module::tryFrom($requestBody->getString('module'));

        if (!$module) {
            throw new ClientException("Le module n'est pas valide.");
        }

        $line = (new InfoBannerLine())
            ->setModule($module)
            ->setPc($requestBody->getBool('pc'))
            ->setTv($requestBody->getBool('tv'))
            ->setMessage($requestBody->getString('message'))
            ->setColor($requestBody->getString('couleur', '#000000'));

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
     * @param HTTPRequestBody $rawData 
     * 
     * @return InfoBannerLine 
     */
    public function createLine(HTTPRequestBody $rawData): InfoBannerLine
    {
        $line = $this->makeLineFromForm($rawData);

        return $this->infoBannerRepository->createLine($line);
    }

    /**
     * Updates an info banner line.
     * 
     * @param int             $id      Line ID.
     * @param HTTPRequestBody $rawData Raw data from the form.
     * 
     * @return InfoBannerLine 
     */
    public function updateLine(int $id, HTTPRequestBody $rawData): InfoBannerLine
    {
        $line = $this->makeLineFromForm($rawData)->setId($id);

        return $this->infoBannerRepository->updateLine($line);
    }

    public function deleteLine(int $id): void
    {
        $this->infoBannerRepository->deleteLine($id);
    }
}
