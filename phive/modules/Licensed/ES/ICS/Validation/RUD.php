<?php
declare(strict_types=1);

namespace ES\ICS\Validation;


use DOMDocument;
use DOMElement;
use DOMXPath;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports;

class RUD extends Validation
{
    public const REPORT_TYPE = 'RUD';

    protected array $additional_fields = ['valid_record_count'];

    public function __construct()
    {
        parent::__construct();

        $this->report_class = Reports\RUD::class;
    }

    /**
     * Run the internal validations on the XML (totals = sum(partial), etc)
     * @param array $reports
     * @param array $current_reports
     * @return array
     */
    protected function checkValues(array $reports, array $current_reports): array
    {
        $accumulated_xml = new DOMDocument();

        foreach ($reports[ICSConstants::DAILY_FREQUENCY] as &$day) {
            $accumulated_xml->loadXML('<Lote/>');

            if ($day) {
                $start = '';
                /** @var string $end */
                foreach ($day as &$file) {
                    if (!$file['file_exists']) {
                        continue;
                    }

                    $xml = $this->readXML($file['filename']);
                    if (!$xml) {
                        //can't read file, leave it with checks on Skipped
                        continue;
                    }

                    //check that files are not misplaced
                    $periodicidad = static::getTagValue('Periodicidad', $xml);
                    $dia = static::getTagValue('Dia', $xml);

                    $file['dates'] = $periodicidad === ICSConstants::FREQUENCY_VALUES[ICSConstants::DAILY_FREQUENCY] &&
                        $dia === $this->phive->hisNow($file['report_data_from'], ICSConstants::DAY_FORMAT);

                    $records = $xml->getElementsByTagName('Registro');
                    $valid_r = $records->length <= ICSConstants::RECORD_PER_BATCH;

                    $file['is_issue3a'] = 'no';
                    $file['is_issue3b'] = 'no';
                    /** @var DOMElement $record */
                    foreach ($records as $record) {
                        $copy = $accumulated_xml->importNode($record, true);
                        $copy->removeAttribute('xsi:type');
                        $accumulated_xml->documentElement->appendChild($copy);

                        $players = $record->getElementsByTagName('Jugador');
                        $valid_r = $valid_r && ($players->length <= ICSConstants::ITEMS_PER_SUBRECORD);

                        if ($valid_r) {
                            /** @var DOMElement $player */
                            foreach ($players as $player) {
                                $jugadorId = static::getTagValue('JugadorId', $player);
                                $changes_flag = static::getTagValue('CambiosEnDatos', $player);
                                if ($changes_flag !== Reports\RUD::USER_DATA_MODIFIED && $changes_flag !== Reports\RUD::USER_DATA_NEW) {
                                    $file['invalid_cambios_en_datos'][] = $jugadorId;
                                }

                                list($issue3a, $issue3b) = $this->isIssue3($player);

                                if ($issue3a) {
                                    $file['is_issue3a'] = 'yes';
                                    $file['issue3a'][] = $jugadorId;
                                }

                                if ($issue3b) {
                                    $file['is_issue3b'] = 'yes';
                                    $file['issue3b'][] = $jugadorId;
                                }
                            }
                        }

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
                        ICSConstants::DAILY_FREQUENCY
                    );
                    foreach ($day as &$file) {
                        $file['same_data_in_db'] = $same_data_in_db;
                    }
                    unset($file);
                }
            }
        }
        unset($day);

        $total_new_players = $total_players = 0;
        $player_list = [];
        $totals_per_status = [];

        if ($reports[ICSConstants::MONTHLY_FREQUENCY]) {
            $accumulated_xml->loadXML('<Lote/>');

            $start = '';
            /** @var string $end */

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
                $periodicidad = static::getTagValue('Periodicidad', $xml);
                $mes = static::getTagValue('Mes', $xml);

                $file['dates'] = $periodicidad === ICSConstants::FREQUENCY_VALUES[ICSConstants::MONTHLY_FREQUENCY] &&
                    $mes === $this->phive->hisNow($file['report_data_from'], ICSConstants::MONTH_FORMAT);

                $records = $xml->getElementsByTagName('Registro');
                $valid_r = $records->length <= ICSConstants::RECORD_PER_BATCH;

                $file['is_issue3a'] = 'no';
                $file['is_issue3b'] = 'no';
                /** @var DOMElement $registro */
                foreach ($records as $record) {
                    $copy = $accumulated_xml->importNode($record, true);
                    $copy->removeAttribute('xsi:type');
                    $accumulated_xml->documentElement->appendChild($copy);


                    $players = $record->getElementsByTagName('Jugador');

                    /** @var DOMElement $player */
                    foreach ($players as $player) {
                        $jugadorId = static::getTagValue('JugadorId', $player);
                        $status = static::getTagValue('CambiosEnDatos', $player);

                        if ($status === 'A') {
                            $total_new_players++;
                        }
                        //used for JUD checking
                        $player_list[$jugadorId] = static::getTagValue('FechaActivacion', $player);

                        $totals_per_status[static::getTagValue('EstadoOperador', $player)]++;

                        list($issue3a, $issue3b) = $this->isIssue3($player);

                        if ($issue3a) {
                            $file['is_issue3a'] = 'yes';
                            $file['issue3a'][] = $jugadorId;
                        }

                        if ($issue3b) {
                            $file['is_issue3b'] = 'yes';
                            $file['issue3b'][] = $jugadorId;
                        }
                    }

                    $total_players += $players->length;

                    $valid_r = $valid_r && ($players->length <= ICSConstants::ITEMS_PER_SUBRECORD);
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
                    $file['same_data_in_db'] = $same_data_in_db;
                }
                unset($file);
            }

        }

