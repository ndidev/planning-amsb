<?php

namespace Api\Controllers\Bois;

use Api\Models\Bois\ConfirmationAffretementModel;
use Api\Utils\BaseController;
use Api\Utils\ETag;

use Api\Utils\Exceptions\Auth\AccessException;

class ConfirmationAffretementController extends BaseController
{
  private $model;
  private $module = "bois";

  public function __construct(
    private ?int $id
  ) {
    parent::__construct();
    $this->model = new ConfirmationAffretementModel;
    $this->processRequest();
  }

  public function processRequest()
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET, PATCH");
        break;

      case 'GET':
      case 'HEAD':
        $this->read($this->id);
        break;

      case 'PATCH':
        $this->update($this->id);
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET, PATCH");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère une confirmation affrètement bois.
   * 
   * @param int  $id      id du RDV à récupérer.
   * @param bool $dry_run Optionnel. Récupérer la ressource sans renvoyer la réponse HTTP.
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
   * Met à jour une confirmation affrètement.
   * 
   * @param int $id id du RDV à modifier.
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
}
