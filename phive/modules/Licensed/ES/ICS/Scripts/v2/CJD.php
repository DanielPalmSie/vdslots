<?php

use Carbon\Carbon;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Validation\InternalVersionMismatchException;

require_once '/var/www/videoslots/phive/vendor/autoload.php';
include_once '/var/www/videoslots/phive/phive.php';

// Example
// php CJD.php '2022-02-01 00:00:00' '2022-02-01 23:59:59' daily
// php CJD.php '2022-02-01 00:00:00' '2022-02-28 23:59:59' monthly

$start = $argv[1];
$end = $argv[2];
$frequency = ucfirst($argv[3]);
$internal_version = 11;

validateArguments($start, $end, $frequency);
validateInternalVersion($internal_version);
collectDataAndPutIntoCsv($start, $end, $frequency);

function collectDataAndPutIntoCsv(string $start, string $end, string $frequency)
{
    $file_name = getFileName($start, $end, $frequency, 'totals');

    $fp = fopen($file_name, 'w');
    fputcsv($fp, ['user_id', 'initial_balance', 'currency', 'final_balance', 'currency', 'deposit', 'withdrawals', 'bets', 'rollback_bets', 'wins', 'other_affecting_balance', 'bonus']);

    $user_ids = getUserIds($start, $end, $frequency);

    foreach ($user_ids as $user_id) {
        $initial_balance = getInitialBalance($user_id, $start);
        $end_balance = getEndBalance($user_id, $end);

        $deposits = getDeposits($user_id, $start, $end);
        $withdrawals = getWithdrawals($user_id, $start, $end);
        $bonus = getBonus($user_id, $start, $end);

        $fields = [
            $user_id,
            $initial_balance[0]['initial_balance'],
            $initial_balance[0]['currency'],
            $end_balance[0]['final_balance'],
            $end_balance[0]['currency'],
            $deposits['total'],
            $withdrawals['total'],
            getBets($user_id, $start, $end)['total'],
            getRollbackBets($user_id, $start, $end)['total'],
            getWins($user_id, $start, $end)['total'],
            getOtherAffectingBalance($user_id, $start, $end)['total'],
            $bonus['total'],
        ];

        if( $frequency === ICSConstants::DAILY_FREQUENCY &&
            empty($deposits['items']) &&
            empty($withdrawals['items']) &&
            empty($bonus['items']) &&
            ! $fields[7] &&
            ! $fields[8] &&
            ! $fields[9] &&
            ! $fields[10]
        ){
            continue;
        }

        fputcsv($fp, $fields);

        if (sizeof($initial_balance) > 1 || sizeof($end_balance) > 1) {
            $iterable = sizeof($initial_balance) > sizeof($end_balance) ? $initial_balance : $end_balance;

            foreach ($iterable as $i => $row) {
                if ($i === 0) {
                    continue;
                }

                $fields = [
                    $user_id,
                    $initial_balance[$i]['initial_balance'],
                    $initial_balance[$i]['currency'],
                    $end_balance[$i]['final_balance'],
                    $end_balance[$i]['currency'], '','','','','','',''
                ];

                fputcsv($fp, $fields);

            }
        }
    }

    echo "Done! File's name is `{$file_name}`" . PHP_EOL;

    $tables = [
        'deposits' => 'getDeposits',
        'withdrawals' => 'getWithdrawals',
        'bets' => 'getBets',
        'rollback_bets' => 'getRollbackBets',
        'wins' => 'getWins',
        'other_affecting_balance' => 'getOtherAffectingBalance',
        'bonuses' => 'getBonus',
    ];

    foreach ($tables as $table => $rows) {
        ${$table} = [];
        foreach ($user_ids as $user_id) {
            ${"tmp_{$table}"} = ($rows)($user_id, $start, $end)['items'];
            ${$table} = array_merge(${$table}, ${"tmp_{$table}"});
        }

        $file_name = getFileName($start, $end, $frequency, $table);
        $fp = fopen($file_name, 'w');
        fputcsv($fp, array_keys(${$table}[0]));

        foreach (${$table} as $row) {
            fputcsv($fp, $row);
        }

        echo "Done! File's name is `{$file_name}`" . PHP_EOL;
    }
}

