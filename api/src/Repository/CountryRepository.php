<?php

// Path: api/src/Repository/CountryRepository.php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Entity\Country;
use App\Service\CountryService;

final class CountryRepository extends Repository
{
    private string $redisNamespace = "countries";

    /**
     * Fetches all countries.
     * 
     * @return Collection<Country> All fetched countries.
     */
    public function fetchAll(): Collection
    {
        // Redis
        $countriesRaw = json_decode($this->redis->get($this->redisNamespace), true);

        if (!$countriesRaw) {
            $statement = "SELECT * FROM utils_pays ORDER BY nom";

            $countriesRaw = $this->mysql->query($statement)->fetchAll();

            $this->redis->set($this->redisNamespace, json_encode($countriesRaw));
        }

        $countryService = new CountryService();

        $countries = array_map(
            fn(array $countryRaw) => $countryService->makeCountry($countryRaw),
            $countriesRaw
        );

        return new Collection($countries);
    }

    /**
     * Fetches a country.
     * 
     * @param string $iso ISO code of the country to fetch.
     * 
     * @return ?Country Fetched country.
     */
    public function fetchByIso(string $iso): ?Country
    {
        $statement = "SELECT * FROM utils_pays  WHERE iso = :iso";

        $requete = $this->mysql->prepare($statement);
        $requete->execute(["iso" => $iso]);
        $paysRaw = $requete->fetch();

        if (!$paysRaw) return null;

        $pays = (new CountryService())->makeCountry($paysRaw);

        return $pays;
    }
}
