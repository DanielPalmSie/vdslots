<?php

namespace App\Helpers;

use RuntimeException;
use Silex\Application;

class GrsHelper
{
    /**
     * @param Application $app
     * @param string      $tag
     *
     * @return string
     */
    public static function getGlobalScoreColor(Application $app, string $tag): string
    {
        $tones = static::getGlobalScoreTones($app);
        return $tones[$tag] ?? $tones['default'];
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    public static function getGlobalScoreTones(Application $app): array
    {
        return $app['grs_tag_color_map'] ?? [];
    }

    /**
     * @param $user_score
     * @param $start_score
     * @param $end_score
     *
     * @return bool
     */
    public static function isUsersRatingMatchingScore($user_score, $start_score, $end_score): bool
    {
        return $user_score <= $end_score && $user_score >= $start_score;
    }

    /**
     * @param Application $app
     * @param string      $tag
     *
     * @return int
     */
    public static function getRatingFilterEntrypointByTag(Application $app, string $tag)
    {
        return static::getRatingFilterMap($app)[$tag] ?? 0;
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    public static function getRatingFilterMap(Application $app): array
    {
        return $app['grs_range_filter_map'];
    }

    /**
     * @param Application $app
     * @param string      $section
     * @param string      $jurisdiction
     *
     * @return array
     */
    public static function getHighRiskTriggers(Application $app, string $section, string $jurisdiction): array
    {
        switch ($section) {
            case 'RG':
                $triggers = static::getRGHighRiskTriggers($app);
                break;
            case 'AML':
                $triggers = static::getAMLHighRiskTriggers($app);
                break;
            default:
                $triggers = [];
        }

        $all_triggers = $triggers['ALL'] ?? [];

        if ($jurisdiction === 'ALL') {
            return $all_triggers;
        }

        return array_merge($triggers[$jurisdiction] ?? [], $all_triggers);
    }

    /**
     * @param Application $app
     * @param string      $section
     * @param string      $jurisdiction
     *
     * @return array
     */
    public static function getMediumRiskTriggers(Application $app, string $section, string $jurisdiction): array
    {
        switch ($section) {
            case 'RG':
                $triggers = static::getRGMediumRiskTriggers($app);
                break;
            case 'AML':
                $triggers = static::getAMLMediumRiskTriggers($app);
                break;
            default:
                $triggers = [];
        }

        $all_triggers = $triggers['ALL'] ?? [];

        if ($jurisdiction === 'ALL') {
            return $all_triggers;
        }

        return array_merge($triggers[$jurisdiction] ?? [], $all_triggers);
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    public static function getAMLHighRiskTriggers(Application $app): array
    {
        return $app['aml_highrisk_triggers'] ?? [];
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    public static function getRGHighRiskTriggers(Application $app): array
    {
        return $app['rg_highrisk_triggers'] ?? [];
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    public static function getRGMediumRiskTriggers(Application $app): array
    {
        return $app['rg_mediumrisk_triggers'] ?? [];
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    public static function getAMLMediumRiskTriggers(Application $app): array
    {
        return $app['aml_mediumrisk_triggers'] ?? [];
    }

    /**
     * @param Application $app
     * @param string|null $min
     * @param string|null $max
     * @param bool        $to_string
     *
     * @return array|string
     */
    public static function getRatingScoreFilterRange(
        Application $app,
        ?string $min,
        ?string $max,
        bool $to_string = false
    ) {
        $grs_tags = $app['grs_tags'];
        $start_key = array_search($min, $grs_tags);
        $end_key = array_search($max, $grs_tags);

        if ($start_key === false || $end_key === false) {
            if ($to_string) {
                return "'" . implode("', '", $grs_tags) . "'";
            }

            return $grs_tags;
        }

        $shift = $end_key - $start_key + 1;
        $filter_tags = array_slice($grs_tags, $start_key, $shift);

        if ($to_string) {
            return "'" . implode("', '", $filter_tags) . "'";
        }

        return $filter_tags;
    }

    /**
     * @param $period
     *
     * @return void
     * @throws RuntimeException
     */
    public static function validatePeriod($period): void
    {
        $allowed_periods = ['hours', 'days', 'months', 'years'];
        throw_if(
            !in_array($period, $allowed_periods, true),
            new RuntimeException("Invalid period {$period}. Expects to: " . implode(",", $allowed_periods)));
    }
}
