<?php

// Path: api/src/Repository/TideRepository.php

namespace App\Repository;

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

            $requete = $this->mysql->prepare($statement);
            $requete->execute([
                "start" => $start,
                "end" => $end,
            ]);
            $tides = $requete->fetchAll();

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
    public function fetchTidesByYear(int $annee): array
    {
        // Redis
        $marees = json_decode($this->redis->get($this->redisNamespace . ":" . $annee));

        if (!$marees) {
            $statement =
                "SELECT *
          FROM marees
          WHERE SUBSTRING(date, 1, 4) = :annee";

            $requete = $this->mysql->prepare($statement);
            $requete->execute(["annee" => $annee]);
            $marees = $requete->fetchAll();

            for ($i = 0; $i < count($marees); $i++) {
                $marees[$i]["heure"] = substr($marees[$i]["heure"], 0, -3);
                $marees[$i]["te_cesson"] = (float) $marees[$i]["te_cesson"];
                $marees[$i]["te_bassin"] = (float) $marees[$i]["te_bassin"];
            }

            if (!empty($marees)) {
                $this->redis->set($this->redisNamespace . ":" . $annee, json_encode($marees));
            }
        }

        $donnees = $marees;

        return $donnees;
    }

    /**
     * Récupère toutes les années des marées.
     * 
     * @return array Toutes les années récupérées.
     */
    public function fetchYears(): array
    {
        // Redis
        $annees = json_decode($this->redis->get($this->redisNamespace . ":annees"));

        if (!$annees) {
            $statement =
                "SELECT DISTINCT SUBSTRING(date, 1, 4) AS annee
          FROM `utils_marees_shom`";

            $annees = $this->mysql->query($statement)->fetchAll();

            for ($i = 0; $i < count($annees); $i++) {
                $annees[$i] = $annees[$i]["annee"];
            }

            $this->redis->set($this->redisNamespace . ":annees", json_encode($annees));
        }

        $donnees = $annees;

        return $donnees;
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
        $success = $request->execute(["annee" => $annee]);

        $this->invalidateRedis();

        return $success;
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
