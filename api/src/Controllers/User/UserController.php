<?php

namespace Api\Controllers\User;

use Api\Models\User\UserModel;
use Api\Utils\BaseController;
use Api\Utils\ETag;
use Api\Utils\Exceptions\Auth\AuthException;

class UserController extends BaseController
{
  private $model;
  private $module = "user";

  public function __construct()
  {
    parent::__construct();

    $this->model = new UserModel($this->user);
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
        $this->read();
        break;

      case 'PUT':
        $this->update();
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET, PUT");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère l'utilisateur courant.
   */
  public function read()
  {
    try {
      $donnees = $this->model->read();
    } catch (AuthException) {
      $this->response->setCode(401);
      return;
    }

    if (!$donnees) {
      $this->response->setCode(404);
      return;
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
   * Met à jour l"utilisateur courant.
   */
  public function update()
  {
    $input = $this->request->body;

    try {
      $donnees = $this->model->update($input);
    } catch (AuthException) {
      $this->response->setCode(401);
      return;
    }

    $this->response
      ->setBody(json_encode($donnees))
      ->setHeaders($this->headers);

    notify_sse($this->module, __FUNCTION__, $this->user->uid);
    notify_sse("admin/users", __FUNCTION__, $this->user->uid);
  }
}
