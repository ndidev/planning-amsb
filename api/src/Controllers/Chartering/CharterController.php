<?php

namespace Api\Controllers\Chartering;

use Api\Models\Chartering\CharterModel;
use Api\Utils\BaseController;
use Api\Utils\ETag;

use Exception;
use Api\Utils\Exceptions\Auth\AccessException;

class CharterController extends BaseController
{
  private $model;
  private $module = "chartering";

  public function __construct(
    private ?int $id
  ) {
    parent::__construct();
    $this->model = new CharterModel;
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

      case 'DELETE':
        $this->delete($this->id);
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET, POST, PUT, DELETE");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère tous les affrètements maritimes.
   * 
   * @param array $archives
   */
  public function readAll(array $archives)
  {
    if (!$this->user->can_access($this->module)) {
      throw new AccessException();
    }

    $donnees = $this->model->readAll($archives);

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
   * Récupère un affrètement maritime.
   * 
   * @param int  $id      id de l'affrètement à récupérer.
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
   * Crée un affrètement maritime.
   */
  public function create()
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    $input = $this->request->body;

    $donnees = $this->model->create($input);

    $id = $donnees["id"];

    $this->headers["Location"] = $_ENV["API_URL"] . "/chartering/charters/$id";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->module, __FUNCTION__, $id);
  }

  /**
   * Met à jour un affrètement.
   * 
   * @param int $id id de l'affrètement à modifier.
   */
  public function update(int $id)
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    if (!$this->read($id, true)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $donnees = $this->model->update($id, $input);

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->module, __FUNCTION__, $id);
  }

  /**
   * Supprime un affrètement.
   * 
   * @param int $id id de l'affrètement à supprimer.
   */
  public function delete(int $id)
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    if (!$this->read($id, true)) {
      $this->response->setCode(404);
      return;
    }

    $succes = $this->model->delete($id);

    if ($succes) {
      $this->response->setCode(204)->flush();
      notify_sse($this->module, __FUNCTION__, $id);
    } else {
      throw new Exception("Erreur lors de la suppression");
    }
  }
}
