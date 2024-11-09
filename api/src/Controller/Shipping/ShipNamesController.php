<?php

// Path: api/src/Controller/Shipping/ShipNamesController.php

declare(strict_types=1);

namespace App\Controller\Shipping;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\ShippingService;

/**
 * Liste des navires ayant fait escale.
 */
final class ShipNamesController extends Controller
{
    private ShippingService $shippingService;
    /** @phpstan-var Module::* $module */
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
                $this->readAllShipNames();
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
    public function readAllShipNames(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $shipNames = $this->shippingService->getAllShipNames();

        $etag = ETag::get($shipNames);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($shipNames);
    }
}
