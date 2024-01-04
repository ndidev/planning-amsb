<?php

namespace App\Controllers\Tiers;

use App\Models\Tiers\TiersModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class TiersController extends Controller
{
    private $model;
    private $module = "tiers";
    private $sse_event = "tiers";

    public function __construct(
        private ?int $id,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new TiersModel;
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
                    $this->read($this->id, $this->request->query);
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
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Envoi de la réponse HTTP
        $this->response->send();
    }

    /**
     * Récupère tous les tiers.
     * 
     * @param array $options Options de récupérations.
     */
    public function readAll(array $options)
    {
        $donnees = $this->model->readAll($options);

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
     * Récupère un tiers.
     * 
     * @param int   $id      id du tiers à récupérer.
     * @param array $options Options de récupération.
     * @param bool  $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?array $options = [], ?bool $dry_run = false)
    {
        $donnees = $this->model->read($id, $options);

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
     * Crée un tiers.
     */
    public function create()
    {
        if (!$this->user->can_access($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $donnees = $this->model->create($input);

        $id = $donnees["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/tiers/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->flush();

        notify_sse($this->sse_event, __FUNCTION__, $id, $donnees);
    }

    /**
     * Met à jour un tiers.
     * 
     * @param int $id id du tiers à modifier.
     */
    public function update(int $id)
    {
        if (!$this->user->can_access($this->module)) {
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

        notify_sse($this->sse_event, __FUNCTION__, $id, $donnees);
    }

    /**
     * Supprime un tiers.
     * 
     * @param int $id id du tiers à supprimer.
     */
    public function delete(int $id)
    {
        if (!$this->user->can_access($this->module)) {
            throw new AccessException();
        }

        if (!$this->model->exists($id)) {
            $this->response->setCode(404);
            return;
        }

        $succes = $this->model->delete($id);

        if ($succes) {
            $this->response->setCode(204)->flush();
            notify_sse($this->sse_event, __FUNCTION__, $id);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
