<?php

// Path: api/src/Repository/TideRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\TidesDTO;
use const App\Core\Component\Constants\ONE_WEEK;

/**
 * Repository for the tide data.
 */
final class TideRepository extends Repository
{
    private string $redisNamespace = "tides";

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
        $redisValue = $this->redis->get($this->redisNamespace . ":" . $datesHash);
        $tidesArray = is_string($redisValue) ? json_decode($redisValue, true) : null;

        if (!$tidesArray || !is_array($tidesArray)) {
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
            $tidesArray = $request->fetchAll();

            if (!empty($tidesArray)) {
                $this->redis->setex(
                    $this->redisNamespace . ":" . $datesHash,
                    ONE_WEEK,
                    json_encode($tidesArray)
                );
            }
        }

        $tidesDTO = new TidesDTO($tidesArray);

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
        $redisValue = $this->redis->get($this->redisNamespace . ":" . $year);
        $tides = is_string($redisValue) ? json_decode($redisValue, true) : null;

        if (!$tides || !is_array($tides)) {
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
        $redisValue = $this->redis->get($this->redisNamespace . ":years");
        $years = is_string($redisValue) ? json_decode($redisValue) : null;

        if (!is_array($years)) {
            $statement = "SELECT DISTINCT SUBSTRING(date, 1, 4) FROM `utils_marees_shom`";

            $yearsRequest = $this->mysql->query($statement);

            if (!$yearsRequest) {
                throw new DBException("Impossible de récupérer les années des marées.");
            }

            /** @var string[] */
            $years = $yearsRequest->fetchAll(\PDO::FETCH_COLUMN);

            $this->redis->set($this->redisNamespace . ":years", json_encode($years));
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
     */
    public function delete(int $year): void
    {
        $request = $this->mysql->prepare("DELETE FROM utils_marees_shom WHERE SUBSTRING(date, 1, 4) = :year");
        $isDeleted = $request->execute(["year" => $year]);

        if (!$isDeleted) {
            throw new DBException("Erreur lors de la suppression");
        }

        $this->invalidateRedis();
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
