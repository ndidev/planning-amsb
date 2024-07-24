<?php

namespace App\Controllers\Config;

use App\Models\Config\AjoutRapideBoisModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class AjoutRapideController extends Controller
{
    private $model;
    private $module = "config";
    private $sse_event = "config/ajouts-rapides";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new AjoutRapideBoisModel;
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
        $donnees = $this->model->readAll();

        // Filtre sur les catégories autorisées pour l'utilisateur
        foreach ($donnees as $key => $ligne) {
            if ($this->user->can_access($ligne["module"]) === false) {
                unset($donnees[$key]);
            }
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
     * Récupère un ajout rapide.
     * 
     * @param int  $id      id de l'ajout rapide à récupérer.
     * @param bool $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dry_run = false)
    {
        $donnees = $this->model->read($id);

        if (!$donnees && !$dry_run) {
            $this->response->setCode(404);
            return;
        }

        if (
            $donnees && !$this->user->can_access($donnees["module"])
        ) {
            throw new AccessException();
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
     * Crée un ajout rapide.
     */
    public function create()
    {
        if (!$this->user->can_access($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $donnees = $this->model->create($input);

        $id = $donnees["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/ajouts-rapides/bois/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->flush();

        notify_sse($this->sse_event, __FUNCTION__, $id, $donnees);
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
            !$this->user->can_access($this->module)
            || !$this->user->can_edit($current["module"])
            || !$this->user->can_edit($input["module"])
        ) {
            throw new AccessException();
        }

        $donnees = $this->model->update($id, $input);

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->flush();

        notify_sse($this->sse_event, __FUNCTION__, $id, $donnees);
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
            !$this->user->can_access($this->module)
            || !$this->user->can_edit($current["module"])
        ) {
            throw new AccessException();
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