function validateArguments(string $start, string $end, string $frequency)
{
    if (empty($start) || empty($end) || strlen($start) != 19 || strlen($end) != 19) {
        throw new Exception('Wrong `start` & `end`');
    }

    if ((date('Y-m-d H:i:s', strtotime($start)) !== $start) || (date('Y-m-d H:i:s', strtotime($end)) !== $end)) {
        throw new Exception('Wrong `start` & `end`');
    }

    if (empty($frequency) || !in_array($frequency, [ICSConstants::DAILY_FREQUENCY, ICSConstants::MONTHLY_FREQUENCY])) {
        throw new Exception('Wrong `frequency`');
    }
}

/**
 * @param $internal_version
 * @throws Exception
 */
function validateInternalVersion ($internal_version){
    if($internal_version !== \ES\ICS\Reports\v2\CJD::getInternalVersion()){
        throw new InternalVersionMismatchException();
    }
}

function getUserIds(string $start, string $end, string $frequency)
{
    return $frequency === ICSConstants::DAILY_FREQUENCY
        ? getUsersIdsWithTransactions($start, $end)
        : getUsersIdsFullyRegistered($start, $end);
}

function getUsersIdsWithTransactions(string $start, string $end)
{
    $fully_registered = getSqlUsersIdsFullyRegistered($start, $end);

    $sql = "SELECT DISTINCT user_id
            FROM external_regulatory_user_balances
            WHERE
                balance_date BETWEEN '{$start}' AND '{$end}'
                AND user_id IN ({$fully_registered})
        ";

    return phive('SQL')->readOnly()->shs()->loadCol($sql, 'user_id');
}

function filterUsers(string $user_id_column)
{
    return "
        AND {$user_id_column} NOT IN (
            SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'test_account' AND u_s.value = 1
        )
    ";
}

function getSqlUsersIdsFullyRegistered(string $start, string $end)
{
    $where = getBaseUserWhereCondition(null, $start, $end);

    return "
        SELECT id
        FROM users
        WHERE {$where}
    ";

}

function getUsersIdsFullyRegistered(string $start, string $end)
{
    return phive('SQL')->readOnly()->shs()->loadCol(getSqlUsersIdsFullyRegistered($start, $end), 'id');
}

function getBaseUserWhereCondition(?string $signComparing = null, string $start = '', string $end = ''): string
{
    $filter_user = filterUsers('users.id');

    $sql = "
            users.country = 'ES'
            {$filter_user}
            AND users.id NOT IN (
                SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'registration_in_progress' AND u_s.value >= 1
            )
        ";

    if ($signComparing !== '<=') {
        $sql .= "
                AND users.id IN (
                    SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'registration_end_date' AND u_s.value <= '{$end}'
                )
            ";
    }

    if (in_array($signComparing, ['>=', '<='])) {
        $sql .= "
                AND users.id IN (
                    SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'registration_end_date' AND u_s.value {$signComparing} '{$start}'
                )
            ";
    }

    return $sql;
}

function getFileName(string $start, string $end, string $frequency, string $table)
{
    $start = substr($start, 0, 10);
    $end = substr($end, 0, 10);
    $file_name = basename(__FILE__, ".php");

    return "{$file_name}_{$table}_{$frequency}_{$start}_{$end}.csv";
}

function getEndBalance(int $user_id, string $end): array
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
                              balance_date <= DATE('{$end}')
                          AND user_id = {$user_id}
                        GROUP BY user_id, currency
                    )
        ";

    return phive('SQL')->readOnly()->sh($user_id)->loadArray($sql) ?: [];
}

