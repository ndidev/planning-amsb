<?php

// Path: api/src/Controller/Stevedoring/EquipmentController.php

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
use App\Service\StevedoringService;

final class EquipmentController extends Controller
{
    private StevedoringService $stevedoringService;
    private string $module = Module::STEVEDORING;
    private string $sseEventName = "manutention/equipements";

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
            throw new AccessException("Vous n'avez pas les droits pour accéder aux équipements de manutention.");
        }

        $allEquipments = $this->stevedoringService->getAllEquipments();

        $etag = ETag::get($allEquipments);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($allEquipments);
    }

    public function read(int $id): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux équipements de manutention.");
        }

        $equipment = $this->stevedoringService->getEquipment($id);

        if (!$equipment) {
            throw new NotFoundException("L'équipement de manutention n'existe pas.");
        }

        $etag = ETag::get($equipment);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($equipment);
    }

    public function create(): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour ajouter des équipments de manutention.");
        }

        $requestBody = $this->request->getBody();

        $equipment = $this->stevedoringService->createEquipment($requestBody);

        /** @var int $id */
        $id = $equipment->id;

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/manutention/equipements/$id")
            ->setJSON($equipment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $equipment);
    }

    public function update(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier les équipements de manutention.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de l'équipement est obligatoire.");
        }

        if (!$this->stevedoringService->equipmentExists($id)) {
            throw new NotFoundException("L'équipement de manutention n'existe pas.");
        }

        $requestBody = $this->request->getBody();

        $equipment = $this->stevedoringService->updateEquipment($id, $requestBody);

        $this->response
            ->setCode(HTTPResponse::HTTP_OK_200)
            ->setJSON($equipment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $equipment);
    }

    public function delete(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer les équipements de manutention.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de l'équipement est obligatoire.");
        }

        if (!$this->stevedoringService->equipmentExists($id)) {
            throw new NotFoundException("L'équipement de manutention n'existe pas.");
        }

        $this->stevedoringService->deleteEquipment($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
