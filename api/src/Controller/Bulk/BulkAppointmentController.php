<?php

// Path: api/src/Controller/Bulk/BulkAppointmentController.php

declare(strict_types=1);

namespace App\Controller\Bulk;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Array\Environment;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\DTO\Filter\BulkFilterDTO;
use App\Service\BulkService;

final class BulkAppointmentController extends Controller
{
    private BulkService $bulkService;
    private string $module = Module::BULK;
    private string $sseEventName = "vrac/rdvs";

    public function __construct(
        private ?int $id = null
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
        $this->bulkService = new BulkService();
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

            case 'PATCH':
                $this->patch($this->id);
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

    /**
     * Récupère tous les RDV vrac.
     */
    public function readAll(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux RDVs vrac.");
        }

        $filter = new BulkFilterDTO($this->request->getQuery());

        $appointments = $this->bulkService->getAppointments($filter);

        $etag = ETag::get($appointments);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($appointments);
    }

    /**
     * Récupère un RDV vrac.
     * 
     * @param int  $id ID du RDV à récupérer.
     */
    public function read(int $id): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux RDVs vrac.");
        }

        $appointment = $this->bulkService->getAppointment($id);

        if (!$appointment) {
            throw new NotFoundException("Le RDV n'existe pas.");
        }

        $etag = ETag::get($appointment);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($appointment);
    }

    /**
     * Crée un RDV vrac.
     */
    public function create(): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour créer un RDV vrac.");
        }

        $input = $this->request->getBody();

        $appointment = $this->bulkService->createAppointment($input);

        /** @var int $id */
        $id = $appointment->id;

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/vrac/rdvs/$id")
            ->setJSON($appointment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $appointment);
    }

    /**
     * Met à jour un RDV.
     * 
     * @param ?int $id id du RDV à modifier.
     */
    public function update(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier un RDV vrac.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du RDV est obligatoire.");
        }

        if (!$this->bulkService->appointmentExists($id)) {
            throw new NotFoundException("Le RDV n'existe pas.");
        }

        $input = $this->request->getBody();

        $appointment = $this->bulkService->updateAppointment($id, $input);

        $this->response->setJSON($appointment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $appointment);
    }

    /**
     * Met à jour certaines proriétés d'un RDV vrac.
     * 
     * @param ?int $id id du RDV à modifier.
     */
    public function patch(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier un RDV vrac.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du RDV est obligatoire.");
        }

        if (!$this->bulkService->appointmentExists($id)) {
            throw new NotFoundException("Le RDV n'existe pas.");
        }

        $input = $this->request->getBody();

        $appointment = $this->bulkService->patchAppointment($id, $input);

        $this->response->setJSON($appointment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $appointment);
    }

    /**
     * Supprime un RDV.
     * 
     * @param ?int $id id du RDV à supprimer.
     */
    public function delete(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer un RDV vrac.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du RDV est obligatoire.");
        }

        if (!$this->bulkService->appointmentExists($id)) {
            throw new NotFoundException("Le RDV n'existe pas.");
        }

        $this->bulkService->deleteAppointment($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
