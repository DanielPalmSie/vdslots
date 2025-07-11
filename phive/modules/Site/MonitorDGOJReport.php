<?php
declare(strict_types=1);

require_once __DIR__ . '/Monitor.php';
require_once __DIR__ . '../../Licensed/ES/ES.php';

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports;

class MonitorDGOJReport extends Monitor
{
    private const DGOJ_REGULATOR = 'DGOJ';
    private const DAILY_MAX_AGE_HOURS = 25;
    private const MONTHLY_MAX_AGE_HOURS = 768;  // 32 days

    private const ICS_REPORTS = [
        Reports\v2\RUD::SUBTYPE,
        Reports\v2\RUT::SUBTYPE,
        Reports\v2\CJD::SUBTYPE,
        Reports\v2\CJT::SUBTYPE
    ];

    private string $regulation_name;

    private ES $ES;

    private SQL $sql;

    private array $params;

    public function __construct()
    {
        parent::__construct();
        $this->sql = phive('SQL')->readOnly();
        $this->ES = new ES;
        $this->regulation_name = $this->ES->getLicSetting('regulation');
    }

    /**
     * Get DGOJ report KPIs
     *
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="rud",frequency="Daily",max_age_hours="25"} 1656026687
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="rut",frequency="Daily",max_age_hours="25"} 1656026513
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="cjd",frequency="Daily",max_age_hours="25"} 1656022410
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="cjt",frequency="Daily",max_age_hours="25"} 1656022442
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="rud",frequency="Monthly",max_age_hours="768"} 1654050022
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="rut",frequency="Monthly",max_age_hours="768"} 1654049617
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="cjd",frequency="Monthly",max_age_hours="768"} 1654049617
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="cjt",frequency="Monthly",max_age_hours="768"} 1655969381
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="opt",license_type="blj",frequency="Monthly",max_age_hours="768"} 1654049576
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="opt",license_type="rlt",frequency="Monthly",max_age_hours="768"} 1654049576
     * Format is report_last_timestamp {brand="videoslots",regulation="DGOJ",name="opt",license_type="aza",frequency="Monthly",max_age_hours="768"} 1654049576
     * Format is report_active_users_without_verification_date {brand="videoslots",regulation="DGOJ"} 0
     * Format is report_overlapping_game_sessions {brand="videoslots",regulation="DGOJ"} 0
     * Format is report_games_without_game_tags {brand="videoslots",regulation="DGOJ"} 39
     *
     * @param array $params
     * @return string
     */
    public function getReportData(array $params): string
    {
        $this->params = $params;

        $this->getLatestDailyReportTimestamp();

        $this->getLatestMonthlyReportTimestamp();

        $this->getLatestOPTReportTimestamp();

        $this->getActiveUsersWithoutVerificationDate();

        $this->getOverlappingGameSessions();

        $this->getGamesWithoutGameTags();

        return $this->parseResponseWithMultipleLabels();
    }

    /**
     * Get latest timestamp of daily reports
     *
     * @return void
     */
    protected function getLatestDailyReportTimestamp(): void
    {
        foreach (self::ICS_REPORTS as $report_type) {
            $query = "
                SELECT UNIX_TIMESTAMP(created_at) as report_timestamp
                FROM
                    external_regulatory_report_logs
                WHERE
                    report_type = '{$report_type}'
                    AND regulation = '{$this->regulation_name}'
                    AND SUBSTRING(report_data_from, 1, 10) = SUBSTRING(report_data_to, 1, 10)
                ORDER BY
                    created_at DESC
                LIMIT 1
                ";

            $latest_value = $this->sql->getValue($query);

            $this->metrics['report_last_timestamp'][] = [
                'value' => $latest_value !== false ? $latest_value : null,
                'sub_labels' => [
                    'brand' => $this->config['brand'],
                    'regulation' => self::DGOJ_REGULATOR,
                    'name' => strtolower($report_type),
                    'frequency' => ICSConstants::DAILY_FREQUENCY,
                    'max_age_hours' => self::DAILY_MAX_AGE_HOURS
                ]
            ];
        }
    }

    /**
     * Get latest timestamp of monthly reports
     *
     * @return void
     */
    protected function getLatestMonthlyReportTimestamp(): void
    {
        foreach (self::ICS_REPORTS as $report_type) {
            $query = "
                SELECT UNIX_TIMESTAMP(created_at) as report_timestamp
                FROM
                    external_regulatory_report_logs
                WHERE
                    report_type = '{$report_type}'
                    AND regulation = '{$this->regulation_name}'
                    AND SUBSTRING(report_data_from, 1, 10) != SUBSTRING(report_data_to, 1, 10)
                ORDER BY
                    created_at DESC
                LIMIT 1
                ";
            $latest_value = $this->sql->getValue($query);

            $this->metrics['report_last_timestamp'][] = [
                'value' => $latest_value !== false ? $latest_value : null,
                'sub_labels' => [
                    'brand' => $this->config['brand'],
                    'regulation' => self::DGOJ_REGULATOR,
                    'name' => strtolower($report_type),
                    'frequency' => ICSConstants::MONTHLY_FREQUENCY,
                    'max_age_hours' => self::MONTHLY_MAX_AGE_HOURS
                ]
            ];
        }
    }

