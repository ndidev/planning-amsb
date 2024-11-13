<?php

// Path: api/tests/DTO/CurrentUserInfoDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\Core\Auth\UserRoles;
use App\DTO\CurrentUserInfoDTO;
use App\Entity\UserAccount;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurrentUserInfoDTO::class)]
#[UsesClass(UserAccount::class)]
#[UsesClass(UserRoles::class)]
final class CurrentUserInfoDTOTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        // Given
        $userInfo = [
            "uid" => "user-id",
            "login" => "test",
            "nom" => "Test User",
            "roles" => [
                "module1" => UserRoles::ACCESS,
                "module2" => UserRoles::EDIT
            ],
            "statut" => "active",
        ];

        $user = (new UserAccount())
            ->setUid($userInfo['uid'])
            ->setLogin($userInfo['login'])
            ->setName($userInfo['nom'])
            ->setRoles($userInfo['roles'])
            ->setStatus($userInfo['statut']);

        $dto = new CurrentUserInfoDTO($user);

        // When
        $dataToSerialize = $dto->jsonSerialize();

        // Then
        $this->assertEquals($userInfo, $dataToSerialize);
    }
}
