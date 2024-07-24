<?php

namespace App\Controllers\Config;

use App\Models\Config\BandeauInfoModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class BandeauInfoController extends Controller
{
    private $model;
    private $module = "config";
    private $sse_event = "config/bandeau-info";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new BandeauInfoModel();
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
     * Récupère toutes les lignes du bandeau.
     * 
     * @param array $filtre
     */
    public function readAll(array $filtre)
    {
        $donnees = $this->model->readAll($filtre);

        // Filtre sur les catégories autorisées pour l'utilisateur
        $donnees =
            array_values(
                array_filter($donnees, function ($ligne) {
                    return $this->user->can_access($ligne["module"]);
                })
            );

        $etag = ETag::get($donnees);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304)->send();
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->send();
    }

    /**
     * Récupère une ligne du bandeau.
     * 
     * @param int  $id      id de la ligne à récupérer.
     * @param bool $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dry_run = false)
    {
        $donnees = $this->model->read($id);

        if (!$donnees && !$dry_run) {
            $this->response->setCode(404)->send();
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
            $this->response->setCode(304)->send();
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->send();
    }

    /**
     * Crée une ligne de bandeau d'informations.
     */
    public function create()
    {
        $input = $this->request->body;

        if (
            !$this->user->can_access($this->module)
            || !$this->user->can_edit($input["module"])
        ) {
            throw new AccessException();
        }

        $donnees = $this->model->create($input);

        $id = $donnees["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/bandeau-info/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers)
            ->flush();

        notify_sse($this->sse_event, __FUNCTION__, $id, $donnees);
    }

    /**
     * Met à jour une ligne du bandeau d'informations.
     * 
     * @param int $id id de la ligne à modifier.
     */
    public function update(int $id)
    {
        $current = $this->read($id, true);

        if (!$current) {
            $message = "Not Found";
            $documentation = $_ENV["API_URL"] . "/doc/#/Bois/modifierLigneBandeauInfo";
            $body = json_encode(["message" => $message, "documentation_url" => $documentation]);
            $this->response->setCode(404)->setBody($body);
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
     * Supprime une ligne du bandeau d'informations.
     * 
     * @param int $id id de la ligne à supprimer.
     */
    public function delete(int $id)
    {
        $current = $this->read($id, true);

        if (!$current) {
            $message = "Not Found";
            $documentation = $_ENV["API_URL"] . "/doc/#/Bandeau/supprimerLigneBandeauInfo";
            $body = json_encode(["message" => $message, "documentation_url" => $documentation]);
            $this->response->setCode(404)->setBody($body);
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
