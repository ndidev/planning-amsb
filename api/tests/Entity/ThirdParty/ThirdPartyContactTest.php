<?php

// Path: api/tests/Entity/ThirdParty/ThirdPartyContactTest.php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ThirdParty\ThirdPartyContact;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ThirdPartyContact::class)]
final class ThirdPartyContactTest extends TestCase
{
    public function testToArray(): void
    {
        // Given
        $contact = new ThirdPartyContact();
        $contact->id = 1;
        $contact->name = 'John Doe';
        $contact->email = 'test@example.com';
        $contact->phone = '1234567890';
        $contact->position = 'Manager';
        $contact->comments = 'Test comment';

        $expected = [
            'id' => 1,
            'nom' => 'John Doe',
            'telephone' => '1234567890',
            'email' => 'test@example.com',
            'role' => 'Manager',
            'commentaire' => 'Test comment',
        ];

        // When
        $result = $contact->toArray();

        // Then
        $this->assertSame($expected, $result);
    }
}
