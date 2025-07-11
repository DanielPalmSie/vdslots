<?php

declare(strict_types=1);

namespace Videoslots\RgLimitsPopup\Services;

use Videoslots\RgLimits\Settings;

final class RgLimitsDataService
{
    /**
     * @param \DBUser $user
     *
     * @return array
     */
    public function getRgLimitsData(\DBUser $user): array
    {
        $rg = phive('Licensed')->rgLimits();
        $rgInfoSetting = licSetting('rg_info', $user);
        $allowedRgTypes = $rgInfoSetting['allowed_types_in_popup'] ?? $rg->resettable;
        $timeSpans = $rg->time_spans;
        $limits = $rg->getGrouped($user, $allowedRgTypes, true);

        return $this->formatRgLimitsTypes($allowedRgTypes, $timeSpans, $limits);
    }

    /**
     * @param array $types
     * @param array $timeSpans
     * @param array $limits
     *
     * @return array
     */
    private function formatRgLimitsTypes(array $types, array $timeSpans, array $limits): array
    {
        $result = [];

        foreach ($types as $type) {
            if ($limits[$type] === null || $type === Settings::LIMIT_NET_DEPOSIT) {
                continue;
            }
            foreach ($timeSpans as $timeSpan) {
                if ($type === Settings::LIMIT_BALANCE) {
                    $result[$type] = $limits[$type];
                } else {
                    $result[$type][$timeSpan] = $limits[$type][$timeSpan];
                }
            }
        }

        return $result;
    }
}
