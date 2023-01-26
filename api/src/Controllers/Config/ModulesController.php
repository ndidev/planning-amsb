<?php

namespace Api\Controllers\Config;

use Api\Models\Config\ModulesModel;
use Api\Utils\BaseController;
use Api\Utils\ETag;


class ModulesController extends BaseController
{
  private $model;

  public function __construct()
  {
    parent::__construct();
    $this->model = new ModulesModel;
    $this->processRequest();
  }

  public function processRequest()
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Access-Control-Allow-Methods", "OPTIONS, HEAD, GET");
        break;

      case 'GET':
      case 'HEAD':
        $this->read();
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Renvoie la liste des modules de l'application.
   */
  public function read()
  {
    $donnees = $this->model->read();

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
}
