<?php
namespace IT\Services;

use IT\Services\Traits\LoadFileTrait;

/**
 * Class ProvincesService
 * @package IT\Services
 */
class ProvincesService
{
    use LoadFileTrait;

    const ITALIAN_SUPPRESSED_MUNICIPALITY_CACHE_KEY = 'italian_suppressed_municipality_cache_key';
    const ITALIAN_PREVIOUS_DENOMINATIONS_CACHE_KEY = 'italian_previous_denominations_cache_key';
    const CONFIG_TAG = __DIR__ . '/../data/';
    const SUPPRESSED_MUNICIPALITY_FILE_NAME = 'suppressed_municipality.min.json';
    const PREVIOUS_DENOMINATIONS_FILE_NAME = 'previous_denominations.min.json';

    /**
     * @var string
     */
    private $cache_key = self::ITALIAN_SUPPRESSED_MUNICIPALITY_CACHE_KEY;

    /**
     * @var array
     */
    private $provinces_data;

    /**
     * @var array
     */
    private $suppressed_municipality_data = [];

    /**
     * @var array
     */
    private $previous_denominations_data = [];

    /**
     * @var string
     */
    private $file_name = self::SUPPRESSED_MUNICIPALITY_FILE_NAME;

    /**
     * @var object
     */
    private $user;

    /**
     * ProvincesService constructor.
     * @param object $user
     */
    public function __construct($user = null)
    {
        $this->user = cu($user);
    }

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
    public function loadProvincesFromDataBase(): array
    {
        return lic('getByTags', ['provinces', true, 'it'], $this->user)['provinces'] ?? [];
    }