        $reports['_aux']['total_players'] = $total_players;
        $reports['_aux']['total_new_players'] = $total_new_players;
        $reports['_aux']['player_list'] = $player_list;
        $reports['_aux']['totals_per_status'] = $totals_per_status;

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
     * @return bool
     */
    protected function compareWithDb(DOMDocument $xml, $start, $end, string $frequency): bool
    {
        $data_ok = true;

        $xpath = new DOMXPath($xml);

        $players = $xml->getElementsByTagName('Jugador');

        $prefix = '';
        if ($players->length) {
            $namespace = $players->item(0)->namespaceURI;
            if ($namespace) {
                //register DGOJ namespace so xpath works
                $xpath->registerNamespace('a', $namespace);
                $prefix = 'a:';
            }
        }


        foreach ($this->getDBInfo($start, $end, $frequency) as $batch) {
            foreach ($batch->getData() as $subrecord) {

                foreach ($subrecord['Jugador'] as $player) {

                    //with the id from the db, find it in the xml file copy and remove it
                    $xml_player = $xpath->query(".//{$prefix}JugadorId[text()={$player['ID']['JugadorId']}]");
                    if ($xml_player->length) {
                        $xml_player = $xml_player->item(0)->parentNode->parentNode;

                        $xml_player->parentNode->removeChild($xml_player);

                    } else {
                        //user in db not found in file
                        $data_ok = false;
                        break;
                    }

                }

                if (!$data_ok) {
                    break 2;
                }
            }
        }

        //at this point, there should be no player in the xml that was not found in the db
        return $data_ok && ($players->length === 0);
    }

    /**
     * Checks for know incorrect/inconsistent statuses previously reported by the regulator.
     *
     * @param DOMElement $player
     * @return array[bool|bool]
     */
    protected function isIssue3(DOMElement $player): array
    {
        $EstadoCNJ = static::getTagValue('EstadoCNJ', $player) ?? '';
        $VDocumental = static::getTagValue('VDocumental', $player) ?? '';
        $VSVDI = static::getTagValue('VSVDI', $player) ?? '';

        $issue3A = $this->isIssue3a($VDocumental, $EstadoCNJ);
        $issue3B = $this->isIssue3b($EstadoCNJ, $VDocumental, $VSVDI);

        return [$issue3A, $issue3B];
    }

    /**
     * Checks for Issue 3 problem A reported by the regulator.
     * User has an approved id, but the status is pending verification which is incorrect.
     *
     * @param string $VDocumental
     * @param string $EstadoCNJ
     * @return bool
     */
    protected function isIssue3a(string $VDocumental, string $EstadoCNJ): bool
    {
        return ($VDocumental === ICSConstants::VERIFIED && $EstadoCNJ === ICSConstants::PENDING_DOCUMENT_VERIFICATION);
    }

    /**
     * Checks for Issue 3 problem B reported by the regulator.
     * User has a pending verification status whilst the id has not been verified at registration which is incorrect.
     *
     * @param string $EstadoCNJ
     * @param string $VDocumental
     * @param string $VSVDI
     * @return bool
     */
    protected function isIssue3b(string $EstadoCNJ, string $VDocumental, string $VSVDI): bool
    {
        return (
            $EstadoCNJ === ICSConstants::PENDING_DOCUMENT_VERIFICATION &&
            $VDocumental === ICSConstants::NOT_VERIFIED &&
            $VSVDI === ICSConstants::NOT_VERIFIED
        );
    }

}
