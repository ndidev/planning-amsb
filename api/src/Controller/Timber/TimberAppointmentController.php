<?php

namespace App\Controller\Timber;

use App\Controller\Controller;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\HTTP\ETag;
use App\Entity\Timber\TimberAppointment;
use App\Service\TimberService;

class TimberAppointmentController extends Controller
{
    private $service;
    private $module = "bois";
    private $sse_event = "bois/rdvs";

    public function __construct(
        private ?int $id
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
        $this->service = new TimberService();
        $this->processRequest();
    }

    public function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supported_methods);
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
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Envoi de la réponse HTTP
        $this->response->send();
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

        $rdvs = $this->service->getAppointments($filtre);

        $etag = ETag::get($rdvs);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setBody(
                json_encode(
                    array_map(fn (TimberAppointment $rdv) => $rdv->toArray(), $rdvs)
                )
            );
    }

    /**
     * Récupère un RDV bois.
     * 
     * @param int $id      id du RDV à récupérer.
     * @param bool $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dry_run = false)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $rdv = $this->service->getAppointment($id);

        if (!$rdv && !$dry_run) {
            $this->response->setCode(404);
            return;
        }

        if ($dry_run) {
            return $rdv;
        }

        $etag = ETag::get($rdv);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($rdv->toArray()))
            ->setHeaders($this->headers);
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

        $rdv = $this->service->createAppointment($input);

        $id = $rdv->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/bois/rdv/$id";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setBody(json_encode($rdv->toArray()));

        notify_sse($this->sse_event, __FUNCTION__, $id, $rdv->toArray());
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

        if (!$this->service->appointmentExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $rdv = $this->service->updateAppointment($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setBody(json_encode($rdv->toArray()));

        notify_sse($this->sse_event, __FUNCTION__, $id, $rdv->toArray());
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

        if ($id && !$this->service->appointmentExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $rdv = $this->service->patchAppointment($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setBody(json_encode($rdv->toArray()));

        if ($rdv) {
            notify_sse($this->sse_event, __FUNCTION__, $id, $rdv->toArray());
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

        if (!$this->service->appointmentExists($id)) {
            $message = "Not Found";
            $documentation = $_ENV["API_URL"] . "/doc/#/Bois/supprimerRdvBois";
            $body = json_encode(["message" => $message, "documentation_url" => $documentation]);
            $this->response->setCode(404)->setBody($body);
            return;
        }

        $succes = $this->service->deleteAppointment($id);

        if ($succes) {
            $this->response->setCode(204)->flush();
            notify_sse($this->sse_event, __FUNCTION__, $id);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
