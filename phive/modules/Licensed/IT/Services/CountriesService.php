<?php
namespace IT\Services;

use IT\Services\Traits\LoadFileTrait;

/**
 * Class CountriesService
 * @package IT\Services
 */
class CountriesService
{
    use LoadFileTrait;

    const ITALIAN_COUNTRY_CEASED_CACHE_KEY = 'italian_ceased_country_cache_key';
    const CONFIG_TAG = __DIR__ . '/../data/';
    const COUNTRY_CEASED_FILE_NAME = 'country_ceased.min.json';

    /**
     * @var string
     */
    private $cache_key = self::ITALIAN_COUNTRY_CEASED_CACHE_KEY;

    /**
     * @var string
     */
    private $file_name = self::COUNTRY_CEASED_FILE_NAME;

    /**
     * @var array
     */
    private $countries = [];

    /**
     * @var array
     */
    private $ceased_countries = [];

    /**
     * @return string
     */
    protected function getCacheKey(): string
    {
        return $this->cache_key;
    }

    /**
     * @return string
     */
    protected function getPath(): string
    {
        return realpath(self::CONFIG_TAG . $this->file_name);
    }

    /**
     * @param string $config_name
     * @param string $cache_key
     */
    private function setLoadConfig(string $config_name, string $cache_key)
    {
        $this->cache_key = $cache_key;
        $this->file_name = $config_name;
    }

    /**
     * @return array
     */
    private function getCountryFilter(): array
    {
        return [
            'iso',
            'name',
            'printable_name',
            'iso3',
            'numcode',
        ];
    }

    /**
     * @param array $countries_data_base
     * @return array
     */
    private function filterCountriesFields(array $countries_data_base): array
    {
        $countries = [];
        foreach ($countries_data_base as $key => $country_data_base) {
            $countries[$key] = phive()->orderKeysBy($country_data_base, $this->getCountryFilter());
        }
        return $countries;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getCountries(): array
    {
        return $this->filterCountriesFields($this->getCountriesFromDataBase());
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getCeasedCountries(): array
    {
        $this->setLoadConfig(
            self::COUNTRY_CEASED_FILE_NAME,
            self::ITALIAN_COUNTRY_CEASED_CACHE_KEY
        );
        return $this->load($this->ceased_countries);
    }

    /**
     * @param array $list
     * @return array
     * @throws \Exception
     */
    protected function load(array &$list): array
    {
        if (empty($list)) {
            $list = $this->loadFromCache();
        }
        return $list;
    }

    /**
     * @return array
     */
    protected function getCountriesFromDataBase(): array
    {
        return phive('Cashier')->getBankCountries('', true);
    }

    /**
     * @param $country_data
     * @return array
     */
    private function formatCountryReturn(array $country_data): array
    {
        $map_key_old = [
            'iso',
            'name',
            'name',
            'iso3',
            'territory_state',
        ];
        $map = array_combine($this->getCountryFilter(), $map_key_old);
        $country_data_filtered = phive()->mapit($map, $country_data);
        $country_data_filtered['name'] = strtoupper($country_data['name']);

        return $country_data_filtered;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAllCountries(): array
    {
        $cache_key = $this->getCacheKey() . "getAllCountries";
        $countries = $this->getCache($cache_key);
        if (empty($countries)) {
            $countries = $this->getCountries();
            $ceased_countries = $this->getCeasedCountries();
            foreach ($ceased_countries as $ceased_country) {
                $countries[] = $this->formatCountryReturn($ceased_country);
            }

            $this->setCache($cache_key, $countries, $this->getCacheTime());
        }

        return $countries;
    }
}