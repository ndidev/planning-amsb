<?php

namespace App\Controller\ThirdParty;

use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\ThirdPartyService;

class AppointmentCountController extends Controller
{
    private ThirdPartyService $service;

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct();
        $this->service = new ThirdPartyService();
        $this->processRequest();
    }

    public function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204)->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->read($this->id);
                break;

            default:
                $this->response->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Retrieves the number of appointments for a third party or all third parties.
     * 
     * @param ?int $id Optional. The id of the third party to retrieve.
     */
    private function read(?int $id)
    {
        if ($id && !$this->service->thirdPartyExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $appointmentCount = $this->service->getAppointmentCount($id);

        $etag = ETag::get($appointmentCount);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($appointmentCount);
    }
}
