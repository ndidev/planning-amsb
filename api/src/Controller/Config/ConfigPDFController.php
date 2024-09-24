<?php

namespace App\Controller\Config;

use App\Models\Config\ConfigPDFModel;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class ConfigPDFController extends Controller
{
    private $model;
    private $module = "config";
    private $sseEventName = "config/pdf";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new ConfigPDFModel();
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
                    $this->read($this->id);
                } else {
                    $this->readAll($this->request->query);
                }
                break;

            case 'POST':
                $this->create();
                break;

            case 'PUT':
                $this->update($this->id);
                break;

            case 'DELETE':
                $this->delete($this->id);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère toutes les configurations PDF.
     */
    public function readAll()
    {
        $pdfConfigs = $this->model->readAll();

        // Filtre sur les catégories autorisées pour l'utilisateur
        foreach ($pdfConfigs as $key => $ligne) {
            if ($this->user->canAccess($ligne["module"]) === false) {
                unset($pdfConfigs[$key]);
            }
        }

        $etag = ETag::get($pdfConfigs);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($pdfConfigs))
            ->setHeaders($this->headers);
    }

    /**
     * Récupère une configuration PDF.
     * 
     * @param int  $id      id de la configuration à récupérer.
     * @param bool $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dryRun = false)
    {
        $pdfConfig = $this->model->read($id);

        if (!$pdfConfig && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if (
            $pdfConfig && !$this->user->canAccess($pdfConfig["module"])
        ) {
            throw new AccessException();
        }

        if ($dryRun) {
            return $pdfConfig;
        }

        $etag = ETag::get($pdfConfig);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($pdfConfig))
            ->setHeaders($this->headers);
    }

    /**
     * Crée une configuration PDF.
     */
    public function create()
    {
        $input = $this->request->body;

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($input["module"])
        ) {
            throw new AccessException();
        }

        $newPdfConfig = $this->model->create($input);

        $id = $newPdfConfig["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/pdf/configs/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($newPdfConfig))
            ->setHeaders($this->headers);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newPdfConfig);
    }

    /**
     * Met à jour une configuration PDF.
     * 
     * @param int $id id de la configuration à modifier.
     */
    public function update(int $id)
    {
        $current = $this->read($id, true);

        if (!$current) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($current["module"])
            || !$this->user->canEdit($input["module"])
        ) {
            throw new AccessException();
        }

        $updatedPdfConfig = $this->model->update($id, $input);

        $this->response
            ->setBody(json_encode($updatedPdfConfig))
            ->setHeaders($this->headers);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedPdfConfig);
    }

    /**
     * Supprime une configuration PDF.
     * 
     * @param int $id id de la configuration PDF à supprimer.
     */
    public function delete(int $id)
    {
        $current = $this->read($id, true);

        if (!$current) {
            $this->response->setCode(404);
            return;
        }

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($current["module"])
        ) {
            throw new AccessException();
        }

        $success = $this->model->delete($id);

        if ($success) {
            $this->response->setCode(204);
            $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
