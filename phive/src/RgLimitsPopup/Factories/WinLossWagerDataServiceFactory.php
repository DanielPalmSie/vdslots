<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Factories;

use Videoslots\RgLimitsPopup\Services\WinLossWagerDataService;

final class WinLossWagerDataServiceFactory
{
    /**
     * @return \Videoslots\RgLimitsPopup\Services\WinLossWagerDataService
     */
    public function create(): WinLossWagerDataService
    {
        return new WinLossWagerDataService();
    }
}
