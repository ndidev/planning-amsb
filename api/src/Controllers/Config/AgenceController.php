<?php

namespace App\Controllers\Config;

use App\Models\Config\AgenceModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;


class AgenceController extends Controller
{
  private $model;
  private $module = "config";
  private $sse_event = "config/agence";

  public function __construct(
    private ?string $service,

  ) {
    parent::__construct();
    $this->model = new AgenceModel;
    $this->processRequest();
  }

  public function processRequest()
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET, PUT");
        break;

      case 'GET':
      case 'HEAD':
        if ($this->service) {
          $this->read($this->service);
        } else {
          $this->readAll($this->request->query);
        }
        break;

      case 'PUT':
        $this->update($this->service);
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET, PUT");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère les données des services de l'agence.
   */
  public function readAll()
  {
    $donnees = $this->model->readAll();

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
   * Renvoie les données d'un service de l'agence.
   * 
   * @param string $service Service de l'agence à récupérer.
   */
  public function read(string $service, ?bool $dry_run = false)
  {
    $donnees = $this->model->read($service);

    if (!$donnees && !$dry_run) {
      $this->response->setCode(404);
      return;
    }

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
   * Met à jour les données d'un service de l'agence.
   * 
   * @param string $service Service de l'agence à modifier.
   */
  public function update(string $service)
  {
    if (!$this->read($service, true)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $donnees = $this->model->update($service, $input);

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->sse_event, __FUNCTION__, $service, $donnees);
  }
}
