<?php

namespace Api\Controllers\Config;

use Api\Models\Config\RdvRapidesBoisModel;
use Api\Utils\BaseController;
use Api\Utils\ETag;
use Api\Utils\Exceptions\Auth\AccessException;


class RdvRapidesController extends BaseController
{
  private $model;
  private $module = "config/rdvrapides";

  public function __construct(
    private ?int $id
  ) {
    parent::__construct();
    $this->model = new RdvRapidesBoisModel;
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
          $this->readAll();
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
   * Récupère tous les RDV rapides.
   */
  public function readAll()
  {
    $donnees = $this->model->readAll();

    // Filtre sur les catégories autorisées pour l'utilisateur
    foreach ($donnees as $key => $ligne) {
      if ($this->user->can_access($ligne["module"]) === false) {
        unset($donnees[$key]);
      }
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
   * Récupère un RDV rapide.
   * 
   * @param int  $id      id du RDV rapide à récupérer.
   * @param bool $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
   */
  public function read(int $id, ?bool $dry_run = false)
  {
    $donnees = $this->model->read($id);

    if (!$donnees && !$dry_run) {
      $this->response->setCode(404);
      return;
    }

    if (
      $donnees && !$this->user->can_access($donnees["module"])
    ) {
      throw new AccessException();
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
   * Crée un RDV rapide.
   */
  public function create()
  {
    if (!$this->user->can_access("config")) {
      throw new AccessException();
    }

    $input = $this->request->body;

    $donnees = $this->model->create($input);

    $id = $donnees["id"];

    $this->headers["Location"] = $_ENV["API_URL"] . "/rdvrapides/bois/$id";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->module, __FUNCTION__, $id);
  }

  /**
   * Met à jour un RDV rapide.
   * 
   * @param int $id id du RDV rapide à modifier.
   */
  public function update(int $id)
  {
    $current = $this->read($id, true);

    if (!$current) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    if (
      !$this->user->can_access("config")
      || !$this->user->can_edit($current["module"])
      || !$this->user->can_edit($input["module"])
    ) {
      throw new AccessException();
    }

    $donnees = $this->model->update($id, $input);

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->module, __FUNCTION__, $id);
  }

  /**
   * Supprime un RDV rapide.
   * 
   * @param int $id id du RDV rapide à supprimer.
   */
  public function delete(int $id)
  {
    $current = $this->read($id, true);

    if (!$current) {
      $this->response->setCode(404);
      return;
    }

    if (
      !$this->user->can_access("config")
      || !$this->user->can_edit($current["module"])
    ) {
      throw new AccessException();
    }

    $succes = $this->model->delete($id);

    if ($succes) {
      $this->response->setCode(204)->flush();
      notify_sse($this->module, __FUNCTION__, $id);
    } else {
      throw new \Exception("Erreur lors de la suppression");
    }
  }
}
