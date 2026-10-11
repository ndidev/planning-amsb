<?php

// Path: api/tests/DTO/CurrentUserFormDTOValidationTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\CurrentUserFormDTO;
use App\Core\Exceptions\Client\BadRequestException;
use App\Core\Exceptions\Client\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurrentUserFormDTO::class)]
#[UsesClass(BadRequestException::class)]
#[UsesClass(ValidationException::class)]
final class CurrentUserFormDTOValidationTest extends TestCase
{
    public function testExpectValidationExceptionOnNewDTO(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();

        // Then
        $this->expectException(ValidationException::class);

        // When
        $dto->validate();
    }

    public function testExpectNoValidationExceptionWithValidData(): void
    {
        // Given
        $dto = $this->makeValidCurrentUserFormDTO();

        // When
        $dto->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    public function testExceptionWhenSetEmailWithEmptyEmail(): void
    {
        // Given
        $dto = $this->makeValidCurrentUserFormDTO();
        $dto->email = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $dto->validate();
    }

    public function testExceptionWhenSetEmailWithInvalidEmail(): void
    {
        // Given
        $dto = $this->makeValidCurrentUserFormDTO();
        $dto->email = 'invalid-email';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $dto->validate();
    }

    public function testExceptionWhenSetNameWithEmptyName(): void
    {
        // Given
        $dto = $this->makeValidCurrentUserFormDTO();
        $dto->name = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $dto->validate();
    }

    private function makeValidCurrentUserFormDTO(): CurrentUserFormDTO
    {
        $dto = new CurrentUserFormDTO();
        $dto->email = 'valid_email@gmail.com';
        $dto->name = 'Valid Name';

        return $dto;
    }
}
