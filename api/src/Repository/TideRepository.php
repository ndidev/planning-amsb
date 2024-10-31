<?php

// Path: api/src/Repository/TideRepository.php

namespace App\Repository;

use App\DTO\NewTidesDTO;
use App\DTO\TidesDTO;
use const App\Core\Component\Constants\ONE_WEEK;

/**
 * Repository for the tide data.
 */
final class TideRepository extends Repository
{
    private string $redisNamespace = "marees";

    /**
     * Retrieves tides based on the filter.
     * 
     * @param \DateTimeInterface $startDate The start date of the time range.
     * @param \DateTimeInterface $endDate   The end date of the time range.
     * 
     * @return TidesDTO All retrieved tides.
     */
    public function fetchTides(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): TidesDTO {
        $datesHash = md5($startDate->format('Y-m-d') . $endDate->format('Y-m-d'));

        // Redis
        $tides = json_decode($this->redis->get($this->redisNamespace . ":" . $datesHash));

        if (!$tides) {
            $statement = "SELECT * FROM marees m WHERE m.date BETWEEN :start AND :end";

            $request = $this->mysql->prepare($statement);
            $request->execute([
                "start" => $startDate->format('Y-m-d'),
                "end" => $endDate->format('Y-m-d'),
            ]);

            /**
             * @var list<array{
             *             date: string,
             *             heure: string,
             *             te_cesson: string,
             *             te_bassin: string
             *           }>
             */
            $tides = $request->fetchAll();

            if (!empty($tides)) {
                $this->redis->setex(
                    $this->redisNamespace . ":" . $datesHash,
                    ONE_WEEK,
                    json_encode($tides)
                );
            }
        }

        $tidesDTO = new TidesDTO($tides);

        return $tidesDTO;
    }

    /**
     * Récupère les marées d'une année.
     * 
     * @return TidesDTO Les marées de l'année.
     */
    public function fetchTidesByYear(int $year): TidesDTO
    {
        // Redis
        $tides = json_decode($this->redis->get($this->redisNamespace . ":" . $year));

        if (!$tides) {
            $statement = "SELECT * FROM marees WHERE SUBSTRING(date, 1, 4) = :year";

            $tidesRequest = $this->mysql->prepare($statement);
            $tidesRequest->execute(["year" => $year]);

            /**
             * @var list<array{
             *             date: string,
             *             heure: string,
             *             te_cesson: string,
             *             te_bassin: string
             *           }>
             */
            $tides = $tidesRequest->fetchAll();

            if (!empty($tides)) {
                $this->redis->set($this->redisNamespace . ":" . $year, json_encode($tides));
            }
        }

        $tidesDTO = new TidesDTO($tides);

        return $tidesDTO;
    }

    /**
     * Récupère toutes les années des marées.
     * 
     * @return string[] Toutes les années récupérées.
     */
    public function fetchYears(): array
    {
        // Redis
        $years = json_decode($this->redis->get($this->redisNamespace . ":annees"));

        if (!$years) {
            $statement = "SELECT DISTINCT SUBSTRING(date, 1, 4) AS annee FROM `utils_marees_shom`";

            $years = $this->mysql->query($statement)->fetchAll();

            for ($i = 0; $i < count($years); $i++) {
                $years[$i] = $years[$i]["annee"];
            }

            $this->redis->set($this->redisNamespace . ":annees", json_encode($years));
        }

        return $years;
    }

    /**
     * Add tides.
     * 
     * @param list<array{0: string, 1: string, 2: float}> $tides The tides to add.
     */
    public function addTides(array $tides): void
    {
        $statement = "INSERT INTO utils_marees_shom VALUES(:date, :time, :heightOfWater)";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        foreach ($tides as [$date, $time, $heightOfWater]) {
            $request->execute([
                "date" => $date,
                "time" => $time,
                "heightOfWater" => $heightOfWater
            ]);
        }
        $this->mysql->commit();

        $this->invalidateRedis();
    }

    /**
     * Supprime les marrées pour une année.
     * 
     * @param int $year Année pour laquelle supprimer les marées.
     * 
     * @return bool `true` si succès, `false` sinon
     */
    public function delete(int $year): bool
    {
        $request = $this->mysql->prepare("DELETE FROM utils_marees_shom WHERE SUBSTRING(date, 1, 4) = :year");
        $isDeleted = $request->execute(["year" => $year]);

        $this->invalidateRedis();

        return $isDeleted;
    }

    /**
     * Supprime les données des marées de Redis.
     * 
     * @throws \RedisException 
     */
    private function invalidateRedis(): void
    {
        $keys = $this->redis->keys($this->redisNamespace . ":*");

        $this->redis->multi();

        foreach ($keys as $key) {
            $this->redis->del($key);
        }

        $this->redis->exec();
    }
}
