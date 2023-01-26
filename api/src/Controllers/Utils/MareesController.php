<?php

namespace Api\Controllers\Utils;

use Api\Models\Utils\MareesModel;
use Api\Utils\BaseController;
use Api\Utils\ETag;

class MareesController extends BaseController
{
  private $model;
  private $module = "marees";

  public function __construct(
    private ?int $annee
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
        if ($this->annee) {
          $this->read($this->annee);
        } else {
          $this->readAll($this->request->query);
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
   * Récupère toutes les marées.
   * 
   * @param array $filtre
   */
  public function readAll(?array $filtre = null)
  {
    $donnees = $this->model->readAll($filtre);

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
   * Récupère les marées pour une année.
   * 
   * @param int $annee
   */
  public function read(int $annee, bool $dry_run = false)
  {
    $donnees = $this->model->read($annee);

    if ($dry_run) {
      return $donnees;
    }

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

      // Ne pas prendre en compte les lignes non conformes
      if (strpos($line, ";") === false) continue;
      if (strlen($line) <= 2) continue;

      // Enregistrer chaque ligne dans le tableau $marees
      [$date, $heure, $hauteur] = str_getcsv($line, ";");
      array_push($marees, [
        $date,
        $heure,
        (float) $hauteur
      ]);
    }

    $annee = substr($marees[0][0], 0, 4);

    $this->model->create($marees);

    $this->headers["Location"] = $_ENV["API_URL"] . "/marees/$annee";

    $this->response
      ->setCode(201)
      ->setHeaders($this->headers);

    notify_sse($this->module, __FUNCTION__, "");
  }

  /**
   * Supprime les marrées pour une année.
   * 
   * @param int $annee Année pour laquelle supprimer les marées.
   */
  public function delete(int $annee)
  {
    if (!$this->read($annee, true)) {
      $this->response->setCode(404);
      return;
    }

    $succes = $this->model->delete($annee);

    if ($succes) {
      $this->response->setCode(204);
      notify_sse($this->module, __FUNCTION__, $annee);
    } else {
      throw new \Exception("Erreur lors de la suppression");
    }
  }
}
