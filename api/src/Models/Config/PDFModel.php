<?php

namespace Api\Models\Config;

use Api\Utils\PDFUtils;
use Api\Utils\DateUtils;

class PDFModel
{
  public function visualiser(array $query): string
  {
    $module = $query["module"];
    $fournisseur = (int) $query["fournisseur"];
    $date_debut = DateUtils::convertirDate($query["date_debut"]);
    $date_fin = DateUtils::convertirDate($query["date_fin"]);

    $pdf = PDFUtils::genererPDF(
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
    $date_debut = DateUtils::convertirDate($query["date_debut"]);
    $date_fin = DateUtils::convertirDate($query["date_fin"]);

    $pdf = PDFUtils::genererPDF(
      $module,
      (int) $fournisseur,
      $date_debut,
      $date_fin
    );

    $resultat = PDFUtils::envoyerPDF(
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
