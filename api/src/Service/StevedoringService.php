<?php

// Path: api/src/Service/StevedoringService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\HTTP\HTTPRequestBody;
use App\Entity\Stevedoring\StevedoringEquipment;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Repository\StevedoringRepository;

final class StevedoringService
{
    private StevedoringRepository $stevedoringRepository;

    public function __construct()
    {
        $this->stevedoringRepository = new StevedoringRepository($this);
    }

    // =====
    // Staff
    // =====

    /**
     * @param array<mixed> $rawData 
     */
    public function makeStevedoringStaffFromDatabase(array $rawData): StevedoringStaff
    {
        $rawDataAH = new ArrayHandler($rawData);

        $stevedoringStaff = (new StevedoringStaff())
            ->setId($rawDataAH->getInt('id'))
            ->setFirstname($rawDataAH->getString('firstname'))
            ->setLastname($rawDataAH->getString('lastname'))
            ->setPhone($rawDataAH->getString('phone'))
            ->setType($rawDataAH->getString('type'))
            ->setTempWorkAgency($rawDataAH->getString('temp_work_agency', null))
            ->setActive($rawDataAH->getBool('is_active'))
            ->setComments($rawDataAH->getString('comments'))
            ->setDeletedAt($rawDataAH->getDatetime('deleted_at'));

        return $stevedoringStaff;
    }

    public function makeStevedoringStaffFromRequest(HTTPRequestBody $requestBody): StevedoringStaff
    {
        $stevedoringStaff = (new StevedoringStaff())
            ->setId($requestBody->getInt('id'))
            ->setFirstname($requestBody->getString('firstname'))
            ->setLastname($requestBody->getString('lastname'))
            ->setPhone($requestBody->getString('phone'))
            ->setType($requestBody->getString('type'))
            ->setTempWorkAgency($requestBody->getString('tempWorkAgency', null))
            ->setActive($requestBody->getBool('isActive'))
            ->setComments($requestBody->getString('comments'));

        if ($stevedoringStaff->getType() === "cdi") {
            $stevedoringStaff->setTempWorkAgency(null);
        }

        return $stevedoringStaff;
    }

    public function staffExists(int $id): bool
    {
        return $this->stevedoringRepository->staffExists($id);
    }

    /**
     * @return Collection<StevedoringStaff>
     */
    public function getAllStaff(): Collection
    {
        return $this->stevedoringRepository->fetchAllStaff();
    }

    public function getStaff(int $id): ?StevedoringStaff
    {
        return $this->stevedoringRepository->fetchStaff($id);
    }

    public function createStaff(HTTPRequestBody $requestBody): StevedoringStaff
    {
        $stevedoringStaff = $this->makeStevedoringStaffFromRequest($requestBody);

        $stevedoringStaff->validate();

        return $this->stevedoringRepository->createStaff($stevedoringStaff);
    }

    public function updateStaff(int $id, HTTPRequestBody $requestBody): StevedoringStaff
    {
        $stevedoringStaff = $this->makeStevedoringStaffFromRequest($requestBody)->setId($id);

        $stevedoringStaff->validate();

        return $this->stevedoringRepository->updateStaff($stevedoringStaff);
    }

    public function deleteStaff(int $id): void
    {
        $this->stevedoringRepository->deleteStaff($id);
    }

    // =========
    // Equipment
    // =========

    /**
     * @param array<mixed> $rawData
     */
    public function makeStevedoringEquipmentFromDatabase(array $rawData): StevedoringEquipment
    {
        $rawDataAH = new ArrayHandler($rawData);

        $stevedoringEquipment = (new StevedoringEquipment())
            ->setId($rawDataAH->getInt('id'))
            ->setType($rawDataAH->getString('type'))
            ->setBrand($rawDataAH->getString('brand'))
            ->setModel($rawDataAH->getString('model'))
            ->setInternalNumber($rawDataAH->getString('internal_number'))
            ->setSerialNumber($rawDataAH->getString('serial_number'))
            ->setComments($rawDataAH->getString('comments'))
            ->setActive($rawDataAH->getBool('is_active'));

        return $stevedoringEquipment;
    }

    public function makeStevedoringEquipmentFromRequest(HTTPRequestBody $requestBody): StevedoringEquipment
    {
        $stevedoringEquipment = (new StevedoringEquipment())
            ->setId($requestBody->getInt('id'))
            ->setType($requestBody->getString('type'))
            ->setBrand($requestBody->getString('brand'))
            ->setModel($requestBody->getString('model'))
            ->setInternalNumber($requestBody->getString('internalNumber'))
            ->setSerialNumber($requestBody->getString('serialNumber'))
            ->setComments($requestBody->getString('comments'))
            ->setActive($requestBody->getBool('isActive'));

        return $stevedoringEquipment;
    }

    public function equipmentExists(int $id): bool
    {
        return $this->stevedoringRepository->equipmentExists($id);
    }

    /**
     * @return Collection<StevedoringEquipment>
     */
    public function getAllEquipments(): Collection
    {
        return $this->stevedoringRepository->fetchAllEquipments();
    }

    public function getEquipment(int $id): ?StevedoringEquipment
    {
        return $this->stevedoringRepository->fetchEquipment($id);
    }

    public function createEquipment(HTTPRequestBody $requestBody): StevedoringEquipment
    {
        $stevedoringEquipment = $this->makeStevedoringEquipmentFromRequest($requestBody);

        $stevedoringEquipment->validate();

        return $this->stevedoringRepository->createEquipment($stevedoringEquipment);
    }

    public function updateEquipment(int $id, HTTPRequestBody $requestBody): StevedoringEquipment
    {
        $stevedoringEquipment = $this->makeStevedoringEquipmentFromRequest($requestBody)->setId($id);

        $stevedoringEquipment->validate();

        return $this->stevedoringRepository->updateEquipment($stevedoringEquipment);
    }

    public function deleteEquipment(int $id): void
    {
        $this->stevedoringRepository->deleteEquipment($id);
    }
}
