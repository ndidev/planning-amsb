<?php

// Path: api/src/Repository/TideRepository.php

namespace App\Repository;

use const App\Core\Component\Constants\ONE_WEEK;

class TideRepository extends Repository
{
    private $redisNamespace = "marees";

    /**
     * Retrieves tides based on the filter.
     * 
     * @param string|null $start Start date.
     * @param string|null $end   End date.
     * 
     * @return array All retrieved tides.
     */
    public function fetchTides(?string $start, ?string $end): array
    {
        $start ??= "0001-01-01";
        $end ??= "9999-12-31";

        $datesHash = md5($start . $end);

        // Redis
        $tides = json_decode($this->redis->get($this->redisNamespace . ":" . $datesHash));

        if (!$tides) {
            $statement = "SELECT * FROM marees m WHERE m.date BETWEEN :start AND :end";

            $request = $this->mysql->prepare($statement);
            $request->execute([
                "start" => $start,
                "end" => $end,
            ]);
            $tides = $request->fetchAll();

            for ($i = 0; $i < count($tides); $i++) {
                $tides[$i]["heure"] = substr($tides[$i]["heure"], 0, -3);
                $tides[$i]["te_cesson"] = (float) $tides[$i]["te_cesson"];
                $tides[$i]["te_bassin"] = (float) $tides[$i]["te_bassin"];
            }

            if (!empty($tides)) {
                $this->redis->setex($this->redisNamespace . ":" . $datesHash, ONE_WEEK, json_encode($tides));
            }
        }

        return $tides;
    }

    /**
     * Récupère les marées d'une année.
     * 
     * @return array Les marées de l'année.
     */
    public function fetchTidesByYear(int $year): array
    {
        // Redis
        $tides = json_decode($this->redis->get($this->redisNamespace . ":" . $year));

        if (!$tides) {
            $statement = "SELECT * FROM marees WHERE SUBSTRING(date, 1, 4) = :year";

            $request = $this->mysql->prepare($statement);
            $request->execute(["year" => $year]);
            $tides = $request->fetchAll();

            for ($i = 0; $i < count($tides); $i++) {
                $tides[$i]["heure"] = substr($tides[$i]["heure"], 0, -3);
                $tides[$i]["te_cesson"] = (float) $tides[$i]["te_cesson"];
                $tides[$i]["te_bassin"] = (float) $tides[$i]["te_bassin"];
            }

            if (!empty($tides)) {
                $this->redis->set($this->redisNamespace . ":" . $year, json_encode($tides));
            }
        }

        return $tides;
    }

    /**
     * Récupère toutes les années des marées.
     * 
     * @return array Toutes les années récupérées.
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
     * @param int $annee Année pour laquelle supprimer les marées.
     * 
     * @return bool `true` si succès, `false` sinon
     */
    public function delete(int $annee): bool
    {
        $request = $this->mysql->prepare("DELETE FROM utils_marees_shom WHERE SUBSTRING(date, 1, 4) = :annee");
        $isDeleted = $request->execute(["annee" => $annee]);

        $this->invalidateRedis();

        return $isDeleted;
    }

    /**
     * Supprime les données des marées de Redis.
     * 
     * @throws RedisException 
     */
    private function invalidateRedis()
    {
        $keys = $this->redis->keys($this->redisNamespace . ":*");

        $this->redis->multi();

        foreach ($keys as $key) {
            $this->redis->del($key);
        }

        $this->redis->exec();
    }
}
