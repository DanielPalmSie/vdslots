<?php

declare(strict_types=1);

namespace Videoslots\User\MyAchievements;

use Laraphive\Domain\User\DataTransferObjects\MyAchievements\MyAchievementsHeadlinesData;
use Laraphive\Domain\User\DataTransferObjects\MyAchievements\MyAchievementsHeadlinesResponseData;
use Laraphive\Domain\User\Factories\MyAchievementsResponseFactory;

final class MyAchievementsHeadlinesService
{
    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\MyAchievements\MyAchievementsHeadlinesData $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\MyAchievements\MyAchievementsHeadlinesResponseData
     */
    public function getHeadlines(MyAchievementsHeadlinesData $data): MyAchievementsHeadlinesResponseData
    {
        $result = [];
        $user = cu();
        $trophy = phive('Trophy');
        $trophyListBox = phive('BoxHandler')->getRawBox('TrophyListBox');
        $category = $data->getCategory() == 'all' ? '' : $data->getCategory();
        $subCategory  = $data->getSubCategory() == 'all' ? '' : $data->getSubCategory();
        $_REQUEST['substr'] = $data->getSearch();
        $grouped = $trophy->getUserTrophiesHeadlines($user->getId(), false, $data->getType(), $category, $subCategory);
        $grouped = $trophyListBox->searchSubstringInTrophies($data->getSearch(), $grouped);

        if(empty($grouped)) {
            return MyAchievementsResponseFactory::createError(t('trophy.empty.search.result'));
        }

        foreach ($grouped as $item) {
            $result['items'][] = [
                'headline' => $item['headline'],
                'sub_category' => $item['sub_category'],
                'can_reset' => $item['can_reset'],
                'completed' => $item['completed'],
                'reset_col' => $item['reset_col']
            ];
        }

        $result['reset_info'] = [
            'title' => t('restart.your.trophies'),
            'description' => t('restart.your.trophies.descr')
        ];

        return MyAchievementsResponseFactory::createSuccess($result);
    }
}
