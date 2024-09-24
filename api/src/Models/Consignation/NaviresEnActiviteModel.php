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
    public function readAll(array $query)
    {
        $startDate = isset($query['date_debut']) ? ($query['date_debut'] ?: date("Y-m-d")) : date("Y-m-d");
        $endDate = isset($query['date_fin']) ? ($query['date_fin'] ?: "9999-12-31") : "9999-12-31";

        $statement =
            "SELECT navire, ops_date AS debut, etc_date AS fin
            FROM consignation_planning
            WHERE ops_date <= :date_fin AND etc_date >= :date_debut";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            "date_debut" => $startDate,
            "date_fin" => $endDate,
        ]);
        $shipsInOps = $request->fetchAll();

        return $shipsInOps;
    }
}
