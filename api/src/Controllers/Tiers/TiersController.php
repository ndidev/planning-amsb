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
    private $sseEventName = "tiers";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new TiersModel();
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
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les tiers.
     * 
     * @param array $options Options de récupérations.
     */
    public function readAll(array $options)
    {
        $thirdParties = $this->model->readAll($options);

        $etag = ETag::get($thirdParties);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($thirdParties))
            ->setHeaders($this->headers);
    }

    /**
     * Récupère un tiers.
     * 
     * @param int   $id      id du tiers à récupérer.
     * @param array $options Options de récupération.
     * @param bool  $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?array $options = [], ?bool $dryRun = false)
    {
        $thirdParty = $this->model->read($id, $options);

        if (!$thirdParty && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if ($dryRun) {
            return $thirdParty;
        }

        $etag = ETag::get($thirdParty);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($thirdParty))
            ->setHeaders($this->headers);
    }

    /**
     * Crée un tiers.
     */
    public function create()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $newThirdParty = $this->model->create($input);

        $id = $newThirdParty["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/tiers/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($newThirdParty))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newThirdParty);
    }

    /**
     * Met à jour un tiers.
     * 
     * @param int $id id du tiers à modifier.
     */
    public function update(int $id)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        if (!$this->model->exists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $updatedThirdyParty = $this->model->update($id, $input);

        $this->response
            ->setBody(json_encode($updatedThirdyParty))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedThirdyParty);
    }

    /**
     * Supprime un tiers.
     * 
     * @param int $id id du tiers à supprimer.
     */
    public function delete(int $id)
    {
        if (!$this->user->canAccess($this->module)) {
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
