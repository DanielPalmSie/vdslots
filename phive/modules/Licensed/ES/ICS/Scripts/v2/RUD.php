<?php

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Validation\InternalVersionMismatchException;

require_once '/var/www/videoslots/phive/vendor/autoload.php';
include_once '/var/www/videoslots/phive/phive.php';

// Example
// php RUD.php '2022-02-01 00:00:00' '2022-02-01 23:59:59' Daily
// php RUD.php '2022-02-01 00:00:00' '2022-02-28 23:59:59' Monthly

$lic_settings =  phive('Licensed/ES/ES')->getAllLicSettings();

$start = $argv[1];
$end = $argv[2];
$frequency = ucfirst($argv[3]);
$internal_version = 9;

validateArguments($start, $end, $frequency);
validateInternalVersion($internal_version);
collectDataAndPutIntoCsv($start, $end, $frequency, $lic_settings);


function collectDataAndPutIntoCsv(string $start, string $end, string $frequency, array $lic_settings): void
{
    $file_name = getFileName($start, $end, $frequency);
    $fp = fopen($file_name, 'w');
    if (!$fp) {
        die('File opening failed');
    }
    fputcsv($fp, [
        'user_id',
        'registration_end_date',
        'fiscal_region',
        'residence_country',
        'nid',
        'dob',
        'username',
        'firstname',
        'lastname',
        'lastname_second',
        'email',
        'sex',
        'address',
        'city',
        'zipcode',
        'country',
        'mobile',
        'deposit_limit_day',
        'deposit_limit_week',
        'deposit_limit_month',
        'external_status',
        'internal_status',
        'status_created_at'
    ]);
    $user_ids = getUserIds($start, $end, $frequency);
    $users_additional_data = getUsersAdditionalData($user_ids, $start, $end);
    $users_data = mergeUsersData($user_ids, $users_additional_data);

    foreach ($users_data as $user) {
        $deposit_limits = getFormattedDepositLimits($user['deposit_limits'], $lic_settings);
        $status = getUserStatus($user['status_changes']);
        if ($frequency === ICSConstants::MONTHLY_FREQUENCY) {
            $csv_statuses = getCsvStatusesArray();
            $status = mergeStatusChanges($status, $csv_statuses[$user['id']]);
        }

        foreach ($status as $i => $sc) {

            if ($i === 0) {
                $fields = [
                    $user['id'],
                    $user['registration_end_date'],
                    $user['fiscal_region'],
                    $user['residence_country'],
                    $user['nid'],
                    $user['dob'],
                    $user['username'],
                    $user['firstname'],
                    $user['lastname'],
                    $user['lastname_second'],
                    $user['email'],
                    $user['sex'],
                    $user['address'],
                    $user['city'],
                    $user['zipcode'],
                    $user['residence_country'],
                    $user['mobile'],
                    $deposit_limits['day'],
                    $deposit_limits['week'],
                    $deposit_limits['month'],
                    $sc['external_status'],
                    $sc['internal_status'],
                    $sc['created_at']
                ];
            } else {
                $fields = [];
                for ($j = 0; $j < 20; $j++) {
                    $fields[$j] = '';
                }
                $fields[] = $sc['external_status'];
                $fields[] = $sc['internal_status'];
                $fields[] = $sc['created_at'];
            }
            fputcsv($fp, $fields);
        }

    }

    echo "Done! File's name is `{$file_name}`" . PHP_EOL;
}

