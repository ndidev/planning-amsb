<?php

// Path: api/src/Controller/Config/PdfViewerController.php

declare(strict_types=1);

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Exceptions\Client\BadRequestException;
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
        $configId = $this->request->getQuery()->getInt("config", null);
        $startDate = $this->request->getQuery()->getDatetime("date_debut", 'now');
        $endDate = $this->request->getQuery()->getDatetime("date_fin", 'now');

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

        $configId = $input->getInt('config');

        if (null === $configId) {
            throw new BadRequestException("Le paramètre 'config' est obligatoire.");
        }

        $startDate = $input->getDatetime('date_debut', new \DateTimeImmutable());
        $endDate = $input->getDatetime('date_fin', new \DateTimeImmutable());

        $this->pdfService->sendPdfByEmail($configId, $startDate, $endDate);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
    }
}
