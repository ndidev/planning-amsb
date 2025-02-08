<?php

// Path: api/src/DTO/StevedoringDispatchDTO.php

declare(strict_types=1);

namespace App\DTO;

final class StevedoringDispatchDTO implements \JsonSerializable
{
    /** @var array<string,
     *         array<'mensuel'|'interim',
     *           array<string,
     *             array{
     *               tempWorkAgency: string|null,
     *               bulk?: array<int,
     *                 array{
     *                   product: string,
     *                   quality: string,
     *                   remarks: string,
     *                   multiplier: int,
     *                 },
     *               >,
     *               timber?: array<int,
     *                 array{
     *                   remarks: string,
     *                   multiplier: int,
     *                 },
     *               >,
     *             }
     *           >
     *         >
     *       >
     */
    private array $dispatch = [];

    private const FEED_PRODUCT_ID = 1;
    private const MISC_PRODUCT_ID = 2;

    /**
     * @param array{
     *          date: string,
     *          staffName: string,
     *          staffContractType: 'mensuel'|'interim',
     *          staffTempWorkAgency: string,
     *          remarks: string,
     *          productId: int,
     *          productName: string,
     *          qualityName: string,
     *        }[] $bulkData 
     * 
     * @param array{
     *          date: string,
     *          staffName: string,
     *          staffContractType: 'mensuel'|'interim',
     *          staffTempWorkAgency: string,
     *          remarks: string,
     *        }[] $timberData 
     */
    public function __construct(
        private array $bulkData,
        private array $timberData,
    ) {
        $this->makeDispatch();
    }

    private function makeDispatch(): void
    {
        // Bulk
        foreach ($this->bulkData as $data) {
            $this->dispatch[$data['date']][$data['staffContractType']][$data['staffName']] ??= ['tempWorkAgency' => $data['staffTempWorkAgency']];

            $currentItem = &$this->dispatch[$data['date']][$data['staffContractType']][$data['staffName']];

            $currentItem['tempWorkAgency'] = $data['staffTempWorkAgency'];

            $currentItem['bulk'] ??= [];

            $newItem = true;

            $isSpecialProduct = $data['productId'] === self::FEED_PRODUCT_ID || $data['productId'] === self::MISC_PRODUCT_ID;

            foreach ($currentItem['bulk'] as &$line) {
                if (
                    $line['remarks'] === $data['remarks']
                    && $line['product'] === $data['productName']
                    && $line['quality'] === $data['qualityName']
                ) {
                    if (!$isSpecialProduct) {
                        $line['multiplier']++;
                    }
                    $newItem = false;
                    break;
                }
            }

            if ($newItem) {
                $currentItem['bulk'][] = [
                    'product' => $data['productName'],
                    'quality' => $data['qualityName'],
                    'remarks' => $data['remarks'],
                    'multiplier' => $isSpecialProduct ? 0 : 1,
                ];
            }
        }

        // Timber
        foreach ($this->timberData as $data) {
            $this->dispatch[$data['date']][$data['staffContractType']][$data['staffName']] ??= ['tempWorkAgency' => $data['staffTempWorkAgency']];

            $currentItem = &$this->dispatch[$data['date']][$data['staffContractType']][$data['staffName']];

            $currentItem['tempWorkAgency'] = $data['staffTempWorkAgency'];

            $currentItem['timber'] ??= [];

            $newItem = true;

            foreach ($currentItem['timber'] as &$line) {
                if ($line['remarks'] === $data['remarks']) {
                    $line['multiplier']++;
                    $newItem = false;
                    break;
                }
            }

            if ($newItem) {
                $currentItem['timber'][] = [
                    'remarks' => $data['remarks'],
                    'multiplier' => 1,
                ];
            }
        }

        foreach ($this->dispatch as $date => &$dispatchByContractType) {
            if (isset($dispatchByContractType['mensuel'])) {
                \ksort($dispatchByContractType['mensuel']);
            }

            if (isset($dispatchByContractType['interim'])) {
                \ksort($dispatchByContractType['interim']);
            }
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
