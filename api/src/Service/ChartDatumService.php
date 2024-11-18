<?php

// Path: api/src/Service/ChartDatumService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\HTTP\HTTPRequestBody;
use App\Entity\ChartDatum;
use App\Repository\ChartDatumRepository;

/**
 * @phpstan-import-type ChartDatumArray from \App\Repository\ChartDatumRepository
 */
final class ChartDatumService
{
    private ChartDatumRepository $chartDatumRepository;

    public function __construct()
    {
        $this->chartDatumRepository = new ChartDatumRepository($this);
    }

    /**
     * @param array $rawData 
     * 
     * @phpstan-param ChartDatumArray $rawData
     * 
     * @return ChartDatum 
     */
    public function makeChartDatumFromDatabase(array $rawData): ChartDatum
    {
        $chartDatum = (new ChartDatum())
            ->setName($rawData['cote'] ?? '')
            ->setDisplayName($rawData['affichage'] ?? '')
            ->setValue((float) ($rawData['valeur'] ?? 0.0));

        return $chartDatum;
    }

    /**
     * @param HTTPRequestBody $rawData 
     * 
     * @return ChartDatum 
     */
    public function makeChartDatumFromForm(HTTPRequestBody $rawData): ChartDatum
    {
        $chartDatum = (new ChartDatum())
            ->setName($rawData->getString('cote'))
            ->setDisplayName($rawData->getString('affichage'))
            ->setValue($rawData->getFloat('valeur', 0));

        return $chartDatum;
    }

    public function datumExists(string $name): bool
    {
        return $this->chartDatumRepository->datumExists($name);
    }

    /**
     * @return Collection<ChartDatum>
     */
    public function getAllData(): Collection
    {
        return $this->chartDatumRepository->fetchAllData();
    }

    public function getDatum(string $cote): ?ChartDatum
    {
        return $this->chartDatumRepository->fetchDatum($cote);
    }

    /**
     * Updates a chart datum.
     * 
     * @param string          $name 
     * @param HTTPRequestBody $rawData 
     * 
     * @return ChartDatum 
     */
    public function updateDatumValue(string $name, HTTPRequestBody $rawData): ChartDatum
    {
        $chartDatum = $this->makeChartDatumFromForm($rawData)->setName($name);

        $chartDatum->validate();

        return $this->chartDatumRepository->updateDatumValue($chartDatum);
    }
}
