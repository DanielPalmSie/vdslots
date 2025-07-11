<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use DBUser;
use Videoslots\RgLimits\RgLimitsService;

final class ResetablesBuilder implements RgLimitsBuilderInterface
{
    /**
     * @var string
     */
    public const TYPE_LOGIN = "login";

    /**
     * @var string
     */
    public const TYPE_DEPOSIT = "deposit";

    /**
     * @var string
     */
    public const TYPE_CUSTOMER_NET_DEPOSIT = "customer_net_deposit";

    public const GROUP = 'resettables';

    private RgLimitsService $rgLimitsService;

    /**
     * @var string|null
     */
    private ?string $type;

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @param string|null $type
     */
    public function __construct(RgLimitsService $rgLimitsService, ?string $type)
    {
        $this->rgLimitsService = $rgLimitsService;
        $this->type = $type;
    }

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        $resettables = $this->getResettables($rgLimitsService);
        $user = $rgLimitsService->getUser();
        $disableDepositFieldsOptions = lic('disableDepositFieldsOptions', [$user], $user);
        foreach ($resettables as $type) {
            if (
                in_array($type, $rgLimitsService->getRgLimits()->grouped_resettables)
                || $type == self::TYPE_LOGIN && ! lic('showLoginLimit', [], $rgLimitsService->getUser())
            ) {
                continue;
            }

            $data[$type] = $rgLimitsService->getLimitData($type, $type);
            $data[$type]['disableDepositFieldsOptions'] = $disableDepositFieldsOptions;

            if ($type === self::TYPE_DEPOSIT && $this->type === self::TYPE_DEPOSIT ) {
                $data[$type] = $this->formatDepositLimit($data[$type], $rgLimitsService->getUser());
            } elseif ($type === self::TYPE_CUSTOMER_NET_DEPOSIT) {
                $data[$type] = $this->formatCustomerNetDepositLimit($data[$type]);
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
        $resettables = $this->getResettables($rgLimitsService);
        foreach ($resettables as $type) {
            if (isset($data[$type])) {
                $section = $data[$type];
                $user = $rgLimitsService->getUser();
                $editableTimeSpans = $this->rgLimitsService->getRgLimits()->getTimeSpans($type);
                echo $rgLimitsService->getRenderer()->render('profile.rg_limits.resettable-limit', [
                    'headline' => $section['headline'],
                    'description' => $section['description'],
                    'data' => $data[$type],
                    'type' => $type,
                    'cooloff_period' => $rgLimitsService->getCooloffPeriod(),
                    'is_mobile' => phive()->isMobile(),
                    'time_spans' => $editableTimeSpans,
                    'time_spans_json' => json_encode($editableTimeSpans),
                    'canShowCrossBrandLimit' => $this->canShowCrossBrandLimit($type, $user),
                    'disableDepositFieldsOptions' => $data[$type]['disableDepositFieldsOptions'],
                ]);
            }
        }
    }

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    private function getResettables(RgLimitsService $rgLimitsService): array
    {
        $rgLimits = $rgLimitsService->getRgLimits();
        $listOfResettables = $rgLimits->resettable;
        $listOfResettablesToRemove = ['net_deposit'];
        foreach ($rgLimits->grouped_resettables as $groupList) {
            foreach ($groupList as $resettable) {
                array_push($listOfResettablesToRemove, $resettable);
            }
        }

        return array_diff($listOfResettables, $listOfResettablesToRemove);
    }

    private function formatDepositLimit(array $depositLimit, DBUser $user): array
    {
        $defaultLimitsByType = lic('getDefaultLimitsByType', [$user, 'deposit'], $user);
        $columnsToRemove = ['cooloffPeriod', 'disableDepositFieldsOptions'];
        $depositLimit['group'] = self::GROUP;
        foreach ($depositLimit['limits'] ?? [] as $key => $limit) {
            $limit['type'] = $limit['type'] ?? $depositLimit['type'];
            $depositLimit['limits'][$key] = $this->setWhiteListedColumns($limit, $defaultLimitsByType);
        }

        foreach ($columnsToRemove as $column) {
            unset($depositLimit[$column]);
        }

        return $depositLimit;
    }

    private function formatCustomerNetDepositLimit(array $customerNetDepositLimit): array
    {
        if (empty($customerNetDepositLimit['limits']) || empty($customerNetDepositLimit['type'])) {
            return $customerNetDepositLimit;
        }

        $editableTimeSpans = $this->rgLimitsService->getRgLimits()->getTimeSpans($customerNetDepositLimit['type']);
        foreach ($customerNetDepositLimit['limits'] as $key => $limit) {
            $timeSpan = $limit['time_span'] ?? null;
            if (!in_array($timeSpan, $editableTimeSpans, true)) {
                unset($customerNetDepositLimit['limits'][$key]);
            }
        }
        return $customerNetDepositLimit;
    }

    private function setWhiteListedColumns(array $limits, array $defaultLimitsByType): array
    {
        return [
            'resets_at' => $limits['resets_at'],
            'time_span' => $limits['time_span'],
            'type' => $limits['type'],
            'values' => $this->setValues($limits, $defaultLimitsByType),
        ];
    }

    private function setValues(array $limits, array $defaultLimits)
    {
        $isLimitMissing = empty($limits['values']['new']) && empty($limits['values']['cur']);
        $formattedDefaultLimit = $this->rgLimitsService
            ->getRgLimits()
            ->prettyLimit($limits['type'], $defaultLimits[$limits['time_span']], true);

        $newValue = $isLimitMissing ? $formattedDefaultLimit : $limits['new_lim'];
        $remValue = $limits['values']['rem'] ?? '';
        $curValue = $limits['values']['cur'] ?? '';

        return [
            'rem' => (string) $remValue,
            'new' => (string) $newValue,
            'cur' => (string) $curValue,
        ];
    }

    /**
     * @param string $type
     * @param \DBUser $user
     *
     * @return bool
     */
    private function canShowCrossBrandLimit(string $type, DBUser $user = null): bool
    {
        return ! empty(lic('showCrossBrandLimitExtra', [$type, $user], $user));
    }
}
