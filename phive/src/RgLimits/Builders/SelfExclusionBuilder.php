<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use DBUser;
use Videoslots\RgLimits\Builders\Helpers\BuilderHelpers;
use Videoslots\RgLimits\RgLimitsService;

final class SelfExclusionBuilder implements RgLimitsBuilderInterface
{
    use BuilderHelpers;

    /**
     * @var string
     */
    public const TYPE_SELF_EXCLUDE = "self_exclude";

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        $user = $rgLimitsService->getUser();

        $data[self::TYPE_SELF_EXCLUDE] = [
            'type' => self::TYPE_SELF_EXCLUDE,
            'bullet_options' => $this->createBulletOptions($user),
            'buttons' => [
                [
                    'type' => 'submenu',
                    'alias' => 'self.exclude',
                ],
                [
                    'type' => 'save',
                    'alias' => 'lock',
                ],
                [
                    'type' => 'remove',
                    'alias' => 'cancel',
                ],
            ],
            'headline' => 'exclude.account',
            'submenu' => 'exclude.duration',
            'description' => [
                'exclude.account.info.html',
            ],
        ];

        $extraInfo = $this->getSelfExclusionExtraInfo($user);
        if (! empty($extraInfo)) {
            $data[self::TYPE_SELF_EXCLUDE]['description'][] = $extraInfo;
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
        if (isset($data[self::TYPE_SELF_EXCLUDE])) {
            $section = $data[self::TYPE_SELF_EXCLUDE];
            echo $rgLimitsService->getRenderer()->render('profile.rg_limits.self-exclude', [
                'headline' => $section['headline'],
                'submenu' => $section['submenu'],
                'description' => $section['description'],
                'buttons' => $this->groupButtonsByType($section['buttons']),
                'bullet_options' => $section['bullet_options'],
            ]);
        }
    }

    /**
     * @param \DBUser $user
     *
     * @return array
     */
    private function createBulletOptions(DBUser $user): array
    {
        $bulletOptions = [];

        $timeOptions = lic('getSelfExclusionTimeOptions', [], $user);
        foreach ($timeOptions as $value) {
            $bulletOptions[] = [
                'value' => (string) $value,
                'alias' => 'exclude.' . $value . '.days',
                'checked' => false,
            ];
        }

        if (count($bulletOptions) > 0) {
            $bulletOptions[0]['checked'] = true;
        }

        return $bulletOptions;
    }

    /**
     * @param \DBUser $user
     *
     * @return string
     */
    private function getSelfExclusionExtraInfo(DBUser $user): string
    {
        $selfExclusionExtraInfo = lic('getSelfExclusionExtraInfo', [], $user);

        return $selfExclusionExtraInfo ?: '';
    }
}
