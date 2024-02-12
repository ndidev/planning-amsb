<?php

namespace App\Controllers\Vrac;

use App\Service\BulkAppointmentService;
use App\Controllers\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Entity\BulkAppointment;

class RdvController extends Controller
{
  private BulkAppointmentService $bulkAppointmentService;
  private $module = "vrac";
  private $sse_event = "vrac/rdvs";

  public function __construct(
    private ?int $id
  ) {
    parent::__construct("OPTIONS, HEAD, GET, POST, PUT, PATCH, DELETE");
    $this->bulkAppointmentService = new BulkAppointmentService();
    $this->processRequest();
  }

  public function processRequest()
  {
    switch ($this->request->method) {
      case 'OPTIONS':
        $this->response->setCode(204)->addHeader("Allow", $this->supported_methods);
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
        $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère tous les RDV vrac.
   * 
   * @param array $query Détails de la requête.
   */
  public function readAll(array $query)
  {
    if (!$this->user->can_access($this->module)) {
      throw new AccessException();
    }

    $appointments = $this->bulkAppointmentService->getAppointments($query);

    $etag = ETag::get($appointments);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(
        json_encode(
          array_map(fn (BulkAppointment $appointment) => $appointment->toArray(), $appointments)
        )
      )
      ->setHeaders($this->headers);
  }

  /**
   * Récupère un RDV vrac.
   * 
   * @param int  $id      id du RDV à récupérer.
   * @param bool $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
   */
  public function read(int $id, ?bool $dry_run = false)
  {
    if (!$this->user->can_access($this->module)) {
      throw new AccessException();
    }

    $appointment = $this->bulkAppointmentService->getAppointment($id);

    if (!$appointment && !$dry_run) {
      $this->response->setCode(404);
      return;
    }

    if ($dry_run) {
      return $appointment;
    }

    $etag = ETag::get($appointment);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(json_encode($appointment->toArray()))
      ->setHeaders($this->headers);
  }

  /**
   * Crée un RDV vrac.
   */
  public function create()
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    $input = $this->request->body;

    $appointment = $this->bulkAppointmentService->createAppointment($input);

    $id = $appointment["id"];

    $this->headers["Location"] = $_ENV["API_URL"] . "/vrac/rdv/$id";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($appointment->toArray()))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->sse_event, __FUNCTION__, $id, $appointment->toArray());
  }

  /**
   * Met à jour un RDV.
   * 
   * @param int $id id du RDV à modifier.
   */
  public function update(int $id)
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    if (!$this->bulkAppointmentService->appointmentExists($id)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $appointment = $this->bulkAppointmentService->updateAppointment($id, $input);

    $this->response
      ->setBody(json_encode($appointment->toArray()))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->sse_event, __FUNCTION__, $id, $appointment->toArray());
  }

  /**
   * Met à jour certaines proriétés d'un RDV vrac.
   * 
   * @param int $id id du RDV à modifier.
   */
  public function patch(int $id)
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    if (!$this->bulkAppointmentService->appointmentExists($id)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $appointment = $this->bulkAppointmentService->patchAppointment($id, $input);

    $this->response
      ->setBody(json_encode($appointment->toArray()))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->sse_event, __FUNCTION__, $id, $appointment->toArray());
  }

  /**
   * Supprime un RDV.
   * 
   * @param int $id id du RDV à supprimer.
   */
  public function delete(int $id)
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    if (!$this->bulkAppointmentService->appointmentExists($id)) {
      $this->response->setCode(404);
      return;
    }

    $this->bulkAppointmentService->deleteAppointment($id);

    $this->response->setCode(204)->flush();
    notify_sse($this->sse_event, __FUNCTION__, $id);
  }
}
