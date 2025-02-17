<?php

// Path: api/src/Repository/CountryRepository.php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\DB\DBException;
use App\Entity\Country;
use App\Service\CountryService;

/**
 * @phpstan-import-type CountryArray from \App\Entity\Country
 */
final class CountryRepository extends Repository
{
    /** @var \ReflectionClass<Country> */
    private \ReflectionClass $reflector;

    private string $redisNamespace = "countries";

    public function __construct(private CountryService $countryService)
    {
        $this->reflector = new \ReflectionClass(Country::class);
    }

    public function countryExists(string $iso): bool
    {
        return $this->mysql->exists('utils_pays', $iso, 'iso');
    }

    /**
     * Fetches all countries.
     * 
     * @return Collection<Country> All fetched countries.
     */
    public function fetchAll(): Collection
    {
        // Redis
        $redisValue = $this->redis->get($this->redisNamespace);
        $countriesRaw = \is_string($redisValue) ? \json_decode($redisValue, true) : null;

        if (!\is_array($countriesRaw)) {
            $statement = "SELECT * FROM utils_pays ORDER BY nom";

            $countriesRequest = $this->mysql->query($statement);

            if (!$countriesRequest) {
                throw new DBException("Impossible de récupérer les pays.");
            }

            /** @var CountryArray[] */
            $countriesRaw = $countriesRequest->fetchAll();

            $this->redis->set($this->redisNamespace, \json_encode($countriesRaw));

            foreach ($countriesRaw as $countryRaw) {
                $this->redis->set("{$this->redisNamespace}:{$countryRaw['iso']}", \json_encode($countryRaw));
            }
        }

        /** @var CountryArray[] $countriesRaw */

        $countries = \array_map(
            fn($countryRaw) => $this->countryService->makeCountryFromDatabase($countryRaw),
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
        /** @var array<string, Country> */
        static $cache = [];

        if (isset($cache[$iso])) {
            return $cache[$iso];
        }

        if (!$this->countryExists($iso)) {
            return null;
        }

        /** @var Country */
        $country = $this->reflector->newLazyProxy(
            function () use ($iso) {
                try {
                    // Redis
                    $redisValue = $this->redis->get("{$this->redisNamespace}:{$iso}");
                    $countryRaw = \is_string($redisValue) ? \json_decode($redisValue, true) : null;

                    if (!\is_array($countryRaw)) {
                        $statement = "SELECT * FROM utils_pays  WHERE iso = :iso";

                        $countryRaw = $this->mysql
                            ->prepareAndExecute($statement, ["iso" => $iso])
                            ->fetch();

                        $this->redis->set("{$this->redisNamespace}:{$iso}", \json_encode($countryRaw));
                    }

                    /** @var CountryArray $countryRaw */

                    return $this->countryService->makeCountryFromDatabase($countryRaw);
                } catch (\Throwable $th) {
                    throw new DBException("Impossible de récupérer le pays.", previous: $th);
                }
            }
        );

        $this->reflector->getProperty('iso')->setRawValueWithoutLazyInitialization($country, $iso);

        $cache[$iso] = $country;

        return $country;
    }
}
