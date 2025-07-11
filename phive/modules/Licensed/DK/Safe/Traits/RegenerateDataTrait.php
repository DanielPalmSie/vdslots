<?php


trait RegenerateDataTrait
{
    private $end_of_day_id;

    /**
     * We use this value to restore the cursor once regeneration finish.
     * We need to temporary store the value as the current logic relies on "safe_params" and we need to override it.
     *
     * @var string
     */
    private string $current_cursor_to_restore = '';

    /**
     * We use this value to restore the mrvegas cursor once regeneration finish.
     * We need to temporary store the value as the current logic relies on "safe_params" and we need to override it.
     *
     * @var string
     */
    private string $secondary_cursor_to_restore = '';

    /**
     * Max number of retries to regenerate a report in case another report is running.
     * To avoid failing when a little extra wait can solve the issue.
     */
    private $regenerate_max_retry = 60;
    private $regenerate_retry_interval = 15; // Seconds

    /**
     * Temporary store current cursor.
     */
    private function shelveCurrentCursor()
    {
        if(empty($this->safe_params)) {
            $this->extractParams();
        }
        $this->current_cursor_to_restore = $this->safe_params->getCursor();

        // shelve secondary cursor
        $secondary_cursor = $this->getSecondaryCursor();
        $this->secondary_cursor_to_restore = $secondary_cursor['mrvegas'];
    }

    /**
     * Restore the current cursor once regeneration process is over, or in case of failure.
     */
    private function restoreCurrentCursor()
    {
        $this->setCursor($this->current_cursor_to_restore);

        // restore secondary cursor
        $secondary_cursor = $this->getSecondaryCursor();
        $secondary_cursor['mrvegas'] = $this->secondary_cursor_to_restore;
        phive()->miscCache(SAFE::SAFE_CURSOR_SECONDARY, json_encode([$secondary_cursor], JSON_THROW_ON_ERROR), true);
    }

    /**
     * MANUAL ACTION:
     * Regenerate a Zip file in different Tamper Token.
     * Ex. if a MAC validation fail for DGA
     *
     * @param $token_id
     * @param $run - if we want to run only a specific report
     * @param $calculate_rollbacks - if we want to calculate rollbacks
     * @param $regenerate_request_info - if we need to force values in the report ( used for EOD cancellation )
     * @return string|false
     */
    public function regenerateData($token_id, string $run = 'all', string $calculate_rollbacks = 'all', array $regenerate_request_info = [])
    {
        $this->shelveCurrentCursor();

        $is_token_closed = $this->closeTamperToken();
        $retries = 1;
        while($is_token_closed === false && $retries <= $this->regenerate_max_retry) {
            echo "Attempt #$retries. There is another report running. Retrying in $this->regenerate_retry_interval seconds... \n";
            sleep($this->regenerate_retry_interval);
            $retries++;
            $is_token_closed = $this->closeTamperToken();
        }

        if ($is_token_closed === false) {
            echo "Could not regenerate the report.\n";
            echo "Exiting regeneration.\n";
            return false;
        }

        $this->shouldCalculateRollbacks($calculate_rollbacks);

        if($run === 'all' || $run === SafeConstants::KASINO_SPIL) {
            $this->regenerateDataForKasinoSpil($token_id);
        }

        if ($run === 'all' || $run === SafeConstants::END_OF_DAY) {
            $this->regenerateDataForEndOfDay($token_id, [], $regenerate_request_info);
        }

        $token = (string)$this->safe_params->getTokenId();

        $this->restoreCurrentCursor();

        $result = $this->closeTamperToken();
        if ($result === false) {
            echo "ERROR , something was wrong !!";
            return false;
        }

        return $token;
    }

    /**
     * Regenerates data request for kasino spil.
     *
     * @param $token_id
     * @param array $data_range
     * @return bool
     */
    private function regenerateDataForKasinoSpil($token_id = '', array $data_range = [])
    {
        if (empty($data_range)) {
            $data_range = DataService::getKasinoSpilRegenerateDateRange($token_id);
        }

        $this->regenerating_from_token = $token_id;

        $kasino_spil = $this->exportData(
            SafeConstants::KASINO_SPIL,
            $this->iso,
            $data_range['report_data_to'],
            $data_range['report_data_from']
        );

        if ($token_id) {
            $custom_reports = DataService::getKasinoSpilCustomReports($token_id, $this->lic_settings['excluded_providers']);
            foreach ($custom_reports as $custom_report) {
                $kasino_spil_data = json_decode($custom_report['log_info']);
                $this->generateCustomKasinoSpilReport(
                    $kasino_spil_data['game_ref'],
                    $kasino_spil_data['report_type']
                );
            }
        }

        if ($kasino_spil === false) {
            return $this->returnErrorRegenerateData(SafeConstants::KASINO_SPIL);
        }

        if ($token_id) {
            $this->regenerateCancels($token_id);
        }

        return true;
    }

