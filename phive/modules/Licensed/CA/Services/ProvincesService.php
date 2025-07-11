<?php

namespace CA\Services;

require_once __DIR__ . '/../../Traits/LoadProvinces.php';

use IT\Services\Traits\LoadFileTrait;
use LoadProvinces;

/**
 * Class CountriesService
 * @package CA\Services
 */
class ProvincesService
{
    use LoadFileTrait;
    use LoadProvinces;

    const CANADIAN_PROVINCES = 'canadian_provinces';
    const CONFIG_TAG = __DIR__ . '/../data/';

    /**
     * @var string
     */
    private string $cache_key = self::CANADIAN_PROVINCES;

    /**
     * @var array
     */
    private array $provinces_data;

    /**
     * @var object
     */
    private $user;

    /**
     * ProvincesService constructor.
     *
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
     * @param array $list
     * @return array
     * @throws \Exception
     */
    protected function load(array &$list = []): array
    {
        if (empty($list)) {
            $list = $this->loadFromCache();
        }

        return $list;
    }

    /**
     * Will fetch list of province from cache, if not found fetch from DB and store in cache.
     *
     * @return array
     */
    public function getProvinceList(): array
    {
        $cache_key = $this->getCacheKey();
        $province_list = $this->getCache($cache_key);

        if (empty($province_list)) {
            $data = $this->loadProvinces('ca');
            $province_list = [];
            foreach ($data as $province_data) {
                $province_list[$province_data['iso_code']] = $province_data['province'];
            }
            $this->setCache($cache_key, $province_list, $this->getCacheTime());
        }

        return $province_list;
    }
}