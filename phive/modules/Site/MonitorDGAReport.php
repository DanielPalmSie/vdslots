<?php
declare(strict_types=1);

require_once __DIR__ . '/Monitor.php';
require_once __DIR__ . '../../Licensed/DK/Safe/SAFE.php';

/**
 *
 */
class MonitorDGAReport extends Monitor
{
    private const DGA_REGULATOR = 'DGA';
    private const KASINOSPIL_MAX_AGE_SECONDS = 1800;
    private const ENDOFDAY_MAX_AGE_SECONDS = 90000;
    private const TAMPERTOKEN_MAX_AGE_SECONDS = 90000;

    private SQL $sql;

    private array $params;

    private string $regulation_name = 'SAFE';

    public function __construct()
    {
        parent::__construct();
        $this->sql = phive('SQL')->readOnly();
    }

    /**
     * Get DGA report KPIs
     *
     * Format is report_last_timestamp {brand="videoslots",regulation="DGA",name="kasinospil",max_age_seconds="1800"} 1654843510
     * Format is report_last_timestamp {brand="videoslots",regulation="DGA",name="endofday",max_age_seconds="90000"} 1654826839
     * Format is report_tampertoken_last_timestamp_start {brand="videoslots",regulation="DGA",max_age_seconds="90000"} 1654833004
     * Format is report_count {brand="videoslots",regulation="DGA",name="kasinospil"} 5
     * Format is report_count {brand="videoslots",regulation="DGA",name="endofday"} 1
     *
     * @param array $params
     * @return string
     */
    public function getReportData(array $params): string
    {
        $this->params = $params;

        // get kasinospill last timestamp
        $latest_kasinospil_report = $this->getLatestReportByReportType(SafeConstants::KASINO_SPIL);
        $this->metrics['report_last_timestamp'][] = [
            'value' => $latest_kasinospil_report['report_timestamp'] ?? null,
            'sub_labels' => [
                'brand' => $this->config['brand'],
                'regulation' => self::DGA_REGULATOR,
                'name' => strtolower(SafeConstants::KASINO_SPIL),
                'max_age_seconds' => self::KASINOSPIL_MAX_AGE_SECONDS
            ]
        ];

        // get endofday last timestamp
        $latest_endofday_report = $this->getLatestReportByReportType(SafeConstants::END_OF_DAY);
        $this->metrics['report_last_timestamp'][] = [
            'value' => $latest_endofday_report['report_timestamp'] ?? null,
            'sub_labels' => [
                'brand' => $this->config['brand'],
                'regulation' => self::DGA_REGULATOR,
                'name' => strtolower(SafeConstants::END_OF_DAY),
                'max_age_seconds' => self::ENDOFDAY_MAX_AGE_SECONDS
            ]
        ];

        // get tampertoken last timestamp
        $latest_tampertoken_start = $this->getLatestTamperToken();
        $this->metrics['report_tampertoken_last_timestamp_start'] = [
            'value' => $latest_tampertoken_start,
            'sub_labels' => [
                'brand' => $this->config['brand'],
                'regulation' => self::DGA_REGULATOR,
                'max_age_seconds' => self::TAMPERTOKEN_MAX_AGE_SECONDS
            ]
        ];

        // no. of kasinospil reports generated
        $kasinospil_report_count = $this->getReportCountByReportType(SafeConstants::KASINO_SPIL);
        $this->metrics['report_count'][] = [
            'value' => $kasinospil_report_count,
            'sub_labels' => [
                'brand' => $this->config['brand'],
                'regulation' => self::DGA_REGULATOR,
                'name' => strtolower(SafeConstants::KASINO_SPIL)
            ]
        ];

        // no. of endofday reports generated
        $endofday_report_count = $this->getReportCountByReportType(SafeConstants::END_OF_DAY);
        $this->metrics['report_count'][] = [
            'value' => $endofday_report_count,
            'sub_labels' => [
                'brand' => $this->config['brand'],
                'regulation' => self::DGA_REGULATOR,
                'name' => strtolower(SafeConstants::END_OF_DAY)
            ]
        ];

        return $this->parseResponseWithMultipleLabels();
    }

    /**
     * Get latest report by report type
     *
     * @param string $report_type
     * @return array|null
     */
    protected function getLatestReportByReportType(string $report_type): ?array
    {
        $query = "
                SELECT UNIX_TIMESTAMP(created_at) as report_timestamp
                FROM 
                    external_regulatory_report_logs
                WHERE
                    report_type = '{$report_type}'
                    AND regulation = '{$this->regulation_name}'
                ORDER BY 
                    created_at DESC
                LIMIT 1
                ";

        return $this->sql->loadAssoc($query);
    }

    /**
     * Get report count by report type
     *
     * @param string $report_type
     * @return int
     */
    protected function getReportCountByReportType(string $report_type): int
    {
        $interval = $this->params['interval'] ?? 15;

        $query = "
                SELECT COUNT(*)
                FROM 
                    external_regulatory_report_logs
                WHERE
                    report_type = '{$report_type}'
                    AND regulation = '{$this->regulation_name}'
                AND created_at >= (NOW() - INTERVAL {$interval} MINUTE)   
                ";

        return (int)$this->sql->getValue($query);
    }

    /**
     * Get latest TamperToken Timestamp
     *
     * @return int
     */
    protected function getLatestTamperToken(): int
    {
        $safe_params = phive()->getMiscCache(SAFE::SAFE_PARAM_KEY);
        $safe_params = json_decode($safe_params, true);
        return strtotime($safe_params['TamperTokenUdstedelseDatoTid']);
    }

}