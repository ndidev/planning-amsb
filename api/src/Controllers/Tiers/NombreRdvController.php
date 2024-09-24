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
        private ?int $id = null,
    ) {
        parent::__construct();
        $this->model = new NombreRdvModel();
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
                if ($this->id) {
                    $this->read($this->id, $this->request->query);
                } else {
                    $this->readAll($this->request->query);
                }
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère le nombre de RDV pour tous les tiers.
     * 
     * @param array $options Options de récupérations.
     */
    public function readAll(array $options)
    {
        $appointmentsQuantity = $this->model->readAll($options);

        $etag = ETag::get($appointmentsQuantity);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($appointmentsQuantity))
            ->setHeaders($this->headers);
    }

    /**
     * Récupère le nombre de RDV pour un tiers.
     * 
     * @param int   $id      id du tiers à récupérer.
     * @param array $options Options de récupération.
     * @param bool  $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?array $options = [], ?bool $dryRun = false)
    {
        $appointmentsQuantity = $this->model->read($id, $options);

        if (!$appointmentsQuantity && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if ($dryRun) {
            return $appointmentsQuantity;
        }

        $etag = ETag::get($appointmentsQuantity);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($appointmentsQuantity))
            ->setHeaders($this->headers);
    }
}
