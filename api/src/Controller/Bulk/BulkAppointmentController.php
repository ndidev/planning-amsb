<?php

namespace App\Controller\Bulk;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\BulkService;

class BulkAppointmentController extends Controller
{
    private BulkService $bulkService;
    private Module $module = Module::BULK;
    private string $sseEventName = "vrac/rdvs";

    public function __construct(
        private ?int $id = null
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
        $this->bulkService = new BulkService();
        $this->processRequest();
    }

    public function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204)->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                if ($this->id) {
                    $this->read($this->id);
                } else {
                    $this->readAll($this->request->query);
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
                $this->response->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les RDV vrac.
     * 
     * @param array $query Détails de la requête.
     */
    public function readAll(array $query)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $appointments = $this->bulkService->getAppointments($query);

        $etag = ETag::get($appointments);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($appointments);
    }

    /**
     * Récupère un RDV vrac.
     * 
     * @param int  $id ID du RDV à récupérer.
     */
    public function read(int $id)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $appointment = $this->bulkService->getAppointment($id);

        if (!$appointment) {
            $this->response->setCode(404);
            return;
        }

        $etag = ETag::get($appointment);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($appointment);
    }

    /**
     * Crée un RDV vrac.
     */
    public function create()
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $appointment = $this->bulkService->createAppointment($input);

        $id = $appointment->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/vrac/rdv/$id";

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->setHeaders($this->headers)
            ->setJSON($appointment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $appointment);
    }

    /**
     * Met à jour un RDV.
     * 
     * @param int $id id du RDV à modifier.
     */
    public function update(int $id)
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        if (!$this->bulkService->appointmentExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $appointment = $this->bulkService->updateAppointment($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($appointment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $appointment);
    }

    /**
     * Met à jour certaines proriétés d'un RDV vrac.
     * 
     * @param int $id id du RDV à modifier.
     */
    public function patch(int $id)
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        if (!$this->bulkService->appointmentExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $appointment = $this->bulkService->patchAppointment($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($appointment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $appointment);
    }

    /**
     * Supprime un RDV.
     * 
     * @param int $id id du RDV à supprimer.
     */
    public function delete(int $id)
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        if (!$this->bulkService->appointmentExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $this->bulkService->deleteAppointment($id);

        $this->response->setCode(204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
