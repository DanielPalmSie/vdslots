<?php

namespace ES\ICS\Reports\v2;

use Carbon\Carbon;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Type\Card;
use Exception;
use ES\ICS\Reports\BaseReport;

class CJD extends BaseReport
{
    public const TYPE = 'CJ';
    public const SUBTYPE = 'CJD';
    public const NAME = 'RegistroCJD';
    protected static int $internal_version = 11;

    private $card;

    /**
     * @var string List of cash_transactions transactiontypes. Used for Bonos calculation
     */
    protected string $bonus_types;

    public function __construct($iso, $lic_settings = [], $report_settings = [])
    {
        parent::__construct($iso, $lic_settings, $report_settings);

        $this->bonus_types = implode(',', $this->getBonusTypes());
        $this->card = new Card($this->frequency);
    }

    /**
     * Return list of users grouped according to report and sub record limit rules
     *
     * @return array
     * @throws Exception
     */
    public function getGroupedRecords(): array
    {
        $is_daily = $this->getFrequency() === ICSConstants::DAILY_FREQUENCY;
        $users_ids = $is_daily
            ? $this->getUsersIdsWithTransactions()
            : $this->getUsersIdsFullyRegistered();

        $users = array_map(function ($user_id) {
            return $this->formatUserData($user_id);
        }, $users_ids);

        if($is_daily) {
            $users = array_filter($users, [$this, 'onlyPlayersWithMovements']);
        }

        $sub_records = array_chunk($users, ICSConstants::ITEMS_PER_SUBRECORD);

        return array_chunk($sub_records, ICSConstants::RECORD_PER_BATCH);
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

        $return = array_map(function ($users, $index) use ($total) {
            $xml_data = [
                '_attributes' => ['xsi:type' => self::NAME],
                'Cabecera' => $this->getRecordHeader($index + 1, $total),
                'Periodicidad' => ICSConstants::FREQUENCY_VALUES[$this->getFrequency()],
                'Periodo' => $this->getPeriod($this->getFrequency()),
                'Jugador' => $users,
            ];

            if (empty($xml_data['Jugador'])) {
                unset($xml_data['Jugador']);
            }

            return $xml_data;
        }, $records, array_keys($records));

        $this->logValidationErrors($return);
        return $return;
    }

