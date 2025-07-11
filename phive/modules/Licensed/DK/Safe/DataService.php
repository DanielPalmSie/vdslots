<?php


class DataService
{
    const FREE_SPINS_STATUS_CHECK_DATE = '2025-05-27 00:00:00';

    /**
     * Get latest report
     *
     * @param $unique_id
     * @return array|null
     */
    public static function getLatestReportByUniqueId($unique_id)
    {
        $where_in = phive('SQL')->makeIn(SafeConstants::NO_FILE_REPORTS);

        $sql = "
                SELECT *
                FROM
                    external_regulatory_report_logs
                WHERE
                    unique_id = '{$unique_id}'
                    AND report_type NOT IN ({$where_in})
                ORDER BY
                    sequence DESC
                LIMIT 1
                ";

        $report = phive('SQL')->loadArray($sql);
        return !empty($report) ? $report[0] : null;
    }

    /**
     * Insert the new report.
     *
     * @param SafeParams $safe_params
     * @param string $report_type
     * @param string $filename_prefix
     * @param string $file_path
     * @param $from
     * @param $to
     * @param array $game_session_result
     * @return mixed
     */
    public static function insertReport(
        SafeParams $safe_params,
        string $report_type,
        string $filename_prefix,
        string $file_path,
        $from,
        $to,
        array $game_session_result = []
    ) {
        $log_info = $safe_params->getAll();
        $log_info['game_session_result'] = $game_session_result;

        $insert = [
            'regulation' => 'SAFE',
            'report_type' => $report_type,
            'report_data_from' => $from,
            'report_data_to' => $to,
            'created_at' => date('y-m-d H:i:s'),
            'unique_id' => $safe_params->getTokenId(),
            'sequence' => $safe_params->getSequence(),
            'filename_prefix' => $filename_prefix,
            'file_path' => $file_path,
            'log_info' => json_encode($log_info),
        ];

        return phive('SQL')->insertArray("external_regulatory_report_logs", $insert);
    }

    /**
     * Get data query for end of day. Contains additional FS logic for both bet_count and bet_amount by joining the bonus_entries and bonus_types tables with users_game_sessions
     *
     * @param $date
     * @param $country
     * @param array $excluded_providers
     * @param bool $calculate_rollbacks
     * @return mixed
     */
    public static function endOfDayDailyGameStats(
        $date,
        $country,
        array $excluded_providers = [],
        bool $calculate_rollbacks = true,
        $spilHjemmeside = ''
    ) {
        $from = $date . ' 00:00:00';
        $to = $date . ' 23:59:59';

        $excluded_providers_where_clauses = self::getExcludedProvidersWhere($excluded_providers);

        $win_and_bet_select = "(IFNULL(ugs.win_amount,0) - IFNULL(abs(ugs.wins_rollback),0)) / 100 as win_amount,";
        $win_and_bet_select .= "ROUND((ugs.bet_amount - ugs.bets_rollback + SUM(CASE WHEN bt.id IS NOT NULL THEN bt.reward * bt.frb_cost ELSE 0 END)) / 100, 2) AS bet_amount";

        if (! $calculate_rollbacks) {
            $win_and_bet_select = "(IFNULL(ugs.win_amount,0)) / 100 as win_amount,";
            $win_and_bet_select .= "ROUND((ugs.bet_amount + SUM(CASE WHEN bt.id IS NOT NULL THEN bt.reward * bt.frb_cost ELSE 0 END)) / 100, 2) AS bet_amount";
        }

        $bonus_entries_status_check = self::getFreeSpinStatusCheck($to);

        $sql = "SELECT
                    IFNULL(SUM(ugs_a.bet_cnt), 0) AS EndOfDayRapportAntalSpil,
                    IFNULL(SUM(ugs_a.win_amount), 0) AS win_amount,
                    IFNULL(SUM(ugs_a.bet_amount), 0) AS bet_amount,
                    '{$spilHjemmeside}' AS SpilHjemmeside
                FROM
                    (
                    SELECT
                        (ugs.bet_cnt + SUM(CASE WHEN bt.id IS NOT NULL THEN bt.reward ELSE 0 END)) AS bet_cnt,
                        {$win_and_bet_select}
                    FROM
                        users_game_sessions ugs
                    INNER JOIN users u ON
                        ugs.user_id = u.id
                    LEFT JOIN bonus_entries be ON
                        be.game_session_id = ugs.id
                        AND be.bonus_type = 'freespin'
                        {$bonus_entries_status_check}
                        AND be.frb_remaining = 0
                    LEFT JOIN bonus_types bt ON
                        bt.id = be.bonus_id
                    WHERE
                       ugs.end_time >= '{$from}'
                        AND ugs.end_time <= '{$to}'
                        AND u.country = '{$country}'
                        AND (ugs.bet_amount > 0
                            OR ugs.win_amount > 0
                            OR ugs.bet_cnt > 0
                            OR ugs.bets_rollback != 0
                            OR ugs.wins_rollback != 0)
                        {$excluded_providers_where_clauses}
                    GROUP BY
                        ugs.id) as ugs_a";

        $result = phive('SQL')->shs('sum')->loadArray($sql);
        $result = $result ? $result[0] : [];

        $cancel_result = self::getCancelAmount($date);

        $result['EndOfDayRapportAntalSpil'] -= $cancel_result['bet_cnt'];
        $result['win_amount'] -= $cancel_result['win_amount'];
        $result['bet_amount'] -= $cancel_result['bet_amount'];

        $third_party_report_data = self::thirdPartyEndOfDay($from, $to, $excluded_providers);

        $result['win_amount'] += $third_party_report_data['win_amount'];
        $result['bet_amount'] += $third_party_report_data['bet_amount'];
        $result['EndOfDayRapportAntalSpil'] += $third_party_report_data['bet_count'];

        if (empty($result) || ($result['bet_amount'] <= 0 && $result['win_amount'] <= 0 && $result['EndOfDayRapportAntalSpil'] <= 0)) {
            return [];
        }

        $result['date'] = $date;
        return $result;
    }

