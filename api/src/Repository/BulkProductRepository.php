<?php

// Path: api/src/Repository/BulkProductRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Service\BulkService;
use ReflectionClass;

/**
 * @phpstan-import-type BulkProductArray from \App\Entity\Bulk\BulkProduct
 * @phpstan-import-type BulkQualityArray from \App\Entity\Bulk\BulkQuality
 */
final class BulkProductRepository extends Repository
{
    /** @var ReflectionClass<BulkProduct> */
    private ReflectionClass $productReflector;

    /** @var ReflectionClass<BulkQuality> */
    private ReflectionClass $qualityReflector;

    public function __construct(private BulkService $bulkService)
    {
        $this->productReflector = new ReflectionClass(BulkProduct::class);
        $this->qualityReflector = new ReflectionClass(BulkQuality::class);
    }

    /** @var array<int, BulkProduct> */
    static private array $productsCache = [];

    /** @var array<int, BulkQuality> */
    static private array $qualitiesCache = [];

    /**
     * Checks if a product exists in the database.
     * 
     * @param int $id ID of the product.
     */
    public function productExists(int $id): bool
    {
        return $this->mysql->exists('vrac_produits', $id);
    }

    /**
     * Checks if a quality exists in the database.
     * 
     * @param int $id ID of the quality.
     */
    public function qualityExists(int $id): bool
    {
        return $this->mysql->exists('vrac_qualites', $id);
    }

    /**
     * Fetch all bulk products.
     * 
     * @return Collection<BulkProduct> List of bulk products.
     */
    public function fetchProducts(): Collection
    {
        $productsStatement = "SELECT * FROM vrac_produits ORDER BY nom";
        $qualitiesStatement = "SELECT * FROM vrac_qualites ORDER BY nom";

        // Products
        $productsRequest = $this->mysql->query($productsStatement);

        if (!$productsRequest) {
            throw new DBException("Impossible de récupérer les produits vrac.");
        }

        /** @var BulkProductArray[] */
        $productsRaw = $productsRequest->fetchAll();

        // Qualities
        $qualitiesRequest = $this->mysql->query($qualitiesStatement);

        if (!$qualitiesRequest) {
            throw new DBException("Impossible de récupérer les qualités vrac.");
        }

        /** @var BulkQualityArray[] */
        $qualitiesRaw = $qualitiesRequest->fetchAll();

        $products = \array_map(
            function (array $productRaw) use ($qualitiesRaw) {
                $productRaw["qualites"] = \array_values(
                    \array_filter(
                        $qualitiesRaw,
                        fn($qualityRaw) => $qualityRaw["produit"] === $productRaw["id"]
                    )
                );

                $product = $this->bulkService->makeProductFromDatabase(new ArrayHandler($productRaw));

                return $product;
            },
            $productsRaw
        );

        foreach ($products as $product) {
            /** @var int */
            $id = $product->id;
            static::$productsCache[$id] = $product;
        }

        return new Collection($products);
    }

    /**
     * Fetch a bulk product.
     * 
     * @param int $id IDof the product.
     * 
     * @return ?BulkProduct Fetched product.
     */
    public function fetchProduct(int $id): ?BulkProduct
    {
        if (isset(static::$productsCache[$id])) {
            return static::$productsCache[$id];
        }

        if (!$this->productExists($id)) {
            return null;
        }

        /** @var BulkProduct */
        $product = $this->productReflector->newLazyProxy(
            function () use ($id) {
                $productStatement =
                    "SELECT
                        id,
                        nom,
                        couleur,
                        unite
                    FROM vrac_produits
                    WHERE id = :id";

                try {
                    /** @var BulkProductArray */
                    $productRaw = $this->mysql
                        ->prepareAndExecute($productStatement, ["id" => $id])
                        ->fetch();

                    $product = $this->bulkService->makeProductFromDatabase(new ArrayHandler($productRaw));

                    $product->qualities = $this->fetchProductQualities($id);

                    return $product;
                } catch (\PDOException $e) {
                    throw new DBException("Erreur lors de la récupération du produit vrac.", previous: $e);
                }
            }
        );

        $this->productReflector->getProperty('id')->setRawValueWithoutLazyInitialization($product, $id);

        static::$productsCache[$id] = $product;

        return $product;
    }

