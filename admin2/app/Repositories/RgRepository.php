<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 03/02/2016
 * Time: 12:31
 */

namespace App\Repositories;

use App\Helpers\DownloadHelper;
use App\Models\RiskProfileRating;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;

class RgRepository
{

    private $app;

    /**
     * RgRepository constructor.
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }


    /**
     * Populate the array and export it into a CSV file as streamed response.
     *
     * @param array $data
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportRgLimits($data, $name)
    {
        $records[] = [
            'User ID',
            'Country',
            'Balance',
            'Currency',
            'Limit type',
            'Active value',
            'Time span',
            'Created Date/time',
            'Unlock Date/time',
            'Total deposits',
            'Deposits current month',
            'Average monthly deposit',
            'Total loss',
            'Loss current month',
            'Average monthly loss',
        ];

        foreach ($data as $element) {
            $records[] = [
                $element->id,
                $element->country,
                $element->balance,
                $element->user_currency,
                $element->lock_type,
                $element->cur_lim,
                $element->time_span,
                $element->lock_date,
                $element->unlock_date,
                $element->total_deposits,
                $element->deposits_this_month,
                $element->average_monthly_deposit,
                $element->gross,
                $element->gross_this_month,
                $element->average_monthly_loss,
            ];
        }
        return DownloadHelper::streamAsCsv($this->app, $records, $name);
    }

    public static function getFlags(int $user_id, string $time_interval_from_now = '-1 day'): array
    {
        $query = "
            SELECT * FROM triggers_log
            WHERE 
            user_id = :user_id AND
            trigger_name LIKE :trigger_name AND 
            created_at > :created_at
        ";

        $bindings = [
            'user_id' => $user_id,
            'trigger_name' => RiskProfileRating::RG_SECTION.'%',
            'created_at' => Carbon::createFromTimestamp(strtotime($time_interval_from_now))->format('Y-m-d h-i-s'),
        ];

        $res = collect(DB::shSelect($user_id, 'triggers_log', $query, $bindings))->pluck('trigger_name')->toArray();

        return $res;

    }

}