    /**
     * Format CJD data
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    protected function formatUserData(int $user_id): array
    {
        $final_balance = $this->getEndBalance($user_id);

        return [
            'ID' => [
                'OperadorId' => $this->getOperatorId(),
                'JugadorId' => $user_id,
            ],
            'SaldoInicial' => $this->getInitialBalance($user_id),
            'Depositos' => $this->getDeposits($user_id),
            'Retiradas' => $this->getWithdrawals($user_id),
            'Participacion' => $this->getBets($user_id),
            'ParticipacionDevolucion' => $this->getRollbackBets($user_id),
            'Premios' => $this->getWins($user_id),
            'Otros' => $this->getOtherAffectingBalance($user_id),
            'SaldoFinal' => $final_balance,
            // We don't have "Price in Kind" but the field is mandatory
            'PremiosEspecie' => $this->formatBreakdownByOperator([]),
            'Cuentas' => [
                // Whe only have 1 account per acc what we should use user_id?
                'Cuenta' => $user_id,
                'SaldoFinal' => $final_balance
            ],
            'AjustePremios' => $this->getPrizeAdjustments($user_id),
            // We don't have transfers from other wallets
            'Trans_IN' => $this->formatBreakdownByOperator([]),
            // We don't have transfers to other wallets
            'Trans_OUT' => $this->formatBreakdownByOperator([]),
            'Comision' => $this->getFees($user_id),
            'Bonos' => $this->getBonus($user_id),
        ];
    }

    protected function onlyPlayersWithMovements(array $user): bool
    {
        $hasMovements =
            !empty($user['Depositos']['Operaciones']) || //not check 0 because we can have operation and cancellation
            !empty($user['Retiradas']['Operaciones']) ||
            $user['Participacion']['Total']['Linea'][0]['Cantidad'] !== '0.00' ||
            $user['ParticipacionDevolucion']['Total']['Linea'][0]['Cantidad'] !== '0.00' ||
            $user['Premios']['Total']['Linea'][0]['Cantidad'] !== '0.00' ||
            !empty($user['Otros']['Desglose']) ||
            !empty($user['Bonos']['Desglose']);

        if (!$hasMovements) {
            phive('Licensed/ES/ES')->reportLog(
                "ERROR: removing user with no movements from daily CJD {$user['ID']['JugadorId']}" .
                " {$this->period_start} {$this->period_end}"
            );
        }

        return $hasMovements;
    }

    /**
     * End balance
     * Get the Balance at the end of the report period.
     * users_daily_balance_stats.cash_balance has the starting balance of the day
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function getEndBalance(int $user_id): array
    {
        $sql = "
            SELECT (cash_balance + bonus_balance + extra_balance) AS final_balance,
                   currency
            FROM external_regulatory_user_balances
            WHERE
                    (user_id, balance_date, currency) IN
                    (
                        SELECT user_id, MAX(balance_date), currency
                        FROM external_regulatory_user_balances erub
                        WHERE
                              balance_date <= DATE('{$this->getPeriodEnd()}')
                          AND user_id = {$user_id}
                        GROUP BY user_id, currency
                    )
        ";

        $balance = $this->db->sh($user_id)->load1DArr($sql, 'final_balance', 'currency') ?: 0;

        return $this->formatAmount($balance);
    }

    /**
     * Initial balance
     * Get the balance at the beginning of the report period
     * from users_daily_balance_stats for the day before period start
     *
     * users_daily_balance_stats.cash_balance has the starting balance of the day
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function getInitialBalance(int $user_id): array
    {
        $sql = "
            SELECT (cash_balance + bonus_balance + extra_balance) AS initial_balance,
                   currency
            FROM external_regulatory_user_balances
            WHERE
                    (user_id, balance_date, currency) IN
                    (
                        SELECT user_id, MAX(balance_date), currency
                        FROM external_regulatory_user_balances erub
                        WHERE
                              balance_date < DATE('{$this->getPeriodStart()}')
                          AND user_id = {$user_id}
                        GROUP BY user_id, currency
                    )
        ";

        $initial_balance = $this->db->sh($user_id)->load1DArr($sql, 'initial_balance', 'currency') ?: 0;

        return $this->formatAmount($initial_balance);
    }

    /**
     * Deposits
     * Get the user deposits for that period, deposits table
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function getDeposits(int $user_id): array
    {
        $sql = "
            SELECT
                d.amount,
                d.timestamp,
                d.ip_num AS ip,
                d.scheme,
                d.display_name,
                d.dep_type AS type,
                d.card_hash,
                d.status,
                d.mts_id,
                us.equipment,
                a.descr AS uagent
            FROM deposits AS d
            LEFT JOIN users_sessions AS us
                ON us.id = (
                    SELECT us2.id
                    FROM users_sessions AS us2
                    WHERE us2.user_id = {$user_id}
                    AND us2.created_at < d.timestamp
                    ORDER BY us2.created_at DESC
                    LIMIT 1
                )
            LEFT JOIN actions AS a
                ON a.id = (
                    SELECT a.id
                    FROM actions AS a
                    WHERE (a.target = {$user_id} or a.actor = {$user_id})
                    AND tag = 'uagent'
                    AND a.created_at < d.timestamp
                    ORDER BY a.created_at DESC
                    LIMIT 1
                )
            WHERE
                d.user_id = {$user_id}
                AND d.timestamp BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}';
        ";

        $deposits = $this->db->sh($user_id)->loadArray($sql);
        $card_hashes = $this->getOnlyUndefinedCardHashes($deposits, 'card_hash');
        $card_type = $this->card->getCardType($card_hashes);
        foreach($deposits as &$deposit) {
            if(isset($card_type[$deposit['card_hash']])) {
                $deposit['card_type'] = $card_type[$deposit['card_hash']];
            }
        }

        return $this->formatOperations($deposits, 'Operaciones', self::GROUP_DEPOSITS);
    }

    /**
     * Withdrawals
     * Get the user only the finalized withdrawals for that period, pending_withdrawals
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function getWithdrawals(int $user_id): array
    {
        $sql = "
            SELECT
                w.amount,
                w.timestamp,
                w.ip,
                w.scheme,
                w.display_name,
                w.type,
                w.card_hash,
                us.equipment,
                a.descr AS uagent,
                w.status,
                w.mts_id,
                w.user_id,
                w.approved_by
            FROM (
                SELECT
                    pw.amount * -1 AS amount,
                    pw.timestamp,
                    pw.ip_num AS ip,
                    pw.scheme,
                    pw.payment_method AS display_name,
                    pw.payment_method AS type,
                    pw.scheme AS card_hash,
                    pw.status,
                    pw.mts_id,
                    pw.user_id,
                    pw.approved_by
                FROM pending_withdrawals pw
                WHERE
                    pw.user_id = '{$user_id}'
                    AND pw.timestamp BETWEEN '{$this->getPeriodStart()}'
                        AND '{$this->getPeriodEnd()}'
            UNION ALL
                SELECT
                    ct.amount,
                    ct.timestamp,
                    pw.ip_num AS ip,
                    pw.scheme,
                    pw.payment_method AS display_name,
                    pw.payment_method AS type,
                    pw.scheme AS card_hash,
                    pw.status,
                    pw.mts_id,
                    pw.user_id,
                    pw.approved_by
                FROM cash_transactions ct
                INNER JOIN pending_withdrawals pw ON ct.parent_id = pw.id
                WHERE
                    ct.parent_id != 0
                    AND ct.transactiontype = 13
                    AND ct.user_id = '{$user_id}'
                    AND ct.timestamp BETWEEN '{$this->getPeriodStart()}'
                        AND '{$this->getPeriodEnd()}'
            ) AS w
            LEFT JOIN users_sessions AS us
                ON us.id = (
                    SELECT us2.id
                    FROM users_sessions AS us2
                    WHERE us2.user_id = '{$user_id}'
                    AND us2.created_at < w.timestamp
                    ORDER BY us2.created_at DESC
                    LIMIT 1
                )
            LEFT JOIN actions AS a
                ON a.id = (
                    SELECT a.id
                    FROM actions AS a
                    WHERE (a.target = '{$user_id}' or a.actor = '{$user_id}')
                    AND tag = 'uagent'
                    AND a.created_at < w.timestamp
                    ORDER BY a.created_at DESC
                    LIMIT 1
                )
            ORDER BY timestamp";

        $withdrawals = $this->db->sh($user_id)->loadArray($sql);
        $card_hashes = $this->getOnlyUndefinedCardHashes($withdrawals, 'scheme');
        $card_type = $this->card->getCardType($card_hashes);
        foreach($withdrawals as &$withdrawal) {
            if(isset($card_type[$withdrawal['scheme']])) {
                $withdrawal['card_type'] = $card_type[$withdrawal['scheme']];
            }
        }

        return $this->formatOperations($withdrawals, 'Operaciones', self::GROUP_WITHDRAWALS);
    }

    /**
     * Bets
     * Get the user bets for that period by game type
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function getBets(int $user_id): array
    {
        // we use bets here because ugs can go over the 00:00 mark, so reporting is askew
        $sql = "
            SELECT
                {$user_id} as user_id,
                SUM(bets.amount) * -1 AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM bets
            INNER JOIN micro_games AS mg ON mg.ext_game_name = bets.game_ref AND mg.device_type_num = bets.device_type
            WHERE bets.user_id = {$user_id}
                AND bets.created_at BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
            GROUP BY mg.id;
        ";

        $bets = $this->db->sh($user_id)->loadArray($sql);
        $this->db->prependFromNodeArchive($bets, $user_id, $this->getPeriodStart(), $sql, 'bets');

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
                WHERE ugs.user_id = {$user_id}
                    AND ugs.bets_rollback > 0
                    AND ugs.start_time BETWEEN '{$this->getPeriodStart()}' AND '{$rollbacks_period_end}'
                GROUP BY ugs.id";
            $rollbacks = $this->db->sh($user_id)->loadArray($sql);
            if ($rollbacks) {
                $bets = array_merge($bets, $rollbacks);
            }
        }

        return $this->formatBreakdownByGameType($bets);
    }

    /**
     * Rollback bets
     * Get the user reimbursed bets for that period by game type
     * - table bets, where mg_id like "%ref"
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function getRollbackBets(int $user_id): array
    {
        // we should get this info from cash_transactions type=7,
        // but at the moment it doesn't include any relationship to the game played,
        // so we can't get the tag that way
        $sql = "
            SELECT
                ugs.bets_rollback AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM users_game_sessions AS ugs
            LEFT JOIN micro_games AS mg ON mg.ext_game_name = ugs.game_ref
            WHERE ugs.user_id = {$user_id}
                AND ugs.bets_rollback > 0
                AND ugs.start_time BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
            GROUP BY ugs.id;
        ";

        $bets = $this->db->sh($user_id)->loadArray($sql);

        return $this->formatBreakdownByGameType($bets);
    }

    /**
     * WINS
     * Get the user wins for that period by game type
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function getWins(int $user_id): array
    {
        // we use wins here because ugs can go over the 00:00 mark, so reporting is askew
        $sql = "
            SELECT
                SUM(wins.amount) AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM wins
            INNER JOIN micro_games AS mg ON mg.ext_game_name = wins.game_ref AND mg.device_type_num = wins.device_type
            WHERE wins.user_id = {$user_id}
                AND wins.created_at BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                AND bonus_bet <> " . ICSConstants::FS_WIN_TYPE . "
            GROUP BY mg.id
        ";

        $wins = $this->db->sh($user_id)->loadArray($sql);

        $this->db->prependFromNodeArchive($wins, $user_id, $this->getPeriodStart(), $sql, 'wins');

        return $this->formatBreakdownByGameType($wins);
    }

    /**
     * Other stuff affecting the user balance
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function getOtherAffectingBalance(int $user_id): array
    {
        //we filter by parent_id here, because type 13 is used for automatic withdrawal reversals, those are already accounted for when checking status=approved,
        //but it's also incorrectly used to manually add money to an account

        $sql = "
        SELECT
            amount,
            description,
            transactiontype,
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
                return ['amount' => $row['amount'], 'description' => $this->getOthersDescription($row)];
            },
            $data);

        return $this->formatBreakdownByOperatorDescription($data);
    }

    /**
     * Prize adjustments
     *
     * @param int $user_id
     * @return array
     * @noinspection PhpUnusedParameterInspection
     */
    public function getPrizeAdjustments(int $user_id): array
    {
        return $this->formatBreakdownByGameType([]);
    }

