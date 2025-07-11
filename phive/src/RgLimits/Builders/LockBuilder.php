<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use Videoslots\RgLimits\Builders\Helpers\BuilderHelpers;
use Videoslots\RgLimits\Builders\Locks\LockInterface;
use Videoslots\RgLimits\RgLimitsService;

final class LockBuilder implements RgLimitsBuilderInterface
{
    use BuilderHelpers;

    /**
     * @var string
     */
    public const TYPE_LOCK = "lock";

    /**
     * @var \Videoslots\RgLimits\Builders\Locks\LockInterface
     */
    private LockInterface $lockBuilder;

    public function __construct()
    {
        $this->lockBuilder = lic('createLockBuilder');
    }

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        $user = $rgLimitsService->getUser();
        if (empty(lic('hasRgSection', ['lock'], $user))) {
            return $data;
        }

        $lockStringAliases = lic('getLockAccountMessages', [], $user);
        $data[self::TYPE_LOCK] = [
            'type' => self::TYPE_LOCK,
            'form' => $this->lockBuilder->buildData($rgLimitsService),
            'bullet_options' => [],
            'checkboxes_options' => [],
            'buttons' => [
                [
                    'type' => 'remove',
                    'alias' => 'cancel',
                ],
                [
                    'type' => 'save',
                    'alias' => 'lock',
                ],
                [
                    'type' => 'submenu',
                    'alias' => 'set.a.lock',
                ],
            ],
            'headline' => $lockStringAliases['headline'],
            'description' => [
                $lockStringAliases['description'],
            ],
            'submenu' => $lockStringAliases['submenu'],
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
        if (! isset($data[self::TYPE_LOCK])) {
            return;
        }

        $section = $data[self::TYPE_LOCK];
        echo $rgLimitsService->getRenderer()->render('profile.rg_limits.lock', [
            'headline' => $section['headline'],
            'description' => $section['description'],
            'submenu' => $section['submenu'],
            'cooloff_period' => $rgLimitsService->getCooloffPeriod(),
            'buttons' => $this->groupButtonsByType($section['buttons']),
            'form' => $this->lockBuilder->render($section['form'], $rgLimitsService),
        ]);
    }
}
