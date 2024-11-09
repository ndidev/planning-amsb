<?php

// Path: api/src/Controller/Timber/TimberDeliveryNoteController.php

declare(strict_types=1);

namespace App\Controller\Timber;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\HTTPResponse;
use App\Service\TimberService;

final class TimberDeliveryNoteController extends Controller
{
    private TimberService $timberService;
    /** @phpstan-var Module::* $module */
    private string $module = Module::TIMBER;

    public function __construct()
    {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->timberService = new TimberService();
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
     * Récupère  RDV bois.
     */
    public function read(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux RDVs bois.");
        }

        $request = $this->request;

        $supplierId = $request->getQuery()->getParam('supplierId', type: 'int');
        $deliveryNoteNumber = $request->getQuery()->getParam('deliveryNoteNumber', '');
        $currentAppointmentId = $request->getQuery()->getParam('currentAppointmentId', type: 'int');

        $isDeliveryNoteNumberAvailable = $this->timberService->isDeliveryNoteNumberAvailable(
            $deliveryNoteNumber,
            $supplierId,
            $currentAppointmentId,
        );

        $this->response->setJSON($isDeliveryNoteNumberAvailable);
    }
}