    /**
     * Fees
     * Get the user fees (GP fees are excluded) for that period
     *
     * @param int $user_id
     * @return array
     * @noinspection PhpUnusedParameterInspection
     */
    public function getFees(int $user_id): array
    {
        return $this->formatBreakdownByGameType([]);
    }

    /**
     * Bonus
     * Get user bonus
     * - the reason for why the bonus was assigned
     *
     * @param int $user_id
     * @return array
     * @throws Exception
     */
    public function getBonus(int $user_id): array
    {
        $period_start = $this->getPeriodStart();
        $period_end = $this->getPeriodEnd();
        $sql = "
            SELECT
                ABS(amount) as amount,
                ct.transactiontype as bonus_type
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
                " . ICSConstants::FRB_COST . " as bonus_type
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

        return $this->formatBreakdownByOperatorDescription($bonuses);
    }

    /**
     * Return the list of users who finished registration before the period end
     *
     * @return array
     * @throws Exception
     */
    private function getUsersIdsFullyRegistered(): array
    {
        $sql = $this->getSqlUsersIdsFullyRegistered();

        return $this->db->shs()->loadCol($sql, 'id');
    }

    /**
     * Get list of users who made transactions of at least 1 euro
     *
     * @return array
     * @throws Exception
     */
    private function getUsersIdsWithTransactions(): array
    {
        $sql = $this->getSqlSelectUsersIdsDailyStats();

        return $this->db->shs()->loadCol($sql, 'user_id');
    }

