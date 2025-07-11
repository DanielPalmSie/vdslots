<?php
namespace Licensed\Services;

require_once __DIR__ . '/../Traits/LoadOccupations.php';

use IT\Services\Traits\LoadFileTrait;
use LoadOccupations;

/**
 * Class OccupationService
 * @package Services
 */
class OccupationService
{
    use LoadFileTrait;
    use LoadOccupations;

    const OCCUPATIONS = 'occupations';
    const CONFIG_TAG = __DIR__ . '/../data/';

    /**
     * @var string
     */
    private string $cache_key = self::OCCUPATIONS;

    /**
     * @var array
     */
    private array $occupation_list = [];

    /**
     * @var object
     */
    private $user;

    /**
     * OccupationService constructor.
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
    public function getOccupationsInSelectedIndustry(string $country, string $industry): array
    {
        $cache_key = $this->getCacheKey() .'-'. $country .'-'. $industry;
        $cachedResponse = $this->getCache($cache_key);

        if (empty($cachedResponse[$industry])) {
            $data = $this->loadOccupations($country, $industry);
            foreach ($data as $key => $occupations_data) {
                $this->occupation_list[$key] = $occupations_data;
            }

            $this->setCache($cache_key, $this->occupation_list, $this->getCacheTime());

        }
        return $cachedResponse[$industry] ?? $this->occupation_list[$industry] ?? [];
    }
}
