<?php

use ES\ICS\Validation\InternalVersionMismatchException;

require_once '/var/www/videoslots/phive/vendor/autoload.php';
include_once '/var/www/videoslots/phive/phive.php';

// Example
// php JUT.php '2023-10-02_00:00:00' '2023-10-02_23:59:59'

$start = str_replace('_', ' ', $argv[1]);
$end = str_replace('_', ' ', $argv[2]);
$lic_settings =  phive('Licensed/ES/ES')->getAllLicSettings();
$internal_version = 5;

validateArguments($start, $end,);
validateInternalVersion($internal_version);
collectDataAndPutIntoCsv($start, $end, $lic_settings);

function collectDataAndPutIntoCsv(string $start, string $end, array $lic_settings): void
{
    $file_name = getFileName($start, $end);
    $data = getData($start, $end, $lic_settings);

    $fp = fopen($file_name, 'w');

    if (!$fp) {
        die('File opening failed');
    }

    fputcsv($fp, ['record_id', 'game_desc', 'group', 'start_time', 'end_time', 'bet_amount', 'rollback_bets', 'wins', 'game_subtype', 'game_id', 'number_of_games_played']);

    foreach ($data as $key => $group) {
        foreach ($group as $record){
            $fields = [
                $record['id'],
                $record['game_desc'],
                $key,
                $record['start_time'],
                $record['end_time'],
                $record['bet_amount'],
                $record['bets_rollback'],
                $record['win_amount'],
                getGameVariant($record['game_tag'], $record['game_id'], $lic_settings),
                $record['game_id'],
                $record['bet_cnt'] === 0 ? 1 : $record['bet_cnt'],
            ];
            fputcsv($fp, $fields);
        }
    }


    echo "Done! File's name is `{$file_name}`" . PHP_EOL;
}

function validateArguments(string $start, string $end): void
{
    if (empty($start) || empty($end) || strlen($start) != 19 || strlen($end) != 19) {
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
    if($internal_version !== \ES\ICS\Reports\v2\JUT::getInternalVersion()){
        throw new InternalVersionMismatchException();
    }
}

function filterUsers(string $user_id_column): string
{
    return "
        AND {$user_id_column} NOT IN (
            SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'test_account' AND u_s.value = 1
        )
    ";
}

function getFileName(string $start, string $end): string
{
    $start = substr($start, 0, 10);
    $end = substr($end, 0, 10);
    $file_name = basename(__FILE__, ".php");

    return "{$file_name}_{$start}_{$end}.csv";
}

function getData(string $start, string $end, array $lic_settings): array
{
    $filter_users = filterUsers('ugs.user_id');

    $sql = "
             SELECT
                ugs.id,
                ugs.user_id,
                ugs.start_time,
                ugs.end_time,
                ugs.game_ref,
                ugs.ip,
                ugs.bet_amount * -1 AS bet_amount,
                ugs.win_amount,
                ugs.session_id,
                ugs.bets_rollback,
                ugs.bet_cnt,
                users_sessions.equipment,
                micro_games.tag AS game_tag,
                micro_games.id AS game_id,
                micro_games.game_name AS game_desc
            FROM users_game_sessions as ugs
            LEFT JOIN users_sessions ON users_sessions.id = ugs.session_id
            LEFT JOIN micro_games ON micro_games.ext_game_name = ugs.game_ref  AND micro_games.device_type_num = ugs.device_type_num
            WHERE ugs.end_time BETWEEN '{$start}' AND '{$end}'
            AND ugs.id NOT IN (
                SELECT id
                FROM
                    users_game_sessions
                WHERE end_time BETWEEN '{$start}' AND '{$end}'
                AND (bet_amount = 0 AND win_amount <> 0)
                AND user_id IN (SELECT id FROM users WHERE country = 'ES')
            )
            AND ugs.user_id IN (SELECT id FROM users WHERE country = 'ES')
            {$filter_users}
            GROUP BY ugs.id;
        ";

    $items = phive('SQL')->readOnly()->shs()->loadArray($sql);
    $start_time = array_column( $items, "start_time" );
    array_multisort( $start_time, SORT_ASC, $items );

    return array_reduce($items, function ($carry, $session) use ($lic_settings) {
        $game_type = getGameType($session['game_tag'], $session['game_id'], $lic_settings);
        if (empty($carry[$game_type])) {
            $carry[$game_type] = [];
        }

        $carry[$game_type][] = $session;

        return $carry;
    }, []);
}

function getGameType($game_tag, $game_id = null, array $lic_settings): string
{
    $game_sub_tags = getGameSubTags()[$game_id] ?? [];
    $gambling_license = phive('MicroGames')->getExpandedGameCategoryByTagAndSubtag($game_tag ?? "", $game_sub_tags);

    return $lic_settings['ICS']['game_type'][$gambling_license] ?? 'AZA';
}

function getGameSubTags()
{
    static $data;
    if(is_null($data)) {
        $game_tag_con = phive('SQL')->readOnly()->loadArray(
            "SELECT game_id, alias FROM game_tag_con gtc INNER JOIN game_tags gt ON gtc.tag_id=gt.id"
        );

        $data = array_reduce($game_tag_con, function ($carry, $el) {
            if (empty($carry[$el['game_id']])) {
                $carry[$el['game_id']] = [];
            }
            $carry[$el['game_id']][] = $el['alias'];

            return $carry;
        }, []);
    }

    return $data;
}

function getGameVariant($game_tag, $game_id, array $lic_settings): string
{
    $game_sub_tags = getGameSubTags();
    $game_type = getGameType($game_tag, $game_id, $lic_settings);
    $mapping = [
        'RLT' => [
            'default' => 'Francesa',
            '_roulette-french.cgames' => 'Francesa',
            '_roulette-american.cgames' => 'Americana'
        ],
        'BLJ' => [
            'default' => 'CL',
            '_blackjack-classic.cgames' => 'CL',
            '_blackjack-american.cgames' => 'AM',
            '_blackjack-ponton.cgames' => 'PO',
            '_blackjack-surrender.cgames' => 'SU',
            '_blackjack-super21.cgames' => '21'
        ]
    ];
    // Current game type doesn't support variant
    if(!isset($mapping[$game_type])) {
        return false;
    }
    foreach($game_sub_tags as $game_sub_tag) {
        if(array_key_exists($game_sub_tag, $mapping[$game_type])) {
            return $mapping[$game_type][$game_sub_tag];
        }
    }
    // Return default value to prevent generating XML not compliant with XSD schema
    return $mapping[$game_type]['default'];
}
