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
    public function testGetRoles(): void
    {
        // Given
        $thirdParty = new ThirdParty();

        // When
        $roles = $thirdParty->roles;

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

    public function testSetAndGetLogoUrlWithFilename(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $logoFilename = 'filename.webp';
        $expected = Environment::getString('LOGOS_URL') . '/' . $logoFilename;

        // When
        $thirdParty->logoFilename = $logoFilename;
        $actual = $thirdParty->logoUrl;

        // Then
        $this->assertSame($expected, $actual);
    }

    public function testSetAndGetLogoUrlWithNull(): void
    {
        // Given
        $thirdParty = new ThirdParty();
        $logoFilename = null;

        // When
        $thirdParty->logoFilename = $logoFilename;
        $actual = $thirdParty->logoUrl;

        // Then
        $this->assertNull($actual);
    }

    public function testToArray(): void
    {
        // Given
        $country = new Country();
        $country->iso = 'FR';

        $thirdParty = new ThirdParty();
        $thirdParty->id = 1;
        $thirdParty->shortName = 'Short Name';
        $thirdParty->fullName = 'Full Name';
        $thirdParty->addressLine1 = 'Address Line 1';
        $thirdParty->addressLine2 = 'Address Line 2';
        $thirdParty->postCode = 'Post Code';
        $thirdParty->city = 'City';
        $thirdParty->country = $country;
        $thirdParty->phone = 'Phone';
        $thirdParty->comments = 'Comments';
        $thirdParty->setRole('bois_affreteur', true);
        $thirdParty->isNonEditable = true;
        $thirdParty->isAgency = true;
        $thirdParty->logoFilename = 'filename.webp';
        $thirdParty->isActive = true;
        $thirdParty->appointmentCount = 5;

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
