<?php

// Path: api/src/Entity/Timber/TimberDispatch.php

namespace App\Entity\Timber;

use App\Core\Component\DateUtils;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;
use App\Entity\Stevedoring\StevedoringStaff;
use App\Entity\Timber\TimberAppointment;

/**
 * @phpstan-type TimberDispatchArray array{
 *                                     appointment_id: int,
 *                                     staff_id: int,
 *                                     remarks: string,
 *                                   }
 */
class TimberDispatchItem extends AbstractEntity
{
    public ?TimberAppointment $appointment = null;

    #[Required("Le personnel est obligatoire.")]
    public ?StevedoringStaff $staff = null;

    #[Required("La date est obligatoire.")]
    public ?\DateTimeImmutable $date = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
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
