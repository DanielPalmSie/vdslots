<?php

namespace ES\ICS\Reports\v2;

use Carbon\Carbon;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Type\Card;
use ES\ICS\Type\Transaction;
use Exception;
use ES\ICS\Reports\BaseReport;

class CJT extends BaseReport
{
    public const TYPE = 'CJ';
    public const SUBTYPE = 'CJT';
    public const NAME = 'RegistroCJT';
    protected static int $internal_version = 9;

    private $card;

    /**
     * @var string List of cash_transactions transactiontypes. Used for Bonos calculation
     */
    protected string $bonus_types;

    public function __construct($iso, $lic_settings = [], $report_settings = [], $users = [])
    {
        parent::__construct($iso, $lic_settings, $report_settings, $users);

        $this->bonus_types = implode(',', $this->getBonusTypes());
        $this->card = new Card($this->frequency);
    }

    /**
     * todo: get all the data in a single query
     * todo: this is currently just a hack to follow the established process
     *
     * @return array
     * @throws Exception
     */
    public function getGroupedRecords(): array
    {
        return [
            [1]
        ];
    }


    /**
     * Map list of users to required Registro structure
     *
     * @param array $records
     * @param string $group CJ* - batch number, Other - game type
     * @return array
     * @throws Exception
     */
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
                'Otros'                   => $this->getOtherAffectingBalance(),
                'SaldoFinal'              => $this->getFinalBalance(),
                // We dont have "Price in Kind" but the field is mandatory
                'PremiosEspecie'          => $this->formatBreakdownByOperator([]),
                'AjustePremios'           => $this->getPrizeAdjustments(),
                // We don't have transfers from other wallets
                'Trans_IN'                => $this->formatBreakdownByOperator([]),
                // We don't have transfers to other wallets
                'Trans_OUT'               => $this->formatBreakdownByOperator([]),
                'Comision'                => $this->getFees(),
                'Bonos'                   => $this->getBonus(),
            ];
        }, $records, array_keys($records));
        $this->logValidationErrors($return);

        return $return;
    }

    /**
     * Get list of users which will be reported
     * Where there's no list on the report we return only one item
     *
     * @return array
     */
    public function getUsersToReport(): array
    {
        return [1];
    }

    /**
     * Convert transactional operation to the required structure
     *
     * @param Transaction $operation
     * @param string $format
     *
     * @return array
     */
    public function mapOperationFormat(
        Transaction $operation,
        string $format = ICSConstants::DATETIME_FORMAT,
        ?string $group = null
    ): array {

        $tipo_medio_pago = $operation->getCardType() ?: $operation->getPaymentMethodType();

        $operation_formatted = [
            'MedioPago' => $operation->getPaymentMethod(),
            'TipoMedioPago' => $tipo_medio_pago,
            'OtroTipoEspecificar' => $tipo_medio_pago == 99 ? $operation->getDisplayName() : '',
            'Importe' => $operation->getAmount(),
        ];

        if (empty($operation_formatted['OtroTipoEspecificar'])) {
            unset($operation_formatted['OtroTipoEspecificar']);
        }

        return $operation_formatted;
    }

    /**
     * Sum of opening balances of all gambling accounts
     *
     * The sum of the participants' balances in their gambling accounts,
     * as they stood at the start of the period
     *
     * @return array
     * @throws Exception
     */
    protected function getInitialBalance(): array
    {
        $sql = "
            SELECT
                   SUM(udbs.cash_balance + udbs.bonus_balance + udbs.extra_balance) AS initial_balance,
                   currency
            FROM external_regulatory_user_balances AS udbs
            WHERE
                    (user_id, balance_date, currency) IN
                    (
                        SELECT user_id, MAX(balance_date), currency
                        FROM external_regulatory_user_balances erub
                        WHERE
                              balance_date < DATE('{$this->getPeriodStart()}')
                          {$this->filterByUserId('erub.user_id')}
                        GROUP BY user_id, currency
                    )
            GROUP BY currency
        ";

        $res = $this->db->shs()->loadArray($sql) ?: [0 => ['initial_balance' => "0", 'currency' => ICSConstants::CURRENCY]];

        $res = array_reduce($res,
            static function ($carry, $line) {
                $carry[$line['currency']] = ($carry[$line['currency']] ?? 0) + $line['initial_balance'];
                return $carry;
            },
            []);

        return $this->formatAmount($res);
    }

    /**
     * Sum of deposits by participants, broken down by payment method
     *
     * @return array
     * @throws Exception
     */
    protected function getDeposits(): array
    {
        $sql = "
            SELECT
                   d.amount,
                   d.timestamp,
                   d.ip_num AS ip,
                   d.scheme,
                   d.display_name,
                   d.dep_type AS type,
                   d.card_hash
            FROM deposits AS d
            WHERE d.timestamp BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                {$this->filterByUserId('d.user_id')}
        ";

        $items = $this->db->shs()->loadArray($sql);
        $card_hashes = $this->getOnlyUndefinedCardHashes($items, 'card_hash');
        $card_type = $this->card->getCardType($card_hashes);
        foreach($items as &$item) {
            if(isset($card_type[$item['card_hash']])) {
                $item['card_type'] = $card_type[$item['card_hash']];
            }
        }
        $items = $this->groupTransactions($items, function ($operation) {
            return (new Transaction($operation, $this->lic_settings))->getPaymentMethod();
        });

        return $this->formatOperations($items);
    }

    /**
     * Withdrawals by the participant, broken down by payment method
     *
     * @return array
     * @throws Exception
     */
    protected function getWithdrawals(): array
    {
        $sql = "
            SELECT
                   pw.amount * -1 AS amount,
                   pw.scheme,
                   pw.payment_method AS display_name,
                   pw.payment_method AS type,
                   pw.scheme AS card_hash
            FROM pending_withdrawals pw
            WHERE pw.timestamp BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                {$this->filterByUserId('pw.user_id')}
        UNION ALL
            SELECT
                ct.amount,
                pw.scheme,
                pw.payment_method AS display_name,
                pw.payment_method AS type,
                pw.scheme AS card_hash
            FROM cash_transactions ct
            INNER JOIN pending_withdrawals pw ON ct.parent_id = pw.id
            WHERE
                ct.parent_id != 0
                AND ct.transactiontype = 13
                {$this->filterByUserId('pw.user_id')}
                AND ct.timestamp BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
        ";

        $items = $this->db->shs()->loadArray($sql);
        $card_hashes = $this->getOnlyUndefinedCardHashes($items, 'scheme');
        $card_type = $this->card->getCardType($card_hashes);
        foreach($items as &$item) {
            if(isset($card_type[$item['scheme']])) {
                $item['card_type'] = $card_type[$item['scheme']];
            }
        }
        $items = $this->groupTransactions($items, function ($operation) {
            return (new Transaction($operation, $this->lic_settings))->getPaymentMethod();
        });

        return $this->formatOperations($items);
    }

    /**
     * Sum of amounts wagered in gambling
     *  - broken down according to type of gambling and operator
     *
     * @return array
     * @throws Exception
     */
    protected function getBets(): array
    {
        $sql = "
            SELECT
                   SUM(bets.amount) * -1 AS amount,
                   mg.tag AS game_tag,
                   mg.id AS game_id
            FROM bets
            INNER JOIN micro_games AS mg ON mg.ext_game_name = bets.game_ref AND mg.device_type_num = bets.device_type
            WHERE bets.created_at BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                {$this->filterByUserId('bets.user_id')}
            GROUP BY mg.id;
        ";

        $items = $this->db->shs()->loadArray($sql);

        $this->db->prependFromArchives($items, $this->getPeriodStart(), $sql, 'bets');

        // we add rollbacks here, because the rollback process deletes those bets, but they must be informed for SaldoFinal to be correct
        $change_to_rollbacks_v2_timestamp = phive('Licensed')->getSetting('change_to_rollbacks_v2_timestamp');
        $is_before_rollbacks_v2 = Carbon::createFromFormat('Y-m-d H:i:s', $this->getPeriodStart())
            ->isBefore($change_to_rollbacks_v2_timestamp);
        if ($is_before_rollbacks_v2) {
            $rollbacks_period_end = $this->getPeriodEnd();

            if (Carbon::createFromFormat('Y-m-d H:i:s', $change_to_rollbacks_v2_timestamp)->isBefore($this->getPeriodEnd())) {
                $rollbacks_period_end = $change_to_rollbacks_v2_timestamp;
            }

            $sql = "SELECT
                ugs.bets_rollback * -1 AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM users_game_sessions AS ugs
            LEFT JOIN micro_games AS mg ON mg.ext_game_name = ugs.game_ref AND mg.device_type_num=ugs.device_type_num
            WHERE
                ugs.bets_rollback > 0
                AND ugs.start_time BETWEEN '{$this->getPeriodStart()}' AND '{$rollbacks_period_end}'
                {$this->filterByUserId('ugs.user_id')}
            GROUP BY ugs.id";
            $rollbacks = $this->db->shs()->loadArray($sql);
            if ($rollbacks) {
                $items = array_merge($items, $rollbacks);
            }
        }

        $items = $this->groupTransactions($items, function ($operation) {
            return $this->getGameType($operation['game_tag'], $operation['game_id']);
        });

        return $this->formatBreakdownByGameType($items, true);
    }

    /**
     * Sum of reimbursements of wagered amounts
     *  - broken down according to type of gambling and operator
     *
     * @return array
     * @throws Exception
     */
    protected function getRollbackBets(): array
    {
        $sql = "
            SELECT
                ugs.bets_rollback AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM users_game_sessions AS ugs
            LEFT JOIN micro_games AS mg ON mg.ext_game_name = ugs.game_ref AND mg.device_type_num=ugs.device_type_num
            WHERE ugs.start_time BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                AND ugs.bets_rollback > 0
                {$this->filterByUserId('ugs.user_id')}
            GROUP BY ugs.id;
        ";

        $items = $this->db->shs()->loadArray($sql);
        $items = $this->groupTransactions($items, function ($operation) {
            return $this->getGameType($operation['game_tag'], $operation['game_id']);
        });

        return $this->formatBreakdownByGameType($items, true);
    }

    /**
     * Sum of cash prizes
     *  - broken down according to the type of gambling and operator
     *
     * @return array
     * @throws Exception
     */
    protected function getWins(): array
    {
        $sql = "
            SELECT
                SUM(wins.amount) AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM wins
            INNER JOIN micro_games AS mg ON mg.ext_game_name = wins.game_ref AND mg.device_type_num=wins.device_type
            WHERE wins.created_at BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                AND bonus_bet <>  " . ICSConstants::FS_WIN_TYPE . "
                {$this->filterByUserId('wins.user_id')}
            GROUP BY mg.id;
        ";

        $items = $this->db->shs()->loadArray($sql);

        $this->db->prependFromArchives($items, $this->getPeriodStart(), $sql, 'wins');

        $items = $this->groupTransactions($items, function ($operation) {
            return $this->getGameType($operation['game_tag'], $operation['game_id']);
        });

        return $this->formatBreakdownByGameType($items, true);
    }

    /**
     * Sum of other transactions
     *  - broken down according to the type of gambling, operator and heading
     *
     * @return array
     * @throws Exception
     */
    protected function getOtherAffectingBalance(): array
    {
        //we filter by parent_id here, because type 13 is used for automatic withdrawal reversals, those are already accounted for when checking status=approved,
        //but it's also incorrectly used to manually add money to an account

        $sql = "
        SELECT
            amount,
            description,
            id,
            transactiontype
        FROM cash_transactions ct
        WHERE
            ct.transactiontype IN ({$this->getOthersTypes()})
            AND ct.parent_id = 0
            AND ct.timestamp BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
            {$this->filterByUserId('ct.user_id')}
        ";
        $data = $this->db->shs()->loadArray($sql);

        $data = array_map(
            function ($row) {
                return ['amount' => $row['amount'], 'description' => $this->getOthersDescription($row)];
            },
            $data);

        return $this->formatBreakdownByOperatorDescription($data);
    }

    /**
     * Sum of closing balances
     *
     * @return array
     * @throws Exception
     */
    protected function getFinalBalance(): array
    {
        $sql = "
            SELECT
                   SUM(udbs.cash_balance + udbs.bonus_balance + udbs.extra_balance) AS final_balance,
                   currency
            FROM external_regulatory_user_balances AS udbs
            WHERE
                    (user_id, balance_date, currency) IN
                    (
                        SELECT user_id, MAX(balance_date), currency
                        FROM external_regulatory_user_balances erub
                        WHERE
                              balance_date <= DATE('{$this->getPeriodEnd()}')
                          {$this->filterByUserId('erub.user_id')}
                        GROUP BY user_id, currency
                    )
            GROUP BY currency
        ";

        $res = $this->db->shs()->loadArray($sql) ?: [0 => ['final_balance' => "0", 'currency' => ICSConstants::CURRENCY]];

        $res = array_reduce($res,
            static function ($carry, $line) {
                $carry[$line['currency']] = ($carry[$line['currency']] ?? 0) + $line['final_balance'];
                return $carry;
            },
            []);

        return $this->formatAmount($res);
    }

    /**
     * Sum of prize adjustments or cancellations
     *  - broken down according to the type of gambling and operator
     *
     * @return array
     * @throws Exception
     */
    protected function getPrizeAdjustments(): array
    {
        return $this->formatBreakdownByGameType([]);
    }

    /**
     * Sum of fees paid
     *  - broken down according to the type of gambling and operator
     *
     * @return array
     * @throws Exception
     */
    protected function getFees(): array
    {
        return $this->formatBreakdownByGameType([]);
    }

    /**
     * Sum of bonuses
     * - broken down according to operator
     *
     * @return array
     * @throws Exception
     */
    protected function getBonus(): array
    {
        $period_start = $this->getPeriodStart();
        $period_end = $this->getPeriodEnd();

        $sql = "
            SELECT
                abs(amount) as amount,
                ct.transactiontype as bonus_type
            FROM cash_transactions AS ct
            LEFT JOIN bonus_types AS bt ON bt.id = ct.bonus_id
            WHERE ct.transactiontype IN ({$this->bonus_types})
                AND ct.timestamp BETWEEN '{$period_start}' AND '{$period_end}'
                {$this->filterByUserId('ct.user_id')}
        ";

        /** @var array $bonuses */
        $bonuses = $this->db->shs()->loadArray($sql);

        $fs_sql = "
            SELECT
                ABS(amount) as amount,
                " . ICSConstants::FRB_COST . " as bonus_type
            FROM wins
            WHERE
                bonus_bet = " . ICSConstants::FS_WIN_TYPE . "
                AND
                  created_at BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                {$this->filterByUserId('user_id')}
        ";

        /** @var array $fs_wins */
        $fs_wins = $this->db->shs()->loadArray($fs_sql);

        foreach ($fs_wins as $row) {
            if( is_array($bonuses) ){
                $bonuses[] = $row;
            }
        }

        foreach ($bonuses as $i => $bonus) {
            $bonuses[$i]['description'] = $this->getBonusDescription((string) $bonus['bonus_type']);
        }

        return $this->formatBreakdownByOperatorDescription($bonuses);
    }

    protected function logValidationErrors(array $records): void
    {
        foreach ($records as $record){
            $total = $record['SaldoInicial']['Linea'][0]['Cantidad']
                + $record['Depositos']['Total']
                + $record['Retiradas']['Total']
                + $record['Participacion']['Total']['Linea'][0]['Cantidad']
                + $record['ParticipacionDevolucion']['Total']['Linea'][0]['Cantidad']
                + $record['Premios']['Total']['Linea'][0]['Cantidad']
                + $record['Otros']['Total']['Linea'][0]['Cantidad']
                + $record['PremiosEspecie']['Total']['Linea'][0]['Cantidad']
                + $record['AjustePremios']['Total']['Linea'][0]['Cantidad']
                + $record['Trans_IN']['Total']['Linea'][0]['Cantidad']
                + $record['Trans_OUT']['Total']['Linea'][0]['Cantidad']
                + $record['Bonos']['Total']['Linea'][0]['Cantidad']
                + $record['Comision']['Total']['Linea'][0]['Cantidad'];

            if (!static::compareAmounts($total, $record['SaldoFinal']['Linea'][0]['Cantidad'])){
                phive('Licensed/ES/ES')->reportLog("ERROR :: ICS:CJT {$this->frequency} {$this->period_start} {$this->period_end} Error in the transaction calculations!");
            }
        }
    }

    protected function getOthersDescription($row): string
    {
        switch ($row['transactiontype']){
            case 85:
            case 38:
                return 'BOS';
            default:
                return parent::getOthersDescription($row);
        }
    }
}
