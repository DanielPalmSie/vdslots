<?php
declare(strict_types=1);

namespace ES\ICS\Validation;


use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports;

class CJD extends Validation
{

    public const REPORT_TYPE = 'CJD';

    protected array $additional_fields = ['valid_record_count', 'valid_totals'];

    public function __construct()
    {
        parent::__construct();

        $this->report_class = Reports\CJD::class;
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
        $total_checks = ['Depositos', 'Retiradas', 'Participacion', 'Premios', 'Otros'];
        $total_daily_values = [];

        foreach ($reports[ICSConstants::DAILY_FREQUENCY] as &$day) {
            $accumulated_xml->loadXML('<Lote/>');

            if ($day) {
                $start = $end = '';
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

                    /** @var DOMElement $record */
                    foreach ($records as $record) {
                        /** @var DOMElement $copy */
                        $copy = $accumulated_xml->importNode($record, true);
                        $copy->removeAttribute('xsi:type');
                        $accumulated_xml->documentElement->appendChild($copy);

                        $players = $record->getElementsByTagName('Jugador');
                        $valid_r = $valid_r && ($players <= ICSConstants::ITEMS_PER_SUBRECORD);

                        if ($valid_r) {
                            $valid_total = true;
                            $valid_importe_and_ip_for_retiradas = true;
                            $invalid_totals = [];
                            $invalid_balance = [];
                            $players_without_movements = [];
                            $invalid_player_importe_and_ip_for_retiradas = [];
                            $total_daily_retiradas = $total_daily_depositos = 0;

                            /** @var DOMElement $player */
                            foreach ($players as $player) {
                                $player_id = static::getTagValue('JugadorId', $player->getElementsByTagName('ID')[0]);

                                $depositos = $player->getElementsByTagName('Depositos')[0];
                                $retiradas = $player->getElementsByTagName('Retiradas')[0];
                                $participacion = $player->getElementsByTagName('Participacion')[0];
                                $participaciondevolucion = $player->getElementsByTagName('ParticipacionDevolucion')[0];
                                $premios = $player->getElementsByTagName('Premios')[0];
                                $comision = $player->getElementsByTagName('Comision')[0];
                                $bonos = $player->getElementsByTagName('Bonos')[0];
                                $otros = $player->getElementsByTagName('Otros')[0];
                                $premiosespecie = $player->getElementsByTagName('PremiosEspecie')[0];
                                $ajustepremios = $player->getElementsByTagName('AjustePremios')[0];
                                $trans_in = $player->getElementsByTagName('Trans_IN')[0];
                                $trans_out = $player->getElementsByTagName('Trans_OUT')[0];
                                $saldo_inicial = $player->getElementsByTagName('SaldoInicial')[0];
                                $saldo_final = $player->getElementsByTagName('SaldoFinal')[0];

                                $valid_player_total = $this->checkDetails($depositos);
                                $valid_player_total = $valid_player_total && $this->checkDetails($retiradas);
                                $valid_player_total = $valid_player_total && $this->checkDetails($participacion);
                                $valid_player_total = $valid_player_total && $this->checkDetails($participaciondevolucion);
                                $valid_player_total = $valid_player_total && $this->checkDetails($premios);
                                $valid_player_total = $valid_player_total && $this->checkDetails($comision);
                                $valid_player_total = $valid_player_total && $this->checkDetails($bonos);
                                $valid_player_total = $valid_player_total && $this->checkDetails($otros);
                                $valid_player_total = $valid_player_total && $this->checkDetails($premiosespecie);
                                $valid_player_total = $valid_player_total && $this->checkDetails($ajustepremios);
                                $valid_player_total = $valid_player_total && $this->checkDetails($trans_in);
                                $valid_player_total = $valid_player_total && $this->checkDetails($trans_out);

                                if(!$valid_player_total){
                                    $invalid_totals[] = $player_id;
                                    $valid_total = false;
                                }

                                $valid_player_importe_and_ip_for_retiradas = $this->checkImporteIsNegativeAndIpExistsForRetiradas($retiradas);

                                if(!$valid_player_importe_and_ip_for_retiradas){
                                    $invalid_player_importe_and_ip_for_retiradas[] = $player_id;
                                    $valid_importe_and_ip_for_retiradas = false;
                                }

                                $valid_balance = $this->checkFinalBalance(
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
                                    $saldo_inicial,
                                    $saldo_final
                                );
                                if(!$valid_balance){
                                    $invalid_balance[] = $player_id;
                                }

                                foreach ($total_checks as $elem){
                                    $total_daily_values[$elem] += $this->getValue($player->getElementsByTagName($elem)[0]);
                                }

                                $total_daily_depositos += (double)static::getTagValue('Total', $depositos);
                                $total_daily_retiradas += (double)static::getTagValue('Total', $retiradas);

                                if(!$this->checkHasMovements(
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
                                    $trans_out)){
                                    $players_without_movements[] = $player_id;
                                }

                            }

                            $file['valid_totals'] = $valid_total;
                            $file['valid_importe_and_ip_for_retiradas'] = $valid_importe_and_ip_for_retiradas;

                            //used in crosscheck with CJT
                            $reports['_aux']['total_daily_depositos'][Carbon::parse($file['report_data_from'])->format('Y-m-d')] = $total_daily_depositos;
                            $reports['_aux']['total_daily_retiradas'][Carbon::parse($file['report_data_from'])->format('Y-m-d')] = $total_daily_retiradas;


                            if($invalid_totals){
                                $file['players_with_invalid_total'] = $invalid_totals;
                            }

                            if($invalid_balance){
                                $file['invalid_end_balance_on_players'] = $invalid_balance;
                            }

                            if($invalid_player_importe_and_ip_for_retiradas){
                                $file['invalid_player_importe_and_ip_for_retiradas'] = $invalid_player_importe_and_ip_for_retiradas;
                            }

                            if($players_without_movements){
                                $file['invalid_player_has_no_movements'] = $players_without_movements;
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

        $total_players = 0;
        $total_initial_balance = 0;
        $total_final_balance = 0;
        $total_monthly_values = [];
        $total_calculated_final_balance = 0;

        if ($reports[ICSConstants::MONTHLY_FREQUENCY]) {
            $accumulated_xml->loadXML('<Lote/>');

            $start = $end = '';

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

                /** @var DOMElement $registro */
                foreach ($records as $record) {
                    /** @var DOMElement $copy */
                    $copy = $accumulated_xml->importNode($record, true);
                    $copy->removeAttribute('xsi:type');
                    $accumulated_xml->documentElement->appendChild($copy);


                    $players = $record->getElementsByTagName('Jugador');

                    $valid_r = $valid_r && ($players->length <= ICSConstants::ITEMS_PER_SUBRECORD);

                    if ($valid_r) {
                        $valid_total = true;
                        $invalid_totals = [];
                        $invalid_balance = [];
                        /** @var DOMElement $player */
                        foreach ($players as $player) {
                            $depositos = $player->getElementsByTagName('Depositos')[0];
                            $retiradas = $player->getElementsByTagName('Retiradas')[0];
                            $participacion = $player->getElementsByTagName('Participacion')[0];
                            $participaciondevolucion = $player->getElementsByTagName('ParticipacionDevolucion')[0];
                            $premios = $player->getElementsByTagName('Premios')[0];
                            $comision = $player->getElementsByTagName('Comision')[0];
                            $bonos = $player->getElementsByTagName('Bonos')[0];
                            $otros = $player->getElementsByTagName('Otros')[0];
                            $premiosespecie = $player->getElementsByTagName('PremiosEspecie')[0];
                            $ajustepremios = $player->getElementsByTagName('AjustePremios')[0];
                            $trans_in = $player->getElementsByTagName('Trans_IN')[0];
                            $trans_out = $player->getElementsByTagName('Trans_OUT')[0];
                            $saldo_inicial = $player->getElementsByTagName('SaldoInicial')[0];
                            $saldo_final = $player->getElementsByTagName('SaldoFinal')[0];

                            $valid_player_total = $this->checkDetails($depositos);
                            $valid_player_total = $valid_player_total && $this->checkDetails($retiradas);
                            $valid_player_total = $valid_player_total && $this->checkDetails($participacion);
                            $valid_player_total = $valid_player_total && $this->checkDetails($participaciondevolucion);
                            $valid_player_total = $valid_player_total && $this->checkDetails($premios);
                            $valid_player_total = $valid_player_total && $this->checkDetails($comision);
                            $valid_player_total = $valid_player_total && $this->checkDetails($bonos);
                            $valid_player_total = $valid_player_total && $this->checkDetails($otros);
                            $valid_player_total = $valid_player_total && $this->checkDetails($premiosespecie);
                            $valid_player_total = $valid_player_total && $this->checkDetails($ajustepremios);
                            $valid_player_total = $valid_player_total && $this->checkDetails($trans_in);
                            $valid_player_total = $valid_player_total && $this->checkDetails($trans_out);

                            if(!$valid_player_total){
                                $invalid_totals[] = static::getTagValue('JugadorId', $player->getElementsByTagName('ID')[0]);
                                $valid_total = false;
                            }

                            $valid_balance = $this->checkFinalBalance(
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
                                $saldo_inicial,
                                $saldo_final
                            );

                            $total_calculated_final_balance += $this->calcPlayerFinalBalance(
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

                            if(!$valid_balance){
                                $invalid_balance[] = static::getTagValue('JugadorId', $player->getElementsByTagName('ID')[0]);
                            }

                            $total_initial_balance += (double)static::getTagValue(
                                'Cantidad',
                                $saldo_inicial
                            );
                            $total_final_balance += (double)static::getTagValue(
                                'Cantidad',
                                $saldo_final
                            );

                            foreach ($total_checks as $elem){
                                $total_monthly_values[$elem] += $this->getValue($player->getElementsByTagName($elem)[0]);
                            }
                        }

                        $file['valid_totals'] = $valid_total;

                        $totalFinalBalanceCheck = static::compareAmounts($total_calculated_final_balance, $total_final_balance);
                        $file['total_final_balance_matches_rest_economic_transactions'] = $totalFinalBalanceCheck;

                        if (!$totalFinalBalanceCheck){
                            $file['total_final_balance_matches_rest_economic_transactions_error'][] = "$total_calculated_final_balance != $total_final_balance";
                        }

                        if($invalid_totals){
                            $file['players_with_invalid_total'] = $invalid_totals;
                        }

                        if($invalid_balance){
                            $file['invalid_end_balance_on_players'] = $invalid_balance;
                        }
                    }

                    $total_players += $players->length;

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

        $this->addTotalChecks($total_daily_values, $total_monthly_values);

        $reports['_aux']['total_players'] = $total_players;
        $reports['_aux']['total_initial_balance'] = $total_initial_balance;
        $reports['_aux']['total_final_balance'] = $total_final_balance;

        return $reports;
    }


    /**
     * Add checks that compares the sum of daily totals with the monthly total
     *
     * @param array $daily_values
     * @param array $monthly_values
     * @return void
     */
    private function addTotalChecks(array $daily_values, array $monthly_values): void
    {
        foreach ($daily_values as $key => $value){
            $this->checks["total_{$key}_daily_matches_monthly_CJD"] = static::compareAmounts(
                $value,
                $monthly_values[$key],
            );
        }
    }

    /**
     * Get the node value
     *
     * @param DOMDocument $node
     * @return float
     */
    private function getValue(DOMElement $node): float
    {
        if (in_array($node->nodeName, ['Depositos', 'Retiradas'])) {
            //Depositos and Retiradas have a different structure
            $total = trim(static::getTagValue('Total', $node));
        } else {
            $total = trim(static::getTagValue('Cantidad', $node->getElementsByTagName('Total')[0]));
        }

        return (float)$total;
    }

    protected function checkDetails(DOMElement $node): bool
    {
        if (in_array($node->nodeName, ['Depositos', 'Retiradas'])) {
            //Depositos and Retiradas have a different name
            $details = $node->getElementsByTagName('Operaciones');
            $total = trim(static::getTagValue('Total', $node));
        } else {
            $details = $node->getElementsByTagName('Desglose');
            $total = trim(static::getTagValue('Cantidad', $node->getElementsByTagName('Total')[0]));
        }

        $calculated_total = 0;
        /** @var DOMElement $detail */
        foreach ($details as $detail) {
            if ($detail->nodeName === 'Desglose') {
                $calculated_total += (double)static::getTagValue('Cantidad', $detail);
            } else {
                //on Operaciones nodes, Importe is a number instead of a complexType
                $calculated_total += (double)static::getTagValue('Importe', $detail);
            }
        }

        return static::compareAmounts($calculated_total, $total);
    }

    /**
     * Check if the importe field is negative, and if the ip field exists for retiradas
     *
     * @param DOMDocument $node
     * @return bool
     */
    protected function checkImporteIsNegativeAndIpExistsForRetiradas(DOMElement $node): bool
    {
        if ($node->nodeName !== 'Retiradas'){
            return true;
        }

        if (trim(static::getTagValue('Total', $node)) == 0){
            return true;
        }

        $details = $node->getElementsByTagName('Operaciones');
        foreach ($details as $detail) {
            if ((double)static::getTagValue('Importe', $detail) > 0) {
                return false;
            }

            if (static::getTagValue('IP', $detail) === '') {
                return false;
            }
        }

        return true;
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

                        if ($data_ok) {
                            $totals = $xpath->query('.//Total', $xml_player);

                            /** @var DOMElement $total */
                            foreach ($totals as $total) {
                                $type = $total->parentNode->nodeName;
                                if (in_array($type, ['Depositos', 'Retiradas'])) {
                                    $data_ok = static::compareAmounts($total->textContent, $player[$type]['Total']);
                                } else {
                                    $data_ok = static::compareAmounts($total->getElementsByTagName('Importe')[0]->textContent,
                                        $player[$type]['Total']['Linea']['Cantidad']);
                                }

                                //don't lose time checking the rest of the users, we are only reporting the full file
                                if (!$data_ok) {
                                    break 2;
                                }
                            }
                        }

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
}
