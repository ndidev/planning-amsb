<?php

// Path: api/src/Repository/BulkProductRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Service\BulkService;

/**
 * @phpstan-type BulkProductArray array{
 *                                  id?: int,
 *                                  nom?: string,
 *                                  couleur?: string,
 *                                  unite?: string,
 *                                  qualites?: BulkQualityArray[],
 *                                }
 * 
 * @phpstan-type BulkQualityArray array{
 *                                  id?: int,
 *                                  nom?: string,
 *                                  couleur?: string,
 *                                }
 */
final class BulkProductRepository extends Repository
{
    public function __construct(private BulkService $bulkService)
    {
        parent::__construct();
    }

    /**
     * @var BulkProduct[]
     */
    static private array $productsCache = [];

    /**
     * @var BulkQuality[]
     */
    static private array $qualitiesCache = [];

    /**
     * Checks if a product exists in the database.
     * 
     * @param int $id ID of the product.
     */
    public function productExists(int $id): bool
    {
        return $this->mysql->exists("vrac_produits", $id);
    }

    /**
     * Checks if a quality exists in the database.
     * 
     * @param int $id ID of the quality.
     */
    public function qualityExists(int $id): bool
    {
        return $this->mysql->exists("vrac_qualites", $id);
    }

    /**
     * Fetch all bulk products.
     * 
     * @return Collection<BulkProduct> List of bulk products.
     */
    public function getProducts(): Collection
    {
        $productsStatement = "SELECT * FROM vrac_produits ORDER BY nom";
        $qualitiesStatement = "SELECT * FROM vrac_qualites ORDER BY nom";

        // Products
        $productsRequest = $this->mysql->query($productsStatement);

        if (!$productsRequest) {
            throw new DBException("Impossible de récupérer les produits vrac.");
        }

        $productsRaw = $productsRequest->fetchAll();

        // Qualities
        $qualitiesRequest = $this->mysql->query($qualitiesStatement);

        if (!$qualitiesRequest) {
            throw new DBException("Impossible de récupérer les qualités vrac.");
        }

        $qualitiesRaw = $qualitiesRequest->fetchAll();

        $products = array_map(
            function (array $productRaw) use ($qualitiesRaw) {
                $productRaw["qualites"] = array_values(
                    array_filter(
                        $qualitiesRaw,
                        fn(array $qualityRaw) => $qualityRaw["produit"] === $productRaw["id"]
                    )
                );

                $product = $this->bulkService->makeProductFromDatabase($productRaw);

                return $product;
            },
            $productsRaw
        );

        static::$productsCache = $products;

        return new Collection($products);
    }

    /**
     * Fetch a bulk product.
     * 
     * @param int $id IDof the product.
     * 
     * @return ?BulkProduct Fetched product.
     */
    public function getProduct(int $id): ?BulkProduct
    {
        $cachedProducts = array_values(array_filter(
            static::$productsCache,
            fn(BulkProduct $cachedProduct) => $cachedProduct->getId() === $id
        ));

        $product = $cachedProducts[0] ?? null;

        if ($product) {
            return $product;
        }

        // Product
        $productStatement =
            "SELECT
                id,
                nom,
                couleur,
                unite
            FROM vrac_produits
            WHERE id = :id";

        $productRequest = $this->mysql->prepare($productStatement);
        $productRequest->execute(["id" => $id]);
        $productRaw = $productRequest->fetch();

        if (!is_array($productRaw)) return null;

        $product = $this->bulkService->makeProductFromDatabase($productRaw);

        // Qualities
        $qualities = $this->getProductQualities($id);

        $product->setQualities($qualities);

        array_push(static::$productsCache, $product);

        return $product;
    }

    /**
     * Fetch the qualities of a bulk product.
     * 
     * @param int $productId ID of the product.
     * 
     * @return BulkQuality[] Fetched qualities.
     */
    public function getProductQualities(int $productId): array
    {
        $qualitiesStatement =
            "SELECT
                id,
                nom,
                couleur
            FROM vrac_qualites
            WHERE produit = :productId";

        $qualitiesRequest = $this->mysql->prepare($qualitiesStatement);
        $qualitiesRequest->execute(["productId" => $productId]);
        $qualitiesRaw = $qualitiesRequest->fetchAll();

        $qualities = array_map(
            fn(array $qualityRaw) => $this->bulkService->makeQualityFromDatabase($qualityRaw),
            $qualitiesRaw
        );

        // Add qualities to cache
        static::$qualitiesCache = array_merge(static::$qualitiesCache, $qualities);

        return $qualities;
    }

    /**
     * Fetch a bulk quality.
     * 
     * @param int $id ID of the quality.
     * 
     * @return ?BulkQuality Fetched quality.
     */
    public function getQuality(int $id): ?BulkQuality
    {
        $cachedQualities = array_values(array_filter(
            static::$qualitiesCache,
            fn(BulkQuality $cachedQuality) => $cachedQuality->getId() === $id
        ));

        $quality = $cachedQualities[0] ?? null;

        if ($quality) {
            return $quality;
        }

        $qualityStatement =
            "SELECT
                id,
                nom,
                couleur
            FROM vrac_qualites
            WHERE id = :id";

        // Product
        $qualityRequest = $this->mysql->prepare($qualityStatement);
        $qualityRequest->execute(["id" => $id]);
        $qualityRaw = $qualityRequest->fetch();

        if (!is_array($qualityRaw)) return null;

        $quality = $this->bulkService->makeQualityFromDatabase($qualityRaw);

        array_push(static::$qualitiesCache, $quality);

        return $quality;
    }

