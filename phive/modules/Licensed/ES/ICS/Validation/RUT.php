<?php
declare(strict_types=1);

namespace ES\ICS\Validation;


use DOMDocument;
use DOMElement;
use DOMNode;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports;

class RUT extends Validation
{
    public const REPORT_TYPE = 'RUT';

    protected array $additional_fields = ['correct_status_total'];

    public function __construct()
    {
        parent::__construct();

        $this->report_class = Reports\RUT::class;
    }

    /**
     * @inheritdoc
     */
    protected function afterValidate(array $current_report, array $present_reports): void
    {
        $this->checks['total_players_matches_RUD'] = static::compareAmounts(
            $present_reports['_aux']['total_players'],
            $current_report['reports'][RUD::REPORT_TYPE][1]['_aux']['total_players']
        );
        $this->checks['total_new_players_matches_RUD'] = static::compareAmounts(
            $present_reports['_aux']['total_new_players'],
            $current_report['reports'][RUD::REPORT_TYPE][1]['_aux']['total_new_players']
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
        $total_added = 0;
        $last_count = 0;

        foreach ($reports[ICSConstants::DAILY_FREQUENCY] as &$day) {
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

                //running total must match monthly
                $total_added += (int)static::getTagValue('NumeroAltas', $xml);


                $total_players = (int)static::getTagValue('NumeroJugadores', $xml);
                $last_count = $total_players;

                //total per status == totalplayers
                /** @var DOMElement $estado */
                foreach ($xml->getElementsByTagName('NumeroJugadoresPorEstado') as $estado) {
                    $total_players -= (int)static::getTagValue('Numero', $estado);
                }

                $file['correct_status_total'] = $total_players === 0;

                $file['same_data_in_db'] = $this->compareWithDb(
                    $xml,
                    $file['report_data_from'],
                    $file['report_data_to'],
                    ICSConstants::DAILY_FREQUENCY
                );
            }
            unset($file);
        }
        unset($day);


        $file = &$reports[ICSConstants::MONTHLY_FREQUENCY][0];
        if (!$file['file_exists']) {
            return $reports;
        }

        //copy from RUD to compare
        $reports['_aux']['totals_per_status'] = $current_reports['reports'][RUD::REPORT_TYPE][1]['_aux']['totals_per_status'];

        $xml = $this->readXML($file['filename']);
        //if can't read file, leave it with checks on Skipped
        if ($xml) {

            //check that files are not misplaced
            $periodicidad = static::getTagValue('Periodicidad', $xml);
            $mes = static::getTagValue('Mes', $xml);

            $file['dates'] = $periodicidad === ICSConstants::FREQUENCY_VALUES[ICSConstants::MONTHLY_FREQUENCY] &&
                $mes === $this->phive->hisNow($file['report_data_from'], ICSConstants::MONTH_FORMAT);

            //running total must match monthly
            $month_added = (int)static::getTagValue('NumeroAltas', $xml);

            if ($month_added === $total_added) {
                $file['total_month_added'] = $month_added;
            } else {
                $file['total_month_added'] = 'Invalid';
            }

            $total_players = (int)static::getTagValue('NumeroJugadores', $xml);

            $reports['_aux']['total_players'] = $total_players;
            $reports['_aux']['total_new_players'] = $month_added;

            //totalplayers == last total players
            $file['reported_different_from_sum'] = $total_players !== $last_count;


            //total per status == totalplayers
            /** @var DOMElement $estado */
            foreach ($xml->getElementsByTagName('NumeroJugadoresPorEstado') as $estado) {
                $numero = (int) static::getTagValue('Numero', $estado);
                $total_players -= $numero;
                $reports['_aux']['totals_per_status'][static::getTagValue('EstadoOperador', $estado)] -= $numero;
            }
            $file['correct_status_total'] = $total_players === 0;

            $file['total_per_status_same_RUD'] = !array_filter($reports['_aux']['totals_per_status']);
            if (!$file['total_per_status_same_RUD']) {
                $file['total_per_status_same_RUD_dets'] = array_map(
                    static fn($v, $k) => $k.':'.$v,
                    $reports['_aux']['totals_per_status'],
                    array_keys($reports['_aux']['totals_per_status'])
                );
            }

            $file['same_data_in_db'] = $this->compareWithDb(
                $xml,
                $file['report_data_from'],
                $file['report_data_to'],
                ICSConstants::MONTHLY_FREQUENCY
            );
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
     * @return bool
     */
    protected function compareWithDb(DOMDocument $xml, $start, $end, string $frequency): bool
    {
        //RUT only has a one record in data
        /** @var array $db_data */
        foreach ($this->getDBInfo($start, $end, $frequency) as $record) {
            $db_data = $record->getData()[0];
        }

        /**
         * @param string $name
         * @return string
         */
        $get_value = static function (string $name) use ($xml) {
            return $xml->getElementsByTagName($name)[0]->nodeValue;
        };
        /**
         * @param DOMNode $node
         * @return array
         */
        $node_to_array = static function (DOMNode $node) {
            $res = [];
            /** @var DOMNode $element */
            foreach ($node->childNodes as $element) {
                $res[$element->nodeName] = $element->nodeValue;
            }
            return $res;
        };

        $data_ok = true;

        $data_ok = $data_ok && (int)$get_value('NumeroJugadores') === $db_data['NumeroJugadores'];
        $data_ok = $data_ok && (int)$get_value('NumeroAltas') === $db_data['NumeroAltas'];
        $data_ok = $data_ok && (int)$get_value('NumeroBajas') === $db_data['NumeroBajas'];
        $data_ok = $data_ok && (int)$get_value('NumeroActividad') === $db_data['NumeroActividad'];
        $data_ok = $data_ok && (int)$get_value('NumeroJugadores') === $db_data['NumeroJugadores'];

        $xml_porestado = $xml->getElementsByTagName('NumeroJugadoresPorEstado');
        $db_porestado = $db_data['NumeroJugadoresPorEstado'];

        $data_ok = $data_ok && ($xml_porestado->length === count($db_porestado));

        if ($data_ok) {
            foreach ($xml_porestado as $xml_estado) {
                $xml_estado = $node_to_array($xml_estado);
                $i = array_search($xml_estado['EstadoCNJ'], array_column($db_porestado, 'EstadoCNJ'), true);
                if ($i !== false) {
                    $data_ok = $data_ok && ((int)$xml_estado['Numero'] === (int)$db_porestado[$i]['Numero']);
                    array_splice($db_porestado, $i, 1);
                }
            }
        }
        //after comparing, it should be empty
        return $data_ok && !count($db_porestado);
    }
}
