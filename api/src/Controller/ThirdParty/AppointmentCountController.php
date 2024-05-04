<?php

namespace App\Controller\ThirdParty;

use App\Service\ThirdPartyService;
use App\Controller\Controller;
use App\Core\HTTP\ETag;

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

    public function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supported_methods);
                break;

            case 'HEAD':
            case 'GET':
                $this->read($this->id);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Send the HTTP response
        $this->response->send();
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
            ->setBody(json_encode($appointmentCount));
    }
}
