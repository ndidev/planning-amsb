<?php

// Path: api/src/Service/BulkService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\HTTPRequestBody;
use App\DTO\Filter\BulkFilterDTO;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Entity\ThirdParty;
use App\Repository\BulkAppointmentRepository;
use App\Repository\BulkProductRepository;

/**
 * @phpstan-import-type BulkAppointmentArray from \App\Repository\BulkAppointmentRepository
 * @phpstan-import-type BulkProductArray from \App\Repository\BulkProductRepository
 * @phpstan-import-type BulkQualityArray from \App\Repository\BulkProductRepository
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
        $rawDataAH = new ArrayHandler($rawData);

        $appointment = (new BulkAppointment())
            ->setId($rawDataAH->getInt('id'))
            ->setDate($rawDataAH->getDatetime('date_rdv', new \DateTimeImmutable("now")))
            ->setTime($rawDataAH->getDatetime('heure', null))
            ->setProduct($this->getProduct($rawDataAH->getInt('produit')))
            ->setQuality($this->getQuality($rawDataAH->getInt('qualite')))
            ->setQuantityValue($rawDataAH->getInt('quantite', 0))
            ->setQuantityIsMax($rawDataAH->getBool('max'))
            ->setReady($rawDataAH->getBool('commande_prete'))
            ->setSupplier($this->thirdPartyService->getThirdParty($rawDataAH->getInt('fournisseur')))
            ->setCustomer($this->thirdPartyService->getThirdParty($rawDataAH->getInt('client')))
            ->setCarrier($this->thirdPartyService->getThirdParty($rawDataAH->getInt('transporteur')))
            ->setOrderNumber($rawDataAH->getString('num_commande'))
            ->setPublicComments($rawDataAH->getString('commentaire_public'))
            ->setPrivateComments($rawDataAH->getString('commentaire_prive'))
            ->setArchive($rawDataAH->getBool('archive'));

        return $appointment;
    }

    /**
     * Creates a bulk appointment from form data.
     * 
     * @param HTTPRequestBody $requestBody
     * 
     * @return BulkAppointment
     */
    public function makeBulkAppointmentFromFormData(HTTPRequestBody $requestBody): BulkAppointment
    {
        $appointment = (new BulkAppointment())
            ->setId($requestBody->getInt('id'))
            ->setDate($requestBody->getDatetime('date_rdv', 'now'))
            ->setTime($requestBody->getDatetime('heure'))
            ->setProduct($this->getProduct($requestBody->getInt('produit')))
            ->setQuality($this->getQuality($requestBody->getInt('qualite')))
            ->setQuantityValue($requestBody->getInt('quantite', 0))
            ->setQuantityIsMax($requestBody->getBool('max'))
            ->setReady($requestBody->getBool('commande_prete'))
            ->setSupplier($this->thirdPartyService->getThirdParty($requestBody->getInt('fournisseur')))
            ->setCustomer($this->thirdPartyService->getThirdParty($requestBody->getInt('client')))
            ->setCarrier($this->thirdPartyService->getThirdParty($requestBody->getInt('transporteur')))
            ->setOrderNumber($requestBody->getString('num_commande'))
            ->setPublicComments($requestBody->getString('commentaire_public'))
            ->setPrivateComments($requestBody->getString('commentaire_prive'))
            ->setArchive($requestBody->getBool('archive'));

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
     * @param BulkFilterDTO $filter Filter to apply.
     * 
     * @return Collection<BulkAppointment> All retrieved appointments.
     */
    public function getAppointments(BulkFilterDTO $filter): Collection
    {
        return $this->appointmentRepository->getAppointments($filter);
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
     * @param HTTPRequestBody $input Elements of the appointment to create.
     * 
     * @return BulkAppointment Created appointment.
     */
    public function createAppointment(HTTPRequestBody $input): BulkAppointment
    {
        $appointment = $this->makeBulkAppointmentFromFormData($input);

        $appointment->validate();

        return $this->appointmentRepository->createAppointment($appointment);
    }

    /**
     * Update a bulk appointment.
     * 
     * @param int             $id    ID of the appointment to update.
     * @param HTTPRequestBody $input Elements of the appointment to update.
     * 
     * @return BulkAppointment Updated appointment.
     */
    public function updateAppointment($id, HTTPRequestBody $input): BulkAppointment
    {
        $appointment = $this->makeBulkAppointmentFromFormData($input)->setId($id);

        $appointment->validate();

        return $this->appointmentRepository->updateAppointment($appointment);
    }

    /**
     * Updates certain properties of a bulk appointment.
     * 
     * @param int             $id    ID of the appointment to update.
     * @param HTTPRequestBody $input Data to update.
     * 
     * @return BulkAppointment Updated appointment.
     */
    public function patchAppointment(int $id, HTTPRequestBody $input): BulkAppointment
    {
        $appointment = $this->getAppointment($id);

        if ($appointment === null) {
            throw new NotFoundException("Le RDV vrac n'existe pas.");
        }

        if ($input->isSet('commande_prete')) {
            $appointment = $this->appointmentRepository->setIsReady($id, $input->getBool('commande_prete'));
        }

        if ($input->isSet('archive')) {
            $appointment = $this->appointmentRepository->setIsArchive($id, $input->getBool('archive'));
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
            ->setId($rawData["id"])
            ->setName($rawData["nom"])
            ->setColor($rawData["couleur"])
            ->setUnit($rawData["unite"]);

        $qualities = \array_map(
            fn(array $quality) => $this->makeQualityFromDatabase($quality),
            $rawData["qualites"] ?? []
        );

        $product->setQualities($qualities);

        return $product;
    }

    /**
     * Creates a bulk product from form data.
     * 
     * @param HTTPRequestBody $requestBody 
     * 
     * @return BulkProduct 
     */
    public function makeProductFromFormData(HTTPRequestBody $requestBody): BulkProduct
    {
        $product = (new BulkProduct())
            ->setId($requestBody->getInt('id'))
            ->setName($requestBody->getString('nom'))
            ->setColor($requestBody->getString('couleur'))
            ->setUnit($requestBody->getString('unite'));

        /** @phpstan-var BulkQualityArray[] $qualities */
        $qualities = $requestBody->getArray('qualites');

        $product->setQualities(
            \array_map(
                fn(array $quality) => $this->makeQualityFromFormData($quality),
                $qualities
            )
        );

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
        return $this->productRepository->fetchProducts();
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

        return $this->productRepository->fetchProduct($id);
    }

    /**
     * Create a bulk product.
     * 
     * @param HTTPRequestBody $input Elements of the product to create.
     * 
     * @return BulkProduct Created product.
     */
    public function createProduct(HTTPRequestBody $input): BulkProduct
    {
        $product = $this->makeProductFromFormData($input);

        $product->validate();

        return $this->productRepository->createProduct($product);
    }

    /**
     * Update a bulk product.
     * 
     * @param int             $id    ID of the product to update.
     * @param HTTPRequestBody $input Elements of the product to update.
     * 
     * @return BulkProduct Updated product.
     */
    public function updateProduct(int $id, HTTPRequestBody $input): BulkProduct
    {
        $product = $this->makeProductFromFormData($input)->setId($id);

        $product->validate();

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
            ->setId($rawData["id"])
            ->setName($rawData["nom"])
            ->setColor($rawData["couleur"]);

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
        $rawDataAH = new ArrayHandler($rawData);

        $quality = (new BulkQuality())
            ->setId($rawDataAH->getInt('id'))
            ->setName($rawDataAH->getString('nom'))
            ->setColor($rawDataAH->getString('couleur'));

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
        return $this->productRepository->fetchProductQualities($productId);
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

        return $this->productRepository->fetchQuality($id);
    }
}
