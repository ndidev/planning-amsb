<?php

// Path: api/src/Service/CountryService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Entity\Country;
use App\Repository\CountryRepository;

/**
 * @phpstan-type CountryArray array{
 *                              iso?: string,
 *                              nom?: string,
 *                            }
 */
final class CountryService
{
    private CountryRepository $countryRepository;

    public function __construct()
    {
        $this->countryRepository = new CountryRepository();
    }

    /**
     * Creates a Country object from database data.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param CountryArray $rawData
     * 
     * @return Country 
     */
    public function makeCountry(array $rawData): Country
    {
        return (new Country())
            ->setISO($rawData["iso"] ?? "")
            ->setName($rawData["nom"] ?? "");
    }

    /**
     * Fetches all countries.
     * 
     * @return Collection<Country> All fetched countries.
     */
    public function getCountries(): Collection
    {
        return $this->countryRepository->fetchAll();
    }

    public function getCountry(string $iso): ?Country
    {
        return $this->countryRepository->fetchByIso($iso);
    }
}
