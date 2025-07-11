<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 16/03/16
 * Time: 16:48
 */

namespace App\Repositories;

use App\Extensions\Database\Connection\Connection;
use App\Models\Action;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;

class UserMonthlyInteractionReportRepository
{
    protected $year;

    protected $month;

    protected $sql;

    /** @var  Connection|\Illuminate\Database\Connection $connection */
    protected $connection = null;


    //constructor
    public function __construct($year = null, $month = null)
    {
        $this->year = is_null($year) ? null : $year;
        $this->month = is_null($month) ? null : $month;
        $this->sql = phive('SQL');
    }

    /**
     * @param Connection|\Illuminate\Database\Connection $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Connection|\Illuminate\Database\Connection
     */
    protected function connection()
    {
        return empty($this->connection) ? DB::connection() : $this->connection;
    }

    /**
     * @return array
     */
    private function getActions(){
        return [
                Action::TAG_CALL_TO_USER,
                Action::TAG_EMAIL_TO_USER,
                Action::TAG_FORCE_DEPOSIT_LIMIT,
                Action::TAG_ASK_PLAY_TOO_LONG,
                Action::TAG_FORCE_LOGIN_LIMIT,
                Action::TAG_ASK_BET_TOO_HIGH,
                Action::TAG_FORCE_MAX_BET_PROTECTION,
                Action::TAG_ASK_GAMBLE_TOO_MUCH,
                Action::TAG_FORCE_SELF_EXCLUSION,
        ];
    }

    public function truncateDataForMonth(Carbon $selected_month) {
      $date_start = $selected_month->firstOfMonth()->toDateString();
      $date_end = $selected_month->lastOfMonth()->toDateString();
      $this->connection->statement("
        DELETE FROM videoslots.users_monthly_interaction_stats
        WHERE date BETWEEN '$date_start' AND '$date_end'
      ");
    }

    /**
     * @return array
     */
    public function getRawData() {
        $tags = $this->sql->makeIn($this->getActions());
        $from_date = Carbon::create($this->year, $this->month, 1)->startOfMonth()->startOfDay()->format('Y-m-d H:i:s');;
        $to_date = Carbon::create($this->year, $this->month, 1)->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');;

        $bindings_array = [];
        foreach(range(1, 6) as $num){
            $bindings_array["from_date$num"] = $from_date;
            $bindings_array["to_date$num"] = $to_date;
        }

        $result = $this->connection()->select("
SELECT 
	actions.user_id, actions.actions, 
	COALESCE(daily_stats.current_deposit_amount, 0) AS current_deposit_amount, COALESCE(daily_stats.previous_deposit_amount, 0) AS previous_deposit_amount,
	COALESCE(daily_stats.current_loss_amount, 0) AS current_loss_amount, COALESCE(daily_stats.previous_loss_amount, 0) AS previous_loss_amount,  
	COALESCE(sessions.current_time_spent_seconds, 0) AS current_time_spent_seconds, COALESCE(sessions.previous_time_spent_seconds, 0) AS previous_time_spent_seconds,
	COALESCE(limits.has_limit, 0) AS has_limit
FROM 
(
    -- first we get the user_id list by tags (and also the tags themselves separated by whitespace)
	SELECT target as user_id, GROUP_CONCAT(DISTINCT tag ORDER BY tag DESC SEPARATOR ' ') actions
	FROM actions
	WHERE 
		created_at BETWEEN :from_date1 AND :to_date1
		AND tag IN ({$tags}) 
	GROUP BY target
			
) AS actions
LEFT JOIN 
(
    -- We get deposits and losses from current and previous month in order to calculate percentage, we use 2 subselects left joined by user_id
	SELECT 
		ds_current.user_id,
		ds_current.deposit_amount AS current_deposit_amount,  
		ds_previous.deposit_amount AS previous_deposit_amount, 	
		ds_current.loss_amount AS current_loss_amount,  	
		ds_previous.loss_amount AS previous_loss_amount
	FROM 
	(
		SELECT user_id, 
		   COALESCE(SUM(deposits),0) AS deposit_amount, 
		   COALESCE(SUM(gross),0) AS loss_amount
		FROM  users_daily_stats AS uds
		WHERE 
			uds.date BETWEEN :from_date2 AND :to_date2   
		GROUP BY user_id
	) AS ds_current
	LEFT JOIN 
	(
		SELECT user_id, 
		   COALESCE(SUM(deposits),0) AS deposit_amount, 
		   COALESCE(SUM(gross),0) AS loss_amount
		FROM  users_daily_stats AS uds
		WHERE 
			uds.date BETWEEN DATE_ADD(:from_date3, INTERVAL -1 MONTH) AND DATE_ADD(:to_date3, INTERVAL -1 MONTH)   
		GROUP BY user_id
	) AS ds_previous
	ON ds_current.user_id = ds_previous.user_id
) AS daily_stats
ON actions.user_id = daily_stats.user_id
LEFT JOIN
(
    -- Here we calculate the time spent on site with session durations from current and previous month in order to calculate percentage, we use 2 subselects left joined by user_id
	SELECT 
		us_current.user_id, 
		us_current.time_spent AS current_time_spent_seconds, 
		us_previous.time_spent AS previous_time_spent_seconds
	FROM
		(
			SELECT 
				user_id, 
				COALESCE(
					SUM(
						TIMESTAMPDIFF(
							SECOND, 
							created_at, 
							updated_at
						)
					), 
					0
				) AS time_spent 
			FROM  users_sessions 
			WHERE 
				created_at BETWEEN :from_date4 AND :to_date4 
			GROUP BY user_id
		) AS us_current
	LEFT JOIN
	(
		SELECT 
			user_id, 
			COALESCE(
				SUM(
					TIMESTAMPDIFF(
						SECOND, 
						created_at, 
						updated_at
					)
				),
				0
			) AS time_spent 
		FROM  users_sessions 
		WHERE 
			created_at BETWEEN DATE_ADD(:from_date5, INTERVAL -1 MONTH) AND DATE_ADD(:to_date5, INTERVAL -1 MONTH) 
		GROUP BY user_id
	) AS us_previous
	ON us_current.user_id = us_previous.user_id
) AS sessions
ON actions.user_id = sessions.user_id
LEFT JOIN
(
    -- we look for information if the users deposit limit was set in searched month. We are looking for intervals that are intersecting with our month.
	SELECT user_id, IF(COALESCE(cur_lim ,0) > 0, 1, 0) as has_limit
	FROM  rg_limits
	WHERE 
		resets_at > :from_date6 AND
		updated_at < :to_date6
	GROUP BY user_id
) AS limits
ON actions.user_id = limits.user_id
", $bindings_array);

        return $result;
    }

}
