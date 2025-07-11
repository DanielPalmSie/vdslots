<?php

use Carbon\Carbon;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Validation\InternalVersionMismatchException;

require_once '/var/www/videoslots/phive/vendor/autoload.php';
include_once '/var/www/videoslots/phive/phive.php';

// Example
// php OPT.php '2022-02-01 00:00:00' '2022-02-28 23:59:59'

// OPT report can be run only with monthly frequency, that's why there is no third argument (frequency)

$start = $argv[1];
$end = $argv[2];
$internal_version = 4;

validateArguments($start, $end);
validateInternalVersion($internal_version);

$es_lic_settings = phive('Licensed/ES/ES')->getAllLicSettings();
$expanded_game_categories = phive('MicroGames')->getSetting('expanded_game_categories', []);
$game_sub_tags = getGameSubTags();

collectDataAndPutIntoCsv($expanded_game_categories, $es_lic_settings, $game_sub_tags, getBets($start, $end), getFileName('Bets', $start, $end));

collectDataAndPutIntoCsv($expanded_game_categories, $es_lic_settings, $game_sub_tags, getWins($start, $end), getFileName('Wins', $start, $end));

collectDataAndPutIntoCsv($expanded_game_categories, $es_lic_settings, $game_sub_tags, getRollbackBets($start, $end), getFileName('Rollback_Bets', $start, $end));

function collectDataAndPutIntoCsv(array $expanded_game_categories, array $es_lic_settings, array $game_sub_tags, array $data, string $file_name)
{
    $result = [];

    foreach ($data as $datum) {
        $game_sub_tags_local = $game_sub_tags[$datum['game_id']] ?? [];
        $game = getExpandedGameCategoryByTagAndSubtag($expanded_game_categories, $game_sub_tags_local, $datum['game_tag'] ?? '');

        $game_license = $es_lic_settings['ICS']['game_type'][$game] ?? 'AZA';
        $datum['game_license'] = $game_license;

        $result[$game_license][] = $datum;
    }

    $total = [];
    foreach ($result as $game_license => $datum) {
        $file_name_with_game_lic = str_replace('#GAME_TYPE#', $game_license, $file_name);

        $fp = fopen($file_name_with_game_lic, 'w');

        fputcsv($fp, array_keys($datum[0]));

        foreach($datum as $row){
            fputcsv($fp, $row);
        }

        $total[$game_license . '_' . explode('_', $file_name)[0]] = array_sum(array_filter(array_column($datum, 'amount')));

        echo "File has been generated! File's name is `{$file_name_with_game_lic}`" . PHP_EOL;
    }
}

function getFileName(string $name, string $start, string $end)
{
    $start = substr($start, 0, 10);
    $end = substr($end, 0, 10);
    $file_name = basename(__FILE__, ".php");

    return "{$file_name}_#GAME_TYPE#_{$name}_{$start}_{$end}.csv";
}

function getBets(string $start, string $end)
{
    $where_in = filterByUserId('bets.user_id', $start, $end);

    $sql = "
            SELECT
                SUM(bets.amount) AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM bets
            INNER JOIN micro_games AS mg ON mg.ext_game_name = bets.game_ref AND mg.device_type_num = bets.device_type
            WHERE bets.created_at BETWEEN '{$start}' AND '{$end}'
                {$where_in}
            GROUP BY mg.id;
        ";

    $items = phive('SQL')->readOnly()->shs()->loadArray($sql);

    $where_in = filterByUserId('ugs.user_id', $start, $end);

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
                ugs.bets_rollback AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM users_game_sessions AS ugs
            LEFT JOIN micro_games AS mg ON mg.ext_game_name = ugs.game_ref AND mg.device_type_num=ugs.device_type_num
            WHERE
                ugs.bets_rollback > 0
                AND ugs.start_time BETWEEN '$start' AND '$rollbacks_period_end'
                {$where_in}
            GROUP BY ugs.id";
        $rollbacks = phive('SQL')->readOnly()->shs()->loadArray($sql);
        if ($rollbacks) {
            $items = array_merge($items, $rollbacks);
        }
    }

    return $items;
}

