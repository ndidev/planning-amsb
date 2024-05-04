<?php

// Path: api/src/Controller/Utils/PortController.php

namespace App\Controller\Utils;

use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Entity\Port;
use App\Service\PortService;

class PortController extends Controller
{
  private $service;

  public function __construct(
    private ?string $locode,
  ) {
    parent::__construct("OPTIONS, HEAD, GET");
    $this->service = new PortService();
    $this->processRequest();
  }

  public function processRequest(): void
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Allow", $this->supported_methods);
        break;

      case 'HEAD':
      case 'GET':
        if ($this->locode) {
          $this->read($this->locode);
        } else {
          $this->readAll();
        }
        break;

      default:
        $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
        break;
    }

    // Send the HTTP response
    $this->response->send();
  }

  /**
   * Fetch all ports.
   */
  private function readAll()
  {
    $ports = $this->service->getPorts();

    $etag = ETag::get($ports);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;
    $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

    $this->response
      ->setHeaders($this->headers)
      ->setBody(
        json_encode(
          array_map(fn (Port $port) => $port->toArray(), $ports)
        )
      );
  }

  /**
   * Fetch one port.
   * 
   * @param string $locode  UNLOCODE du port à récupérer.
   */
  private function read(string $locode)
  {
    $port = $this->service->getPort($locode);

    if (!$port) {
      $this->response->setCode(404);
      return;
    }

    $etag = ETag::get($port);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;
    $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

    $this->response
      ->setHeaders($this->headers)
      ->setBody(json_encode($port->toArray()));
  }
}
