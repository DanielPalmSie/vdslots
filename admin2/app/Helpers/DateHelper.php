<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 17/03/16
 * Time: 09:35
 */

namespace App\Helpers;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;

class DateHelper
{
    public const START_OF_DAY = '00:00:00';
    public const END_OF_DAY = '23:59:59';

    /**
     * Validate and adjust the date range provided in the request.
     *
     * @param Request $request
     * @param int $months Maximum difference in months between start and end dates. Defaults to 6 months.
     * @return array An array with 'start_date' and 'end_date'.
     */
    public static function validateDateRange(Request $request, int $months = 6): array
    {
        $startDateInput = $request->get('start_date');
        $endDateInput = $request->get('end_date');

        $endDate = self::validateCarbonString($endDateInput)
            ? Carbon::parse($endDateInput)->setTimeFromTimeString(self::END_OF_DAY)
            : Carbon::now()->setTimeFromTimeString(self::END_OF_DAY);

        $startDate = self::validateCarbonString($startDateInput)
            ? Carbon::parse($startDateInput)->setTimeFromTimeString(self::START_OF_DAY)
            : (clone $endDate)->subMonths($months)->setTimeFromTimeString(self::START_OF_DAY);

        if ($endDate->diffInMonths($startDate) > $months) {
            $startDate = (clone $endDate)->subMonths($months)->setTimeFromTimeString(self::START_OF_DAY);
        }

        return [
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Validate if a given string can be parsed by Carbon.
     *
     * @param string|null $string
     * @return bool True if valid, false otherwise.
     */
    public static function validateCarbonString(?string $string): bool
    {
        if (!$string || !is_string($string)) {
            return false;
        }

        try {
            Carbon::parse($string);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get a date set to the start of the day.
     *
     * @param string|null $date Optional date string.
     * @param int $subMonths Optional number of months to subtract if $date is null. Default is 1.
     * @return string Formatted start-of-day date string.
     */
    public static function validateStartOfDay(?string $date = null, int $subMonths = 1): string
    {
        $date = $date ?: Carbon::now()->subMonths($subMonths)->format('Y-m-d');
        return self::formatDateWithTime($date, self::START_OF_DAY);
    }

    /**
     * Get a date set to the end of the day.
     *
     * @param string|null $date Optional date string.
     * @param int $subMonths Optional number of months to subtract if $date is null. Default is 0.
     * @return string Formatted end-of-day date string.
     */
    public static function validateEndOfDay(?string $date = null, int $subMonths = 0): string
    {
        $date = $date ?: Carbon::now()->subMonths($subMonths)->format('Y-m-d');
        return self::formatDateWithTime($date, self::END_OF_DAY);
    }

    /**
     * Format a date with the specified time component.
     *
     * @param string|null $date The base date string.
     * @param string $time The time to append to the date. Defaults to empty.
     * @return string Formatted date with the time component.
     */
    public static function formatDateWithTime(?string $date = null, string $time = ''): string
    {
        $date = $date ?: Carbon::now()->format('Y-m-d');

        if (strlen($date) === 10 && $time) {
            return sprintf('%s %s', $date, $time);
        }

        return $date;
    }
}
