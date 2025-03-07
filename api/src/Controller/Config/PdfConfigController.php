<?php

// Path: api/src/Controller/Config/PdfConfigController.php

declare(strict_types=1);

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Array\Environment;
use App\Core\Component\SseEventNames;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\PdfConfigService;

final class PdfConfigController extends Controller
{
    private PdfConfigService $pdfService;
    private string $module = Module::CONFIG;
    private string $sseEventName = SseEventNames::CONFIG_PDF_CONFIG;

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->pdfService = new PdfConfigService();
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
     * Récupère toutes les configurations PDF.
     */
    public function readAll(): void
    {
        $pdfConfigs = $this->pdfService->getAllConfigs();

        // Filtre sur les catégories autorisées pour l'utilisateur
        foreach ($pdfConfigs as $config) {
            if (!$this->user->canAccess($config->module)) {
                $pdfConfigs->remove($config);
            }
        }

        $etag = ETag::get($pdfConfigs);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($pdfConfigs);
    }

    /**
     * Récupère une configuration PDF.
     * 
     * @param int  $id id de la configuration à récupérer.
     */
    public function read(int $id): void
    {
        $pdfConfig = $this->pdfService->getConfig($id);

        if (!$pdfConfig) {
            throw new NotFoundException("Cette configuration PDF n'existe pas.");
        }

        if (!$this->user->canAccess($pdfConfig->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder à cette configuration PDF.");
        }

        $etag = ETag::get($pdfConfig);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($pdfConfig);
    }

    /**
     * Crée une configuration PDF.
     */
    public function create(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        $input = $this->request->getBody();

        if (!$this->user->canEdit(Module::tryFrom($input->getString('module')))) {
            throw new AccessException("Vous n'avez pas les droits pour créer une configuration PDF {$input->getString('module')}.");
        }

        $newPdfConfig = $this->pdfService->createConfig($input);

        /** @var int $id */
        $id = $newPdfConfig->id;

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/pdf/configs/$id")
            ->setJSON($newPdfConfig);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newPdfConfig);
    }

    /**
     * Met à jour une configuration PDF.
     * 
     * @param ?int $id id de la configuration à modifier.
     */
    public function update(?int $id = null): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de la configuration est obligatoire.");
        }

        $current = $this->pdfService->getConfig($id);

        if (!$current) {
            throw new NotFoundException("Cette configuration PDF n'existe pas.");
        }

        $input = $this->request->getBody();

        if (
            !$this->user->canEdit($current->module)
            || !$this->user->canEdit(Module::tryFrom($input->getString('module')))
        ) {
            throw new AccessException("Vous n'avez pas les droits pour modifier cette configuration PDF.");
        }

        $updatedPdfConfig = $this->pdfService->updateConfig($id, $input);

        $this->response->setJSON($updatedPdfConfig);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedPdfConfig);
    }

    /**
     * Supprime une configuration PDF.
     * 
     * @param ?int $id id de la configuration PDF à supprimer.
     */
    public function delete(?int $id = null): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de la configuration est obligatoire.");
        }

        $config = $this->pdfService->getConfig($id);

        if (!$config) {
            throw new NotFoundException("Cette configuration PDF n'existe pas.");
        }

        if (!$this->user->canEdit($config->module)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer cette configuration PDF.");
        }

        $this->pdfService->deleteConfig($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
