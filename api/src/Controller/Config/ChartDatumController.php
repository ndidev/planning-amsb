<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
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
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
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
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
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
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($heightData);
    }

    /**
     * Récupère une côte.
     */
    public function read(string $name)
    {
        $heightDatum = $this->chartDatumService->getDatum($name);

        if (!$heightDatum) {
            throw new NotFoundException("Cette côte n'existe pas.");
        }

        $etag = ETag::get($heightDatum);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($heightDatum);
    }

    /**
     * Met à jour une côte.
     * 
     * @param string $name Côte à modifier.
     */
    public function update(string $name)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        if (!$this->user->canEdit(Module::SHIPPING)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier les côtes.");
        }

        if (!$this->chartDatumService->datumExists($name)) {
            throw new NotFoundException("Cette côte n'existe pas.");
        }

        $input = $this->request->getBody();

        $updatedHeightDatum = $this->chartDatumService->updateDatumValue($name, $input);

        $this->response->setJSON($updatedHeightDatum);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $name, $updatedHeightDatum);
    }
}
