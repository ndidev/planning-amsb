<?php

namespace App\Controller\Bulk;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\BulkService;

class BulkProductController extends Controller
{
    private BulkService $bulkService;
    private Module $module = Module::BULK;
    private string $sseEventName = "vrac/produits";

    public function __construct(
        private ?int $id = null
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->bulkService = new BulkService();
        $this->processRequest();
    }

    public function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204)->addHeader("Allow", $this->supportedMethods);
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
                $this->response->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)->addHeader("Allow", $this->supportedMethods);
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
     */
    public function read(int $id)
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException();
        }

        $product = $this->bulkService->getProduct($id);

        if (!$product) {
            $this->response->setCode(404);
            return;
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

        $product = $this->bulkService->createProduct($input);

        $id = $product->getId();

        $this->headers["Location"] = $_ENV["API_URL"] . "/vrac/produits/$id";

        $this->response
            ->setCode(201)
            ->setHeaders($this->headers)
            ->setJSON($product);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $product);
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

        $product = $this->bulkService->updateProduct($id, $input);

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($product);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $product);
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

        $this->response->setCode(204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
