<?php

// Path: api/tests/Entity/UserAccountTest.php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\UserAccount;
use App\Core\Auth\AccountStatus;
use App\Core\Auth\UserRoles;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserAccount::class)]
final class UserAccountTest extends TestCase
{
    public function testInstanciationWithoutUid(): void
    {
        // Given
        $userAccount = new UserAccount();

        // When
        $actualUid = $userAccount->getUid();

        // Then
        $this->assertNull($actualUid);
    }

    public function testInstanciationWithUid(): void
    {
        // Given
        $uid = 'uid';

        // When
        $userAccount = new UserAccount($uid);
        $actualUid = $userAccount->getUid();

        // Then
        $this->assertSame($uid, $actualUid);
    }

    public function testSetAndGetUid(): void
    {
        // Given
        $userAccount = new UserAccount();
        $uid = 'uid';

        // When
        $userAccount->setUid($uid);
        $actualUid = $userAccount->getUid();

        // Then
        $this->assertSame($uid, $actualUid);
    }

    public function testSetAndGetLogin(): void
    {
        // Given
        $userAccount = new UserAccount();
        $login = 'login';

        // When
        $userAccount->setLogin($login);
        $actualLogin = $userAccount->getLogin();

        // Then
        $this->assertSame($login, $actualLogin);
    }

    public function testSetAndGetPasswordHash(): void
    {
        // Given
        $userAccount = new UserAccount();
        $passwordHash = 'password';

        // When
        $userAccount->setPasswordHash($passwordHash);
        $actualPasswordHash = $userAccount->getPasswordHash();

        // Then
        $this->assertSame($passwordHash, $actualPasswordHash);
    }

    public function testSetAndGetCanLogin(): void
    {
        // Given
        $userAccount = new UserAccount();
        $canLogin = true;

        // When
        $userAccount->setCanLogin($canLogin);
        $actualCanLogin = $userAccount->canLogin();

        // Then
        $this->assertSame($canLogin, $actualCanLogin);
    }

    public function testSetAndGetName(): void
    {
        // Given
        $userAccount = new UserAccount();
        $name = 'name';

        // When
        $userAccount->setName($name);
        $actualName = $userAccount->getName();

        // Then
        $this->assertSame($name, $actualName);
    }

    public function testSetAndGetLoginAttempts(): void
    {
        // Given
        $userAccount = new UserAccount();
        $loginAttempts = 1;

        // When
        $userAccount->setLoginAttempts($loginAttempts);
        $actualLoginAttempts = $userAccount->getLoginAttempts();

        // Then
        $this->assertSame($loginAttempts, $actualLoginAttempts);
    }

    public function testSetAndGetLastLogin(): void
    {
        // Given
        $userAccount = new UserAccount();
        $lastLogin = new \DateTimeImmutable();

        // When
        $userAccount->setLastLogin($lastLogin);
        $actualLastLogin = $userAccount->getLastLogin();

        // Then
        $this->assertEquals($lastLogin, $actualLastLogin);
    }

    public function testSetAndGetStatusWithEnum(): void
    {
        // Given
        $userAccount = new UserAccount();
        $status = AccountStatus::ACTIVE;

        // When
        $userAccount->setStatus($status);
        $actualStatus = $userAccount->getStatus();

        // Then
        $this->assertSame($status, $actualStatus);
    }

    public function testSetAndGetStatusWithString(): void
    {
        // Given
        $userAccount = new UserAccount();
        $status = 'active';

        // When
        $userAccount->setStatus($status);
        $actualStatus = $userAccount->getStatus();

        // Then
        $this->assertSame(AccountStatus::ACTIVE, $actualStatus);
    }

    public function testExpectsExceptionWhenSetStatusWithInvalidString(): void
    {
        // Given
        $userAccount = new UserAccount();
        $status = 'invalid';

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $userAccount->setStatus($status);
    }

    public function testSetAndGetRolesFromString(): void
    {
        // Given
        $userAccount = new UserAccount();
        $roles = '{"role1": 0,"role2": 1}';
        $expectedRolesArray = [
            'role1' => 0,
            'role2' => 1,
        ];

        // When
        $userAccount->setRoles($roles);
        $actualRolesArray = $userAccount->getRoles()->toArray();

        // Then
        $this->assertSame($expectedRolesArray, $actualRolesArray);
    }

    public function testSetAndGetRolesFromArray(): void
    {
        // Given
        $userAccount = new UserAccount();
        $roles = [
            'role1' => 0,
            'role2' => 1,
        ];

        // When
        $userAccount->setRoles($roles);
        $actualRolesArray = $userAccount->getRoles()->toArray();

        // Then
        $this->assertSame($roles, $actualRolesArray);
    }

    public function testGetRole(): void
    {
        // Given
        $userAccount = new UserAccount();
        $role = 'role';
        $value = 1;

        // When
        $userAccount->setRoles([$role => $value]);
        $actualValue = $userAccount->getRole($role);

        // Then
        $this->assertSame($value, $actualValue);
    }

    public function testSetAndGetAdmin(): void
    {
        // Given
        $userAccount = new UserAccount();
        $admin = true;

        // When
        $userAccount->setAdmin($admin);
        $actualAdmin = $userAccount->isAdmin();

        // Then
        $this->assertSame($admin, $actualAdmin);
    }

    public function testSetAndGetComments(): void
    {
        // Given
        $userAccount = new UserAccount();
        $comments = 'comments';

        // When
        $userAccount->setComments($comments);
        $actualComments = $userAccount->getComments();

        // Then
        $this->assertSame($comments, $actualComments);
    }

    public function testSetAndGetHistory(): void
    {
        // Given
        $userAccount = new UserAccount();
        $history = 'history';

        // When
        $userAccount->setHistory($history);
        $actualHistory = $userAccount->getHistory();

        // Then
        $this->assertSame($history, $actualHistory);
    }

    public function testToArray(): void
    {
        // Given
        $userAccount = (new UserAccount())
            ->setUid('uid1')
            ->setLogin('login string')
            ->setPasswordHash('1234567890')
            ->setCanLogin(true)
            ->setName('User name')
            ->setLoginAttempts(4)
            ->setLastLogin(new \DateTimeImmutable('2024-03-05 12:15:16'))
            ->setStatus(AccountStatus::ACTIVE)
            ->setRoles('{"role1": 0,"role2": 1}')
            ->setComments('Comments')
            ->setHistory('History');

        $roles = '{"role1": 0,"role2": 1}';

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
