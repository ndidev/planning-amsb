<?php

// Path: api/src/Controller/Timber/TimberRegistryController.php

declare(strict_types=1);

namespace App\Controller\Timber;

use App\Controller\Controller;
use App\Core\Component\DateUtils;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\TimberService;

final class TimberRegistryController extends Controller
{
    private TimberService $timberService;
    /** @phpstan-var Module::* $module */
    private string $module = Module::TIMBER;

    public function __construct()
    {
        parent::__construct();
        $this->timberService = new TimberService();
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
                $this->get();
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
     */
    public function get(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $defaultStartDate = DateUtils::format(
            DateUtils::SQL_DATE,
            DateUtils::getPreviousWorkingDay(new \DateTimeImmutable())
        );
        $defaultEndDate = date("Y-m-d");

        // Filtre
        /** @var \DateTime $startDate */
        $startDate = $this->request->getQuery()->getParam('date_debut', $defaultStartDate, 'datetime');
        /** @var \DateTime $endDate */
        $endDate = $this->request->getQuery()->getParam('date_fin', $defaultEndDate, 'datetime');

        $csv = $this->timberService->getChateringRegister($startDate, $endDate);

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
