<?php

namespace App\Controller\Admin;

use App\Controller\Controller;
use App\Core\Exceptions\Client\Auth\AdminException;
use App\Core\Exceptions\Client\Auth\ForbiddenException;
use App\Core\HTTP\ETag;
use App\Service\UserService;

class UserAccountController extends Controller
{
    private UserService $userService;
    private $sseEventName = "admin/users";

    public function __construct(
        private ?string $uid = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");

        if ($this->user->isAdmin === false) {
            throw new AdminException();
        }

        $this->userService = new UserService($this->sse);
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
        $users = $this->userService->getUsers();

        $etag = ETag::get($users);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($users);
    }

    /**
     * Récupère un compte utilisateur.
     * 
     * @param string $uid     UID du compte à récupérer.
     * @param bool   $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(string $uid, ?bool $dryRun = false)
    {
        $user = $this->userService->getUser($uid);

        if (!$user && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if ($dryRun) {
            return $user;
        }

        $etag = ETag::get($user);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($user);
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

        $newUser = $this->userService->createUser($input, $this->user->name);

        $uid = $newUser->getUid();

        $this->headers["Location"] = $_ENV["API_URL"] . "/admin/users/$uid";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setJSON($newUser);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $uid, $newUser);
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

        $updatedUser = $this->userService->updateUser($uid, $input, $this->user);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($updatedUser);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $uid, $updatedUser);
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

        $this->userService->deleteUser($uid, $this->user->name);

        $this->response->setCode(204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $uid);
    }
}
