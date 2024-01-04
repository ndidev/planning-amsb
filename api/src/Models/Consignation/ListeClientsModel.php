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
            ORDER BY client ASC";

        $requete = $this->mysql->query($statement);

        $clients = $requete->fetchAll(\PDO::FETCH_COLUMN);

        $donnees = $clients;

        return $donnees;
    }
}
