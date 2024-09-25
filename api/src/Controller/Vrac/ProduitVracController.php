<?php

namespace App\Controller\Vrac;

use App\Models\Vrac\ProduitModel;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Server\DB\DBException;

class ProduitVracController extends Controller
{
  private $model;
  private $module = "vrac";
  private $sseEventName = "vrac/produits";

  public function __construct(
    private ?int $id = null,
  ) {
    parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
    $this->model = new ProduitModel();
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
          $this->readAll();
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
   * Récupère tous les produits vrac.
   */
  public function readAll()
  {
    if (!$this->user->canAccess($this->module)) {
      throw new AccessException();
    }

    $bulkProducts = $this->model->readAll();

    $etag = ETag::get($bulkProducts);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(json_encode($bulkProducts))
      ->setHeaders($this->headers);
  }

  /**
   * Récupère un produit vrac.
   * 
   * @param int  $id      id du produit à récupérer.
   * @param bool $dryRun Récupérer la ressource sans renvoyer la réponse HTTP.
   */
  public function read(int $id, ?bool $dryRun = false)
  {
    if (!$this->user->canAccess($this->module)) {
      throw new AccessException();
    }

    $bulkProduct = $this->model->read($id);

    if (!$bulkProduct && !$dryRun) {
      $this->response->setCode(404);
      return;
    }

    if ($dryRun) {
      return $bulkProduct;
    }

    $etag = ETag::get($bulkProduct);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(json_encode($bulkProduct))
      ->setHeaders($this->headers);
  }

  /**
   * Crée un produit vrac.
   */
  public function create()
  {
    if (!$this->user->canEdit($this->module)) {
      throw new AccessException();
    }

    $input = $this->request->body;

    $newBulkProduct = $this->model->create($input);

    $id = $newBulkProduct["id"];

    $this->headers["Location"] = $_ENV["API_URL"] . "/vrac/produits/$id";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($newBulkProduct))
      ->setHeaders($this->headers)
      ->flush();

    $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $newBulkProduct);
  }

  /**
   * Met à jour un produit.
   * 
   * @param int $id id du produit à modifier.
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

    $updatedBulkProduct = $this->model->update($id, $input);

    $this->response
      ->setBody(json_encode($updatedBulkProduct))
      ->setHeaders($this->headers)
      ->flush();

    $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $updatedBulkProduct);
  }

  /**
   * Supprime un produit.
   * 
   * @param int $id id du produit à supprimer.
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
      $this->response->setCode(204)->flush();
      $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    } else {
      throw new DBException("Erreur lors de la suppression");
    }
  }
}
