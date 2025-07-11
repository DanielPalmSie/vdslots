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

class JUD extends Validation
{
    use JUCheckFiles;

    public const REPORT_TYPE = 'JUD';

    protected array $additional_fields = ['valid_record_count', 'has_players_not_in_RUD'];

    protected bool $checks_monthly = false;

    protected bool $reports_from_range = true;

    public function __construct()
    {
        parent::__construct();

        $this->report_class = Reports\JUD::class;

        $this->ignore_type = 'JUT';
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


        $player_list = $current_reports['reports'][RUD::REPORT_TYPE][1]['_aux']['player_list'];
        $game_list = [];

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

                    $file['has_players_not_in_RUD'] = false;

                    $file['dates'] = true;

                    /** @var DOMElement $record */
                    foreach ($records as $record) {
                        /** @var DOMElement $copy */
                        $copy = $accumulated_xml->importNode($record, true);
                        $copy->removeAttribute('xsi:type');
                        $accumulated_xml->documentElement->appendChild($copy);

                        $fecha_fin = static::getTagValue('FechaFin', $record);

                        //check that files are not misplaced
                        $report_date = DateTime::createFromFormat(
                            ICSConstants::DATETIME_TO_GMT_FORMAT,
                            $fecha_fin
                        )->format(ICSConstants::DAY_FORMAT);

                        $file['dates'] = $file['dates'] &&
                            $report_date >= $this->phive->hisNow($file['report_data_from'], ICSConstants::DAY_FORMAT) &&
                            $report_date <= $this->phive->hisNow($file['report_data_to'], ICSConstants::DAY_FORMAT);


                        //We only need the player_ids, no need to get the full player block
                        $players = $record->getElementsByTagName('JugadorId');
                        $valid_r = $valid_r && ($players <= ICSConstants::ITEMS_PER_SUBRECORD);

                        //cross check: every player on a game must appear in the monthly RUD (total registered users)
                        if (!$file['has_players_not_in_RUD']) {
                            foreach ($players as $player) {
                                if (!array_key_exists($player->nodeValue, $player_list)) {
                                    $file['has_players_not_in_RUD'] = true;
                                } elseif ($fecha_fin < $player_list[$player->nodeValue]) {
                                    //game date should be same day or after registration
                                    $file['has_player_before_registration'] = true;
                                }
                            }
                        }

                        $total = 0;
                        /** @var DOMElement $amount */
                        foreach ($record->getElementsByTagName('Cantidad') as $amount) {
                            $total += (float)$amount->nodeValue;
                        }

                        if (!array_key_exists($report_date, $game_list)) {
                            $game_list[$report_date] = [];
                        }

                        //cross check: save games ids+enddate+record_total since they must match in JUT
                        $game_list[$report_date][static::getTagValue('JuegoId', $record)] = [
                            $fecha_fin,
                            $total
                        ];


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

        $reports['_aux']['game_list'] = $game_list;

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
                            $xml_record)[0]->nodeValue, $subrecord['Jugador']['Participacion']['Linea']['Cantidad'])
                        );

                    $data_ok = $data_ok && (
                        static::compareAmounts($xpath->query(".//{$prefix}ParticipacionDevolucion//{$prefix}Cantidad",
                            $xml_record)[0]->nodeValue,
                            $subrecord['Jugador']['ParticipacionDevolucion']['Linea']['Cantidad'])
                        );
                    $data_ok = $data_ok && (
                        static::compareAmounts($xpath->query(".//{$prefix}Premios//{$prefix}Cantidad",
                            $xml_record)[0]->nodeValue, $subrecord['Jugador']['Premios']['Linea']['Cantidad'])
                        );

                    $data_ok = $data_ok && (
                            $xpath->query(".//{$prefix}DuracionLimite",
                                $xml_record)[0]->nodeValue === $subrecord['Jugador']['PlanificacionAzar']['DuracionLimite']
                        );
                    $data_ok = $data_ok && (
                        static::compareAmounts($xpath->query(".//{$prefix}ParticipacionLimite",
                            $xml_record)[0]->nodeValue,
                            $subrecord['Jugador']['PlanificacionAzar']['ParticipacionLimite'])
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
