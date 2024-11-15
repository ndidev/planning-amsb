<?php

// Path: api/src/Controller/ThirdParty/ThirdPartyController.php

declare(strict_types=1);

namespace App\Controller\ThirdParty;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Array\Environment;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\ThirdPartyService;

final class ThirdPartyController extends Controller
{
    private ThirdPartyService $thirdPartyService;
    private string $module = Module::THIRD_PARTY;
    private string $sseEventName = "tiers";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->thirdPartyService = new ThirdPartyService();
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
     * Retrieves all third parties.
     */
    public function readAll(): void
    {
        $thirdParties = $this->thirdPartyService->getThirdParties();

        $etag = ETag::get($thirdParties);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($thirdParties);
    }

    /**
     * Retrieves a third party.
     * 
     * @param int $id id of the third party to retrieve.
     */
    public function read(int $id): void
    {
        $thirdParty = $this->thirdPartyService->getThirdParty($id);

        if (!$thirdParty) {
            throw new NotFoundException("Ce tiers n'existe pas.");
        }

        $etag = ETag::get($thirdParty);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($thirdParty);
    }

    /**
     * Create a third party.
     */
    public function create(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour créer un tiers.");
        }

        $input = $this->request->getBody();

        if ($input->isEmpty()) {
            throw new ClientException("Le corps de la requête est vide.");
        }

        $thirdParty = $this->thirdPartyService->createThirdParty($input);

        /** @var int $id */
        $id = $thirdParty->getId();

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/tiers/$id")
            ->setJSON($thirdParty);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $thirdParty->toArray());
    }

    /**
     * Updates a third party.
     * 
     * @param ?int $id id of the third party to modify.
     */
    public function update(?int $id = null): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier un tiers.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du tiers est obligatoire.");
        }

        if (!$this->thirdPartyService->thirdPartyExists($id)) {
            throw new NotFoundException("Ce tiers n'existe pas.");
        }

        $input = $this->request->getBody();

        $thirdParty = $this->thirdPartyService->updateThirdParty($id, $input);

        $this->response->setJSON($thirdParty);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $thirdParty->toArray());
    }

    /**
     * Deletes a third party.
     * 
     * @param ?int $id id of the third party to delete.
     */
    public function delete(?int $id = null): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer un tiers.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du tiers est obligatoire.");
        }

        if (!$this->thirdPartyService->thirdPartyExists($id)) {
            throw new NotFoundException("Ce tiers n'existe pas.");
        }

        $this->thirdPartyService->deleteThirdParty($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
