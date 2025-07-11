<?php

declare(strict_types=1);

namespace Videoslots\User\MyAchievements;

use Laraphive\Domain\User\DataTransferObjects\MyAchievements\MyAchievementsFilterData;

final class MyAchievementsFilterService
{
    /**
     * @param string|null $category
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\MyAchievements\MyAchievementsFilterData
     */
    public function getFilter(?string $category): MyAchievementsFilterData
    {
        $user = cu();
        $category = $category ?? 'all';
        $trophy = phive('Trophy');
        $isGrouped = !empty($trophy->getUserTrophiesHeadlines($user->getId(), false, 'progressed'));
        $categories = $trophy->getCategories($user, 'category', '', 'trophy');
        $subCategories = $trophy->getCategories($user, 'sub_category', $category == 'all' ? '' : $category, 'trophy');

        return new MyAchievementsFilterData(
            t('mobile.mission.overview'),
            [
                'type' => 'input',
                'placeholder' => t('search.trophies')
            ],
            [
                'type' => 'select',
                'options' => $this->formatCategories($categories),
            ],
            [
                'type' => 'select',
                'options' => $this->formatCategories($subCategories, 'all.trophy.subcategories'),
            ],
            [
                'type' => 'radio',
                'options' => $this->formatTypes($isGrouped)
            ],
            [
                'type' => 'submit',
                'value' => t('submit')
            ]
        );
    }

    /**
     * @param array $categories
     * @param string $defaultName
     *
     * @return array
     */
    private function formatCategories(array $categories, string $defaultName = 'all.trophy.categories'): array
    {
        $result = [
            [
                'value' => 'all',
                'name' => t($defaultName)
            ]
        ];

        foreach ($categories as $key => $category) {
            $result[] = [
                'value' => $key,
                'name' => $category
            ];
        }

        return $result;
    }

    /**
     * @param bool $isGrouped
     *
     * @return array
     */
    private function formatTypes(bool $isGrouped): array
    {
        return [
            [
                'label' => t('mobile.trophies.only.progressed'),
                'checked' => $isGrouped
            ],
            [
                'label' => t('trophies.not.completed'),
                'checked' => false
            ],
            [
                'label' => t('trophies.all'),
                'checked' => !$isGrouped
            ],
        ];

    }
}
