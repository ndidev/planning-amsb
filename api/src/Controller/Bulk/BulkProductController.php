<?php

namespace App\Controller\Bulk;

use App\Controller\Controller;
use App\Service\BulkService;
use App\Core\HTTP\ETag;
use App\Core\Exceptions\Client\Auth\AccessException;

class BulkProductController extends Controller
{
    private BulkService $bulkService;
    private $module = "vrac";
    private $sse_event = "vrac/produits";

    public function __construct(
        private ?int $id
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->bulkService = new BulkService();
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

        $products = $this->bulkService->getProducts();

        $etag = ETag::get($products);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($products);
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

        $product = $this->bulkService->getProduct($id);

        if (!$product && !$dryRun) {
            $this->response->setCode(404);
            return;
        }

        if ($dryRun) {
            return $product;
        }

        $etag = ETag::get($product);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($product);
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

        $produit = $this->bulkService->createProduct($input);

        $id = $produit->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/vrac/produits/$id";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setJSON($produit);

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

        if (!$this->bulkService->productExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $input = $this->request->body;

        $produit = $this->bulkService->updateProduct($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($produit->toArray());

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

        if (!$this->bulkService->productExists($id)) {
            $this->response->setCode(404);
            return;
        }

        $this->bulkService->deleteProduct($id);

        $this->response->setCode(204)->flush();
        notify_sse($this->sse_event, __FUNCTION__, $id);
    }
}
