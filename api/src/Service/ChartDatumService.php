<?php

// Path: api/src/Service/ChartDatumService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\ChartDatum;
use App\Repository\ChartDatumRepository;

class ChartDatumService
{
    private ChartDatumRepository $chartDatumRepository;

    public function __construct()
    {
        $this->chartDatumRepository = new ChartDatumRepository();
    }

    public function makeChartDatumFromDatabase(array $rawData): ChartDatum
    {
        $chartDatum = (new ChartDatum())
            ->setName($rawData['cote'])
            ->setDisplayName($rawData['affichage'])
            ->setValue($rawData['valeur']);

        return $chartDatum;
    }

    public function makeChartDatumFromForm(array $rawData): ChartDatum
    {
        $chartDatum = (new ChartDatum())
            ->setName($rawData['cote'])
            ->setDisplayName($rawData['affichage'])
            ->setValue($rawData['valeur']);

        return $chartDatum;
    }

    public function datumExists(string $name): bool
    {
        return $this->chartDatumRepository->datumExists($name);
    }

    public function getAllData(): Collection
    {
        return $this->chartDatumRepository->fetchAllData();
    }

    public function getDatum(string $cote): ?ChartDatum
    {
        return $this->chartDatumRepository->fetchDatum($cote);
    }

    public function updateDatumValue($name, array $rawData): ChartDatum
    {
        $chartDatum = $this->makeChartDatumFromForm($rawData)->setName($name);

        return $this->chartDatumRepository->updateDatumValue($chartDatum);
    }
}
