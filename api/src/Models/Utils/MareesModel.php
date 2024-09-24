<?php

namespace App\Models\Utils;

use App\Models\Model;
use App\Core\Constants;

class MareesModel extends Model
{
    private $redis_ns = "marees";

    /**
     * Récupère les marées en fonction du filtre.
     * 
     * @return array Toutes les marées récupérées.
     */
    public function read(array $filter = []): array
    {
        $startDate = $filter["debut"] ?? "0001-01-01";
        $endDate = $filter["fin"] ?? "9999-12-31";

        $datesHash = md5($startDate . $endDate);

        // Redis
        $tides = json_decode($this->redis->get($this->redis_ns . ":" . $datesHash));

        if (!$tides) {
            $statement = "SELECT * FROM marees WHERE `date` BETWEEN :debut AND :fin";

            $request = $this->mysql->prepare($statement);
            $request->execute([
                "debut" => $startDate,
                "fin" => $endDate
            ]);
            $tides = $request->fetchAll();

            for ($i = 0; $i < count($tides); $i++) {
                $tides[$i]["heure"] = substr($tides[$i]["heure"], 0, -3);
                $tides[$i]["te_cesson"] = (float) $tides[$i]["te_cesson"];
                $tides[$i]["te_bassin"] = (float) $tides[$i]["te_bassin"];
            }

            if (!empty($tides)) {
                $this->redis->setex($this->redis_ns . ":" . $datesHash, Constants::ONE_WEEK, json_encode($tides));
            }
        }

        return $tides;
    }

    /**
     * Récupère les marées d'une année.
     * 
     * @return array Les marées de l'année.
     */
    public function readYear(int $annee): array
    {
        // Redis
        $tidesOfYear = json_decode($this->redis->get($this->redis_ns . ":" . $annee));

        if (!$tidesOfYear) {
            $statement = "SELECT * FROM marees WHERE SUBSTRING(date, 1, 4) = :annee";

            $request = $this->mysql->prepare($statement);
            $request->execute(["annee" => $annee]);
            $tidesOfYear = $request->fetchAll();

            for ($i = 0; $i < count($tidesOfYear); $i++) {
                $tidesOfYear[$i]["heure"] = substr($tidesOfYear[$i]["heure"], 0, -3);
                $tidesOfYear[$i]["te_cesson"] = (float) $tidesOfYear[$i]["te_cesson"];
                $tidesOfYear[$i]["te_bassin"] = (float) $tidesOfYear[$i]["te_bassin"];
            }

            if (!empty($tidesOfYear)) {
                $this->redis->set($this->redis_ns . ":" . $annee, json_encode($tidesOfYear));
            }
        }

        return $tidesOfYear;
    }

    /**
     * Récupère toutes les années des marées.
     * 
     * @return array Toutes les années récupérées.
     */
    public function readYears(): array
    {
        // Redis
        $years = json_decode($this->redis->get($this->redis_ns . ":annees"));

        if (!$years) {
            $statement = "SELECT DISTINCT SUBSTRING(date, 1, 4) AS annee FROM `utils_marees_shom`";

            $years = $this->mysql->query($statement)->fetchAll();

            for ($i = 0; $i < count($years); $i++) {
                $years[$i] = $years[$i]["annee"];
            }

            $this->redis->set($this->redis_ns . ":annees", json_encode($years));
        }

        return $years;
    }

    /**
     * Ajoute des marées.
     */
    public function create(array $tides): void
    {
        $statement = "INSERT INTO utils_marees_shom VALUES(:date, :heure, :hauteur)";

        $request = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        foreach ($tides as [$date, $time, $height]) {
            $request->execute([
                "date" => $date,
                "heure" => $time,
                "hauteur" => $height
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
     * @throws \RedisException 
     */
    private function invalidateRedis()
    {
        $keys = $this->redis->keys($this->redis_ns . ":*");

        $this->redis->multi();

        foreach ($keys as $key) {
            $this->redis->del($key);
        }

        $this->redis->exec();
    }
}
