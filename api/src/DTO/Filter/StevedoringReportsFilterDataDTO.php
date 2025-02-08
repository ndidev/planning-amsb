<?php

// Path: api/src/DTO/Filter/StevedoringReportsFilterDataDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

/**
 * Filter data for stevedoring reports.
 */
final readonly class StevedoringReportsFilterDataDTO extends FilterData
{
    /**
     * @param string[] $ships 
     * @param string[] $ports 
     * @param string[] $berths 
     * @param string[] $cargoes 
     * @param string[] $customers 
     * @param string[] $storageNames 
     */
    public function __construct(
        protected array $ships,
        protected array $ports,
        protected array $berths,
        protected array $cargoes,
        protected array $customers,
        protected array $storageNames,
    ) {}
}
