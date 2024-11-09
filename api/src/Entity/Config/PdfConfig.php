<?php

// Path: api/src/Entity/Config/PdfConfig.php

declare(strict_types=1);

namespace App\Entity\Config;

use App\Core\Component\Module;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Entity\ThirdParty;

class PdfConfig extends AbstractEntity
{
    use IdentifierTrait;

    /** @var ?Module::* $module */
    private ?string $module = null;
    private ?ThirdParty $supplier = null;
    private bool $autoSend = false;
    /** @var string[] */
    private array $emails = [];
    private int $daysBefore = 0;
    private int $daysAfter = 0;

    /**
     * @return Module::*|null
     */
    public function getModule(): ?string
    {
        return $this->module;
    }

    public function setModule(?string $module): static
    {
        if (is_string($module)) {
            $this->module = Module::tryFrom($module);
        } else {
            $this->module = $module;
        }

        return $this;
    }

    public function getSupplier(): ?ThirdParty
    {
        return $this->supplier;
    }

    public function setSupplier(?ThirdParty $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function isAutoSend(): bool
    {
        return $this->autoSend;
    }

    public function setAutoSend(bool|int $autoSend): static
    {
        $this->autoSend = (bool) $autoSend;

        return $this;
    }

    /**
     * Get the list of emails.
     * 
     * @return string[]
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    public function getEmailsAsString(): string
    {
        return implode(PHP_EOL, $this->emails);
    }

    /**
     * Set the list of emails.
     * 
     * @param array<mixed>|string $emails 
     */
    public function setEmails(array|string $emails): static
    {
        if (is_string($emails)) {
            $this->emails = array_map('trim', explode(PHP_EOL, $emails));
        } else {
            $this->emails = array_filter($emails, 'is_string');
        }

        return $this;
    }

    public function getDaysBefore(): int
    {
        return $this->daysBefore;
    }

    public function setDaysBefore(int $daysBefore): static
    {
        $this->daysBefore = $daysBefore;

        return $this;
    }

    public function getDaysAfter(): int
    {
        return $this->daysAfter;
    }

    public function setDaysAfter(int $daysAfter): static
    {
        $this->daysAfter = $daysAfter;

        return $this;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "module" => $this->getModule(),
            "fournisseur" => $this->getSupplier()?->getId(),
            "envoi_auto" => $this->isAutoSend(),
            "liste_emails" => $this->getEmailsAsString(),
            "jours_avant" => $this->getDaysBefore(),
            "jours_apres" => $this->getDaysAfter(),
        ];
    }
}
