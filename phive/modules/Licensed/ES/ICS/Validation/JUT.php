<?php
declare(strict_types=1);

namespace ES\ICS\Validation;


use DateTime;
use DOMDocument;
use DOMElement;
use DOMXPath;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports;
use ES\ICS\Validation\Traits\JUCheckFiles;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;

class JUT extends Validation
{
    use JUCheckFiles;

    public const REPORT_TYPE = 'JUT';

    protected array $additional_fields = ['valid_record_count'];

    protected bool $checks_monthly = false;

    protected bool $reports_from_range = true;

    public function __construct()
    {
        parent::__construct();

        $this->report_class = Reports\JUT::class;

        $this->ignore_type = 'JUD';
    }

    /**
     * @inheritdoc
     */
    protected function afterValidate(array $current_report, array $present_reports): void
    {
        $gameTypes = ['AZA', 'RLT'];

        foreach ($gameTypes as $gameType){
            $this->checks["total_participation_equals_OPT_$gameType"] = static::compareAmounts(
                $present_reports['_aux']['total_participation_by_game_type'][$gameType],
                $current_report['reports'][OPT::REPORT_TYPE][1]['_aux']['total_participation_by_game_type'][$gameType]
            );
            $this->checks["total_premios_equals_OPT_$gameType"] = static::compareAmounts(
                $present_reports['_aux']['total_premios_by_game_type'][$gameType],
                $current_report['reports'][OPT::REPORT_TYPE][1]['_aux']['total_premios_by_game_type'][$gameType]
            );
        }
    }

    /**
     * Run the internal validations on the XML (totals = sum(partial), etc)
     * @param array $reports
     * @param array $current_reports
     * @return array
     */
    protected function checkValues(array $reports, array $current_reports): array
    {

        $zip_parent = new ZipFile();
        $zip = new ZipFile();
        $pass = $this->license->getLicSetting('regulation_password');

        $xml = new DOMDocument();


        $game_list = $current_reports['reports'][JUD::REPORT_TYPE][1]['_aux']['game_list'];
        $reportTotals = [];

        foreach ($reports[ICSConstants::DAILY_FREQUENCY] as &$day) {
            if ($day) {

                $parent_opened = false;

                /** @var string $end */
                foreach ($day as &$file) {
                    if (!$file['file_exists'] || !$file['daily_file_exists']) {
                        continue;
                    }

                    if (!$parent_opened) {
                        try {
                            $zip_parent->openFile($file['daily_filename']);
                        } catch (ZipException $e) {
                            //can't read file, leave it with checks on Skipped
                            continue;
                        }
                        $parent_opened = true;
                    }

                    try {
                        $zip->openFromStream($zip_parent->getEntryStream($file['filename']));
                        $zip->setReadPassword($pass);
                        $xml->loadXML($zip->getEntryContents('enveloped.xml'));
                    } catch (ZipException $e) {
                        //can't read file, leave it with checks on Skipped
                        continue;
                    }

                    $accumulated_xml = new DOMDocument();
                    $accumulated_xml->loadXML('<Lote/>');

                    $records = $xml->getElementsByTagName('Registro');
                    $valid_r = $records->length <= ICSConstants::REAL_TIME_ENTRIES;

                    $file['dates'] = true;

                    /** @var DOMElement $record */
                    foreach ($records as $record) {
                        /** @var DOMElement $copy */
                        $copy = $accumulated_xml->importNode($record, true);
                        $copy->removeAttribute('xsi:type');
                        $accumulated_xml->documentElement->appendChild($copy);

                        //check that files are not misplaced
                        $report_date = DateTime::createFromFormat(
                            ICSConstants::DATETIME_TO_GMT_FORMAT,
                            static::getTagValue('FechaFin', $record)
                        )->format(ICSConstants::DAY_FORMAT);

                        $file['dates'] = $file['dates'] &&
                            $report_date >= $this->phive->hisNow($file['report_data_from'], ICSConstants::DAY_FORMAT) &&
                            $report_date <= $this->phive->hisNow($file['report_data_to'], ICSConstants::DAY_FORMAT);


                        //cross check: save games ids+enddate+record_total must match in JUD
                        $game_id = static::getTagValue('JuegoId', $record);

                        $total = 0;
                        foreach ($record->getElementsByTagName('Total') as $amount) {
                            $total += (float)static::getTagValue('Cantidad', $amount);
                        }

                        if (
                            isset($game_list[$report_date][$game_id]) &&
                            $game_list[$report_date][$game_id][0] === static::getTagValue('FechaFin', $record) &&
                            static::compareAmounts($game_list[$report_date][$game_id][1], $total)
                        ) {
                                unset($game_list[$report_date][$game_id]);

                                if (empty($game_list[$report_date])) {
                                    unset($game_list[$report_date]);
                                }
                        }

                        //Get the totals for participation/premios per game type
                        $game_type = static::getTagValue('TipoJuego', $record);
                        if (!isset($reports['_aux']['total_participation_by_game_type'][$game_type]) || !isset($reports['_aux']['total_premios_by_game_type'][$game_type])){
                            $reports['_aux']['total_participation_by_game_type'][$game_type] = $reports['_aux']['total_premios_by_game_type'][$game_type] = 0;
                        }

                        $reports['_aux']['total_participation_by_game_type'][$game_type] += static::getTagValue('Participacion', $record);
                        $reports['_aux']['total_premios_by_game_type'][$game_type] += static::getTagValue('Premios', $record);
                    }

                    $file['valid_record_count'] = $valid_r;

                    $start = $file['report_data_from'];
                    $end = $file['report_data_to'];
                    $same_data_in_db = $this->compareWithDb(
                        $accumulated_xml,
                        $start,
                        $end,
                        ICSConstants::DAILY_FREQUENCY
                    );
                    $file['same_data_in_db'] = $same_data_in_db;
                }
                unset($file);
            }

        }
        unset($day);

        $this->checks['difference_between_JUT_JUD_games'] = !empty($game_list);

        return $reports;
    }

