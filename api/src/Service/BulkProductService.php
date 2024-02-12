<?php

namespace App\Service;

use App\Repository\BulkProductRepository;
use App\Entity\{BulkProduct, BulkQuality};

class BulkProductService
{
    private BulkProductRepository $productRepository;

    public function __construct()
    {
        $this->productRepository = new BulkProductRepository();
    }

    /**
     * Vérifie si un produit existe dans la base de données.
     * 
     * @param int $id Identifiant du produit.
     */
    public function productExists(int $id): bool
    {
        return $this->productRepository->productExists($id);
    }

    /**
     * Récupère tous les produits vrac.
     * 
     * @return array<int, \App\Entity\BulkProduct> Liste des produits vrac
     */
    public function getProducts(): array
    {
        return $this->productRepository->getProducts();
    }

    /**
     * Récupère un produit vrac.
     * 
     * @param int $id ID du produit à récupérer
     * 
     * @return ?BulkProduct Produit récupéré
     */
    public function getProduct(int $id): ?BulkProduct
    {
        return $this->productRepository->getProduct($id);
    }

    /**
     * Crée un produit vrac.
     * 
     * @param array $input Eléments du produit à créer
     * 
     * @return BulkProduct Produit créé
     */
    public function createProduct(array $input): BulkProduct
    {
        return $this->productRepository->createProduct($input);
    }

    /**
     * Met à jour un produit vrac.
     * 
     * @param int   $id     ID du produit à modifier
     * @param array $input  Eléments du produit à modifier
     * 
     * @return BulkProduct Produit modifié
     */
    public function updateProduct($id, array $input): BulkProduct
    {
        return $this->productRepository->updateProduct($id, $input);
    }

    /**
     * Supprime un produit vrac.
     * 
     * @param int $id ID du produit à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->deleteProduct($id);
    }

    /**
     * Récupère les qualités d'un produit vrac.
     * 
     * @param int $productId ID du produit.
     * 
     * @return array<int, \App\Entity\BulkQuality> Qualités récupérées.
     */
    public function getQualities(int $productId): array
    {
        return $this->productRepository->getQualities($productId);
    }

    /**
     * Récupère une qualité vrac.
     * 
     * @param int $id ID de la qualité à récupérer.
     * 
     * @return ?BulkQuality Qualité récupérée.
     */
    public function getQuality(int $id): ?BulkQuality
    {
        return $this->productRepository->getQuality($id);
    }
}
