<?php

namespace App\Models\Consignation;

use App\Models\Model;

class ListeMarchandisesModel extends Model
{
    /**
     * Récupère la liste des marchandises utilisées en consignation.
     * 
     * @return array Liste des marchandises.
     */
    public function readAll()
    {
        $statement =
            "SELECT DISTINCT marchandise
            FROM consignation_escales_marchandises
            WHERE marchandise IS NOT NULL
            AND marchandise <> ''
            ORDER BY marchandise ASC";

        $request = $this->mysql->query($statement);

        $cargoesNames = $request->fetchAll(\PDO::FETCH_COLUMN);

        return $cargoesNames;
    }
}
