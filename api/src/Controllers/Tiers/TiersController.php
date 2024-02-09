<?php

namespace App\Controllers\Tiers;

use App\Models\Tiers\TiersModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\ThirdParty;

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
    public function readAll()
    {
        $listeTiers = $this->model->readAll();

        $etag = ETag::get($listeTiers);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(
                json_encode(
                    array_map(fn (ThirdParty $tiers) => $tiers->toArray(), $listeTiers)
                )
            )
            ->setHeaders($this->headers);
    }

    /**
     * Récupère un tiers.
     * 
     * @param int   $id      id du tiers à récupérer.
     * @param bool  $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dry_run = false)
    {
        $tiers = $this->model->read($id);

        if (!$tiers && !$dry_run) {
            $this->response->setCode(404);
            return;
        }

        if ($dry_run) {
            return $tiers;
        }

        $etag = ETag::get($tiers);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($tiers->toArray()))
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

        $tiers = $this->model->create($input);

        $id = $tiers["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/tiers/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($tiers->toArray()))
            ->setHeaders($this->headers)
            ->flush();

        notify_sse($this->sse_event, __FUNCTION__, $id, $tiers->toArray());
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

        $tiers = $this->model->update($id, $input);

        $this->response
            ->setBody(json_encode($tiers->toArray()))
            ->setHeaders($this->headers)
            ->flush();

        notify_sse($this->sse_event, __FUNCTION__, $id, $tiers->toArray());
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
