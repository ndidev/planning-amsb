<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\DateUtils;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
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

    private function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->getPdfFile($this->request->query);
                break;

            case 'POST':
                $this->sendPdfFileByEmail();
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
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
        $startDate = DateUtils::convertDate($query["date_debut"] ?? new \DateTime());
        $endDate = DateUtils::convertDate($query["date_fin"] ?? new \DateTime());

        $pdf = $this->pdfService->generatePDF($configId, $startDate, $endDate);

        $pdfAsString = $pdf->stringifyPDF();

        $etag = ETag::get($pdfAsString);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setType('pdf')
            ->addHeader("Content-Disposition", "inline")
            ->setBody($pdfAsString);
    }

    /**
     * Envoi un PDF par e-mail.
     */
    public function sendPdfFileByEmail()
    {
        $input = $this->request->getBody();

        $configId = $input["config"] ?? null;
        $startDate = DateUtils::convertDate($input["date_debut"]);
        $endDate = DateUtils::convertDate($input["date_fin"]);

        $sendingResults = $this->pdfService->sendPdfByEmail($configId, $startDate, $endDate);

        $this->response->setJSON($sendingResults);
    }
}
