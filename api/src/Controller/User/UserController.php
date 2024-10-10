<?php

namespace App\Controller\User;

use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Service\UserService;

class UserController extends Controller
{
    private UserService $userService;
    private string $sseEventName = "admin/users";

    public function __construct()
    {
        parent::__construct("OPTIONS, HEAD, GET, PUT");
        $this->userService = new UserService(currentUser: $this->user);
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
        $user = $this->userService->getCurrentUserInfo();

        if (!$user) {
            $this->response->setCode(404);
            return;
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
     * Met à jour l"utilisateur courant.
     */
    public function update()
    {
        $input = $this->request->body;

        $updatedUser = $this->userService->updateCurrentUser($input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($updatedUser);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $this->user->uid, $updatedUser);
    }
}
