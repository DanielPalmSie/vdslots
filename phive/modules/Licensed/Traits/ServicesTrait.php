<?php
namespace Licensed\Traits;

use Licensed\Services\IndustryService;
//use OccupationService;
use Licensed\Services\OccupationService;

require_once __DIR__ . '/../Services/OccupationService.php';
require_once __DIR__ . '/../Services/IndustryService.php';

/**
 * Trait ServicesTrait
 * @package IT\Traits
 */
trait ServicesTrait
{

    /**
     * Return a IndustryService instance
     * @return IndustryService
     */
    protected function getIndustryServiceInstance($user): IndustryService
    {
        return new IndustryService($user);
    }

    /**
     * Return a OccupationService instance
     * @return OccupationService
     */
    protected function getOccupationService($user): OccupationService
    {
        return new OccupationService($user);
    }

}
