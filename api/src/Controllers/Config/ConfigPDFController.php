<?php

namespace Api\Controllers\Config;

use Api\Models\Config\ConfigPDFModel;
use Api\Utils\BaseController;
use Api\Utils\ETag;
use Api\Utils\Exceptions\Auth\AccessException;

class ConfigPDFController extends BaseController
{
  private $model;
  private $module = "config/pdf";

  public function __construct(
    private ?int $id
  ) {
    parent::__construct();
    $this->model = new ConfigPDFModel();
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
   * Récupère toutes les configurations PDF.
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
   * Récupère une configuration PDF.
   * 
   * @param int  $id      id de la configuration à récupérer.
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
   * Crée une configuration PDF.
   */
  public function create()
  {
    $input = $this->request->body;

    if (
      !$this->user->can_access("config")
      || !$this->user->can_edit($input["module"])
    ) {
      throw new AccessException();
    }

    $donnees = $this->model->create($input);

    $id = $donnees["id"];

    $this->headers["Location"] = $_ENV["API_URL"] . "/pdf/configs/$id";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);

    notify_sse($this->module, __FUNCTION__, $id);
  }

  /**
   * Met à jour une configuration PDF.
   * 
   * @param int $id id de la configuration à modifier.
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
      ->setHeaders($this->headers);

    notify_sse($this->module, __FUNCTION__, $id);
  }

  /**
   * Supprime une configuration PDF.
   * 
   * @param int $id id de la configuration PDF à supprimer.
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
      $this->response->setCode(204);
      notify_sse($this->module, __FUNCTION__, $id);
    } else {
      throw new \Exception("Erreur lors de la suppression");
    }
  }
}