function getInitialBalance(int $user_id, string $start): array
{
    $sql = "
            SELECT
                (cash_balance + extra_balance + bonus_balance) as initial_balance,
                currency
            FROM external_regulatory_user_balances
            WHERE
                (user_id, balance_date, currency) IN
                (
                    SELECT user_id, MAX(balance_date), currency
                        FROM external_regulatory_user_balances erub
                        WHERE
                              balance_date < DATE('{$start}')
                              AND user_id = {$user_id}
                        GROUP BY user_id, currency
                    )
        ";

    return phive('SQL')->readOnly()->sh($user_id)->loadArray($sql) ?: [];
}

function convertCentsToEur($amount_cents)
{
    return rnfCents($amount_cents, '.', '');
}

function getDeposits(int $user_id, string $start, string $end)
{
    $sql = "
            SELECT
                d.user_id,
                d.amount,
                d.timestamp,
                d.ip_num AS ip,
                d.scheme,
                d.display_name,
                d.dep_type AS type,
                d.card_hash,
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
                AND d.timestamp BETWEEN '{$start}' AND '{$end}';
        ";

    $deposits = phive('SQL')->readOnly()->sh($user_id)->loadArray($sql);
    $total = array_sum(array_column($deposits,'amount'));

    return [
        'total' => $total,
        'items' => $deposits
    ];
}

function getWithdrawals(int $user_id, string $start, string $end)
{
    $sql = "
            SELECT
                w.user_id,
                w.amount,
                w.timestamp,
                w.ip,
                w.scheme,
                w.display_name,
                w.type,
                w.card_hash,
                us.equipment,
                a.descr AS uagent
            FROM (
                SELECT
                    pw.user_id,
                    pw.amount * -1 AS amount,
                    pw.timestamp,
                    pw.ip_num AS ip,
                    pw.scheme,
                    pw.payment_method AS display_name,
                    pw.payment_method AS type,
                    pw.scheme AS card_hash
                FROM pending_withdrawals pw
                WHERE
                    pw.user_id = {$user_id}
                    AND pw.timestamp BETWEEN '{$start}' AND '{$end}'
            UNION ALL
                SELECT
                    ct.user_id,
                    ct.amount,
                    ct.timestamp,
                    pw.ip_num AS ip,
                    pw.scheme,
                    pw.payment_method AS display_name,
                    pw.payment_method AS type,
                    pw.scheme AS card_hash
                FROM cash_transactions ct
                INNER JOIN pending_withdrawals pw ON ct.parent_id = pw.id
                WHERE
                    ct.parent_id != 0
                    AND ct.transactiontype = 13
                    AND ct.user_id = '{$user_id}'
                    AND ct.timestamp BETWEEN '{$start}' AND '{$end}'
            ) AS w
            LEFT JOIN users_sessions AS us
                ON us.id = (
                    SELECT us2.id
                    FROM users_sessions AS us2
                    WHERE us2.user_id = {$user_id}
                    AND us2.created_at < w.timestamp
                    ORDER BY us2.created_at DESC
                    LIMIT 1
                )
            LEFT JOIN actions AS a
                ON a.id = (
                    SELECT a.id
                    FROM actions AS a
                    WHERE (a.target = {$user_id} or a.actor = {$user_id})
                    AND tag = 'uagent'
                    AND a.created_at < w.timestamp
                    ORDER BY a.created_at DESC
                    LIMIT 1
                )
";

    $withdrawals = phive('SQL')->readOnly()->sh($user_id)->loadArray($sql);

    $total = array_sum(array_column($withdrawals,'amount'));

    return [
        'total' => $total,
        'items' => $withdrawals
    ];
}

