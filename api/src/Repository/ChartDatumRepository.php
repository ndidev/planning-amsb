<?php

// Path: api/src/Repository/ChartDatumRepository.php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\ChartDatum;
use App\Service\ChartDatumService;

final class ChartDatumRepository extends Repository
{
    public function datumExists(string $name): bool
    {
        return $this->mysql->exists('config_cotes', $name, 'cote');
    }

    /**
     * Récupère toutes les côtes.
     * 
     * @return Collection<ChartDatum> Toutes les côtes récupérées.
     */
    public function fetchAllData(): Collection
    {
        $statement = "SELECT * FROM config_cotes";

        $chartDataRequest = $this->mysql->query($statement);

        if (!$chartDataRequest) {
            throw new DBException("Impossible de récupérer les côtes.");
        }

        $chartDataRaw = $chartDataRequest->fetchAll();

        $chartDatumService = new ChartDatumService();

        $chartData = array_map(
            fn(array $datum) => $chartDatumService->makeChartDatumFromDatabase($datum),
            $chartDataRaw
        );

        return new Collection($chartData);
    }

    /**
     * Récupère une côte.
     * 
     * @param string $name Nom de la côte à récupérer.
     * 
     * @return ChartDatum Côte récupérée.
     */
    public function fetchDatum(string $name): ?ChartDatum
    {
        $statement = "SELECT * FROM config_cotes WHERE cote = :cote";

        $request = $this->mysql->prepare($statement);
        $request->execute(["cote" => $name]);
        $chartDatumRaw = $request->fetch();

        if (!$chartDatumRaw) return null;

        $chartDatumService = new ChartDatumService();

        $chartDatum = $chartDatumService->makeChartDatumFromDatabase($chartDatumRaw);

        return $chartDatum;
    }

    /**
     * Met à jour une côte.
     * 
     * @param ChartDatum  $datum Eléments de la côte à modifier.
     * 
     * @return ChartDatum Côte modifiée.
     */
    public function updateDatumValue(ChartDatum $datum): ChartDatum
    {
        $statement = "UPDATE config_cotes SET valeur = :value WHERE cote = :name";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'value' => $datum->getValue(),
            'name' => $datum->getName(),
        ]);

        /** @var ChartDatum */
        $updatedDatum = $this->fetchDatum($datum->getName());

        return $updatedDatum;
    }
}
