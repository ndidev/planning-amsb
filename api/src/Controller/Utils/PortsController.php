<?php

namespace App\Controller\Utils;

use App\Models\Utils\PortsModel;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Server\DB\DBException;

class PortsController extends Controller
{
  private $model;
  private $sseEventName = "ports";

  public function __construct(
    private ?string $locode = null,
  ) {
    parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
    $this->model = new PortsModel();
    $this->processRequest();
  }

  public function processRequest()
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Allow", $this->supportedMethods);
        break;

      case 'HEAD':
      case 'GET':
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
        $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
        break;
    }
  }

  /**
   * Récupère tous les ports.
   */
  public function readAll()
  {
    $ports = $this->model->readAll();

    $etag = ETag::get($ports);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;
    $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

    $this->response
      ->setBody(json_encode($ports))
      ->setHeaders($this->headers);
  }

  /**
   * Récupère un port.
   * 
   * @param string $locode  UNLOCODE du port à récupérer.
   * @param bool   $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
   */
  public function read(string $locode, ?bool $dryRun = false)
  {
    $port = $this->model->read($locode);

    if (!$port && !$dryRun) {
      $this->response->setCode(404);
      return;
    }

    if ($dryRun) {
      return $port;
    }

    $etag = ETag::get($port);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;
    $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

    $this->response
      ->setBody(json_encode($port))
      ->setHeaders($this->headers);
  }

  /**
   * Crée un port.
   */
  public function create()
  {
    $input = $this->request->body;

    $newPort = $this->model->create($input);

    $locode = $newPort["locode"];

    $this->headers["Location"] = $_ENV["API_URL"] . "/ports/$locode";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($newPort))
      ->setHeaders($this->headers);

    $this->sse->addEvent($this->sseEventName, __FUNCTION__, $locode, $newPort);
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

    $updatedPort = $this->model->update($locode, $input);

    $this->response
      ->setBody(json_encode($updatedPort))
      ->setHeaders($this->headers);

    $this->sse->addEvent($this->sseEventName, __FUNCTION__, $locode, $updatedPort);
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

    $success = $this->model->delete($locode);

    if ($success) {
      $this->response->setCode(204);
      $this->sse->addEvent($this->sseEventName, __FUNCTION__, $locode);
    } else {
      throw new DBException("Erreur lors de la suppression");
    }
  }
}
