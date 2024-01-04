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
            ORDER BY marchandise ASC";

        $requete = $this->mysql->query($statement);

        $marchandises = $requete->fetchAll(\PDO::FETCH_COLUMN);

        $donnees = $marchandises;

        return $donnees;
    }
}