    /**
     * Insert in external_regulatory_report_logs the request from a third party end of day report.
     *
     * @param $from
     * @param $to
     * @param array $excluded_providers
     * @return array
     */
    public static function thirdPartyEndOfDay($from, $to, $excluded_providers = [])
    {
        $excluded_providers = SafeConstants::generateProvidersReportType(SafeConstants::END_OF_DAY, $excluded_providers);
        $excluded_providers = sprintf("'%s'", implode("','", $excluded_providers ) );

        $bet_amount = $win_amount = $bet_count = 0;

        if ($excluded_providers) {
            $sql = "
                SELECT *
                FROM
                    external_regulatory_report_logs
                WHERE
                    created_at >= '{$from}'
                    AND created_at <= '{$to}'
                    AND report_type IN ({$excluded_providers})
                ORDER BY
                    sequence DESC
                ";

            $reports = phive('SQL')->loadArray($sql);

            foreach ($reports as $report) {
                $info = json_decode($report['log_info']);

                $bet_amount += $info->extra_info->bet_amount;
                $win_amount += $info->extra_info->win_amount;
                $bet_count += $info->extra_info->EndOfDayRapportAntalSpil;
            }
        }

        return [
            'bet_amount' => $bet_amount,
            'win_amount' => $win_amount,
            'bet_count' => $bet_count
        ];
    }

    /**
     * Get rollback amounts for bets and wins.
     *
     * @param $date
     * @return array
     */
    public static function getCancelAmount($date)
    {
        $type = SafeConstants::KASINO_SPIL_CANCEL;

        $from = $date . ' 00:00:00';
        $to = $date . ' 23:59:59';

        $sql = "
                SELECT *
                FROM
                    external_regulatory_report_logs
                WHERE
                    created_at > '{$from}'
                    AND created_at < '{$to}'
                    AND report_type = '{$type}'
                ";

        $result = phive('SQL')->loadArray($sql);

        $report_amounts = [
            'bet_amount' => 0,
            'win_amount' => 0,
            'bet_cnt' => 0,
        ];

        foreach ($result as $report) {
            $log_info = json_decode($report['log_info']);
            if (! empty($log_info->is_rollback) && $log_info->is_rollback === true) { // TODO remove this statement
                if ($log_info->rollback_type === 'wins') {
                    $report_amounts['win_amount'] += (int) $log_info->rollback_amount;
                } elseif ($log_info->rollback_type === 'bets') {
                    $report_amounts['bet_amount'] += (int) $log_info->rollback_amount;
                    $report_amounts['bet_cnt'] += 1;
                }
            }
        }

        if ($result) {
            $report_amounts['win_amount'] = $report_amounts['win_amount'] / 100;
            $report_amounts['bet_amount'] = $report_amounts['bet_amount'] / 100;
        }

        return $report_amounts;
    }

