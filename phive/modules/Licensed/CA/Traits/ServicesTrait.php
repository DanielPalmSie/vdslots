<?php
namespace CA\Traits;

use CA\Services\ProvincesService;
use CA\Services\IndustryService;
/**
 * Trait ServicesTrait
 * @package IT\Traits
 */
trait ServicesTrait
{


    /**
     * Return a CountriesService instance
     * @return ProvincesService
     */
    protected function getProvinceService(): ProvincesService
    {
        return new ProvincesService();
    }

    protected function getIndustryService(): IndustryService
    {
        return new IndustryService();
    }

}
