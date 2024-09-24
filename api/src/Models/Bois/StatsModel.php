<?php

namespace App\Models\Bois;

use App\Models\Model;

class StatsModel extends Model
{
    /**
     * Récupère les stats bois.
     * 
     * @param array $filter Filtre qui contient...
     */
    public function readAll(array $filter): array
    {
        // Filtre
        // $date_debut = isset($filtre['date_debut']) ? ($filtre['date_debut'] ?: date("Y-m-d")) : date("Y-m-d");
        $startDate = isset($filter['date_debut']) ? ($filter['date_debut'] ?: "0001-01-01") : "0001-01-01";
        $endDate = isset($filter["date_fin"]) ? ($filter['date_fin'] ?: "9999-12-31") : "9999-12-31";
        $supplierFilter = trim($filter['fournisseur'] ?? "", ",");
        $clientFilter = trim($filter['client'] ?? "", ",");
        $loadingFilter = trim($filter['chargement'] ?? "", ",");
        $deliveryFilter = trim($filter['livraison'] ?? "", ",");
        $transportFilter = trim($filter['transporteur'] ?? "", ",");
        $chartererFilter = trim($filter['affreteur'] ?? "", ",");

        $sqlSupplierFilter = $supplierFilter === "" ? "" : " AND fournisseur IN ($supplierFilter)";
        $sqlClientFilter = $clientFilter === "" ? "" : " AND client IN ($clientFilter)";
        $sqlLoadingFilter = $loadingFilter === "" ? "" : " AND chargement IN ($loadingFilter)";
        $sqlDeliveryFilter = $deliveryFilter === "" ? "" : " AND livraison IN ($deliveryFilter)";
        $sqlTransportFilter = $transportFilter === "" ? "" : " AND transporteur IN ($transportFilter)";
        $sqlChartererFilter = $chartererFilter === "" ? "" : " AND affreteur IN ($chartererFilter)";

        $sqlFilter =
            $sqlSupplierFilter
            . $sqlClientFilter
            . $sqlLoadingFilter
            . $sqlDeliveryFilter
            . $sqlTransportFilter
            . $sqlChartererFilter;

        $appointmentsStatement =
            "SELECT date_rdv as `date`
            FROM bois_planning
            WHERE date_rdv BETWEEN :startDate AND :endDate
            AND attente = 0
            $sqlFilter";


        $appointmentsRequest = $this->mysql->prepare($appointmentsStatement);

        $appointmentsRequest->execute([
            "startDate" => $startDate,
            "endDate" => $endDate
        ]);

        $appointments = $appointmentsRequest->fetchAll();

        $stats = [
            "Total" => 0,
            "Par année" => [],
        ];

        $yearTemplate = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            10 => 0,
            11 => 0,
            12 => 0,
        ];

        // Compilation du nombre de RDV par année et par mois
        foreach ($appointments as $appointment) {
            $date = explode("-", $appointment["date"]);
            $year = $date[0];
            $month = $date[1];

            if (!array_key_exists($year, $stats["Par année"])) {
                $stats["Par année"][$year] = $yearTemplate;
            };

            $stats["Total"]++;
            $stats["Par année"][$year][(int) $month]++;
        }

        return $stats;
    }
}
