<?php

namespace App\Models\Consignation;

use App\Models\Model;

class ListeNaviresModel extends Model
{
    /**
     * Récupère la liste des tous les noms de navire.
     * 
     * @return array Liste des noms de navire.
     */
    public function readAll()
    {
        $statement =
            "SELECT DISTINCT navire
            FROM consignation_planning
            WHERE navire IS NOT NULL
            AND navire <> ''
            ORDER BY navire ASC";

        $requete = $this->mysql->query($statement);

        $navires = $requete->fetchAll(\PDO::FETCH_COLUMN);

        $donnees = $navires;

        return $donnees;
    }
}
