<?php

declare(strict_types=1);

namespace Videoslots\RgLimits\Builders;

use DBUser;
use Videoslots\RgLimits\Builders\Helpers\BuilderHelpers;
use Videoslots\RgLimits\RgLimitsService;

final class GamebreakIndefiniteBuilder implements RgLimitsBuilderInterface
{
    use BuilderHelpers;

    /**
     * @var string
     */
    public const TYPE_GAMEBREAK_INDEFINITE = "gamebreak_indefinite";

    /**
     * @param array $data
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     *
     * @return array
     */
    public function build(array $data, RgLimitsService $rgLimitsService): array
    {
        $user = $rgLimitsService->getUser();
        if (empty(licSetting('gamebreak_indefinite')) || empty($user)) {
            return $data;
        }

        $checkboxesOptions = $this->getCheckboxOptions($rgLimitsService->getUser());

        $data[self::TYPE_GAMEBREAK_INDEFINITE] = [
            'type' => self::TYPE_GAMEBREAK_INDEFINITE,
            'checkboxes_options' => $checkboxesOptions,
            'headline' => 'game-category-block-indefinite.title',
            'description' => [
                'game-category-block-indefinite.description',
            ],
            'unblock' => 'game-category-block-indefinite.unblock',
            'buttons' => [
                [
                    'type' => 'save',
                    'alias' => 'save',
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
        if (isset($data[self::TYPE_GAMEBREAK_INDEFINITE])) {
            $section = $data[self::TYPE_GAMEBREAK_INDEFINITE];
            echo $rgLimitsService->getRenderer()->render('profile.rg_limits.gamebreak_indefinite', [
                'headline' => $section['headline'],
                'description' => $section['description'],
                'checkboxes_options' => $section['checkboxes_options'],
                'buttons' => $this->groupButtonsByType($section['buttons']),
            ]);
        }
    }

    /**
     * @param \DBUser $user
     *
     * @return array
     */
    private function getCheckboxOptions(DBUser $user): array
    {
        $availableCategories = lic('getGamebreak24Categories');

        $lockedCategoriesAndPeriod = $user->getRgLockedGamesAndPeriod();
        $extractedCategories = $user->getExtractedCategoriesFromVisibleMenu();

        $lockedCategoriesNames = [];
        foreach ($lockedCategoriesAndPeriod as $category => $period) {
            if (empty($period)) {
                $lockedCategoriesNames[] = $category;
            }
        }

        $checkboxesOptions = [];
        foreach ($availableCategories as $category) {
            $isChecked = in_array($category['alias'], $lockedCategoriesNames);

            $period = '';
            $categoryName = $category['name'];
            if (! in_array($categoryName, $extractedCategories)) {
                $period = $lockedCategoriesAndPeriod[$category['alias']] ?? '';

                if ($period == 0) {
                    $period = '';
                }
            }

            $checkboxesOptions[] = [
                'name' => $categoryName,
                'alias' => $category['alias'],
                'checked' => $isChecked,
                'period' => $period,
            ];
        }

        return $checkboxesOptions;
    }
}
