<?php

// Path: api/tests/Entity/Config/AgencyDepartmentTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Config;

use App\Entity\Config\AgencyDepartment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AgencyDepartment::class)]
final class AgencyDepartmentTest extends TestCase
{
    public function testToArray(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $agencyDepartment->service = 'service';
        $agencyDepartment->displayName = 'displayName';
        $agencyDepartment->fullName = 'fullName';
        $agencyDepartment->addressLine1 = 'addressLine1';
        $agencyDepartment->addressLine2 = 'addressLine2';
        $agencyDepartment->postCode = 'postCode';
        $agencyDepartment->city = 'city';
        $agencyDepartment->country = 'country';
        $agencyDepartment->phoneNumber = 'phone';
        $agencyDepartment->mobileNumber = 'mobile';
        $agencyDepartment->emailAddress = 'email';

        $expectedArray = [
            'service' => 'service',
            'affichage' => 'displayName',
            'nom' => 'fullName',
            'adresse_ligne_1' => 'addressLine1',
            'adresse_ligne_2' => 'addressLine2',
            'cp' => 'postCode',
            'ville' => 'city',
            'pays' => 'country',
            'telephone' => 'phone',
            'mobile' => 'mobile',
            'email' => 'email',
        ];

        // When
        $actualArray = $agencyDepartment->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