function getUserIds(string $start, string $end, string $frequency): array
{
    return $frequency === ICSConstants::DAILY_FREQUENCY
        ? array_replace(getNewUsers($start, $end), getModifiedUsers($start, $end))
        : getAllUsersIds($start, $end);
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
    if($internal_version !== \ES\ICS\Reports\v2\RUD::getInternalVersion()){
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

function getUsersToReport(string $frequency, string $start, string $end): array
{
    if ($frequency === ICSConstants::DAILY_FREQUENCY) {
        return array_replace(getNewUsers(), getModifiedUsers($start, $end));
    } else {
        return getAllUsersIds();
    }
}

function getNewUsers(string $start, string $end): array
{
    $condition = getBaseUserWhereCondition('>=', $start, $end);
    $sql = "
            SELECT
                users.id
            FROM
                users
            WHERE
                {$condition}
        ";

    return phive('SQL')->readOnly()->shs()->load1DArr($sql, 'id', 'id');
}

function getModifiedUsers(string $start, string $end): array
{
    $sql_module = Phive('SQL');
    $condition = getBaseUserWhereCondition('<=', $start, $end);
    $base_users_sql = "
        SELECT
            users.id
        FROM
            users
        WHERE
            {$condition}
    ";
    $tags = "'deposit-rgl-applied', 'profile-update-success', 'profile-update-by-admin', 'user_status_changed'";
    $sql = "
        {$base_users_sql}
          AND users.id IN (
            SELECT target
            FROM actions
            WHERE tag IN ({$tags})
              AND created_at >= '{$start}'
              AND created_at <= '{$end}'
        );
    ";
    $result = $sql_module->readOnly()->shs()->load1DArr($sql, 'id', 'id');

    $users_registered_in_period = $sql_module->readOnly()->shs()->load1DArr($base_users_sql, 'id', 'id');
    $sql_archive_user_ids = $sql_module->makeIn(array_keys($users_registered_in_period));
    $sql_archive = "
        SELECT target AS id
        FROM actions
        WHERE tag in ({$tags})
          AND created_at >= '{$start}'
          AND created_at <= '{$end}'
          AND target IN ({$sql_archive_user_ids})
    ";
    $result_from_archive = [];
    $sql_module->prependFromArchives($result_from_archive, $start, $sql_archive, 'actions');

    foreach ($result_from_archive as $r) {
        $result[$r['id']] = $r['id'];
    }

    return $result;
}

function getAllUsersIds(string $start, string $end): array
{
    $condition = getBaseUserWhereCondition(null, $start, $end);
    $sql = "
            SELECT
                users.id
            FROM
                users
            WHERE
                {$condition}
        ";

    return phive('SQL')->readOnly()->shs()->load1DArr($sql, 'id', 'id');
}

function getUsersAdditionalData(array $users_ids, string $start, string $end): array
{
    $users_ids_in = phive('SQL')->makeIn($users_ids);

    $users_settings = [];
    $users_statuses = [];
    $users_deposit_limits = [];
    $users_changes_stats = [];

    if ($users_ids_in) {
        $sql_statuses = getQueryUsersStatuses($users_ids_in, $end);
        $sql_settings = getQueryUsersSettings($users_ids_in, $end);
        $sql_deposit_limits = getQueryUsersDepositLimits($users_ids_in, $end);
        $sql_users_changes_stats = getQueryUsersChangeStats($users_ids_in, $end);
        $users_statuses = phive('SQL')->readOnly()->shs()->loadArray($sql_statuses);
        $users_settings = phive('SQL')->readOnly()->shs()->loadArray($sql_settings);
        $users_deposit_limits = phive('SQL')->readOnly()->shs()->loadArray($sql_deposit_limits);
        $users_changes_stats = phive('SQL')->readOnly()->shs()->loadArray($sql_users_changes_stats);
    }

    return [
        'users_settings' => $users_settings,
        'users_statuses' => $users_statuses,
        'users_deposit_limits' => $users_deposit_limits,
        'users_change_stats' => $users_changes_stats,
    ];
}

function mergeUsersData(array $users_ids, array $users_data): array
{
    $field_name_dni_verification = getSettingNameDniVerification();
    $field_name_dni_verification_date = getSettingNameDniVerificationDate();

    $users = array_reduce(
        $users_ids,
        function ($carry, $user_id) use ($field_name_dni_verification, $field_name_dni_verification_date) {
            if (!isset($carry[$user_id])) {
                $carry[$user_id] = [
                    'id' => $user_id,
                    'username' => '',
                    'firstname' => '',
                    'dob' => '',
                    'nid' => '',
                    'address' => '',
                    'city' => '',
                    'zipcode' => '',
                    'country' => '',
                    'email' => '',
                    'sex' => '',
                    'mobile' => '',
                    'registration_end_date' => '',
                    'status_changes' => [],
                    'deposit_limits' => '',
                    'first_verification_date' => '',
                    $field_name_dni_verification => '',
                    $field_name_dni_verification_date => '',
                ];
            }

            return $carry;
        }
    );

    foreach ($users_data['users_settings'] as $settings) {
        $user_id = $settings['user_id'];

        if (isset($users[$user_id])) {
            $users[$user_id][$settings['setting']] = $settings['value'];
            $users[$user_id]["{$settings['setting']}_set_at"] = $settings['setting_at'];
        }
    }

    foreach ($users_data['users_statuses'] as $action) {
        $user_id = $action['user_id'];

        if (isset($users[$user_id])) {
            $users[$user_id]['status_changes'][] = ['created_at' => $action['created_at'], 'descr' => $action['descr']];
        }
    }

    foreach ($users_data['users_deposit_limits'] as $deposit_limits) {
        $user_id = $deposit_limits['user_id'];

        if (isset($users[$user_id])) {
            $users[$user_id]['deposit_limits'] = $deposit_limits['deposit_limits'];
        }
    }

    foreach ($users_data['users_change_stats'] as $users_stat) {
        $user_id = $users_stat['user_id'];

        if (isset($users[$user_id])) {
            $users[$user_id][$users_stat['type']] = $users_stat['post_value'];
        }
    }

    return $users;
}

function getQueryUsersSettings(string $users_ids_in, string $end): string
{
    $field_name_dni_verification = getSettingNameDniVerification();
    $field_name_dni_verification_date = getSettingNameDniVerificationDate();

    return "
            SELECT
                user_id,
                setting,
                value,
                created_at AS setting_at
            FROM
                users_settings
            WHERE user_id IN ({$users_ids_in})
                AND setting IN (
                    'lastname', 'lastname_second', 'fiscal_region', 'residence_country', 'nationality', 'registration_end_date',
                    '{$field_name_dni_verification}', '{$field_name_dni_verification_date}', 'first_verification_date'
                )
                AND created_at <= '{$end}'
        ";
}

function getQueryUsersStatuses(string $users_ids_in, string $end): string
{
    return "
            SELECT
                target AS user_id,
                descr,
                created_at
            FROM
                actions
            WHERE target IN ({$users_ids_in})
                AND tag = 'user_status_changed'
                AND created_at <= '{$end}'
            ORDER BY created_at, id
        ";
}

function getQueryUsersDepositLimits(string $users_ids_in, string $end): string
{
    return "
            SELECT
                target AS user_id,
                descr AS deposit_limits
            FROM actions as a1
            WHERE a1.target IN ({$users_ids_in})
            AND a1.id = (
                SELECT a2.id
                FROM actions as a2
                WHERE a2.tag = 'deposit-rgl-current'
                    AND a2.target = a1.target
                    AND a2.target IN ({$users_ids_in})
                    AND a2.created_at <= '{$end}'
                ORDER BY a2.created_at DESC, a2.id DESC LIMIT 1
            )
    ";
}

function getQueryUsersChangeStats(string $users_ids_in, string $end): string
{
    return "
            SELECT user_id, type, post_value
            FROM users_changes_stats
            WHERE id IN (
                    SELECT max(id) FROM users_changes_stats
                    WHERE user_id IN ({$users_ids_in})
                        AND type IN ('username', 'firstname', 'dob', 'nid', 'address', 'city', 'zipcode', 'country', 'email', 'sex', 'mobile')
                        AND created_at <= '{$end}'
                    GROUP BY type, user_id
                )
        ";
}

function getSettingNameDniVerification(): string
{
    return phive('Licensed/ES/ES')->getSettingNameDniVerification();
}

function getSettingNameDniVerificationDate(): string
{
    return phive('Licensed/ES/ES')->getSettingNameDniVerificationDate();
}

function getFormattedDepositLimits(string $deposit_limits, array $lic_settings): array
{
    if ($deposit_limits) {
        $currency = ICSConstants::CURRENCY;
        preg_match(
            "/Limits: (?<day>(.*?)) $currency,(?<week>(.*?)) $currency,(?<month>(.*?)) $currency/",
            $deposit_limits,
            $matches
        );

        if (empty($matches)){
            preg_match(
                '/Limits: (?P<day>-?[\d]+),(?P<week>-?[\d]+),(?P<month>-?[\d]+)/',
                $deposit_limits,
                $matches
            );
        }
    }


    return [
        'day' => !empty($matches['day']) ? (int)preg_replace('/[,.]/i', '', $matches['day']) : (int)$lic_settings['deposit_limit']['highest_allowed_limit']['day'],
        'week' => !empty($matches['week']) ? (int)preg_replace('/[,.]/i', '', $matches['week']) : (int)$lic_settings['deposit_limit']['highest_allowed_limit']['week'],
        'month' => !empty($matches['month']) ? (int)preg_replace('/[,.]/i', '', $matches['month']) : (int)$lic_settings['deposit_limit']['highest_allowed_limit']['month'],
    ];
}

function getUserStatus(array $status_changes): array
{
    usort(
        $status_changes,
        function ($a, $b) {
            return strtotime($b["created_at"]) - strtotime($a["created_at"]);
        }
    );
    return array_map(
        function ($status_change) {
            $formatted = phive('Licensed/ES/ES')->formatUserStatusChangeAction($status_change);

            return [
                // External status see 3.5.7.2 Player's Status
                'external_status' => $formatted["external_status_to"],
                // Internal status need to map to a player status
                'internal_status' => $formatted["status_to"],
                // Date of the status change
                'created_at' => phive()->fDate($formatted['created_at'], ICSConstants::DATETIME_TO_GMT_FORMAT),
            ];
        },
        $status_changes
    );
}

function getCsvStatusesArray()
{
    $csv_file_path = dirname(__FILE__) . '/../Csv/statuses.csv';
    $csv_statuses = [];

    if (($handle = fopen($csv_file_path, 'r')) !== false) {
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $user_id = $row[0];
            $status = $row[1];
            $date = $row[2];

            if (!isset($csv_statuses[$user_id])) {
                $csv_statuses[$user_id] = [];
            }

            $csv_statuses[$user_id][] = ['created_at' => date('YmdHisO', strtotime($date)), 'internal_status' => $status, 'external_status' => phive('Licensed/ES/ES')->getExternalUserStatusMapping($status)];
        }

        fclose($handle);
    }

    return $csv_statuses;
}

function mergeStatusChanges(array $db_status_changes, array $csv_statuses): array
{
    $merged_status_changes = array_merge($db_status_changes, $csv_statuses);

    $status_history = [];
    $unique_keys = [];
    foreach ($merged_status_changes as $item) {
        $unique_key = $item['created_at'] . '-' . $item['descr'];

        if (!in_array($unique_key, $unique_keys)) {
            $unique_keys[] = $unique_key;
            $status_history[] = $item;
        }
    }

    usort($status_history, function ($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });

    return $status_history;
}
