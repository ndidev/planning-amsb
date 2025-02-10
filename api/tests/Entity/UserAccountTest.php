<?php

// Path: api/tests/Entity/UserAccountTest.php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Core\Auth\AccountStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
final class UserAccountTest extends TestCase
{
    public function testInstanciationWithoutUid(): void
    {
        // Given
        $userAccount = new User();

        // When
        $actualUid = $userAccount->uid;

        // Then
        $this->assertNull($actualUid);
    }

    public function testInstanciationWithUid(): void
    {
        // Given
        $uid = 'uid';

        // When
        $userAccount = new User($uid);
        $actualUid = $userAccount->uid;

        // Then
        $this->assertSame($uid, $actualUid);
    }

    public function testSetAndGetUid(): void
    {
        // Given
        $userAccount = new User();
        $uid = 'uid';

        // When
        $userAccount->uid = $uid;
        $actualUid = $userAccount->uid;

        // Then
        $this->assertSame($uid, $actualUid);
    }

    public function testSetAndGetLogin(): void
    {
        // Given
        $userAccount = new User();
        $login = 'login';

        // When
        $userAccount->login = $login;
        $actualLogin = $userAccount->login;

        // Then
        $this->assertSame($login, $actualLogin);
    }

    public function testSetAndGetPasswordHash(): void
    {
        // Given
        $userAccount = new User();
        $passwordHash = 'password';

        // When
        $userAccount->passwordHash = $passwordHash;
        $actualPasswordHash = $userAccount->passwordHash;

        // Then
        $this->assertSame($passwordHash, $actualPasswordHash);
    }

    public function testSetAndGetCanLogin(): void
    {
        // Given
        $userAccount = new User();
        $canLogin = true;

        // When
        $userAccount->canLogin = $canLogin;
        $actualCanLogin = $userAccount->canLogin;

        // Then
        $this->assertSame($canLogin, $actualCanLogin); // @phpstan-ignore method.alreadyNarrowedType
    }

    public function testSetAndGetName(): void
    {
        // Given
        $userAccount = new User();
        $name = 'name';

        // When
        $userAccount->name = $name;
        $actualName = $userAccount->name;

        // Then
        $this->assertSame($name, $actualName);
    }

    public function testSetAndGetLoginAttempts(): void
    {
        // Given
        $userAccount = new User();
        $loginAttempts = 1;

        // When
        $userAccount->loginAttempts = $loginAttempts;
        $actualLoginAttempts = $userAccount->loginAttempts;

        // Then
        $this->assertSame($loginAttempts, $actualLoginAttempts);
    }

    public function testSetAndGetLastLogin(): void
    {
        // Given
        $userAccount = new User();
        $lastLogin = new \DateTimeImmutable();

        // When
        $userAccount->lastLogin = $lastLogin;
        $actualLastLogin = $userAccount->lastLogin;

        // Then
        $this->assertEquals($lastLogin, $actualLastLogin);
    }

    public function testSetAndGetStatusWithEnum(): void
    {
        // Given
        $userAccount = new User();
        $status = AccountStatus::ACTIVE;

        // When
        $userAccount->status = $status;
        $actualStatus = $userAccount->status;

        // Then
        $this->assertSame($status, $actualStatus);
    }

    public function testSetAndGetStatusWithString(): void
    {
        // Given
        $userAccount = new User();
        $status = 'active';

        // When
        $userAccount->status = $status;
        $actualStatus = $userAccount->status;

        // Then
        $this->assertSame(AccountStatus::ACTIVE, $actualStatus);
    }

    public function testExpectsExceptionWhenSetStatusWithInvalidString(): void
    {
        // Given
        $userAccount = new User();
        $status = 'invalid';

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $userAccount->status = $status;
    }

    public function testSetAndGetRolesFromString(): void
    {
        // Given
        $userAccount = new User();
        $roles = '{"role1": 0,"role2": 1}';
        $expectedRolesArray = [
            'role1' => 0,
            'role2' => 1,
        ];

        // When
        $userAccount->roles = $roles;
        $actualRolesArray = $userAccount->roles->toArray();

        // Then
        $this->assertSame($expectedRolesArray, $actualRolesArray);
    }

    public function testSetAndGetRolesFromArray(): void
    {
        // Given
        $userAccount = new User();
        $roles = [
            'role1' => 0,
            'role2' => 1,
        ];

        // When
        $userAccount->roles = $roles;
        $actualRolesArray = $userAccount->roles->toArray();

        // Then
        $this->assertSame($roles, $actualRolesArray);
    }

    public function testGetRole(): void
    {
        // Given
        $userAccount = new User();
        $role = 'role';
        $value = 1;

        // When
        $userAccount->roles->$role = $value;
        $actualValue = $userAccount->roles->$role;

        // Then
        $this->assertSame($value, $actualValue);
    }

    public function testSetAndGetAdmin(): void
    {
        // Given
        $userAccount = new User();
        $admin = true;

        // When
        $userAccount->roles->admin = (int) $admin;
        $actualAdmin = $userAccount->isAdmin;

        // Then
        $this->assertSame($admin, $actualAdmin);
    }

    public function testSetAndGetComments(): void
    {
        // Given
        $userAccount = new User();
        $comments = 'comments';

        // When
        $userAccount->comments = $comments;
        $actualComments = $userAccount->comments;

        // Then
        $this->assertSame($comments, $actualComments);
    }

    public function testSetAndGetHistory(): void
    {
        // Given
        $userAccount = new User();
        $history = 'history';

        // When
        $userAccount->history = $history;
        $actualHistory = $userAccount->history;

        // Then
        $this->assertSame($history, $actualHistory);
    }

    public function testToArray(): void
    {
        // Given
        $userAccount = new User();
        $userAccount->uid = 'uid1';
        $userAccount->login = 'login string';
        $userAccount->passwordHash = '1234567890';
        $userAccount->canLogin = true;
        $userAccount->name = 'User name';
        $userAccount->loginAttempts = 4;
        $userAccount->lastLogin = new \DateTimeImmutable('2024-03-05 12:15:16');
        $userAccount->status = AccountStatus::ACTIVE;
        $userAccount->roles = '{"role1": 0,"role2": 1}';
        $userAccount->comments = 'Comments';
        $userAccount->history = 'History';

        $expectedArray = [
            'uid' => 'uid1',
            'login' => 'login string',
            'nom' => 'User name',
            'roles' => [
                'role1' => 0,
                'role2' => 1,
            ],
            'statut' => AccountStatus::ACTIVE,
            'commentaire' => 'Comments',
            'historique' => 'History',
            'last_connection' => '2024-03-05 12:15:16',
        ];

        // When
        $actualArray = $userAccount->toArray();

        // Then
        $this->assertSame($expectedArray, $actualArray);
    }
}
