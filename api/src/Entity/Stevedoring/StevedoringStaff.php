<?php

// Path: api/src/Entity/Stevedoring/StevedoringStaff.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Array\ArrayHandler;
use App\Core\Exceptions\Client\ValidationException;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\InArray;
use App\Core\Validation\Constraints\Required;
use App\Core\Validation\ValidationResult;
use App\Entity\AbstractEntity;

/**
 * @phpstan-type StevedoringStaffArray array{
 *                                       id: ?int,
 *                                       firstname?: string,
 *                                       lastname?: string,
 *                                       phone?: string,
 *                                       type?: string,
 *                                       tempWorkAgency?: ?string,
 *                                       isActive?: bool,
 *                                       comments?: string,
 *                                       deletedAt?: ?string
 *                                     }
 */
final class StevedoringStaff extends AbstractEntity implements \Stringable
{
    use IdentifierTrait;

    #[Required(message: "Le prénom du personnel est requis.")]
    public string $firstname = '';

    #[Required(message: "Le nom de famille du personnel est requis.")]
    public string $lastname = '';

    /**
     * Virtual property.
     */
    public string $fullname {
        get => \trim($this->firstname . ' ' . $this->lastname) ?: '(Personnel supprimé)';
    }

    public string $phone = '';

    #[InArray(values: ['mensuel', 'interim'], message: "Le type de contrat n'est pas valide.")]
    public string $type = '' {
        set => \mb_strtolower($value);
    }

    public ?string $tempWorkAgency = null;

    public bool $isActive = true;

    public string $comments = '';

    public ?\DateTimeImmutable $deletedAt = null;

    /**
     * @param ArrayHandler|StevedoringStaffArray|null $data
     */
    public function __construct(ArrayHandler|array|null $data = null)
    {
        if (null === $data) {
            return;
        }

        $dataAH = $data instanceof ArrayHandler ? $data : new ArrayHandler($data);

        $this->id = $dataAH->getInt('id');
        $this->firstname = $dataAH->getString('firstname');
        $this->lastname = $dataAH->getString('lastname');
        $this->phone = $dataAH->getString('phone');
        $this->type = $dataAH->getString('type');
        $this->tempWorkAgency = $dataAH->getString('tempWorkAgency', null);
        $this->isActive = $dataAH->getBool('isActive');
        $this->comments = $dataAH->getString('comments');

        if ($this->type === "cdi") {
            $this->tempWorkAgency = null;
        }
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'fullname' => $this->fullname,
            'phone' => $this->phone,
            'type' => $this->type,
            'tempWorkAgency' => $this->tempWorkAgency,
            'isActive' => $this->isActive,
            'comments' => $this->comments,
            'deletedAt' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return $this->fullname;
    }

    #[\Override]
    public function validate(bool $throw = true): ValidationResult
    {
        $validationResult = parent::validate(false);

        if ($this->type === "interim" && !$this->tempWorkAgency) {
            $validationResult->addError("L'agence d'intérim est requise pour un personnel intérimaire.");
        }

        if ($throw && $validationResult->hasErrors()) {
            throw new ValidationException($validationResult->getErrorMessage());
        }

        return $validationResult;
    }
}
