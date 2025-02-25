<?php

// Path: api/src/Service/BulkService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\Exceptions\Server\DB\DBException;
use App\Core\HTTP\HTTPRequestBody;
use App\DTO\BulkDispatchStatsDTO;
use App\DTO\Filter\BulkDispatchStatsFilterDTO;
use App\DTO\Filter\BulkFilterDTO;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkDispatchItem;
use App\Entity\Bulk\BulkProduct;
use App\Entity\Bulk\BulkQuality;
use App\Entity\ThirdParty;
use App\Repository\BulkAppointmentRepository;
use App\Repository\BulkProductRepository;

/**
 * @phpstan-import-type BulkAppointmentArray from \App\Entity\Bulk\BulkAppointment
 * @phpstan-import-type BulkProductArray from \App\Entity\Bulk\BulkProduct
 * @phpstan-import-type BulkQualityArray from \App\Entity\Bulk\BulkQuality
 * @phpstan-import-type BulkDispatchArray from \App\Entity\Bulk\BulkDispatchItem
 */
final class BulkService
{
    private BulkAppointmentRepository $appointmentRepository;
    private BulkProductRepository $productRepository;
    private ThirdPartyService $thirdPartyService;
    private StevedoringService $stevedoringService;

    public function __construct()
    {
        $this->appointmentRepository = new BulkAppointmentRepository($this);
        $this->productRepository = new BulkProductRepository($this);
        $this->thirdPartyService = new ThirdPartyService();
        $this->stevedoringService = new StevedoringService();
    }

    // ============
    // Appointments
    // ============

