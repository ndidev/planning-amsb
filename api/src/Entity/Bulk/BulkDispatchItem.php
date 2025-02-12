<?php

// Path: api/src/Entity/Bulk/BulkDispatch.php

namespace App\Entity\Bulk;

use App\Core\Array\ArrayHandler;
use App\Core\Component\DateUtils;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;
use App\Entity\Bulk\BulkAppointment;
use App\Entity\Stevedoring\StevedoringStaff;

/**
 * @phpstan-type BulkDispatchArray array{
 *                                   appointment_id: int,
 *                                   staff_id: int,
 *                                   date: string,
 *                                   remarks: string,
 *                                 }
 */
class BulkDispatchItem extends AbstractEntity
{
    public ?BulkAppointment $appointment = null;

    #[Required("Le personnel est obligatoire.")]
    public ?StevedoringStaff $staff = null;

    #[Required("La date est obligatoire.")]
    public ?\DateTimeImmutable $date = null {
        set(\DateTimeInterface|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public string $remarks = '';

    public function toArray(): array
    {
        return [
            'appointmentId' => $this->appointment?->id,
            'staffId' => $this->staff?->id,
            'date' => $this->date?->format('Y-m-d'),
            'remarks' => $this->remarks,
        ];
    }
}
