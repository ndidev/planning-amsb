<?php

namespace Api\Controllers\Utils;

use Api\Models\Utils\PaysModel;
use Api\Utils\BaseController;
use Api\Utils\ETag;

use Exception;

class PaysController extends BaseController
{
  private $model;

  public function __construct(
    private string $iso
  ) {
    parent::__construct();
    $this->model = new PaysModel;
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
        if ($this->iso) {
          $this->read($this->iso);
        } else {
          $this->readAll();
        }
        break;

      case 'POST':
        $this->create();
        break;

      case 'PUT':
        $this->update($this->iso);
        break;

      case 'DELETE':
        $this->delete($this->iso);
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET, POST, PUT, DELETE");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère tous les pays.
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
   * Récupère un pays.
   * 
   * @param string $iso     Code ISO du pays à récupérer.
   * @param bool   $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
   */
  public function read(string $iso, ?bool $dry_run = false)
  {
    $donnees = $this->model->read($iso);

    if (!$donnees && !$dry_run) {
      $message = "Not Found";
      $documentation = $_ENV["API_URL"] . "/doc/#/Consignation/lireEscaleConsignation";
      $body = json_encode(["message" => $message, "documentation_url" => $documentation]);
      $this->response->setCode(404)->setBody($body);
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
   * Crée un pays.
   */
  public function create()
  {
    $input = $this->request->body;

    $donnees = $this->model->create($input);

    $this->headers["Location"] = $_ENV["API_URL"] . "/consignation/escales/" . $donnees["iso"];

    $this->response
      ->setCode(201)
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);
  }

  /**
   * Met à jour un pays.
   * 
   * @param string $iso Code ISO du pays à modifier.
   */
  public function update(string $iso)
  {
    if (!$this->read($iso, true)) {
      $this->response->setCode(404);
      return;
    }

    $input = (array) json_decode(file_get_contents("php://input"), TRUE);

    $donnees = $this->model->update($iso, $input);

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);
  }

  /**
   * Supprime un pays.
   * 
   * @param string $iso Code ISO du pays à supprimer.
   */
  public function delete(string $iso)
  {
    if (!$this->read($iso, true)) {
      $this->response->setCode(404);
      return;
    }

    $succes = $this->model->delete($iso);

    if ($succes) {
      $this->response->setCode(204);
    } else {
      throw new Exception("Erreur lors de la suppression");
    }
  }
}
