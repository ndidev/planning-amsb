<?php

namespace App\Controller\Timber;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\TimberService;

class TransportSuggestionsController extends Controller
{
    private TimberService $service;
    private Module $module = Module::TIMBER;

    public function __construct()
    {
        parent::__construct();
        $this->service = new TimberService();
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
                $this->readAll($this->request->query);
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Renvoie les suggestions de transporteurs pour un chargement et une livraison.
     * 
     * @param array $filter
     */
    public function readAll(array $filter)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $loadingPlaceId = $filter["chargement"] ?? 0;
        $deliveryPlaceId = $filter["livraison"] ?? 0;

        $suggestions = $this->service->getTransportSuggestions($loadingPlaceId, $deliveryPlaceId);

        $etag = ETag::get($suggestions);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($suggestions);
    }
}