    /**
     * Make sure that the reported values and the DB values still correspond,
     * if not we'll need to make a correction report
     *
     * @param DOMDocument $xml
     * @param string $start
     * @param string $end
     * @param string $frequency
     * @return bool
     */
    protected function compareWithDb(DOMDocument $xml, string $start, string $end, string $frequency): bool
    {
        $data_ok = true;

        $xpath = new DOMXPath($xml);

        $records = $xml->getElementsByTagName('Registro');

        $prefix = '';
        if ($records->length) {
            $namespace = $records->item(0)->namespaceURI;
            if ($namespace) {
                //register DGOJ namespace so xpath works
                $xpath->registerNamespace('a', $namespace);
                $prefix = 'a:';
            }
        }


        foreach ($this->getDBInfo($start, $end, $frequency) as $batch) {
            foreach ($batch->getData() as $subrecord) {

                $xml_record = $xpath->query(".//{$prefix}JuegoId[text()={$subrecord['JuegoId']}]");

                if ($xml_record->length) {
                    $xml_record = $xml_record->item(0)->parentNode;

                    $data_ok = $data_ok && (
                            $xpath->query(".//{$prefix}FechaFin", $xml_record)[0]->nodeValue === $subrecord['FechaFin']
                        );

                    $data_ok = $data_ok && (
                        static::compareAmounts($xpath->query(".//{$prefix}Participacion//{$prefix}Cantidad",
                            $xml_record)[0]->nodeValue,
                            $subrecord['Totales']['Participacion']['Total']['Linea'][0]['Cantidad'])
                        );

                    $data_ok = $data_ok && (
                        static::compareAmounts($xpath->query(".//{$prefix}ParticipacionDevolucion//{$prefix}Cantidad",
                            $xml_record)[0]->nodeValue,
                            $subrecord['Totales']['ParticipacionDevolucion']['Total']['Linea'][0]['Cantidad'])
                        );
                    $data_ok = $data_ok && (
                        static::compareAmounts($xpath->query(".//{$prefix}Premios//{$prefix}Cantidad",
                            $xml_record)[0]->nodeValue, $subrecord['Totales']['Premios']['Total']['Linea'][0]['Cantidad'])
                        );

                    $xml_record->parentNode->removeChild($xml_record);

                }

                if (!$data_ok) {
                    break 2;
                }
            }
        }

        //at this point, there should be no record in the xml that was not found in the db
        return $data_ok && ($records->length === 0);
    }
}
