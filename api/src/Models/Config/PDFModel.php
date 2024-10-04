<?php

namespace App\Models\Config;

use App\Core\Component\Module;
use App\Core\PDF\PDFUtils;
use App\Core\DateUtils;

class PDFModel
{
    public function getPdfAsString(array $query): string
    {
        $module = Module::tryFrom($query["module"]);
        $supplierId = (int) $query["fournisseur"];
        $startDate = DateUtils::convertDate($query["date_debut"]);
        $endDate = DateUtils::convertDate($query["date_fin"]);

        $pdf = PDFUtils::generatePDF(
            $module,
            $supplierId,
            $startDate,
            $endDate
        );

        $pdfAsString = PDFUtils::stringifyPDF($pdf);

        return $pdfAsString;
    }

    /**
     * Envoi un PDF par e-mail.
     * 
     * @param array $query Données de l'envoi.
     * 
     * @return array Résultat de l'envoi.
     */
    public function sendPdfFileByEmail(array $query): array
    {
        $module = Module::tryFrom($query["module"]);
        $supplierId = $query["fournisseur"];
        $emailList = $query["liste_emails"];
        $startDate = DateUtils::convertDate($query["date_debut"]);
        $endDate = DateUtils::convertDate($query["date_fin"]);

        $pdf = PDFUtils::generatePDF(
            $module,
            (int) $supplierId,
            $startDate,
            $endDate
        );

        $sendResult = PDFUtils::sendPDF(
            $pdf,
            $module,
            (int) $supplierId,
            $emailList,
            $startDate,
            $endDate
        );

        return $sendResult;
    }
}
