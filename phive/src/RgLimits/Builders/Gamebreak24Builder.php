<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use Videoslots\RgLimits\Builders\Helpers\BuilderHelpers;
use Videoslots\RgLimits\RgLimitsService;

final class Gamebreak24Builder implements RgLimitsBuilderInterface
{
    use BuilderHelpers;

    /**
     * @var string
     */
    public const TYPE_GAMEBREAK24 = "gamebreak_24";

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        if (empty(lic('getLicSetting', ['gamebreak_24']))) {
            return $data;
        }

        $preselectGamebreak24 = lic('preselectGamebreak24');

        $availableCategories = lic('getGamebreak24Categories');
        $lockedCategories = $rgLimitsService->getUser()->getRgLockedGames();

        $checkboxesOptions = [];
        foreach ($availableCategories as &$category) {
            $isChecked = in_array($category['alias'], $lockedCategories);
            $checkboxesOptions[] = [
                'name' => $category['name'],
                'alias' => $category['alias'],
                'checked' => $preselectGamebreak24 || $isChecked,
                'disabled' => $isChecked,
            ];
        }

        $data[self::TYPE_GAMEBREAK24] = [
            'type' => self::TYPE_GAMEBREAK24,
            'checkboxes_options' => $checkboxesOptions,
            'headline' => 'spelpaus.24.headline',
            'description' => [
                'spelpaus.24.info.html',
            ],
            'buttons' => [
                [
                    'type' => 'save',
                    'alias' => 'game-category.locked.lock',
                ],
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
        if (isset($data[self::TYPE_GAMEBREAK24])) {
            $section = $data[self::TYPE_GAMEBREAK24];
            echo $rgLimitsService->getRenderer()->render('profile.rg_limits.gamebreak24', [
                'headline' => $section['headline'],
                'description' => $section['description'],
                'checkboxes_options' => $section['checkboxes_options'],
                'buttons' => $this->groupButtonsByType($section['buttons']),
            ]);
        }
    }
}
