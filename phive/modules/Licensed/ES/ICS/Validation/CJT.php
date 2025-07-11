<?php
declare(strict_types=1);

namespace ES\ICS\Validation;


use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports;

class CJT extends Validation
{
    public const REPORT_TYPE = 'CJT';

    protected array $additional_fields = ['valid_record_count', 'valid_totals', 'valid_calc_final_balance'];

    public function __construct()
    {
        parent::__construct();

        $this->report_class = Reports\CJT::class;
    }

    /**
     * @inheritdoc
     */
    protected function afterValidate(array $current_report, array $present_reports): void
    {
        $this->checks['total_initial_balance_equals_CJD'] = static::compareAmounts(
            $present_reports['_aux']['total_initial_balance'],
            $current_report['reports'][CJD::REPORT_TYPE][1]['_aux']['total_initial_balance']
        );
        $this->checks['total_final_balance_equals_CJD'] = static::compareAmounts(
            $present_reports['_aux']['total_final_balance'],
            $current_report['reports'][CJD::REPORT_TYPE][1]['_aux']['total_final_balance']
        );
    }

    /**
     * Run the internal validations on the XML (totals = sum(partial), etc)
     * @param array $reports
     * @param array $current_reports
     * @return array
     */
    protected function checkValues(array $reports, array $current_reports): array
    {
        $total_checks = ['Depositos', 'Retiradas', 'Participacion', 'Premios', 'Otros'];
        $total_daily_values = [];
        foreach ($reports[ICSConstants::DAILY_FREQUENCY] as &$day) {
            if ($day) {
                if (count($day) > 1) {
                    $this->checks['days with extra files'] = true;
                }
                //Should only be one file per day
                $file = &$day[0];

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
                $valid_r = $records->length === 1;
                $total_daily_retiradas = $total_daily_depositos = 0;

                if ($valid_r) {
                    /** @var DOMElement $record */
                    $record = $records->item(0);

                    $depositos = $record->getElementsByTagName('Depositos')[0];
                    $retiradas = $record->getElementsByTagName('Retiradas')[0];
                    $participacion = $record->getElementsByTagName('Participacion')[0];
                    $participaciondevolucion = $record->getElementsByTagName('ParticipacionDevolucion')[0];
                    $premios = $record->getElementsByTagName('Premios')[0];
                    $comision = $record->getElementsByTagName('Comision')[0];
                    $bonos = $record->getElementsByTagName('Bonos')[0];
                    $otros = $record->getElementsByTagName('Otros')[0];
                    $premiosespecie = $record->getElementsByTagName('PremiosEspecie')[0];
                    $ajustepremios = $record->getElementsByTagName('AjustePremios')[0];
                    $trans_in = $record->getElementsByTagName('Trans_IN')[0];
                    $trans_out = $record->getElementsByTagName('Trans_OUT')[0];
                    $saldo_inicial = $record->getElementsByTagName('SaldoInicial')[0];
                    $saldo_final = $record->getElementsByTagName('SaldoFinal')[0];

                    $valid_total = true;

                    $valid_total = $valid_total && $this->checkDetails($depositos);
                    $valid_total = $valid_total && $this->checkDetails($retiradas);
                    $valid_total = $valid_total && $this->checkDetails($participacion);
                    $valid_total = $valid_total && $this->checkDetails($participaciondevolucion);
                    $valid_total = $valid_total && $this->checkDetails($premios);
                    $valid_total = $valid_total && $this->checkDetails($comision);
                    $valid_total = $valid_total && $this->checkDetails($bonos);
                    $valid_total = $valid_total && $this->checkDetails($otros);
                    $valid_total = $valid_total && $this->checkDetails($premiosespecie);
                    $valid_total = $valid_total && $this->checkDetails($ajustepremios);
                    $valid_total = $valid_total && $this->checkDetails($trans_in);
                    $valid_total = $valid_total && $this->checkDetails($trans_out);

                    $file['valid_totals'] = $valid_total;

                    $valid_retaridas_total_and_importe = $this->checkRetaridasTotalAndImporteAreNegative($retiradas);
                    $file['valid_retaridas_total_and_importe'] = $valid_retaridas_total_and_importe;

                    $file['valid_calc_final_balance'] = $this->checkFinalBalance(
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

                    $total_daily_depositos += (double)static::getTagValue('Total', $depositos);
                    $total_daily_retiradas += (double)static::getTagValue('Total', $retiradas);

                    foreach ($total_checks as $elem){
                        $total_daily_values[$elem] += $this->getValue($record->getElementsByTagName($elem)[0]);
                    }
                }

                $file['valid_record_count'] = $valid_r;

                $start = $file['report_data_from'];
                $end = $file['report_data_to'];

                // Check if the daily totals for deposits and withdrawals in CJD and CJT match up
                $file['retiradas_matches_daily_CJD'] =
                    static::compareAmounts(
                        $current_reports['reports'][CJD::REPORT_TYPE][1]['_aux']['total_daily_retiradas'][Carbon::parse($start)->format('Y-m-d')],
                        $total_daily_retiradas
                    ) ?? false;

                $file['depositos_matches_daily_CJD'] =
                    static::compareAmounts(
                        $current_reports['reports'][CJD::REPORT_TYPE][1]['_aux']['total_daily_depositos'][Carbon::parse($start)->format('Y-m-d')],
                        $total_daily_depositos
                    ) ?? false;

                $same_data_in_db = $this->compareWithDb(
                    $xml,
                    $start,
                    $end,
                    ICSConstants::DAILY_FREQUENCY
                );
                $file['same_data_in_db'] = $same_data_in_db;

            }
        }
        unset($day);

        $total_players = 0;
        $total_initial_balance = 0;
        $total_final_balance = 0;
        $total_monthly_values = [];
        $total_participation_by_game_type = $total_premios_by_game_type = [];

        if ($reports[ICSConstants::MONTHLY_FREQUENCY]) {
            if (count($reports[ICSConstants::MONTHLY_FREQUENCY]) > 1) {
                $this->checks['days with extra files'] = true;
            }
            //Should only be one file per day
            $file = &$reports[ICSConstants::MONTHLY_FREQUENCY][0];

            if ($file['file_exists']) {
                $xml = $this->readXML($file['filename']);
                //if can't read file, leave it with checks on Skipped
                if ($xml) {

                    //check that files are not misplaced
                    $periodicidad = static::getTagValue('Periodicidad', $xml);
                    $mes = static::getTagValue('Mes', $xml);

                    $file['dates'] = $periodicidad === ICSConstants::FREQUENCY_VALUES[ICSConstants::MONTHLY_FREQUENCY] &&
                        $mes === $this->phive->hisNow($file['report_data_from'], ICSConstants::MONTH_FORMAT);

                    $records = $xml->getElementsByTagName('Registro');
                    $valid_r = $records->length === 1;

                    if ($valid_r) {
                        $valid_total = true;

                        /** @var DOMElement $record */
                        $record = $records->item(0);

                        $depositos = $record->getElementsByTagName('Depositos')[0];
                        $retiradas = $record->getElementsByTagName('Retiradas')[0];
                        $participacion = $record->getElementsByTagName('Participacion')[0];
                        $participaciondevolucion = $record->getElementsByTagName('ParticipacionDevolucion')[0];
                        $premios = $record->getElementsByTagName('Premios')[0];
                        $comision = $record->getElementsByTagName('Comision')[0];
                        $bonos = $record->getElementsByTagName('Bonos')[0];
                        $otros = $record->getElementsByTagName('Otros')[0];
                        $premiosespecie = $record->getElementsByTagName('PremiosEspecie')[0];
                        $ajustepremios = $record->getElementsByTagName('AjustePremios')[0];
                        $trans_in = $record->getElementsByTagName('Trans_IN')[0];
                        $trans_out = $record->getElementsByTagName('Trans_OUT')[0];
                        $saldo_inicial = $record->getElementsByTagName('SaldoInicial')[0];
                        $saldo_final = $record->getElementsByTagName('SaldoFinal')[0];

                        $valid_total = $valid_total && $this->checkDetails($depositos);
                        $valid_total = $valid_total && $this->checkDetails($retiradas);
                        $valid_total = $valid_total && $this->checkDetails($participacion);
                        $valid_total = $valid_total && $this->checkDetails($participaciondevolucion);
                        $valid_total = $valid_total && $this->checkDetails($premios);
                        $valid_total = $valid_total && $this->checkDetails($comision);
                        $valid_total = $valid_total && $this->checkDetails($bonos);
                        $valid_total = $valid_total && $this->checkDetails($otros);
                        $valid_total = $valid_total && $this->checkDetails($premiosespecie);
                        $valid_total = $valid_total && $this->checkDetails($ajustepremios);
                        $valid_total = $valid_total && $this->checkDetails($trans_in);
                        $valid_total = $valid_total && $this->checkDetails($trans_out);

                        $total_initial_balance += (double)static::getTagValue(
                            'Cantidad',
                            $saldo_inicial
                        );
                        $total_final_balance += (double)static::getTagValue(
                            'Cantidad',
                            $saldo_final
                        );

                        $file['valid_totals'] = $valid_total;

                        $file['valid_calc_final_balance'] = $this->checkFinalBalance(
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
                    }

                    $file['valid_record_count'] = $valid_r;

                    $total_participation_by_game_type = $this->getTotalsByGameType($participacion);
                    $total_premios_by_game_type = $this->getTotalsByGameType($premios);

                    $start = $file['report_data_from'];
                    $end = $file['report_data_to'];

                    $same_data_in_db = $this->compareWithDb(
                        $xml,
                        $start,
                        $end,
                        ICSConstants::MONTHLY_FREQUENCY
                    );
                    $file['same_data_in_db'] = $same_data_in_db;

                    foreach ($total_checks as $elem){
                        $total_monthly_values[$elem] += $this->getValue($record->getElementsByTagName($elem)[0]);
                    }
                }
            }
        }

        $this->addTotalChecks($total_daily_values, $total_monthly_values);

        $reports['_aux']['total_players'] = $total_players;
        $reports['_aux']['total_initial_balance'] = $total_initial_balance;
        $reports['_aux']['total_final_balance'] = $total_final_balance;

        //used in crosscheck with OPT
        $reports['_aux']['total_participation_by_game_type'] = $total_participation_by_game_type;
        $reports['_aux']['total_premios_by_game_type'] = $total_premios_by_game_type;

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
            $this->checks["total_{$key}_daily_matches_monthly_CJT"] = static::compareAmounts(
                $value,
                $monthly_values[$key],
            );
        }
    }

    protected function checkDetails(DOMElement $node): bool
    {
        $details = $node->getElementsByTagName('Desglose');
        $is_operation = false;
        if (in_array($node->nodeName, ['Depositos', 'Retiradas'])) {
            //Depositos and Retiradas have a different structure
            $total = trim(static::getTagValue('Total', $node));
            $is_operation = true;
        } else {
            $total = trim(static::getTagValue('Cantidad', $node->getElementsByTagName('Total')[0]));
        }

        $calculated_total = 0;
        /** @var DOMElement $detail */
        foreach ($details as $detail) {
            if (!$is_operation) {
                $calculated_total += (double)static::getTagValue('Cantidad', $detail);
            } else {
                //on Operaciones nodes, Importe is a number instead of a complexType
                $calculated_total += (double)static::getTagValue('Importe', $detail);
            }
        }

        return static::compareAmounts($calculated_total, $total);
    }

    protected function getTotalsByGameType(DOMElement $node): array
    {
        $total['AZA'] = $total['RLT'] = $total['BLJ'] = 0;

        $details = $node->getElementsByTagName('Desglose');
        /** @var DOMElement $detail */
        foreach ($details as $detail) {
            if (static::getTagValue('TipoJuego', $detail) === 'AZA'){
                $total['AZA'] = (double)static::getTagValue('Cantidad', $detail);
            }else if (static::getTagValue('TipoJuego', $detail) === 'RLT'){
                $total['RLT'] = (double)static::getTagValue('Cantidad', $detail);
            }else if (static::getTagValue('TipoJuego', $detail) === 'BLJ'){
                $total['BLJ'] = (double)static::getTagValue('Cantidad', $detail);
            }
        }

        return $total;
    }

    /**
     * Check if the total and the importe field are negative for withdrawal
     *
     * @param DOMDocument $node
     * @return bool
     */
    protected function checkRetaridasTotalAndImporteAreNegative(DOMElement $node): bool
    {
        if ($node->nodeName !== 'Retiradas'){
            return false;
        }

        if (static::getTagValue('Total', $node) == 0){
            return true;
        }

        if (static::getTagValue('Total', $node) > 0){
            return false;
        }

        $details = $node->getElementsByTagName('Desglose');
        /** @var DOMElement $detail */
        foreach ($details as $detail) {
            //on Operaciones nodes, Importe is a number instead of a complexType
            if ((double)static::getTagValue('Importe', $detail) > 0){
                return false;
            }
        }

        return true;
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


        $prefix = '';

        $namespace = $xml->firstChild->namespaceURI;
        if ($namespace) {
            //register DGOJ namespace so xpath works
            $xpath->registerNamespace('a', $namespace);
            $prefix = 'a:';
        }


        foreach ($this->getDBInfo($start, $end, $frequency) as $batch) {
            foreach ($batch->getData() as $subrecord) {

                $xml_player = $xpath->query(".//{$prefix}Registro");
                if ($xml_player->length) {
                    $xml_player = $xml_player[0];
                    if ($data_ok) {

                        $totals = $xpath->query(".//{$prefix}Total", $xml_player);

                        /** @var DOMElement $total */
                        foreach ($totals as $total) {
                            $type = $total->parentNode->nodeName;
                            if (in_array($type, ['Depositos', 'Retiradas'])) {
                                $data_ok = static::compareAmounts($total->textContent, $subrecord[$type]['Total']);
                            } else {
                                $data_ok = static::compareAmounts(
                                    static::getTagValue('Cantidad', $total),
                                    $subrecord[$type]['Total']['Linea'][0]['Cantidad']
                                );
                            }

                            //don't lose time checking the rest of the values, we are only reporting the full file
                            if (!$data_ok) {
                                break;
                            }
                        }
                    }

                } else {
                    //missing Registro node
                    $data_ok = false;
                    break;
                }


                if (!$data_ok) {
                    break 2;
                }
            }
        }

        return $data_ok;
    }
}
