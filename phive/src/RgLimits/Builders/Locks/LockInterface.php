<?php

namespace Videoslots\RgLimits\Builders\Locks;

use Videoslots\RgLimits\RgLimitsService;

interface LockInterface
{
    /**
     * @return array
     */
    public function buildData(): array;

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return string
     */
    public function render(array $data, RgLimitsService $rgLimitsService): string;
}
