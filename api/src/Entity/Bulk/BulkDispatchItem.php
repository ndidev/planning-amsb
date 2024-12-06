<?php

// Path: api/src/Entity/Bulk/BulkDispatch.php

namespace App\Entity\Bulk;

use App\Entity\AbstractEntity;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Stevedoring\StevedoringStaff;

class BulkDispatchItem extends AbstractEntity
{
    private ?BulkAppointment $appointment = null;

    private ?StevedoringStaff $staff = null;

    private string $remarks = '';

    public function setAppointment(BulkAppointment $appointment): static
    {
        $this->appointment = $appointment;

        return $this;
    }

    public function getAppointment(): ?BulkAppointment
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
            'appointmentId' => $this->getAppointment()?->getId(),
            'staffId' => $this->getStaff()?->getId(),
            'remarks' => $this->getRemarks(),
        ];
    }
}
