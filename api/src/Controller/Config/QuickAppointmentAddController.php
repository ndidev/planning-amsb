<?php

namespace App\Controller\Config;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\QuickAppointmentAddService;

final class QuickAppointmentAddController extends Controller
{
    private QuickAppointmentAddService $quickAppointmentAddService;

    public function __construct()
    {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->quickAppointmentAddService = new QuickAppointmentAddService();
        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->readAllConfigs();
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
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
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($quickAdds);
    }
}
