<?php

namespace App\Controllers\Config;

use App\Models\Config\BandeauInfoModel;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class BandeauInfoController extends Controller
{
    private $model;
    private $module = "config";
    private $sseEventName = "config/bandeau-info";

    public function __construct(
        private ?int $id = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->model = new BandeauInfoModel();
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
                if ($this->id) {
                    $this->read($this->id);
                } else {
                    $this->readAll($this->request->query);
                }
                break;

            case 'POST':
                $this->create();
                break;

            case 'PUT':
                $this->update($this->id);
                break;

            case 'DELETE':
                $this->delete($this->id);
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère toutes les lignes du bandeau.
     * 
     * @param array $filtre
     */
    public function readAll(array $filtre)
    {
        $bannerEntries = $this->model->readAll($filtre);

        // Filtre sur les catégories autorisées pour l'utilisateur
        $bannerEntries =
            array_values(
                array_filter($bannerEntries, function ($ligne) {
                    return $this->user->canAccess($ligne["module"]);
                })
            );

        $etag = ETag::get($bannerEntries);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304)->send();
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($bannerEntries))
            ->setHeaders($this->headers)
            ->send();
    }

    /**
     * Récupère une ligne du bandeau.
     * 
     * @param int  $id      id de la ligne à récupérer.
     * @param bool $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
     */
    public function read(int $id, ?bool $dryRun = false)
    {
        $bannerEntry = $this->model->read($id);

        if (!$bannerEntry && !$dryRun) {
            $this->response->setCode(404)->send();
            return;
        }

        if (
            $bannerEntry && !$this->user->canAccess($bannerEntry["module"])
        ) {
            throw new AccessException();
        }

        if ($dryRun) {
            return $bannerEntry;
        }

        $etag = ETag::get($bannerEntry);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304)->send();
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setBody(json_encode($bannerEntry))
            ->setHeaders($this->headers)
            ->send();
    }

    /**
     * Crée une ligne de bandeau d'informations.
     */
    public function create()
    {
        $input = $this->request->body;

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($input["module"])
        ) {
            throw new AccessException();
        }

        $newBannerEntry = $this->model->create($input);

        $id = $newBannerEntry["id"];

        $this->headers["Location"] = $_ENV["API_URL"] . "/bandeau-info/$id";

        $this->response
            ->setCode(201)
            ->setBody(json_encode($newBannerEntry))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newBannerEntry);
    }

    /**
     * Met à jour une ligne du bandeau d'informations.
     * 
     * @param int $id id de la ligne à modifier.
     */
    public function update(int $id)
    {
        $current = $this->read($id, true);

        if (!$current) {
            $message = "Not Found";
            $docURL = $_ENV["API_URL"] . "/doc/#/Bois/modifierLigneBandeauInfo";
            $body = json_encode(["message" => $message, "documentation_url" => $docURL]);
            $this->response->setCode(404)->setBody($body);
            return;
        }

        $input = $this->request->body;

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($current["module"])
            || !$this->user->canEdit($input["module"])
        ) {
            throw new AccessException();
        }

        $updatedBannerEntry = $this->model->update($id, $input);

        $this->response
            ->setBody(json_encode($updatedBannerEntry))
            ->setHeaders($this->headers)
            ->flush();

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedBannerEntry);
    }

    /**
     * Supprime une ligne du bandeau d'informations.
     * 
     * @param int $id id de la ligne à supprimer.
     */
    public function delete(int $id)
    {
        $current = $this->read($id, true);

        if (!$current) {
            $message = "Not Found";
            $docURL = $_ENV["API_URL"] . "/doc/#/Bandeau/supprimerLigneBandeauInfo";
            $body = json_encode(["message" => $message, "documentation_url" => $docURL]);
            $this->response->setCode(404)->setBody($body);
            return;
        }

        if (
            !$this->user->canAccess($this->module)
            || !$this->user->canEdit($current["module"])
        ) {
            throw new AccessException();
        }

        $success = $this->model->delete($id);

        if ($success) {
            $this->response->setCode(204)->flush();
            $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
        } else {
            throw new DBException("Erreur lors de la suppression");
        }
    }
}
