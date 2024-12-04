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
    public function testSetAndGetFirstname(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $firstname = 'John';

        // When
        $stevedoringStaff->setFirstname($firstname);
        $actualName = $stevedoringStaff->getFirstname();

        // Then
        $this->assertSame($firstname, $actualName);
    }

    public function testSetAndGetLastname(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $lastname = 'Doe';

        // When
        $stevedoringStaff->setLastname($lastname);
        $actualName = $stevedoringStaff->getLastname();

        // Then
        $this->assertSame($lastname, $actualName);
    }

    public function testSetAndGetPhone(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $phone = '0123456789';

        // When
        $stevedoringStaff->setPhone($phone);
        $actualPhone = $stevedoringStaff->getPhone();

        // Then
        $this->assertSame($phone, $actualPhone);
    }

    public function testSetAndGetType(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $type = 'cdi';

        // When
        $stevedoringStaff->setType($type);
        $actualType = $stevedoringStaff->getType();

        // Then
        $this->assertSame($type, $actualType);
    }

    public function testSetAndGetTempWorkAgency(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $tempWorkAgency = 'Some agency';

        // When
        $stevedoringStaff->setTempWorkAgency($tempWorkAgency);
        $actualTempWorkAgency = $stevedoringStaff->getTempWorkAgency();

        // Then
        $this->assertSame($tempWorkAgency, $actualTempWorkAgency);
    }

    public function testSetAndIsActive(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $isActive = false;

        // When
        $stevedoringStaff->setActive($isActive);
        $actualIsActive = $stevedoringStaff->isActive();

        // Then
        $this->assertSame($isActive, $actualIsActive);
    }

    public function testSetAndGetComments(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $comments = 'Some comments';

        // When
        $stevedoringStaff->setComments($comments);
        $actualComments = $stevedoringStaff->getComments();

        // Then
        $this->assertSame($comments, $actualComments);
    }

    public function testSetAndGetDeletedAt(): void
    {
        // Given
        $stevedoringStaff = new StevedoringStaff();
        $deletionDate = new \DateTimeImmutable('2021-01-01 01:23:45');

        // When
        $stevedoringStaff->setDeletedAt($deletionDate);
        $actualDeletionDate = $stevedoringStaff->getDeletedAt();

        // Then
        $this->assertSame($deletionDate, $actualDeletionDate);
    }

    public function testToArray(): void
    {
        // Given
        $deletionDate = new \DateTimeImmutable('2021-01-01 01:23:45');
        $stevedoringStaff =
            (new StevedoringStaff())
            ->setId(1)
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setPhone('0123456789')
            ->setType('interim')
            ->setTempWorkAgency('Some agency')
            ->setActive(true)
            ->setComments('Some comments')
            ->setDeletedAt($deletionDate);

        $expectedArray = [
            'id' => 1,
            'firstname' => 'John',
            'lastname' => 'Doe',
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
}
