<?php

namespace App\Controllers\Config\PDF;

use App\Models\Config\PDFModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;

class VisualiserPDFController extends Controller
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
                $this->response->setCode(204)->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->read($this->request->query);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
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
}
