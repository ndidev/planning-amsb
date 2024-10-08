<?php

namespace App\Controllers\Consignation;

use App\Models\Consignation\ListeNaviresModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;

/**
 * Liste des navires ayant fait escale.
 */
class ListeNaviresController extends Controller
{
    private $model;
    private $module = "consignation";

    public function __construct()
    {
        parent::__construct();
        $this->model = new ListeNaviresModel();
        $this->processRequest();
    }

    public function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->readAll();
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Renvoie le dernier numéro de voyage du navire.
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
}
