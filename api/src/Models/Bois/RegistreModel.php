<?php

namespace App\Models\Bois;

use App\Models\Model;
use App\Core\DateUtils;
use \DateTime;

class RegistreModel extends Model
{
    /**
     * Récupère tous les RDV bois.
     * 
     * @param array $filter Filtre qui contient...
     */
    public function readAll(array $filter): array
    {
        $defaultStartDate = DateUtils::format(DateUtils::SQL_DATE, DateUtils::previousWorkingDay(new DateTime()));
        $defaultEndDate = date("Y-m-d");

        // Filtre
        $startDate = isset($filter['date_debut'])
            ? ($filter['date_debut'] ?: $defaultStartDate)
            : $defaultStartDate;

        $endDate = isset($filter['date_fin'])
            ? ($filter['date_fin'] ?: $defaultEndDate)
            : $defaultEndDate;

        $statement =
            "SELECT
              p.date_rdv,
              f.nom_court AS fournisseur,
              c.nom_court AS chargement_nom,
              c.ville AS chargement_ville,
              cpays.nom AS chargement_pays,
              l.nom_court AS livraison_nom,
              l.cp AS livraison_cp,
              l.ville AS livraison_ville,
              lpays.nom AS livraison_pays,
              p.numero_bl,
              t.nom_court AS transporteur
            FROM bois_planning p
            LEFT JOIN tiers AS c ON p.chargement = c.id
            LEFT JOIN tiers AS l ON p.livraison = l.id
            LEFT JOIN tiers AS a ON p.affreteur = a.id
            LEFT JOIN tiers AS f ON p.fournisseur = f.id
            LEFT JOIN tiers AS t ON p.transporteur = t.id
            LEFT JOIN utils_pays cpays ON c.pays = cpays.iso
            LEFT JOIN utils_pays lpays ON l.pays = lpays.iso
            WHERE a.lie_agence = 1
            AND date_rdv
            BETWEEN :date_debut
            AND :date_fin
            AND attente = 0
            ORDER BY
              date_rdv,
              numero_bl";

        $request = $this->mysql->prepare($statement);

        $request->execute([
            "date_debut" => $startDate,
            "date_fin" => $endDate
        ]);

        $registryLines = $request->fetchAll();

        return $registryLines;
    }
}
