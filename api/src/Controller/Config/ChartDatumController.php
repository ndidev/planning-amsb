<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Service\ChartDatumService;

class ChartDatumController extends Controller
{
    private ChartDatumService $chartDatumService;
    private Module $module = Module::CONFIG;
    private string $sseEventName = "config/cotes";

    public function __construct(
        private ?string $cote = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, PUT");
        $this->chartDatumService = new ChartDatumService();
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
                if ($this->cote) {
                    $this->read($this->cote);
                } else {
                    $this->readAll();
                }
                break;

            case 'PUT':
                $this->update($this->cote);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère toutes les côtes.
     */
    public function readAll()
    {
        $heightData = $this->chartDatumService->getAllData();

        $etag = ETag::get($heightData);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($heightData);
    }

    /**
     * Récupère une côte.
     */
    public function read(string $name)
    {
        $heightDatum = $this->chartDatumService->getDatum($name);

        if (!$heightDatum) {
            $this->response->setCode(404);
            return;
        }

        $etag = ETag::get($heightDatum);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($heightDatum);
    }

    /**
     * Met à jour une côte.
     * 
     * @param string $name Côte à modifier.
     */
    public function update(string $name)
    {
        if (
            !$this->user->canAccess($this->module)
            && !$this->user->canEdit(Module::SHIPPING)
        ) {
            throw new AccessException();
        }

        if (!$this->chartDatumService->datumExists($name)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $updatedHeightDatum = $this->chartDatumService->updateDatumValue($name, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($updatedHeightDatum);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $name, $updatedHeightDatum);
    }
}