    private function logValidationErrors(array $records): void
    {
        foreach ($records as $batch){
            foreach ($batch['Jugador'] as $player){
                $total = $player['SaldoInicial']['Linea'][0]['Cantidad']
                    + $player['Depositos']['Total']
                    + $player['Retiradas']['Total']
                    + $player['Participacion']['Total']['Linea'][0]['Cantidad']
                    + $player['ParticipacionDevolucion']['Total']['Linea'][0]['Cantidad']
                    + $player['Premios']['Total']['Linea'][0]['Cantidad']
                    + $player['Otros']['Total']['Linea'][0]['Cantidad']
                    + $player['PremiosEspecie']['Total']['Linea'][0]['Cantidad']
                    + $player['AjustePremios']['Total']['Linea'][0]['Cantidad']
                    + $player['Trans_IN']['Total']['Linea'][0]['Cantidad']
                    + $player['Trans_OUT']['Total']['Linea'][0]['Cantidad']
                    + $player['Bonos']['Total']['Linea'][0]['Cantidad']
                    + $player['Comision']['Total']['Linea'][0]['Cantidad'];

                if (!static::compareAmounts($total, $player['SaldoFinal']['Linea'][0]['Cantidad'])){
                    phive('Licensed/ES/ES')->reportLog("ERROR :: ICS:CJD {$this->frequency} {$this->period_start} {$this->period_end} Error in the transaction calculations for user: {$player['ID']['JugadorId']}");
                }
            }
        }
    }
}
