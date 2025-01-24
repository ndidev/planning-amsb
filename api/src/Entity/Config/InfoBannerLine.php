<?php

// Path: api/src/Entity/Config/InfoBannerLine.php

declare(strict_types=1);

namespace App\Entity\Config;

use App\Core\Validation\Constraints\Required;
use App\Core\Traits\IdentifierTrait;
use App\Core\Traits\ModuleTrait;
use App\Entity\AbstractEntity;

class InfoBannerLine extends AbstractEntity
{
    use IdentifierTrait;
    use ModuleTrait;

    public const DEFAULT_COLOR = '#000000';

    private bool $pc = false;

    private bool $tv = false;

    #[Required("Le message est obligatoire.")]
    private string $message = '';

    private string $color = self::DEFAULT_COLOR;

    public function setPc(bool|int $pc): static
    {
        $this->pc = (bool) $pc;

        return $this;
    }

    public function isPc(): bool
    {
        return $this->pc;
    }

    public function setTv(bool|int $tv): static
    {
        $this->tv = (bool) $tv;

        return $this;
    }

    public function isTv(): bool
    {
        return $this->tv;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color ?: self::DEFAULT_COLOR;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'module' => $this->getModule(),
            'pc' => $this->isPc(),
            'tv' => $this->isTv(),
            'message' => $this->getMessage(),
            'couleur' => $this->getColor(),
        ];
    }
}
