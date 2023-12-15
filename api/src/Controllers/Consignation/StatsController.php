<?php

namespace Api\Controllers\Consignation;

use Api\Models\Consignation\StatsModel;
use Api\Utils\BaseController;
use Api\Utils\HTTP\ETag;

use Api\Utils\Exceptions\Auth\AccessException;

class StatsController extends BaseController
{
    private $model;
    private $module = "consignation";

    public function __construct()
    {
        parent::__construct();
        $this->model = new StatsModel;
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
                $this->readAll($this->request->query);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", "OPTIONS, HEAD, GET");
                break;
        }

        // Envoi de la réponse HTTP
        $this->response->send();
    }

    /**
     * Récupère toutes les escales consignation.
     * 
     * @param array $filtre
     */
    public function readAll(array $filtre)
    {
        if (!$this->user->can_access($this->module)) {
            throw new AccessException();
        }

        $donnees = $this->model->readAll($filtre);

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
