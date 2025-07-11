<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use Videoslots\RgLimits\RgLimitsService;

final class GroupedResetablesBuilder implements RgLimitsBuilderInterface
{
    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        foreach ($rgLimitsService->getRgLimits()->grouped_resettables as $groupname => $groupList) {
            foreach ($groupList as $type) {
                $typeSplit = explode('-', $type);
                if (count($typeSplit) > 1 && lic('getLicSetting', [$typeSplit[1]]) !== true) {
                    continue;
                }

                $data[$type] = $rgLimitsService->getLimitData($type, $groupname);
                if (lic('isSportsbookEnabled') || lic('isPoolxEnabled')) {
                    $data[$type]['subheadline'] = $type . ".section.limit.headline";
                }
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return void
     */
    public function render(array $data, RgLimitsService $rgLimitsService): void
    {
        $rgLimits = $rgLimitsService->getRgLimits();
        $groupedResettables = $rgLimits->grouped_resettables;
        foreach ($groupedResettables as $groupname => $groupList) {
            if (! isset($data[$groupname])) {
                continue;
            }

            $section = $data[$groupname];
            $editableTimeSpans = $rgLimitsService->getRgLimits()->getTimeSpans($groupname);
            echo $rgLimitsService->getRenderer()->render('profile.rg_limits.grouped-resettable-limit', [
                'headline' => $section['headline'],
                'description' => $section['description'],
                'data' => $data,
                'groupName' => $groupname,
                'list_of_types' => $groupList,
                'cooloff_period' => $rgLimitsService->getCooloffPeriod(),
                'is_mobile' => phive()->isMobile(),
                'time_spans' => $editableTimeSpans,
                'time_spans_json' => json_encode($editableTimeSpans),
            ]);
        }
    }
}
