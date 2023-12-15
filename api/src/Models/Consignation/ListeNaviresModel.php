<?php

namespace Api\Models\Consignation;

use Api\Utils\BaseModel;

class ListeNaviresModel extends BaseModel
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
                ORDER BY navire ASC";

        $requete = $this->mysql->query($statement);

        $navires = $requete->fetchAll(\PDO::FETCH_COLUMN);

        $donnees = $navires;

        return $donnees;
    }
}
