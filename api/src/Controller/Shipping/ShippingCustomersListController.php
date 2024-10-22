<?php

// Path: api/src/Controller/Shipping/ShippingCustomersListController.php

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\ShippingService;

/**
 * Liste des clients en consignation.
 */
class ShippingCustomersListController extends Controller
{
    private ShippingService $shippingService;
    private Module $module = Module::SHIPPING;

    public function __construct()
    {
        parent::__construct();
        $this->shippingService = new ShippingService();
        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'GET':
            case 'HEAD':
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
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($customers);
    }
}
