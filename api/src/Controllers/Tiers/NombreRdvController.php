<?php

namespace App\Controllers\Tiers;

use App\Service\TiersService;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;

class NombreRdvController extends Controller
{
    private TiersService $service;
    private $module = "tiers";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct();
        $this->service = new TiersService();
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
                $this->read($this->id);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Envoi de la réponse HTTP
        $this->response->send();
    }

    /**
     * Récupère le nombre de RDV pour un tiers.
     * 
     * @param int   $id      id du tiers à récupérer.
     * @param bool  $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(?int $id, ?bool $dry_run = false)
    {
        if ($id && !$this->service->tiersExiste($id) && !$dry_run) {
            $this->response->setCode(404);
            return;
        }

        $rdvCount = $this->service->getNombreRdvTiers($id);

        if ($dry_run) {
            return $rdvCount;
        }

        $etag = ETag::get($rdvCount);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($rdvCount))
            ->setHeaders($this->headers);
    }
}
