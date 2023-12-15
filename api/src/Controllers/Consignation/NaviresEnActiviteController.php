<?php

namespace Api\Controllers\Consignation;

use Api\Models\Consignation\NaviresEnActiviteModel;
use Api\Utils\BaseController;
use Api\Utils\HTTP\ETag;

/**
 * Liste des navires en opération entre deux dates.
 */
class NaviresEnActiviteController extends BaseController
{
  private $model;
  private $module = "consignation";

  public function __construct()
  {
    parent::__construct();
    $this->model = new NaviresEnActiviteModel;
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
        $this->readAll();
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Renvoie le dernier numéro de voyage du navire.
   */
  public function readAll()
  {
    $input = $this->request->query;

    $donnees = $this->model->readAll($input);

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
