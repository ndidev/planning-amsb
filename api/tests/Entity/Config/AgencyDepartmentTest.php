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
    public function testSetAndGetService(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $service = 'service';

        // When
        $agencyDepartment->setService($service);
        $actualService = $agencyDepartment->getService();

        // Then
        $this->assertSame($service, $actualService);
    }

    public function testSetAndGetDisplayName(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $displayName = 'displayName';

        // When
        $agencyDepartment->setDisplayName($displayName);
        $actualDisplayName = $agencyDepartment->getDisplayName();

        // Then
        $this->assertSame($displayName, $actualDisplayName);
    }

    public function testSetAndGetFullName(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $fullName = 'fullName';

        // When
        $agencyDepartment->setFullName($fullName);
        $actualFullName = $agencyDepartment->getFullName();

        // Then
        $this->assertSame($fullName, $actualFullName);
    }

    public function testSetAndGetAddressLine1(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $addressLine1 = 'addressLine1';

        // When
        $agencyDepartment->setAddressLine1($addressLine1);
        $actualAddressLine1 = $agencyDepartment->getAddressLine1();

        // Then
        $this->assertSame($addressLine1, $actualAddressLine1);
    }

    public function testSetAndGetAddressLine2(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $addressLine2 = 'addressLine2';

        // When
        $agencyDepartment->setAddressLine2($addressLine2);
        $actualAddressLine2 = $agencyDepartment->getAddressLine2();

        // Then
        $this->assertSame($addressLine2, $actualAddressLine2);
    }

    public function testSetAndGetPostCode(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $postCode = 'postCode';

        // When
        $agencyDepartment->setPostCode($postCode);
        $actualPostCode = $agencyDepartment->getPostCode();

        // Then
        $this->assertSame($postCode, $actualPostCode);
    }

    public function testSetAndGetCity(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $city = 'city';

        // When
        $agencyDepartment->setCity($city);
        $actualCity = $agencyDepartment->getCity();

        // Then
        $this->assertSame($city, $actualCity);
    }

    public function testSetAndGetCountry(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $country = 'country';

        // When
        $agencyDepartment->setCountry($country);
        $actualCountry = $agencyDepartment->getCountry();

        // Then
        $this->assertSame($country, $actualCountry);
    }

    public function testSetAndGetPhone(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $phone = 'phone';

        // When
        $agencyDepartment->setPhone($phone);
        $actualPhone = $agencyDepartment->getPhone();

        // Then
        $this->assertSame($phone, $actualPhone);
    }

    public function testSetAndGetMobile(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $mobile = 'mobile';

        // When
        $agencyDepartment->setMobile($mobile);
        $actualMobile = $agencyDepartment->getMobile();

        // Then
        $this->assertSame($mobile, $actualMobile);
    }

    public function testSetAndGetEmail(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $email = 'email';

        // When
        $agencyDepartment->setEmail($email);
        $actualEmail = $agencyDepartment->getEmail();

        // Then
        $this->assertSame($email, $actualEmail);
    }

    public function testToArray(): void
    {
        // Given
        $agencyDepartment = new AgencyDepartment();
        $agencyDepartment->setService('service');
        $agencyDepartment->setDisplayName('displayName');
        $agencyDepartment->setFullName('fullName');
        $agencyDepartment->setAddressLine1('addressLine1');
        $agencyDepartment->setAddressLine2('addressLine2');
        $agencyDepartment->setPostCode('postCode');
        $agencyDepartment->setCity('city');
        $agencyDepartment->setCountry('country');
        $agencyDepartment->setPhone('phone');
        $agencyDepartment->setMobile('mobile');
        $agencyDepartment->setEmail('email');

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
