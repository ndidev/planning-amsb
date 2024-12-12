<?php

// Path: api/src/DTO/Filter/BulkFilterDTO.php

declare(strict_types=1);

namespace App\DTO\Filter;

use App\Core\HTTP\HTTPRequestQuery;

final class BulkFilterDTO
{
    private bool $isArchive;
    private bool $isOnTv;

    public function __construct(HTTPRequestQuery $query)
    {
        $this->isArchive = $query->isSet('archives');
        $this->isOnTv = $query->isSet('tv');
    }

    public function isArchive(): bool
    {
        return $this->isArchive;
    }

    public function isOnTv(): bool
    {
        return $this->isOnTv;
    }

    public function getSqlTvFilter(): string
    {
        return $this->isOnTv ? " AND `show_on_tv` = 1" : "";
    }
}
