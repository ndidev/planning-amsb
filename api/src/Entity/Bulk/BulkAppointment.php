<?php

// Path: api/src/Entity/Bulk/BulkAppointment.php

declare(strict_types=1);

namespace App\Entity\Bulk;

use App\Core\Array\ArrayHandler;
use App\Core\Component\DateUtils;
use App\Core\Exceptions\Client\ValidationException;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\Constraints\PositiveOrNullNumber;
use App\Core\Validation\Constraints\Required;
use App\Core\Validation\ValidationResult;
use App\Entity\AbstractEntity;
use App\Entity\ThirdParty;

/**
 * @phpstan-type BulkAppointmentArray array{
 *                                      id: int,
 *                                      date_rdv: string,
 *                                      heure: ?string,
 *                                      produit: int,
 *                                      qualite: ?int,
 *                                      quantite: int,
 *                                      max: int,
 *                                      commande_prete: int,
 *                                      fournisseur: int,
 *                                      client: int,
 *                                      transporteur: ?int,
 *                                      num_commande: string,
 *                                      commentaire_public: string,
 *                                      commentaire_prive: string,
 *                                      show_on_tv: int,
 *                                      archive: int,
 *                                    }
 */
class BulkAppointment extends AbstractEntity
{
    use IdentifierTrait;

    #[Required("La date est obligatoire.")]
    public ?\DateTimeImmutable $date = null {
        set(\DateTimeInterface|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public ?string $sqlDate {
        get => $this->date?->format('Y-m-d');
    }

    public ?\DateTimeImmutable $time = null {
        set(\DateTimeInterface|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public ?string $sqlTime {
        get => $this->time?->format('H:i');
    }

    #[Required("Le produit est obligatoire.")]
    public ?BulkProduct $product = null;

    public ?BulkQuality $quality = null;

    #[PositiveOrNullNumber("La quantité doit être un nombre positif ou nul.")]
    public int $quantityValue = 0;

    public bool $quantityIsMax = false;

    public bool $isReady = false;

    #[Required("Le fournisseur est obligatoire.")]
    public ?ThirdParty $supplier = null;

    #[Required("Le client est obligatoire.")]
    public ?ThirdParty $customer = null;

    public ?ThirdParty $carrier = null;

    public string $orderNumber = "";

    public string $publicComments = "";

    public string $privateComments = "";

    public bool $isOnTv = true;

    public bool $isArchive = false;

    /** @var BulkDispatchItem[] */
    public array $dispatch = [] {
        set {
            foreach ($value as $item) {
                /** @disregard P1006 */
                $item->appointment = $this;
            }

            $this->dispatch = $value;
        }
    }

    #[\Override]
    public function toArray(): array
    {
        return  [
            "id" => $this->id,
            "date_rdv" => $this->date?->format('Y-m-d'),
            "heure" => $this->time?->format('H:i'),
            "produit" => $this->product?->id,
            "qualite" => $this->quality?->id,
            "quantite" => $this->quantityValue,
            "max" => $this->quantityIsMax,
            "commande_prete" => $this->isReady,
            "fournisseur" => $this->supplier?->id,
            "client" => $this->customer?->id,
            "transporteur" => $this->carrier?->id,
            "num_commande" => $this->orderNumber,
            "commentaire_public" => $this->publicComments,
            "commentaire_prive" => $this->privateComments,
            "showOnTv" => $this->isOnTv,
            "archive" => $this->isArchive,
            "dispatch" => \array_map(fn($dispatch) => $dispatch->toArray(), $this->dispatch),
        ];
    }

    #[\Override]
    public function validate(bool $throw = true): ValidationResult
    {
        $validationResult = parent::validate(false);

        foreach ($this->dispatch as $dispatch) {
            $validationResult->merge($dispatch->validate(false));
        }

        if ($throw && $validationResult->hasErrors()) {
            throw new ValidationException(errors: $validationResult->getErrorMessage());
        }

        return $validationResult;
    }
}
