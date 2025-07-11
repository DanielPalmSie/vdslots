<?php
declare(strict_types=1);

namespace ES\ICS\Validation;


use DOMDocument;
use DOMElement;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports;
use JsonException;
use Licensed;
use Phive;
use PhpZip\Constants\ZipEncryptionMethod;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use SQL;
use XML\XSDValidator;

abstract class Validation
{
    public const REPORT_TYPE = 'BASE';
    protected static ZipFile $zip;
    protected static string $zip_password;
    /**
     * @var bool[]
     */
    protected array $checks = [
        'days missing' => false,
        'monthly missing' => false,
        'files missing' => false,
        'wrong zip format' => false,
        'wrong zip structure' => false,
    ];
    protected string $regulation;
    protected array $additional_fields;
    /**
     * @var Licensed
     */
    protected $license;
    /**
     * @var Reports\BaseReport|string
     */
    protected $report_class;
    protected XSDValidator $xsd_validator;
    protected Phive $phive;
    /**
     * @var SQL
     */
    protected $db;
    protected bool $checks_daily = true;
    protected bool $checks_monthly = true;
    protected bool $reports_from_range = false;

    public function __construct()
    {
        /** @var Phive $phive */
        $this->phive = phive();
        /** @var SQL $db */
        $this->db = phive('SQL');
        /** @var Licensed $license */
        $this->license = phive('Licensed/ES/ES');

        $this->regulation = $this->license->getLicSetting('regulation');

        $this->xsd_validator = new XSDValidator(__DIR__ . '/xsd');

    }

    /**
     * Used to avoid problems in float to float comparison
     * @param $val1
     * @param $val2
     * @return bool
     */
    public static function compareAmounts($val1, $val2): bool
    {
        $val1 = (int) round($val1*100);
        $val2 = (int) round($val2*100);

        return $val1===$val2;
    }

    /**
     * Small convenience method to reduce line length
     * @param string $name
     * @param DOMDocument|DOMElement $xml
     * @return string
     */
    protected static function getTagValue(string $name, $xml): string
    {
        return $xml->getElementsByTagName($name)[0]->nodeValue;
    }

    public function validate(array $current_report): array
    {
        $month = $current_report['reported_month'];
        $year = $current_report['reported_year'];

        //Check that files were generated
        $cur_day = 1;

        $cur_date = $start_date = mktime(0, 0, 0, $month, $cur_day, $year);
        $end_date = mktime(23, 59, 59, $month + 1, 0, $year);

        $present_reports = [
            '_aux' => [],
        ];

        //daily
        if ($this->checks_daily) {
            $present_reports[ICSConstants::DAILY_FREQUENCY] = [];


            while ($cur_date < $end_date) {
                $cur_date_end = mktime(23, 59, 59, $month, $cur_day, $year);

                $reports = $this->getReports($cur_date, $cur_date_end);

                if (empty($reports)) {
                    $this->checks['days missing'] = true;
                } else {
                    $reports = $this->checkFiles($reports, $cur_date);
                }

                $present_reports[ICSConstants::DAILY_FREQUENCY][$this->phive->hisNow($cur_date, 'Y-m-d')] = $reports;

                $cur_day++;
                $cur_date = mktime(0, 0, 0, $month, $cur_day, $year);
            }
        } else {
            unset($this->checks['days missing']);
        }
        //monthly

        if ($this->checks_monthly) {
            $present_reports[ICSConstants::MONTHLY_FREQUENCY] = [];


            $reports = $this->getReports($start_date, $end_date);

            if (empty($reports)) {
                $this->checks['monthly missing'] = true;
            } else {
                $reports = $this->checkFiles($reports, 0);
            }


            $present_reports[ICSConstants::MONTHLY_FREQUENCY] = $reports;
        } else {
            unset($this->checks['monthly missing']);
        }


        $present_reports = $this->checkValues($present_reports, $current_report);

        $this->afterValidate($current_report, $present_reports);

        return [$this->checks, $present_reports];
    }

    /**
     * Obtain generated reports from log
     * @param $start
     * @param $end
     * @return array
     * @throws JsonException
     */
    protected function getReports($start, $end): array
    {
        $report_type = static::REPORT_TYPE;
        $from_operator = $to_operator = '=';

        if ($this->reports_from_range) {
            $from_operator = '>=';
            $to_operator = '<=';
        }

        $sql = "
                SELECT file_path, filename_prefix, report_data_from, report_data_to, unique_id, log_info
                FROM
                    external_regulatory_report_logs
                WHERE
                    regulation = '{$this->regulation}'
                    AND report_type = '{$report_type}'
                    AND report_data_from {$from_operator} '{$this->phive->hisNow($start, ICSConstants::DATETIME_DBFORMAT)}'
                    AND report_data_to {$to_operator} '{$this->phive->hisNow($end, ICSConstants::DATETIME_DBFORMAT)}'
                ORDER BY
                    report_data_from, created_at
                ";

        $reports = $this->db->loadArray($sql);
        $rectified = [];

        foreach ($reports as &$report) {
            $log_info = json_decode($report['log_info'], true, 512, JSON_THROW_ON_ERROR);
            if ($log_info['rectification_info']) {
                $rectified[$log_info['rectification_info']['Rectificacion']['RegistroId']] = 1;
            }
            unset($report['log_info']);
        }
        unset($report);

        if($rectified) {
            foreach ($reports as $i => $report) {
                if (isset($rectified[$report['unique_id']])) {
                    unset($reports[$i]);
                } else {
                    unset($reports[$i]['unique_id']);
                }
            }
        }

        return array_values($reports);

    }

