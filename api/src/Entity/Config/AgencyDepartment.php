<?php

// Path: api/src/Entity/AgencyDepartment.php

namespace App\Entity\Config;

use App\Entity\AbstractEntity;

class AgencyDepartment extends AbstractEntity
{
    private string $service = '';
    private string $displayName = '';
    private string $fullName = '';
    private string $addressLine1 = '';
    private string $addressLine2 = '';
    private string $postCode = '';
    private string $city = '';
    private string $country = '';
    private string $phone = '';
    private string $mobile = '';
    private string $email = '';

    public function getService(): string
    {
        return $this->service;
    }

    public function setService(string $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(string $addressLine1): static
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    public function getAddressLine2(): string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(string $addressLine2): static
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    public function getPostCode(): string
    {
        return $this->postCode;
    }

    public function setPostCode(string $postCode): static
    {
        $this->postCode = $postCode;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getMobile(): string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): static
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function toArray(): array
    {
        return [
            "service" => $this->getService(),
            "affichage" => $this->getDisplayName(),
            "nom" => $this->getFullName(),
            "adresse_ligne_1" => $this->getAddressLine1(),
            "adresse_ligne_2" => $this->getAddressLine2(),
            "cp" => $this->getPostCode(),
            "ville" => $this->getCity(),
            "pays" => $this->getCountry(),
            "telephone" => $this->getPhone(),
            "mobile" => $this->getMobile(),
            "email" => $this->getEmail(),
        ];
    }
}
