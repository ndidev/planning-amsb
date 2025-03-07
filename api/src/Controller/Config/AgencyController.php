<?php

// Path: api/src/Controller/Config/AgencyController.php

declare(strict_types=1);

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Component\SseEventNames;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\AgencyService;

final class AgencyController extends Controller
{
    private AgencyService $agencyService;
    private string $module = Module::CONFIG;
    private string $sseEventName = SseEventNames::CONFIG_AGENCY;

    public function __construct(
        private ?string $service = null,

    ) {
        parent::__construct("OPTIONS, HEAD, GET, PUT");
        $this->agencyService = new AgencyService();
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
                if ($this->service) {
                    $this->read($this->service);
                } else {
                    $this->readAll();
                }
                break;

            case 'PUT':
                $this->update($this->service);
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère les données des services de l'agence.
     */
    public function readAll(): void
    {
        $departments = $this->agencyService->getAllDepartments();

        $etag = ETag::get($departments);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($departments);
    }

    /**
     * Renvoie les données d'un service de l'agence.
     * 
     * @param string $departmentName Service de l'agence à récupérer.
     */
    public function read(string $departmentName): void
    {
        $department = $this->agencyService->getDepartment($departmentName);

        if (!$department) {
            throw new NotFoundException("Ce service n'existe pas.");
        }

        $etag = ETag::get($department);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($department);
    }

    /**
     * Met à jour les données d'un service de l'agence.
     * 
     * @param ?string $departmentName Service de l'agence à modifier.
     */
    public function update(?string $departmentName = null): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier la configuration.");
        }

        if (!$departmentName) {
            throw new BadRequestException("L'identifiant du service est obligatoire.");
        }

        if (!$this->agencyService->departmentExists($departmentName)) {
            throw new NotFoundException("Ce service n'existe pas.");
        }

        $input = $this->request->getBody();

        $updatedDepartment = $this->agencyService->updateDepartment($departmentName, $input);

        $this->response->setJSON($updatedDepartment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $departmentName, $updatedDepartment);
    }
}