    /**
     * Cancel old KasinoSpil requests to regenerate them again.
     *
     * @param $token_id
     */
    private function regenerateCancels($token_id)
    {
        $all_cancel = DataService::getCancelReportsByTokenID($token_id);

        foreach ($all_cancel as $cancel_report) {
            $log_info = json_decode($cancel_report['log_info']);
            $this->cancelReportSession($log_info->game, $log_info->user_id, $log_info->rollback_type, $log_info->rollback_amount);
        }
    }

    /**
     * Regenerate data request for End of Day.
     *
     * @param $token_id
     * @param array $end_of_day_reports
     * @param array $regenerate_request_info
     * @return bool
     */
    private function regenerateDataForEndOfDay($token_id = '', $end_of_day_reports = [], $regenerate_request_info = [])
    {
        if (empty($end_of_day_reports) && $token_id) {
            $end_of_day_reports = DataService::getEndOfDayReportsByToken($token_id);
        }

        $this->regenerating_from_token = $token_id;

        foreach ($end_of_day_reports as $end_of_day_report) {
            $end_of_day = $this->replacementEndOfTheDay($end_of_day_report, $regenerate_request_info);

            if ($end_of_day === false) {
                return $this->returnErrorRegenerateData(SafeConstants::END_OF_DAY);
            }
        }

        return true;
    }

    /**
     * Export data for end of the day by setting the previous uuid
     *
     * @param $end_of_day
     * @param $regenerate_request_info
     * @return mixed
     */
    public function replacementEndOfTheDay($end_of_day, $regenerate_request_info = [])
    {
        $log_info = json_decode($end_of_day['log_info']);

        $this->uuid = $log_info->uuid;
        if ($this->end_of_day_id) {
            $this->uuid = $this->end_of_day_id;
        }

        return $this->exportData(SafeConstants::END_OF_DAY, $this->iso, $end_of_day['report_data_from'], '', $regenerate_request_info);

    }

    /**
     * Common error handler in case of data regeneration failure.
     * Will restore the "cursor" to his initial state before regeneration process started.
     *
     * @param string $type - which part of regeneration failed
     * @return false
     */
    public function returnErrorRegenerateData(string $type = ''): bool
    {
        $this->restoreCurrentCursor();
        echo "Report could not be regenerated: $type. \n";
        return false;
    }

    /**
     *
     * MANUAL ACTION:
     * Regenerate a Zip file in different Tamper Token.
     * Ex. if a MAC validation fail for DGA
     *
     * @param string $start_time
     * @param string $end_time
     * @param string $run - if we want to run only a specific report
     * @param string $end_of_day_id - regenerate EOD with the custom ID
     * @param string $calculate_rollbacks - regenerate EOD with the custom ID
     * @param array $regenerate_request_info - All information about the regeneration request
     * @return string|false the new token, or false on error
     */
    public function regenerateDataByDateRange(
        string $start_time,
        string $end_time,
        string $run = 'all',
        string $end_of_day_id = "",
        string $calculate_rollbacks = 'all',
        array $regenerate_request_info = []
    ) {
        $this->shelveCurrentCursor();
        $this->setCursor($start_time);
        $is_token_closed = $this->closeTamperToken();

        // Custom ID for regenerating EOD
        $this->end_of_day_id = $end_of_day_id;
        $this->shouldCalculateRollbacks($calculate_rollbacks);

        if ($is_token_closed === false) {
            return $this->returnErrorRegenerateData('token not closed');
        }

        echo "Regenerating data for $start_time - $end_time \n";

        if($run === 'all' || $run === SafeConstants::KASINO_SPIL) {
            $data_range = [
                'report_data_from' => $start_time,
                'report_data_to' => $end_time,
            ];

            $this->regenerateDataForKasinoSpil('', $data_range);
        }

        if ($run === 'all' || $run === SafeConstants::END_OF_DAY) {
            $every_day_reports = $this->getEndOfDayReportsBetweenDateRange($start_time, $end_time);

            $this->regenerateDataForEndOfDay('', $every_day_reports, $regenerate_request_info);
        }

        echo "Regenerated data for $start_time - $end_time \n";

        $token = (string) $this->safe_params->getTokenId();

        $this->restoreCurrentCursor();

        $result = $this->closeTamperToken();
        if ($result === false) {
            echo "ERROR , something was wrong !!\n";
            return false;
        }

        return $token;
    }

