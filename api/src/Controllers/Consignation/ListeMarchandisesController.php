<?php

namespace App\Controllers\Consignation;

use App\Models\Consignation\ListeMarchandisesModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;

/**
 * Liste des marchandises utilisées en consignation.
 */
class ListeMarchandisesController extends Controller
{
    private $model;
    private $module = "consignation";

    public function __construct()
    {
        parent::__construct();
        $this->model = new ListeMarchandisesModel();
        $this->processRequest();
    }

    public function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supportedMethods);
                break;

            case 'GET':
            case 'HEAD':
                $this->readAll();
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Renvoie la liste des marchandises utilisées en consignation.
     */
    public function readAll()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $cargoes = $this->model->readAll();

        $etag = ETag::get($cargoes);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($cargoes))
            ->setHeaders($this->headers);
    }
}
