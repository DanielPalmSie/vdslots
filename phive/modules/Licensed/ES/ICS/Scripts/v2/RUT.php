<?php

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Validation\InternalVersionMismatchException;

require_once '/var/www/videoslots/phive/vendor/autoload.php';
include_once '/var/www/videoslots/phive/phive.php';

// Example
// php RUT.php '2023-04-30 00:00:00' '2023-04-30 23:59:59' Daily
// php RUT.php '2023-10-01 00:00:00' '2023-10-31 23:59:59' Monthly

$start = $argv[1];
$end = $argv[2];
$frequency = ucfirst($argv[3]);
$internal_version = 7;

validateArguments($start, $end, $frequency);
validateInternalVersion($internal_version);
collectDataAndPutIntoCsv($start, $end, $frequency);

function collectDataAndPutIntoCsv(string $start, string $end, string $frequency): void
{
    $file_name = getFileName($start, $end, $frequency);
    $fp = fopen($file_name, 'w');
    if (!$fp) {
        die('File opening failed');
    }
    fputcsv($fp, ['users_total', 'users_new', 'users_removed', 'users_active', 'external_status', 'internal_status', 'users_count']);
    $players_per_status = getPlayersPerStatus($start, $end, $frequency);
    foreach ($players_per_status as $i => $player) {
        if ($i === 0){
            $fields = [
                getUsersCount($start, $end, $frequency),
                getNewUsersCount($start, $end),
                getRemovedUsersCount(),
                getActiveUsersCount($start, $end),
                $player['external_status'],
                $player['internal_status'],
                $player['users_count']
            ];
        } else {
            $fields = [
                '',
                '',
                '',
                '',
                $player['external_status'],
                $player['internal_status'],
                $player['users_count']
            ];
        }
        fputcsv($fp, $fields);
    }

    echo "Done! File's name is `{$file_name}`" . PHP_EOL;
}

