<?php

// Path: api/src/Schedule/Tasks/CreateFeedBulkAppointment.php

declare(strict_types=1);

namespace App\Scheduler\Tasks;

use App\Core\Array\ArrayHandler;
use App\Core\Component\SseEventNames;
use App\Core\Component\SSEHandler;
use App\DTO\Filter\BulkFilterDTO;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Bulk\BulkProduct;
use App\Scheduler\Task;
use App\Service\BulkService;
use App\Service\ThirdPartyService;

/**
 * Tâche pour créer un rendez-vous de vracs agro.
 */
final class CreateFeedBulkAppointment extends Task
{
    private BulkService $bulkService;
    private ThirdPartyService $thirdPartyService;

    private const FEED_PRODUCT_ID = 1;
    private const MISC_THIRD_PARTY_ID = 6;

    public function __construct(string $name = 'create_feed_bulk_appointment')
    {
        parent::__construct($name);

        $this->bulkService = new BulkService();
        $this->thirdPartyService = new ThirdPartyService();
    }

    public function execute(): void
    {
        $product = $this->getProduct();

        if ($this->appointmentAlreadyExists()) {
            throw new \RuntimeException('Le rendez-vous existe déjà');
        }

        $this->createAppointment($product);

        echo 'Appointment created successfully';
    }

    private function getProduct(): BulkProduct
    {
        $product = $this->bulkService->getProduct(self::FEED_PRODUCT_ID);

        if ($product === null) {
            throw new \RuntimeException("Le produit n'existe pas");
        }

        return $product;
    }

    private function appointmentAlreadyExists(): bool
    {
        $filterParameters = [
            'date_debut' => 'now',
            'date_fin' => 'now',
            'produit' => self::FEED_PRODUCT_ID,
        ];

        $bulkFilterDto = new BulkFilterDTO(new ArrayHandler($filterParameters));

        $existingAppointments = $this->bulkService->getAppointments($bulkFilterDto);

        return count($existingAppointments) > 0;
    }

    private function createAppointment(BulkProduct $product): void
    {
        $appointment = new BulkAppointment();
        $appointment->product = $product;
        $appointment->supplier = $this->thirdPartyService->getThirdParty(self::MISC_THIRD_PARTY_ID);
        $appointment->customer = $this->thirdPartyService->getThirdParty(self::MISC_THIRD_PARTY_ID);
        $appointment->date = new \DateTime('now');
        $appointment->isOnTv = false;

        $appointment = $this->bulkService->createAppointment($appointment);

        $sse = SSEHandler::getInstance();
        $sse->addEvent(
            SseEventNames::BULK_APPOINTMENT,
            'create',
            $appointment->id, // @phpstan-ignore argument.type
            $appointment
        );
        $sse->notify();
    }
}
