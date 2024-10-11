<?php

namespace App\Controller\User;

use App\Controller\Controller;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
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

    private function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->read();
                break;

            case 'PUT':
                $this->update();
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
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
            throw new NotFoundException();
        }

        $etag = ETag::get($user);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($user);
    }

    /**
     * Met à jour l"utilisateur courant.
     */
    public function update()
    {
        $user = $this->userService->getCurrentUserInfo();

        if (!$user) {
            throw new NotFoundException();
        }

        $input = $this->request->getBody();

        $updatedUser = $this->userService->updateCurrentUser($input);

        $this->response->setJSON($updatedUser);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $this->user->uid, $updatedUser);
    }
}