    /**
     * Check the zip files for correct structure
     * @param array $reports
     * @param int $date used for JU
     * @return array
     */
    protected function checkFiles(array $reports, int $date): array
    {

        $zip = new ZipFile();
        $pass = $this->license->getLicSetting('regulation_password');

        foreach ($reports as &$rep) {
            $filename = $rep['file_path'] . '/' . $rep['filename_prefix'] . '.zip';
            $rep['filename'] = $filename;
            $rep['file_exists'] = file_exists($filename);
            $rep['zip'] = 'Skipped';
            $rep['xsd'] = 'Skipped';

            $rep['dates'] = 'Skipped';
            $rep['same_data_in_db'] = 'Skipped';
            foreach ($this->additional_fields as $field) {
                $rep[$field] = 'Skipped';
            }

            if (!$rep['file_exists']) {
                $this->checks['files missing'] = true;
                continue;
            }

            if(!$this->checkFileNameWithDB($rep)) {
                $rep['invalid_filename'] = true;
            }

            try {
                $zip->openFile($filename);

                if ($zip->count() !== 1 || !$zip->hasEntry('enveloped.xml')) {
                    $rep['zip'] = 'Required filename not found.';
                    $this->checks['wrong zip structure'] = true;
                    continue;
                }
                if (($em = $zip->getEntry('enveloped.xml')->getEncryptionMethod()) !== ZipEncryptionMethod::WINZIP_AES_256) {
                    $rep['zip'] = 'Invalid encryption used: ' . ZipEncryptionMethod::getEncryptionMethodName($em);
                    $this->checks['wrong zip format'] = true;
                    continue;
                }
            } catch (ZipException $e) {
                $rep['zip'] = 'Error reading file: ' . $e->getMessage();
                $this->checks['wrong zip format'] = true;
                continue;
            }

            $rep['zip'] = 'Valid';

            if ($validation_errors = $this->xsd_validator->validate($filename, $pass)) {
                $rep['xsd'] = $validation_errors;
                continue;
            }

            $rep['xsd'] = 'Valid';

        }
        return $reports;
    }

    /**
     * Check correctness of information
     *
     * @param array $reports
     * @param array $current_reports
     * @return array
     */
    abstract protected function checkValues(array $reports, array $current_reports): array;

    /**
     * Child classes can extend it for extra validations after the common path
     * @param array $current_report
     * @param array $present_reports
     */
    protected function afterValidate(array $current_report, array $present_reports): void
    {

    }

    /**
     * Obtain current data directly from the database
     * @param string $start
     * @param string $end
     * @param string $frequency
     * @return Reports\BaseReport[]
     */
    protected function getDBInfo(string $start, string $end, string $frequency): iterable
    {
        /** @var Reports\BaseReport $report */
        $report = new $this->report_class(
            get_class($this->license), //getIso() is protected... change to public or use reflection?
            $this->license->getAllLicSettings(),
            [
                'period_start' => $start,
                'period_end' => $end,
                'frequency' => $frequency,
            ]
        );
        return $report->getFiles();
    }

    protected function readXML(string $filename): ?DOMDocument
    {
        if (!isset(self::$zip)) {
            self::$zip = new ZipFile();
            self::$zip_password = $this->license->getLicSetting('regulation_password');
        }

        try {
            self::$zip->openFile($filename);
            self::$zip->setReadPassword(self::$zip_password);

            $xml = new DOMDocument();
            $xml->loadXML(self::$zip->getEntryContents('enveloped.xml'));
        } catch (ZipException $e) {
            $xml = null;
        }

        return $xml;
    }

