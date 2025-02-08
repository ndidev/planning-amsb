<?php

// Path: api/src/Controller/Bulk/BulkDispatchController.php

declare(strict_types=1);

namespace App\Controller\Bulk;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\DTO\Filter\BulkDispatchStatsFilterDTO;
use App\Service\BulkService;

final class BulkDispatchController extends Controller
{
    private BulkService $bulkService;
    private string $module = Module::BULK;

    public function __construct()
    {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->bulkService = new BulkService();
        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch ($this->request->getMethod()) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->readAll();
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les RDV vrac.
     */
    public function readAll(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux RDVs vrac.");
        }

        $filter = new BulkDispatchStatsFilterDTO($this->request->getQuery());

        $statsDTO = $this->bulkService->getBulkDispatchStats($filter);

        $etag = ETag::get($statsDTO);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($statsDTO);
    }
}
