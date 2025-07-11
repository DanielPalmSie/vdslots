<?php

namespace CA\Services;

require_once __DIR__ . '/../Traits/LoadIndustries.php';

use IT\Services\Traits\LoadFileTrait;
use LoadIndustries;

/**
 * Class CountriesService
 * @package CA\Services
 */
class IndustryService
{
    use LoadFileTrait;
    use LoadIndustries;

    const CANADIAN_INDUSTRIES = 'canadian_industries';
    const CONFIG_TAG = __DIR__ . '/../data/';

    /**
     * @var string
     */
    private string $cache_key = self::CANADIAN_INDUSTRIES;

    /**
     * @var array
     */
    private array $industry_list;

    /**
     * @var array
     */
    private array $industries_data;


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
    public function getIndustryList(): array
    {
        if (empty($this->industry_list)) {
            $data = $this->loadIndustries('ca');
            $this->industry_list = [];
            foreach ($data as $industry_data) {
                $this->industry_list[$industry_data['industry']] = $industry_data['industry'];
            }

        }
        return $this->industry_list;
    }
}
