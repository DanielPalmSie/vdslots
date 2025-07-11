<?php
declare(strict_types=1);

namespace ES\ICS\Validation;


use DOMDocument;
use XML\XAdES;
use JsonException;
use Spatie\ArrayToXml\ArrayToXml;

class Report
{

    private array $lic_settings;

    public function __construct($lic_settings = [])
    {
        $this->lic_settings = $lic_settings;
    }

    /**
     * @throws JsonException
     * @return string the generated file name
     */
    public function createReport(int $year, int $month): string
    {
        $report = $this->generateValidationReport($year, $month, $this->getLicSetting('ICS.verification_responsible'));

        $file_path = $this->getLicSetting('ICS.verification_export_folder', '/tmp');
        $filename_prefix = 'verification_report_'.date('YmdHis');
        $filename =
            $file_path.
            '/'.
            $filename_prefix.
            '.xml'
        ;

        $hash = $this->saveToXML($report, $filename);
        $this->saveLog($report, $file_path, $filename_prefix, $hash);
        return $filename;
    }


    public function generateValidationReport(int $year, int $month, $responsible = ''): array
    {
        $phive = phive();

        $report = [
            'reported_year' => $year,
            'reported_month' => $month,
            'report_date' => $phive->hisNow(),
            'report_responsible' => $responsible,
            'reports' => [],
        ];

        $report_classes = [
            RUD::class,
            RUT::class,
            CJD::class,
            CJT::class,
            OPT::class,
            JUD::class,
            JUT::class,
        ];

        foreach ($report_classes as $class_name) {
            /** @var Validation $class */
            $class = new $class_name();
            $report['reports'][$class::REPORT_TYPE] = $class->validate($report);
        }

        return $report;
    }

    /**
     * @param array $report
     * @param string $filename
     * @param bool $signed add XAdES signature
     * @return false|string
     */
    public function saveToXML(array $report, string $filename, bool $signed = true)
    {
        $report_content = $this->formatXML($report);
        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $xml->loadXML($report_content);

        if($signed){
            $private_key = $this->getLicSetting('DGOJ.ssl.private_key');
            $certificate = $this->getLicSetting('DGOJ.ssl.certificate');

            XAdES::sign($xml, $private_key, $certificate);
        }

        $xml_data = $xml->saveXML();

        file_put_contents($filename, $xml_data);

        return sha1_file($filename);
    }

    /**
     * @throws JsonException
     */
    public function saveLog(array $report, string $file_path, string $filename_prefix, string $hash): void
    {
        $start = date('Y-m-d H:i:s', mktime(23, 59, 59, $report['reported_month'], 1, $report['reported_year']));
        $end = date('Y-m-d H:i:s', mktime(23, 59, 59, $report['reported_month'] + 1, 0, $report['reported_year']));

        phive('SQL')->insertArray("external_regulatory_report_logs", [
            'regulation' => $this->getLicSetting('regulation').'_verification',
            'report_type' => 'verif',
            'report_data_from' => $start,
            'report_data_to' => $end,
            'unique_id' => $report['report_date'],
            'sequence' => 0,
            'filename_prefix' => $filename_prefix,
            'file_path' => $file_path,
            'log_info' => json_encode([
                'reported_year' => $report['reported_year'],
                'reported_month' => $report['reported_month'],
                'report_date' => $report['report_date'],
                'report_responsible' => $report['report_responsible'],
                'file_hash' => $hash,
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    public function formatXML(array $report): string
    {
        $report_classes = [
            RUD::REPORT_TYPE,
            RUT::REPORT_TYPE,
            CJD::REPORT_TYPE,
            CJT::REPORT_TYPE,
            OPT::REPORT_TYPE,
            JUD::REPORT_TYPE,
            JUT::REPORT_TYPE,
        ];

        foreach ($report_classes as $report_class) {
            $report['reports'][$report_class] = $this->formatReportXML($report['reports'][$report_class]);
        }

        static::convertBoolsToText($report);

        return ArrayToXml::convert($report, 'report');
    }

    public function formatReportXML(array $report): array
    {
        $files = ['Daily' => [], 'Monthly' => []];

        if (!array_key_exists('Daily', $report[1])) {
            unset($files['Daily']);
        }
        if (!array_key_exists('Monthly', $report[1])) {
            unset($files['Monthly']);
        }

        foreach ($report[1]['Daily'] as $date => $day_files) {
            $day = [];
            $day['_attributes']['date'] = $date;
            if ($day_files) {
                $day['file'] = [];
                foreach ($day_files as $f) {
                    $day['file'][] = static::reportToElement($f);
                }
            }
            $files['Daily']['Day'][] = $day;
        }


        foreach ($report[1]['Monthly'] as $f) {
            $files['Monthly']['file'][] = $f ? static::reportToElement($f): [];
        }

        return [
            'summary' => $report[0],
            'files' => $files,
        ];
    }

    protected static function reportToElement(array $report_data): array
    {
        $report_data['filename'] = basename($report_data['filename']);
        unset($report_data['file_path'], $report_data['filename_prefix']);
        $file = [
            '_attributes' => $report_data,
        ];
        if (is_array($report_data['xsd'])) {
            $report_data['xsd_errors'] = reset($report_data['xsd']);
            $file['_attributes']['xsd'] = 'Invalid';
            unset($report_data['xsd']);
        }
        foreach($report_data as $key => $attribute){
            if(is_array($attribute)){
                unset($file['_attributes'][$key]);
                $file[$key] = ['_cdata' => implode("\n", $attribute)];
            }
        }

        return $file;
    }

    protected static function convertBoolsToText(array &$array): void
    {
        array_walk_recursive($array, static function (&$val) {
            if (is_bool($val)) {
                $val = $val ? 'Yes' : 'No';
            }
        });
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
            return $this->lic_settings;
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
}
