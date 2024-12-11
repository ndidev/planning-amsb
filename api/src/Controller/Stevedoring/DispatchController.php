<?php

// Path: api/src/Controller/Stevedoring/DispatchController.php

declare(strict_types=1);

namespace App\Controller\Stevedoring;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\DTO\Filter\StevedoringDispatchFilterDTO;
use App\Service\StevedoringService;

final class DispatchController extends Controller
{
    private StevedoringService $stevedoringService;
    private string $module = Module::STEVEDORING;

    public function __construct()
    {
        parent::__construct("OPTIONS, HEAD, GET");

        $this->stevedoringService = new StevedoringService();

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

    public function readAll(): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux heures de manutention.");
        }

        $filter = new StevedoringDispatchFilterDTO($this->request->getQuery());

        $dispatchDto = $this->stevedoringService->getDispatch($filter);

        $etag = ETag::get($dispatchDto);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($dispatchDto);
    }
}
