<?php

// Path: api/src/Service/StevedoringService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\HTTP\HTTPRequestBody;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Repository\StevedoringRepository;

final class StevedoringService
{
    private StevedoringRepository $stevedoringRepository;

    public function __construct()
    {
        $this->stevedoringRepository = new StevedoringRepository($this);
    }

    /**
     * @param array<mixed> $rawData 
     * 
     * @return StevedoringStaff 
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
            ->setActive($rawDataAH->getBool('active'))
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
            ->setActive($requestBody->getBool('active'))
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
}
