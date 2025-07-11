<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders\Locks;

use Videoslots\RgLimits\RgLimitsService;

final class DK implements LockInterface
{
    /**
     * @return array[]
     */
    public function buildData(): array
    {
        $data = [
            'input' => [
                'name' => 'lock-hours',
            ],
            'labels' => [
                'or' => 'or',
            ],
            'checkbox_option' => [
                'alias' => 'permanently.self-excluded',
                'name' => 'indefinitely',
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
        return $rgLimitsService->getRenderer()->render('profile.rg_limits.lock-sections.dk', [
            'checkbox_option' => $data['checkbox_option'],
            'input' => $data['input'],
            'labels' => $data['labels'],
        ]);
    }
}
