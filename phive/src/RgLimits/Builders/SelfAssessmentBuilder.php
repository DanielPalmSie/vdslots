<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use Videoslots\RgLimits\Builders\Helpers\BuilderHelpers;
use Videoslots\RgLimits\RgLimitsService;

final class SelfAssessmentBuilder implements RgLimitsBuilderInterface
{
    use BuilderHelpers;

    /**
     * @var string
     */
    public const TYPE_SELF_ASSESSMENT = "self_assessment";

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        $user = $rgLimitsService->getUser();
        if (empty(licSetting('self_test', $user))) {
            return $data;
        }

        $data[self::TYPE_SELF_ASSESSMENT] = [
            'type' => self::TYPE_SELF_ASSESSMENT,
            'buttons' => [
                [
                    'type' => 'navigate',
                    'link' => lic('getGamTestUrl', [$user], $user),
                    'alias' => 'take.test',
                ],
            ],
            'headline' => 'gamtest.headline',
            'description' => [
                'gamtest.info.html',
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
        if (isset($data[self::TYPE_SELF_ASSESSMENT])) {
            $user = $rgLimitsService->getUser();
            $section = $data[self::TYPE_SELF_ASSESSMENT];
            echo $rgLimitsService->getRenderer()->render('profile.rg_limits.self-assessment', [
                'headline' => $section['headline'],
                'description' => $section['description'],
                'link' => lic('getGamTestUrl', [$user], $user),
                'buttons' => $this->groupButtonsByType($section['buttons']),
            ]);
        }
    }
}
