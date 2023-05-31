<?php

namespace Api\Controllers\Bois;

use Api\Models\Bois\RegistreModel;
use Api\Utils\BaseController;
use Api\Utils\HTTP\ETag;

use Api\Utils\DateUtils;
use DateTime;
use Api\Utils\Exceptions\Auth\AccessException;

class RegistreController extends BaseController
{
  private $model;
  private $module = "bois";

  public function __construct()
  {
    parent::__construct();
    $this->model = new RegistreModel;
    $this->processRequest();
  }

  public function processRequest()
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET");
        break;

      case 'GET':
      case 'HEAD':
        $this->get($this->request->query);
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Renvoie l'extrait du registre d'affrètement avec le filtre appliqué.
   * 
   * @param array $filtre
   */
  public function get(array $filtre)
  {
    if (!$this->user->can_access($this->module)) {
      throw new AccessException();
    }

    $donnees = $this->model->readAll($filtre);

    $etag = ETag::get($donnees);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $date = date('YmdHis');
    $fichier = "registre_bois_$date.csv";

    $rdvs = $donnees;

    $output = fopen("php://temp/maxmemory:" . (5 * 1024 * 1024), "r+");

    if ($output) {
      try {
        // UTF-8 BOM
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        fputs($output, $bom);

        // En-tête
        $entete = [
          "Date",
          "Mois",
          "Donneur d'ordre",
          "Marchandise",
          "Chargement",
          "Livraison",
          "Numéro BL",
          "Transporteur"
        ];
        fputcsv($output, $entete, ';', '"');

        // Lignes de RDV
        foreach ($rdvs as $rdv) {

          /**
           * @var string $date_rdv
           * @var string $fournisseur
           * @var string $chargement_nom
           * @var string $chargement_ville
           * @var string $chargement_pays
           * @var string $livraison_nom
           * @var string $livraison_cp
           * @var string $livraison_ville
           * @var string $livraison_pays
           * @var string $numero_bl
           * @var string $transporteur
           */
          extract($rdv);

          $mois = DateUtils::format("LLLL", new DateTime($date_rdv));

          if (strtolower($chargement_pays) == 'france') {
            $chargement_pays = "";
          } else {
            $chargement_pays = " ($chargement_pays)";
          }

          if (strtolower($livraison_pays) === 'france') {
            $livraison_departement = " " . substr($livraison_cp, 0, 2);
            $livraison_pays = "";
          } else {
            $livraison_departement = "";
            $livraison_pays = " ($livraison_pays)";
          }

          $ligne = [
            date('d/m/Y', strtotime($date_rdv)),
            $mois,
            $fournisseur,
            "1 COMPLET DE BOIS",
            $chargement_nom === "AMSB" ? "AMSB" : $chargement_nom . ' ' . $chargement_ville . $chargement_pays,
            $livraison_nom === NULL
              ? "Pas de lieu de livraison renseigné"
              : $livraison_nom . $livraison_departement . ' ' . $livraison_ville . $livraison_pays,
            $numero_bl,
            $transporteur ?? "",
          ];

          fputcsv($output, $ligne, ';', '"');
        }
      } catch (\Throwable $e) {
        throw "Erreur écriture lignes";
      }

      rewind($output);

      $csv = stream_get_contents($output);
    }

    $this->headers["ETag"] = $etag;
    $this->headers["Content-Type"] = "text/csv";
    $this->headers["Content-Disposition"] = "attachment; filename=$fichier";
    $this->headers["Cache-Control"] = "no-store, no-cache";

    $this->response
      ->setBody($csv)
      ->setHeaders($this->headers);
  }
}
