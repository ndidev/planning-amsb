<?php

// Path: api/src/DTO/BulkProductAppointmentCountDTO.php

declare(strict_types=1);

namespace App\DTO;

/**
 * @phpstan-type BulkProductAppointmentCountArray = array{
 *                                                    'id': 'total'|int,
 *                                                    'count': int
 *                                                  }
 */
final class BulkProductAppointmentCountDTO implements \JsonSerializable
{
    /**
     * The number of appointments for the bulk product.
     * 
     * @var array<'total'|int, int>
     */
    public array $appointmentCount;

    public int $total {
        get => $this->appointmentCount['total'] ?? 0;
    }

    /**
     * Constructor.
     *
     * @param BulkProductAppointmentCountArray[] $rawData
     */
    public function __construct(array $rawData)
    {
        $this->appointmentCount = [];

        foreach ($rawData as ['id' => $id, 'count' => $count]) {
            if ($id) {
                $this->appointmentCount[$id] = $count;
            }
        }
    }

    public function forQuality(int $id): int
    {
        return $this->appointmentCount[$id] ?? 0;
    }

    public function jsonSerialize(): mixed
    {
        return $this->appointmentCount;
    }
}
