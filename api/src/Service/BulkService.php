<?php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Repository\BulkAppointmentRepository;
use App\Repository\BulkProductRepository;

class BulkService
{
    private BulkAppointmentRepository $appointmentRepository;
    private BulkProductRepository $productRepository;

    public function __construct()
    {
        $this->appointmentRepository = new BulkAppointmentRepository();
        $this->productRepository = new BulkProductRepository();
    }

    // ============
    // Appointments
    // ============

    /**
     * Creates a bulk appointment from database data.
     *
     * @param array $rawData 
     *
     * @return BulkAppointment 
     */
    public function makeBulkAppointmentFromDatabase(array $rawData): BulkAppointment
    {
        $thirdPartyService = new ThirdPartyService();

        $appointment = (new BulkAppointment())
            ->setId($rawData["id"] ?? null)
            ->setDate($rawData["date_rdv"] ?? new \DateTimeImmutable("now"))
            ->setTime($rawData["heure"] ?? null)
            ->setProduct($this->getProduct($rawData["produit"] ?? null))
            ->setQuality($this->getQuality($rawData["qualite"] ?? null))
            ->setQuantity($rawData["quantite"] ?? 0, $rawData["max"] ?? false)
            ->setReady($rawData["commande_prete"] ?? false)
            ->setSupplier($thirdPartyService->getThirdParty($rawData["fournisseur"] ?? null))
            ->setCustomer($thirdPartyService->getThirdParty($rawData["client"] ?? null))
            ->setCarrier($thirdPartyService->getThirdParty($rawData["transporteur"] ?? null))
            ->setOrderNumber($rawData["num_commande"] ?? "")
            ->setComments($rawData["commentaire"] ?? "");

        return $appointment;
    }

    /**
     * Creates a bulk appointment from form data.
     * 
     * @param array $rawData
     * 
     * @return BulkAppointment
     */
    public function makeBulkAppointmentFromFormData(array $rawData): BulkAppointment
    {
        $thirdPartyService = new ThirdPartyService();

        $appointment = (new BulkAppointment())
            ->setId($rawData["id"] ?? null)
            ->setDate($rawData["date_rdv"] ?? new \DateTimeImmutable("now"))
            ->setTime($rawData["heure"] ?? null)
            ->setProduct($rawData["produit"] ?? null)
            ->setQuality($rawData["qualite"] ?? null)
            ->setQuantity($rawData["quantite"] ?? 0, $rawData["max"] ?? false)
            ->setReady($rawData["commande_prete"] ?? false)
            ->setSupplier($thirdPartyService->getThirdParty($rawData["fournisseur"] ?? null))
            ->setCustomer($thirdPartyService->getThirdParty($rawData["client"] ?? null))
            ->setCarrier($thirdPartyService->getThirdParty($rawData["transporteur"] ?? null))
            ->setOrderNumber($rawData["num_commande"] ?? "")
            ->setComments($rawData["commentaire"] ?? "");

        return $appointment;
    }

    /**
     * Checks if a bulk appointment exists in the database.
     * 
     * @param int $id Bulk appointment ID.
     * 
     * @return bool True if the bulk appointment exists, false otherwise.
     */
    public function appointmentExists(int $id): bool
    {
        return $this->appointmentRepository->appointmentExists($id);
    }

    /**
     * Retrieves all bulk appointments.
     * 
     * @return Collection<BulkAppointment> All retrieved appointments.
     */
    public function getAppointments(): Collection
    {
        return $this->appointmentRepository->getAppointments();
    }

    /**
     * Retrieves a bulk appointment.
     * 
     * @param int $id ID of the appointment to retrieve.
     * 
     * @return ?BulkAppointment Retrieved appointment.
     */
    public function getAppointment(int $id): ?BulkAppointment
    {
        return $this->appointmentRepository->getAppointment($id);
    }

    /**
     * Create a bulk appointment.
     * 
     * @param array $input Elements of the appointment to create.
     * 
     * @return BulkAppointment Created appointment.
     */
    public function createAppointment(array $input): BulkAppointment
    {
        $rdv = $this->makeBulkAppointmentFromFormData($input);

        return $this->appointmentRepository->createAppointment($rdv);
    }

    /**
     * Update a bulk appointment.
     * 
     * @param int   $id    ID of the appointment to update.
     * @param array $input Elements of the appointment to update.
     * 
     * @return BulkAppointment Updated appointment.
     */
    public function updateAppointment($id, array $input): BulkAppointment
    {
        $rdv = $this->makeBulkAppointmentFromFormData($input)->setId($id);

        return $this->appointmentRepository->updateAppointment($rdv);
    }

    /**
     * Updates certain properties of a bulk appointment.
     * 
     * @param int   $id    ID of the appointment to update.
     * @param array $input Data to update.
     * 
     * @return BulkAppointment Updated appointment.
     */
    public function patchAppointment(int $id, array $input): BulkAppointment
    {
        if (isset($input["commande_prete"])) {
            return $this->appointmentRepository->setIsReady($id, (bool) $input["commande_prete"]);
        }

        return $this->getAppointment($id);
    }

