<?php

namespace Videoslots\RgLimitsPopup\Factories;

use Videoslots\RgLimitsPopup\Services\RgLimitsDataService;

final class RgLimitsDataServiceFactory
{
    /**
     * @return \Videoslots\RgLimitsPopup\Services\RgLimitsDataService
     */
    public function create(): RgLimitsDataService
    {
        return new RgLimitsDataService();
    }
}
