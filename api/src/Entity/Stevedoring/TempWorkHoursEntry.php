<?php

// Path: api/src/Entity/Stevedoring/TempWorkHoursEntry.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;

class TempWorkHoursEntry extends AbstractEntity
{
    use IdentifierTrait;

    #[Required('Le personnel intérimaire est obligaoire.')]
    private ?StevedoringStaff $staff = null;

    #[Required('La date est obligatoire.')]
    private ?\DateTimeImmutable $date = null;

    private float $hoursWorked = 0;

    private string $comments = '';

    public function setStaff(?StevedoringStaff $staff): static
    {
        $this->staff = $staff;

        return $this;
    }

    public function getStaff(): ?StevedoringStaff
    {
        return $this->staff;
    }

    public function setDate(\DateTimeImmutable|string|null $date): static
    {
        $this->date = DateUtils::makeDateTimeImmutable($date);

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setHoursWorked(float $hoursWorked): static
    {
        $this->hoursWorked = $hoursWorked;

        return $this;
    }

    public function getHoursWorked(): float
    {
        return $this->hoursWorked;
    }

    public function setComments(string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'staffId' => $this->getStaff()?->getId(),
            'date' => $this->getDate()?->format('Y-m-d'),
            'hoursWorked' => $this->getHoursWorked(),
            'comments' => $this->getComments(),
        ];
    }
}
