<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\InfoBannerService;

final class InfoBannerController extends Controller
{
    private InfoBannerService $infoBannerService;
    private Module $module = Module::CONFIG;
    private string $sseEventName = "config/bandeau-info";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->infoBannerService = new InfoBannerService();
        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch ($this->request->method) {
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
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère toutes les lignes du bandeau.
     * 
     * @param array $filter
     */
    public function readAll(array $filter)
    {
        $lines = $this->infoBannerService->getAllLines($filter);

        // Filtre sur les catégories autorisées pour l'utilisateur
        foreach ($lines as $line) {
            if (!$this->user->canAccess($line->getModule())) {
                $lines->remove($line);
            }
        }

        $etag = ETag::get($lines);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($lines);
    }

    /**
     * Récupère une ligne du bandeau.
     * 
     * @param int  $id ID de la ligne à récupérer.
     */
    public function read(int $id)
    {
        $line = $this->infoBannerService->getLine($id);

        if (!$line) {
            throw new NotFoundException("Cette ligne de bandeau d'information n'existe pas.");
        }

        if (!$this->user->canAccess($line->getModule())) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux informations {$line->getModule()->value}.");
        }

        $etag = ETag::get($line);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($line);
    }

    /**
     * Crée une ligne de bandeau d'informations.
     */
    public function create()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        $input = $this->request->getBody();

        if (!$this->user->canEdit(Module::tryFrom($input["module"]))) {
            throw new AccessException("Vous n'avez pas les droits pour modifier les informations de ce module.");
        }

        $newLine = $this->infoBannerService->createLine($input);

        $id = $newLine->getId();

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", $_ENV["API_URL"] . "/bandeau-info/$id")
            ->setJSON($newLine);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newLine);
    }

    /**
     * Met à jour une ligne du bandeau d'informations.
     * 
     * @param int $id id de la ligne à modifier.
     */
    public function update(int $id)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        $current = $this->infoBannerService->getLine($id);

        if (!$current) {
            throw new NotFoundException("Cette ligne de bandeau d'information n'existe pas.");
        }

        $input = $this->request->getBody();

        if (
            !$this->user->canEdit($current->getModule())
            || !$this->user->canEdit(Module::tryFrom($input["module"]))
        ) {
            throw new AccessException("Vous n'avez pas les droits pour modifier les informations de ce module.");
        }

        $updatedLine = $this->infoBannerService->updateLine($id, $input);

        $this->response->setJSON($updatedLine);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedLine);
    }

    /**
     * Supprime une ligne du bandeau d'informations.
     * 
     * @param int $id id de la ligne à supprimer.
     */
    public function delete(int $id)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        $line = $this->infoBannerService->getLine($id);

        if (!$line) {
            throw new NotFoundException("Cette ligne de bandeau d'information n'existe pas.");
        }

        if (!$this->user->canEdit($line->getModule())) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer les informations {$line->getModule()->value}.");
        }

        $this->infoBannerService->deleteLine($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
