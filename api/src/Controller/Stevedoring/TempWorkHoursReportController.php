<?php

// Path: api/src/Controller/Stevedoring/TempWorkHoursReportController.php

declare(strict_types=1);

namespace App\Controller\Stevedoring;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\StevedoringService;

final class TempWorkHoursReportController extends Controller
{
    private StevedoringService $stevedoringService;
    private string $module = Module::STEVEDORING;

    public function __construct()
    {
        parent::__construct("OPTIONS, HEAD, GET");

        $this->stevedoringService = new StevedoringService();

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
                $this->read();
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    public function read(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux agences d'intérim.");
        }

        $query = $this->request->getQuery();

        $date = $query->getDatetime('date');

        if (null === $date) {
            throw new BadRequestException("La date est obligatoire.");
        }

        $reportsZipArchiveFilename = $this->stevedoringService->getTempWorkHoursReports($date);

        $zipAsString = file_get_contents($reportsZipArchiveFilename);

        if (!$zipAsString) {
            throw new ServerException(
                "Impossible de générer le fichier ZIP.",
                previous: new \Exception("file_get_contents($reportsZipArchiveFilename) failed.")
            );
        }

        \unlink($reportsZipArchiveFilename);

        $etag = ETag::get($zipAsString);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setType("application/zip")
            ->addHeader("Content-Disposition", "attachment; filename=\"rapports-heures-interimaires.zip\"")
            ->setBody($zipAsString);
    }
}
