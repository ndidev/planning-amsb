<?php

// Path: api/src/Controller/Bulk/AppointmentCountController.php

declare(strict_types=1);

namespace App\Controller\Bulk;

use App\Controller\Controller;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\BulkService;

final class AppointmentCountController extends Controller
{
    private BulkService $service;

    public function __construct(
        private int $id,
    ) {
        parent::__construct();
        $this->service = new BulkService();
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
                $this->read($this->id);
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Retrieves the number of appointments for a bulk product.
     * 
     * @param int $id The id of the bulk product to retrieve.
     */
    private function read(int $id): void
    {
        if (!$this->service->productExists($id)) {
            throw new NotFoundException("Le produit n'existe pas.");
        }

        $appointmentCount = $this->service->getAppointmentCountForProductId($id);

        $etag = ETag::get($appointmentCount);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($appointmentCount);
    }
}
