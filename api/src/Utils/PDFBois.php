<?php

namespace Api\Utils;

use Api\Utils\DateUtils;
use Api\Utils\PDFPlanning;
use DateTime;

class PDFBois extends PDFPlanning
{
  /**
   * Génère un PDF bois.
   * 
   * @param array    $fournisseur Infos sur le fournisseur.
   * @param array    $rdvs        RDVs à inclure dans le PDF.
   * @param DateTime $date_debut  Date de début des RDV.
   * @param DateTime $date_fin    Date de fin des RDV.
   * @param array    $agence      Infos sur l'agence.
   * 
   * @return void 
   * @throws Exception 
   */
  public function __construct(
    protected array $fournisseur,
    protected array $rdvs,
    protected DateTime $date_debut,
    protected DateTime $date_fin,
    protected array $agence
  ) {
    parent::__construct();

    $this->genererPDF();
  }

  protected function genererPDF()
  {
    $this->AliasNbPages();
    $this->AddPage();

    /**
     * Vérification de la présence de rendez-vous
     * S'il y a des RDV sur la période, peuplement des lignes
     * Sinon, affichage de "Aucun RDV"
     */
    if (count($this->rdvs["non_attente"]) > 0) {
      $date_precedente = "";

      foreach ($this->rdvs["non_attente"] as $rdv) {
        /**
         * @var string $date_rdv
         * @var string $client_id
         * @var string $client_nom
         * @var string $client_cp
         * @var string $client_ville
         * @var string $client_pays
         * @var string $livraison_id
         * @var string $livraison_nom
         * @var string $livraison_cp
         * @var string $livraison_ville
         * @var string $livraison_pays
         * @var string $affreteur_lie_agence
         * @var string $affreteur_nom
         * @var string $numero_bl
         * @var string $commentaire_public
         */
        extract($rdv);

        $date_mise_en_forme = DateUtils::format(DateUtils::DATE_FULL, $date_rdv);

        if ($client_pays === 'FR') {
          $client_departement = ' ' . substr($client_cp, 0, 2);
          $client_pays = '';
        } else {
          $client_departement = '';
          $client_pays = ' (' . $client_pays . ')';
        }

        if ($livraison_pays === 'FR') {
          $livraison_departement = ' ' . substr($livraison_cp, 0, 2);
          $livraison_pays = '';
        } else {
          $livraison_departement = '';
          $livraison_pays = ' (' . $livraison_pays . ')';
        }

        $affreteur_nom = $affreteur_nom ?? "À affréter";

        if ($date_rdv != $date_precedente) {
          $this->AddDate($date_mise_en_forme);
        }
        $this->AddLine(
          $client_id,
          $client_nom,
          $client_departement,
          $client_ville,
          $client_pays,
          $livraison_id,
          $livraison_nom,
          $livraison_departement,
          $livraison_ville,
          $livraison_pays,
          $affreteur_lie_agence,
          $affreteur_nom,
          $numero_bl,
          $commentaire_public
        );

        $date_precedente = $date_rdv;
      }
    } else {
      // Affichage de "Aucun RDV"
      $this->AucunRDV($this->date_debut, $this->date_fin);
    }

    /**
     * Vérification de la présence de rendez-vous en attente
     * S'il y a des RDV sur la période, peuplement des lignes
     * Sinon, affichage de "Aucun RDV"
     */
    if (count($this->rdvs["attente"]) > 0) {
      foreach ($this->rdvs["attente"] as $rdv) {
        /**
         * @var string $date_rdv
         * @var string $client_id
         * @var string $client_nom
         * @var string $client_cp
         * @var string $client_ville
         * @var string $client_pays
         * @var string $livraison_id
         * @var string $livraison_nom
         * @var string $livraison_cp
         * @var string $livraison_ville
         * @var string $livraison_pays
         * @var string $commentaire_public
         */
        extract($rdv);

        if ($date_rdv === NULL) {
          $date_mise_en_forme = "Pas de date";
        } else {
          $date_mise_en_forme = DateUtils::format(DateUtils::DATE_FULL, $date_rdv);
        }

        if (strtolower($client_pays) === 'france') {
          $client_departement = ' ' . substr($client_cp, 0, 2);
          $client_pays = '';
        } else {
          $client_departement = '';
          $client_pays = ' (' . $client_pays . ')';
        }

        if (strtolower($livraison_pays) === 'france') {
          $livraison_departement = ' ' . substr($livraison_cp, 0, 2);
          $livraison_pays = '';
        } else {
          $livraison_departement = '';
          $livraison_pays = ' (' . $livraison_pays . ')';
        }

        $this->AddLineAttente(
          $date_mise_en_forme,
          $client_id,
          $client_nom,
          $client_departement,
          $client_ville,
          $client_pays,
          $livraison_id,
          $livraison_nom,
          $livraison_departement,
          $livraison_ville,
          $livraison_pays,
          $commentaire_public
        );
      }
    } else {
      // Affichage de "Aucun RDV"
      $this->AucunRDVAttente();
    }

    return $this;
  }

