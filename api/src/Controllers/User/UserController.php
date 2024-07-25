<?php

namespace App\Controllers\User;

use App\Models\User\UserModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AuthException;

class UserController extends Controller
{
    private $model;
    private $module = "user";
    private $sseEventName = "admin/users";

    public function __construct()
    {
        parent::__construct("OPTIONS, HEAD, GET, PUT");
        $this->model = new UserModel($this->user);
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
                $this->read();
                break;

            case 'PUT':
                $this->update();
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère l'utilisateur courant.
     */
    public function read()
    {
        try {
            $donnees = $this->model->read();
        } catch (AuthException) {
            $this->response->setCode(401);
            return;
        }

        if (!$donnees) {
            $this->response->setCode(404);
            return;
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
     * Met à jour l"utilisateur courant.
     */
    public function update()
    {
        $input = $this->request->body;

        try {
            $donnees = $this->model->update($input);
        } catch (AuthException) {
            $this->response->setCode(401);
            return;
        }

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $this->user->uid, $donnees);
    }
}
