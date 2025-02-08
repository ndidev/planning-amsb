<?php

// Path: api/tests/Entity/ThirdPartyTest.php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Core\Array\Environment;
use App\Entity\Country;
use App\Entity\ThirdParty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ThirdParty::class)]
final class ThirdPartyTest extends TestCase
{
    public function testSetAndGetShortName(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $shortName = 'Short Name';

        // When
        $thirdParty->setShortName($shortName);
        $actual = $thirdParty->getShortName();

        // Then
        $this->assertSame($shortName, $actual);
    }

    public function testSetAndGetFullName(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $fullName = 'Full Name';

        // When
        $thirdParty->setFullName($fullName);
        $actual = $thirdParty->getFullName();

        // Then
        $this->assertSame($fullName, $actual);
    }

    public function testSetAndGetAddressLine1(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $addressLine1 = 'Address Line 1';

        // When
        $thirdParty->setAddressLine1($addressLine1);
        $actual = $thirdParty->getAddressLine1();

        // Then
        $this->assertSame($addressLine1, $actual);
    }

    public function testSetAndGetAddressLine2(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $addressLine2 = 'Address Line 2';

        // When
        $thirdParty->setAddressLine2($addressLine2);
        $actual = $thirdParty->getAddressLine2();

        // Then
        $this->assertSame($addressLine2, $actual);
    }

    public function testSetAndGetPostCode(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $postCode = 'Post Code';

        // When
        $thirdParty->setPostCode($postCode);
        $actual = $thirdParty->getPostCode();

        // Then
        $this->assertSame($postCode, $actual);
    }

    public function testSetAndGetCity(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $city = 'City';

        // When
        $thirdParty->setCity($city);
        $actual = $thirdParty->getCity();

        // Then
        $this->assertSame($city, $actual);
    }

    public function testSetAndGetCountry(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $country = new Country();

        // When
        $thirdParty->setCountry($country);
        $actual = $thirdParty->getCountry();

        // Then
        $this->assertSame($country, $actual);
    }

    public function testSetAndGetPhone(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $phone = 'Phone';

        // When
        $thirdParty->setPhone($phone);
        $actual = $thirdParty->getPhone();

        // Then
        $this->assertSame($phone, $actual);
    }

    public function testSetAndGetComments(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $comments = 'Comments';

        // When
        $thirdParty->setComments($comments);
        $actual = $thirdParty->getComments();

        // Then
        $this->assertSame($comments, $actual);
    }

    public function testGetRoles(): void
    {
        // Given
        $thirdParty = new ThirdParty();

        // When
        $roles = $thirdParty->getRoles();

        // Then
        $this->assertArrayHasKey('bois_fournisseur', $roles, 'The role "bois_affreteur" is missing.');
        $this->assertArrayHasKey('bois_client', $roles, 'The role "bois_client" is missing.');
        $this->assertArrayHasKey('bois_transporteur', $roles, 'The role "bois_transporteur" is missing.');
        $this->assertArrayHasKey('bois_affreteur', $roles, 'The role "bois_affreteur" is missing.');
        $this->assertArrayHasKey('vrac_fournisseur', $roles, 'The role "vrac_fournisseur" is missing.');
        $this->assertArrayHasKey('vrac_client', $roles, 'The role "vrac_client" is missing.');
        $this->assertArrayHasKey('vrac_transporteur', $roles, 'The role "vrac_transporteur" is missing.');
        $this->assertArrayHasKey('maritime_armateur', $roles, 'The role "maritime_armateur" is missing.');
        $this->assertArrayHasKey('maritime_affreteur', $roles, 'The role "maritime_affreteur" is missing.');
        $this->assertArrayHasKey('maritime_courtier', $roles, 'The role "maritime_courtier" is missing.');
    }

    public function testSetAndGetRole(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $roleName = 'bois_affreteur';
        $roleValue = true;

        // When
        $thirdParty->setRole($roleName, $roleValue);
        $actualRoleValue = $thirdParty->getRole($roleName);

        // Then
        $this->assertSame($roleValue, $actualRoleValue);
    }