    /**
     * Fetch the qualities of a bulk product.
     * 
     * @param int $productId ID of the product.
     * 
     * @return BulkQuality[] Fetched qualities.
     */
    public function fetchProductQualities(int $productId): array
    {
        $qualitiesStatement =
            "SELECT
                id,
                nom,
                couleur
            FROM vrac_qualites
            WHERE produit = :productId";


        /** @var BulkQualityArray[] */
        $qualitiesRaw = $this->mysql
            ->prepareAndExecute($qualitiesStatement, ["productId" => $productId])
            ->fetchAll();

        $qualities = \array_map(
            fn($qualityRaw) => $this->bulkService->makeQualityFromDatabase(new ArrayHandler($qualityRaw)),
            $qualitiesRaw
        );

        // Add qualities to cache
        foreach ($qualities as $quality) {
            /** @var int */
            $id = $quality->id;
            static::$qualitiesCache[$id] = $quality;
        }

        return $qualities;
    }

    /**
     * Fetch a bulk quality.
     * 
     * @param int $id ID of the quality.
     * 
     * @return ?BulkQuality Fetched quality.
     */
    public function fetchQuality(int $id): ?BulkQuality
    {
        if (isset(static::$qualitiesCache[$id])) {
            return static::$qualitiesCache[$id];
        }

        if (!$this->qualityExists($id)) {
            return null;
        }

        /** @var BulkQuality */
        $quality = $this->qualityReflector->newLazyProxy(
            function () use ($id) {
                $qualityStatement =
                    "SELECT
                        id,
                        nom,
                        couleur
                    FROM vrac_qualites
                    WHERE id = :id";

                try {
                    /** @var BulkQualityArray */
                    $qualityRaw = $this->mysql
                        ->prepareAndExecute($qualityStatement, ["id" => $id])
                        ->fetch();

                    return $this->bulkService->makeQualityFromDatabase(new ArrayHandler($qualityRaw));
                } catch (\PDOException $e) {
                    throw new DBException("Erreur lors de la récupération de la qualité vrac.", previous: $e);
                }
            }
        );

        $this->qualityReflector->getProperty('id')->setRawValueWithoutLazyInitialization($quality, $id);

        static::$qualitiesCache[$id] = $quality;

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
            SET
                nom = :name,
                couleur = :color,
                unite = :unit
            ";

        $qualitiesStatement =
            "INSERT INTO vrac_qualites
            SET
                produit = :productId,
                nom = :name,
                couleur = :color
            )";

        $this->mysql->beginTransaction();

        $this->mysql->prepareAndExecute($productStatement, [
            'name' => $product->name,
            'color' => $product->color,
            'unit' => $product->unit,
        ]);

        $lastInsertId = (int) $this->mysql->lastInsertId();

        $this->mysql->prepareAndExecute($qualitiesStatement, \array_map(
            fn($quality) => [
                'productId' => $lastInsertId,
                'name' => $quality->name,
                'color' => $quality->color,
            ],
            $product->qualities
        ));

        $this->mysql->commit();

        /** @var BulkProduct */
        $newProduct = $this->fetchProduct($lastInsertId);

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

        $qualityStatement =
            "INSERT INTO vrac_qualites
            SET
                id = :id,
                produit = :productId,
                nom = :name,
                couleur = :color
            ON DUPLICATE KEY UPDATE
                nom = :name,
                couleur = :color
            ";

        $this->mysql->prepareAndExecute($productStatement, [
            'name' => $product->name,
            'color' => $product->color,
            'unit' => $product->unit,
            'id' => $product->id,
        ]);

        // QUALITIES
        // Delete qualities
        // !! DELETION TO BE PLACED *BEFORE* ADDING QUALITIES TO AVOID IMMEDIATE DELETION AFTER ADDITION !!
        // Compare the array passed by POST with the existing list of qualities for the relevant product
        $qualitiesRequest = $this->mysql->prepare("SELECT id FROM vrac_qualites WHERE produit = :productId");
        $qualitiesRequest->execute(['productId' => $product->id]);
        $existingQualitiesIds = $qualitiesRequest->fetchAll(\PDO::FETCH_COLUMN, 0);

        $submittedQualitiesIds = \array_map(fn($quality) => $quality->id, $product->qualities);
        $qualitiesIdsToBeDeleted = \array_diff($existingQualitiesIds, $submittedQualitiesIds);

        if (!empty($qualitiesIdsToBeDeleted)) {
            $deleteQualitiesStatement = "DELETE FROM vrac_qualites WHERE id IN (" . \implode(",", $qualitiesIdsToBeDeleted) . ")";
            $this->mysql->exec($deleteQualitiesStatement);
        }

        // Insert and update qualities
        $this->mysql->prepareAndExecute($qualityStatement, \array_map(
            fn($quality) => [
                'id' => $quality->id,
                'productId' => $product->id,
                'name' => $quality->name,
                'color' => $quality->color,
            ],
            $product->qualities
        ));

        /** @var int */
        $id = $product->id;

        /** @var BulkProduct */
        $updatedProduct = $this->fetchProduct($id);

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
