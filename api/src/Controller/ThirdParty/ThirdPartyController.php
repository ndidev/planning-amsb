<?php

namespace App\Controller\ThirdParty;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Entity\ThirdParty;
use App\Service\ThirdPartyService;

class ThirdPartyController extends Controller
{
    private ThirdPartyService $thirdPartyService;
    private Module $module = Module::THIRD_PARTY;
    private string $sseEventName = "tiers";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->thirdPartyService = new ThirdPartyService();
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
                $this->response->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Retrieves all third parties.
     */
    public function readAll()
    {
        $thirdParties = $this->thirdPartyService->getThirdParties();

        $etag = ETag::get($thirdParties);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($thirdParties);
    }

    /**
     * Retrieves a third party.
     * 
     * @param int   $id      id of the third party to retrieve.
     * @param bool  $dryRun Retrieve the resource without sending the HTTP response.
     */
    public function read(int $id, ?bool $dryRun = false)
    {
        $thirdParty = $this->thirdPartyService->getThirdParty($id);

        if (!$thirdParty && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if ($dryRun) {
            return $thirdParty;
        }

        $etag = ETag::get($thirdParty);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($thirdParty);
    }

    /**
     * Create a third party.
     */
    public function create()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->body;

        $thirdParty = $this->thirdPartyService->createThirdParty($input);

        $id = $thirdParty->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/tiers/$id";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setJSON($thirdParty);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $thirdParty->toArray());
    }

    /**
     * Updates a third party.
     * 
     * @param int $id id of the third party to modify.
     */
    public function update(int $id)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        if (!$this->thirdPartyService->thirdPartyExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $thirdParty = $this->thirdPartyService->updateThirdParty($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($thirdParty);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $thirdParty->toArray());
    }

    /**
     * Deletes a third party.
     * 
     * @param int $id id of the third party to delete.
     */
    public function delete(int $id)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        if (!$this->thirdPartyService->thirdPartyExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $this->thirdPartyService->deleteThirdParty($id);

        $this->response->setCode(204);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
