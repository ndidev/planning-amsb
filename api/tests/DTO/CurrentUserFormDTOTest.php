<?php

// Path: api/tests/DTO/CurrentUserFormDTOTest.php

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
final class CurrentUserFormDTOTest extends TestCase
{
    public function testSetAndGetUid(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();
        $uid = 'uid';

        // When
        $dto->uid = $uid;
        $result = $dto->uid;

        // Then
        $this->assertSame($uid, $result);
    }

    public function testSetAndGetName(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();
        $name = 'name';

        // When
        $dto->name = $name;
        $result = $dto->name;

        // Then
        $this->assertSame($name, $result);
    }

    public function testExceptionWhenSetNameWithEmptyName(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();
        $dto->name = '';

        // Then
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Le nom est requis.');

        // When
        $dto->validate();
    }

    public function testSetAndGetStringPassword(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();
        $password = 'password';

        // When
        $dto->password = $password;
        $result = $dto->password;

        // Then
        $this->assertSame($password, $result);
    }

    public function testSetAndGetNullPassword(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();

        // When
        $dto->password = null;
        $result = $dto->password;

        // Then
        $this->assertNull($result); // @phpstan-ignore method.alreadyNarrowedType
    }

    public function testGetPasswordHashWithStringPassword(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();
        $password = 'password';

        // When
        $dto->password = $password;
        /** @var string */
        $passwordHash = $dto->passwordHash;

        // Then
        $this->assertTrue(\password_verify($password, $passwordHash));
    }

    public function testGetPasswordHashWithNullPassword(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();

        // When
        $dto->password = null;
        $passwordHash = $dto->passwordHash;

        // Then
        $this->assertNull($passwordHash);
    }

    public function testGetPasswordHashWithEmptyPassword(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();

        // When
        $dto->password = '';
        $passwordHash = $dto->passwordHash;

        // Then
        $this->assertNull($passwordHash);
    }
}
