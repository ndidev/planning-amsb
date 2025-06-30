<?php

// Path: api/src/Entity/ThirdParty/ThirdPartyContact.php

declare(strict_types=1);

namespace App\Entity\ThirdParty;

use App\Core\Traits\IdentifierTrait;
use App\Entity\AbstractEntity;
use App\Core\Validation\Constraints\Required;

/**
 * @phpstan-type ThirdPartyContactArray array{
 *                                        id: int,
 *                                        tiers: int,
 *                                        nom: string,
 *                                        telephone: string,
 *                                        email: string,
 *                                        fonction: string,
 *                                        commentaire: string,
 *                                      }
 */
class ThirdPartyContact extends AbstractEntity
{
    use IdentifierTrait;

    public ?ThirdParty $thirdParty = null;

    #[Required("Le nom du contact est obligatoire.")]
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $position = '';

    public string $comments = '';

    #[\Override]
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->name,
            'telephone' => $this->phone,
            'email' => $this->email,
            'fonction' => $this->position,
            'commentaire' => $this->comments,
        ];
    }
}
