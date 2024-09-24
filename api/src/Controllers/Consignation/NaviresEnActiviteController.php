<?php

namespace App\Controllers\Consignation;

use App\Models\Consignation\NaviresEnActiviteModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;

/**
 * Liste des navires en opération entre deux dates.
 */
class NaviresEnActiviteController extends Controller
{
    private $model;
    private $module = "consignation";

    public function __construct()
    {
        parent::__construct();
        $this->model = new NaviresEnActiviteModel();
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
        $input = $this->request->query;

        $shipsInOps = $this->model->readAll($input);

        $etag = ETag::get($shipsInOps);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($shipsInOps))
            ->setHeaders($this->headers);
    }
}