    /**
     * Create a bulk product.
     * 
     * @param BulkProduct $product Product to be created.
     * 
     * @return BulkProduct Created product
     */
    public function createProduct(BulkProduct $product): BulkProduct
    {
        $productStatement =
            "INSERT INTO vrac_produits
            VALUES(
                NULL,
                :nom,
                :couleur,
                :unite
            )";

        $qualitiesStatement =
            "INSERT INTO vrac_qualites
            VALUES(
                NULL,
                :produit,
                :nom,
                :couleur
            )";

        $productRequest = $this->mysql->prepare($productStatement);

        $this->mysql->beginTransaction();
        $productRequest->execute([
            'nom' => $product->getName(),
            'couleur' => $product->getColor(),
            'unite' => $product->getUnit(),
        ]);
        $lastInsertId = (int) $this->mysql->lastInsertId();
        $this->mysql->commit();

        // Qualities
        $qualitiesRequest = $this->mysql->prepare($qualitiesStatement);
        foreach ($product->getQualities() as $quality) {
            $qualitiesRequest->execute([
                'produit' => $lastInsertId,
                'nom' => $quality->getName(),
                'couleur' => $quality->getColor(),
            ]);
        }

        /** @var BulkProduct */
        $newProduct = $this->getProduct($lastInsertId);

        return $newProduct;
    }

    /**
     * Update a bulk product.
     * 
     * @param BulkProduct $product Product to be updated.
     * 
     * @return BulkProduct Updated product.
     */
    public function updateProduct(BulkProduct $product): BulkProduct
    {
        $productStatement =
            "UPDATE vrac_produits
            SET
                nom = :name,
                couleur = :color,
                unite = :unit
            WHERE id = :id";

        $insertQualityStatement =
            "INSERT INTO vrac_qualites
            VALUES(
                NULL,
                :productId,
                :name,
                :color
            )";

        $updateQualityStatement =
            "UPDATE vrac_qualites
            SET
                nom = :name,
                couleur = :color
            WHERE id = :id";

        $productRequest = $this->mysql->prepare($productStatement);
        $productRequest->execute([
            'name' => $product->getName(),
            'color' => $product->getColor(),
            'unit' => $product->getUnit(),
            'id' => $product->getId(),
        ]);

        // QUALITIES
        // Delete qualities
        // !! DELETION TO BE PLACED *BEFORE* ADDING QUALITIES TO AVOID IMMEDIATE DELETION AFTER ADDITION !!
        // Compare the array passed by POST with the existing list of qualities for the relevant product
        $qualitiesRequest = $this->mysql->prepare("SELECT id FROM vrac_qualites WHERE produit = :productId");
        $qualitiesRequest->execute(['productId' => $product->getId()]);
        $existingQualitiesIds = $qualitiesRequest->fetchAll(\PDO::FETCH_COLUMN, 0);

        $submittedQualitiesIds = array_map(fn(BulkQuality $quality) => $quality->getId(), $product->getQualities());
        $qualitiesIdsToBeDeleted = array_diff($existingQualitiesIds, $submittedQualitiesIds);

        if (!empty($qualitiesIdsToBeDeleted)) {
            $deleteQualitiesStatement = "DELETE FROM vrac_qualites WHERE id IN (" . implode(",", $qualitiesIdsToBeDeleted) . ")";
            $this->mysql->exec($deleteQualitiesStatement);
        }

        // Insert and update qualities
        $insertQualityRequest = $this->mysql->prepare($insertQualityStatement);
        $updateQualityRequest = $this->mysql->prepare($updateQualityStatement);

        foreach ($product->getQualities() as $quality) {
            if ($quality->getId()) {
                $updateQualityRequest->execute([
                    'name' => $quality->getName(),
                    'color' => $quality->getColor(),
                    'id' => $quality->getId(),
                ]);
            } else {
                $insertQualityRequest->execute([
                    'productId' => $product->getId(),
                    'name' => $quality->getName(),
                    'color' => $quality->getColor(),
                ]);
            }
        }

        /** @var int */
        $id = $product->getId();

        /** @var BulkProduct */
        $updatedProduct = $this->getProduct($id);

        return $updatedProduct;
    }

    /**
     * Delete a bulk product.
     * 
     * @param int $id ID of the product to be deleted.
     * 
     * @return bool `true` if success, `false` if error.
     */
    public function deleteProduct(int $id): bool
    {
        $request = $this->mysql->prepare("DELETE FROM vrac_produits WHERE id = :id");
        $success = $request->execute(["id" => $id]);

        if (!$success) {
            throw new DBException("Erreur lors de la suppression");
        }

        return $success;
    }
}
