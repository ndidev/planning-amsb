<?php

namespace App\Models\Consignation;

use App\Models\Model;

class NumVoyageModel extends Model
{
    /**
     * Récupère un numéro de voyage pour un navire.
     * 
     * @param array $input
     * 
     * @return array Nouveau numéro de voyage
     */
    public function read($input)
    {
        extract($input);
        // $navire
        // $id

        // Si un id d'escale est fourni, récupérer le dernier numéro de voyage
        // de l'escale précédente :
        //  - id !== id fourni
        //  - eta <= eta de l'id fourni
        //  - etc <= etc de l'id fourni (permet de gérer les escale avec backload)
        $sql = $id === ""
            ? ""
            : " AND NOT id = $id
                AND eta_date <= (SELECT eta_date FROM consignation_planning WHERE id = $id)
                AND eta_date <= COALESCE((SELECT eta_date FROM consignation_planning WHERE id = $id), '9999-12-31')";

        $statement =
            "SELECT voyage
            FROM consignation_planning
            WHERE navire = :navire
            $sql
            ORDER BY eta_date DESC, etc_date DESC
            LIMIT 1";

        $reponse_voyage = $this->mysql->prepare($statement);
        $reponse_voyage->execute(["navire" => $navire]);
        $voyage = $reponse_voyage->fetch()["voyage"] ?? NULL;

        $donnees = [
            "voyage" => $voyage
        ];

        return $donnees;
    }
}
