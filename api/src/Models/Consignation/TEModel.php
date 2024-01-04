<?php

namespace App\Models\Consignation;

use App\Models\Model;

class TEModel extends Model
{
    /**
     * Récupère tous les tirants d'eau du planning consignation.
     * 
     * @return array Tous les tirants d'eau récupérés
     */
    public function readAll()
    {
        $statement = "SELECT * FROM drafts_par_tonnage";

        $donnees = $this->mysql->query($statement)->fetchAll();

        return $donnees;
    }
}
