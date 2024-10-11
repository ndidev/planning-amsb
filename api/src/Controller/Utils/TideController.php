<?php

// Path: api/src/Controller/Utils/TideController.php

namespace App\Controller\Utils;

use App\Controller\Controller;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\TideService;

class TideController extends Controller
{
    private TideService $service;
    private string $sseEventName = "marees";

    public function __construct(
        private ?int $year = 0,
        private bool $years = false,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, DELETE");
        $this->service = new TideService();

        if (str_contains($this->request->path, "/annees")) {
            $this->years = true;
        }

        $this->processRequest();
    }

    private function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                if ($this->years) {
                    $this->getYears();
                } else if ($this->year) {
                    $this->getTidesByYear($this->year);
                } else {
                    $this->getTides($this->request->query);
                }
                break;

            case 'POST':
                $this->create();
                break;

            case 'DELETE':
                $this->delete($this->year);
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Retrieves tides.
     * 
     * @param array $filter
     */
    public function getTides(?array $filter = []): void
    {
        $start = $filter["debut"] ?? null;
        $end = $filter["fin"] ?? null;

        $tides = $this->service->getTides($start, $end);

        $etag = ETag::get($tides);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($tides);
    }

    /**
     * Retrieves tides for a year.
     * 
     * @param int $year
     */
    public function getTidesByYear(int $year): void
    {
        $tides = $this->service->getTidesByYear($year);

        $etag = ETag::get($tides);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($tides);
    }

    /**
     * Retrieves the years.
     */
    public function getYears(): void
    {
        $years = $this->service->getYears();

        $etag = ETag::get($years);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($years);
    }

    /**
     * Adds tides for a year.
     */
    public function create(): void
    {
        if (empty($_FILES) || !isset($_FILES["csv"])) {
            throw new ClientException("Fichier CSV manquant");
        }

        $year = $this->service->addTides($_FILES["csv"]);

        $data = ["annee" => (int) $year];

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", $_ENV["API_URL"] . "/marees/$year")
            ->setJSON($data);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $year, $data);
    }

    /**
     * Deletes tides for a year.
     * 
     * @param int $year Year for which to delete the tides.
     */
    public function delete(int $year): void
    {
        $success = $this->service->deleteTides($year);

        if ($success) {
            $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
            $this->sse->addEvent($this->sseEventName, __FUNCTION__, $year);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