    /**
     * Get latest timestamp of monthly OPT reports
     *
     * @return void
     */
    protected function getLatestOPTReportTimestamp(): void
    {
        $opt_name = Reports\v2\OPT::SUBTYPE;
        $opt_sub_reports = $this->ES->getLicSetting('ICS')['licensed_external_game_types'];

        foreach ($opt_sub_reports as $report_type) {
            $query = "
                SELECT UNIX_TIMESTAMP(created_at) as report_timestamp
                FROM
                    external_regulatory_report_logs
                WHERE
                    report_type = '{$opt_name}'
                    AND regulation = '{$this->regulation_name}'
                    AND filename_prefix LIKE '%_{$report_type}_%'
                    AND SUBSTRING(report_data_from, 1, 10) != SUBSTRING(report_data_to, 1, 10)
                ORDER BY
                    created_at DESC
                LIMIT 1
                ";

            $latest_value = $this->sql->getValue($query);

            $this->metrics["report_last_timestamp"][] = [
                'value' => $latest_value !== false ? $latest_value : null,
                'sub_labels' => [
                    'brand' => $this->config['brand'],
                    'regulation' => self::DGOJ_REGULATOR,
                    'name' => strtolower($opt_name),
                    'license_type' => strtolower($report_type),
                    'frequency' => ICSConstants::MONTHLY_FREQUENCY,
                    'max_age_hours' => self::MONTHLY_MAX_AGE_HOURS
                ]
            ];
        }
    }

    /**
     * Get users who are active without having the verification date in users_settings table
     *
     * @return void
     */
    private function getActiveUsersWithoutVerificationDate(): void
    {
        $query = "
            SELECT COUNT(*)
            FROM
                 users AS u
            WHERE
                country = '" . ICSConstants::COUNTRY . "'
                AND id IN
                    (SELECT us.user_id
                    FROM users_settings AS us
                    WHERE us.setting = 'current_status'
                        AND (us.value = '" . UserStatus::STATUS_ACTIVE . "'
                        OR us.value = '" . UserStatus::STATUS_PENDING_VERIFICATION . "'))
                AND NOT EXISTS
                    (SELECT 1 FROM users_settings as us
                    WHERE us.setting = 'first_verification_date' AND us.user_id = u.id)
            ";

        $this->metrics['report_active_users_without_verification_date'] = [
            'value' => (int)$this->sql->getValue($query),
            'sub_labels' => [
                'brand' => $this->config['brand'],
                'regulation' => self::DGOJ_REGULATOR
            ]
        ];
    }

    /**
     * Get overlapping game sessions
     *
     * @return void
     */
    private function getOverlappingGameSessions(): void
    {
        $interval = $this->params['interval'] ?? 60;

        $query = "
            SELECT COUNT(*)
            FROM users_game_sessions ugs
            WHERE
                EXISTS (SELECT id FROM users_game_sessions ugs2
                    WHERE ugs.end_time>=ugs2.start_time
                      AND ugs.start_time >=ugs2.start_time
                      AND ugs.start_time <= ugs2.end_time
                      AND ugs2.start_time >= (NOW() - INTERVAL {$interval} MINUTE)
                      AND ugs.id!=ugs2.id
                      AND ugs.user_id=ugs2.user_id)
                AND user_id IN
                    (SELECT id FROM users
                    WHERE country='" . ICSConstants::COUNTRY . "');
            ";

        $this->metrics['report_overlapping_game_sessions'] = [
            'value' => (int)$this->sql->getValue($query),
            'sub_labels' => [
                'brand' => $this->config['brand'],
                'regulation' => self::DGOJ_REGULATOR
            ]
        ];
    }

    /**
     * Get all games enabled in Spain that do not have their game tags set
     *
     * @return void
     */
    private function getGamesWithoutGameTags(): void
    {
        $query = "
            SELECT COUNT(*)
            FROM micro_games mg
            LEFT JOIN game_tag_con gtc
                ON gtc.game_id = mg.id
            WHERE
                mg.blocked_countries NOT LIKE '%" . ICSConstants::COUNTRY . "%'
                AND (mg.included_countries = '' OR mg.included_countries LIKE '%" . ICSConstants::COUNTRY . "%')
                AND gtc.game_id IS NULL;
            ";

        $this->metrics['report_games_without_game_tags'] = [
            'value' => (int)$this->sql->getValue($query),
            'sub_labels' => [
                'brand' => $this->config['brand'],
                'regulation' => self::DGOJ_REGULATOR
            ]
        ];
    }

}
