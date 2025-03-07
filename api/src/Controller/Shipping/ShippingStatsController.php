<?php

// Path: api/src/Controller/Shipping/ShippingStatsController.php

declare(strict_types=1);

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\DTO\Filter\ShippingFilterDTO;
use App\Service\ShippingService;

final class ShippingStatsController extends Controller
{
    private ShippingService $shippingService;
    private string $module = Module::SHIPPING;

    public function __construct(
        private string|int|null $ids = null,
    ) {
        parent::__construct();
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

            case 'GET':
            case 'HEAD':
                if ($this->ids) {
                    $this->readDetails((string) $this->ids);
                } else {
                    $this->readSummary();
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
     */
    public function readSummary(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $filter = new ShippingFilterDTO($this->request->getQuery());

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
     * Récupère le détails d'escales consignation.
     * 
     * @param string $ids Liste des identifiants.
     */
    public function readDetails(string $ids): void
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
