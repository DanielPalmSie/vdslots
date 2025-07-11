<?php

namespace Videoslots\RgLimits\Validators;

use Videoslots\RgLimits\RgLimitsService;

interface RgLimitsValidatorInterface
{
    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @return array
     */
    public function validate(RgLimitsService $rgLimitsService): array;
}
