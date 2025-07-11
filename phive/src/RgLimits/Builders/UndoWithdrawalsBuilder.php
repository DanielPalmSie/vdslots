<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use Videoslots\RgLimits\Builders\Helpers\BuilderHelpers;
use Videoslots\RgLimits\RgLimitsService;

final class UndoWithdrawalsBuilder implements RgLimitsBuilderInterface
{
    use BuilderHelpers;

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        if (empty(lic('licSetting', ['undo_withdrawals']))) {
            return $data;
        }

        $optedIn = empty($rgLimitsService->getUser()->getSetting('undo_withdrawals_optout'));

        $data['undo_withdrawal_optout'] = [
            'type' => 'undo_withdrawal_optout',
            'bullet_options' => [
                [
                    'name' => 'undo_withdrawals-yes',
                    'alias' => 'rg.undo.withdrawals.opt.in',
                    'checked' => $optedIn,
                    'value' => 1,
                ],
                [
                    'name' => 'undo_withdrawals-no',
                    'alias' => 'rg.undo.withdrawals.opt.out',
                    'checked' => ! $optedIn,
                    'value' => ! $optedIn,
                ],
            ],
            'buttons' => [
                [
                    'type' => 'save',
                    'alias' => 'save',
                ],
            ],
            'headline' => 'rg.undo.withdrawals.headline',
            'description' => [
                'rg.undo.withdrawals.info.html',
            ],
        ];

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
        if (isset($data['undo_withdrawal_optout'])) {
            $section = $data['undo_withdrawal_optout'];
            echo $rgLimitsService->getRenderer()->render('profile.rg_limits.undo_withdrawals_optout', [
                'headline' => $section['headline'],
                'description' => $section['description'],
                'bullet_options' => $section['bullet_options'],
                'buttons' => $this->groupButtonsByType($section['buttons']),
            ]);
        }
    }
}