    /**
     * Creates a bulk appointment from database data. 
     * 
     * @param ArrayHandler $rawData 
     *
     * @return BulkAppointment 
     */
    public function makeBulkAppointmentFromDatabase(ArrayHandler $rawData): BulkAppointment
    {
        $appointment = new BulkAppointment();
        $appointment->id = $rawData->getInt('id');
        $appointment->date = $rawData->getDatetime('date_rdv', 'now');
        $appointment->time = $rawData->getDatetime('heure', null);
        $appointment->product = $this->getProduct($rawData->getInt('produit'));
        $appointment->quality = $this->getQuality($rawData->getInt('qualite'));
        $appointment->quantityValue = $rawData->getInt('quantite', 0);
        $appointment->quantityIsMax = $rawData->getBool('max');
        $appointment->supplier = $this->thirdPartyService->getThirdParty($rawData->getInt('fournisseur'));
        $appointment->customer = $this->thirdPartyService->getThirdParty($rawData->getInt('client'));
        $appointment->carrier = $this->thirdPartyService->getThirdParty($rawData->getInt('transporteur'));
        $appointment->isReady = $rawData->getBool('commande_prete');
        $appointment->orderNumber = $rawData->getString('num_commande');
        $appointment->publicComments = $rawData->getString('commentaire_public');
        $appointment->privateComments = $rawData->getString('commentaire_prive');
        $appointment->isOnTv = $rawData->getBool('show_on_tv');
        $appointment->isArchive = $rawData->getBool('archive');

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
        $appointment = new BulkAppointment();
        $appointment->id = $requestBody->getInt('id');
        $appointment->date = $requestBody->getDatetime('date_rdv', 'now');
        $appointment->time = $requestBody->getDatetime('heure', null);
        $appointment->product = $this->getProduct($requestBody->getInt('produit'));
        $appointment->quality = $this->getQuality($requestBody->getInt('qualite'));
        $appointment->quantityValue = $requestBody->getInt('quantite', 0);
        $appointment->quantityIsMax = $requestBody->getBool('max');
        $appointment->supplier = $this->thirdPartyService->getThirdParty($requestBody->getInt('fournisseur'));
        $appointment->customer = $this->thirdPartyService->getThirdParty($requestBody->getInt('client'));
        $appointment->carrier = $this->thirdPartyService->getThirdParty($requestBody->getInt('transporteur'));
        $appointment->isReady = $requestBody->getBool('commande_prete');
        $appointment->orderNumber = $requestBody->getString('num_commande');
        $appointment->publicComments = $requestBody->getString('commentaire_public');
        $appointment->privateComments = $requestBody->getString('commentaire_prive');
        $appointment->isOnTv = $requestBody->getBool('show_on_tv');
        $appointment->isArchive = $requestBody->getBool('archive');
        $appointment->dispatch = \array_map(
            // @phpstan-ignore argument.type
            fn($dispatchRaw) => $this->makeBulkDispatchItemFromFormData(new ArrayHandler($dispatchRaw)),
            $requestBody->getArray('dispatch')
        );

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

        if ($input->isSet('dispatch')) {
            $dispatchItems = \array_map(
                // @phpstan-ignore argument.type
                function (array $dispatchRaw) {
                    $dispatchItem = $this->makeBulkDispatchItemFromFormData(new ArrayHandler($dispatchRaw));
                    $dispatchItem->validate();
                    return $dispatchItem;
                },
                $input->getArray('dispatch')
            );
            $appointment = $this->appointmentRepository->updateDispatchForAppointment($id, $dispatchItems);
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
     * @param ArrayHandler $rawData
     * 
     * @return BulkProduct 
     */
    public function makeProductFromDatabase(ArrayHandler $rawData): BulkProduct
    {
        $product = new BulkProduct();
        $product->id = $rawData->getInt('id');
        $product->name = $rawData->getString('nom');
        $product->color = $rawData->getString('couleur');
        $product->unit = $rawData->getString('unite');
        $product->qualities = \array_map(
            // @phpstan-ignore argument.type
            fn($quality) => $this->makeQualityFromDatabase(new ArrayHandler($quality)),
            $rawData->getArray('qualites')
        );

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
        $product = new BulkProduct();
        $product->id = $requestBody->getInt('id');
        $product->name = $requestBody->getString('nom');
        $product->color = $requestBody->getString('couleur');
        $product->unit = $requestBody->getString('unite');
        $product->qualities = \array_map(
            // @phpstan-ignore argument.type
            fn($quality) => $this->makeQualityFromFormData(new ArrayHandler($quality)),
            $requestBody->getArray('qualites')
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
     * @param ArrayHandler $rawData 
     * 
     * @return BulkQuality 
     */
    public function makeQualityFromDatabase(ArrayHandler $rawData): BulkQuality
    {
        $quality = new BulkQuality();
        $quality->id = $rawData->getInt('id');
        $quality->name = $rawData->getString('nom');
        $quality->color = $rawData->getString('couleur');

        return $quality;
    }

    /**
     * Creates a bulk quality from form data.
     * 
     * @param ArrayHandler $rawData 
     * 
     * @return BulkQuality 
     */
    public function makeQualityFromFormData(ArrayHandler $rawData): BulkQuality
    {
        $quality = new BulkQuality();
        $quality->id = $rawData->getInt('id');
        $quality->name = $rawData->getString('nom');
        $quality->color = $rawData->getString('couleur');

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

    // ========
    // Dispatch
    // ========

    /**
     * Creates a bulk dispatch from database data.
     * 
     * @param ArrayHandler $rawData 
     * 
     * @return BulkDispatchItem 
     * 
     * @throws DBException 
     */
    public function makeBulkDispatchItemFromDatabase(ArrayHandler $rawData): BulkDispatchItem
    {
        $dispatch = new BulkDispatchItem();
        $dispatch->staff = $this->stevedoringService->getStaff($rawData->getInt('staff_id'));
        $dispatch->date = $rawData->getDatetime('date');
        $dispatch->remarks = $rawData->getString('remarks');

        return $dispatch;
    }

    public function makeBulkDispatchItemFromFormData(ArrayHandler $requestBody): BulkDispatchItem
    {
        $dispatch = new BulkDispatchItem();
        $dispatch->staff = $this->stevedoringService->getStaff($requestBody->getInt('staffId'));
        $dispatch->date = $requestBody->getDatetime('date');
        $dispatch->remarks = $requestBody->getString('remarks');

        return $dispatch;
    }

    public function getBulkDispatchStats(BulkDispatchStatsFilterDTO $filter): BulkDispatchStatsDTO
    {
        return $this->appointmentRepository->fetchDispatchStats($filter);
    }
}
