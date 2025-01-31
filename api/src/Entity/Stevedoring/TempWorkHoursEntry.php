<?php

// Path: api/src/Entity/Stevedoring/TempWorkHoursEntry.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Maximum;
use App\Core\Validation\Constraints\Minimum;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;

class TempWorkHoursEntry extends AbstractEntity
{
    use IdentifierTrait;

    #[Required('Le personnel intérimaire est obligatoire.')]
    public ?StevedoringStaff $staff = null;

    #[Required('La date est obligatoire.')]
    public ?\DateTimeImmutable $date = null {
        set(\DateTimeImmutable|string|null $date) {
            $this->date = DateUtils::makeDateTimeImmutable($date);
        }
    }

    #[Minimum(0, message: "Les heures travaillées ne peuvent pas être négatives.")]
    #[Maximum(24, message: "Les heures travaillées ne peuvent pas dépasser 24 heures.")]
    public float $hoursWorked = 0;

    public string $comments = '';

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'staffId' => $this->staff?->id,
            'date' => $this->date?->format('Y-m-d'),
            'hoursWorked' => $this->hoursWorked,
            'comments' => $this->comments,
        ];
    }
}
