<?php

namespace App\Controllers\Vrac;

use App\Service\VracService;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Entity\Vrac\RdvVrac;

class RdvController extends Controller
{
    private VracService $vracService;
    private $module = "vrac";
    private $sse_event = "vrac/rdvs";

    public function __construct(
        private ?int $id
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
        $this->vracService = new VracService();
        $this->processRequest();
    }

    public function processRequest()
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
     * Récupère tous les RDV vrac.
     * 
     * @param array $query Détails de la requête.
     */
    public function readAll(array $query)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $rdvs = $this->vracService->getRdvs($query);

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
                    array_map(fn (RdvVrac $rdv) => $rdv->toArray(), $rdvs)
                )
            );
    }

    /**
     * Récupère un RDV vrac.
     * 
     * @param int  $id      id du RDV à récupérer.
     * @param bool $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dry_run = false)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $rdv = $this->vracService->getRdv($id);

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
     * Crée un RDV vrac.
     */
    public function create()
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $rdv = $this->vracService->createRdv($input);

        $id = $rdv->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/vrac/rdv/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($rdv->toArray()))
            ->setHeaders($this->headers);

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

        if (!$this->vracService->rdvExiste($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $rdv = $this->vracService->updateRdv($id, $input);

        $this->response
            ->setBody(json_encode($rdv->toArray()))
            ->setHeaders($this->headers);

        notify_sse($this->sse_event, __FUNCTION__, $id, $rdv->toArray());
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

        if (!$this->vracService->rdvExiste($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $rdv = $this->vracService->patchRdv($id, $input);

        $this->response
            ->setBody(json_encode($rdv->toArray()))
            ->setHeaders($this->headers);

        notify_sse($this->sse_event, __FUNCTION__, $id, $rdv->toArray());
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

        if (!$this->vracService->rdvExiste($id)) {
            $this->response->setCode(404);
            return;
        }

        $this->vracService->deleteRdv($id);

        $this->response->setCode(204)->flush();
        notify_sse($this->sse_event, __FUNCTION__, $id);
    }
}
