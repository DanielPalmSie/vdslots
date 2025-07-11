<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Factories;

use DBUser;
use Videoslots\RgLimits\RgLimitsService;
use Videoslots\Services\Renderer\Renderer;

final class RgLimitsServiceFactory
{
    /**
     * @param \DBUser $user
     * @param bool $isApi
     *
     * @return \Videoslots\RgLimits\RgLimitsService
     */
    public static function createRgLimitsServiceFactory(DBUser $user, bool $isApi = false): RgLimitsService
    {
        return new RgLimitsService(rgLimits(), $user, new Renderer(), $isApi);
    }
}
