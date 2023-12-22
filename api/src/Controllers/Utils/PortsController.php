<?php

namespace App\Controllers\Utils;

use App\Models\Utils\PortsModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;

class PortsController extends Controller
{
  private $model;
  private $sse_event = "ports";

  public function __construct(
    private ?string $locode,
  ) {
    parent::__construct();
    $this->model = new PortsModel;
    $this->processRequest();
  }

  public function processRequest()
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET, POST, PUT, DELETE");
        break;

      case 'GET':
      case 'HEAD':
        if ($this->locode) {
          $this->read($this->locode);
        } else {
          $this->readAll();
        }
        break;

      case 'POST':
        $this->create();
        break;

      case 'PUT':
        $this->update($this->locode);
        break;

      case 'DELETE':
        $this->delete($this->locode);
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET, POST, PUT, DELETE");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère tous les ports.
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
    $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);
  }

  /**
   * Récupère un port.
   * 
   * @param string $locode  UNLOCODE du port à récupérer.
   * @param bool   $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
   */
  public function read(string $locode, ?bool $dry_run = false)
  {
    $donnees = $this->model->read($locode);

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
    $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);
  }

  /**
   * Crée un port.
   */
  public function create()
  {
    $input = $this->request->body;

    $donnees = $this->model->create($input);

    $locode = $donnees["locode"];

    $this->headers["Location"] = $_ENV["API_URL"] . "/ports/$locode";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);

    notify_sse($this->sse_event, __FUNCTION__, $locode, $donnees);
  }

  /**
   * Met à jour un port.
   * 
   * @param string $locode UNLOCODE du port à modifier.
   */
  public function update(string $locode)
  {
    if (!$this->read($locode, true)) {
      $this->response->setCode(404);
      return;
    }

    $input = (array) json_decode(file_get_contents("php://input"), true);

    $donnees = $this->model->update($locode, $input);

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);

    notify_sse($this->sse_event, __FUNCTION__, $locode, $donnees);
  }

  /**
   * Supprime un port.
   * 
   * @param string $locode UNLOCODE du port à supprimer.
   */
  public function delete(string $locode)
  {
    if (!$this->read($locode, true)) {
      $this->response->setCode(404);
      return;
    }

    $succes = $this->model->delete($locode);

    if ($succes) {
      $this->response->setCode(204);
      notify_sse($this->sse_event, __FUNCTION__, $locode);
    } else {
      throw new \Exception("Erreur lors de la suppression");
    }
  }
}
