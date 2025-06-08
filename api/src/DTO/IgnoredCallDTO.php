<?php

// Path: api/DTO/IgnoredCallDTO.php

declare(strict_types=1);

namespace App\DTO;

/**
 * @phpstan-type IgnoredCall array{
 *                             id: int,
 *                             shipName: string,
 *                             startDate?: string,
 *                             endDate?: string,
 *                           }
 */
final readonly class IgnoredCallDTO
{
    /**
     * Call ID.
     */
    public int $id;

    /**
     * Ship name.
     */
    public string $shipName;

    /**
     * Start date of the call.
     */
    public ?string $startDate;

    /**
     * End date of the call.
     */
    public ?string $endDate;

    /**
     * Constructor.
     *
     * @phpstan-param IgnoredCall $rawData
     */
    public function __construct(array $rawData)
    {
        $this->id = $rawData['id'];
        $this->shipName = $rawData['shipName'];
        $this->startDate = $rawData['startDate'] ?? null;
        $this->endDate = $rawData['endDate'] ?? null;
    }
}
