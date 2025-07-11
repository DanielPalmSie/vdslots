<?php

namespace ES\ICS\Reports\v3;

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Type\Transaction;
use Videoslots\Mts\MtsClient;

class CJD extends \ES\ICS\Reports\v2\CJD
{
    protected const CACHE_MTS_KEY_PREFIX = 'CJD_MTS_transaction_details_';
    protected const CACHE_MTS_TIMEOUT_SECONDS = 60 * 60 * 24 * 31;
    protected static int $internal_version = 1;

    protected const PLAYER_ID_MAX_LENGTH = 50;
    protected const CURRENCY_MAX_LENGTH = 20;
    protected const PAYMENT_METHOD_MAX_LENGTH = 100;
    protected const OTHER_PAYMENT_TYPE_MAX_LENGTH = 100;
    protected const IP_ADDRESS_MAX_LENGTH = 50;
    protected const DEVICE_ID_MAX_LENGTH = 100;
    protected const OPERATOR_ID_MAX_LENGTH = 4;
    protected const ACCOUNT_MAX_LENGTH = 50;

    public function getRecordHeader(int $subregister_index = 1, int $total_subregisters = 1): array
    {
        $header = parent::getRecordHeader($subregister_index, $total_subregisters);

        unset($header['OperadorId']);
        unset($header['AlmacenId']);
        return $header;
    }

