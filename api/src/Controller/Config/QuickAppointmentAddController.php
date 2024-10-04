<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\HTTP\ETag;
use App\Service\QuickAppointmentAddService;

class QuickAppointmentAddController extends Controller
{
    private QuickAppointmentAddService $quickAppointmentAddService;

    public function __construct()
    {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->quickAppointmentAddService = new QuickAppointmentAddService();
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
                $this->readAllConfigs();
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les ajouts rapides.
     */
    public function readAllConfigs()
    {
        $quickAdds = $this->quickAppointmentAddService->getAllQuickAppointmentAdds();

        // Filtre sur les catégories autorisées pour l'utilisateur
        foreach ($quickAdds as $moduleName => $collection) {
            if (!$this->user->canAccess(Module::from($moduleName))) {
                unset($quickAdds[$moduleName]);
            }
        }

        $etag = ETag::get($quickAdds);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($quickAdds);
    }
}
