<?php

namespace App\Models\Consignation;

use App\Models\Model;

class StatsModel extends Model
{
    /**
     * Récupère les stats consignation.
     * 
     * @param array $filtre Filtre qui contient...
     * 
     * @return array Stats consignation.
     */
    public function readAll(array $filtre): array
    {
        // Filtre
        $startDate = isset($filtre['date_debut']) ? ($filtre['date_debut'] ?: "0001-01-01") : "0001-01-01";
        $endDate = isset($filtre["date_fin"]) ? ($filtre['date_fin'] ?: "9999-12-31") : "9999-12-31";
        $shipFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $filtre['navire'] ?? ""), ",");
        $shipOwnerFilter = trim($filtre['armateur'] ?? "", ",");
        $cargoFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $filtre['marchandise'] ?? ""), ",");
        $customerFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $filtre['client'] ?? ""), ",");
        $lastPortFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $filtre['last_port'] ?? ""), ",");
        $nextPortFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $filtre['next_port'] ?? ""), ",");

        $sqlShipFilter = $shipFilter === "" ? "" : " AND cp.navire IN ($shipFilter)";
        $sqlShipOwnerFilter = $shipOwnerFilter === "" ? "" : " AND cp.armateur IN ($shipOwnerFilter)";
        $sqlCargoFilter = $cargoFilter === "" ? "" : " AND cem.marchandise IN ($cargoFilter)";
        $sqlCustomerFilter = $customerFilter === "" ? "" : " AND cem.client IN ($customerFilter)";
        $sqlLastPortFilter = $lastPortFilter === "" ? "" : " AND cp.last_port IN ($lastPortFilter)";
        $sqlNextPortFilter = $nextPortFilter === "" ? "" : " AND cp.next_port IN ($nextPortFilter)";

        $sqlFilter =
            $sqlShipFilter
            . $sqlCargoFilter
            . $sqlShipOwnerFilter
            . $sqlCustomerFilter
            . $sqlLastPortFilter
            . $sqlNextPortFilter;

        $callsStatement =
            "SELECT
                cp.id,
                cp.etc_date as `date`
            FROM consignation_planning cp
            LEFT JOIN consignation_escales_marchandises cem ON cem.escale_id = cp.id
            WHERE cp.etc_date BETWEEN :date_debut AND :date_fin
            $sqlFilter
            GROUP BY cp.id";

        $callsRequest = $this->mysql->prepare($callsStatement);

        $callsRequest->execute([
            "date_debut" => $startDate,
            "date_fin" => $endDate
        ]);

        $calls = $callsRequest->fetchAll();

        $stats = [
            "Total" => count($calls),
            "Par année" => [],
        ];

        $yearTemplate = [
            1 => ["nombre" => 0, "ids" => []],
            2 => ["nombre" => 0, "ids" => []],
            3 => ["nombre" => 0, "ids" => []],
            4 => ["nombre" => 0, "ids" => []],
            5 => ["nombre" => 0, "ids" => []],
            6 => ["nombre" => 0, "ids" => []],
            7 => ["nombre" => 0, "ids" => []],
            8 => ["nombre" => 0, "ids" => []],
            9 => ["nombre" => 0, "ids" => []],
            10 => ["nombre" => 0, "ids" => []],
            11 => ["nombre" => 0, "ids" => []],
            12 => ["nombre" => 0, "ids" => []],
        ];

        // Compilation du nombre de RDV par année et par mois
        foreach ($calls as $call) {
            $date = explode("-", $call["date"]);
            $year = $date[0];
            $month = $date[1];

            if (!array_key_exists($year, $stats["Par année"])) {
                $stats["Par année"][$year] = $yearTemplate;
            };

            // $stats["Total"]++;
            $stats["Par année"][$year][(int) $month]["nombre"]++;
            $stats["Par année"][$year][(int) $month]["ids"][] = $call["id"];
        }

        return $stats;
    }

    /**
     * Récupère les détails des stats consignation.
     * 
     * @param string $ids Identifiants des escales.
     * 
     * @return array Détails des stats.
     */
    public function readDetails(string $ids): array
    {
        // Filtre
        $idsFilter = trim(preg_replace("/([^,]+),?/", "'$1',", $ids ?? ""), ",");

        $callsStatement =
            "SELECT
                cp.id,
                cp.navire,
                cp.ops_date,
                cp.etc_date,
                IFNULL(cem.marchandise, '') as marchandise,
                IFNULL(cem.client, '') as client,
                cem.tonnage_bl,
                cem.tonnage_outturn,
                cem.cubage_bl,
                cem.cubage_outturn,
                cem.nombre_bl,
                cem.nombre_outturn
            FROM consignation_planning cp
            LEFT JOIN consignation_escales_marchandises cem ON cem.escale_id = cp.id
            WHERE cp.id IN ($idsFilter)
            ORDER BY cp.etc_date DESC";

        $callsRequest = $this->mysql->query($callsStatement);

        $calls = $callsRequest->fetchAll();

        $groupedCalls = [];

        // Grouper par escale
        foreach ($calls as $call) {
            if (!array_key_exists($call["id"], $groupedCalls)) {
                $groupedCalls[$call["id"]] = [
                    "id" => $call["id"],
                    "navire" => $call["navire"],
                    "ops_date" => $call["ops_date"],
                    "etc_date" => $call["etc_date"],
                    "marchandises" => [],
                ];
            }

            $groupedCalls[$call["id"]]["marchandises"][] = [
                "marchandise" => $call["marchandise"],
                "client" => $call["client"],
                "tonnage_outturn" => $call["tonnage_outturn"] ?: $call["tonnage_bl"],
                "cubage_outturn" => $call["cubage_outturn"] ?: $call["cubage_bl"],
                "nombre_outturn" => $call["nombre_outturn"] ?: $call["nombre_bl"],
            ];
        }

        return array_values($groupedCalls);
    }
}
