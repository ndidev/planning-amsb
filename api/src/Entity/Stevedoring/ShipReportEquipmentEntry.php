<?php

// Path: api/src/Entity/Stevedoring/ShipReportEquipmentEntry.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Maximum;
use App\Core\Validation\Constraints\Minimum;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;
use App\Entity\Stevedoring\StevedoringEquipment;

class ShipReportEquipmentEntry extends AbstractEntity
{
    use IdentifierTrait;

    public ?ShipReport $report = null;

    #[Required("L'équipement de manutention est obligatoire.")]
    public ?StevedoringEquipment $equipment = null;

    #[Required("La date est obligatoire.")]
    public ?\DateTimeImmutable $date = null {
        set(\DateTimeImmutable|string|null $date) {
            $this->date = DateUtils::makeDateTimeImmutable($date);
        }
    }

    public string $hoursHint = '';

    #[Minimum(0, message: "Les heures travaillées ne peuvent pas être négatives.")]
    #[Maximum(24, message: "Les heures travaillées ne peuvent pas dépasser 24 heures.")]
    public float $hoursWorked = 0.0;

    public string $comments = '';

    public function setReport(ShipReport $report): static
    {
        $this->report = $report;

        return $this;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'equipmentId' => $this->equipment?->id,
            'date' => $this->date?->format('Y-m-d'),
            'hoursHint' => $this->hoursHint,
            'hoursWorked' => $this->hoursWorked,
            'comments' => $this->comments,
        ];
    }
}
