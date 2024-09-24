<?php

namespace App\Controller\Bois;

use App\Models\Bois\SuggestionsTransporteursModel as Model;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;

class SuggestionsTransporteursController extends Controller
{
    private $model;
    private $module = "bois";

    public function __construct()
    {
        parent::__construct();
        $this->model = new Model();
        $this->processRequest();
    }

    public function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->readAll($this->request->query);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Renvoie les suggestions de transporteurs pour un chargement et une livraison.
     * 
     * @param array $filtre
     */
    public function readAll(array $filtre)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $suggestions = $this->model->readAll($filtre);

        $etag = ETag::get($suggestions);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($suggestions))
            ->setHeaders($this->headers);
    }
}
