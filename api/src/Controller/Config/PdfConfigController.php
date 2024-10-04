<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Service\PdfService;

class PdfConfigController extends Controller
{
    private PdfService $pdfConfigService;
    private Module $module = Module::CONFIG;
    private string $sseEventName = "config/pdf";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->pdfConfigService = new PdfService();
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
     * Récupère toutes les configurations PDF.
     */
    public function readAll()
    {
        $pdfConfigs = $this->pdfConfigService->getAllConfigs();

        // Filtre sur les catégories autorisées pour l'utilisateur
        foreach ($pdfConfigs as $config) {
            if (!$this->user->canAccess($config->getModule())) {
                $pdfConfigs->remove($config);
            }
        }

        $etag = ETag::get($pdfConfigs);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($pdfConfigs);
    }

    /**
     * Récupère une configuration PDF.
     * 
     * @param int  $id id de la configuration à récupérer.
     */
    public function read(int $id)
    {
        $pdfConfig = $this->pdfConfigService->getConfig($id);

        if (!$pdfConfig) {
            $this->response->setCode(404);
            return;
        }

        if (!$this->user->canAccess($pdfConfig->getModule())) {
            throw new AccessException();
        }

        $etag = ETag::get($pdfConfig);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($pdfConfig);
    }

    /**
     * Crée une configuration PDF.
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

        $newPdfConfig = $this->pdfConfigService->createConfig($input);

        $id = $newPdfConfig->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/pdf/configs/$id";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setJSON($newPdfConfig);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newPdfConfig);
    }

    /**
     * Met à jour une configuration PDF.
     * 
     * @param int $id id de la configuration à modifier.
     */
    public function update(int $id)
    {
        $current = $this->pdfConfigService->getConfig($id);

        if (!$current) {
            $this->response->setCode(404);
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

        $updatedPdfConfig = $this->pdfConfigService->updateConfig($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($updatedPdfConfig);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedPdfConfig);
    }

    /**
     * Supprime une configuration PDF.
     * 
     * @param int $id id de la configuration PDF à supprimer.
     */
    public function delete(int $id)
    {
        $config = $this->pdfConfigService->getConfig($id);

        if (!$config) {
            $this->response->setCode(404);
            return;
        }

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($config->getModule())
        ) {
            throw new AccessException();
        }

        $this->pdfConfigService->deleteConfig($id);

        $this->response->setCode(204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
