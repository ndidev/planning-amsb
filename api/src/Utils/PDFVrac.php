<?php

namespace Api\Utils;

use Api\Utils\DateUtils;
use Api\Utils\PDFPlanning;
use DateTime;

class PDFVrac extends PDFPlanning
{
  /**
   * Génère un PDF vrac.
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
    if (count($this->rdvs) > 0) // Peuplement des lignes
    {
      $date_precedente = "";

      foreach ($this->rdvs as $rdv) {

        /**
         * @var string $date_rdv
         * @var string $heure
         * @var string $produit_nom
         * @var string $produit_couleur
         * @var string $qualite_nom
         * @var string $qualite_couleur
         * @var string $quantite
         * @var string $unite
         * @var string $client_nom
         * @var string $client_ville
         * @var string $transporteur_nom
         * @var string $num_commande
         */
        extract($rdv);


        $heure_mise_en_forme =
          $heure
          ? DateUtils::format(DateUtils::ISO_TIME, $heure)
          : "";

        if ($date_rdv != $date_precedente) {
          $date_mise_en_forme = DateUtils::format(DateUtils::DATE_FULL, $date_rdv);
          $this->AddDate($date_mise_en_forme);
        }

        $this->AddLine(
          $heure_mise_en_forme,
          $produit_nom,
          $produit_couleur,
          $qualite_nom,
          $qualite_couleur,
          $quantite,
          $unite,
          $client_nom,
          $client_ville,
          $transporteur_nom,
          $num_commande
        );

        $date_precedente = $date_rdv;
      }
    } else {
      // Affichage de "Aucun RDV"
      $this->AucunRDV($this->date_debut, $this->date_fin);
    }

    return $this;
  }

  /**
   * Ajout d'une ligne de date.
   * 
   * @param string $date Date mise en forme.
   */
  function AddDate(string $date): void
  {
    $this->SetFont('RobotoB', '', 12);
    $this->SetTextColor(88, 200, 95);
    $this->Cell(0, 10, $date, 0, 1);
  }

  /**
   * Ajout d'une ligne RDV.
   * 
   * @param string $heure 
   * @param string $produit_nom 
   * @param string $produit_couleur 
   * @param string $qualite_nom 
   * @param string $qualite_couleur 
   * @param string $quantite 
   * @param string $unite 
   * @param string $client_nom 
   * @param string $client_ville 
   * @param string $transporteur_nom 
   * @param string $num_commande 
   */
  function AddLine(
    ?string $heure,
    ?string $produit_nom,
    ?string $produit_couleur,
    ?string $qualite_nom,
    ?string $qualite_couleur,
    ?string $quantite,
    ?string $unite,
    ?string $client_nom,
    ?string $client_ville,
    ?string $transporteur_nom,
    string $num_commande
  ): void {
    $this->SetFont('Roboto', '', 10);
    // Heure
    $couleur = explode(',', hex_vers_rgb('#D91FFA'));
    $this->SetTextColor($couleur[0], $couleur[1], $couleur[2]);
    $this->Cell(15, 6, $heure);
    // Produit
    $couleur = explode(',', hex_vers_rgb($produit_couleur));
    $this->SetTextColor($couleur[0], $couleur[1], $couleur[2]);
    $this->Cell(20, 6, $produit_nom);
    // Qualité
    if ($qualite_couleur) {
      $couleur = explode(',', hex_vers_rgb($qualite_couleur));
      $this->SetTextColor($couleur[0], $couleur[1], $couleur[2]);
    }
    $this->Cell(20, 6, $qualite_nom);
    // Client
    $this->SetTextColor(0, 0, 0);
    $this->Cell(70, 6, $client_nom . ' ' . $client_ville);
    // Transporteur
    $this->Cell(30, 6, $transporteur_nom);
    // Numéro commande
    $this->Cell(30, 6, $num_commande, 0, 1);
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
    $this->Cell(0, 30, "Aucun rendez-vous du $date_debut_mise_en_forme au $date_fin_mise_en_forme", 0, 0, 'C');
  }
}
