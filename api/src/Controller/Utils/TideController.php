<?php

namespace App\Controller\Utils;

use App\Controller\Controller;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\HTTP\ETag;
use App\Service\TideService;

class TideController extends Controller
{
    private $service;
    private $sse_event = "marees";

    public function __construct(
        private ?int $annee = 0,
        private bool $annees = false
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, DELETE");
        $this->service = new TideService();
        $this->processRequest();
    }

    public function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supported_methods);
                break;

            case 'HEAD':
            case 'GET':
                if ($this->annees) {
                    $this->getYears();
                } else if ($this->annee) {
                    $this->getTidesByYear($this->annee);
                } else {
                    $this->getTides($this->request->query);
                }
                break;

            case 'POST':
                $this->create();
                break;

            case 'DELETE':
                $this->delete($this->annee);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Send the HTTP response
        $this->response->send();
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
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setBody(json_encode($tides));
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
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setBody(json_encode($tides));
    }

    /**
     * Retrieves the years.
     */
    public function getYears(): void
    {
        $tides = $this->service->getYears();

        $etag = ETag::get($tides);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setBody(json_encode($tides));
    }

    /**
     * Adds tides for a year.
     */
    public function create(): void
    {
        if (empty($_FILES) || !isset($_FILES["csv"])) {
            $this->response
                ->setCode(400)
                ->setHeaders($this->headers);
            return;
        }

        $year = $this->service->addTides($_FILES["csv"]);

        $this->headers["Location"] = $_ENV["API_URL"] . "/marees/$year";

        $data = ["annee" => (int) $year];

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setBody(json_encode($data));

        notify_sse($this->sse_event, __FUNCTION__, $year, $data);
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
            $this->response->setCode(204);
            notify_sse($this->sse_event, __FUNCTION__, $year);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
