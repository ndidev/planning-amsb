<?php

// Path: api/src/Entity/Stevedoring/ShipReportSubcontractEntry.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Component\DateUtils;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\Maximum;
use App\Core\Validation\Constraints\Minimum;
use App\Core\Validation\Constraints\Required;
use App\Core\Validation\ValidationResult;
use App\Entity\AbstractEntity;

/**
 * @phpstan-type ShipReportSubcontractEntryArray array{
 *                                                 id: int,
 *                                                 ship_report_id: int,
 *                                                 subreport_id: int,
 *                                                 subcontractor_name: string,
 *                                                 date: string,
 *                                                 hours_worked: float|null,
 *                                                 cost: float|null,
 *                                                 comments: string,
 *                                               }
 */
final class ShipReportSubcontractEntry extends AbstractEntity
{
    use IdentifierTrait;

    public ?ShipSubreport $subreport = null;

    #[Required("Le nom du sous-traitant est obligatoire.")]
    public string $subcontractorName = '';

    #[Required("La nature de la prestation est obligatoire.")]
    public string $type = '';

    #[Required("La date est obligatoire.")]
    public ?\DateTimeImmutable $date = null {
        set(\DateTimeImmutable|string|null $date) => DateUtils::makeDateTimeImmutable($date);
    }

    #[Minimum(0, message: "Les heures travaillées ne peuvent pas être négatives.")]
    #[Maximum(24, message: "Les heures travaillées ne peuvent pas dépasser 24 heures.")]
    public ?float $hoursWorked = null;

    public ?float $cost = null;

    public string $comments = '';

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subcontractorName' => $this->subcontractorName,
            'type' => $this->type,
            'date' => $this->date?->format('Y-m-d'),
            'hoursWorked' => $this->hoursWorked,
            'cost' => $this->cost,
            'comments' => $this->comments,
        ];
    }

    #[\Override]
    public function validate(bool $throw = true): ValidationResult
    {
        $result = parent::validate(false);

        if ($this->hoursWorked === null && $this->cost === null) {
            $result->addError("Sous-traitance : les heures ou le coût doivent être renseignés.");
        }

        return $result;
    }
}
