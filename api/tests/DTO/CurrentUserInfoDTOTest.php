<?php

// Path: api/tests/DTO/CurrentUserInfoDTOTest.php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\Core\Auth\UserRoles;
use App\DTO\CurrentUserInfoDTO;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurrentUserInfoDTO::class)]
#[UsesClass(User::class)]
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

        $user = new User();
        $user->uid = $userInfo['uid'];
        $user->login = $userInfo['login'];
        $user->name = $userInfo['nom'];
        $user->roles = $userInfo['roles'];
        $user->status = $userInfo['statut'];

        $dto = new CurrentUserInfoDTO($user);

        // When
        $dataToSerialize = $dto->jsonSerialize();

        // Then
        $this->assertEquals($userInfo, $dataToSerialize);
    }
}
