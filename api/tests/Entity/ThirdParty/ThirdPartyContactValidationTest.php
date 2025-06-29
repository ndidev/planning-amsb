<?php

// Path: api/tests/Entity/ThirdParty/ThirdPartyContactValidationTest.php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Core\Exceptions\Client\ValidationException;
use App\Entity\ThirdParty\ThirdPartyContact;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ThirdPartyContact::class)]
final class ThirdPartyContactValidationTest extends TestCase
{
    public function testExpectValidationExceptionOnNewContact(): void
    {
        // Given
        $contact = new ThirdPartyContact();

        // Then
        $this->expectException(ValidationException::class);

        // When
        $contact->validate();
    }

    public function testNoExceptionOnValidContact(): void
    {
        // Given
        $contact = $this->makeValidContact();

        // When
        $contact->validate();

        // Then
        $this->expectNotToPerformAssertions();
    }

    public function testExpectValidationExceptionWhenNameIsEmpty(): void
    {
        // Given
        $contact = $this->makeValidContact();
        $contact->name = '';

        // Then
        $this->expectException(ValidationException::class);

        // When
        $contact->validate();
    }

    private function makeValidContact(): ThirdPartyContact
    {
        $contact = new ThirdPartyContact();
        $contact->id = 1;
        $contact->name = 'John Doe';
        $contact->email = 'test@example.com';
        $contact->phone = '1234567890';
        $contact->position = 'Manager';
        $contact->comments = 'Test comment';

        return $contact;
    }
}
