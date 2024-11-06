<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\DateUtils;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\PdfService;

final class PdfViewerController extends Controller
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
        switch ($this->request->getMethod()) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->getPdfFile();
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
     */
    public function getPdfFile(): void
    {
        /** @var ?int $configId */
        $configId = $this->request->getQuery()->getParam("config", null, "int");
        /** @var \DateTime $startDate */
        $startDate = $this->request->getQuery()->getParam("date_debut", 'now', "datetime");
        /** @var \DateTime $endDate */
        $endDate = $this->request->getQuery()->getParam("date_fin", 'now', "datetime");

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
    public function sendPdfFileByEmail(): void
    {
        $input = $this->request->getBody();

        $configId = $input["config"] ?? null;
        $startDate = DateUtils::convertDate($input["date_debut"] ?? new \DateTimeImmutable());
        $endDate = DateUtils::convertDate($input["date_fin"] ?? new \DateTimeImmutable());

        $sendingResults = $this->pdfService->sendPdfByEmail($configId, $startDate, $endDate);

        $this->response->setJSON($sendingResults);
    }
}