    protected function formatUserData(int $user_id): array
    {
        $final_balance = $this->getEndBalance($user_id);

        return [
            'JugadorId' => substr((string)$user_id, 0, self::PLAYER_ID_MAX_LENGTH),
            'SaldoInicial' => $this->getInitialBalance($user_id),
            'Depositos' => $this->getDeposits($user_id),
            'Retiradas' => $this->getWithdrawals($user_id),
            'Participacion' => $this->getBets($user_id),
            'ParticipacionDevolucion' => $this->getRollbackBets($user_id),
            'Premios' => $this->getWins($user_id),
            'AjustePremios' => $this->getPrizeAdjustments($user_id),
            // We don't have transfers from other wallets
            'Trans_IN' => $this->formatBreakdownByOperator([]),
            // We don't have transfers to other wallets
            'Trans_OUT' => $this->formatBreakdownByOperator([]),
            'Otros' => $this->getOtherAffectingBalance($user_id),
            'SaldoFinal' => $final_balance,
            'Cuentas' => [
                // Whe only have 1 account per acc what we should use user_id?
                'Cuenta' => substr($user_id, 0, self::ACCOUNT_MAX_LENGTH),
                'SaldoFinal' => $final_balance
            ],
            'Comision' => $this->getFees($user_id),
            'Bonos' => $this->getBonus($user_id),
            // We don't have "Price in Kind" (PremiosEspecie)
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

    public function mapOperationFormat(
        Transaction $operation,
        string $format = ICSConstants::DATETIME_FORMAT,
        ?string $group = null
    ): array {

        $tipo_medio_pago = $operation->getCardType() ?: $operation->getPaymentMethodType();

        $operation_formatted = [
            'Fecha' => $operation->getTimestamp($format),
            'Importe' => $operation->getAmount(),
            'MedioPago' => substr($operation->getPaymentMethod(), 0, self::PAYMENT_METHOD_MAX_LENGTH),
            'TipoMedioPago' => $tipo_medio_pago,
            'OtroTipoEspecificar' => $tipo_medio_pago == 99 ?
                substr($operation->getDisplayName(), 0, self::OTHER_PAYMENT_TYPE_MAX_LENGTH) : '',
            'TitularidadVerificada' => $this->isOwnershipVerified($operation) ? 'S' : 'N',
            'ResultadoOperacion' => $this->mapOperationResult($operation, $group),
            'IP' => substr($operation->getIp(), 0, self::IP_ADDRESS_MAX_LENGTH),
            'Dispositivo' => $operation->getDeviceType(),
            'IdDispositivo' => substr($operation->getDeviceId(), 0, self::DEVICE_ID_MAX_LENGTH),
            'UltimosDigitosMedioPago' => $operation->getLastFourDigitsCard()
        ];

        if (empty($operation_formatted['OtroTipoEspecificar'])) {
            unset($operation_formatted['OtroTipoEspecificar']);
        }

        if (empty($operation_formatted['UltimosDigitosMedioPago'])) {
            unset($operation_formatted['UltimosDigitosMedioPago']);
        }

        return $operation_formatted;
    }

    protected function mapOperationResult(Transaction $transaction, ?string $group): string
    {
        if ($transaction->getStatus() === 'approved') {
            return ICSConstants::TRANSACTION_RESULT_OK;
        }

        if (empty($transaction->getMtsId())) {

            if ($group === self::GROUP_DEPOSITS && $transaction->getStatus() === 'disapproved') {
                return ICSConstants::TRANSACTION_RESULT_OTHER;
            }

            if ($group === self::GROUP_WITHDRAWALS && $transaction->getApprovedBy() != $transaction->getUserId()) {
                return ICSConstants::TRANSACTION_RESULT_CANCELLED_BY_OPERATOR;
            }

            return ICSConstants::TRANSACTION_RESULT_CANCELLED_BY_PLAYER;
        }

        if ($transactionResult = phMget($this->getMtsTransactionDetailsCacheKey($transaction))) {
            return $transactionResult;
        }

        try {

            // get additional data about transaction failure or cancellation from MTS
            $mtsClient = new MtsClient(
                phive('Cashier')->getSetting('mts'),
                phive('Logger')->channel('payments')
            );
            $transactionDetailsArray = $mtsClient->getTransactionDetails(['ids' => [$transaction->getMtsId()]]);

            $transactionResult = $this->mapTransactionDetailsArrayToTransactionResult(
                $transactionDetailsArray,
                $transaction
            );

            phMset(
                $this->getMtsTransactionDetailsCacheKey($transaction),
                $transactionResult,
                self::CACHE_MTS_TIMEOUT_SECONDS
            );

        } catch (\Exception $e) {
            phive('Licensed/ES/ES')->reportLog("ERROR :: ICS:CJD {$this->frequency} {$this->period_start}
                {$this->period_end} Error fetching transaction details for MTS Id: {$transaction->getMtsId()}");
            return ICSConstants::TRANSACTION_RESULT_OTHER;
        }

        return $transactionResult;
    }

    protected function getMtsTransactionDetailsCacheKey(Transaction $transaction): string
    {
        return self::CACHE_MTS_KEY_PREFIX.$transaction->getMtsId();
    }

    protected function mapTransactionDetailsArrayToTransactionResult(
        array $transactionDetailsArray,
        Transaction $transaction
    ): string {

        if (!isset($transactionDetailsArray['data'][$transaction->getMtsId()])) {
            return ICSConstants::TRANSACTION_RESULT_OTHER;
        }

        $data = $transactionDetailsArray['data'][$transaction->getMtsId()];

        if ($data['status'] == 9) {
            return ICSConstants::TRANSACTION_RESULT_CANCELLED_BY_PLAYER;
        }

        if ($data['status'] == -1) {

            if (empty($data['transaction_error'])) {
                return ICSConstants::TRANSACTION_RESULT_CANCELLED_BY_PAYMENT_GATEWAY;
            }

            if (($data['transaction_error']['internal_code'] >= 0 && $data['transaction_error']['internal_code'] <= 99)
                || $data['transaction_error']['internal_code'] == 401) {
                return ICSConstants::TRANSACTION_RESULT_CANCELLED_BY_OPERATOR;
            }

            if ($data['transaction_error']['code'] >= 300 && $data['transaction_error']['code'] <= 399) {
                return ICSConstants::TRANSACTION_RESULT_CANCELLED_BY_PAYMENT_GATEWAY;
            }

            return ICSConstants::TRANSACTION_RESULT_OTHER;
        }

        return ICSConstants::TRANSACTION_RESULT_OTHER;
    }

    /** This will be implemented on ticket https://videoslots.atlassian.net/browse/PHNX-4968 */
    protected function isOwnershipVerified(Transaction $operation): bool
    {
        return false;
    }

    protected function formatBreakdownByGameType(array $operations, bool $should_report_all_games_types = false): array
    {
        $operations = array_reduce(
            $operations,
            function ($carry, $operation) {
                $carry['total'] += $operation['amount'];
                $ext_game_type = $this->getGameType($operation['game_tag'], $operation['game_id']);

                $carry['breakdown'][$ext_game_type] = $operation['amount'] + ($carry['breakdown'][$ext_game_type] ?? 0);

                return $carry;
            },
            [
                'total' => 0,
                'breakdown' => []
            ]
        );

        if ($operations['total'] === 0 && !$should_report_all_games_types) {
            return [
                'Total' => $this->formatAmount($operations['total']),
            ];
        }

        $desglose = [];

        // We should report all licensed game's types for CJT
        if ($should_report_all_games_types) {
            foreach ($this->getLicensedExternalGamblingTypes() as $ext_game_type) {
                if (empty($operations['breakdown'][$ext_game_type])) {
                    $operations['breakdown'][$ext_game_type] = 0;
                }
            }
        }

        foreach ($operations['breakdown'] as $ext_game_type => $amount) {
            $desglose[] = [
                'TipoJuego' => $ext_game_type,
                'Importe' => $this->formatAmount($amount),
            ];
        }

        return [
            'Total' => $this->formatAmount($operations['total']),
            'Desglose' => $desglose
        ];
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

    protected function formatBreakdownByOperatorDescription(array $operations): array
    {
        return $this->removeKeyRecursive(
            parent::formatBreakdownByOperatorDescription($operations),
            'OperadorId'
        );
    }

    public function getOtherAffectingBalance(int $user_id): array
    {
        //we filter by parent_id here, because type 13 is used for automatic withdrawal reversals, those are already accounted for when checking status=approved,
        //but it's also incorrectly used to manually add money to an account

        $sql = "
        SELECT
            amount,
            description,
            transactiontype,
            timestamp,
            id
        FROM cash_transactions
        WHERE
            user_id = {$user_id}
            AND transactiontype IN ({$this->getOthersTypes()})
            AND parent_id = 0
            AND timestamp BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
        ";
        $data = $this->db->sh($user_id)->loadArray($sql);

        $data = array_map(
            function ($row) {
                return [
                    'amount' => $row['amount'],
                    'description' => $this->getOthersDescription($row),
                    'timestamp' => $row['timestamp']
                ];
            },
            $data);

        return $this->formatBreakdownByOperatorForOtherAffectingBalance($data);
    }

    protected function formatBreakdownByOperatorForOtherAffectingBalance(array $operations): array
    {
        // We are only a single operator so we only have 1 breakdown
        // If we dont need to report anything about GP, we can do the total in the query itself

        $operations =  array_reduce(
            $operations,
            static function ($carry, $operation) {
                $currency = $operation['currency'] ?? 'EUR';
                $bonus_reason = $operation['description'] ?? '';
                $carry['total'][$currency] = ($carry['total'][$currency] ?? 0) + $operation['amount'];
                $carry['breakdown'][$bonus_reason][$currency] = $operation['amount'] + ($carry['breakdown'][$bonus_reason][$currency] ?? 0);

                return $carry;
            },
            [
                'total'     => ['EUR' => 0],
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
                'Concepto' => $bonus_reason,
                'Importe' => $this->formatAmount($amount),
            ];
        }

        return [
            'Total' => $this->formatAmount($operations['total']),
            'Desglose' => $desglose
        ];
    }

    public function getBonus(int $user_id): array
    {
        $period_start = $this->getPeriodStart();
        $period_end = $this->getPeriodEnd();
        $sql = "
            SELECT
                ABS(amount) as amount,
                ct.transactiontype as bonus_type,
                timestamp
            FROM cash_transactions AS ct
            LEFT JOIN bonus_types AS bt ON bt.id = ct.bonus_id
            WHERE
                ct.user_id = {$user_id}
                AND ct.transactiontype IN ({$this->bonus_types})
                AND ct.timestamp BETWEEN '{$period_start}' AND '{$period_end}'
        ";
        /** @var array $bonuses */
        $bonuses = $this->db->sh($user_id)->loadArray($sql);

        $fs_sql = "
            SELECT
                ABS(amount) as amount,
                " . ICSConstants::FRB_COST . " as bonus_type,
                created_at as timestamp
            FROM wins
            WHERE
                bonus_bet = " . ICSConstants::FS_WIN_TYPE . "
                AND
                  created_at BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                AND
                    user_id = {$user_id}
        ";

        /** @var array $fs_wins */
        $fs_wins = $this->db->sh($user_id)->loadArray($fs_sql);

        foreach ($fs_wins as $row) {
            if( is_array($bonuses) ){
                $bonuses[] = $row;
            }
        }

        foreach ($bonuses as $i => $bonus) {
            $bonuses[$i]['description'] = $this->getBonusDescription((string) $bonus['bonus_type']);
        }

        return $this->formatBreakdownForBonuses($bonuses);
    }

    protected function formatBreakdownForBonuses(array $operations): array
    {
        if (empty($operations)) {
            return [
                'Total' => $this->formatAmount(0),
            ];
        }

        $desglose = $totals = [];

        foreach ($operations as $operation)
        {
            $currency = $operation['currency'] ?? 'EUR';
            $amount = $operation['amount'];
            $totals[$currency] += $amount;
            $date = phive()->fDate($operation['timestamp'], ICSConstants::DATETIME_FORMAT);

            // Wager bonus credit
            if ($operation['bonus_type'] == 68) {
                $desglose[] = [
                    'Concepto' => ICSConstants::BONUS_DESCRIPTION_CONCESSION,
                    'Fecha' => $date,
                    'FechaActivacion' => $date,
                    'Importe' => $this->formatAmount($amount, 'EURBono'),
                ];
                continue;
            }

            // Wager bonus payout / shift
            if ($operation['bonus_type'] == 69) {
                $desglose[] = [
                    'Concepto' => ICSConstants::BONUS_DESCRIPTION_RELEASE,
                    'Fecha' => $date,
                    'FechaActivacion' => $date,
                    'Importe' => $this->formatAmount(-$amount, 'EURBono'),
                ];

                $desglose[] = [
                    'Concepto' => ICSConstants::BONUS_DESCRIPTION_RELEASE,
                    'Fecha' => $date,
                    'FechaActivacion' => $date,
                    'Importe' => $this->formatAmount($amount, $currency),
                ];
                continue;
            }

            // Wager bonus debit
            if ($operation['bonus_type'] == 70) {
                $desglose[] = [
                    'Concepto' => ICSConstants::BONUS_DESCRIPTION_CANCELLATION,
                    'Fecha' => $date,
                    'FechaActivacion' => $date,
                    'Importe' => $this->formatAmount(-$amount, 'EURBono'),
                ];
                continue;
            }

            $desglose[] = [
                'Concepto' => ICSConstants::BONUS_DESCRIPTION_CONCESSION,
                'Fecha' => $date,
                'FechaActivacion' => $date,
                'Importe' => $this->formatAmount($amount, 'EURBono'),
            ];

            $desglose[] = [
                'Concepto' => ICSConstants::BONUS_DESCRIPTION_RELEASE,
                'Fecha' => $date,
                'FechaActivacion' => $date,
                'Importe' => $this->formatAmount(-$amount, 'EURBono'),
            ];

            $desglose[] = [
                'Concepto' => ICSConstants::BONUS_DESCRIPTION_RELEASE,
                'Fecha' => $date,
                'FechaActivacion' => $date,
                'Importe' => $this->formatAmount($amount, $currency),
            ];
        }

        return [
            'Total' => $this->formatAmount($totals),
            'Desglose' => $desglose
        ];
    }

    protected function mapBonusType(int $bonus_type): string
    {
        return ICSConstants::BONUS_TYPE_DESCRIPTIONS[$bonus_type] ??
            ICSConstants::BONUS_DESCRIPTION_CONCESSION;
    }
}
