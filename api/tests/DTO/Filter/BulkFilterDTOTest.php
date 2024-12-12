<?php

// Path: api/tests/DTO/Filter/BulkFilterDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\Core\HTTP\HTTPRequestQuery;
use App\DTO\Filter\BulkFilterDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;


#[CoversClass(BulkFilterDTO::class)]
#[UsesClass(HTTPRequestQuery::class)]
final class BulkFilterDTOTest extends TestCase
{
    public function testIsArchive(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?archives";
        $query = new HTTPRequestQuery();
        $dto = new BulkFilterDTO($query);

        // When
        $result = $dto->isArchive();

        // Then
        $this->assertTrue($result);
    }

    public function testIsNotArchive(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new BulkFilterDTO($query);

        // When
        $result = $dto->isArchive();

        // Then
        $this->assertFalse($result);
    }

    public function testIsOnTv(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path?tv";
        $query = new HTTPRequestQuery();
        $dto = new BulkFilterDTO($query);

        // When
        $result = $dto->isOnTv();

        // Then
        $this->assertTrue($result);
    }

    public function testIsNotOnTv(): void
    {
        // Given
        $_SERVER['REQUEST_URI'] = "/path";
        $query = new HTTPRequestQuery();
        $dto = new BulkFilterDTO($query);

        // When
        $result = $dto->isOnTv();

        // Then
        $this->assertFalse($result);
    }
}
