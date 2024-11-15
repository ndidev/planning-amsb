<?php

// Path: api/src/Controller/Charter/CharterController.php

declare(strict_types=1);

namespace App\Controller\Chartering;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Array\Environment;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\DTO\Filter\CharteringFilterDTO;
use App\Service\CharteringService;

final class CharterController extends Controller
{
    private CharteringService $charteringService;
    private string $module = Module::CHARTERING;
    private string $sseEventName = "chartering/charters";

    public function __construct(
        private ?int $id = null
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->charteringService = new CharteringService;
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
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les affrètements maritimes.
     */
    public function readAll(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux affrètements.");
        }

        $filter = new CharteringFilterDTO($this->request->getQuery());

        $charters = $this->charteringService->getCharters($filter);

        $etag = ETag::get($charters);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($charters);
    }

    /**
     * Récupère un affrètement maritime.
     * 
     * @param int  $id ID de l'affrètement à récupérer.
     */
    public function read(int $id): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux affrètements.");
        }

        $charter = $this->charteringService->getCharter($id);

        if (!$charter) {
            throw new NotFoundException("L'affrètement n'existe pas.");
        }

        $etag = ETag::get($charter);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($charter);
    }

    /**
     * Crée un affrètement maritime.
     */
    public function create(): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour créer un affrètement.");
        }

        $input = $this->request->getBody();

        $newCharter = $this->charteringService->createCharter($input);

        /** @var int $id */
        $id = $newCharter->getId();

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/chartering/charters/$id")
            ->setJSON($newCharter);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newCharter);
    }

    /**
     * Met à jour un affrètement.
     * 
     * @param ?int $id id de l'affrètement à modifier.
     */
    public function update(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier un affrètement.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de l'affrètement est obligatoire.");
        }

        if (!$this->charteringService->charterExists($id)) {
            throw new NotFoundException("L'affrètement n'existe pas.");
        }

        $input = $this->request->getBody();

        $updatedCharter = $this->charteringService->updateCharter($id, $input);

        $this->response->setJSON($updatedCharter);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedCharter);
    }

    /**
     * Supprime un affrètement.
     * 
     * @param ?int $id id de l'affrètement à supprimer.
     */
    public function delete(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer un affrètement.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de l'affrètement est obligatoire.");
        }

        if (!$this->charteringService->charterExists($id)) {
            throw new NotFoundException("L'affrètement n'existe pas.");
        }

        $this->charteringService->deleteCharter($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
