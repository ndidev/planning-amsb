<?php

// Path: api/DTO/IgnoredCallDTO.php

declare(strict_types=1);

namespace App\DTO;

/**
 * @phpstan-type IgnoredCall array{
 *                             id: int,
 *                             shipName: string,
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
     * Constructor.
     *
     * @phpstan-param IgnoredCall $rawData
     */
    public function __construct(array $rawData)
    {
        $this->id = $rawData['id'];
        $this->shipName = $rawData['shipName'];
    }
}
