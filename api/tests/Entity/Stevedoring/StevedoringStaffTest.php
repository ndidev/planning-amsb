<?php

// Path: api/tests/Entity/Stevedoring/StevedoringStaffTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Stevedoring;

use App\Entity\Stevedoring\StevedoringStaff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StevedoringStaff::class)]
final class StevedoringStaffTest extends TestCase
{
    public function testGetFullname(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $stevedoringStaff->firstname = 'John';
        $stevedoringStaff->lastname = 'Doe';

        // When
        $fullname = $stevedoringStaff->fullname;

        // Then
        $this->assertSame('John Doe', $fullname);
    }

    public function testGetEmptyFullname(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $stevedoringStaff->firstname = '';
        $stevedoringStaff->lastname = '';
        $expected = '(Personnel supprimé)';

        // When
        $fullname = $stevedoringStaff->fullname;

        // Then
        $this->assertSame($expected, $fullname);
    }

    public function testToArray(): void
    {
        // Given
        $deletionDate = new \DateTimeImmutable('2021-01-01 01:23:45');
        $stevedoringStaff = new StevedoringStaff();
        $stevedoringStaff->id = 1;
        $stevedoringStaff->firstname = 'John';
        $stevedoringStaff->lastname = 'Doe';
        $stevedoringStaff->phone = '0123456789';
        $stevedoringStaff->type = 'interim';
        $stevedoringStaff->tempWorkAgency = 'Some agency';
        $stevedoringStaff->isActive = true;
        $stevedoringStaff->comments = 'Some comments';
        $stevedoringStaff->deletedAt = $deletionDate;

        $expectedArray = [
            'id' => 1,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'fullname' => 'John Doe',
            'phone' => '0123456789',
            'type' => 'interim',
            'tempWorkAgency' => 'Some agency',
            'isActive' => true,
            'comments' => 'Some comments',
            'deletedAt' => '2021-01-01 01:23:45',
        ];

        // When
        $actualArray = $stevedoringStaff->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }

    public function testStringable(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $stevedoringStaff->firstname = 'John';
        $stevedoringStaff->lastname = 'Doe';

        $expectedString = 'John Doe';

        // When
        $actualString = (string) $stevedoringStaff;

        // Then
        $this->assertSame($expectedString, $actualString);
    }
}
