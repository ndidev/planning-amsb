<?php

namespace App\Controllers\Vrac;

use App\Controllers\Controller;
use App\Service\BulkProductService;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Entity\BulkProduct;

class ProduitController extends Controller
{
  private BulkProductService $productService;
  private $module = "vrac";
  private $sse_event = "vrac/produits";

  public function __construct(
    private ?int $id
  ) {
    parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
    $this->productService = new BulkProductService();
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
    if (!$this->user->can_access($this->module)) {
      throw new AccessException();
    }

    $products = $this->productService->getProducts();

    $etag = ETag::get($products);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(
        json_encode(
          array_map(fn (BulkProduct $product) => $product->toArray(), $products)
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
    if (!$this->user->can_access($this->module)) {
      throw new AccessException();
    }

    $product = $this->productService->getProduct($id);

    if (!$product && !$dry_run) {
      $this->response->setCode(404);
      return;
    }

    if ($dry_run) {
      return $product;
    }

    $etag = ETag::get($product);

    if ($this->request->etag === $etag) {
      $this->response->setCode(304);
      return;
    }

    $this->headers["ETag"] = $etag;

    $this->response
      ->setBody(json_encode($product?->toArray()))
      ->setHeaders($this->headers);
  }

  /**
   * Crée un produit vrac.
   */
  public function create()
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    $input = $this->request->body;

    $product = $this->productService->createProduct($input);

    $id = $product->getId();

    $this->headers["Location"] = $_ENV["API_URL"] . "/vrac/produits/$id";

    $this->response
      ->setCode(201)
      ->setBody(json_encode($product->toArray()))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->sse_event, __FUNCTION__, $id, $product->toArray());
  }

  /**
   * Met à jour un produit.
   * 
   * @param int $id id du produit à modifier.
   */
  public function update(int $id)
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    if (!$this->productService->productExists($id)) {
      $this->response->setCode(404);
      return;
    }

    $input = $this->request->body;

    $product = $this->productService->updateProduct($id, $input);

    $this->response
      ->setBody(json_encode($product->toArray()))
      ->setHeaders($this->headers)
      ->flush();

    notify_sse($this->sse_event, __FUNCTION__, $id, $product->toArray());
  }

  /**
   * Supprime un produit.
   * 
   * @param int $id id du produit à supprimer.
   */
  public function delete(int $id)
  {
    if (!$this->user->can_edit($this->module)) {
      throw new AccessException();
    }

    if (!$this->productService->productExists($id)) {
      $this->response->setCode(404);
      return;
    }

    $this->productService->deleteProduct($id);

    $this->response->setCode(204)->flush();
    notify_sse($this->sse_event, __FUNCTION__, $id);
  }
}
