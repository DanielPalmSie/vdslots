<?php

declare(strict_types=1);

namespace ES\ICS\Reports\v3;

use ES\ICS\Constants\ICSConstants;

class OPT extends \ES\ICS\Reports\v2\OPT
{
    protected static int $internal_version = 1;

    public const OPERATOR_ID_MAX_LENGTH = 4;
    public const CURRENCY_MAX_LENGTH = 20;

    public function getRecordHeader(int $subregister_index = 1, int $total_subregisters = 1): array
    {
        $header = parent::getRecordHeader($subregister_index, $total_subregisters);

        unset($header['OperadorId']);
        unset($header['AlmacenId']);
        return $header;
    }

    public function setupRecords(array $records, string $group = ''): array
    {
        $total = count($records);
        return array_map(function($record, $index) use ($group, $total) {
            return [
                '_attributes' => ['xsi:type' => self::NAME],
                'Cabecera' => $this->getRecordHeader($index + 1, $total),
                'Mes' => phive()->fDate($this->getPeriodEnd(), ICSConstants::MONTH_FORMAT),

                'TipoJuego' => $group,
                'Participacion' => $this->formatNumberToBreakdown((int) $record['bets'] * -1),
                'ParticipacionDevolucion' => $this->formatNumberToBreakdown($record['rollback_bets']),
                'Premios' => $this->formatNumberToBreakdown($record['wins']),
                'PremiosEspecie' => $this->formatBreakdownByOperator([]),
                'Botes' => $this->formatBreakdownByOperator([]),
                'AjustesRed' => $this->formatBreakdownByOperator([]),
                'Otros' => $this->formatBreakdownByOperatorDescription([]),
                'Comision' => $this->formatBreakdownByOperator([]),
                'GGR' => $this->formatAmount((int)$record['rollback_bets'] + (int)$record['wins'] - (int)$record['bets']),
                'FechaInicioOferta' => phive()->fDate(
                    $this->getLicensedExternalGamblingTypesStartDate($group),
                    ICSConstants::DAY_FORMAT
                )
            ];
        }, $records, array_keys($records));
    }

    protected function formatBreakdownByOperator(array $operations): array
    {
        // We are only a single operator so we only have 1 breakdown
        // If we dont need to report anything about GP, we can do the total in the query itself

        $operations =  array_reduce(
            $operations,
            function ($carry, $operation) {
                $carry['total'] = $carry['total'] + $operation['amount'];
                $carry['breakdown'][] = [
                    'OperadorId' => substr($this->getOperatorId(), 0, self::OPERATOR_ID_MAX_LENGTH),
                    'Importe' => $this->formatAmount($operation['amount']),
                ];
                return $carry;
            },
            [
                'total' => 0,
                'breakdown' => []
            ]
        );

        if ($operations['total'] === 0) {
            return [
                'Total' => $this->formatAmount($operations['total']),
            ];
        }

        return [
            'Total' => $this->formatAmount($operations['total']),
            'Desglose' => $operations['breakdown']
        ];
    }

    protected function formatAmount($amount, string $currency = 'EUR'): array
    {
        if(!is_array($amount)){
            $amount = [$currency => $amount];
        }
        $lines = [];
        foreach($amount as $unit => $value){
            $lines[] = [
                'Cantidad' => $this->format2Decimal($value),
                'Unidad' => substr($unit, 0, self::CURRENCY_MAX_LENGTH)
            ];
        }

        return [
            'Linea' => $lines
        ];
    }

    /**
     * @throws \Exception
     * @return string Game type start date
     */
    public function getLicensedExternalGamblingTypesStartDate(string $type): string
    {
        $startDates = $this->getLicSetting('ICS.game_types_start_dates');

        if (empty($startDates) || !isset($startDates[$type])) {
            throw new \Exception(sprintf('Could not find start date for "%s"', $type));
        }

        return $startDates[$type];
    }
}
