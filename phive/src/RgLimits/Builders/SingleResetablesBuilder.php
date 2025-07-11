<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use Videoslots\RgLimits\RgLimitsService;

final class SingleResetablesBuilder implements RgLimitsBuilderInterface
{
    /**
     * @var string
     */
    public const TYPE_BALANCE = "balance";

    /**
     * @var string
     */
    public const TYPE_TIMEOUT = "timeout";

    /**
     * @var string
     */
    public const TYPE_BETMAX = "betmax";

    /**
     * @var string
     */
    public const TYPE_RC = "rc";

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        if (lic('hasBalanceTypeLimit')) {
            $data[self::TYPE_BALANCE] = $rgLimitsService->getLimitData(self::TYPE_BALANCE);
        }

        $data[self::TYPE_TIMEOUT] = $rgLimitsService->getLimitData(self::TYPE_TIMEOUT);
        $data[self::TYPE_BETMAX] = $rgLimitsService->getLimitData(self::TYPE_BETMAX);
        $data[self::TYPE_BETMAX]['bullet_options'] = $this->prepareBetmaxBulletOptions($data[self::TYPE_BETMAX]);
        $rc_data = lic('getRcConfigs', [], $rgLimitsService->getUser());

        if (! empty(phive('Casino')->startAndGetRealityInterval())) {
            $data[self::TYPE_RC] = $rgLimitsService->getLimitData(self::TYPE_RC);
            $data = $this->setMinMaxStepValues(self::TYPE_RC, $rc_data, $data);
        }

        $data = $this->setMinMaxStepValues(self::TYPE_TIMEOUT, $rc_data, $data);

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
        $cooloffPeriod = $rgLimitsService->getCooloffPeriod();

        foreach (self::getResetableLimits() as $type) {
            if (! isset($data[$type])) {
                continue;
            }

            $section = $data[$type];

            $limitParts = $this->getLimitParts($type);

            $bulletOptions = [];
            if ($type == self::TYPE_BETMAX) {
                $bulletOptions = $this->prepareBetmaxBulletOptions($section);
            }

            $user = $rgLimitsService->getUser();
            if ((licSetting('SHOW_REALITY_CHECK_RESPONSIBLE_GAMING_PAGE', $user) === false) && $section['headline'] === 'rc.limit.headline') {
                unset($section, $bulletOptions, $type, $cooloffPeriod, $limitParts);
            }

            echo $rgLimitsService->getRenderer()->render('profile.rg_limits.single-resetables', [
                'headline' => $section['headline'],
                'description' => $section['description'],
                'data' => $section,
                'bullet_options' => $bulletOptions,
                'type' => $type,
                'cooloff_period' => $cooloffPeriod,
                'limit_parts' => $limitParts,
                'disp_unit' => $data[$type]['disp_unit'],
            ]);
        }
    }

    /**
     * @return string[]
     */
    public static function getResetableLimits(): array
    {
        return [
            self::TYPE_BALANCE,
            self::TYPE_TIMEOUT,
            self::TYPE_BETMAX,
            self::TYPE_RC,
        ];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function prepareBetmaxBulletOptions(array $data): array
    {
        $bulletOptions = [];
        if (isset($data['limits'][0])) {
            $timespan = $data['limits'][0]['time_span'];
            $bulletOptions = [
                [
                    'alias' => 'rg.none.cooloff',
                    'value' => 'na',
                    'checked' => $timespan == 'na',
                ],
                [
                    'alias' => 'rg.day.cooloff',
                    'value' => 'day',
                    'checked' => $timespan == 'day',
                ],
                [
                    'alias' => 'rg.week.cooloff',
                    'value' => 'week',
                    'checked' => $timespan == 'week',
                ],
                [
                    'alias' => 'rg.month.cooloff',
                    'value' => 'month',
                    'checked' => $timespan == 'month',
                ],
            ];
        }

        return $bulletOptions;
    }

    /**
     * @param string $type
     *
     * @return string[]
     */
    private function getLimitParts(string $type): array
    {
        if ($type == self::TYPE_BETMAX) {
            $limitParts = ['active.limit', 'new.limit'];
        } else {
            $limitParts = ['active.limit', 'remaining', 'new.limit'];
        }

        return $limitParts;
    }

    /**
     * @param string $type
     * @param array $rc_data
     * @param array $data
     *
     * @return array
     */
    private function setMinMaxStepValues(string $type, array $rc_data, array $data): array
    {
        $data[$type]['limits'][0]['values']['min'] = $rc_data['rc_min_interval'];
        $data[$type]['limits'][0]['values']['max'] = $rc_data['rc_max_interval'];
        $data[$type]['limits'][0]['values']['step'] = $rc_data['rc_steps'];

        return $data;
    }
}
