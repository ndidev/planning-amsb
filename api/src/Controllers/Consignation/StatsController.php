<?php

namespace App\Controllers\Consignation;

use App\Models\Consignation\StatsModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;

class StatsController extends Controller
{
    private $model;
    private $module = "consignation";

    public function __construct(private ?string $ids)
    {
        parent::__construct();
        $this->model = new StatsModel;
        $this->processRequest();
    }

    public function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supported_methods);
                break;

            case 'GET':
            case 'HEAD':
                if ($this->ids) {
                    $this->readDetails($this->ids);
                } else {
                    $this->readAll($this->request->query);
                }
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
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

    /**
     * Récupère toutes les escales consignation.
     * 
     * @param array $filtre
     */
    public function readDetails($ids)
    {
        if (!$this->user->can_access($this->module)) {
            throw new AccessException();
        }

        $donnees = $this->model->readDetails($ids);

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
