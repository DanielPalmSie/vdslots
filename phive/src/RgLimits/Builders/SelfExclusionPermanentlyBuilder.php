<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use Videoslots\RgLimits\Builders\Helpers\BuilderHelpers;
use Videoslots\RgLimits\RgLimitsService;

final class SelfExclusionPermanentlyBuilder implements RgLimitsBuilderInterface
{
    use BuilderHelpers;

    /**
     * @var string
     */
    public const TYPE_SELF_EXCLUDE_PERMANENT = "self_exclude_permanent";

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        $user = $rgLimitsService->getUser();

        if (! lic('permanentSelfExclusion', [], $user)) {
            return $data;
        }

        $data[self::TYPE_SELF_EXCLUDE_PERMANENT] = [
            'type' => self::TYPE_SELF_EXCLUDE_PERMANENT,
            'buttons' => [
                [
                    'type' => 'save',
                    'alias' => 'self.exclude',
                ],
            ],
            'headline' => 'exclude.account.permanent',
            'description' => [
                'exclude.account.permanent.info.html',
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
        if (isset($data[self::TYPE_SELF_EXCLUDE_PERMANENT])) {
            $section = $data[self::TYPE_SELF_EXCLUDE_PERMANENT];
            echo $rgLimitsService->getRenderer()->render('profile.rg_limits.self_exclude_permanent', [
                'headline' => $section['headline'],
                'description' => $section['description'],
                'buttons' => $this->groupButtonsByType($section['buttons']),
            ]);
        }
    }
}
