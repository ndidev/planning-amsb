<?php

// Path: api/src/Repository/StevedoringRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Service\StevedoringService;

final class StevedoringRepository extends Repository
{
    public function __construct(private StevedoringService $stevedoringService)
    {
        parent::__construct();
    }

    public function staffExists(int $id): bool
    {
        $statement = "SELECT deleted_at FROM stevedoring_staff WHERE id = :id";

        $request = $this->mysql->prepare($statement);
        $request->execute(['id' => $id]);

        $response = $request->fetch(\PDO::FETCH_NUM);

        if (!is_array($response)) {
            return false;
        }

        if (null !== $response[0]) {
            return false;
        }

        return true;
    }

    public function taskExists(int $id): bool
    {
        return $this->mysql->exists("stevedoring_tasks", $id);
    }

    /**
     * @return Collection<StevedoringStaff>
     */
    public function fetchAllStaff(): Collection
    {
        $staffStatement = "SELECT * FROM stevedoring_staff ORDER BY lastname ASC";

        $staffRequest = $this->mysql->query($staffStatement);

        if (!$staffRequest) {
            throw new DBException("Impossible de récupérer le personnel de manutention.");
        }

        /** @var array<array<mixed>> */
        $staffRaw = $staffRequest->fetchAll();

        $allStaff = array_map(
            fn($staff) => $this->stevedoringService->makeStevedoringStaffFromDatabase($staff),
            $staffRaw
        );

        return new Collection($allStaff);
    }

    public function fetchStaff(int $id): ?StevedoringStaff
    {
        $staffStatement = "SELECT * FROM stevedoring_staff WHERE id = :id";

        $staffRequest = $this->mysql->prepare($staffStatement);

        if (!$staffRequest) {
            throw new DBException("Impossible de récupérer le personnel de manutention.");
        }

        $staffRequest->execute(['id' => $id]);

        $staffRaw = $staffRequest->fetch();

        if (!\is_array($staffRaw)) {
            return null;
        }

        $staff = $this->stevedoringService->makeStevedoringStaffFromDatabase($staffRaw);

        return $staff;
    }

    public function createStaff(StevedoringStaff $staff): StevedoringStaff
    {
        $statement =
            "INSERT INTO stevedoring_staff
            SET
                firstname = :firstname,
                lastname = :lastname,
                phone = :phone,
                type = :type,
                temp_work_agency = :tempWorkAgency,
                active = :active,
                comments = :comments";

        $request = $this->mysql->prepare($statement);

        if (!$request) {
            throw new DBException("Impossible de créer le personnel de manutention.");
        }

        try {
            $this->mysql->beginTransaction();

            $request->execute([
                'firstname' => $staff->getFirstname(),
                'lastname' => $staff->getLastname(),
                'phone' => $staff->getPhone(),
                'type' => $staff->getType(),
                'tempWorkAgency' => $staff->getTempWorkAgency(),
                'active' => (int) $staff->isActive(),
                'comments' => $staff->getComments(),
            ]);

            $lastInsertId = (int) $this->mysql->lastInsertId();

            $this->mysql->commit();
        } catch (\PDOException $e) {
            $this->mysql->rollBack();
            throw new DBException("Impossible de créer le personnel de manutention.", previous: $e);
        }

        /** @var StevedoringStaff */
        $createdStaff = $this->fetchStaff($lastInsertId);

        return $createdStaff;
    }

    public function updateStaff(StevedoringStaff $staff): StevedoringStaff
    {
        $statement =
            "UPDATE stevedoring_staff
            SET
                firstname = :firstname,
                lastname = :lastname,
                phone = :phone,
                type = :type,
                temp_work_agency = :tempWorkAgency,
                active = :active,
                comments = :comments
            WHERE
                id = :id";

        $request = $this->mysql->prepare($statement);

        if (!$request) {
            throw new DBException("Impossible de mettre à jour le personnel de manutention.");
        }

        try {
            $request->execute([
                'firstname' => $staff->getFirstname(),
                'lastname' => $staff->getLastname(),
                'phone' => $staff->getPhone(),
                'type' => $staff->getType(),
                'tempWorkAgency' => $staff->getTempWorkAgency(),
                'active' => (int) $staff->isActive(),
                'comments' => $staff->getComments(),
                'id' => $staff->getId(),
            ]);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de mettre à jour le personnel de manutention.", previous: $e);
        }

        return $staff;
    }

    public function deleteStaff(int $id): void
    {
        $statement =
            "UPDATE stevedoring_staff
            SET
                firstname = '',
                lastname = '',
                phone = '',
                active = 0,
                comments = '',
                deleted_at = NOW()
            WHERE
                id = :id";

        $request = $this->mysql->prepare($statement);

        if (!$request) {
            throw new DBException("Impossible de supprimer le personnel de manutention.");
        }

        try {
            $request->execute(['id' => $id]);
        } catch (\PDOException $e) {
            throw new DBException("Impossible de supprimer le personnel de manutention.", previous: $e);
        }
    }
}
