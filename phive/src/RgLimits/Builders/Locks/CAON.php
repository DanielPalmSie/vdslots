<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders\Locks;

use Videoslots\RgLimits\RgLimitsService;

final class CAON implements LockInterface
{
    /**
     * @return array
     */
    public function buildData(): array
    {
        $data = [
            'bullet_options' => $this->createBulletOptions(),
            'other_option' => [
                'alias' => 'other',
                'name' => 'other',
                'value' => '',
                'checked' => false,
            ],
            'input' => [
                'name' => 'lock-hours',
            ],
        ];

        return $data;
    }

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return string
     */
    public function render(array $data, RgLimitsService $rgLimitsService): string
    {
        return $rgLimitsService->getRenderer()->render('profile.rg_limits.lock-sections.caon', [
            'bullet_options' => $data['bullet_options'],
            'other_option' => $data['other_option'],
            'input' => $data['input'],
        ]);
    }

    /**
     * @return array
     */
    private function createBulletOptions(): array
    {
        $options = array_map(fn ($num) => [
            'alias' => 'exclude.' . $num . '.hours',
            'value' => (string) $num,
            'checked' => false,
        ], [24, 168, 744, 1460, 2190]);

        $options[0]['checked'] = true;

        return $options;
    }
}
