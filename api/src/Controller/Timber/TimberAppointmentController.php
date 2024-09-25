<?php

namespace App\Controller\Timber;

use App\Controller\Controller;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Service\TimberService;

class TimberAppointmentController extends Controller
{
    private TimberService $timberService;
    private string $module = "bois";
    private string $sse_event = "bois/rdvs";

    public function __construct(
        private ?int $id = null
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
        $this->timberService = new TimberService();
        $this->processRequest();
    }

    public function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supportedMethods);
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
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les RDV bois.
     * 
     * @param array $filtre
     */
    public function readAll(array $filtre)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $appointments = $this->timberService->getAppointments($filtre);

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
     * Récupère un RDV bois.
     * 
     * @param int $id      id du RDV à récupérer.
     * @param bool $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dryRun = false)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $appointment = $this->timberService->getAppointment($id);

        if (!$appointment && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if ($dryRun) {
            return $appointment;
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
     * Crée un RDV bois.
     */
    public function create()
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        if (empty($input)) {
            $this->response
                ->setCode(400)
                ->setHeaders($this->headers);
            return;
        }

        $appointment = $this->timberService->createAppointment($input);

        $id = $appointment->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/bois/rdv/$id";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setJSON($appointment);

        $this->sse->addEvent($this->sse_event, __FUNCTION__, $id, $appointment);
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

        if (!$this->timberService->appointmentExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $appointment = $this->timberService->updateAppointment($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($appointment);

        $this->sse->addEvent($this->sse_event, __FUNCTION__, $id, $appointment);
    }

    /**
     * Met à jour certaines proriétés d'un RDV bois.
     * 
     * @param int $id id du RDV à modifier.
     */
    public function patch(?int $id)
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        if ($id && !$this->timberService->appointmentExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $appointment = $this->timberService->patchAppointment($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($appointment);

        if ($appointment) {
            $this->sse->addEvent($this->sse_event, __FUNCTION__, $id, $appointment);
        }
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

        if (!$this->timberService->appointmentExists($id)) {
            $message = "Not Found";
            $documentation = $_ENV["API_URL"] . "/doc/#/Bois/supprimerRdvBois";
            $body = json_encode(["message" => $message, "documentation_url" => $documentation]);
            $this->response->setCode(404)->setBody($body);
            return;
        }

        $this->timberService->deleteAppointment($id);

        $this->response->setCode(204)->flush();
        $this->sse->addEvent($this->sse_event, __FUNCTION__, $id);
    }
}
