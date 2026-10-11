<?php

// Path: api/tests/Entity/UserValidationTest.php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Core\Exceptions\Client\ValidationException;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
#[UsesClass(ValidationException::class)]
final class UserValidationTest extends TestCase
{
    public function testExpectValidationExceptionOnNewUser(): void
    {
        // Given
        $contact = new User();

        // Then
        $this->expectException(ValidationException::class);

        // When
        $contact->validate();
    }

    public function testNoExceptionOnValidUser(): void
    {
        // Given
        $contact = $this->makeValidUser();

        // When
        $contact->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    public function testExpectValidationExceptionOnUserWithEmptyEmail(): void
    {
        // Given
        $contact = $this->makeValidUser();
        $contact->email = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $contact->validate();
    }

    public function testExpectValidationExceptionOnUserWithInvalidEmail(): void
    {
        // Given
        $contact = $this->makeValidUser();
        $contact->email = 'invalid-email';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $contact->validate();
    }

    public function testExpectValidationExceptionOnUserWithoutLogin(): void
    {
        // Given
        $contact = $this->makeValidUser();
        $contact->login = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $contact->validate();
    }

    public function testExpectValidationExceptionOnUserWithoutName(): void
    {
        // Given
        $contact = $this->makeValidUser();
        $contact->name = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $contact->validate();
    }

    private function makeValidUser(): User
    {
        $user = new User();
        $user->login = 'john.doe';
        $user->name = 'John Doe';
        $user->email = 'john.doe@gmail.com';
        $user->canLogin = true;
        return $user;
    }
}
