<?php

namespace App\Controller\Vrac;

use App\Models\Vrac\RdvModel;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class RdvController extends Controller
{
  private $model;
  private $module = "vrac";
  private $sseEventName = "vrac/rdvs";

  public function __construct(
    private ?int $id = null,
  ) {
    parent::__construct("OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
    $this->model = new RdvModel();
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

      case 'PATCH':
        $this->patch($this->id);
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
   * Récupère tous les RDV vrac.
   * 
   * @param array $query Détails de la requête.
   */
  public function readAll(array $query)
  {
    if (!$this->user->canAccess($this->module)) {
      throw new AccessException();
    }

    $appointments = $this->model->readAll($query);

    $etag = ETag::get($appointments);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(json_encode($appointments))
      ->setHeaders($this->headers);
  }

  /**
   * Récupère un RDV vrac.
   * 
   * @param int  $id      id du RDV à récupérer.
   * @param bool $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
   */
  public function read(int $id, ?bool $dryRun = false)
  {
    if (!$this->user->canAccess($this->module)) {
      throw new AccessException();
    }

    $appointment = $this->model->read($id);

    if (!$appointment && !$dryRun) {
      $this->response->setCode(404);
      return;
    }

    if ($dryRun) {
      return $appointment;
    }

    $etag = ETag::get($appointment);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(json_encode($appointment))
      ->setHeaders($this->headers);
  }

  /**
   * Crée un RDV vrac.
   */
  public function create()
  {
    if (!$this->user->canEdit($this->module)) {
      throw new AccessException();
    }

    $input = $this->request->body;

    $newAppointment = $this->model->create($input);

    $id = $newAppointment["id"];

    $this->headers["Location"] = $_ENV["API_URL"] . "/vrac/rdv/$id";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($newAppointment))
      ->setHeaders($this->headers);

    $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newAppointment);
  }

  /**
   * Met à jour un RDV.
   * 
   * @param int $id id du RDV à modifier.
   */
  public function update(int $id)
  {
    if (!$this->user->canEdit($this->module)) {
      throw new AccessException();
    }

    if (!$this->model->exists($id)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $updatedAppointment = $this->model->update($id, $input);

    $this->response
      ->setBody(json_encode($updatedAppointment))
      ->setHeaders($this->headers);

    $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedAppointment);
  }

  /**
   * Met à jour certaines proriétés d'un RDV vrac.
   * 
   * @param int $id id du RDV à modifier.
   */
  public function patch(int $id)
  {
    if (!$this->user->canEdit($this->module)) {
      throw new AccessException();
    }

    if (!$this->model->exists($id)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $patchedAppointment = $this->model->patch($id, $input);

    $this->response
      ->setBody(json_encode($patchedAppointment))
      ->setHeaders($this->headers);

    $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $patchedAppointment);
  }

  /**
   * Supprime un RDV.
   * 
   * @param int $id id du RDV à supprimer.
   */
  public function delete(int $id)
  {
    if (!$this->user->canEdit($this->module)) {
      throw new AccessException();
    }

    if (!$this->model->exists($id)) {
      $this->response->setCode(404);
      return;
    }

    $success = $this->model->delete($id);

    if ($success) {
      $this->response->setCode(204);
      $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    } else {
      throw new DBException("Erreur lors de la suppression");
    }
  }
}
