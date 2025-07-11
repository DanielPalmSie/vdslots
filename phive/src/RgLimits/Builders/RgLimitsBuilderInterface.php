<?php

namespace Videoslots\RgLimits\Builders;

use Videoslots\RgLimits\RgLimitsService;

interface RgLimitsBuilderInterface
{
    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array;

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return void
     */
    public function render(array $data, RgLimitsService $rgLimitsService): void;
}
