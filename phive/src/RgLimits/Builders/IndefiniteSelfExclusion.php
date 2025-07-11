<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use Videoslots\RgLimits\Builders\Helpers\BuilderHelpers;
use Videoslots\RgLimits\RgLimitsService;

final class IndefiniteSelfExclusion implements RgLimitsBuilderInterface
{
    use BuilderHelpers;

    /**
     * @var string
     */
    public const TYPE_INDEFINITE_SELF_EXCLUSION = "self_exclude_indefinitely";

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        $user = $rgLimitsService->getUser();
        if (! lic('indefiniteSelfExclusion', [], $user)) {
            return $data;
        }

        $data[self::TYPE_INDEFINITE_SELF_EXCLUSION] = [
            'type' => self::TYPE_INDEFINITE_SELF_EXCLUSION,
            'buttons' => [
                [
                    'type' => 'save',
                    'alias' => 'self.exclude',
                ],
            ],
            'headline' => 'exclude.account.indefinite',
            'description' => [
                'exclude.account.indefinite.info.html',
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
        if (isset($data[self::TYPE_INDEFINITE_SELF_EXCLUSION])) {
            $section = $data[self::TYPE_INDEFINITE_SELF_EXCLUSION];
            echo $rgLimitsService->getRenderer()->render('profile.rg_limits.indefinite-self-exclusion', [
                'headline' => $section['headline'],
                'description' => $section['description'],
                'buttons' => $this->groupButtonsByType($section['buttons']),
            ]);
        }
    }
}
