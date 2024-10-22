<?php

// Path: api/src/Repository/CountryRepository.php

namespace App\Repository;

use App\Entity\Country;

final class CountryRepository extends Repository
{
    private $redisNamespace = "countries";

    /**
     * Fetches all countries.
     * 
     * @return Country[] All fetched countries.
     */
    public function fetchAll(): array
    {
        // Redis
        $countriesRaw = json_decode($this->redis->get($this->redisNamespace), true);

        if (!$countriesRaw) {
            $statement = "SELECT * FROM utils_pays ORDER BY nom";

            $countriesRaw = $this->mysql->query($statement)->fetchAll();

            $this->redis->set($this->redisNamespace, json_encode($countriesRaw));
        }

        $countries = array_map(fn(array $countryRaw) => new Country($countryRaw), $countriesRaw);

        return $countries;
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

        $pays = new Country($paysRaw);

        return $pays;
    }
}
