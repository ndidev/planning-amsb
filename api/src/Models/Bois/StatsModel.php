<?php

namespace App\Models\Bois;

use App\Models\Model;

class StatsModel extends Model
{
    /**
     * Récupère les stats bois.
     * 
     * @param array $filtre Filtre qui contient...
     */
    public function readAll(array $filtre): array
    {
        // Filtre
        // $date_debut = isset($filtre['date_debut']) ? ($filtre['date_debut'] ?: date("Y-m-d")) : date("Y-m-d");
        $date_debut = isset($filtre['date_debut']) ? ($filtre['date_debut'] ?: "0001-01-01") : "0001-01-01";
        $date_fin = isset($filtre["date_fin"]) ? ($filtre['date_fin'] ?: "9999-12-31") : "9999-12-31";
        $filtre_fournisseur = trim($filtre['fournisseur'] ?? "", ",");
        $filtre_client = trim($filtre['client'] ?? "", ",");
        $filtre_chargement = trim($filtre['chargement'] ?? "", ",");
        $filtre_livraison = trim($filtre['livraison'] ?? "", ",");
        $filtre_transporteur = trim($filtre['transporteur'] ?? "", ",");
        $filtre_affreteur = trim($filtre['affreteur'] ?? "", ",");

        $filtre_sql_fournisseur = $filtre_fournisseur === "" ? "" : " AND fournisseur IN ($filtre_fournisseur)";
        $filtre_sql_client = $filtre_client === "" ? "" : " AND client IN ($filtre_client)";
        $filtre_sql_chargement = $filtre_chargement === "" ? "" : " AND chargement IN ($filtre_chargement)";
        $filtre_sql_livraison = $filtre_livraison === "" ? "" : " AND livraison IN ($filtre_livraison)";
        $filtre_sql_transporteur = $filtre_transporteur === "" ? "" : " AND transporteur IN ($filtre_transporteur)";
        $filtre_sql_affreteur = $filtre_affreteur === "" ? "" : " AND affreteur IN ($filtre_affreteur)";

        $filtre_sql =
            $filtre_sql_fournisseur
            . $filtre_sql_client
            . $filtre_sql_chargement
            . $filtre_sql_livraison
            . $filtre_sql_transporteur
            . $filtre_sql_affreteur;

        $statement_rdvs =
            "SELECT
          date_rdv as `date`
        FROM bois_planning
        WHERE date_rdv BETWEEN :date_debut AND :date_fin
        AND attente = 0
        $filtre_sql";


        $requete_rdvs = $this->mysql->prepare($statement_rdvs);

        $requete_rdvs->execute([
            "date_debut" => $date_debut,
            "date_fin" => $date_fin
        ]);

        $rdvs = $requete_rdvs->fetchAll();

        $stats = [
            "Total" => 0,
            "Par année" => [],
        ];

        $modele_annee = [
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
        foreach ($rdvs as $rdv) {
            $date = explode("-", $rdv["date"]);
            $annee = $date[0];
            $mois = $date[1];

            if (!array_key_exists($annee, $stats["Par année"])) {
                $stats["Par année"][$annee] = $modele_annee;
            };

            $stats["Total"]++;
            $stats["Par année"][$annee][(int) $mois]++;
        }

        $donnees = $stats;

        return $donnees;
    }
}
