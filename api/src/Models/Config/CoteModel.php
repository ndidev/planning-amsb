<?php

namespace App\Models\Config;

use App\Models\Model;

class CoteModel extends Model
{
    /**
     * Récupère toutes les côtes.
     * 
     * @return array Toutes les côtes récupérées.
     */
    public function readAll(): array
    {
        $statement = "SELECT * FROM config_cotes";

        $chartData = $this->mysql->query($statement)->fetchAll();

        for ($i = 0; $i < count($chartData); $i++) {
            $chartData[$i]["valeur"] = (float) $chartData[$i]["valeur"];
        }

        return $chartData;
    }

    /**
     * Récupère une côte.
     * 
     * @param string $cote Nom de la côte à récupérer
     * 
     * @return array Côte récupérée
     */
    public function read(string $nom_cote): ?array
    {
        $statement = "SELECT * FROM config_cotes WHERE cote = :cote";

        $request = $this->mysql->prepare($statement);
        $request->execute(["cote" => $nom_cote]);
        $chartDatum = $request->fetch();

        if (!$chartDatum) return null;

        $chartDatum["valeur"] = (float) $chartDatum["valeur"];

        return $chartDatum;
    }

    /**
     * Met à jour une côte.
     * 
     * @param string $cote  Nom de la côte à modifier
     * @param array  $input Valeur de la côte à modifier
     * 
     * @return array Côte modifiée
     */
    public function update(string $cote, array $input): array
    {
        $statement = "UPDATE config_cotes SET valeur = :valeur WHERE cote = :cote";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            'valeur' => (float) $input["valeur"],
            'cote' => $cote
        ]);

        return $this->read($cote);
    }
}
