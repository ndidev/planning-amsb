<?php

// Path: api/src/Controller/Utils/PortController.php

declare(strict_types=1);

namespace App\Controller\Utils;

use App\Controller\Controller;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\PortService;

final class PortController extends Controller
{
    private PortService $service;

    public function __construct(
        private ?string $locode = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->service = new PortService();
        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch ($this->request->getMethod()) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
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
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les ports.
     */
    public function readAll(): void
    {
        $ports = $this->service->getPorts();

        $etag = ETag::get($ports);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->addHeader("Cache-control", "max-age=31557600, must-revalidate")
            ->setJSON($ports);
    }

    /**
     * Récupère un port.
     * 
     * @param string $locode UNLOCODE du port à récupérer.
     */
    public function read(string $locode): void
    {
        $port = $this->service->getPort($locode);

        if (!$port) {
            throw new NotFoundException("Ce port n'existe pas.");
        }

        $etag = ETag::get($port);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->addHeader("Cache-control", "max-age=31557600, must-revalidate")
            ->setJSON($port);
    }
}
