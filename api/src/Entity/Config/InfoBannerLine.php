<?php

// Path: api/src/Entity/Config/InfoBannerLine.php

declare(strict_types=1);

namespace App\Entity\Config;

use App\Core\Validation\Constraints\Required;
use App\Core\Traits\IdentifierTrait;
use App\Core\Traits\ModuleTrait;
use App\Entity\AbstractEntity;

/**
 * @phpstan-type InfoBannerLineArray array{
 *                                     id: int,
 *                                     module: string,
 *                                     pc: bool,
 *                                     tv: bool,
 *                                     message: string,
 *                                     couleur: string,
 *                                   }
 */
final class InfoBannerLine extends AbstractEntity
{
    use IdentifierTrait;
    use ModuleTrait;

    public const DEFAULT_COLOR = '#000000';

    public bool $isDisplayedOnPC = false {
        set(bool|int $value) => $this->isDisplayedOnPC = (bool) $value;
    }

    public bool $isDisplayedOnTV = false {
        set(bool|int $value) => $this->isDisplayedOnTV = (bool) $value;
    }

    #[Required("Le message est obligatoire.")]
    public string $message = '';

    public string $color = self::DEFAULT_COLOR {
        set => $value ?: self::DEFAULT_COLOR;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'module' => $this->module,
            'pc' => $this->isDisplayedOnPC,
            'tv' => $this->isDisplayedOnTV,
            'message' => $this->message,
            'couleur' => $this->color,
        ];
    }
}
