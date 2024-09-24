<?php

namespace App\Controllers\Chartering;

use App\Models\Chartering\CharterModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class CharterController extends Controller
{
    private $model;
    private $module = "chartering";
    private $sseEventName = "chartering/charters";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new CharterModel();
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
     * Récupère tous les affrètements maritimes.
     * 
     * @param array $archives
     */
    public function readAll(array $archives)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $charters = $this->model->readAll($archives);

        $etag = ETag::get($charters);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($charters))
            ->setHeaders($this->headers);
    }

    /**
     * Récupère un affrètement maritime.
     * 
     * @param int  $id      id de l'affrètement à récupérer.
     * @param bool $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dryRun = false)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $charter = $this->model->read($id);

        if (!$charter && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if ($dryRun) {
            return $charter;
        }

        $etag = ETag::get($charter);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($charter))
            ->setHeaders($this->headers);
    }

    /**
     * Crée un affrètement maritime.
     */
    public function create()
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $newCharter = $this->model->create($input);

        $id = $newCharter["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/chartering/charters/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($newCharter))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newCharter);
    }

    /**
     * Met à jour un affrètement.
     * 
     * @param int $id id de l'affrètement à modifier.
     */
    public function update(int $id)
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        if (!$this->model->exists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $updatedCharter = $this->model->update($id, $input);

        $this->response
            ->setBody(json_encode($updatedCharter))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedCharter);
    }

    /**
     * Supprime un affrètement.
     * 
     * @param int $id id de l'affrètement à supprimer.
     */
    public function delete(int $id)
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        if (!$this->model->exists($id)) {
            $this->response->setCode(404);
            return;
        }

        $success = $this->model->delete($id);

        if ($success) {
            $this->response->setCode(204)->flush();
            $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
