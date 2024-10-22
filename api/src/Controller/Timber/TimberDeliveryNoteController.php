<?php

// Path: api/src/Controller/Timber/TimberDeliveryNoteController.php

namespace App\Controller\Timber;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\ClientException;
use App\Core\HTTP\HTTPResponse;
use App\Service\TimberService;

final class TimberDeliveryNoteController extends Controller
{
    private TimberService $timberService;
    private Module $module = Module::TIMBER;

    public function __construct()
    {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->timberService = new TimberService();
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
     * Récupère  RDV bois.
     */
    public function read()
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux RDVs bois.");
        }

        $input = $this->request->query;

        $supplierId = $input["supplierId"] ?? null;
        $deliveryNoteNumber = $input["deliveryNoteNumber"] ?? null;
        $currentAppointmentId = $input["currentAppointmentId"] ?? null;

        if (!$supplierId) {
            throw new ClientException("L'identifiant du fournisseur est obligatoire.");
        }

        if (!$deliveryNoteNumber) {
            throw new ClientException("Le numéro de BL est obligatoire.");
        }

        $isDeliveryNoteNumberAvailable = $this->timberService->isDeliveryNoteNumberAvailable(
            $deliveryNoteNumber,
            $supplierId,
            $currentAppointmentId,
        );

        $this->response->setJSON($isDeliveryNoteNumberAvailable);
    }
}
