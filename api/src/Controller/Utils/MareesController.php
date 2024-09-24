<?php

namespace App\Controller\Utils;

use App\Models\Utils\MareesModel;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Server\DB\DBException;

class MareesController extends Controller
{
    private $model;
    private $module = "marees";
    private $sseEventName = "marees";

    public function __construct(
        private ?int $year = 0,
        private bool $years = false,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, DELETE");
        $this->model = new MareesModel();

        if (str_contains($this->request->path, "/annees")) {
            $this->years = true;
        }

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
                if ($this->years) {
                    $this->readYears();
                } else if ($this->year) {
                    $this->readYear($this->year);
                } else {
                    $this->read($this->request->query);
                }
                break;

            case 'POST':
                $this->create();
                break;

            case 'DELETE':
                $this->delete($this->year);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère les marées.
     * 
     * @param array $filtre
     */
    public function read(?array $filtre = null)
    {
        $tides = $this->model->read($filtre);

        $etag = ETag::get($tides);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($tides))
            ->setHeaders($this->headers);
    }

    /**
     * Récupère les marées d'une année.
     * 
     * @param int $annee
     */
    public function readYear(int $annee)
    {
        $tidesOfYear = $this->model->readYear($annee);

        $etag = ETag::get($tidesOfYear);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($tidesOfYear))
            ->setHeaders($this->headers);
    }

    /**
     * Récupère les années.
     */
    public function readYears()
    {
        $years = $this->model->readYears();

        $etag = ETag::get($years);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($years))
            ->setHeaders($this->headers);
    }

    /**
     * Ajoute des marées pour une année.
     */
    public function create()
    {
        if (empty($_FILES)) {
            $this->response
                ->setCode(400)
                ->setHeaders($this->headers);
            return;
        }

        $csv = $_FILES["csv"];
        $content = file_get_contents($csv["tmp_name"]);
        // Supprimer le BOM
        $content = str_replace("\u{FEFF}", "", $content);
        // Supprimer le carriage return produit par Windows
        $content = str_replace("\r", "", $content);
        $lines = explode(PHP_EOL, $content);

        $separator = ";";

        $tides = [];
        foreach ($lines as $line) {
            // Ne pas prendre en compte les lignes non conformes
            if (strpos($line, $separator) === false) continue;
            if (strlen($line) <= 2) continue;

            // Enregistrer chaque ligne dans le tableau $tides
            [$date, $time, $height] = str_getcsv($line, $separator);
            array_push($tides, [
                $date,
                $time,
                (float) $height
            ]);
        }

        $year = substr($tides[0][0], 0, 4);

        $this->model->create($tides);

        $this->headers["Location"] = $_ENV["API_URL"] . "/marees/$year";

        $newYear = ["annee" => (int) $year];

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setBody(json_encode($newYear));

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $year, $newYear);
    }

    /**
     * Supprime les marrées pour une année.
     * 
     * @param int $year Année pour laquelle supprimer les marées.
     */
    public function delete(int $year)
    {
        $success = $this->model->delete($year);

        if ($success) {
            $this->response->setCode(204);
            $this->sse->addEvent($this->sseEventName, __FUNCTION__, $year);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
