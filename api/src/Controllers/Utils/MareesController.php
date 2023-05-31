<?php

namespace Api\Controllers\Utils;

use Api\Models\Utils\MareesModel;
use Api\Utils\BaseController;
use Api\Utils\HTTP\ETag;

class MareesController extends BaseController
{
  private $model;
  private $module = "marees";
  private $sse_event = "marees";

  public function __construct(
    private ?int $annee = 0,
    private bool $annees = false
  ) {
    parent::__construct();
    $this->model = new MareesModel;
    $this->processRequest();
  }

  public function processRequest()
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET, POST, DELETE");
        break;

      case 'GET':
      case 'HEAD':
        if ($this->annees) {
          $this->readYears();
        } else if ($this->annee) {
          $this->readYear($this->annee);
        } else {
          $this->read($this->request->query);
        }
        break;

      case 'POST':
        $this->create();
        break;

      case 'DELETE':
        $this->delete($this->annee);
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET, POST, DELETE");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère les marées.
   * 
   * @param array $filtre
   */
  public function read(?array $filtre = null)
  {
    $donnees = $this->model->read($filtre);

    $etag = ETag::get($donnees);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);
  }

  /**
   * Récupère les marées d'une année.
   * 
   * @param int $annee
   */
  public function readYear(int $annee)
  {
    $donnees = $this->model->readYear($annee);

    $etag = ETag::get($donnees);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);
  }

  /**
   * Récupère les années.
   */
  public function readYears()
  {
    $donnees = $this->model->readYears();

    $etag = ETag::get($donnees);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);
  }

  /**
   * Ajoute des marées pour une année.
   */
  public function create()
  {
    if (empty($_FILES)) {
      $this->response
        ->setCode(400)
        ->setHeaders($this->headers);
      return;
    }

    $csv = $_FILES["csv"];
    $content = file_get_contents($csv["tmp_name"]);
    $lines = explode(PHP_EOL, $content);

    $marees = [];
    foreach ($lines as $line) {
      // Supprimer le carriage return produit par Windows
      $line = str_replace("\r", "", $line);

      $separator = ";";

      // Ne pas prendre en compte les lignes non conformes
      if (strpos($line, $separator) === false) continue;
      if (strlen($line) <= 2) continue;

      // Enregistrer chaque ligne dans le tableau $marees
      [$date, $heure, $hauteur] = str_getcsv($line, $separator);
      array_push($marees, [
        $date,
        $heure,
        (float) $hauteur
      ]);
    }

    $annee = substr($marees[0][0], 0, 4);

    $this->model->create($marees);

    $this->headers["Location"] = $_ENV["API_URL"] . "/marees/$annee";

    $donnees = ["annee" => (int) $annee];

    $this->response
      ->setCode(201)
      ->setHeaders($this->headers)
      ->setBody(json_encode($donnees));

    notify_sse($this->sse_event, __FUNCTION__, $annee, $donnees);
  }

  /**
   * Supprime les marrées pour une année.
   * 
   * @param int $annee Année pour laquelle supprimer les marées.
   */
  public function delete(int $annee)
  {
    $succes = $this->model->delete($annee);

    if ($succes) {
      $this->response->setCode(204);
      notify_sse($this->sse_event, __FUNCTION__, $annee);
    } else {
      throw new \Exception("Erreur lors de la suppression");
    }
  }
}
