<?php

// Path: api/src/Service/CountryService.php

namespace App\Service;

use App\Entity\Country;
use App\Repository\CountryRepository;

class CountryService
{
    private CountryRepository $countryRepository;

    public function __construct()
    {
        $this->countryRepository = new CountryRepository();
    }

    public function makeCountry(array $rawData): Country
    {
        return (new Country())
            ->setISO($rawData["iso"] ?? "")
            ->setName($rawData["nom"] ?? "");
    }

    /**
     * Fetches all countries.
     * 
     * @return Country[] All fetched countries.
     */
    public function getCountries(): array
    {
        return $this->countryRepository->fetchAll();
    }

    public function getCountry(string $iso): ?Country
    {
        return $this->countryRepository->fetchByIso($iso);
    }
}