  /**
   * Ajout d'une ligne de date.
   * 
   * @param string $date_mise_en_forme Date mise en forme.
   */
  function AddDate(string $date_mise_en_forme): void
  {
    $this->SetFont('RobotoB', '', 12);
    $this->SetTextColor(88, 200, 95);
    $this->Cell(0, 10, $date_mise_en_forme, 0, 1);
  }

  /**
   * Ajout d'une ligne RDV.
   * 
   * @param string $client_id 
   * @param string $client_nom 
   * @param string $client_departement 
   * @param string $client_ville 
   * @param string $client_pays 
   * @param string $livraison_id 
   * @param string $livraison_nom 
   * @param string $livraison_departement 
   * @param string $livraison_ville 
   * @param string $livraison_pays 
   * @param string $affreteur_lie_agence 
   * @param string $affreteur 
   * @param string $numero_bl 
   * @param string $commentaire 
   */
  function AddLine(
    ?string $client_id,
    ?string $client_nom,
    ?string $client_departement,
    ?string $client_ville,
    ?string $client_pays,
    ?string $livraison_id,
    ?string $livraison_nom,
    ?string $livraison_departement,
    ?string $livraison_ville,
    ?string $livraison_pays,
    ?string $affreteur_lie_agence,
    string $affreteur,
    string $numero_bl,
    string $commentaire
  ): void {
    $this->SetFont('Roboto', '', 10);
    $this->SetTextColor(0, 0, 0);
    $this->Cell(100, 6, $client_nom . $client_departement . ' ' . $client_ville . $client_pays);
    if ($affreteur_lie_agence == 1) {
      $this->SetTextColor(0, 0, 255);
    }
    $this->Cell(40, 6, $affreteur);
    $this->SetTextColor(0, 0, 0);
    $this->Cell(40, 6, $numero_bl, 0, 1);
    if ($client_id !== $livraison_id) {
      $this->SetTextColor(100, 100, 100);
      $this->Cell(10, 6, "chez");
      $this->SetTextColor(0, 0, 0);
      $this->Cell(100, 6, $livraison_nom . $livraison_departement . ' ' . $livraison_ville . $livraison_pays, 0, 1);
    }
    if ($commentaire !== '') {
      $commentaire = str_replace(" <br> ", "\n", $commentaire);
      $this->Cell(5, 6); // Décalage de 0.5cm
      $this->MultiCell(0, 6, $commentaire);
    }
    $this->Cell(0, 4, '', 0, 1); // Espace avant le prochain rdv pour la lisibilité
  }

  function AddLineAttente(
    string $date_mise_en_forme,
    string $client_id,
    string $client_nom,
    string $client_departement,
    string $client_ville,
    string $client_pays,
    string $livraison_id,
    string $livraison_nom,
    string $livraison_departement,
    string $livraison_ville,
    string $livraison_pays,
    string $commentaire
  ) {
    $this->SetFont('Roboto', '', 10);
    $this->SetTextColor(100, 100, 100);
    $this->Cell(10, 6); // Décalage de 1cm
    $this->Cell(50, 6, $date_mise_en_forme);
    $this->Cell(100, 6, $client_nom . $client_departement . ' ' . $client_ville . $client_pays, 0, 1);
    if ($client_id !== $livraison_id) {
      $this->Cell(60, 6); // Décalage de 6cm
      $this->Cell(10, 6, "chez");
      $this->Cell(100, 6, $livraison_nom . $livraison_departement . ' ' . $livraison_ville . $livraison_pays, 0, 1);
    }
    if ($commentaire != '') {
      $commentaire = str_replace(" <br> ", "\n", $commentaire);
      $this->Cell(60, 6); // Décalage de 6cm
      $this->MultiCell(0, 6, $commentaire);
    }
    $this->Cell(0, 4, '', 0, 1); // Espace avant le prochain rdv pour la lisibilité
  }

  /**
   * Affichage d'un message "Aucun RDV".
   * 
   * @param DateTime $date_debut Date de début.
   * @param DateTime $date_fin   Date de fin.
   */
  function AucunRDV(DateTime $date_debut, DateTime $date_fin): void
  {
    $this->SetFont('Roboto', '', 12);
    $this->SetTextColor(0, 0, 0);
    $date_debut_mise_en_forme = DateUtils::format(DateUtils::DATE_FULL, $date_debut);
    $date_fin_mise_en_forme = DateUtils::format(DateUtils::DATE_FULL, $date_fin);
    $this->Cell(0, 30, "Aucun rendez-vous du $date_debut_mise_en_forme au $date_fin_mise_en_forme", 0, 1, 'C');
  }

  /**
   * Affichage d'un message "Aucun RDV en attente".
   */
  function AucunRDVAttente(): void
  {
    $this->SetFont('Roboto', '', 12);
    $this->SetTextColor(0, 0, 0);
    $this->Cell(0, 30, 'Aucun rendez-vous en attente', 0, 0, 'C');
  }
}
