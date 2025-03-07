<?php

// Path: api/src/Controller/Shipping/VoyageNumberController.php

declare(strict_types=1);

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\HTTPResponse;
use App\Service\ShippingService;

final class VoyageNumberController extends Controller
{
    private ShippingService $shippingService;
    private string $module = Module::SHIPPING;

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
    public function read(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $shipName = $this->request->getQuery()->getString('navire');
        $currentCallId = $this->request->getQuery()->getInt('id');

        $voyageNumber = $this->shippingService->getLastVoyageNumber($shipName, $currentCallId);

        $this->response->setJSON(['voyage' => $voyageNumber]);
    }
}
