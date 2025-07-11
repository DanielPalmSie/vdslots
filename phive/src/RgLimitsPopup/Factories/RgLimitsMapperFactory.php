<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Factories;

use Videoslots\RgLimitsPopup\Mappers\RgLimitsMapper;

class RgLimitsMapperFactory
{
    /**
     * @param \DBUser $user
     *
     * @return \Videoslots\RgLimitsPopup\Mappers\RgLimitsMapper
     */
    public function create(\DBUser $user): RgLimitsMapper
    {
        return new RgLimitsMapper(new \RgLimits(), $user);
    }
}
