<?php

namespace App\Controllers\Bois;

use App\Models\Bois\RdvModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class RdvController extends Controller
{
    private $model;
    private $module = "bois";
    private $sseEventName = "bois/rdvs";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
        $this->model = new RdvModel();
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
        if (!$this->user->can_access($this->module)) {
            throw new AccessException();
        }

        $donnees = $this->model->readAll($filtre);

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
     * Récupère un RDV bois.
     * 
     * @param int $id      id du RDV à récupérer.
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
     * Crée un RDV bois.
     */
    public function create()
    {
        if (!$this->user->can_edit($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        if (empty($input)) {
            $this->response
                ->setCode(400)
                ->setHeaders($this->headers);
            return;
        }

        $donnees = $this->model->create($input);

        $id = $donnees["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/bois/rdv/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $donnees);
    }

    /**
     * Met à jour un RDV.
     * 
     * @param int $id id du RDV à modifier.
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
     * Met à jour certaines proriétés d'un RDV bois.
     * 
     * @param int $id id du RDV à modifier.
     */
    public function patch(int $id)
    {
        if (!$this->user->can_edit($this->module)) {
            throw new AccessException();
        }

        if (!$this->model->exists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $donnees = $this->model->patch($id, $input);

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $donnees);
    }

    /**
     * Supprime un RDV.
     * 
     * @param int $id id du RDV à supprimer.
     */
    public function delete(int $id)
    {
        if (!$this->user->can_edit($this->module)) {
            throw new AccessException();
        }

        if (!$this->model->exists($id)) {
            $message = "Not Found";
            $documentation = $_ENV["API_URL"] . "/doc/#/Bois/supprimerRdvBois";
            $body = json_encode(["message" => $message, "documentation_url" => $documentation]);
            $this->response->setCode(404)->setBody($body);
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
