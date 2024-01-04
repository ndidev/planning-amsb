<?php

namespace App\Controllers\Tiers;

use App\Models\Tiers\NombreRdvModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;

class NombreRdvController extends Controller
{
    private $model;
    private $module = "tiers";

    public function __construct(
        private ?int $id,
    ) {
        parent::__construct();
        $this->model = new NombreRdvModel;
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
                if ($this->id) {
                    $this->read($this->id, $this->request->query);
                } else {
                    $this->readAll($this->request->query);
                }
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Envoi de la réponse HTTP
        $this->response->send();
    }

    /**
     * Récupère le nombre de RDV pour tous les tiers.
     * 
     * @param array $options Options de récupérations.
     */
    public function readAll(array $options)
    {
        $donnees = $this->model->readAll($options);

        $etag = ETag::get($donnees);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers);
    }

    /**
     * Récupère le nombre de RDV pour un tiers.
     * 
     * @param int   $id      id du tiers à récupérer.
     * @param array $options Options de récupération.
     * @param bool  $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?array $options = [], ?bool $dry_run = false)
    {
        $donnees = $this->model->read($id, $options);

        if (!$donnees && !$dry_run) {
            $this->response->setCode(404);
            return;
        }

        if ($dry_run) {
            return $donnees;
        }

        $etag = ETag::get($donnees);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($donnees))
            ->setHeaders($this->headers);
    }
}
