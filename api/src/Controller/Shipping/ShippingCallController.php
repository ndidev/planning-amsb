<?php

// Path: api/src/Controller/Shipping/ShippingCallController.php

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\ShippingService;

class ShippingCallController extends Controller
{
    private ShippingService $shippingService;
    private Module $module = Module::SHIPPING;
    private string $sseEventName = "consignation/escales";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->shippingService = new ShippingService();
        $this->processRequest();
    }

    public function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204)->addHeader("Allow", $this->supportedMethods);
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
                $this->response->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les escale consignation.
     * 
     * @param array $archives
     */
    public function readAll(array $archives)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $calls = $this->shippingService->getShippingCalls($archives);

        $etag = ETag::get($calls);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($calls);
    }

    /**
     * Récupère une escale consignation.
     * 
     * @param int  $id      id de l'escale à récupérer.
     * @param bool $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dryRun = false)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $call = $this->shippingService->getShippingCall($id);

        if (!$call && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if ($dryRun) {
            return $call;
        }

        $etag = ETag::get($call);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($call);
    }

    /**
     * Crée une escale consignation.
     */
    public function create()
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $newCall = $this->shippingService->createShippingCall($input);

        $id = $newCall->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/consignation/escales/$id";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setJSON($newCall);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newCall);
    }

    /**
     * Met à jour une escale.
     * 
     * @param int $id id de l'escale à modifier.
     */
    public function update(int $id)
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        if (!$this->shippingService->callExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $updatedCall = $this->shippingService->updateShippingCall($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($updatedCall);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedCall);
    }

    /**
     * Supprime une escale.
     * 
     * @param int $id id de l'escale à supprimer.
     */
    public function delete(int $id)
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException();
        }

        if (!$this->shippingService->callExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $this->shippingService->deleteShippingCall($id);

        $this->response->setCode(204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
