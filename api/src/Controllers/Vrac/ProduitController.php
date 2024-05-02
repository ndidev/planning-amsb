<?php

namespace App\Controllers\Vrac;

use App\Controllers\Controller;
use App\Service\VracService;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Entity\Vrac\ProduitVrac;

class ProduitController extends Controller
{
  private VracService $produitService;
  private $module = "vrac";
  private $sse_event = "vrac/produits";

  public function __construct(
    private ?int $id
  ) {
    parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
    $this->produitService = new VracService();
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
        $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
        break;
    }

    // Envoi de la réponse HTTP
    $this->response->send();
  }

  /**
   * Récupère tous les produits vrac.
   */
  public function readAll()
  {
    if (!$this->user->canAccess($this->module)) {
      throw new AccessException();
    }

    $produits = $this->produitService->getProduits();

    $etag = ETag::get($produits);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(
        json_encode(
          array_map(fn (ProduitVrac $produit) => $produit->toArray(), $produits)
        )
      )
      ->setHeaders($this->headers);
  }

  /**
   * Récupère un produit vrac.
   * 
   * @param int  $id      id du produit à récupérer.
   * @param bool $dry_run Récupérer la ressource sans renvoyer la réponse HTTP.
   */
  public function read(int $id, ?bool $dry_run = false)
  {
    if (!$this->user->canAccess($this->module)) {
      throw new AccessException();
    }

    $produit = $this->produitService->getProduit($id);

    if (!$produit && !$dry_run) {
      $this->response->setCode(404);
      return;
    }

    if ($dry_run) {
      return $produit;
    }

    $etag = ETag::get($produit);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(json_encode($produit?->toArray()))
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

    $produit = $this->produitService->createProduit($input);

    $id = $produit->getId();

    $this->headers["Location"] = $_ENV["API_URL"] . "/vrac/produits/$id";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($produit->toArray()))
      ->setHeaders($this->headers);

    notify_sse($this->sse_event, __FUNCTION__, $id, $produit->toArray());
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

    if (!$this->produitService->produitExists($id)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $produit = $this->produitService->updateProduit($id, $input);

    $this->response
      ->setBody(json_encode($produit->toArray()))
      ->setHeaders($this->headers);

    notify_sse($this->sse_event, __FUNCTION__, $id, $produit->toArray());
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

    if (!$this->produitService->produitExists($id)) {
      $this->response->setCode(404);
      return;
    }

    $this->produitService->deleteProduit($id);

    $this->response->setCode(204)->flush();
    notify_sse($this->sse_event, __FUNCTION__, $id);
  }
}
