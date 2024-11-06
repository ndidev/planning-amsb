<?php

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\Exceptions\Client\NotFoundException;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Entity\ThirdParty;
use App\Repository\BulkAppointmentRepository;
use App\Repository\BulkProductRepository;

/**
 * @phpstan-type BulkAppointmentArray array{
 *                                      id?: int,
 *                                      date_rdv?: string,
 *                                      heure?: string,
 *                                      produit?: int,
 *                                      qualite?: int,
 *                                      quantite?: int,
 *                                      max?: bool,
 *                                      commande_prete?: bool,
 *                                      fournisseur?: int,
 *                                      client?: int,
 *                                      transporteur?: int,
 *                                      num_commande?: string,
 *                                      commentaire?: string,
 *                                    }
 * 
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
final class BulkService
{
    private BulkAppointmentRepository $appointmentRepository;
    private BulkProductRepository $productRepository;
    private ThirdPartyService $thirdPartyService;

    public function __construct()
    {
        $this->appointmentRepository = new BulkAppointmentRepository($this);
        $this->productRepository = new BulkProductRepository($this);
        $this->thirdPartyService = new ThirdPartyService();
    }

    // ============
    // Appointments
    // ============

    /**
     * Creates a bulk appointment from database data.
     *
     * @param array $rawData 
     * 
     * @phpstan-param BulkAppointmentArray $rawData 
     *
     * @return BulkAppointment 
     */
    public function makeBulkAppointmentFromDatabase(array $rawData): BulkAppointment
    {
        $appointment = (new BulkAppointment())
            ->setId($rawData["id"] ?? null)
            ->setDate($rawData["date_rdv"] ?? new \DateTimeImmutable("now"))
            ->setTime($rawData["heure"] ?? null)
            ->setProduct($this->getProduct($rawData["produit"] ?? null))
            ->setQuality($this->getQuality($rawData["qualite"] ?? null))
            ->setQuantity($rawData["quantite"] ?? 0, $rawData["max"] ?? false)
            ->setReady($rawData["commande_prete"] ?? false)
            ->setSupplier($this->thirdPartyService->getThirdParty($rawData["fournisseur"] ?? null))
            ->setCustomer($this->thirdPartyService->getThirdParty($rawData["client"] ?? null))
            ->setCarrier($this->thirdPartyService->getThirdParty($rawData["transporteur"] ?? null))
            ->setOrderNumber($rawData["num_commande"] ?? "")
            ->setComments($rawData["commentaire"] ?? "");

        return $appointment;
    }

    /**
     * Creates a bulk appointment from form data.
     * 
     * @param array $rawData
     * 
     * @phpstan-param BulkAppointmentArray $rawData
     * 
     * @return BulkAppointment
     */
    public function makeBulkAppointmentFromFormData(array $rawData): BulkAppointment
    {
        $appointment = (new BulkAppointment())
            ->setId($rawData["id"] ?? null)
            ->setDate($rawData["date_rdv"] ?? new \DateTimeImmutable("now"))
            ->setTime($rawData["heure"] ?? null)
            ->setProduct($this->getProduct($rawData["produit"] ?? null))
            ->setQuality($this->getQuality($rawData["qualite"] ?? null))
            ->setQuantity($rawData["quantite"] ?? 0, $rawData["max"] ?? false)
            ->setReady($rawData["commande_prete"] ?? false)
            ->setSupplier($this->thirdPartyService->getThirdParty($rawData["fournisseur"] ?? null))
            ->setCustomer($this->thirdPartyService->getThirdParty($rawData["client"] ?? null))
            ->setCarrier($this->thirdPartyService->getThirdParty($rawData["transporteur"] ?? null))
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
     * Get appointments for a supplier.
     * 
     * @param ThirdParty $supplier 
     * @param \DateTimeInterface $startDate 
     * @param \DateTimeInterface $endDate 
     * 
     * @return Collection<BulkAppointment>
     */
    public function getPdfAppointments(
        ThirdParty $supplier,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): Collection {
        return $this->appointmentRepository->getPdfAppointments(
            $supplier,
            $startDate,
            $endDate
        );
    }

    /**
     * Create a bulk appointment.
     * 
     * @param array $input Elements of the appointment to create.
     * 
     * @phpstan-param BulkAppointmentArray $input
     * 
     * @return BulkAppointment Created appointment.
     */
    public function createAppointment(array $input): BulkAppointment
    {
        $appointment = $this->makeBulkAppointmentFromFormData($input);

        return $this->appointmentRepository->createAppointment($appointment);
    }

    /**
     * Update a bulk appointment.
     * 
     * @param int   $id    ID of the appointment to update.
     * @param array $input Elements of the appointment to update.
     * 
     * @phpstan-param BulkAppointmentArray $input
     * 
     * @return BulkAppointment Updated appointment.
     */
    public function updateAppointment($id, array $input): BulkAppointment
    {
        $appointment = $this->makeBulkAppointmentFromFormData($input)->setId($id);

        return $this->appointmentRepository->updateAppointment($appointment);
    }

    /**
     * Updates certain properties of a bulk appointment.
     * 
     * @param int   $id    ID of the appointment to update.
     * @param array $input Data to update.
     * 
     * @phpstan-param array{commande_prete?: int} $input
     * 
     * @return BulkAppointment Updated appointment.
     */
    public function patchAppointment(int $id, array $input): BulkAppointment
    {
        $appointment = $this->getAppointment($id);

        if ($appointment === null) {
            throw new NotFoundException("Le RDV vrac n'existe pas.");
        }

        if (isset($input["commande_prete"])) {
            $appointment = $this->appointmentRepository->setIsReady($id, (bool) $input["commande_prete"]);
        }

        return $appointment;
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
     * @phpstan-param BulkProductArray $rawData
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
     * @phpstan-param BulkProductArray $rawData
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
     * @phpstan-param BulkProductArray $input
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
     * @phpstan-param BulkProductArray $input
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
     * @phpstan-param BulkQualityArray $rawData
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
     * @phpstan-param BulkQualityArray $rawData
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
    public function getQualities(int $productId): array
    {
        return $this->productRepository->getProductQualities($productId);
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
