<?php

// Path: api/src/Controller/Stevedoring/TempWorkDispatchNamesController.php

declare(strict_types=1);

namespace App\Controller\Stevedoring;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\StevedoringService;

final class TempWorkDispatchNamesController extends Controller
{
    private StevedoringService $stevedoringService;
    private string $module = Module::STEVEDORING;

    public function __construct(private string $date)
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
                $this->read($this->date);
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    public function read(string $date): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux heures de manutention.");
        }

        try {
            $dateTime = new \DateTimeImmutable($date);
        } catch (\Exception) {
            throw new BadRequestException("La date n'est pas valide.");
        }

        $staffIds = $this->stevedoringService->getTempWorkDispatchNamesForDate($dateTime);

        $etag = ETag::get($staffIds);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($staffIds);
    }
}