    /**
     *
     * Regenerate specific files of a token using sequence number
     *
     * @param array $sequences The sequence numbers of the files to be regenerated
     * @param string $token_id The token of the files
     * @param string $calculate_rollbacks The report types for which rollback should be calculated
     * @return string|false The new token, or false on error
     */
    public function regenerateDataBySequence(
        array $sequences,
        string $token_id,
        string $calculate_rollbacks = 'all'
    ) {
        $this->shelveCurrentCursor();
        $is_token_closed = $this->closeTamperToken();
        $retries = 1;
        while($is_token_closed === false && $retries <= $this->regenerate_max_retry) {
            echo "Attempt #$retries. There is another report running. Retrying in $this->regenerate_retry_interval seconds... \n";
            sleep($this->regenerate_retry_interval);
            $retries++;
            $is_token_closed = $this->closeTamperToken();
        }

        if ($is_token_closed === false) {
            echo "Could not regenerate the report.\n";
            echo "Exiting regeneration.\n";
            return false;
        }

        $this->shouldCalculateRollbacks($calculate_rollbacks);

        echo "Regenerating data for token $token_id \n";

        $reports = phive('SQL')->loadArray("
            SELECT report_data_from, report_data_to, report_type
            FROM external_regulatory_report_logs
            WHERE unique_id = '".$token_id."'
            AND sequence IN (".phive('SQL')->makeIn($sequences).")");

        foreach($reports as $report_data) {
            $start_time = $report_data['report_data_from'];
            $end_time = $report_data['report_data_to'];
            $report_type = $report_data['report_type'];

            if ($report_type === SafeConstants::KASINO_SPIL) {
                $data_range = [
                    'report_data_from' => $start_time,
                    'report_data_to' => $end_time,
                ];

                $this->regenerateDataForKasinoSpil('', $data_range);
            }

            if ($report_type === SafeConstants::END_OF_DAY) {
                $this->regenerateDataForEndOfDay($token_id);
            }
        }

        echo "Regenerated data for $token_id \n";

        $token = (string) $this->safe_params->getTokenId();

        $this->restoreCurrentCursor();

        $result = $this->closeTamperToken();
        if ($result === false) {
            echo "ERROR , something was wrong !!\n";
            return false;
        }

        return $token;
    }

    /**
     * Used to get ed of day reports from misc cache and the date of the report.
     *
     * @param $start_time
     * @param $end_time
     * @return array
     */
    public function getEndOfDayReportsBetweenDateRange($start_time, $end_time)
    {
        $every_day_reports = [];

        $date = strtotime($start_time);
        while ($date <= strtotime($end_time)) {
            $report_day = phive()->hisNow($date, 'Y-m-d');

            $report_prefix = SAFE::DAILY_LOG_KEY . '_' . $report_day;
            $report = phive('SQL')->loadArray("SELECT cache_value FROM misc_cache WHERE id_str LIKE '{$report_prefix}%' ORDER BY id_str DESC LIMIT 1");

            $every_day_reports[] = [
                'report_data_from' => $report_day,
                'log_info' => ! empty($report[0]) ? $report[0]['cache_value'] : [],
            ];

            $date = strtotime('+1 day', $date);
        }

        return $every_day_reports;
    }

    /**
     * Should be called when a regeneration is being done, so we don't have reports overwriting each other.
     *
     * @return bool
     * @throws Exception
     */
    public function regenerateRunning()
    {
        $has_running_reports = phive()->getMiscCache(self::RUNNING_REPORT_KEY);

        if ($has_running_reports || $this->isRegenerationRunning()) {
            echo "Some reports are running please try again.\n";
            return false;
        }

        // Insert SAFE_report_running
        phive()->miscCache(self::REGENERATION_RUNNING, date('Y-m-d H:i:s'));
        return true;
    }

    /**
     * Close regenerate process so the normal reports can continue.
     *
     * @return bool
     */
    public function closeRegenerate()
    {
        return phive('SQL')->delete('misc_cache', ['id_str' => self::REGENERATION_RUNNING]);
    }

    /**
     * Check if a regeneration is running. Failsafe if regeneration is running for more than 20 min remove it.
     *
     * @return bool
     * @throws Exception
     */
    public function isRegenerationRunning()
    {
        $regeneration_reports = phive()->getMiscCache(self::REGENERATION_RUNNING);
        if (!$regeneration_reports) {
            return false;
        }

        $regeneration_start_time = new DateTime($regeneration_reports);
        $current_time = new DateTime();
        $diff = $regeneration_start_time->diff($current_time);
        $time_difference_in_minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

        if ($time_difference_in_minutes > 20) {
            $this->closeRegenerate();
            return false;
        }

        return true;
    }

}
