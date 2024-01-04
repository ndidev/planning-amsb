<?php

namespace App\Models\Consignation;

use App\Models\Model;

class NaviresEnActiviteModel extends Model
{
    /**
     * Récupère la liste des navires en activité entre deux dates.
     * 
     * @param array $query
     * 
     * @return array Liste des navires en activité.
     */
    public function readAll($query)
    {
        $date_debut = isset($query['date_debut']) ? ($query['date_debut'] ?: date("Y-m-d")) : date("Y-m-d");
        $date_fin = isset($query['date_fin']) ? ($query['date_fin'] ?: "9999-12-31") : "9999-12-31";

        $statement =
            "SELECT navire, ops_date AS debut, etc_date AS fin
            FROM consignation_planning
            WHERE ops_date <= :date_fin AND etc_date >= :date_debut";

        $requete = $this->mysql->prepare($statement);
        $requete->execute([
            "date_debut" => $date_debut,
            "date_fin" => $date_fin,
        ]);
        $navires = $requete->fetchAll();

        $donnees = $navires;

        return $donnees;
    }
}
