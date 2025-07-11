<?php

namespace App\Traits;

use App\Extensions\Database\FManager as DB;
use App\Helpers\GrsHelper;
use App\Models\RiskProfileRating;

trait GlobalRatingScoreTrait
{
    /**
     * @param string $category
     * @param string $jurisdiction
     * @param string $rating_type
     * @return array
     * @throws \Exception
     */
    public static function getCategorySettings(string $category, string $jurisdiction, string $rating_type): array
    {
        return DB::table('risk_profile_rating')
            ->where('category', $category)
            ->where('jurisdiction', $jurisdiction)
            ->where('section', $rating_type)
            ->orderBy('score')
            ->get()
            ->map(function ($item) {
                return (array)$item;
            })->toArray();
    }

    /**
     * @param $app
     * @param int $rating
     * @param string $jurisdiction
     * @param string $rating_type
     * @param bool $log_on_failure
     * @return string|null
     * @throws \Exception
     */
    public static function getGRSRatingTag(
        $app,
        int $rating,
        string $jurisdiction,
        string $rating_type,
        bool $log_on_failure = false
    ): ?string {
        $rating_tag = null;
        $log_settings = [];
        $rating_score_settings = static::getCategorySettings(
            RiskProfileRating::RATING_SCORE_PARENT_CATEGORY,
            $jurisdiction,
            $rating_type
        );

        foreach ($rating_score_settings as $key => $item) {
            $start_score = ($key == 0) ? 0 : $rating_score_settings[$key - 1]['score'] + 1;
            $log_settings[$item['name']] = [$start_score, $item['score']];

            if (GrsHelper::isUsersRatingMatchingScore($rating, $start_score, $item['score'])) {
                $rating_tag = $item['title'];
                break;
            }
        }

        if (!$rating_tag && $log_on_failure) {
            $app['monolog']->addError("Error: the Rating tag has not been set.
            User's rating {$rating}. Jurisdiction: {$jurisdiction}. Type: {$rating_type}. Settings:", $log_settings);
        }

        return $rating_tag;
    }

    /**
     * @param $user_score
     * @param $category_name
     * @param $section
     * @param $jurisdiction
     * @return array
     * @throws \Exception
     */
    public function prepareUserRatingScore($user_score, $category_name, $section, $jurisdiction): array
    {
        $data = [];
        $rating_score = static::getCategorySettings($category_name, $jurisdiction, $section);
        foreach ($rating_score as $key => $item) {
            $start_score = ($key == 0) ? 0 : $rating_score[$key - 1]['score'] + 1;
            $data[] = [
                'title' => $item['title'],
                'active' => GrsHelper::isUsersRatingMatchingScore($user_score, $start_score, $item['score']),
            ];
        }

        return $data;
    }
}