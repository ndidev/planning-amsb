<?php

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\ShippingService;

class ShippingStatsController extends Controller
{
    private ShippingService $shippingService;
    private Module $module = Module::SHIPPING;

    public function __construct(
        private ?string $ids = null,
    ) {
        parent::__construct();
        $this->shippingService = new ShippingService();
        $this->processRequest();
    }

    private function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'GET':
            case 'HEAD':
                if ($this->ids) {
                    $this->readDetails($this->ids);
                } else {
                    $this->readSummary($this->request->query);
                }
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère toutes les escales consignation.
     * 
     * @param array $filter
     */
    public function readSummary(array $filter)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $stats = $this->shippingService->getStatsSummary($filter);

        $etag = ETag::get($stats);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($stats);
    }

    /**
     * Récupère toutes les escales consignation.
     * 
     * @param array $filtre
     */
    public function readDetails(string $ids)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $stats = $this->shippingService->getStatsDetails($ids);

        $etag = ETag::get($stats);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($stats);
    }
}
