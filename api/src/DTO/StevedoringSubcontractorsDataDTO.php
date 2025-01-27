<?php

// Path: api/src/DTO/StevedoringSubcontractorsDataDTO.php

declare(strict_types=1);

namespace App\DTO;

final class StevedoringSubcontractorsDataDTO implements \JsonSerializable
{
    /**
     * @var array{trucking: string[], other: string[]}
     */
    private array $subcontractorsData = [
        'trucking' => [],
        'other' => []
    ];

    /**
     * @param array{name: string, type: string}[] $rawData 
     */
    public function __construct(private array $rawData)
    {
        $this->organizeData();
    }

    private function organizeData(): void
    {
        foreach ($this->rawData as $subcontractor) {
            if ($subcontractor['type'] === 'trucking') {
                $this->subcontractorsData['trucking'][] = $subcontractor['name'];
            } else {
                $this->subcontractorsData['other'][] = $subcontractor['name'];
            }
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->subcontractorsData;
    }
}
