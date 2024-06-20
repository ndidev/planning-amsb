<?php

namespace App\Controller\ThirdParty;

use App\Service\ThirdPartyService;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Entity\ThirdParty;

class ThirdPartyController extends Controller
{
    private ThirdPartyService $thirdPartyService;
    private $module = "tiers";
    private $sse_event = "tiers";

    public function __construct(
        private ?int $id,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->thirdPartyService = new ThirdPartyService();
        $this->processRequest();
    }

    public function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supported_methods);
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
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Send the HTTP response.
        $this->response->send();
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
            ->setJSON(array_map(fn (ThirdParty $thirdParty) => $thirdParty->toArray(), $thirdParties));
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

        notify_sse($this->sse_event, __FUNCTION__, $id, $thirdParty->toArray());
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

        notify_sse($this->sse_event, __FUNCTION__, $id, $thirdParty->toArray());
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

        $this->response->setCode(204)->flush();

        notify_sse($this->sse_event, __FUNCTION__, $id);
    }
}
