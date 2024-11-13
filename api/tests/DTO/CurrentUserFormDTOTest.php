<?php

// Path: api/tests/DTO/CurrentUserFormDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\CurrentUserFormDTO;
use App\Core\Exceptions\Client\BadRequestException;
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
        $dto->setUid($uid);
        $result = $dto->getUid();

        // Then
        $this->assertSame($uid, $result);
    }

    public function testSetAndGetName(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();
        $name = 'name';

        // When
        $dto->setName($name);
        $result = $dto->getName();

        // Then
        $this->assertSame($name, $result);
    }

    public function testExceptionWhenSetNameWithEmptyName(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();

        // Then
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Le nom est requis.');

        // When
        $dto->setName('');
    }

    public function testSetAndGetStringPassword(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();
        $password = 'password';

        // When
        $dto->setPassword($password);
        $result = $dto->getPassword();

        // Then
        $this->assertSame($password, $result);
    }

    public function testSetAndGetNullPassword(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();

        // When
        $dto->setPassword(null);
        $result = $dto->getPassword();

        // Then
        $this->assertNull($result);
    }

    public function testGetPasswordHashWithStringPassword(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();
        $password = 'password';

        // When
        $dto->setPassword($password);
        /** @var string */
        $passwordHash = $dto->getPasswordHash();

        // Then
        $this->assertTrue(password_verify($password, $passwordHash));
    }

    public function testGetPasswordHashWithNullPassword(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();

        // When
        $dto->setPassword(null);
        $passwordHash = $dto->getPasswordHash();

        // Then
        $this->assertNull($passwordHash);
    }

    public function testGetPasswordHashWithEmptyPassword(): void
    {
        // Given
        $dto = new CurrentUserFormDTO();

        // When
        $dto->setPassword('');
        $passwordHash = $dto->getPasswordHash();

        // Then
        $this->assertNull($passwordHash);
    }
}
