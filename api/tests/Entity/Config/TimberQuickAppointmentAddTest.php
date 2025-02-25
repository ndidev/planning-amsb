<?php

// Path: api/tests/Entity/Config/TimberQuickAppointmentAddTest.php

declare(strict_types=1);

namespace App\Tests\Entity\Config;

use App\Core\Component\Module;
use App\Entity\Config\TimberQuickAppointmentAdd;
use App\Entity\ThirdParty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimberQuickAppointmentAdd::class)]
#[UsesClass(ThirdParty::class)]
#[UsesClass(Module::class)]
final class TimberQuickAppointmentAddTest extends TestCase
{
    public function testToArray(): void
    {
        // Given
        $timberQuickAppointmentAdd = new TimberQuickAppointmentAdd();
        $timberQuickAppointmentAdd->id = 1;
        $timberQuickAppointmentAdd->supplier = new ThirdParty()->setId(10);
        $timberQuickAppointmentAdd->carrier = new ThirdParty()->setId(20);
        $timberQuickAppointmentAdd->charterer = new ThirdParty()->setId(30);
        $timberQuickAppointmentAdd->loading = new ThirdParty()->setId(40);
        $timberQuickAppointmentAdd->customer = new ThirdParty()->setId(50);
        $timberQuickAppointmentAdd->delivery = new ThirdParty()->setId(60);

        $expectedArray = [
            'id' => 1,
            'module' => Module::TIMBER,
            'fournisseur' => 10,
            'transporteur' => 20,
            'affreteur' => 30,
            'chargement' => 40,
            'client' => 50,
            'livraison' => 60,
        ];

        // When
        $result = $timberQuickAppointmentAdd->toArray();

        // Then
        $this->assertSame($expectedArray, $result);
    }
}
