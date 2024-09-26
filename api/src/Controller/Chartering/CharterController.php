<?php

namespace App\Controller\Chartering;

use App\Service\CharteringService;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;

class CharterController extends Controller
{
    private CharteringService $charteringService;
    private string $module = "chartering";
    private string $sseEventName = "chartering/charters";

    public function __construct(
        private ?int $id = null
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->charteringService = new CharteringService;
        $this->processRequest();
    }

    public function processRequest(): void
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
     * Récupère tous les affrètements maritimes.
     * 
     * @param array $query
     */
    public function readAll(array $query)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $charters = $this->charteringService->getCharters($query);

        $etag = ETag::get($charters);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($charters);
    }

    /**
     * Récupère un affrètement maritime.
     * 
     * @param int  $id      id de l'affrètement à récupérer.
     * @param bool $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dryRun = false)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $charter = $this->charteringService->getCharter($id);

        if (!$charter && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if ($dryRun) {
            return $charter;
        }

        $etag = ETag::get($charter);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($charter);
    }

    /**
     * Crée un affrètement maritime.
     */
    public function create()
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $newCharter = $this->charteringService->createCharter($input);

        $id = $newCharter->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/chartering/charters/$id";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setJSON($newCharter);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newCharter);
    }

    /**
     * Met à jour un affrètement.
     * 
     * @param int $id id de l'affrètement à modifier.
     */
    public function update(int $id)
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        if (!$this->charteringService->charterExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $updatedCharter = $this->charteringService->updateCharter($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($updatedCharter);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedCharter);
    }

    /**
     * Supprime un affrètement.
     * 
     * @param int $id id de l'affrètement à supprimer.
     */
    public function delete(int $id)
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        if (!$this->charteringService->charterExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $this->charteringService->deleteCharter($id);

        $this->response->setCode(204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
