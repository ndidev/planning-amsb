<?php

// Path: api/src/DTO/BulkDispatchStatsDTO.php

declare(strict_types=1);

namespace App\DTO;

use App\Core\Component\DateUtils;

final class BulkDispatchStatsDTO implements \JsonSerializable
{
    /** @var array<mixed> */
    private array $stats = [
        'formattedDates' => [],
        'staffLabels' => [],
        'byType' => [
            self::JCB => [],
            self::FUNNEL => [],
            self::LOADER => [],
        ],
    ];

    private ?\Transliterator $transliterator;

    private const JCB = "jcb";
    private const FUNNEL = "trémie";
    private const LOADER = "chargeuse";

    /**
     * @param array{
     *          date: string,
     *          unit: string,
     *          staffLabel: string,
     *          remarks: string,
     *        }[] $rawData 
     */
    public function __construct(private array $rawData)
    {
        $this->rawData = $rawData;

        $this->transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');

        $this->makeStats();
    }

    private function makeStats(): void
    {
        $allStaffLabels =
            \array_unique(
                \array_map(fn($data) => $data['staffLabel'], $this->rawData)
            );

        // Place "Intérimaires" at the end of the array
        $interimairesIndex = \array_search('Intérimaires', $allStaffLabels);
        if ($interimairesIndex !== false) {
            unset($allStaffLabels[$interimairesIndex]);
            $allStaffLabels[] = 'Intérimaires';
        }

        $this->stats['staffLabels'] = \array_values($allStaffLabels);

        $allFormattedDates =
            \array_values(
                \array_unique(
                    \array_map(
                        fn($data) => DateUtils::format('MMMM yyyy', $data['date']),
                        $this->rawData
                    )
                )
            );

        $this->stats['formattedDates'] = $allFormattedDates;

        foreach ($this->rawData as $data) {
            $formattedDate = DateUtils::format('MMMM yyyy', $data['date']);

            $type = $this->guessType($data['unit'], $data['remarks']);

            if (!$type) continue;

            $staffLabel = $data['staffLabel'];

            // @phpstan-ignore-next-line
            $this->stats['byType'][$type][$staffLabel] ??= ['total' => 0];
            // @phpstan-ignore-next-line
            $this->stats['byType'][$type][$staffLabel][$formattedDate] ??= 0;
            // @phpstan-ignore-next-line
            $this->stats['byType'][$type][$staffLabel][$formattedDate]++;
            // @phpstan-ignore-next-line
            $this->stats['byType'][$type][$staffLabel]['total']++;
        }
    }

    /**
     * @phpstan-return self::*|null
     */
    private function guessType(string $unit, string $remarks): ?string
    {
        $hintsForJcb = [
            'jcb',
            'telesco',
            'engin',
        ];

        $hintsForFunnel = [
            'tremi',
            'craq',
            'crac',
            'citern',
        ];

        $hintsForLoader = [
            'charge',
            'volvo',
            'cater',
        ];

        $remarksToLowerCase = $this->transliterator?->transliterate($remarks);

        if (!\is_string($remarksToLowerCase)) {
            $remarksToLowerCase = \strtolower($remarks);
        }

        $remarksContainHintForJcb = false;
        foreach ($hintsForJcb as $hint) {
            if (\str_contains($remarksToLowerCase, $hint)) {
                $remarksContainHintForJcb = true;
                break;
            }
        }

        $remarksContainHintForFunnel = false;
        foreach ($hintsForFunnel as $hint) {
            if (\str_contains($remarksToLowerCase, $hint)) {
                $remarksContainHintForFunnel = true;
                break;
            }
        }

        $remarksContainHintForLoader = false;
        foreach ($hintsForLoader as $hint) {
            if (\str_contains($remarksToLowerCase, $hint)) {
                $remarksContainHintForLoader = true;
                break;
            }
        }

        if ($unit === 'BB' && $remarksContainHintForJcb) {
            return self::JCB;
        }

        if ($unit === 'BB' && $remarksContainHintForFunnel) {
            return self::FUNNEL;
        }

        if ($unit === 'T' && $remarksContainHintForLoader) {
            return self::LOADER;
        }

        return null;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->stats;
    }

    public function __serialize(): array
    {
        return $this->stats;
    }
}
