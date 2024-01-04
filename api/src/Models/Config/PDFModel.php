<?php

namespace App\Models\Config;

use App\Core\PDF\PDFUtils;
use App\Core\DateUtils;

class PDFModel
{
    public function visualiser(array $query): string
    {
        $module = $query["module"];
        $fournisseur = (int) $query["fournisseur"];
        $date_debut = DateUtils::convertDate($query["date_debut"]);
        $date_fin = DateUtils::convertDate($query["date_fin"]);

        $pdf = PDFUtils::generatePDF(
            $module,
            $fournisseur,
            $date_debut,
            $date_fin
        );

        $donnees = PDFUtils::stringifyPDF($pdf);

        return $donnees;
    }

    /**
     * Envoi un PDF par e-mail.
     * 
     * @param array $query Données de l'envoi.
     * 
     * @return array Résultat de l'envoi.
     */
    public function envoyer(array $query): array
    {
        $module = $query["module"];
        $fournisseur = $query["fournisseur"];
        $liste_emails = $query["liste_emails"];
        $date_debut = DateUtils::convertDate($query["date_debut"]);
        $date_fin = DateUtils::convertDate($query["date_fin"]);

        $pdf = PDFUtils::generatePDF(
            $module,
            (int) $fournisseur,
            $date_debut,
            $date_fin
        );

        $resultat = PDFUtils::sendPDF(
            $pdf,
            $module,
            (int) $fournisseur,
            $liste_emails,
            $date_debut,
            $date_fin
        );

        return $resultat;
    }
}
