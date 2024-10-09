<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\DateUtils;
use App\Core\HTTP\ETag;
use App\Service\PdfService;

class PdfViewerController extends Controller
{
    private PdfService $pdfService;

    public function __construct()
    {
        parent::__construct("OPTIONS, HEAD, GET, POST");
        $this->pdfService = new PdfService();
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
        $configId = $query["config"] ?? null;
        $startDate = DateUtils::convertDate($query["date_debut"]);
        $endDate = DateUtils::convertDate($query["date_fin"]);

        $pdf = $this->pdfService->generatePDF($configId, $startDate, $endDate);

        $pdfAsString = $pdf->stringifyPDF();

        $etag = ETag::get($pdfAsString);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;
        $this->headers["Content-Type"] = "application/pdf";
        $this->headers["Content-Disposition"] = "inline";

        $this->response
            ->setHeaders($this->headers)
            ->setBody($pdfAsString);
    }

    /**
     * Envoi un PDF par e-mail.
     */
    public function sendPdfFileByEmail()
    {
        $input = $this->request->body;

        $configId = $input["config"] ?? null;
        $startDate = DateUtils::convertDate($input["date_debut"]);
        $endDate = DateUtils::convertDate($input["date_fin"]);

        $sendingResults = $this->pdfService->sendPdfByEmail($configId, $startDate, $endDate);

        $this->response
            ->setCode(200)
            ->setJSON($sendingResults);
    }
}