    /**
     * Get all end of day reports for a specific token.
     *
     * @param int $unique_id
     * @return mixed
     */
    public static function getEndOfDayReportsByToken(int $unique_id)
    {
        $type = SafeConstants::END_OF_DAY;

        $sql = "
                SELECT *
                FROM
                    external_regulatory_report_logs
                WHERE
                    unique_id = '{$unique_id}'
                    AND report_type = '{$type}'
                    AND file_path is not null
                ";

        return phive('SQL')->loadArray($sql);
    }

    /**
     * Data range to regenerate of kasino spil report for a token.
     *
     * @param int $unique_id
     * @return array|null
     */
    public static function getKasinoSpilRegenerateDateRange(int $unique_id)
    {
        $type = SafeConstants::KASINO_SPIL;

        $sql = "
            SELECT
                min(report_data_from) as report_data_from,
                max(report_data_to) as report_data_to
            FROM external_regulatory_report_logs
            WHERE unique_id = '{$unique_id}'
            AND report_type = '{$type}'
            GROUP BY unique_id
        ";

        $report = phive('SQL')->loadArray($sql);

        return !empty($report) ? $report[0] : null;
    }

    /**
     * Get game session for kasino spil. Contains additional FS logic for both bet_count and bet_amount by joining the bonus_entries and bonus_types tables with users_game_sessions
     *
     * !!! IMPORTANT !!!
     * In case of regenerate scenario start/end_time are inclusive Ex. [start_time => yyyy-mm-dd 00:00:00, end_time => yyyy-mm-dd 23:59:59]
     * will generate "users_game_sessions.end_time >= '{$cursor}' AND users_game_sessions.end_time <= '{$end_date}'"
     * while normal flow will not include start_time (already reported on previous report).
     *
     * @param $date - end_time (standard flow: '' -> 5 sec in the past, regenerate flow: a full date 'Y-m-d H:i:s')
     * @param $cursor - start_time
     * @param $country
     * @param bool $count_rollbacks
     * @param array $excluded_providers
     * @param string $spilHjemmeside (Brand)
     * @return mixed
     */
    public static function kasinoSpilGameSessionData(
        $date,
        $cursor,
        $country,
        $count_rollbacks = true,
        array $excluded_providers = [],
        $spilHjemmeside = ''
    ) {
        $bet_amount_select = 'ugs.bet_amount + SUM(CASE WHEN bt.id IS NOT NULL THEN bt.reward * bt.frb_cost ELSE 0 END)';
        $win_amount_select = 'ugs.win_amount';

        /**
         * When we want to calculate the bets and win amount and taking rollback in the consideration
         * This is used for the old regeneration logic (to get the data that we were not keeping track on the external_regulatory_report_logs table)
         */
        if ($count_rollbacks) {
            $bet_amount_select = '(ugs.bet_amount - ugs.bets_rollback) + SUM(CASE WHEN bt.id IS NOT NULL THEN bt.reward * bt.frb_cost ELSE 0 END)';
            $win_amount_select = '(ugs.win_amount - abs(ugs.wins_rollback))';
        }

        // exclude game session of the required providers
        $excluded_providers_where_clauses = self::getExcludedProvidersWhere($excluded_providers);

        // -5 seconds fail safe to make sure we are not including some data that got inserted during the process.
        $end_date = phive()->hisMod('-5 SECONDS');
        $include_start_time = '';
        if(!empty($date)) {
            $end_date = $date;
            $include_start_time = '=';
        }

        $bonus_entries_status_check = self::getFreeSpinStatusCheck($end_date);

        $sql = "SELECT
                    ugs.id,
                    ugs.device_type_num,
                    ugs.game_ref,
                    ROUND({$bet_amount_select}, 2) as bet_amount,
                    ugs.user_id,
                    ugs.start_time,
                    ugs.end_time,
                    {$win_amount_select} as win_amount,
                    ugs.bet_cnt + SUM(CASE WHEN bt.id IS NOT NULL THEN bt.reward ELSE 0 END) as bet_cnt,
                    u.currency,
                    '{$spilHjemmeside}' as SpilHjemmeside
                FROM
                    users_game_sessions ugs
                INNER JOIN users u ON
                    ugs.user_id = u.id
                LEFT JOIN bonus_entries be ON
                    be.game_session_id = ugs.id
                    AND be.bonus_type = 'freespin'
                    $bonus_entries_status_check
	                AND be.frb_remaining = 0
                LEFT JOIN bonus_types bt ON
                    bt.id = be.bonus_id
                WHERE
                    ugs.end_time >{$include_start_time} '{$cursor}'
                    AND ugs.end_time <= '{$end_date}'
                    AND u.country = '{$country}'
                    AND (ugs.bet_amount > 0
                        OR ugs.win_amount > 0
                        OR ugs.bet_cnt > 0
                        OR ugs.bets_rollback != 0
                        OR ugs.wins_rollback != 0)
                        {$excluded_providers_where_clauses}
                GROUP BY
                    ugs.id
                ORDER BY
                    ugs.end_time DESC";

        return phive('SQL')->shs()->loadArray($sql);
    }