    protected function checkHasMovements(
        DOMElement $depositos,
        DOMElement $retiradas,
        DOMElement $participacion,
        DOMElement $participaciondevolucion,
        DOMElement $premios,
        DOMElement $comision,
        DOMElement $bonos,
        DOMElement $otros,
        DOMElement $premiosespecie,
        DOMElement $ajustepremios,
        DOMElement $trans_in,
        DOMElement $trans_out
    ): bool
    {
        $isNotZero = $depositos->getElementsByTagName('Operaciones')->count();
        $isNotZero = $isNotZero || $retiradas->getElementsByTagName('Operaciones')->count();
        $isNotZero = $isNotZero || (double)static::getTagValue(
                'Cantidad',
                $participacion->getElementsByTagName('Total')[0]
            );
        $isNotZero = $isNotZero || (double)static::getTagValue(
                'Cantidad',
                $participaciondevolucion->getElementsByTagName('Total')[0]
            );
        $isNotZero = $isNotZero || (double)static::getTagValue(
                'Cantidad',
                $premios->getElementsByTagName('Total')[0]
            );
        $isNotZero = $isNotZero || (double)static::getTagValue(
                'Cantidad',
                $comision->getElementsByTagName('Total')[0]
            );
        $isNotZero = $isNotZero || ((double)static::getTagValue(
                'Cantidad',
                $bonos->getElementsByTagName('Total')[0]
            ) && $bonos->getElementsByTagName('Desglose')->count());
        $isNotZero = $isNotZero || (double)static::getTagValue(
                'Cantidad',
                $otros->getElementsByTagName('Total')[0]
            );
        $isNotZero = $isNotZero || (double)static::getTagValue(
                'Cantidad',
                $premiosespecie->getElementsByTagName('Total')[0]
            );
        $isNotZero = $isNotZero || (double)static::getTagValue(
                'Cantidad',
                $ajustepremios->getElementsByTagName('Total')[0]
            );
        $isNotZero = $isNotZero || (double)static::getTagValue(
                'Cantidad',
                $trans_in->getElementsByTagName('Total')[0]
            );
        $isNotZero = $isNotZero || (double)static::getTagValue(
                'Cantidad',
                $trans_out->getElementsByTagName('Total')[0]
            );

        return $isNotZero;
    }

    protected function checkFinalBalance(
        DOMElement $depositos,
        DOMElement $retiradas,
        DOMElement $participacion,
        DOMElement $participaciondevolucion,
        DOMElement $premios,
        DOMElement $comision,
        DOMElement $bonos,
        DOMElement $otros,
        DOMElement $premiosespecie,
        DOMElement $ajustepremios,
        DOMElement $trans_in,
        DOMElement $trans_out,
        DOMElement $saldo_inicial,
        DOMElement $saldo_final
    ): bool
    {
        $balance = $this->calcPlayerFinalBalance(
            $depositos,
            $retiradas,
            $participacion,
            $participaciondevolucion,
            $premios,
            $comision,
            $bonos,
            $otros,
            $premiosespecie,
            $ajustepremios,
            $trans_in,
            $trans_out,
            $saldo_inicial
        );

        return static::compareAmounts($balance, static::getTagValue('Cantidad', $saldo_final));
    }

    protected function calcPlayerFinalBalance(
        DOMElement $depositos,
        DOMElement $retiradas,
        DOMElement $participacion,
        DOMElement $participaciondevolucion,
        DOMElement $premios,
        DOMElement $comision,
        DOMElement $bonos,
        DOMElement $otros,
        DOMElement $premiosespecie,
        DOMElement $ajustepremios,
        DOMElement $trans_in,
        DOMElement $trans_out,
        DOMElement $saldo_inicial
    ): float
    {
        $balance = (double) static::getTagValue('Cantidad', $saldo_inicial);
        $balance += (double)static::getTagValue('Total', $depositos);
        $balance += (double)static::getTagValue('Total', $retiradas);
        $balance += (double)static::getTagValue('Cantidad', $participacion->getElementsByTagName('Total')[0]);
        $balance += (double)static::getTagValue('Cantidad', $participaciondevolucion->getElementsByTagName('Total')[0]);
        $balance += (double)static::getTagValue('Cantidad', $premios->getElementsByTagName('Total')[0]);
        $balance += (double)static::getTagValue('Cantidad', $comision->getElementsByTagName('Total')[0]);
        $balance += (double)static::getTagValue('Cantidad', $bonos->getElementsByTagName('Total')[0]);
        $balance += (double)static::getTagValue('Cantidad', $otros->getElementsByTagName('Total')[0]);
        $balance += (double)static::getTagValue('Cantidad', $premiosespecie->getElementsByTagName('Total')[0]);
        $balance += (double)static::getTagValue('Cantidad', $ajustepremios->getElementsByTagName('Total')[0]);
        $balance += (double)static::getTagValue('Cantidad', $trans_in->getElementsByTagName('Total')[0]);
        $balance += (double)static::getTagValue('Cantidad', $trans_out->getElementsByTagName('Total')[0]);

        return $balance;
    }

    protected function checkFileNameWithDB(array $rep): bool
    {
        //<OperadorId>_<AlmacenId>_<Tipo>_<Subtipo>_<Periodicidad>_<Fecha>_<LoteId>
        $filename = explode('_', $rep['filename_prefix'], 7);
        $is_daily = strncmp($rep['report_data_from'], $rep['report_data_to'], 10) === 0;

        return
            count($filename) === 7 &&
            $filename[5] === str_replace('-', '', substr($rep['report_data_to'], 0, ($is_daily ? 10 : 7))) &&
            $filename[3] === static::REPORT_TYPE &&
            strpos(static::REPORT_TYPE, $filename[2]) === 0 &&
            $filename[4] === ($is_daily ? 'D' : 'M') &&
            $filename[0] === $this->license->getLicSetting('operatorId') &&
            $filename[1] === $this->license->getLicSetting('storageId')
            ;
    }

}
