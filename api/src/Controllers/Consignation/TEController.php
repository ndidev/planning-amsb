<?php

namespace App\Controllers\Consignation;

use App\Models\Consignation\TEModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;

class TEController extends Controller
{
    private $model;
    private $module = "consignation";

    public function __construct()
    {
        parent::__construct();
        $this->model = new TEModel();
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
     * Renvoie les tirants d'eau du planning consignation.
     */
    public function readAll()
    {
        $drafts = $this->model->readAll();

        $etag = ETag::get($drafts);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($drafts))
            ->setHeaders($this->headers);
    }
}
