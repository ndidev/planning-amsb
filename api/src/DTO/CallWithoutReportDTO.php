<?php

// Path: api/DTO/CallWithoutReportDTO.php

declare(strict_types=1);

namespace App\DTO;

/**
 * @phpstan-type CallWithoutReport array{
 *                                   id: int,
 *                                   shipName: string,
 *                                 }
 */
final readonly class CallWithoutReportDTO
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
     * Constructor.
     *
     * @phpstan-param CallWithoutReport $rawData
     */
    public function __construct(array $rawData)
    {
        $this->id = $rawData['id'];
        $this->shipName = $rawData['shipName'];
    }
}
