<?php

// Path: api/src/DTO/Filter/BulkFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final class BulkFilterDTO
{
    private bool $isArchive;

    public function __construct(HTTPRequestQuery $query)
    {
        $this->isArchive = $query->isSet('archives');
    }

    public function isArchive(): bool
    {
        return $this->isArchive;
    }
}
