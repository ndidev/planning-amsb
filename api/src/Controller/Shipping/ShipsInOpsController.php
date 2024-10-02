<?php

namespace App\Controller\Shipping;

use App\Models\Consignation\NaviresEnActiviteModel;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Service\ShippingService;

/**
 * Liste des navires en opération entre deux dates.
 */
class ShipsInOpsController extends Controller
{
    private ShippingService $shippingService;
    private $module = "consignation";

    public function __construct()
    {
        parent::__construct();
        $this->shippingService = new ShippingService();
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
                $this->readAll();
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Renvoie le dernier numéro de voyage du navire.
     */
    public function readAll()
    {
        $query = $this->request->query;

        $shipsInOps = $this->shippingService->getShipsInOps($query);

        $etag = ETag::get($shipsInOps);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($shipsInOps);
    }
}
