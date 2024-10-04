<?php

// Path: api/src/Entity/Config/InfoBannerLine.php

namespace App\Entity\Config;

use App\Core\Component\Module;
use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;

class InfoBannerLine extends AbstractEntity
{
    use IdentifierTrait;

    private Module $module;
    private bool $pc;
    private bool $tv;
    private string $message;
    private string $color;

    public function getModule(): Module
    {
        return $this->module;
    }

    public function setModule(Module|string $module): static
    {
        if (is_string($module)) {
            $this->module = Module::tryFrom($module);
        } else {
            $this->module = $module;
        }

        return $this;
    }

    public function isPc(): bool
    {
        return $this->pc;
    }

    public function setPc(bool|int $pc): static
    {
        $this->pc = (bool) $pc;

        return $this;
    }

    public function isTv(): bool
    {
        return $this->tv;
    }

    public function setTv(bool|int $tv): static
    {
        $this->tv = (bool) $tv;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'module' => $this->getModule(),
            'pc' => $this->isPc(),
            'tv' => $this->isTv(),
            'message' => $this->getMessage(),
            'couleur' => $this->getColor(),
        ];
    }
}
