<?php

namespace App\Controllers\Vrac;

use App\Models\Vrac\RdvModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;

use Exception;
use App\Core\Exceptions\Auth\AccessException;

class RdvController extends Controller
{
  private $model;
  private $module = "vrac";
  private $sse_event = "vrac/rdvs";

  public function __construct(
    private ?int $id
  ) {
    parent::__construct();
    $this->model = new RdvModel;
    $this->processRequest();
  }

  public function processRequest()
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
        break;

      case 'GET':
      case 'HEAD':
        if ($this->id) {
          $this->read($this->id);
        } else {
          $this->readAll($this->request->query);
        }
        break;

      case 'POST':
        $this->create();
        break;

      case 'PUT':
        $this->update($this->id);
        break;

      case 'PATCH':
        $this->patch($this->id);
        break;

      case 'DELETE':
        $this->delete($this->id);
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère tous les RDV vrac.
   * 
   * @param array $query Détails de la requête.
   */
  public function readAll(array $query)
  {
    if (!$this->user->can_access($this->module)) {
      throw new AccessException();
    }

    $donnees = $this->model->readAll($query);

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
   * Récupère un RDV vrac.
   * 
   * @param int  $id      id du RDV à récupérer.
   * @param bool $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
   */
  public function read(int $id, ?bool $dry_run = false)
  {
    if (!$this->user->can_access($this->module)) {
      throw new AccessException();
    }

    $donnees = $this->model->read($id);

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
   * Crée un RDV vrac.
   */
  public function create()
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    $input = $this->request->body;

    $donnees = $this->model->create($input);

    $id = $donnees["id"];

    $this->headers["Location"] = $_ENV["API_URL"] . "/vrac/rdv/$id";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->sse_event, __FUNCTION__, $id, $donnees);
  }

  /**
   * Met à jour un RDV.
   * 
   * @param int $id id du RDV à modifier.
   */
  public function update(int $id)
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    if (!$this->model->exists($id)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $donnees = $this->model->update($id, $input);

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->sse_event, __FUNCTION__, $id, $donnees);
  }

  /**
   * Met à jour certaines proriétés d'un RDV vrac.
   * 
   * @param int $id id du RDV à modifier.
   */
  public function patch(int $id)
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    if (!$this->model->exists($id)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $donnees = $this->model->patch($id, $input);

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->sse_event, __FUNCTION__, $id, $donnees);
  }

  /**
   * Supprime un RDV.
   * 
   * @param int $id id du RDV à supprimer.
   */
  public function delete(int $id)
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    if (!$this->model->exists($id)) {
      $this->response->setCode(404);
      return;
    }

    $succes = $this->model->delete($id);

    if ($succes) {
      $this->response->setCode(204)->flush();
      notify_sse($this->sse_event, __FUNCTION__, $id);
    } else {
      throw new Exception("Erreur lors de la suppression");
    }
  }
}
