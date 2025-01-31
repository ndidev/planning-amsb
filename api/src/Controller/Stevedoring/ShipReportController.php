<?php

// Path: api/src/Controller/Stevedoring/ShipReportController.php

declare(strict_types=1);

namespace App\Controller\Stevedoring;

use App\Controller\Controller;
use App\Core\Array\Environment;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\DTO\Filter\StevedoringReportsFilterDTO;
use App\Service\StevedoringService;

final class ShipReportController extends Controller
{
    private StevedoringService $stevedoringService;
    private string $module = Module::STEVEDORING;
    private string $sseEventName = "stevedoring/ship-report";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");

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
                if ($this->id) {
                    $this->read($this->id);
                } else {
                    $this->readAll();
                }
                break;

            case 'POST':
                $this->create();
                break;

            case 'PUT':
                $this->update($this->id);
                break;

            case 'DELETE':
                $this->delete($this->id);
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    public function readAll(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux rapports navires.");
        }

        $filter = new StevedoringReportsFilterDTO($this->request->getQuery());

        $allReports = $this->stevedoringService->getAllShipReports($filter);

        $etag = ETag::get($allReports);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($allReports);
    }

    public function read(int $id): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux rapports navires.");
        }

        $report = $this->stevedoringService->getShipReport($id);

        if (!$report) {
            throw new NotFoundException("Le rapport navire n'existe pas.");
        }

        $etag = ETag::get($report);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($report);
    }

    public function create(): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour ajouter des rapports navires.");
        }

        $requestBody = $this->request->getBody();

        $newReport = $this->stevedoringService->createShipReport($requestBody);

        /** @var int $id */
        $id = $newReport->id;

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/manutention/rapports-navires/$id")
            ->setJSON($newReport);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newReport);
    }

    public function update(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier les rapports navires.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du rapport est obligatoire.");
        }

        if (!$this->stevedoringService->shipReportExists($id)) {
            throw new NotFoundException("Le rapport navire n'existe pas.");
        }

        $requestBody = $this->request->getBody();

        $updatedReport = $this->stevedoringService->updateShipReport($id, $requestBody);

        $this->response
            ->setCode(HTTPResponse::HTTP_OK_200)
            ->setJSON($updatedReport);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedReport);
    }

    public function delete(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer les rapports navires.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du rapport navire est obligatoire.");
        }

        if (!$this->stevedoringService->shipReportExists($id)) {
            throw new NotFoundException("Le rapport navire n'existe pas.");
        }

        $this->stevedoringService->deleteShipReport($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
