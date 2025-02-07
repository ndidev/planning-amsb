<?php

// Path: api/src/Service/CountryService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Entity\Country;
use App\Repository\CountryRepository;

/**
 * @phpstan-import-type CountryArray from \App\Entity\Country
 */
final class CountryService
{
    private CountryRepository $countryRepository;

    public function __construct()
    {
        $this->countryRepository = new CountryRepository($this);
    }

    /**
     * Creates a Country object from database data.
     * 
     * @param CountryArray $rawData 
     * 
     * @return Country 
     */
    public function makeCountryFromDatabase(array $rawData): Country
    {
        return new Country($rawData);
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
        /** @var array<string, Country> */
        static $cache = [];

        if (!$iso) {
            return null;
        }

        if (isset($cache[$iso])) {
            return $cache[$iso];
        }

        $reflector = new \ReflectionClass(Country::class);
        $countryRepository = $this->countryRepository;
        /** @var Country */
        $country = $reflector->newLazyGhost(
            function (Country $country) use ($iso, $countryRepository) {
                $data = $countryRepository->fetchByIso($iso, true);
                $country->__construct($data);
            }
        );

        $reflector->getProperty('iso')->setRawValueWithoutLazyInitialization($country, $iso);

        $cache[$iso] = $country;

        return $country;
    }
}
