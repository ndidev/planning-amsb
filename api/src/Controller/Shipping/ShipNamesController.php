<?php

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Service\ShippingService;

/**
 * Liste des navires ayant fait escale.
 */
class ShipNamesController extends Controller
{
    private ShippingService $shippingService;
    private Module $module = Module::SHIPPING;

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
                $this->readAllShipNames();
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Renvoie le dernier numéro de voyage du navire.
     */
    public function readAllShipNames()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $shipNames = $this->shippingService->getAllShipNames();

        $etag = ETag::get($shipNames);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($shipNames);
    }
}
