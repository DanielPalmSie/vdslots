<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 02/03/16
 * Time: 09:35
 */

namespace App\Helpers;

use Carbon\Carbon;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class ValidationHelper
{
    /**
     * Validate a range date on the request. Returns a properly processed end and start date on an array.
     *
     * @param Request $request
     * @param int $months Default difference in months between start and end date.
     * @return array
     */
    public static function validateDateRange(Request $request, $months = 6)
    {

        // Get start_date from request, or set it to one month before today
        $start_date = $request->get('start_date')
            ? Carbon::parse($request->get('start_date'))->startOfDay()
            : Carbon::now()->subMonth()->startOfDay();

        // Get end_date from request, or set it to one month after start_date
        $end_date = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))->endOfDay()
            : $start_date->copy()->addMonth()->endOfDay();

        // If the difference between start_date and end_date is greater than $months, limit end_date to start_date + $months
        if ($start_date->diffInMonths($end_date) > $months) {
            $end_date = $start_date->copy()->addMonths($months)->endOfDay();
        }

        return [
            'start_date' => $start_date->format('Y-m-d 00:00:00'),
            'end_date' => $end_date->format('Y-m-d 23:59:59'),
        ];
    }

    public static function validateOrderBySort($sort_string, $default = 'ASC')
    {
        if (strcasecmp($sort_string, 'ASC') == 0) {
            return strtoupper($sort_string);
        } elseif (strcasecmp($sort_string, 'DESC') == 0) {
            return strtoupper($sort_string);
        } else {
            return $default;
        }
    }

    public static function validateEmptyFields(Request $request, array $fields)
    {
        foreach ($fields as $field) {
            if (empty($request->get($field))) {
                return $field;
            }
        }
        return false;
    }
}
