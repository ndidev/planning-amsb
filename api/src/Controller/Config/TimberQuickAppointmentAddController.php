<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Service\QuickAppointmentAddService;

class TimberQuickAppointmentAddController extends Controller
{
    private QuickAppointmentAddService $quickAppointmentAddService;
    private Module $module = Module::CONFIG;
    private string $sseEventName = "config/ajouts-rapides";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->quickAppointmentAddService = new QuickAppointmentAddService();
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
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les ajouts rapides.
     */
    public function readAllConfigs()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $quickAdds = $this->quickAppointmentAddService->getAllTimberQuickAppointmentAdds();

        $etag = ETag::get($quickAdds);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($quickAdds);
    }

    /**
     * Récupère un ajout rapide.
     * 
     * @param int  $id      id de l'ajout rapide à récupérer.
     * @param bool $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function readConfig(int $id)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $quickAppointment = $this->quickAppointmentAddService->getTimberQuickAppointmentAdd($id);

        if (!$quickAppointment) {
            throw new NotFoundException("La configuration n'existe pas.");
        }

        $etag = ETag::get($quickAppointment);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($quickAppointment);
    }

    /**
     * Crée un ajout rapide.
     */
    public function createConfig()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $newQuickAppointment = $this->quickAppointmentAddService->createTimberQuickAppointmentAdd($input);

        $id = $newQuickAppointment->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/ajouts-rapides/bois/$id";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setJSON($newQuickAppointment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newQuickAppointment);
    }

    /**
     * Met à jour un ajout rapide.
     * 
     * @param int $id id de l'ajout rapide à modifier.
     */
    public function updateConfig(int $id)
    {
        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit(Module::TIMBER)
        ) {
            throw new AccessException();
        }

        if (!$this->quickAppointmentAddService->quickAddExists(Module::TIMBER, $id)) {
            throw new NotFoundException("La configuration n'existe pas.");
        }

        $input = $this->request->body;

        $updatedQuickAppointment = $this->quickAppointmentAddService->updateTimberQuickAppointmentAdd($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($updatedQuickAppointment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedQuickAppointment);
    }

    /**
     * Supprime un ajout rapide.
     * 
     * @param int $id id de l'ajout rapide à supprimer.
     */
    public function deleteConfig(int $id)
    {
        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit(Module::TIMBER)
        ) {
            throw new AccessException();
        }

        if (!$this->quickAppointmentAddService->quickAddExists(Module::TIMBER, $id)) {
            throw new NotFoundException("La configuration n'existe pas.");
        }

        $this->quickAppointmentAddService->deleteTimberQuickAppointmentAdd($id);

        $this->response->setCode(204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
