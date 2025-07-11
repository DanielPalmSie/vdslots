<?php

namespace ES\ICS\Reports\v2;

use ES\ICS\Constants\ICSConstants;
use Exception;
use ES\ICS\Reports\BaseReport;

class RUT extends BaseReport
{
    public const TYPE = 'RU';
    public const SUBTYPE = 'RUT';
    public const NAME = 'RegistroRUT';
    protected static int $internal_version = 7;

    private ?array $users_with_movements = null;

    /**
     * Return total users data
     *
     * @return array
     * @throws Exception
     */
    protected function getGroupedRecords(): array
    {
        return [
            [
                [
                    'users_total' => $this->getUsersCount(),
                    'users_new' => $this->getNewUsersCount(),
                    'users_removed' => $this->getRemovedUsersCount(),
                    'users_active' => $this->getActiveUsersCount(),
                    'users_by_status' => $this->getPlayersPerStatus(),
                ]
            ]
        ];
    }

    /**
     * Map operator data to required Registro structure
     *
     * @param array $records
     * @param string $group RU* - batch number, Other - game type
     * @return array
     * @throws Exception
     */
    protected function setupRecords(array $records, string $group = ''): array
    {
        $total = count($records);

        return array_map(function($record, $index) use ($total) {
            $data = [
                '_attributes' => ['xsi:type' => self::NAME],
                // Record Header
                'Cabecera' => $this->getRecordHeader($index + 1, $total),
                // Frequency
                'Periodicidad' =>  ICSConstants::FREQUENCY_VALUES[$this->getFrequency()],
                // Period
                'Periodo' => $this->getPeriod($this->getFrequency()),
                // Total number of players in that period
                'NumeroJugadores' => $record['users_total'],
                // New players in that period
                'NumeroAltas' => $record['users_new'],
                // Closed account players in that period
                'NumeroBajas' => $record['users_removed'],
                // Players active more than 1e transactions
                'NumeroActividad' => $record['users_active'],
                // Player count by internal/external status
                'NumeroJugadoresPorEstado' => $record['users_by_status'],
            ];
            if (!$record['users_by_status']) {
                unset($data['NumeroJugadoresPorEstado']);
            }
            return $data;
        }, $records, array_keys($records));
    }

    /**
     * Players that should be classified by status
     *
     * @return array
     * @throws Exception
     */
    private function playersToBeClassifiedByStatus(): array
    {
        if ($this->getFrequency() === ICSConstants::DAILY_FREQUENCY) {
            return $this->users_with_movements;
        }

        $sql = "
            SELECT a1.target, a1.descr, a1.created_at
            FROM actions AS a1
            WHERE a1.id = (
                SELECT a2.id FROM actions AS a2 WHERE a2.tag = 'user_status_changed'
                AND a2.created_at <= '{$this->getPeriodEnd()}'
                AND a2.target = a1.target
                ORDER BY a2.created_at DESC, a2.id DESC LIMIT 1
            )
            AND a1.target IN (
                SELECT id FROM users WHERE country = '{$this->getCountry()}'
                AND id NOT IN (
                    SELECT user_id FROM users_settings WHERE users_settings.setting = 'registration_in_progress'
                    AND users_settings.value >= 1
                )
                AND id IN (
                    SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'registration_end_date'
                    AND u_s.value <= '{$this->getPeriodEnd()}'
                )
                {$this->filterUsers('id')}
            );
        ";

        return $this->db->shs()->loadArray($sql);
    }

    /**
     * Players that should be classified by status from archives
     *
     * @param array $players_per_status
     * @return array
     * @throws Exception
     */
    private function playersToBeClassifiedByStatusArchives(array $players_per_status): array
    {
        $total_users = $this->getUsersArray();
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
                    AND a2.created_at <= '{$this->getPeriodEnd()}'
                    AND a2.target = a1.target
                ORDER BY
                    a2.created_at DESC,
                    a2.id DESC
                LIMIT 1)
                AND a1.target IN ($imploded_users)";

