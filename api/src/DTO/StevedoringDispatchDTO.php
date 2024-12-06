<?php

// Path: api/src/DTO/StevedoringDispatchDTO.php

declare(strict_types=1);

namespace App\DTO;

final class StevedoringDispatchDTO implements \JsonSerializable
{
    /** @var array<mixed> */
    private array $dispatch = [];

    /**
     * @param array{
     *          date: string,
     *          staffName: string,
     *          staffContractType: string,
     *          staffTempWorkAgency: string,
     *          remarks: string,
     *          productName: string,
     *          qualityName: string
     *        }[] $bulkData 
     */
    public function __construct(private array $bulkData)
    {
        $this->bulkData = $bulkData;

        $this->makeDispatch();
    }

    private function makeDispatch(): void
    {
        foreach ($this->bulkData as $data) {
            // @phpstan-ignore-next-line
            $this->dispatch[$data["date"]][$data["staffContractType"]][$data["staffName"]]["tempWorkAgency"] = $data["staffTempWorkAgency"];

            // @phpstan-ignore-next-line
            $this->dispatch[$data["date"]][$data["staffContractType"]][$data["staffName"]]["bulk"][] = [
                "product" => $data["productName"],
                "quality" => $data["qualityName"],
                "remarks" => $data["remarks"],
            ];
        }
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->dispatch;
    }
}
