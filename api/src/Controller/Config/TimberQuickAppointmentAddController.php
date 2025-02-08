<?php

// Path: api/src/Controller/Config/TimberQuickAppointmentAddController.php

declare(strict_types=1);

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Array\Environment;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\QuickAppointmentAddService;

final class TimberQuickAppointmentAddController extends Controller
{
    private QuickAppointmentAddService $quickAppointmentAddService;
    private string $module = Module::CONFIG;
    private string $sseEventName = "config/ajouts-rapides";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->quickAppointmentAddService = new QuickAppointmentAddService();
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
                    $this->readConfig($this->id);
                } else {
                    $this->readAllConfigs();
                }
                break;

            case 'POST':
                $this->createConfig();
                break;

            case 'PUT':
                $this->updateConfig($this->id);
                break;

            case 'DELETE':
                $this->deleteConfig($this->id);
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les ajouts rapides.
     */
    public function readAllConfigs(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        $quickAdds = $this->quickAppointmentAddService->getAllTimberQuickAppointmentAdds();

        $etag = ETag::get($quickAdds);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($quickAdds);
    }

    /**
     * Récupère un ajout rapide.
     * 
     * @param int  $id ID de l'ajout rapide à récupérer.
     */
    public function readConfig(int $id): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        $quickAppointment = $this->quickAppointmentAddService->getTimberQuickAppointmentAdd($id);

        if (!$quickAppointment) {
            throw new NotFoundException("L'ajout rapide n'existe pas.");
        }

        $etag = ETag::get($quickAppointment);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($quickAppointment);
    }

    /**
     * Crée un ajout rapide.
     */
    public function createConfig(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        $input = $this->request->getBody();

        $newQuickAppointment = $this->quickAppointmentAddService->createTimberQuickAppointmentAdd($input);

        /** @var int $id */
        $id = $newQuickAppointment->id;

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/ajouts-rapides/bois/$id")
            ->setJSON($newQuickAppointment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newQuickAppointment);
    }

    /**
     * Met à jour un ajout rapide.
     * 
     * @param ?int $id id de l'ajout rapide à modifier.
     */
    public function updateConfig(?int $id = null): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        if (!$this->user->canEdit(Module::TIMBER)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier un ajout rapide bois.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de l'ajout rapide est obligatoire.");
        }

        if (!$this->quickAppointmentAddService->quickAddExists(Module::TIMBER, $id)) {
            throw new NotFoundException("L'ajout rapide n'existe pas.");
        }

        $input = $this->request->getBody();

        $updatedQuickAppointment = $this->quickAppointmentAddService->updateTimberQuickAppointmentAdd($id, $input);

        $this->response->setJSON($updatedQuickAppointment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedQuickAppointment);
    }

    /**
     * Supprime un ajout rapide.
     * 
     * @param ?int $id id de l'ajout rapide à supprimer.
     */
    public function deleteConfig(?int $id = null): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        if (!$this->user->canEdit(Module::TIMBER)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer un ajout rapide bois.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de l'ajout rapide est obligatoire.");
        }

        if (!$this->quickAppointmentAddService->quickAddExists(Module::TIMBER, $id)) {
            throw new NotFoundException("L'ajout rapide n'existe pas.");
        }

        $this->quickAppointmentAddService->deleteTimberQuickAppointmentAdd($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