    public function testSetAndGetNonEditable(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $nonEditable = true;

        // When
        $thirdParty->setNonEditable($nonEditable);
        $actual = $thirdParty->isNonEditable();

        // Then
        $this->assertSame($nonEditable, $actual);
    }

    public function testSetAndGetIsAgency(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $isAgency = true;

        // When
        $thirdParty->setIsAgency($isAgency);
        $actual = $thirdParty->isAgency();

        // Then
        $this->assertSame($isAgency, $actual);
    }

    public function testSetAndGetLogoFilename(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $logoFilename = 'filename.webp';

        // When
        $thirdParty->setLogo($logoFilename);
        $actual = $thirdParty->getLogoFilename();

        // Then
        $this->assertSame($logoFilename, $actual);
    }

    public function testSetAndGetLogoFalse(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $logoFilename = false;

        // When
        $thirdParty->setLogo($logoFilename);
        $actual = $thirdParty->getLogoFilename();

        // Then
        $this->assertSame($logoFilename, $actual);
    }

    public function testSetAndGetLogoNull(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $logoFilename = null;

        // When
        $thirdParty->setLogo($logoFilename);
        $actual = $thirdParty->getLogoFilename();

        // Then
        $this->assertSame($logoFilename, $actual);
    }

    public function testSetAndGetLogoUrlWithFilename(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $logoFilename = 'filename.webp';
        $expected = Environment::getString('LOGOS_URL') . '/' . $logoFilename;

        // When
        $thirdParty->setLogo($logoFilename);
        $actual = $thirdParty->getLogoURL();

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetLogoUrlWithNull(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $logoFilename = null;

        // When
        $thirdParty->setLogo($logoFilename);
        $actual = $thirdParty->getLogoURL();

        // Then
        $this->assertNull($actual);
    }

    public function testSetAndGetActive(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $active = true;

        // When
        $thirdParty->setActive($active);
        $actual = $thirdParty->isActive();

        // Then
        $this->assertSame($active, $actual);
    }

    public function testSetAndGetAppointmentCount(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $appointmentCount = 5;

        // When
        $thirdParty->setAppointmentCount($appointmentCount);
        $actual = $thirdParty->getAppointmentCount();

        // Then
        $this->assertSame($appointmentCount, $actual);
    }

    public function testToArray(): void
    {
        // Given
        $thirdParty =
            (new ThirdParty())
            ->setId(1)
            ->setShortName('Short Name')
            ->setFullName('Full Name')
            ->setAddressLine1('Address Line 1')
            ->setAddressLine2('Address Line 2')
            ->setPostCode('Post Code')
            ->setCity('City')
            ->setCountry((new Country())->setISO('FR'))
            ->setPhone('Phone')
            ->setComments('Comments')
            ->setRole('bois_affreteur', true)
            ->setNonEditable(true)
            ->setIsAgency(true)
            ->setLogo('filename.webp')
            ->setActive(true)
            ->setAppointmentCount(5);

        $expectedArray = [
            'id' => 1,
            'nom_court' => 'Short Name',
            'nom_complet' => 'Full Name',
            'adresse_ligne_1' => 'Address Line 1',
            'adresse_ligne_2' => 'Address Line 2',
            'cp' => 'Post Code',
            'ville' => 'City',
            'pays' => 'FR',
            'telephone' => 'Phone',
            'commentaire' => 'Comments',
            'roles' => [
                'bois_fournisseur' => false,
                'bois_client' => false,
                'bois_transporteur' => false,
                'bois_affreteur' => true,
                'vrac_fournisseur' => false,
                'vrac_client' => false,
                'vrac_transporteur' => false,
                'maritime_armateur' => false,
                'maritime_affreteur' => false,
                'maritime_courtier' => false,
            ],
            'non_modifiable' => true,
            'lie_agence' => true,
            'logo' => Environment::getString('LOGOS_URL') . '/filename.webp',
            'actif' => true,
            'nombre_rdv' => 5,
        ];

        // When
        $actual = $thirdParty->toArray();

        // Then
        $this->assertSame($expectedArray, $actual);
    }
}