function getWins($start, $end)
{
    $where_in = filterByUserId('wins.user_id', $start, $end);

    $sql = "
            SELECT
                SUM(wins.amount) AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM wins
            INNER JOIN micro_games AS mg ON mg.ext_game_name = wins.game_ref AND mg.device_type_num=wins.device_type
            WHERE wins.created_at BETWEEN '{$start}' AND '{$end}'
            AND bonus_bet <> " . ICSConstants::FS_WIN_TYPE . "
                {$where_in}
            GROUP BY mg.id
        ";

    return phive('SQL')->readOnly()->shs()->loadArray($sql);
}

function getRollbackBets($start, $end)
{
    $where_in = filterUsers('ugs.user_id', $start, $end);

    $sql = "
            SELECT
                ugs.bets_rollback AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM users_game_sessions AS ugs
            LEFT JOIN micro_games AS mg ON mg.ext_game_name = ugs.game_ref
            WHERE ugs.end_time BETWEEN '{$start}'
                AND '{$end}'
                AND ugs.bets_rollback > 0
                AND ugs.user_id IN (SELECT id FROM users WHERE country = 'ES')
                {$where_in}
            GROUP BY ugs.id;
        ";

    return phive('SQL')->readOnly()->shs()->loadArray($sql);
}

function getExpandedGameCategoryByTagAndSubtag($expanded_game_categories, array $sub_tags = [], string $main_tag = '')
{
    if (empty($main_tag) && empty($sub_tags)) {
        return 'slots';
    }

    foreach ($expanded_game_categories as $expanded_game_category => $details) {
        if (in_array($main_tag, $details['direct_match'], true)) {
            return $expanded_game_category;
        }
        if (!empty($details['combined_match']['main_tags']) && in_array($main_tag, $details['combined_match']['main_tags'], true)) {
            foreach ($sub_tags as $sub_tag) {
                if (in_array($sub_tag, $details['combined_match']['sub_tags'], true)) {
                    return $expanded_game_category;
                }
            }
        }
    }

    // TODO TBC if we can default to slots, being the most generic category (if yes remove initial IF condition)
    return 'slots';
}

function getGameSubTags()
{
    $game_tag_con = phive('SQL')->readOnly()->loadArray("SELECT game_id, alias FROM game_tag_con gtc INNER JOIN game_tags gt ON gtc.tag_id=gt.id");

    return array_reduce($game_tag_con, function ($carry, $el) {
        if (empty($carry[$el['game_id']])) {
            $carry[$el['game_id']] = [];
        }
        $carry[$el['game_id']][] = $el['alias'];

        return $carry;
    }, []);
}

function validateArguments(string $start, string $end)
{
    if (empty($start) || empty($end) || strlen($start) != 19 || strlen($end) != 19 || $start === $end) {
        throw new Exception('Wrong `start` & `end`');
    }

    if ((date('Y-m-d H:i:s', strtotime($start)) !== $start) || (date('Y-m-d H:i:s', strtotime($end)) !== $end)) {
        throw new Exception('Wrong `start` & `end`');
    }
}

/**
 * @param $internal_version
 * @throws Exception
 */
function validateInternalVersion ($internal_version){
    if($internal_version !== \ES\ICS\Reports\v2\OPT::getInternalVersion()){
        throw new InternalVersionMismatchException();
    }
}

function filterByUserId(string $user_id_column, string $start, string $end): string
{
    $in = getSqlUsersIdsFullyRegistered($start, $end);

    return "
            AND {$user_id_column} IN ({$in})
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

function filterUsers(string $user_id_column)
{
    return "
        AND {$user_id_column} NOT IN (
            SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'test_account' AND u_s.value = 1
        )
    ";
}
