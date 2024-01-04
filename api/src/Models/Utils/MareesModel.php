<?php

namespace App\Models\Utils;

use App\Models\Model;
use RedisException;

class MareesModel extends Model
{
    private $redis_ns = "marees";

    /**
     * Récupère les marées en fonction du filtre.
     * 
     * @return array Toutes les marées récupérées.
     */
    public function read(array $filtre = []): array
    {
        $debut = $filtre["debut"] ?? "0001-01-01";
        $fin = $filtre["fin"] ?? "9999-12-31";

        $request_hash = md5($debut . $fin);

        // Redis
        $marees = json_decode($this->redis->get($this->redis_ns . ":" . $request_hash));

        if (!$marees) {
            $statement =
                "SELECT *
          FROM marees m
          WHERE m.date BETWEEN :debut AND :fin";

            $requete = $this->mysql->prepare($statement);
            $requete->execute([
                "debut" => $debut,
                "fin" => $fin
            ]);
            $marees = $requete->fetchAll();

            for ($i = 0; $i < count($marees); $i++) {
                $marees[$i]["heure"] = substr($marees[$i]["heure"], 0, -3);
                $marees[$i]["te_cesson"] = (float) $marees[$i]["te_cesson"];
                $marees[$i]["te_bassin"] = (float) $marees[$i]["te_bassin"];
            }

            if (!empty($marees)) {
                $this->redis->setex($this->redis_ns . ":" . $request_hash, ONE_WEEK, json_encode($marees));
            }
        }

        $donnees = $marees;

        return $donnees;
    }

    /**
     * Récupère les marées d'une année.
     * 
     * @return array Les marées de l'année.
     */
    public function readYear(int $annee): array
    {
        // Redis
        $marees = json_decode($this->redis->get($this->redis_ns . ":" . $annee));

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
                $this->redis->set($this->redis_ns . ":" . $annee, json_encode($marees));
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
    public function readYears(): array
    {
        // Redis
        $annees = json_decode($this->redis->get($this->redis_ns . ":annees"));

        if (!$annees) {
            $statement =
                "SELECT DISTINCT SUBSTRING(date, 1, 4) AS annee
          FROM `utils_marees_shom`";

            $annees = $this->mysql->query($statement)->fetchAll();

            for ($i = 0; $i < count($annees); $i++) {
                $annees[$i] = $annees[$i]["annee"];
            }

            $this->redis->set($this->redis_ns . ":annees", json_encode($annees));
        }

        $donnees = $annees;

        return $donnees;
    }

    /**
     * Ajoute des marées.
     */
    public function create(array $marees): void
    {
        $statement = "INSERT INTO utils_marees_shom
      VALUES(
        :date,
        :heure,
        :hauteur
      )
    ";

        $requete = $this->mysql->prepare($statement);

        $this->mysql->beginTransaction();
        foreach ($marees as [$date, $heure, $hauteur]) {
            $requete->execute([
                "date" => $date,
                "heure" => $heure,
                "hauteur" => $hauteur
            ]);
        }
        $this->mysql->commit();

        $this->invalidate_redis();
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
        $requete = $this->mysql->prepare("DELETE FROM utils_marees_shom WHERE SUBSTRING(date, 1, 4) = :annee");
        $succes = $requete->execute(["annee" => $annee]);

        $this->invalidate_redis();

        return $succes;
    }

    /**
     * Supprime les données des marées de Redis.
     * 
     * @throws RedisException 
     */
    private function invalidate_redis()
    {
        $keys = $this->redis->keys($this->redis_ns . ":*");

        $this->redis->multi();

        foreach ($keys as $key) {
            $this->redis->del($key);
        }

        $this->redis->exec();
    }
}
