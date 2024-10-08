<?php

namespace App\Controllers\Admin;

use App\Models\Admin\UserAccountModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AdminException;
use App\Core\Exceptions\Client\Auth\ForbiddenException;
use App\Core\Exceptions\Server\DB\DBException;

class UserAccountController extends Controller
{
    private $model;
    private $module = "admin";
    private $sseEventName = "admin/users";

    public function __construct(
        private ?string $uid = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");

        if ($this->user->is_admin === false) {
            throw new AdminException();
        }

        $this->model = new UserAccountModel(admin: $this->user);
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
                if ($this->uid) {
                    $this->read($this->uid);
                } else {
                    $this->readAll();
                }
                break;

            case 'POST':
                $this->create();
                break;

            case 'PUT':
                $this->update($this->uid);
                break;

            case 'DELETE':
                $this->delete($this->uid);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous comptes utilisateurs.
     */
    public function readAll()
    {
        $donnees = $this->model->readAll();

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
     * Récupère un compte utilisateur.
     * 
     * @param string $uid     UID du compte à récupérer.
     * @param bool   $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(string $uid, ?bool $dry_run = false)
    {
        $donnees = $this->model->read($uid);

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
     * Crée un compte utilisateur.
     */
    public function create()
    {
        $input = $this->request->body;

        if (empty($input)) {
            $this->response->setCode(400);
            return;
        }

        $donnees = $this->model->create($input);

        $uid = $donnees["uid"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/admin/users/$uid";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $uid, $donnees);
    }

    /**
     * Met à jour un compte utilisateur.
     * 
     * @param string $uid UID du compte à modifier.
     */
    public function update(string $uid)
    {
        if (!$this->read($uid, true)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $donnees = $this->model->update($uid, $input);

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $uid, $donnees);
    }

    /**
     * Supprime un compte utilisateur.
     * 
     * @param string $uid UID du compte à supprimer.
     */
    public function delete(string $uid)
    {
        // Un utilisateur ne peut pas se supprimer lui-même
        if ($this->user->uid === $uid) {
            throw new ForbiddenException("Un administrateur ne peut pas supprimer son propre compte.");
        }

        if (!$this->read($uid, true)) {
            $this->response->setCode(404);
            return;
        }

        $succes = $this->model->delete($uid);

        if ($succes) {
            $this->response->setCode(204);
            $this->sse->addEvent($this->sseEventName, __FUNCTION__, $uid);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
