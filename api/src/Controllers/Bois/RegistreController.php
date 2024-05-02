<?php

namespace App\Controllers\Bois;

use App\Service\BoisService;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;

class RegistreController extends Controller
{
    private $service;
    private $module = "bois";

    public function __construct()
    {
        parent::__construct();
        $this->service = new BoisService();
        $this->processRequest();
    }

    public function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supported_methods);
                break;

            case 'HEAD':
            case 'GET':
                $this->get($this->request->query);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Envoi de la réponse HTTP
        $this->response->send();
    }

    /**
     * Renvoie l'extrait du registre d'affrètement avec le filtre appliqué.
     * 
     * @param array $filtre
     */
    public function get(array $filtre)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $csv = $this->service->getRegistreAffretement($filtre);

        $etag = ETag::get($csv);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $date = date('YmdHis');
        $fichier = "registre_bois_$date.csv";

        $this->headers["ETag"] = $etag;
        $this->headers["Content-Type"] = "text/csv";
        $this->headers["Content-Disposition"] = "attachment; filename=$fichier";
        $this->headers["Cache-Control"] = "no-store, no-cache";

        $this->response
            ->setHeaders($this->headers)
            ->setBody($csv);
    }
}
