<?php

namespace App\Controllers\Config;

use App\Models\Config\CoteModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;

class CoteController extends Controller
{
    private $model;
    private $module = "config";
    private $sse_event = "config/cotes";

    public function __construct(
        private ?string $cote
    ) {
        parent::__construct("OPTIONS, HEAD, GET, PUT");
        $this->model = new CoteModel;
        $this->processRequest();
    }

    public function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supported_methods);
                break;

            case 'HEAD':
            case 'GET':
                if ($this->cote) {
                    $this->read($this->cote);
                } else {
                    $this->readAll();
                }
                break;

            case 'PUT':
                $this->update($this->cote);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Envoi de la réponse HTTP
        $this->response->send();
    }

    /**
     * Récupère toutes les côtes.
     */
    public function readAll()
    {
        $donnees = $this->model->readAll();

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
     * Récupère une côte.
     */
    public function read(string $cote, ?bool $dry_run = false)
    {
        $donnees = $this->model->read($cote);

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
     * Met à jour une côte.
     * 
     * @param string $cote Côte à modifier.
     */
    public function update(string $cote)
    {
        if (!$this->read($cote, true)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $donnees = $this->model->update($cote, $input);

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->flush();

        notify_sse($this->sse_event, __FUNCTION__, $cote, $donnees);
    }
}
