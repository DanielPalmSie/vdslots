<?php

use Carbon\Carbon;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Validation\InternalVersionMismatchException;

require_once '/var/www/videoslots/phive/vendor/autoload.php';
include_once '/var/www/videoslots/phive/phive.php';

// Example
// php CJT.php '2022-02-01 00:00:00' '2022-02-01 23:59:59' daily
// php CJT.php '2022-02-01 00:00:00' '2022-02-28 23:59:59' monthly

$start = $argv[1];
$end = $argv[2];
$frequency = ucfirst($argv[3]);
$internal_version = 9;

validateArguments($start, $end, $frequency);
validateInternalVersion($internal_version);
collectDataAndPutIntoCsv($start, $end, $frequency);

function collectDataAndPutIntoCsv(string $start, string $end, string $frequency)
{
    $file_name_total = getFileName($start, $end, $frequency, 'totals');

    $fp = fopen($file_name_total, 'w');
    fputcsv($fp, ['initial_balance', 'final_balance', 'deposit', 'withdrawals', 'bets', 'rollback_bets', 'wins', 'other_affecting_balance', 'bonus']);

    $fields = [
        getInitialBalance($start, $end, $frequency)['total'],
        getFinalBalance($start, $end, $frequency)['total'],
        getDeposits($start, $end, $frequency)['total'],
        getWithdrawals($start, $end, $frequency)['total'],
        getBets($start, $end, $frequency)['total'],
        getRollbackBets($start, $end, $frequency)['total'],
        getWins($start, $end, $frequency)['total'],
        getOtherAffectingBalance($start, $end, $frequency)['total'],
        getBonus($start, $end, $frequency)['total'],
    ];

    fputcsv($fp, $fields);

    echo "Done! File's name is `{$file_name_total}`" . PHP_EOL;

    $card_hashes = array_merge(getDeposits($start, $end, $frequency)['card_hashes'],
        getWithdrawals($start, $end, $frequency)['card_hashes']);
    $card_type = (new Card())->getCardType($card_hashes, $frequency);
    $tables = [
        'initial_balance' => getInitialBalance($start, $end, $frequency)['items'],
        'final_balance' => getFinalBalance($start, $end, $frequency)['items'],
        'deposits' => getDeposits($start, $end, $frequency)['items'],
        'withdrawals' => getWithdrawals($start, $end, $frequency)['items'],
        'bets' => getBets($start, $end, $frequency)['items'],
        'rollback_bets' => getRollbackBets($start, $end, $frequency)['items'],
        'wins' => getWins($start, $end, $frequency)['items'],
        'other_affecting_balance' => getOtherAffectingBalance($start, $end, $frequency)['items'],
        'bonuses' => getBonus($start, $end, $frequency)['items'],
        'card_types' => $card_type
    ];

    foreach ($tables as $table => $rows) {

        $file_name = getFileName($start, $end, $frequency, $table);
        $fp = fopen($file_name, 'w');
        fputcsv($fp, array_keys($rows[0]));

        foreach ($rows as $row) {
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
    if($internal_version !== \ES\ICS\Reports\v2\CJT::getInternalVersion()){
        throw new InternalVersionMismatchException();
    }
}
function getInitialBalance(string $start, string $end, string $frequency)
{
    $where = filterByUserId('udbs.user_id', $start, $end, $frequency);

    $sql = "
            SELECT
                   IFNULL(SUM(udbs.cash_balance + udbs.extra_balance), 0) AS initial_balance
            FROM external_regulatory_user_balances AS udbs
            WHERE
                udbs.id = (
                    SELECT id
                    FROM external_regulatory_user_balances AS b
                    WHERE b.user_id = udbs.user_id AND b.balance_date < DATE('{$start}')
                    ORDER BY balance_date DESC
                    LIMIT 1)
                {$where}
        ";

    $res = phive('SQL')->readOnly()->shs()->loadArray($sql) ?? [];
    $total = array_sum(array_filter(array_column($res, 'initial_balance')));

    return [
        'total' => $total,
        'items' => $res
    ];
}

function filterByUserId(string $user_id_column, string $start, string $end, string $frequency): string
{
    if ($frequency === ICSConstants::DAILY_FREQUENCY) {
        $in = getSqlSelectUsersIdsDailyStats($start, $end);

    } else {
        $in = getSqlUsersIdsFullyRegistered($start, $end);

    }

    return "
            AND {$user_id_column} IN ({$in})
        ";
}

function getSqlSelectUsersIdsDailyStats(string $start, string $end): string
{
    $fully_registered = getSqlUsersIdsFullyRegistered($start, $end);
    return "SELECT DISTINCT user_id
            FROM external_regulatory_user_balances
            WHERE
                balance_date BETWEEN '{$start}' AND '{$end}'
                AND user_id IN ({$fully_registered})
        ";
}

function getSqlUsersIdsFullyRegistered(string $start, string $end): string
{
    $where = getBaseUserWhereCondition(null, $start, $end);

    return "
            SELECT id
            FROM users
            WHERE {$where}
        ";
}

function filterUsers(string $user_id_column)
{
    return "
        AND {$user_id_column} NOT IN (
            SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'test_account' AND u_s.value = 1
        )
    ";
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

function getFinalBalance(string $start, string $end, string $frequency)
{
    $where_and = filterByUserId('udbs.user_id', $start, $end, $frequency);

    $sql = "
            SELECT
                IFNULL(SUM(udbs.cash_balance + udbs.extra_balance), 0) AS final_balance
            FROM external_regulatory_user_balances AS udbs
            WHERE
                udbs.id = (
                    SELECT id
                    FROM external_regulatory_user_balances AS b
                    WHERE b.user_id = udbs.user_id AND b.balance_date <= DATE('{$end}')
                    ORDER BY balance_date DESC
                    LIMIT 1)
                {$where_and}
        ";

    $res = phive('SQL')->readOnly()->shs()->loadArray($sql) ?? [];
    $total = array_sum(array_filter(array_column($res, 'final_balance')));

    return [
        'total' => $total,
        'items' => $res
    ];
}

function getDeposits(string $start, string $end, string $frequency)
{
    $where_and = filterByUserId('d.user_id', $start, $end, $frequency);

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
            WHERE d.timestamp BETWEEN '{$start}' AND '{$end}'
                {$where_and}
        ";

    $items = phive('SQL')->readOnly()->shs()->loadArray($sql);
    $total = array_sum(array_filter(array_column($items, 'amount')));

    $cards = array_column(array_filter($items, function($item){
        return in_array($item['type'], Phive('Licensed/ES/ES')->getLicSetting('ICS')['payment_method'][ICSConstants::UNDEFINED_CARD_TYPE]);
    }), 'card_hash');


    return [
        'total' => $total,
        'items' => $items,
        'card_hashes' => $cards
    ];
}

function getWithdrawals(string $start, string $end, string $frequency)
{
    $where_and = filterByUserId('pw.user_id', $start, $end, $frequency);

    $sql = "
            SELECT
                   pw.amount * -1 AS amount,
                   pw.timestamp,
                   pw.ip_num AS ip,
                   pw.scheme,
                   pw.payment_method AS display_name,
                   pw.payment_method AS type,
                   pw.scheme AS card_hash
            FROM pending_withdrawals pw
            WHERE pw.timestamp BETWEEN '{$start}' AND '{$end}'
                {$where_and}
        UNION ALL
            SELECT
                ct.amount,
                ct.timestamp,
                '',
                pw.scheme,
                pw.payment_method AS display_name,
                pw.payment_method AS type,
                pw.scheme AS card_hash
            FROM cash_transactions ct
            INNER JOIN pending_withdrawals pw ON ct.parent_id = pw.id
            WHERE
                ct.parent_id != 0
                AND ct.transactiontype = 13
                AND ct.timestamp BETWEEN '{$start}' AND '{$end}'
                {$where_and}
        ";

    $items = phive('SQL')->readOnly()->shs()->loadArray($sql);
    $total = array_sum(array_filter(array_column($items, 'amount')));

    $cards = array_column(array_filter($items, function($item){
        return in_array($item['type'], Phive('Licensed/ES/ES')->getLicSetting('ICS')['payment_method'][ICSConstants::UNDEFINED_CARD_TYPE]);
    }), 'scheme');

    return [
        'total' => $total,
        'items' => $items,
        'card_hashes' => $cards
    ];
}

function getBets(string $start, string $end, string $frequency)
{
    $where_and = filterByUserId('bets.user_id', $start, $end, $frequency);

    $sql = "
            SELECT
                   SUM(bets.amount) * -1 AS amount,
                   mg.tag AS game_tag,
                   mg.id AS game_id
            FROM bets
            INNER JOIN micro_games AS mg ON mg.ext_game_name = bets.game_ref AND mg.device_type_num = bets.device_type
            WHERE bets.created_at BETWEEN '{$start}' AND '{$end}'
                {$where_and}
            GROUP BY mg.id;
        ";

    $items = phive('SQL')->readOnly()->shs()->loadArray($sql);
    phive('SQL')->readOnly()->prependFromArchives($items, $start, $sql, 'bets');

    $where_and = filterByUserId('ugs.user_id', $start, $end, $frequency);

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
            WHERE
                ugs.bets_rollback > 0
                AND ugs.start_time BETWEEN '{$start}' AND '{$rollbacks_period_end}'
                {$where_and}
            GROUP BY ugs.id";
        $rollbacks = phive('SQL')->readOnly()->shs()->loadArray($sql);
        if ($rollbacks) {
            $items = array_merge($items, $rollbacks);
        }
    }
    $total = array_sum(array_filter(array_column($items, 'amount')));

    return [
        'total' => $total,
        'items' => $items
    ];
}

function getRollbackBets(string $start, string $end, string $frequency): array
{
    $where_and = filterByUserId('ugs.user_id', $start, $end, $frequency);

    $sql = "
            SELECT
                ugs.bets_rollback AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM users_game_sessions AS ugs
            LEFT JOIN micro_games AS mg ON mg.ext_game_name = ugs.game_ref AND mg.device_type_num=ugs.device_type_num
            WHERE ugs.start_time BETWEEN '{$start}' AND '{$end}'
                AND ugs.bets_rollback > 0
                {$where_and}
            GROUP BY ugs.id;
        ";

    $items = phive('SQL')->readOnly()->shs()->loadArray($sql);
    $total = array_sum(array_filter(array_column($items, 'amount')));

    return [
        'total' => $total,
        'items' => $items
    ];
}

function getWins(string $start, string $end, string $frequency)
{
    $where_and = filterByUserId('wins.user_id', $start, $end, $frequency);

    $sql = "
            SELECT
                SUM(wins.amount) AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM wins
            INNER JOIN micro_games AS mg ON mg.ext_game_name = wins.game_ref AND mg.device_type_num=wins.device_type
            WHERE wins.created_at BETWEEN '{$start}' AND '{$end}'
                AND bonus_bet <>  " . ICSConstants::FS_WIN_TYPE . "
                {$where_and}
            GROUP BY mg.id;
        ";

    $items = phive('SQL')->readOnly()->shs()->loadArray($sql);
    phive('SQL')->readOnly()->prependFromArchives($items, $start, $sql, 'wins');

    $total = array_sum(array_filter(array_column($items, 'amount')));

    return [
        'total' => $total,
        'items' => $items
    ];
}

function getOtherAffectingBalance(string $start, string $end, string $frequency)
{
    $where_and = filterByUserId('ct.user_id', $start, $end, $frequency);

    //we filter by parent_id here, because type 13 is used for automatic withdrawal reversals, those are already accounted for when checking status=approved,
    //but it's also incorrectly used to manually add money to an account

    $sql = "
        SELECT
            SUM(amount) AS amount,
            description
        FROM cash_transactions ct
        WHERE
            ct.transactiontype IN (9,13,15,34,38,43,50,52,54,61,63,77,85,91)
            AND ct.parent_id = 0
            AND ct.timestamp BETWEEN '{$start}' AND '{$end}'
            {$where_and}
        GROUP BY ct.description
        ";

    $items = phive('SQL')->readOnly()->shs()->loadArray($sql);
    $total = array_sum(array_filter(array_column($items, 'amount')));

    return [
        'total' => $total,
        'items' => $items
    ];
}

function getBonus(string $start, string $end, string $frequency)
{
    $where_and = filterByUserId('ct.user_id', $start, $end, $frequency);
    $where_and_fs = filterByUserId('user_id', $start, $end, $frequency);

    $sql = "
            SELECT
                abs(amount) as amount,
                ct.transactiontype as bonus_type
            FROM cash_transactions AS ct
            LEFT JOIN bonus_types AS bt ON bt.id = ct.bonus_id
            WHERE ct.transactiontype IN (14,31,32,66,69,80,82,84,86,90,94,95,96)
                AND ct.timestamp BETWEEN '{$start}' AND '{$end}'
                {$where_and}
        ";

    $bonuses = phive('SQL')->readOnly()->shs()->loadArray($sql);

    $fs_sql = "
            SELECT
                ABS(amount) as amount,
                " . ICSConstants::FRB_COST . " as bonus_type
            FROM wins
            WHERE
                bonus_bet = " . ICSConstants::FS_WIN_TYPE . "
                AND
                  created_at BETWEEN '{$start}' AND '{$end}'
                  {$where_and_fs}
        ";

    $fs_wins = phive('SQL')->readOnly()->shs()->loadArray($fs_sql);

    foreach ($fs_wins as $row) {
        if( is_array($bonuses) ){
            $bonuses[] = $row;
        }
    }

    $total = array_sum(array_filter(array_column($bonuses, 'amount')));

    return [
        'total' => $total,
        'items' => $bonuses
    ];
}

function getFileName(string $start, string $end, string $frequency, string $table)
{
    $start = substr($start, 0, 10);
    $end = substr($end, 0, 10);
    $file_name = basename(__FILE__, ".php");

    return "{$file_name}_{$table}_{$frequency}_{$start}_{$end}.csv";
}
