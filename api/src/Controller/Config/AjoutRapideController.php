<?php

namespace App\Controller\Config;

use App\Models\Config\AjoutRapideBoisModel;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class AjoutRapideController extends Controller
{
    private $model;
    private $module = "config";
    private $sseEventName = "config/ajouts-rapides";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new AjoutRapideBoisModel();
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
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les ajouts rapides.
     */
    public function readAll()
    {
        $quickAppointments = $this->model->readAll();

        // Filtre sur les catégories autorisées pour l'utilisateur
        foreach ($quickAppointments as $key => $quickAppointment) {
            if ($this->user->canAccess($quickAppointment["module"]) === false) {
                unset($quickAppointments[$key]);
            }
        }

        $etag = ETag::get($quickAppointments);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($quickAppointments))
            ->setHeaders($this->headers);
    }

    /**
     * Récupère un ajout rapide.
     * 
     * @param int  $id      id de l'ajout rapide à récupérer.
     * @param bool $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dryRun = false)
    {
        $quickAppointment = $this->model->read($id);

        if (!$quickAppointment && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if (
            $quickAppointment && !$this->user->canAccess($quickAppointment["module"])
        ) {
            throw new AccessException();
        }

        if ($dryRun) {
            return $quickAppointment;
        }

        $etag = ETag::get($quickAppointment);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($quickAppointment))
            ->setHeaders($this->headers);
    }

    /**
     * Crée un ajout rapide.
     */
    public function create()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $newQuickAppointment = $this->model->create($input);

        $id = $newQuickAppointment["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/ajouts-rapides/bois/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($newQuickAppointment))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newQuickAppointment);
    }

    /**
     * Met à jour un ajout rapide.
     * 
     * @param int $id id de l'ajout rapide à modifier.
     */
    public function update(int $id)
    {
        $current = $this->read($id, true);

        if (!$current) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($current["module"])
            || !$this->user->canEdit($input["module"])
        ) {
            throw new AccessException();
        }

        $updatedQuickAppointment = $this->model->update($id, $input);

        $this->response
            ->setBody(json_encode($updatedQuickAppointment))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedQuickAppointment);
    }

    /**
     * Supprime un ajout rapide.
     * 
     * @param int $id id de l'ajout rapide à supprimer.
     */
    public function delete(int $id)
    {
        $current = $this->read($id, true);

        if (!$current) {
            $this->response->setCode(404);
            return;
        }

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($current["module"])
        ) {
            throw new AccessException();
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
