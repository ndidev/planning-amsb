<?php

namespace App\Models\Consignation;

use App\Models\Model;

class ListeClientsModel extends Model
{
    /**
     * Récupère la liste des clients en consignation.
     * 
     * @return array Liste des clients.
     */
    public function readAll()
    {
        $statement =
            "SELECT DISTINCT client
            FROM consignation_escales_marchandises
            WHERE client IS NOT NULL
            AND client <> ''
            ORDER BY client ASC";

        $request = $this->mysql->query($statement);

        $customersNames = $request->fetchAll(\PDO::FETCH_COLUMN);

        return $customersNames;
    }
}