function validateArguments(string $start, string $end, string $frequency): void
{
    if (empty($start) || empty($end)) {
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
    if($internal_version !== \ES\ICS\Reports\v2\RUT::getInternalVersion()){
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

function getFileName(string $start, string $end, string $frequency): string
{
    $start = substr($start, 0, 10);
    $end = substr($end, 0, 10);
    $file_name = basename(__FILE__, ".php");

    return "{$file_name}_{$frequency}_{$start}_{$end}.csv";
}

function getTotalUsersCount(string $start, string $end): int
{
    $where = getBaseUserWhereCondition(null, $start, $end);
    $sql = "
            SELECT
                count(users.id) as total_users
            FROM
                users
            WHERE
                {$where}
        ";

    return phive('SQL')->readOnly()->shs('sum')->loadCol($sql, 'total_users')[0] ?? 0;
}

function getUsersWithMovements(string $start, string $end): array
{
    $where = getBaseUserWhereCondition('>=', $start, $end);
    $where1 = getBaseUserWhereCondition('<=', $start, $end);

    $sql = "
            SELECT a1.target, a1.descr, a1.created_at
            FROM (
            SELECT
                users.id
            FROM
                users
            WHERE
                {$where}
            UNION
            SELECT
                users.id
            FROM
                users
            WHERE
                {$where1}
                AND users.id IN (
                    SELECT target
                    FROM actions
                    WHERE tag in ('deposit-rgl-applied', 'profile-update-success', 'profile-update-by-admin', 'user_status_changed')
                    AND created_at >= '{$start}'
                    AND created_at <= '{$end}'
                )) as users_table
            JOIN actions a1 ON
                a1.target = users_table.id
            WHERE
                a1.id = (
                SELECT
                    a2.id
                FROM
                    actions AS a2
                WHERE
                    a2.tag = 'user_status_changed'
                    AND a2.created_at <= '{$end}'
                    AND a2.target = a1.target
                ORDER BY
                    a2.created_at DESC,
                    a2.id DESC
                LIMIT 1
                        )
        ";

    return phive('SQL')->readOnly()->shs()->loadArray($sql);
}

function getUsersCount(string $start, string $end, string $frequency): int
{
    if ($frequency === ICSConstants::DAILY_FREQUENCY) {
        return count(getUsersWithMovements($start, $end));
    } else {
        return getTotalUsersCount($start, $end);
    }
}



function getActiveUsersCount(string $start, string $end): int
{
    $filter_users = filterUsers('users_daily_stats.user_id');
    $sql = "
            SELECT
                count(active_users.user_id) as total_active_users
            FROM (
                SELECT
                    user_id,
                    SUM(bets + wins + deposits + withdrawals) as total
                FROM users_daily_stats
                    WHERE
                        country = 'ES'
                        AND date >= '{$start}'
                        AND date <= '{$end}'
                        AND users_daily_stats.user_id IN (
                            SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'registration_end_date' AND u_s.value <= '{$end}'
                        )
                        {$filter_users}
                GROUP BY user_id
                HAVING total >= 100
            ) as active_users;
        ";

    return phive('SQL')->readOnly()->shs('sum')->loadCol($sql, 'total_active_users')[0] ?? 0;
}

function getNewUsersCount(string $start, string $end): int
{
    $where = getBaseUserWhereCondition('>=', $start, $end);
    $sql = "
            SELECT
                count(users.id) as new_users
            FROM
                users
            WHERE
                {$where}
        ";

    return phive('SQL')->readOnly()->shs('sum')->loadCol($sql, 'new_users')[0] ?? 0;
}

function getRemovedUsersCount(): int
{
    // It looks like we should report as NumberRemoved  - always 0
    // Since we don't really support the removal as per documentation description (As discussed with @ricardo)
    // And, in the same report, they require to have the list of players by status in the report
    // (where CANCELLED is one of the player status that they expect)
    return 0;
}

function playersToBeClassifiedByStatus(string $start, string $end, string $frequency): array
{
    if ($frequency === ICSConstants::DAILY_FREQUENCY) {
        return getUsersWithMovements($start, $end);
    } else {
        $filter_users = filterUsers('id');
        $sql = "
            SELECT a1.target, a1.descr, a1.created_at
            FROM actions AS a1
            WHERE a1.id = (
                SELECT a2.id FROM actions AS a2 WHERE a2.tag = 'user_status_changed'
                AND a2.created_at <= '{$end}'
                AND a2.target = a1.target
                ORDER BY a2.created_at DESC, a2.id DESC LIMIT 1
            )
            AND a1.target IN (
                SELECT id FROM users WHERE country = 'ES'
                AND id NOT IN (
                    SELECT user_id FROM users_settings WHERE users_settings.setting = 'registration_in_progress'
                    AND users_settings.value >= 1
                )
                AND id IN (
                    SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'registration_end_date'
                    AND u_s.value <= '{$end}'
                )
                {$filter_users}
            );
    ";

        return phive('SQL')->readOnly()->shs()->loadArray($sql);
    }
}

function getPlayersPerStatusTotal(string $start, string $end, string $frequency): array
{
    if ($frequency === ICSConstants::DAILY_FREQUENCY) {
        return playersToBeClassifiedByStatus($start, $end, $frequency);
    }

    $players_per_status = playersToBeClassifiedByStatus($start, $end, $frequency);

    return playersToBeClassifiedByStatusArchives($end, $players_per_status);
}

function playersToBeClassifiedByStatusArchives(string $end, array $players_per_status): array
{
    $total_users = getUsersArray($end);
    $archive_users = array_diff($total_users, array_column($players_per_status, 'target'));
    $imploded_users = implode(',', $archive_users);

    $sql = "
            SELECT
                a1.target,
                a1.descr,
                a1.created_at
            FROM
                actions AS a1
            WHERE
                a1.id = (
                SELECT
                    a2.id
                FROM
                    actions AS a2
                WHERE
                    a2.tag = 'user_status_changed'
                    AND a2.created_at <= '{$end}'
                    AND a2.target = a1.target
                ORDER BY
                    a2.created_at DESC,
                    a2.id DESC
                LIMIT 1)
                AND a1.target IN ($imploded_users)";

    phive('SQL')->prependFromArchives($players_per_status, $end, $sql, 'actions');

    return $players_per_status;
}

function getUsersArray(string $end): array
{
    $sql = "
            SELECT
                id
            FROM
                users
            WHERE
                country = 'ES'
                AND id NOT IN (
                SELECT
                    user_id
                FROM
                    users_settings
                WHERE
                    users_settings.setting = 'registration_in_progress'
                    AND users_settings.value >= 1
            )
                AND id IN (
                SELECT
                    user_id
                FROM
                    users_settings AS u_s
                WHERE
                    u_s.setting = 'registration_end_date'
                    AND u_s.value <= '{$end}'
            )
            AND id NOT IN (
                SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'test_account' AND u_s.value = 1
            )
        ";

    $users = phive('SQL')->shs()->loadArray($sql);

    return array_unique(array_column($users,'id'));
}

function getPlayersPerStatus(string $start, string $end, string $frequency): array
{
    $status = getPlayersPerStatusTotal($start, $end, $frequency);
    if ($frequency === ICSConstants::MONTHLY_FREQUENCY) {
        mergeStatusChanges($status, getCsvStatusesArray($end));
    }

    $status_data = array_reduce(
        $status,
        function ($carry, $status_change) {
            $formatted = phive('Licensed')->getLicense('ES')->formatUserStatusChangeAction($status_change);

            $key = "{$formatted['external_status_to']}-{$formatted['status_to']}";

            if (isset($carry[$key])) {
                $carry[$key]['users_count'] = $carry[$key]['users_count'] + 1;
            } else {
                $carry[$key] =  [
                    'external_status' => $formatted['external_status_to'],
                    'internal_status' => $formatted['status_to'],
                    'users_count' => 1
                ];
            }

            return $carry;
        },
        []
    );

    return array_values($status_data);
}

function getCsvStatusesArray($date)
{
    $csv_file_path = dirname(__FILE__) . '/../Csv/statuses.csv';
    $latest_statuses = [];
    $previous_statuses = [];

    if (($handle = fopen($csv_file_path, 'r')) !== false) {
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $user_id = $row[0];
            $status = $row[1];
            $created_at = $row[2];

            if (strtotime($created_at) <= strtotime($date)) {
                $previous_status = $previous_statuses[$user_id] ?? 'NA';

                if (!isset($latest_statuses[$user_id]) || strtotime($created_at) > strtotime($latest_statuses[$user_id]['created_at'])) {
                    $desc = "[{$previous_status}-{$status}] Status changed from {$previous_status} to {$status}";
                    $latest_statuses[$user_id] = [
                        'target' => $user_id,
                        'descr' => $desc,
                        'created_at' => $created_at
                    ];
                }
                $previous_statuses[$user_id] = $status;
            }
        }
        fclose($handle);
    }

    return array_values($latest_statuses);
}

function mergeStatusChanges($db_status_changes, $csv_status_changes)
{
    $mergedArray = array_merge($db_status_changes, $csv_status_changes);

    $latest_statuses = [];
    foreach ($mergedArray as $status) {
        $target = $status['target'];

        if (!isset($latest_statuses[$target]) || strtotime($status['created_at']) > strtotime($latest_statuses[$target]['created_at'])) {
            $latest_statuses[$target] = $status;
        }
    }

    return array_values($latest_statuses);
}
