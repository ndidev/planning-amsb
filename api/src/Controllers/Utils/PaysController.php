<?php

namespace App\Controllers\Utils;

use App\Models\Utils\PaysModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Country;

class PaysController extends Controller
{
    private $model;
    private $sse_event = "pays";

    public function __construct(
        private ?string $iso
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new PaysModel;
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
                if ($this->iso) {
                    $this->read($this->iso);
                } else {
                    $this->readAll();
                }
                break;

            case 'POST':
                $this->create();
                break;

            case 'PUT':
                $this->update($this->iso);
                break;

            case 'DELETE':
                $this->delete($this->iso);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Envoi de la réponse HTTP
        $this->response->send();
    }

    /**
     * Récupère tous les pays.
     */
    public function readAll()
    {
        $listePays = $this->model->readAll();

        $etag = ETag::get($listePays);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;
        $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

        $this->response
            ->setBody(
                json_encode(
                    array_map(fn (Country $pays) => $pays->toArray(), $listePays)
                )
            )
            ->setHeaders($this->headers);
    }

    /**
     * Récupère un pays.
     * 
     * @param string $iso     Code ISO du pays à récupérer.
     * @param bool   $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(string $iso, ?bool $dry_run = false)
    {
        $pays = $this->model->read($iso);

        if (!$pays && !$dry_run) {
            $message = "Not Found";
            $documentation = $_ENV["API_URL"] . "/doc/#/Consignation/lireEscaleConsignation";
            $body = json_encode(["message" => $message, "documentation_url" => $documentation]);
            $this->response->setCode(404)->setBody($body);
            return;
        }

        if ($dry_run) {
            return $pays;
        }

        $etag = ETag::get($pays);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;
        $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

        $this->response
            ->setBody(json_encode($pays->toArray()))
            ->setHeaders($this->headers);
    }

    /**
     * Crée un pays.
     */
    public function create()
    {
        $input = $this->request->body;

        $pays = $this->model->create($input);

        $iso = $pays->getISO();

        $this->headers["Location"] = $_ENV["API_URL"] . "/consignation/escales/$iso";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($pays->toArray()))
            ->setHeaders($this->headers);

        notify_sse($this->sse_event, __FUNCTION__, $iso, $pays->toArray());
    }

    /**
     * Met à jour un pays.
     * 
     * @param string $iso Code ISO du pays à modifier.
     */
    public function update(string $iso)
    {
        if (!$this->read($iso, true)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $pays = $this->model->update($iso, $input);

        $this->response
            ->setBody(json_encode($pays->toArray()))
            ->setHeaders($this->headers);

        notify_sse($this->sse_event, __FUNCTION__, $iso, $pays->toArray());
    }

    /**
     * Supprime un pays.
     * 
     * @param string $iso Code ISO du pays à supprimer.
     */
    public function delete(string $iso)
    {
        if (!$this->read($iso, true)) {
            $this->response->setCode(404);
            return;
        }

        $succes = $this->model->delete($iso);

        if ($succes) {
            $this->response->setCode(204);
            notify_sse($this->sse_event, __FUNCTION__, $iso);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