function getBets(int $user_id, string $start, string $end)
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
                AND bets.created_at BETWEEN '{$start}' AND '{$end}'
            GROUP BY mg.id;
        ";

    $bets = phive('SQL')->readOnly()->sh($user_id)->loadArray($sql);
    phive('SQL')->readOnly()->prependFromArchives($bets, $start, $sql, 'bets');

    // we add rollbacks here, because the rollback process deletes those bets, but they must be informed for SaldoFinal to be correct
    $change_to_rollbacks_v2_timestamp = phive('Licensed')->getSetting('change_to_rollbacks_v2_timestamp');
    $is_before_rollbacks_v2 = Carbon::createFromFormat('Y-m-d H:i:s', $start)
        ->isBefore($change_to_rollbacks_v2_timestamp);
    if ($is_before_rollbacks_v2) {
        $rollbacks_period_end = $end;

        if (Carbon::createFromFormat('Y-m-d H:i:s', $change_to_rollbacks_v2_timestamp)->isBefore($end)) {
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
                    AND ugs.start_time BETWEEN '{$start}' AND '{$rollbacks_period_end}'
                GROUP BY ugs.id";
        $rollbacks = phive('SQL')->readOnly()->sh($user_id)->loadArray($sql);
        if ($rollbacks) {
            $bets = array_merge($bets, $rollbacks);
        }
    }

    $total = array_sum(array_column($bets,'amount'));

    return [
        'total' => $total,
        'items' => $bets
    ];
}

function getRollbackBets(int $user_id, string $start, string $end)
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
                AND ugs.start_time BETWEEN '{$start}' AND '{$end}'
            GROUP BY ugs.id;
        ";

    $bets = phive('SQL')->readOnly()->sh($user_id)->loadArray($sql);

    $total = array_sum(array_column($bets,'amount'));

    return [
        'total' => $total,
        'items' => $bets
    ];
}

function getWins(int $user_id, string $start, string $end)
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
                AND wins.created_at BETWEEN '{$start}' AND '{$end}'
                AND bonus_bet <>  " . ICSConstants::FS_WIN_TYPE . "
            GROUP BY mg.id
        ";

    $wins = phive('SQL')->readOnly()->sh($user_id)->loadArray($sql);
    phive('SQL')->readOnly()->prependFromArchives($wins, $start, $sql, 'wins');

    $total = array_sum(array_column($wins,'amount'));

    return [
        'total' => $total,
        'items' => $wins
    ];
}

function getOtherAffectingBalance(int $user_id, string $start, string $end)
{
    //we filter by parent_id here, because type 13 is used for automatic withdrawal reversals, those are already accounted for when checking status=approved,
    //but it's also incorrectly used to manually add money to an account

    $sql = "
        SELECT
            amount,
            description
        FROM cash_transactions
        WHERE
            user_id = {$user_id}
            AND transactiontype IN (9,13,15,34,38,43,50,52,54,61,63,77,85,91)
            AND parent_id = 0
            AND timestamp BETWEEN '{$start}' AND '{$end}'
        ";

    $data = phive('SQL')->readOnly()->sh($user_id)->loadArray($sql);

    $total = array_sum(array_column($data,'amount'));

    return [
        'total' => $total,
        'items' => $data
    ];
}

function getBonus(int $user_id, string $start, string $end)
{
    $sql = "
            SELECT
                ABS(amount) as amount,
                ct.transactiontype as bonus_type
            FROM cash_transactions AS ct
            LEFT JOIN bonus_types AS bt ON bt.id = ct.bonus_id
            WHERE
                ct.user_id = {$user_id}
                AND ct.transactiontype IN (14,31,32,66,69,80,82,84,86,90,94,95,96)
                AND ct.timestamp BETWEEN '{$start}' AND '{$end}'
        ";

    /** @var array $bonuses */
    $bonuses = phive('SQL')->readOnly()->sh($user_id)->loadArray($sql);

    $fs_sql = "
            SELECT
                ABS(amount) as amount,
                " . ICSConstants::FRB_COST . " as bonus_type
            FROM wins
            WHERE
                bonus_bet = " . ICSConstants::FS_WIN_TYPE . "
                AND
                  created_at BETWEEN '{$start}' AND '{$end}'
                AND
                    user_id = {$user_id}
        ";

    $fs_wins = phive('SQL')->readOnly()->sh($user_id)->loadArray($fs_sql);

    foreach ($fs_wins as $row) {
        if( is_array($bonuses ) ){
            $bonuses [] = $row;
        }
    }

    $total = array_sum(array_column($bonuses,'amount'));

    return [
        'total' => $total,
        'items' => $bonuses
    ];
}
