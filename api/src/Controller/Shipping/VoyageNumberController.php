<?php

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\ShippingService;

class VoyageNumberController extends Controller
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

            case 'HEAD':
            case 'GET':
                $this->read();
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
    public function read()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $input = $this->request->query;

        $shipName = $input["navire"] ?? "";
        $currentCallId = $input["id"] ?? "";

        $voyageNumber = $this->shippingService->getLastVoyageNumber($shipName, $currentCallId);

        $this->response->setJSON(['voyage' => $voyageNumber]);
    }
}
