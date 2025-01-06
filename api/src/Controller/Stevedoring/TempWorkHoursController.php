<?php

// Path: api/src/Controller/Stevedoring/TempWorkHoursController.php

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
use App\DTO\Filter\StevedoringTempWorkHoursFilterDTO;
use App\Service\StevedoringService;

final class TempWorkHoursController extends Controller
{
    private StevedoringService $stevedoringService;
    private string $module = Module::STEVEDORING_STAFF;
    private string $sseEventName = "stevedoring/temp-work-hours";

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
        $filter = new StevedoringTempWorkHoursFilterDTO($this->request->getQuery());

        $allTempWorkHours = $this->stevedoringService->getAllTempWorkHours($filter);

        $etag = ETag::get($allTempWorkHours);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($allTempWorkHours);
    }

    public function read(int $id): void
    {
        $tempWorkHours = $this->stevedoringService->getTempWorkHoursEntry($id);

        if (!$tempWorkHours) {
            throw new NotFoundException("Cette entrée n'existe pas.");
        }

        $etag = ETag::get($tempWorkHours);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($tempWorkHours);
    }

    public function create(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour ajouter les heures des intérimaires.");
        }

        $requestBody = $this->request->getBody();

        $newTempWorkHours = $this->stevedoringService->createTempWorkHours($requestBody);

        /** @var int $id */
        $id = $newTempWorkHours->getId();

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/manutention/heures-interimaires/$id")
            ->setJSON($newTempWorkHours);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newTempWorkHours);
    }

    public function update(?int $id = null): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier les heures des intérimaires.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de l'entrée est obligatoire.");
        }

        if (!$this->stevedoringService->tempWorkHoursEntryExists($id)) {
            throw new NotFoundException("Cette entrée n'existe pas.");
        }

        $requestBody = $this->request->getBody();

        $updatedTempWorkHours = $this->stevedoringService->updateTempWorkHours($id, $requestBody);

        $this->response
            ->setCode(HTTPResponse::HTTP_OK_200)
            ->setJSON($updatedTempWorkHours);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedTempWorkHours);
    }

    public function delete(?int $id = null): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer les heures des intérimaires.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de l'entrée est obligatoire.");
        }

        if (!$this->stevedoringService->tempWorkHoursEntryExists($id)) {
            throw new NotFoundException("Cette entrée n'existe pas.");
        }

        $this->stevedoringService->deleteTempWorkHours($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
