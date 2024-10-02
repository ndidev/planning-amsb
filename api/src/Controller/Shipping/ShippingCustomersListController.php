<?php

// Path: api/src/Controller/Shipping/ShippingCustomersListController.php

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Service\ShippingService;

/**
 * Liste des clients en consignation.
 */
class ShippingCustomersListController extends Controller
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

            case 'GET':
            case 'HEAD':
                $this->readAll();
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Renvoie la liste des clients utilisés en consignation.
     */
    public function readAll()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $customers = $this->shippingService->getAllCustomersNames();

        $etag = ETag::get($customers);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($customers);
    }
}
