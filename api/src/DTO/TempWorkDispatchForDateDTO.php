<?php

// Path: api/src/DTO/TempWorkDispatchForDateDTO.php

declare(strict_types=1);

namespace App\DTO;

use App\Core\Array\ArrayHandler;

/**
 * @phpstan-type TempWorkDispatchForDateData array<int, float>
 */
final class TempWorkDispatchForDateDTO implements \JsonSerializable
{
    /** @phpstan-var TempWorkDispatchForDateData */
    private array $dispatchtData = [];

    /**
     * @param array{id: string, hoursWorked: string}[] $rawData
     */
    public function __construct(private array $rawData)
    {
        $this->makeDispatchData();
    }

    private function makeDispatchData(): void
    {
        foreach ($this->rawData as $data) {
            $rawDataAH = new ArrayHandler($data);

            $staffId = $rawDataAH->getInt('id');
            $hoursWorked = $rawDataAH->getFloat('hoursWorked', 0);

            if (!$staffId) {
                continue;
            }

            $this->dispatchtData[$staffId] ??= $hoursWorked;
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->dispatchtData;
    }
}
