<?php

// Path: api/src/Controller/Shipping/ShippingCallController.php

declare(strict_types=1);

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\Array\Environment;
use App\Core\Component\Module;
use App\Core\Component\SseEventNames;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\DTO\Filter\ShippingFilterDTO;
use App\Service\ShippingService;

final class ShippingCallController extends Controller
{
    private ShippingService $shippingService;
    private string $module = Module::SHIPPING;
    private string $sseEventName = SseEventNames::SHIPPING_CALL;

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->shippingService = new ShippingService();
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
     * Récupère tous les escale consignation.
     */
    public function readAll(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux escales.");
        }

        $filter = new ShippingFilterDTO($this->request->getQuery());

        $calls = $this->shippingService->getShippingCalls($filter);

        $etag = ETag::get($calls);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($calls);
    }

    /**
     * Récupère une escale consignation.
     * 
     * @param int $id id de l'escale à récupérer.
     */
    public function read(int $id): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux escales.");
        }

        $call = $this->shippingService->getShippingCall($id);

        if (!$call) {
            throw new NotFoundException("Cette escale n'existe pas.");
        }

        $etag = ETag::get($call);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($call);
    }

    /**
     * Crée une escale consignation.
     */
    public function create(): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour créer une escale.");
        }

        $input = $this->request->getBody();

        $newCall = $this->shippingService->createShippingCall($input);

        /** @var int $id */
        $id = $newCall->id;

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/consignation/escales/$id")
            ->setJSON($newCall);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newCall);
    }

    /**
     * Met à jour une escale.
     * 
     * @param ?int $id id de l'escale à modifier.
     */
    public function update(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier une escale.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de l'escale est obligatoire.");
        }

        if (!$this->shippingService->callExists($id)) {
            throw new NotFoundException("Cette escale n'existe pas.");
        }

        $input = $this->request->getBody();

        $updatedCall = $this->shippingService->updateShippingCall($id, $input);

        $this->response->setJSON($updatedCall);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedCall);
        if ($updatedCall->shipReport?->id) {
            $this->sse->addEvent(
                SseEventNames::STEVEDORING_SHIP_REPORT,
                __FUNCTION__,
                $updatedCall->shipReport->id,
                $updatedCall->shipReport
            );
        }
    }

    /**
     * Supprime une escale.
     * 
     * @param ?int $id id de l'escale à supprimer.
     */
    public function delete(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer une escale.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant de l'escale est obligatoire.");
        }

        if (!$this->shippingService->callExists($id)) {
            throw new NotFoundException("Cette escale n'existe pas.");
        }

        $this->shippingService->deleteShippingCall($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