    /**
     * Loops throw the providers keys and created exclude queries for them.
     *
     * @param array $excluded_providers
     * @return string
     */
    public static function getExcludedProvidersWhere(array $excluded_providers = [])
    {
        $exclude_providers_wheres = '';
        foreach ($excluded_providers as $excluded_provider) {
            $exclude_providers_wheres .= " AND users_game_sessions.game_ref NOT LIKE '{$excluded_provider}%'";
        }

        return $exclude_providers_wheres;
    }

    /**
     * Get user game session for the cancel.
     *
     * @param $user_id
     * @param $bet
     * @return mixed
     */
    public static function getCancelSession($user_id, $bet)
    {
        $sql_session = "
                SELECT
                    users_game_sessions.* , users.currency
                FROM
                    users_game_sessions
                    INNER JOIN users ON user_id = users.id
                WHERE
                    users_game_sessions.id = {$bet}
                LIMIT 1
            ";

        return phive('SQL')->sh($user_id)->loadAssoc($sql_session);
    }

    /**
     * Get all cancel reports by token id
     *
     * @param $token_id
     * @return mixed
     */
    public static function getCancelReportsByTokenID($token_id)
    {
        $report_type = SafeConstants::KASINO_SPIL_CANCEL;
        $sql_session = "
                SELECT *
                FROM
                    external_regulatory_report_logs
                WHERE
                    unique_id = {$token_id}
                    AND report_type = '{$report_type}'
                LIMIT 1
            ";

        return phive('SQL')->loadArray($sql_session);
    }

    /**
     * Is there any cancellation on this token in tha data range.
     *
     * @param $token_id
     * @param $from
     * @param $to
     * @return mixed
     */
    public static function hasGameSessionOnDateRange($token_id, $from, $to)
    {
        $report_type = SafeConstants::KASINO_SPIL_CANCEL;
        $sql_session = "
                SELECT *
                FROM
                    external_regulatory_report_logs
                WHERE
                    unique_id = {$token_id}
                    AND report_data_from > '{$from}'
                    AND report_data_to < '{$to}'
                    AND report_type = '{$report_type}'
                LIMIT 1
            ";

        $result = phive('SQL')->loadArray($sql_session);

        return ! empty($result);
    }

    /**
     * Get custom reports that were created from third parties by using the endpoint.
     *
     * @param $token_id
     * @param array $excluded_providers
     * @return mixed
     */
    public static function getKasinoSpilCustomReports($token_id, $excluded_providers = [])
    {
        $excluded_providers = SafeConstants::generateProvidersReportType(SafeConstants::KASINO_SPIL, $excluded_providers);
        $excluded_providers = sprintf("'%s'", implode("','", $excluded_providers ) );

        $sql_session = "
                SELECT *
                FROM
                    external_regulatory_report_logs
                WHERE
                    unique_id = {$token_id}
                    AND report_type IN ({$excluded_providers})
            ";

        return phive('SQL')->loadArray($sql_session);
    }

    /**
     * Get the status check for free spins, varies based on the date since we introduced the check which is important for rectifying/cancelling reports before and after it
     *
     * @param $date
     * @return string
     */
    public static function getFreeSpinStatusCheck($date): string
    {
        if ($date < self::FREE_SPINS_STATUS_CHECK_DATE) {
            return " AND be.status = 'approved'";
        }

        return "";
    }
}
