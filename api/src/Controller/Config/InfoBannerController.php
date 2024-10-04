<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Service\InfoBannerService;

class InfoBannerController extends Controller
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

    public function processRequest()
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
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
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
            $this->response->setCode(404);
            return;
        }

        if (!$this->user->canAccess($line->getModule())) {
            throw new AccessException();
        }

        $etag = ETag::get($line);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($line);
    }

    /**
     * Crée une ligne de bandeau d'informations.
     */
    public function create()
    {
        $input = $this->request->body;

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit(Module::tryFrom($input["module"]))
        ) {
            throw new AccessException();
        }

        // $newLine = $this->model->create($input);
        $newLine = $this->infoBannerService->createLine($input);

        $id = $newLine->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/bandeau-info/$id";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
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
        $current = $this->infoBannerService->getLine($id);

        if (!$current) {
            $message = "Not Found";
            $docURL = $_ENV["API_URL"] . "/doc/#/Bois/modifierLigneBandeauInfo";
            $body = json_encode(["message" => $message, "documentation_url" => $docURL]);
            $this->response->setCode(404)->setBody($body);
            return;
        }

        $input = $this->request->body;

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($current->getModule())
            || !$this->user->canEdit(Module::tryFrom($input["module"]))
        ) {
            throw new AccessException();
        }

        // $updatedBannerEntry = $this->model->update($id, $input);
        $updatedLine = $this->infoBannerService->updateLine($id, $input);

        $this->response
            ->setBody(json_encode($updatedLine))
            ->setHeaders($this->headers);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedLine);
    }

    /**
     * Supprime une ligne du bandeau d'informations.
     * 
     * @param int $id id de la ligne à supprimer.
     */
    public function delete(int $id)
    {
        $line = $this->infoBannerService->getLine($id);

        if (!$line) {
            $message = "Not Found";
            $docURL = $_ENV["API_URL"] . "/doc/#/Bandeau/supprimerLigneBandeauInfo";
            $body = json_encode(["message" => $message, "documentation_url" => $docURL]);
            $this->response->setCode(404)->setBody($body);
            return;
        }

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($line->getModule())
        ) {
            throw new AccessException();
        }

        $this->infoBannerService->deleteLine($id);

        $this->response->setCode(204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
