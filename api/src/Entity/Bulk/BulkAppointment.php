<?php

// Path: api/src/Entity/Bulk/BulkAppointment.php

declare(strict_types=1);

namespace App\Entity\Bulk;

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
    private ?\DateTimeImmutable $date = null;

    private ?\DateTimeImmutable $time = null;

    #[Required("Le produit est obligatoire.")]
    private ?BulkProduct $product = null;

    private ?BulkQuality $quality = null;

    #[PositiveOrNullNumber("La quantité doit être un nombre positif ou nul.")]
    private int $quantityValue = 0;

    private bool $quantityIsMax = false;

    private bool $isReady = false;

    #[Required("Le fournisseur est obligatoire.")]
    private ?ThirdParty $supplier = null;

    #[Required("Le client est obligatoire.")]
    private ?ThirdParty $customer = null;

    private ?ThirdParty $carrier = null;

    private string $orderNumber = "";

    private string $publicComments = "";

    private string $privateComments = "";

    private bool $isOnTv = true;

    private bool $isArchive = false;

    /** @var BulkDispatchItem[] */
    private array $dispatch = [];

    public function setDate(\DateTimeInterface|string $date): static
    {
        $this->date = DateUtils::makeDateTimeImmutable($date);

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function getSqlDate(): ?string
    {
        return $this->date?->format('Y-m-d') ?? null;
    }

    public function setTime(\DateTimeInterface|string|null $time): static
    {
        $this->time = DateUtils::makeDateTimeImmutable($time);

        return $this;
    }

    public function getTime(): ?\DateTimeImmutable
    {
        return $this->time;
    }

    public function getSqlTime(): ?string
    {
        return $this->time?->format('H:i') ?? null;
    }

    public function setProduct(?BulkProduct $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?BulkProduct
    {
        return $this->product;
    }

    public function setQuality(?BulkQuality $qualite): static
    {
        $this->quality = $qualite;

        return $this;
    }

    public function getQuality(): ?BulkQuality
    {
        return $this->quality;
    }

    public function setQuantityValue(int $value): static
    {
        $this->quantityValue = $value;

        return $this;
    }

    public function getQuantityValue(): int
    {
        return $this->quantityValue;
    }

    public function setQuantityIsMax(bool $quantityIsMax): static
    {
        $this->quantityIsMax = $quantityIsMax;

        return $this;
    }

    public function getQuantityIsMax(): bool
    {
        return $this->quantityIsMax;
    }

    public function setReady(bool|int $commandePrete): static
    {
        $this->isReady = (bool) $commandePrete;

        return $this;
    }

    public function isReady(): bool
    {
        return $this->isReady;
    }

    public function setSupplier(?ThirdParty $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getSupplier(): ?ThirdParty
    {
        return $this->supplier;
    }

    public function setCustomer(?ThirdParty $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCustomer(): ?ThirdParty
    {
        return $this->customer;
    }

    public function setCarrier(?ThirdParty $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function getCarrier(): ?ThirdParty
    {
        return $this->carrier;
    }

    public function setOrderNumber(string $orderNUmber): static
    {
        $this->orderNumber = $orderNUmber;

        return $this;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setPublicComments(string $comments): static
    {
        $this->publicComments = $comments;

        return $this;
    }

    public function getPublicComments(): string
    {
        return $this->publicComments;
    }

    public function setPrivateComments(string $comments): static
    {
        $this->privateComments = $comments;

        return $this;
    }

    public function getPrivateComments(): string
    {
        return $this->privateComments;
    }

    public function setOnTv(bool $onTv): static
    {
        $this->isOnTv = $onTv;

        return $this;
    }

    public function isOnTv(): bool
    {
        return $this->isOnTv;
    }

    public function setArchive(bool $archive): static
    {
        $this->isArchive = $archive;

        return $this;
    }

    public function isArchive(): bool
    {
        return $this->isArchive;
    }

    /**
     * @param BulkDispatchItem[] $dispatch 
     */
    public function setDispatch(array $dispatch): static
    {
        $this->dispatch = $dispatch;

        foreach ($dispatch as $dispatch) {
            $dispatch->setAppointment($this);
        }

        return $this;
    }

    /**
     * @return BulkDispatchItem[]
     */
    public function getDispatch(): array
    {
        return $this->dispatch;
    }

    #[\Override]
    public function toArray(): array
    {
        return  [
            "id" => $this->id,
            "date_rdv" => $this->getDate()?->format('Y-m-d'),
            "heure" => $this->getTime()?->format('H:i'),
            "produit" => $this->getProduct()?->id,
            "qualite" => $this->getQuality()?->id,
            "quantite" => $this->getQuantityValue(),
            "max" => $this->getQuantityIsMax(),
            "commande_prete" => $this->isReady(),
            "fournisseur" => $this->getSupplier()?->id,
            "client" => $this->getCustomer()?->id,
            "transporteur" => $this->getCarrier()?->id,
            "num_commande" => $this->getOrderNumber(),
            "commentaire_public" => $this->getPublicComments(),
            "commentaire_prive" => $this->getPrivateComments(),
            "showOnTv" => $this->isOnTv(),
            "archive" => $this->isArchive(),
            "dispatch" => array_map(
                fn(BulkDispatchItem $dispatch) => $dispatch->toArray(),
                $this->getDispatch()
            )
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
