<?php
namespace Licensed\Services;

require_once __DIR__ . '/../Traits/LoadIndustries.php';

use IT\Services\Traits\LoadFileTrait;
use Licensed\Traits\LoadIndustries;

/**
 * Class IndustryService
 * @package Services
 */
class IndustryService
{
    use LoadFileTrait;
    use LoadIndustries;

    const INDUSTRIES = 'industries';
    const CONFIG_TAG = __DIR__ . '/../data/';

    /**
     * @var string
     */
    private string $cache_key = self::INDUSTRIES;

    /**
     * @var array
     */
    private array $industry_list;

    /**
     * @var object
     */
    private $user;

    /**
     * IndustryService constructor.
     *
     * @param object $user
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    protected function getCacheKey(): string
    {
        return $this->cache_key;
    }

    /**
     * Will fetch list of province from cache, if not found fetch from DB and store in cache.
     *
     * @return array
     */
    public function getIndustryList(string $country, string $configTag = 'industries'): array
    {
        $cache_key = $this->getCacheKey() .'-'. $country;
        $this->industry_list = $this->getCache($cache_key);

        if (empty($this->industry_list)) {
            $data = $this->loadIndustries($country, $configTag);
            foreach ($data as $industry_data) {
                $this->industry_list[$industry_data['industry']] = $industry_data['industry'];
            }

            $this->setCache($cache_key, $this->industry_list, $this->getCacheTime());
        }
        return $this->industry_list;
    }
}
