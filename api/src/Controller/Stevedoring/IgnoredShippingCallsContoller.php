<?php

// Path: api/src/Controller/Stevedoring/IgnoredShippingCallsContoller.php

declare(strict_types=1);

namespace App\Controller\Stevedoring;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\StevedoringService;

/**
 * Liste des escales consignation ignorées pour les rapports manutention.
 */
final class IgnoredShippingCallsContoller extends Controller
{
    private StevedoringService $stevedoringService;
    private string $module = Module::STEVEDORING;

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, DELETE");
        $this->stevedoringService = new StevedoringService();
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
                $this->read();
                break;

            case 'POST':
                $this->create();
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
     * Renvoie les escales consignation ignorées pour les rapports manutention.
     */
    public function read(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $ignoredCalls = $this->stevedoringService->getIgnoredShippingCalls();

        $etag = ETag::get($ignoredCalls);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($ignoredCalls);
    }

    /**
     * Ajoute une escale consignation à la liste des escales ignorées pour les rapports manutention.
     */
    public function create(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour ignorer une escale consignation.");
        }

        $callId = $this->request->getBody()->getInt('callId');

        if (!$callId) {
            throw new BadRequestException("L'identifiant de l'escale consignation est obligatoire.");
        }

        $this->stevedoringService->ignoreShippingCall($callId);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
    }

    /**
     * Supprime une escale consignation de la liste des escales ignorées
     * pour les rapports manutention.
     */
    public function delete(?int $callId): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour réintégrer une escale consignation.");
        }

        if (!$callId) {
            throw new BadRequestException("L'identifiant de l'escale consignation est obligatoire.");
        }

        $this->stevedoringService->unignoreShippingCall($callId);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
    }
}
