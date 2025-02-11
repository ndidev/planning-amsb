<?php

// Path: api/src/Controller/Admin/UserAccountController.php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Controller;
use App\Core\Exceptions\Client\Auth\AdminException;
use App\Core\Exceptions\Client\Auth\ForbiddenException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Array\Environment;
use App\Core\Component\SseEventNames;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\UserService;

final class UserAccountController extends Controller
{
    private UserService $userService;
    private string $sseEventName = SseEventNames::USER_ACCOUNT;

    public function __construct(
        private ?string $uid = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");

        if ($this->user->isAdmin === false) {
            throw new AdminException();
        }

        $this->userService = new UserService($this->user);

        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch ($this->request->getMethod()) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
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
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous comptes utilisateurs.
     */
    public function readAll(): void
    {
        $users = $this->userService->getUserAccounts();

        $etag = ETag::get($users);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($users);
    }

    /**
     * Récupère un compte utilisateur.
     * 
     * @param string $uid UID du compte à récupérer.
     */
    public function read(string $uid): void
    {
        $user = $this->userService->getUserAccount($uid);

        if (!$user) {
            throw new NotFoundException("L'utilisateur n'existe pas.");
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
     * Crée un compte utilisateur.
     */
    public function create(): void
    {
        $input = $this->request->getBody();

        $newUser = $this->userService->createUserAccount($input);

        /** @var string $uid */
        $uid = $newUser->uid;

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/admin/users/$uid")
            ->setJSON($newUser);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $uid, $newUser);
    }

    /**
     * Met à jour un compte utilisateur.
     * 
     * @param ?string $uid UID du compte à modifier.
     */
    public function update(?string $uid = null): void
    {
        if (!$uid) {
            throw new BadRequestException("L'identifiant de l'utilisateur est obligatoire.");
        }

        if (!$this->userService->userExists($uid)) {
            throw new NotFoundException("L'utilisateur n'existe pas.");
        }

        $input = $this->request->getBody();

        $updatedUser = $this->userService->updateUserAccount($uid, $input);

        $this->response->setJSON($updatedUser);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $uid, $updatedUser);
    }

    /**
     * Supprime un compte utilisateur.
     * 
     * @param ?string $uid UID du compte à supprimer.
     */
    public function delete(?string $uid = null): void
    {
        if (!$uid) {
            throw new BadRequestException("L'identifiant de l'utilisateur est obligatoire.");
        }

        // Un utilisateur ne peut pas se supprimer lui-même
        if ($this->user->uid === $uid) {
            throw new ForbiddenException("Un administrateur ne peut pas supprimer son propre compte.");
        }

        if (!$this->userService->userExists($uid)) {
            throw new NotFoundException("L'utilisateur n'existe pas.");
        }

        $this->userService->deleteUserAccount($uid, $this->user->name);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $uid);
    }
}
