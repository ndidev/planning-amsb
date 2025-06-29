<?php

// Path: api/src/Entity/Timber/TimberAppointment.php

declare(strict_types=1);

namespace App\Entity\Timber;

use App\Core\Validation\Constraints\Required;
use App\Core\Component\DateUtils;
use App\Core\Exceptions\Client\ValidationException;
use App\Core\Traits\IdentifierTrait;
use App\Core\Validation\ValidationResult;
use App\Entity\AbstractEntity;
use App\Entity\ThirdParty\ThirdParty;

/**
 * @phpstan-type TimberAppointmentArray array{
 *                                        id: int,
 *                                        attente: bool,
 *                                        date_rdv: string|null,
 *                                        heure_arrivee: string|null,
 *                                        heure_depart: string|null,
 *                                        fournisseur: int|null,
 *                                        chargement: int|null,
 *                                        livraison: int|null,
 *                                        client: int|null,
 *                                        transporteur: int|null,
 *                                        affreteur: int|null,
 *                                        commande_prete: bool,
 *                                        confirmation_affretement: bool,
 *                                        numero_bl: string,
 *                                        commentaire_public: string,
 *                                        commentaire_cache: string,
 *                                      }
 */
final class TimberAppointment extends AbstractEntity
{
    use IdentifierTrait;

    public bool $isOnHold = false {
        set(bool|int $value) => (bool) $value;
    }

    public ?\DateTimeImmutable $date = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public ?string $sqlDate {
        get => $this->date?->format('Y-m-d');
    }

    public ?\DateTimeImmutable $arrivalTime = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public ?string $sqlArrivalTime {
        get => $this->arrivalTime?->format('H:i');
    }

    public ?\DateTimeImmutable $departureTime = null {
        set(\DateTimeImmutable|string|null $value) => DateUtils::makeDateTimeImmutable($value);
    }

    public ?string $sqlDepartureTime {
        get => $this->departureTime?->format('H:i');
    }

    #[Required("Le fournisseur est obligatoire.")]
    public ?ThirdParty $supplier = null;

    #[Required("Le lieu de chargement est obligatoire.")]
    public ?ThirdParty $loadingPlace = null;

    public ?ThirdParty $deliveryPlace = null;

    #[Required("Le client est obligatoire.")]
    public ?ThirdParty $customer = null;

    public ?ThirdParty $carrier = null;

    public ?ThirdParty $transportBroker = null;

    public bool $isReady = false {
        set(bool|int $value) => (bool) $value;
    }

    public bool $isCharteringConfirmationSent = false {
        set(bool|int $value) => (bool) $value;
    }

    public string $deliveryNoteNumber = "";

    public string $publicComment = "";

    public string $privateComment = "";

    /** @var TimberDispatchItem[] */
    public array $dispatch = [] {
        set(array $value) {
            foreach ($value as $dispatchItem) {
                $dispatchItem->appointment = $this;
            }

            $this->dispatch = $value;
        }
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "attente" => $this->isOnHold,
            "date_rdv" => $this->sqlDate,
            "heure_arrivee" => $this->sqlArrivalTime,
            "heure_depart" => $this->sqlDepartureTime,
            "fournisseur" => $this->supplier?->id,
            "chargement" => $this->loadingPlace?->id,
            "livraison" => $this->deliveryPlace?->id,
            "client" => $this->customer?->id,
            "transporteur" => $this->carrier?->id,
            "affreteur" => $this->transportBroker?->id,
            "commande_prete" => $this->isReady,
            "confirmation_affretement" => $this->isCharteringConfirmationSent,
            "numero_bl" => $this->deliveryNoteNumber,
            "commentaire_public" => $this->publicComment,
            "commentaire_cache" => $this->privateComment,
            "dispatch" => \array_map(fn($item) => $item->toArray(), $this->dispatch),
        ];
    }

    #[\Override]
    public function validate(bool $throw = true): ValidationResult
    {
        $validationResult = parent::validate(false);

        if (null === $this->date && false === $this->isOnHold) {
            $validationResult->addError("La date de rendez-vous est obligatoire si le RDV n'est pas en attente.");
        }

        foreach ($this->dispatch as $dispatchItem) {
            $validationResult->merge($dispatchItem->validate(false));
        }

        if ($throw && $validationResult->hasErrors()) {
            throw new ValidationException(errors: $validationResult->getErrorMessage());
        }

        return $validationResult;
    }
}
