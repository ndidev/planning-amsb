<?php

// Path: api/src/Controller/Bulk/BulkProductController.php

declare(strict_types=1);

namespace App\Controller\Bulk;

use App\Controller\Controller;
use App\Core\Component\Module;
use App\Core\Exceptions\Client\Auth\AccessException;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Array\Environment;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\BulkService;

final class BulkProductController extends Controller
{
    private BulkService $bulkService;
    private string $module = Module::BULK;
    private string $sseEventName = "vrac/produits";

    public function __construct(
        private ?int $id = null
    ) {
        parent::__construct("OPTIONS, HEAD, GET, POST, PUT, DELETE");
        $this->bulkService = new BulkService();
        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch ($this->request->getMethod()) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
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
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les produits vrac.
     */
    public function readAll(): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux produits vrac.");
        }

        $products = $this->bulkService->getProducts();

        $etag = ETag::get($products);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($products);
    }

    /**
     * Récupère un produit vrac.
     * 
     * @param int  $id      id du produit à récupérer.
     */
    public function read(int $id): void
    {
        if (!$this->user->canAccess($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour accéder aux produits vrac.");
        }

        $product = $this->bulkService->getProduct($id);

        if (!$product) {
            throw new NotFoundException("Le produit n'existe pas.");
        }

        $etag = ETag::get($product);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($product);
    }

    /**
     * Crée un produit vrac.
     */
    public function create(): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour créer un produit vrac.");
        }

        $input = $this->request->getBody();

        $product = $this->bulkService->createProduct($input);

        /** @var int $id */
        $id = $product->id;

        $this->response
            ->setCode(HTTPResponse::HTTP_CREATED_201)
            ->addHeader("Location", Environment::getString('API_URL') . "/vrac/produits/$id")
            ->setJSON($product);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $product);
    }

    /**
     * Met à jour un produit.
     * 
     * @param ?int $id id du produit à modifier.
     */
    public function update(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour modifier un produit vrac.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du produit vrac est obligatoire.");
        }

        if (!$this->bulkService->productExists($id)) {
            throw new NotFoundException("Le produit n'existe pas.");
        }

        $input = $this->request->getBody();

        $product = $this->bulkService->updateProduct($id, $input);

        $this->response->setJSON($product);

        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id, $product);
    }

    /**
     * Supprime un produit.
     * 
     * @param ?int $id id du produit à supprimer.
     */
    public function delete(?int $id = null): void
    {
        if (!$this->user->canEdit($this->module)) {
            throw new AccessException("Vous n'avez pas les droits pour supprimer un produit vrac.");
        }

        if (!$id) {
            throw new BadRequestException("L'identifiant du produit vrac est obligatoire.");
        }

        if (!$this->bulkService->productExists($id)) {
            throw new NotFoundException("Le produit n'existe pas.");
        }

        $this->bulkService->deleteProduct($id);

        $this->response->setCode(HTTPResponse::HTTP_NO_CONTENT_204);
        $this->sse->addEvent($this->sseEventName, __FUNCTION__, $id);
    }
}
