<?php

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\ShippingService;

/**
 * Liste des navires en opération entre deux dates.
 */
final class ShipsInOpsController extends Controller
{
    private ShippingService $shippingService;

    public function __construct()
    {
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

            case 'HEAD':
            case 'GET':
                $this->readAll();
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Renvoie le dernier numéro de voyage du navire.
     */
    public function readAll(): void
    {
        $query = $this->request->getQuery();

        $startDate = new \DateTimeImmutable($query["date_debut"] ?? '');
        $endDate = new \DateTimeImmutable($query["date_fin"] ?? '9999-12-31');

        $shipsInOps = $this->shippingService->getShipsInOps($startDate, $endDate);

        $etag = ETag::get($shipsInOps);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($shipsInOps);
    }
}
