<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders\Locks;

use Videoslots\RgLimits\RgLimitsService;

final class DefaultLock implements LockInterface
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
        return $rgLimitsService->getRenderer()->render('profile.rg_limits.lock-sections.mt', [
            'input' => $data['input'],
        ]);
    }
}
