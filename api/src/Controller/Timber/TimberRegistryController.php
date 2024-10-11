<?php

namespace App\Controller\Timber;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\TimberService;

class TimberRegistryController extends Controller
{
    private TimberService $timberService;
    private Module $module = Module::TIMBER;

    public function __construct()
    {
        parent::__construct();
        $this->timberService = new TimberService();
        $this->processRequest();
    }

    public function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->get($this->request->query);
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Renvoie l'extrait du registre d'affrètement avec le filtre appliqué.
     * 
     * @param array $filter
     */
    public function get(array $filter)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $csv = $this->timberService->getChateringRegister($filter);

        $etag = ETag::get($csv);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $date = date('YmdHis');
        $filename = "registre_bois_{$date}.csv";

        $this->response
            ->addHeader("ETag", $etag)
            ->setType('csv')
            ->addHeader("Content-Disposition", "attachment; filename={$filename}")
            ->addHeader("Cache-Control", "no-store, no-cache")
            ->setBody($csv);
    }
}