    /**
     * @param array $list
     * @return array
     * @throws \Exception
     */
    protected function load(array &$list = null): array
    {
        if (empty($list)) {
            $list = $this->loadFromCache();
        }
        return $list;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getSuppressedMunicipality(): array
    {
        $this->setLoadConfig(
            self::SUPPRESSED_MUNICIPALITY_FILE_NAME,
            self::ITALIAN_SUPPRESSED_MUNICIPALITY_CACHE_KEY
        );
        return $this->load($this->suppressed_municipality_data);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getPreviousDenominations(): array
    {
        $this->setLoadConfig(
            self::PREVIOUS_DENOMINATIONS_FILE_NAME,
            self::ITALIAN_PREVIOUS_DENOMINATIONS_CACHE_KEY
        );
         return $this->load($this->previous_denominations_data);
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function loadProvinces(): array
    {
        if (empty($this->provinces_data)) {
            $this->provinces_data = $this->loadProvincesFromDataBase();
        }
        return $this->provinces_data;
    }

   /**
     * @param string $city_name
     * @return array
     * @throws \Exception
     */
    public function getProvinceByCityName(string $city_name): array
    {
        $cities = $this->getProvinceBySelectedKeyList('denomination');
        return $cities[$city_name] ?? [];
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getUserRegionCode(): int
    {
        $province_list = $this->getProvinceBySelectedKeyList('iso_province');
        return (int)($province_list[$this->user->getSetting('main_province')]['region_code'] ?? 0);
    }

    /**
     * @param string $key
     * @return array
     * @throws \Exception
     */
    public function getProvinceBySelectedKeyList(string $key): array
    {
        $cache_key = $this->getCacheKey() . 'getProvinceBySelectedKeyList' . $key;
        $province_list = $this->getCache($cache_key);
        if (empty($municipality_codes)) {
            $data = $this->loadProvinces();
            $province_list = [];
            foreach ($data as $city => $province_data) {
                $province_list[$province_data[$key] ?? $city] = [
                    'region_code' => $province_data['region_code'],
                    'automotive_code' => $province_data['iso_province'],
                    'denomination' => $city,
                    'municipal_territorial_unit' => $province_data['province']
                ];
            }

            $this->setCache($cache_key, $province_list, $this->getCacheTime());
        }

        return $province_list;
    }

    /**
     * @param bool $return_only_denomination
     * @return array
     * @throws \Exception
     */
    public function getMunicipalityByProvinceList(bool $return_only_denomination = true): array
    {
        $cache_key = $this->getCacheKey() . 'getMunicipalityByProvinceList' . $return_only_denomination;
        $municipality_by_province_list = $this->getCache($cache_key);
        if (empty($cMunicipality_by_province_list)) {
            $municipality_codes = $this->getProvinceBySelectedKeyList('cadastral_code_municipality');
            $municipality_by_province_list = [];
            foreach ($municipality_codes as $municipality_code) {
                if (!array_key_exists($municipality_code['automotive_code'], $municipality_by_province_list)) {
                    $municipality_by_province_list[$municipality_code['automotive_code']] = [];
                }
                $municipality_by_province_list[$municipality_code['automotive_code']][$municipality_code['denomination']] =
                    $return_only_denomination ? $municipality_code['denomination'] : $municipality_code;
            }
            $this->setCache($cache_key, $municipality_by_province_list, $this->getCacheTime());
        }

        asort($municipality_by_province_list);
        return $municipality_by_province_list;
    }

    /**
     * @param bool $find_all
     * @return array
     * @throws \Exception
     */
    public function getProvinceList(bool $find_all = false): array
    {
        $cache_key = $this->getCacheKey() . 'getProvinceList' . $find_all;
        $province_list = $this->getCache($cache_key);
        if (empty($province_list)) {
            $municipality_by_province_list =
                $find_all ?
                    $this->getAllMunicipalityByProvinceList(false) :
                    $this->getMunicipalityByProvinceList(false);

            $province_list = [];
            foreach ($municipality_by_province_list as $province => $municipality) {
                if (! empty(current($municipality)['municipal_territorial_unit'])) {
                    $province_list[$province] = current($municipality)['municipal_territorial_unit'];
                    continue;
                }
                if (empty($province)) {
                    continue;
                }
                $province_list[$province] = ucfirst(strtolower(current($municipality)['automotive_code']));

            }
            
            $this->setCache($cache_key, $province_list, $this->getCacheTime());
        }

        return $province_list;
    }

    /**
     * @param bool $return_only_denomination
     * @return array
     * @throws \Exception
     */
    public function getMunicipalityWithSuspendedByProvinceList(bool $return_only_denomination = true): array
    {
        $cache_key = $this->getCacheKey() . 'getMunicipalityWithSuspendedByProvinceList' . $return_only_denomination;
        $municipality_by_province_list = $this->getCache($cache_key);
        if (empty($municipality_by_province_list)) {
            $province_list = $this->getProvinceBySelectedKeyList('automotive_code');
            $municipality_by_province_list = $this->getMunicipalityByProvinceList($return_only_denomination);
            $suppressed_municipality = $this->getSuppressedMunicipality();

            foreach ($suppressed_municipality as $municipality) {
                if (array_key_exists($municipality['automotive_code'], $province_list)) {
                    $municipality_by_province_list[$municipality['automotive_code']][$municipality['denomination']] =
                        $return_only_denomination ? $municipality['denomination'] : $municipality;
                    continue;
                }
                $municipality_by_province_list[$municipality['automotive_code']][$municipality['denomination']] =
                    $return_only_denomination ? $municipality['denomination'] : $municipality;
            }
            $this->setCache($cache_key, $municipality_by_province_list, $this->getCacheTime());
        }

        return $municipality_by_province_list;
    }

    /**
     * @param bool $return_only_denomination
     * @return array
     * @throws \Exception
     */
    public function getAllMunicipalityByProvinceList(bool $return_only_denomination = true): array
    {
        $cache_key = $this->getCacheKey() . 'getAllMunicipalityByProvinceList' . $return_only_denomination;
        $municipality_province_list = $this->getCache($cache_key);
        if (empty($municipality_province_list)) {
            $provinces_codes_list = $this->getProvinceBySelectedKeyList('province_code');
            $municipality_province_list = $this->getMunicipalityWithSuspendedByProvinceList($return_only_denomination);
            foreach ($this->getPreviousDenominations() as $previous_denomination) {
                $province = $provinces_codes_list[(int)$previous_denomination['province_code_old']]['automotive_code'];
                $municipality_province_list[$province][$previous_denomination['denomination_old']] =
                    $return_only_denomination ? $previous_denomination['denomination_old'] : $previous_denomination;
            }
            $this->setCache($cache_key, $municipality_province_list, $this->getCacheTime());
        }
        asort($municipality_province_list);
        return $municipality_province_list;
    }

    /**
     * @param string $province
     * @return array
     */
    public function getPayloadToChangeProvince(string $province): array
    {
        return [
            'account_code' => $this->user->getData('id'),
            'province' => $province,
            'transaction_id' => time()
        ];
    }
}