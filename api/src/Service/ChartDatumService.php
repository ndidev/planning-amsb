<?php

// Path: api/src/Service/ChartDatumService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\ChartDatum;
use App\Repository\ChartDatumRepository;

/**
 * @phpstan-type ChartDatumArray array{
 *                                 cote?: string,
 *                                 affichage?: string,
 *                                 valeur?: float,
 *                               }
 */
final class ChartDatumService
{
    private ChartDatumRepository $chartDatumRepository;

    public function __construct()
    {
        $this->chartDatumRepository = new ChartDatumRepository();
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
            ->setValue((float) ($rawData['valeur'] ?? 0));

        return $chartDatum;
    }

    /**
     * @param array $rawData 
     * 
     * @phpstan-param ChartDatumArray $rawData
     * 
     * @return ChartDatum 
     */
    public function makeChartDatumFromForm(array $rawData): ChartDatum
    {
        $chartDatum = (new ChartDatum())
            ->setName($rawData['cote'] ?? '')
            ->setDisplayName($rawData['affichage'] ?? '')
            ->setValue((float) ($rawData['valeur'] ?? 0));

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
     * @param string $name 
     * @param array $rawData 
     * 
     * @phpstan-param ChartDatumArray $rawData
     * 
     * @return ChartDatum 
     */
    public function updateDatumValue(string $name, array $rawData): ChartDatum
    {
        $chartDatum = $this->makeChartDatumFromForm($rawData)->setName($name);

        return $this->chartDatumRepository->updateDatumValue($chartDatum);
    }
}
