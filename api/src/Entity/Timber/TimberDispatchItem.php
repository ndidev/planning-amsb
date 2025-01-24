<?php

// Path: api/src/Entity/Timber/TimberDispatch.php

namespace App\Entity\Timber;

use App\Core\Component\DateUtils;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Entity\Timber\TimberAppointment;

class TimberDispatchItem extends AbstractEntity
{
    private ?TimberAppointment $appointment = null;

    #[Required("Le personnel est obligatoire.")]
    private ?StevedoringStaff $staff = null;

    #[Required("La date est obligatoire.")]
    private ?\DateTimeImmutable $date = null;

    private string $remarks = '';

    public function setAppointment(TimberAppointment $appointment): static
    {
        $this->appointment = $appointment;

        return $this;
    }

    public function getAppointment(): ?TimberAppointment
    {
        return $this->appointment;
    }

    public function setStaff(?StevedoringStaff $stevedoringStaff): static
    {
        $this->staff = $stevedoringStaff;

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

    public function setRemarks(string $role): static
    {
        $this->remarks = $role;

        return $this;
    }

    public function getRemarks(): string
    {
        return $this->remarks;
    }

    public function toArray(): array
    {
        return [
            'appointmentId' => $this->getAppointment()?->id,
            'staffId' => $this->getStaff()?->id,
            'date' => $this->getDate()?->format('Y-m-d'),
            'remarks' => $this->getRemarks(),
        ];
    }
}
