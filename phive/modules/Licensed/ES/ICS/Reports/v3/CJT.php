<?php

namespace ES\ICS\Reports\v3;

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Type\Transaction;

class CJT extends \ES\ICS\Reports\v2\CJT
{
    protected static int $internal_version = 1;

    protected const PAYMENT_METHOD_MAX_LENGTH = 100;
    protected const CURRENCY_MAX_LENGTH = 20;
    protected const AMOUNT_DESCRIPTION_MAX_LENGTH = 100;

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
        $return = array_map(function ($record, $index) use ($total) {
            return [
                '_attributes'  => ['xsi:type' => self::NAME],
                'Cabecera'     => $this->getRecordHeader($index + 1, $total),
                'Periodicidad' => ICSConstants::FREQUENCY_VALUES[$this->getFrequency()],
                'Periodo'      => $this->getPeriod($this->getFrequency()),

                'SaldoInicial'            => $this->getInitialBalance(),
                'Depositos'               => $this->getDeposits(),
                'Retiradas'               => $this->getWithdrawals(),
                'Participacion'           => $this->getBets(),
                'ParticipacionDevolucion' => $this->getRollbackBets(),
                'Premios'                 => $this->getWins(),
                'AjustePremios'           => $this->getPrizeAdjustments(),
                // We don't have transfers from other wallets
                'Trans_IN'                => $this->formatAmount(0),
                // We don't have transfers to other wallets
                'Trans_OUT'               => $this->formatAmount(0),
                'Otros'                   => $this->getOtherAffectingBalance(),
                'SaldoFinal'              => $this->getFinalBalance(),
                'Comision'                => $this->getFees(),
                'Bonos'                   => $this->getBonus(),
                // We dont have "Price in Kind" but the field is mandatory
                'PremiosEspecie'          => $this->formatBreakdownByOperator([]),
            ];
        }, $records, array_keys($records));
        $this->logValidationErrors($return);

        return $return;
    }

    public function mapOperationFormat(
        Transaction $operation,
        string $format = ICSConstants::DATETIME_FORMAT,
        ?string $group = null
    ): array {

        $tipo_medio_pago = $operation->getCardType() ?: $operation->getPaymentMethodType();

        return [
            'MedioPago' => substr($operation->getPaymentMethod(), 0, self::PAYMENT_METHOD_MAX_LENGTH),
            'TipoMedioPago' => $tipo_medio_pago,
            'Importe' => $operation->getAmount(),
        ];
    }

    protected function formatBreakdownByGameType(array $operations, bool $should_report_all_games_types = false): array
    {
        $breakdown = parent::formatBreakdownByGameType($operations, $should_report_all_games_types);

        return $this->removeKeyRecursive($breakdown, 'OperadorId');
    }

    protected function formatAmount($amount, string $currency = ICSConstants::CURRENCY): array
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
    protected function formatBreakdownByOperatorDescription(array $operations)
    {
        // We are only a single operator so we only have 1 breakdown
        // If we dont need to report anything about GP, we can do the total in the query itself

        $operations =  array_reduce(
            $operations,
            static function ($carry, $operation) {
                $currency = $operation['currency'] ?? ICSConstants::CURRENCY;
                $bonus_reason = $operation['description'] ?? '';
                $carry['total'][$currency] = ($carry['total'][$currency] ?? 0) + $operation['amount'];
                $carry['breakdown'][$bonus_reason][$currency] = $operation['amount'] + ($carry['breakdown'][$bonus_reason][$currency] ?? 0);

                return $carry;
            },
            [
                'total'     => [ICSConstants::CURRENCY => 0],
                'breakdown' => []
            ]
        );

        if (empty($operations['breakdown'])) {
            return [
                'Total' => $this->formatAmount(0),
            ];
        }

        $desglose = [];

        foreach ($operations['breakdown'] as $bonus_reason => $amount) {
            $desglose[] = [
                // The concept 'Concepto' under bonus should contain 'LIBERACION' for redeemed bonus, 'CANCELACION' for the cancellation of bonus, 'CONCESION' for granted bonus.
                'Concepto' => substr($bonus_reason, 0, self::AMOUNT_DESCRIPTION_MAX_LENGTH),
                'Importe' => $this->formatAmount($amount),
            ];
        }

        return [
            'Total' => $this->formatAmount($operations['total']),
            'Desglose' => $desglose
        ];
    }
}
