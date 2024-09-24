<?php

namespace App\Controller\Config\PDF;

use App\Models\Config\PDFModel;
use App\Controller\Controller;
use App\Core\HTTP\ETag;

class EnvoiPDFController extends Controller
{
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PDFModel();
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
                $this->getPdfFile($this->request->query);
                break;

            case 'POST':
                $this->sendPdfFileByEmail();
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
    public function getPdfFile(array $query)
    {
        $pdfString = $this->model->getPdfAsString($query);

        if (!$pdfString) {
            $this->response->setCode(404);
            return;
        }

        $etag = ETag::get($pdfString);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;
        $this->headers["Content-Type"] = "application/pdf";
        $this->headers["Content-Disposition"] = "inline";

        $this->response
            ->setBody($pdfString)
            ->setHeaders($this->headers);
    }

    /**
     * Envoi un PDF par e-mail.
     */
    public function sendPdfFileByEmail()
    {
        $input = $this->request->body;

        $sendingResults = $this->model->sendPdfFileByEmail($input);

        $this->response
            ->setCode(200)
            ->setBody(json_encode($sendingResults));
    }
}
