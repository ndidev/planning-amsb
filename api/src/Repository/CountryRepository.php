<?php

// Path: api/src/Repository/CountryRepository.php

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Country;
use App\Service\CountryService;

final class CountryRepository extends Repository
{
    private string $redisNamespace = "countries";

    public function __construct(private CountryService $countryService)
    {
        parent::__construct();
    }

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

            $countriesRequest = $this->mysql->query($statement);

            if (!$countriesRequest) {
                throw new DBException("Impossible de récupérer les pays.");
            }

            $countriesRaw = $countriesRequest->fetchAll();

            $this->redis->set($this->redisNamespace, json_encode($countriesRaw));

            foreach ($countriesRaw as $countryRaw) {
                $this->redis->set("{$this->redisNamespace}:{$countryRaw['iso']}", json_encode($countryRaw));
            }
        }

        $countries = array_map(
            fn(array $countryRaw) => $this->countryService->makeCountryFromDatabase($countryRaw),
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
        // Redis
        $countryRaw = json_decode($this->redis->get("{$this->redisNamespace}:{$iso}"), true);

        if (!$countryRaw) {
            $statement = "SELECT * FROM utils_pays  WHERE iso = :iso";

            $countryRequest = $this->mysql->prepare($statement);

            if (!$countryRequest) {
                throw new DBException("Impossible de récupérer le pays.");
            }

            $countryRequest->execute(["iso" => $iso]);
            $countryRaw = $countryRequest->fetch();

            if (!$countryRaw) return null;

            $this->redis->set("{$this->redisNamespace}:{$iso}", json_encode($countryRaw));
        }

        $country = $this->countryService->makeCountryFromDatabase($countryRaw);

        return $country;
    }
}
