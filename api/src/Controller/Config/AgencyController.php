<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Service\AgencyService;

class AgencyController extends Controller
{
    private AgencyService $agencyService;
    private Module $module = Module::CONFIG;
    private string $sseEventName = "config/agence";

    public function __construct(
        private ?string $service = null,

    ) {
        parent::__construct("OPTIONS, HEAD, GET, PUT");
        $this->agencyService = new AgencyService();
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
                if ($this->service) {
                    $this->read($this->service);
                } else {
                    $this->readAll($this->request->query);
                }
                break;

            case 'PUT':
                $this->update($this->service);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère les données des services de l'agence.
     */
    public function readAll()
    {
        $departments = $this->agencyService->getAllDepartments();

        $etag = ETag::get($departments);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($departments);
    }

    /**
     * Renvoie les données d'un service de l'agence.
     * 
     * @param string $departmentName Service de l'agence à récupérer.
     */
    public function read(string $departmentName)
    {
        $department = $this->agencyService->getDepartment($departmentName);

        if (!$department) {
            $this->response->setCode(404);
            return;
        }

        $etag = ETag::get($department);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($department);
    }

    /**
     * Met à jour les données d'un service de l'agence.
     * 
     * @param string $departmentName Service de l'agence à modifier.
     */
    public function update(string $departmentName)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        if (!$this->agencyService->departmentExists($departmentName)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $updatedDepartment = $this->agencyService->updateDepartment($departmentName, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($updatedDepartment);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $departmentName, $updatedDepartment);
    }
}
