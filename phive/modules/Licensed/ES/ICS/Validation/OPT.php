<?php
declare(strict_types=1);

namespace ES\ICS\Validation;


use DOMDocument;
use DOMElement;
use DOMXPath;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports;

class OPT extends Validation
{

    public const REPORT_TYPE = 'OPT';

    protected array $additional_fields = ['valid_record_count', 'valid_totals'];

    protected bool $checks_daily = false;

    public function __construct()
    {
        parent::__construct();

        $this->report_class = Reports\OPT::class;
    }

    /**
     * @inheritdoc
     */
    protected function afterValidate(array $current_report, array $present_reports): void
    {
        $gameTypes = ['AZA', 'RLT', 'BLJ'];

        foreach ($gameTypes as $gameType){
            $this->checks["total_participation_equals_CJT_$gameType"] = static::compareAmounts(
                $present_reports['_aux']['total_participation_by_game_type'][$gameType],
                $current_report['reports'][CJT::REPORT_TYPE][1]['_aux']['total_participation_by_game_type'][$gameType]
            );
            $this->checks["total_premios_equals_CJT_$gameType"] = static::compareAmounts(
                $present_reports['_aux']['total_premios_by_game_type'][$gameType],
                $current_report['reports'][CJT::REPORT_TYPE][1]['_aux']['total_premios_by_game_type'][$gameType]
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
        if ($reports[ICSConstants::MONTHLY_FREQUENCY]) {

            $accumulated_xml = new DOMDocument();
            $accumulated_xml->loadXML('<Lote/>');

            $start = '';
            /** @var string $end */

            //For OPT, each file is a different game type

            foreach ($reports[ICSConstants::MONTHLY_FREQUENCY] as &$file) {
                if (!$file['file_exists']) {
                    continue;
                }

                $xml = $this->readXML($file['filename']);
                if (!$xml) {
                    //can't read file, leave it with checks on Skipped
                    continue;
                }

                //check that files are not misplaced
                $mes = static::getTagValue('Mes', $xml);

                $file['dates'] = $mes === $this->phive->hisNow($file['report_data_from'], ICSConstants::MONTH_FORMAT);

                $records = $xml->getElementsByTagName('Registro');
                $valid_r = $records->length === 1;

                if ($valid_r) {
                    /** @var DOMElement $record */
                    $record = $records->item(0);

                    $valid_total = true;
                    $copy = $accumulated_xml->importNode($record, true);
                    $copy->removeAttribute('xsi:type');
                    $accumulated_xml->documentElement->appendChild($copy);

                    $valid_total = $valid_total && $this->checkDetails($record->getElementsByTagName('Participacion')[0]);
                    $valid_total = $valid_total && $this->checkDetails($record->getElementsByTagName('ParticipacionDevolucion')[0]);
                    $valid_total = $valid_total && $this->checkDetails($record->getElementsByTagName('Premios')[0]);
                    $valid_total = $valid_total && $this->checkDetails($record->getElementsByTagName('PremiosEspecie')[0]);
                    $valid_total = $valid_total && $this->checkDetails($record->getElementsByTagName('Botes')[0]);
                    $valid_total = $valid_total && $this->checkDetails($record->getElementsByTagName('AjustesRed')[0]);
                    $valid_total = $valid_total && $this->checkDetails($record->getElementsByTagName('Otros')[0]);
                    $valid_total = $valid_total && $this->checkDetails($record->getElementsByTagName('Comision')[0]);

                    $file['_game_type'] = static::getTagValue('TipoJuego', $record);

                    //used to crosscheck the participaton and premios with CJT and JUT
                    $reports['_aux']['total_participation_by_game_type'][$file['_game_type']] = $this->returnTotal($record->getElementsByTagName('Participacion')[0]);
                    $reports['_aux']['total_premios_by_game_type'][$file['_game_type']] = $this->returnTotal($record->getElementsByTagName('Premios')[0]);

                    $file['valid_totals'] = $valid_total;
                }

                $file['valid_record_count'] = $valid_r;

                $start = $file['report_data_from'];
                $end = $file['report_data_to'];
            }
            unset($file);

            if ($start) {
                $same_data_in_db = $this->compareWithDb(
                    $accumulated_xml,
                    $start,
                    $end,
                    ICSConstants::MONTHLY_FREQUENCY
                );
                foreach ($reports[ICSConstants::MONTHLY_FREQUENCY] as &$file) {
                    $file['same_data_in_db'] = $same_data_in_db[$file['_game_type']];
                    unset($file['_game_type']);
                }
                unset($file);
                $this->checks['all files present'] = $same_data_in_db['_all'] && !($same_data_in_db['_extra'] ?? false);
            }
        }

        return $reports;
    }

    /**
     * Make sure that the reported values and the DB values still correspond,
     * if not we'll need to make a correction report
     *
     * @param DOMDocument $xml
     * @param $start
     * @param $end
     * @param string $frequency
     * @return array
     */
    protected function compareWithDb(DOMDocument $xml, $start, $end, string $frequency): array
    {
        $xpath = new DOMXPath($xml);

        $game_type_records = $xml->getElementsByTagName('Registro');

        $prefix = '';
        if ($game_type_records->length) {
            $namespace = $game_type_records->item(0)->namespaceURI;
            if ($namespace) {
                //register DGOJ namespace so xpath works
                $xpath->registerNamespace('a', $namespace);
                $prefix = 'a:';
            }
        }

        $files = [];


        foreach ($this->getDBInfo($start, $end, $frequency) as $batch) {
            foreach ($batch->getData() as $subrecord) {
                $xml_record = $xpath->query(".//{$prefix}TipoJuego[text()='{$subrecord['TipoJuego']}']");
                if ($xml_record->length) {
                    $xml_record = $xml_record->item(0)->parentNode;
                    $totals = $xpath->query(".//{$prefix}Total", $xml_record);
                    $file_ok = true;
                    /** @var DOMElement $total */
                    foreach ($totals as $total) {
                        $type = $total->parentNode->nodeName;

                        $file_ok = static::compareAmounts($total->getElementsByTagName('Cantidad')[0]->textContent,
                            $subrecord[$type]['Total']['Linea']['Cantidad']);


                        //don't lose time checking the rest of the values, we are only reporting the full file
                        if (!$file_ok) {
                            break;
                        }
                    }
                    $file_ok = $file_ok && static::compareAmounts(
                            $xpath->query(".//{$prefix}GGR//{$prefix}Cantidad", $xml_record)[0]->textContent,
                            $subrecord['GGR']['Linea']['Cantidad']
                        );

                    //if so far all values are the same than the DB, we check the sum
                    $file_ok = $file_ok &&
                        static::compareAmounts(
                            $subrecord['Participacion']['Total']['Linea']['Cantidad'] +
                            $subrecord['ParticipacionDevolucion']['Total']['Linea']['Cantidad'] +
                            $subrecord['Premios']['Total']['Linea']['Cantidad'] +
                            $subrecord['Otros']['Total']['Linea']['Cantidad'],
                            $subrecord['GGR']['Linea']['Cantidad']
                        );

                    $files[$subrecord['TipoJuego']] = $file_ok;

                    $xml_record->parentNode->removeChild($xml_record);
                } else {
                    $files['_extra'] = true;
                }
            }
        }

        //at this point, there should be no Registro in the xml that was not found in the db
        $files['_all'] = ($game_type_records->length === 0);

        return $files;
    }

    protected function checkDetails(DOMElement $node): bool
    {

        $details = $node->getElementsByTagName('Desglose');
        $total = trim(static::getTagValue('Cantidad', $node->getElementsByTagName('Total')[0]));

        $calculated_total = 0;
        /** @var DOMElement $detail */
        foreach ($details as $detail) {
            $calculated_total += (double) static::getTagValue('Cantidad', $detail);

        }

        return static::compareAmounts($calculated_total, $total);
    }

    protected function returnTotal(DOMElement $node){
        return trim(static::getTagValue('Cantidad', $node->getElementsByTagName('Total')[0]));
    }

    protected function checkFileNameWithDB(array $rep): bool
    {
        //<OperadorId>_<AlmacenId>_<Tipo>_<Subtipo>_<TipoJuego>_<Periodicidad>_<Fecha>_<LoteId>
        $filename = explode('_', $rep['filename_prefix'], 8);

        return
            count($filename) === 8 &&
            $filename[6] === str_replace('-', '', substr($rep['report_data_to'], 0, 7)) &&
            $filename[2] === 'OP' &&
            $filename[3] === static::REPORT_TYPE &&
            in_array($filename[4], $this->license->getLicSetting('ICS')['licensed_external_game_types']) &&
            $filename[5] === 'M' &&
            $filename[0] === $this->license->getLicSetting('operatorId') &&
            $filename[1] === $this->license->getLicSetting('storageId')
            ;
    }
}
