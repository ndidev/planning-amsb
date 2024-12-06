<?php

// Path: api/src/Entity/Stevedoring/StevedoringStaff.php

declare(strict_types=1);

namespace App\Entity\Stevedoring;

use App\Core\Exceptions\Client\ValidationException;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\InArray;
use App\Core\Validation\Constraints\Required;
use App\Core\Validation\ValidationResult;
use App\Entity\AbstractEntity;

class StevedoringStaff extends AbstractEntity
{
    use IdentifierTrait;

    #[Required(message: "Le prénom du personnel est requis.")]
    private string $firstname = '';

    #[Required(message: "Le nom de famille du personnel est requis.")]
    private string $lastname = '';

    private string $phone = '';

    #[InArray(values: ['mensuel', 'interim'], message: "Le type de contrat n'est pas valide.")]
    private string $type = '';

    private ?string $tempWorkAgency = null;

    private bool $isActive = true;

    private string $comments = '';

    private ?\DateTimeImmutable $deletedAt = null;

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getFullname(): string
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setType(string $type): static
    {
        $this->type = mb_strtolower($type);

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setTempWorkAgency(?string $tempWorkAgency): static
    {
        $this->tempWorkAgency = $tempWorkAgency ?: null;

        return $this;
    }

    public function getTempWorkAgency(): ?string
    {
        return $this->tempWorkAgency ?: null;
    }

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'fullname' => $this->getFullname(),
            'phone' => $this->getPhone(),
            'type' => $this->getType(),
            'tempWorkAgency' => $this->getTempWorkAgency(),
            'isActive' => $this->isActive(),
            'comments' => $this->getComments(),
            'deletedAt' => $this->getDeletedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    #[\Override]
    public function validate(bool $throw = true): ValidationResult
    {
        $validationResult = parent::validate(false);

        if ($this->type === "interim" && $this->tempWorkAgency === null) {
            $validationResult->addError("L'agence d'intérim est requise pour un personnel intérimaire.");
        }

        if ($throw && $validationResult->hasErrors()) {
            throw new ValidationException($validationResult->getErrorMessage());
        }

        return $validationResult;
    }
}
