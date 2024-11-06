<?php

namespace App\Controller\Timber;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\DTO\Filter\TimberFilterDTO;
use App\Service\TimberService;

final class TimberAppointmentController extends Controller
{
    private TimberService $timberService;
    /** @phpstan-var Module::* $module */
    private string $module = Module::TIMBER;
    private string $sse_event = "bois/rdvs";

    public function __construct(
        private ?int $id = null
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
        $this->timberService = new TimberService();
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
     * Récupère tous les RDV bois.
     */
    public function readAll(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux RDVs bois.");
        }

        $filter = new TimberFilterDTO($this->request->getQuery());

        $appointments = $this->timberService->getAppointments($filter);

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
     * Récupère un RDV bois.
     * 
     * @param int $id Identifiant du RDV à récupérer.
     */
    public function read(int $id): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux RDVs bois.");
        }

        $appointment = $this->timberService->getAppointment($id);

        if (!$appointment) {
            throw new NotFoundException("Ce RDV bois n'existe pas.");
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
     * Crée un RDV bois.
     */
    public function create(): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour créer un RDV bois.");
        }

        $input = $this->request->getBody();

        $appointment = $this->timberService->createAppointment($input);

        /** @var int $id */
        $id = $appointment->getId();

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", $_ENV["API_URL"] . "/bois/rdvs/$id")
            ->setJSON($appointment);

        $this->sse->addEvent($this->sse_event, __FUNCTION__, $id, $appointment);
    }

    /**
     * Met à jour un RDV.
     * 
     * @param ?int $id id du RDV à modifier.
     */
    public function update(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier un RDV bois.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du RDV est obligatoire.");
        }

        if (!$this->timberService->appointmentExists($id)) {
            throw new NotFoundException("Le RDV n'existe pas.");
        }

        $input = $this->request->getBody();

        $appointment = $this->timberService->updateAppointment($id, $input);

        $this->response->setJSON($appointment);

        $this->sse->addEvent($this->sse_event, __FUNCTION__, $id, $appointment);
    }

    /**
     * Met à jour certaines proriétés d'un RDV bois.
     * 
     * @param ?int $id id du RDV à modifier.
     */
    public function patch(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier un RDV bois.");
        }

        if (!$id) {
            throw new NotFoundException("L'identifiant du RDV est requis.");
        }

        if (!$this->timberService->appointmentExists($id)) {
            throw new NotFoundException("Le RDV n'existe pas.");
        }

        $input = $this->request->getBody();

        $appointment = $this->timberService->patchAppointment(
            appointmentId: $id,
            isOrderReady: $input["commande_prete"] ?? null,
            isCharteringConfirmationSent: $input["confirmation_affretement"] ?? null,
            deliveryNoteNumber: $input["numero_bl"] ?? null,
            setArrivalTime: isset($input["heure_arrivee"]),
            setDepartureTime: isset($input["heure_depart"]),
        );

        $this->response->setJSON($appointment);

        $this->sse->addEvent($this->sse_event, __FUNCTION__, $id, $appointment);
    }

    /**
     * Supprime un RDV.
     * 
     * @param ?int $id id du RDV à supprimer.
     */
    public function delete(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer un RDV bois.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du RDV est obligatoire.");
        }

        if (!$this->timberService->appointmentExists($id)) {
            throw new NotFoundException("Le RDV n'existe pas.");
        }

        $this->timberService->deleteAppointment($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sse_event, __FUNCTION__, $id);
    }
}
