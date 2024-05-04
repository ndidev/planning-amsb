<?php

namespace App\Controller\Timber;

use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Service\TimberService;

class TransportSuggestionsController extends Controller
{
    private $service;
    private $module = "bois";

    public function __construct()
    {
        parent::__construct();
        $this->service = new TimberService();
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
                $this->readAll($this->request->query);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Send the HTTP response
        $this->response->send();
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
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setBody(json_encode($suggestions));
    }
}