    /**
     * Delete a bulk appointment.
     * 
     * @param int $id ID of the appointment to delete.
     */
    public function deleteAppointment(int $id): void
    {
        $this->appointmentRepository->deleteAppointment($id);
    }

    // ========
    // Products
    // ========

    /**
     * Creates a bulk product from database data.
     * 
     * @param array $rawData 
     * 
     * @return BulkProduct 
     */
    public function makeProductFromDatabase(array $rawData): BulkProduct
    {
        $product = (new BulkProduct())
            ->setId($rawData["id"] ?? null)
            ->setName($rawData["nom"] ?? "")
            ->setColor($rawData["couleur"] ?? "")
            ->setUnit($rawData["unite"] ?? "");

        $qualities = array_map(fn($quality) => $this->makeQualityFromDatabase($quality), $rawData["qualites"] ?? []);

        $product->setQualities($qualities);

        return $product;
    }

    /**
     * Creates a bulk product from form data.
     * 
     * @param array $rawData 
     * 
     * @return BulkProduct 
     */
    public function makeProductFromFormData(array $rawData): BulkProduct
    {
        $product = (new BulkProduct())
            ->setId($rawData["id"] ?? null)
            ->setName($rawData["nom"] ?? "")
            ->setColor($rawData["couleur"] ?? "")
            ->setUnit($rawData["unite"] ?? "");

        $qualities = array_map(fn($quality) => $this->makeQualityFromFormData($quality), $rawData["qualites"] ?? []);

        $product->setQualities($qualities);

        return $product;
    }

    /**
     * Checks if a product exists in the database.
     * 
     * @param int $id Product ID.
     * 
     * @return bool True if the product exists, false otherwise.
     */
    public function productExists(int $id): bool
    {
        return $this->productRepository->productExists($id);
    }

    /**
     * Retrieves all bulk products.
     * 
     * @return Collection<BulkProduct> List of bulk products.
     */
    public function getProducts(): Collection
    {
        return $this->productRepository->getProducts();
    }

    /**
     * Retrieves a bulk product.
     * 
     * @param ?int $id ID of the product to retrieve.
     * 
     * @return ?BulkProduct Retrieved product.
     */
    public function getProduct(?int $id): ?BulkProduct
    {
        if ($id === null) {
            return null;
        }

        return $this->productRepository->getProduct($id);
    }

    /**
     * Create a bulk product.
     * 
     * @param array $input Elements of the product to create.
     * 
     * @return BulkProduct Created product.
     */
    public function createProduct(array $input): BulkProduct
    {
        $product = $this->makeProductFromFormData($input);

        return $this->productRepository->createProduct($product);
    }

    /**
     * Update a bulk product.
     * 
     * @param int   $id     ID of the product to update.
     * @param array $input  Elements of the product to update.
     * 
     * @return BulkProduct Updated product.
     */
    public function updateProduct(int $id, array $input): BulkProduct
    {
        $product = $this->makeProductFromFormData($input)->setId($id);

        return $this->productRepository->updateProduct($product);
    }

    /**
     * Delete a bulk product.
     * 
     * @param int $id ID of the product to delete.
     * 
     * @return bool TRUE if successful, FALSE if error.
     */
    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->deleteProduct($id);
    }


    // =========
    // Qualities
    // =========

    /**
     * Creates a bulk quality from database data.
     *
     * @param array $rawData 
     *
     * @return BulkQuality 
     */
    public function makeQualityFromDatabase(array $rawData): BulkQuality
    {
        $quality = (new BulkQuality())
            ->setId($rawData["id"] ?? null)
            ->setName($rawData["nom"] ?? "")
            ->setColor($rawData["couleur"] ?? "");

        return $quality;
    }

    /**
     * Creates a bulk quality from form data.
     * 
     * @param array $rawData 
     * 
     * @return BulkQuality 
     */
    public function makeQualityFromFormData(array $rawData): BulkQuality
    {
        $quality = (new BulkQuality())
            ->setId($rawData["id"] ?? null)
            ->setName($rawData["nom"] ?? "")
            ->setColor($rawData["couleur"] ?? "");

        return $quality;
    }

    /**
     * Retrieves the qualities of a bulk product.
     * 
     * @param int $productId ID of the product.
     * 
     * @return BulkQuality[] Retrieved qualities.
     */
    public function getQualities(int $produitId): array
    {
        return $this->productRepository->getProductQualities($produitId);
    }

    /**
     * Get a bulk quality.
     * 
     * @param ?int $id ID of the quality to get.
     * 
     * @return ?BulkQuality Fetched quality.
     */
    public function getQuality(?int $id): ?BulkQuality
    {
        if ($id === null) {
            return null;
        }

        return $this->productRepository->getQuality($id);
    }
}
