<?php

namespace App\Controllers\Consignation;

use App\Models\Consignation\EscaleModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class EscaleController extends Controller
{
    private $model;
    private $module = "consignation";
    private $sseEventName = "consignation/escales";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new EscaleModel();
        $this->processRequest();
    }

    public function processRequest()
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

            case 'DELETE':
                $this->delete($this->id);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les escale consignation.
     * 
     * @param array $archives
     */
    public function readAll(array $archives)
    {
        if (!$this->user->can_access($this->module)) {
            throw new AccessException();
        }

        $donnees = $this->model->readAll($archives);

        $etag = ETag::get($donnees);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers);
    }

    /**
     * Récupère une escale consignation.
     * 
     * @param int  $id      id de l'escale à récupérer.
     * @param bool $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dry_run = false)
    {
        if (!$this->user->can_access($this->module)) {
            throw new AccessException();
        }

        $donnees = $this->model->read($id);

        if (!$donnees && !$dry_run) {
            $this->response->setCode(404);
            return;
        }

        if ($dry_run) {
            return $donnees;
        }

        $etag = ETag::get($donnees);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers);
    }

    /**
     * Crée une escale consignation.
     */
    public function create()
    {
        if (!$this->user->can_edit($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $donnees = $this->model->create($input);

        $id = $donnees["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/consignation/escales/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $donnees);
    }

    /**
     * Met à jour une escale.
     * 
     * @param int $id id de l'escale à modifier.
     */
    public function update(int $id)
    {
        if (!$this->user->can_edit($this->module)) {
            throw new AccessException();
        }

        if (!$this->model->exists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $donnees = $this->model->update($id, $input);

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $donnees);
    }

    /**
     * Supprime une escale.
     * 
     * @param int $id id de l'escale à supprimer.
     */
    public function delete(int $id)
    {
        if (!$this->user->can_edit($this->module)) {
            throw new AccessException();
        }

        if (!$this->model->exists($id)) {
            $this->response->setCode(404);
            return;
        }

        $succes = $this->model->delete($id);

        if ($succes) {
            $this->response->setCode(204)->flush();
            $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
