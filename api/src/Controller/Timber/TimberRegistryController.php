<?php

namespace App\Controller\Timber;

use App\Service\TimberService;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;

class TimberRegistryController extends Controller
{
    private TimberService $timberService;
    private string $module = "bois";

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
                $this->response->setCode(204)->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->get($this->request->query);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
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
            $this->response->setCode(304);
            return;
        }

        $date = date('YmdHis');
        $filename = "registre_bois_{$date}.csv";

        $this->headers["ETag"] = $etag;
        $this->headers["Content-Type"] = "text/csv";
        $this->headers["Content-Disposition"] = "attachment; filename={$filename}";
        $this->headers["Cache-Control"] = "no-store, no-cache";

        $this->response
            ->setHeaders($this->headers)
            ->setBody($csv);
    }
}
