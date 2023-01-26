<?php

namespace Api\Controllers\Config\PDF;

use Api\Models\Config\PDFModel;
use Api\Utils\BaseController;
use Api\Utils\ETag;


class EnvoiPDFController extends BaseController
{
  private $model;

  public function __construct()
  {
    parent::__construct();
    $this->model = new PDFModel;
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
        $this->read($this->request->query);
        break;

      case 'POST':
        $this->envoyer();
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD");
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère un PDF.
   * 
   * @param array $query Détails de la requête HTTP.
   */
  public function read(array $query)
  {
    $donnees = $this->model->visualiser($query);

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
    $this->headers["Content-Type"] = "application/pdf";
    $this->headers["Content-Disposition"] = "inline";

    $this->response
      ->setBody($donnees)
      ->setHeaders($this->headers);
  }

  /**
   * Envoi un PDF par e-mail.
   */
  public function envoyer()
  {
    $input = $this->request->body;

    $donnees = $this->model->envoyer($input);

    $this->response
      ->setCode(200)
      ->setBody(json_encode($donnees));
  }
}
