<?php

namespace NL\CDB\Reports;

use DateTime;
use Exception;
use NL;
use NL\CDB\Constants\CDBConstants;
use Spatie\ArrayToXml\ArrayToXml;
use SQL;

abstract class BaseReport
{
    public const NAME = 'abstract';
    protected const MAX_RECORDS_COUNT = 512;

    protected SQL $db;
    private DateTime $extraction_date;
    private array $lic_settings;
    private string $record_id;
    private string $operator_id;
    private string $data_save_id;

    /**
     * BaseReport constructor.
     *
     * @param array $report_settings
     * @throws Exception
     */
    public function __construct(array $report_settings = [])
    {
        $this->db = phive('SQL');
        $this->lic_settings = (new NL())->getAllLicSettings();

        $this->setExtractionDate($report_settings['extraction_date'] ?? 'now');
    }

    /**
     * @param array $records
     * @param string $group
     *
     * @return array
     */
    public function setupRecords(array $records, string $group = ''): array
    {
        return [];
    }

    /**
     * Transform report data to XML
     *
     * @param bool $signed
     * @return string
     */
    public function toXML(bool $signed = false): string
    {
        $report_content = [
            static::NAME => $this->getCommonData() + $this->getReportData(),
        ];

        $report_xml = ArrayToXml::convert($report_content, $this->getRootElement(), true, 'UTF-8');

        if ($signed) {
            // TODO sign xml
        }

        return $report_xml;
    }

    /**
     * Setup filter users query to be used by subclasses
     *
     * @param string $user_id_column
     *
     * @return string
     */
    protected function filterUsers(string $user_id_column): string
    {
        return "
            AND {$user_id_column} NOT IN (
                SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'test_account' AND u_s.value = 1
            )
        ";
    }

    /**
     * @return array
     */
    protected function getLicSettings(): array
    {
        return $this->lic_settings;
    }

    /**
     *
     *  Get value for the given key from license settings,
     *  allow multilevel using dot notation level1.level2
     *  if doesnt exist return default value
     *
     * @param string $key access key (dot notation)
     * @param null $default default value
     * @return array|mixed|null value
     */
    protected function getLicSetting(string $key, $default = null)
    {
        if (empty($key)) {
            return null;
        }

        $settings = $this->lic_settings;

        foreach (explode('.', $key) as $segment) {
            if (!isset($settings[$segment])) {
                return $default;
            }

            $settings = $settings[$segment];
        }

        return $settings;
    }

    /**
     * @return string
     */
    protected function getCountry(): string
    {
        return CDBConstants::COUNTRY;
    }

    /**
     * @return array
     */
    protected function getReportData(): array
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getFileName(): string
    {
       return implode('_', [
           strtolower(static::NAME),
           $this->getCounterXML(),
           $this->getExtractionDateFormatted(),
       ]);
    }

    /**
     * @param array $report_data
     * @return iterable
     */
    protected function getGroupedRecords(array $report_data): iterable
    {
        foreach (array_chunk($report_data, self::MAX_RECORDS_COUNT) as $data) {
            yield $data;
        }
    }

    /**
     * Get the XML root element
     *
     * @return array
     */
    private function getRootElement() :array
    {
        return [
            'rootElementName' => 'Root',
            '_attributes' => [
                'xmlns:xsi' => CDBConstants::XMLNS_XSI,
                'xsi:schemaLocation' => strtolower(static::NAME) . '.xsd',
            ]
        ];
    }

    /**
     * @return string
     */
    private function getRecordID(): string
    {
        return $this->record_id;
    }

    /**
     * @param string $record_id
     */
    private function setRecordID(string $record_id): void
    {
        $this->record_id = $record_id;
    }

    /**
     * @return string
     */
    private function getExtractionDate(): string
    {
        return $this->extraction_date;
    }

    /**
     * @param string $format
     * @return string
     */
    private function getExtractionDateFormatted(string $format = 'ymdhis'): string
    {
        return $this->extraction_date->format($format);
    }

    /**
     * Set the report period start
     *
     * @param string $date
     *
     * @return self
     * @throws Exception
     */
    private function setExtractionDate(string $date = 'now'): self
    {
        $this->extraction_date = new DateTime($date);

        return $this;
    }

    private function getOperatorID(): string
    {
        return $this->operator_id;
    }

    /**
     * @param string $operator_id
     */
    private function setOperatorID(string $operator_id): void
    {
        $this->operator_id = $operator_id;
    }

    /**
     * @return string
     */
    private function getDataSafeID(): string
    {
        return $this->data_save_id;
    }

    /**
     * @param string $data_safe_id
     */
    private function setDataSafeID(string $data_safe_id): void
    {
        $this->data_save_id = $data_safe_id;
    }

    /**
     * @return string[]
     */
    private function getCommonData(): array
    {
        return [
            'Record_ID' => $this->getRecordID(),
            'Extraction_Date' => $this->getExtractionDate(),
            'Operator_ID' => $this->getOperatorID(),
            'Data_Safe_ID' => $this->getDataSafeID(),
        ];
    }

    /**
     * Sequential counter of XML, with leading zeros.
     * Need to be 10 digits. This needs to be reset at the beginning of a new day.
     *
     * @return string
     */
    private function getCounterXML(): string
    {
        $count_today_reports = 0; // TODO get counter from DB or count files that was generated today

        return sprintf('%010d', ++$count_today_reports);
    }
}