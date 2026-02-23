<?php

// Path: api/src/Entity/Config/PdfConfig.php

declare(strict_types=1);

namespace App\Entity\Config;

use App\Core\Traits\IdentifierTrait;
use App\Core\Traits\ModuleTrait;
use App\Core\Validation\Constraints\PositiveOrNullNumber;
use App\Core\Validation\Constraints\Required;
use App\Entity\AbstractEntity;
use App\Entity\ThirdParty\ThirdParty;

/**
 * /**
 * @phpstan-type PdfConfigArray array{
 *                                id?: int,
 *                                module?: string,
 *                                fournisseur?: int,
 *                                envoi_auto?: bool|int,
 *                                liste_emails?: string|string[],
 *                                jours_avant?: int,
 *                                jours_apres?: int,
 *                              }
 */
final class PdfConfig extends AbstractEntity
{
    use IdentifierTrait;
    use ModuleTrait;

    #[Required("Le fournisseur est obligatoire.")]
    public ?ThirdParty $supplier = null;

    public bool $autoSend = false {
        set(bool|int $value) => $this->autoSend = (bool) $value;
    }

    /** @var string[] */

    public array $emails = [] {
        set(array|string $emails) {
            if (\is_string($emails)) {
                $this->emails = \array_map('trim', \explode(PHP_EOL, $emails));
            } else {
                $this->emails = \array_filter($emails, 'is_string');
            }
        }
    }

    #[PositiveOrNullNumber("Le nombre de jours avant doit être positif ou nul.")]
    public int $daysBefore = 0;

    #[PositiveOrNullNumber("Le nombre de jours après doit être positif ou nul.")]
    public int $daysAfter = 0;

    public function getEmailsAsString(): string
    {
        return \implode(PHP_EOL, $this->emails);
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "module" => $this->module,
            "fournisseur" => $this->supplier?->id,
            "envoi_auto" => $this->autoSend,
            "liste_emails" => $this->getEmailsAsString(),
            "jours_avant" => $this->daysBefore,
            "jours_apres" => $this->daysAfter,
        ];
    }
}
