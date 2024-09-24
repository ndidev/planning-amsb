<?php

namespace App\Controllers\Utils;

use App\Models\Utils\PaysModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Server\DB\DBException;

class PaysController extends Controller
{
    private $model;
    private $sseEventName = "pays";

    public function __construct(
        private ?string $iso = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new PaysModel();
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
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les pays.
     */
    public function readAll()
    {
        $countries = $this->model->readAll();

        $etag = ETag::get($countries);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;
        $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

        $this->response
            ->setBody(json_encode($countries))
            ->setHeaders($this->headers);
    }

    /**
     * Récupère un pays.
     * 
     * @param string $iso     Code ISO du pays à récupérer.
     * @param bool   $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(string $iso, ?bool $dryRun = false)
    {
        $country = $this->model->read($iso);

        if (!$country && !$dryRun) {
            $message = "Not Found";
            $docURL = $_ENV["API_URL"] . "/doc/#/Consignation/lireEscaleConsignation";
            $body = json_encode(["message" => $message, "documentation_url" => $docURL]);
            $this->response->setCode(404)->setBody($body);
            return;
        }

        if ($dryRun) {
            return $country;
        }

        $etag = ETag::get($country);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;
        $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

        $this->response
            ->setBody(json_encode($country))
            ->setHeaders($this->headers);
    }

    /**
     * Crée un pays.
     */
    public function create()
    {
        $input = $this->request->body;

        $newCountry = $this->model->create($input);

        $iso = $newCountry["iso"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/consignation/escales/$iso";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($newCountry))
            ->setHeaders($this->headers);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $iso, $newCountry);
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

        $updatedCountry = $this->model->update($iso, $input);

        $this->response
            ->setBody(json_encode($updatedCountry))
            ->setHeaders($this->headers);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $iso, $updatedCountry);
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

        $success = $this->model->delete($iso);

        if ($success) {
            $this->response->setCode(204);
            $this->sse->addEvent($this->sseEventName, __FUNCTION__, $iso);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