        $this->db->prependFromArchives($players_per_status, $this->getPeriodEnd(), $sql, 'actions');

        return $players_per_status;
    }

    /**
     * Players classified by status
     *
     * @return array
     * @throws Exception
     */
    protected function getPlayersPerStatusTotal(): array
    {
        if ($this->getFrequency() === ICSConstants::DAILY_FREQUENCY) {
            return $this->playersToBeClassifiedByStatus();
        }

        $players_per_status = $this->playersToBeClassifiedByStatus();

        return $this->playersToBeClassifiedByStatusArchives($players_per_status);
    }

    /**
     * Return users array that is used in function that require archive access
     *
     * @return array
     * @throws Exception
     */
    private function getUsersArray(): array
    {
        $sql = "
            SELECT
                id
            FROM
                users
            WHERE
                country = '{$this->getCountry()}'
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
                    AND u_s.value <= '{$this->getPeriodEnd()}'
            )
            {$this->filterUsers('id')}
        ";

        $users = $this->db->shs()->loadArray($sql);

        return array_column($users,'id');
    }

    /**
     * Number of players in every classifiable status at the end of the period.
     * Monthly/Daily: users in period by status
     *
     * @return array
     * @throws Exception
     */
    protected function getPlayersPerStatus(): array
    {
        $status = $this->getPlayersPerStatusTotal();

        if ($this->getFrequency() === ICSConstants::MONTHLY_FREQUENCY) {
            $status = $this->mergeStatusChanges($status);
        }

        $status_data = array_reduce(
            $status,
            function ($carry, $status_change) {
                $formatted = $this->getLicense()->formatUserStatusChangeAction($status_change);

                $key = "{$formatted['external_status_to']}-{$formatted['status_to']}";

                if (isset($carry[$key])) {
                    $carry[$key]['users_count'] = $carry[$key]['users_count'] + 1;
                } else {
                    $carry[$key] =  [
                        'statusCNJ' => $formatted['external_status_to'],
                        'status' => $formatted['status_to'],
                        'users_count' => 1
                    ];
                }

                return $carry;
            },
            []
        );

        $status_data = array_map(
            static function ($user_status) {
                return [
                    'EstadoCNJ' => $user_status['statusCNJ'],
                    'EstadoOperador' => $user_status['status'],
                    'Numero' => $user_status['users_count'],
                ];
            },
            $status_data
        );

        return array_values($status_data);
    }

    /**
     * Number of players removed during the period.
     *
     * After 4 years an user cancelled and treat as removed;
     * From document point of view we have both statuses applying to the current situation:
     * user de-registered and records removed from the platform with keeping record about case of removing
     *
     * @return int
     * @throws Exception
     */
    protected function getRemovedUsersCount(): int
    {
        // It looks like we should report as NumberRemoved  - always 0
        // Since we don't really support the removal as per documentation description (As discussed with @ricardo)
        // And, in the same report, they require to have the list of players by status in the report
        // (where CANCELLED is one of the player status that they expect)
        return 0;
    }

    /**
     * Number of players who joined during the period.
     *
     * @return int
     * @throws Exception
     */
    protected function getNewUsersCount(): int
    {
        $sql = "
            SELECT
                count(users.id) as new_users
            FROM
                users
            WHERE
                {$this->getBaseUserWhereCondition('>=')}
        ";

        return $this->db->shs('sum')->loadCol($sql, 'new_users')[0] ?? 0;
    }

    /**
     * Number of players with activity during the period, i.e.,
     * those who had a transaction on their gambling account (deposits, wagers, etc.) of at least €1.
     *
     * @return int
     * @throws Exception
     */
    protected function getActiveUsersCount(): int
    {
        $sql = "
            SELECT
                count(active_users.user_id) as total_active_users
            FROM (
                SELECT
                    user_id,
                    SUM(bets + wins + deposits + withdrawals) as total
                FROM users_daily_stats
                    WHERE
                        country = '{$this->getCountry()}'
                        AND date >= '{$this->getPeriodStart()}'
                        AND date <= '{$this->getPeriodEnd()}'
                        AND users_daily_stats.user_id IN (
                            SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'registration_end_date' AND u_s.value <= '{$this->getPeriodEnd()}'
                        )
                        {$this->filterUsers('users_daily_stats.user_id')}
                GROUP BY user_id
                HAVING total >= 100
            ) as active_users;
        ";

        return $this->db->shs('sum')->loadCol($sql, 'total_active_users')[0] ?? 0;
    }

    /**
     * @return int
     * @throws Exception
     */
    protected function getUsersCount(): int
    {
        if ($this->getFrequency() === ICSConstants::DAILY_FREQUENCY) {
            $this->setUsersWithMovements();

            return count($this->users_with_movements);
        }

        return $this->getTotalUsersCount();
    }


    /**
     * Total number of players at the end of the period regardless of their status used for the monthly report
     *
     * @return int
     * @throws Exception
     */
    private function getTotalUsersCount(): int
    {
        $sql = "
            SELECT
                count(users.id) as total_users
            FROM
                users
            WHERE
                {$this->getBaseUserWhereCondition()}
        ";

        return $this->db->shs('sum')->loadCol($sql, 'total_users')[0] ?? 0;
    }

    /**
     * Sets players with movements for a period used in the daily report
     *
     * @return self
     * @throws Exception
     */
    private function setUsersWithMovements(): self
    {
        $sql = "
            SELECT a1.target, a1.descr, a1.created_at
            FROM (
            SELECT
                users.id
            FROM
                users
            WHERE
                {$this->getBaseUserWhereCondition('>=')}
            UNION
            SELECT
                users.id
            FROM
                users
            WHERE
                {$this->getBaseUserWhereCondition('<=')}
                AND users.id IN (
                    SELECT target
                    FROM actions
                    WHERE tag in ('deposit-rgl-applied', 'profile-update-success', 'profile-update-by-admin', 'user_status_changed')
                    AND created_at >= '{$this->getPeriodStart()}'
                    AND created_at <= '{$this->getPeriodEnd()}'
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
                    AND a2.created_at <= '{$this->getPeriodEnd()}'
                    AND a2.target = a1.target
                ORDER BY
                    a2.created_at DESC,
                    a2.id DESC
                LIMIT 1
                        )
        ";

        $this->users_with_movements = $this->db->shs()->loadArray($sql);

        return $this;
    }

    /**
     * Set up the statuses array from the csv file. Set up in a structure same as the returned statuses from DB
     *
     * @return self
     * @throws Exception
     */
    public function setCsvStatusesArray(): self
    {
        $csv_file_path = dirname(__FILE__) . self::CSV_RELATIVE_PATH;
        $latest_statuses = [];
        $previous_statuses = [];

        if (($handle = fopen($csv_file_path, 'r')) !== false) {
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                $user_id = $row[0];
                $status = $row[1];
                $created_at = $row[2];

                if (strtotime($created_at) <= strtotime($this->getPeriodEnd())) {
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

        $this->csv_statuses = array_values($latest_statuses);

        return $this;
    }

    /**
     * Merges the status changes from DB with the status changes from the CSV. Returns a trimmed array with only the latest status change for each user
     * @param array $db_status_changes
     * @return array
     */
    public function mergeStatusChanges(array $db_status_changes): array
    {
        $mergedArray = array_merge($db_status_changes, $this->csv_statuses);

        $latest_statuses = [];
        foreach ($mergedArray as $status) {
            $target = $status['target'];

            if (!isset($latest_statuses[$target]) || strtotime($status['created_at']) > strtotime($latest_statuses[$target]['created_at'])) {
                $latest_statuses[$target] = $status;
            }
        }

        return array_values($latest_statuses);
    }
}
