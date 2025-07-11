<?php

namespace ES\ICS\Reports\v3;

use ES\ICS\Constants\ICSConstants;

class RUT extends \ES\ICS\Reports\v2\RUT
{
    protected static int $internal_version = 1;

    protected const VALID_USER_STATUSES = ['A', 'PV', 'S', 'C', 'CD', 'PR', 'AE', 'O'];

    protected const INTEGER8_LENGTH = 8;

    public function getRecordHeader(int $subregister_index = 1, int $total_subregisters = 1): array
    {
        $header = parent::getRecordHeader($subregister_index, $total_subregisters);

        unset($header['OperadorId']);
        unset($header['AlmacenId']);
        return $header;
    }

    protected function setupRecords(array $records, string $group = ''): array
    {
        $total = count($records);

        return array_map(function($record, $index) use ($total) {
            $data = [
                '_attributes' => ['xsi:type' => self::NAME],
                // Record Header
                'Cabecera' => $this->getRecordHeader($index + 1, $total),
                // Month
                'Mes' => phive()->fDate($this->getPeriodEnd(), ICSConstants::MONTH_FORMAT),
                // Total number of players in that period
                'NumeroJugadores' => $this->formatUInt8($record['users_total']),
                // New players in that period
                'NumeroAltas' => $this->formatUInt8($record['users_new']),
                // Closed account players in that period
                'NumeroBajas' => $this->formatUInt8($record['users_removed']),
                // Players active more than 1e transactions
                'NumeroActividad' => $this->formatUInt8($record['users_active']),
                // We don't report test accounts
                'NumeroTest' => 0,
                // Player count by internal/external status
                'NumeroJugadoresPorEstado' => $record['users_by_status'],
                'NumeroJugadoresPorPerfil' => $record['users_profile']
            ];
            if (!$record['users_by_status']) {
                unset($data['NumeroJugadoresPorEstado']);
            }
            return $data;
        }, $records, array_keys($records));
    }

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
                    'users_profile' => $this->getPlayerCountPerProfile(),
                ]
            ]
        ];
    }

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

                $external_status = in_array($formatted['external_status_to'], self::VALID_USER_STATUSES) ?
                    $formatted['external_status_to'] : 'O';

                if (isset($carry[$external_status])) {
                    $carry[$external_status]['users_count'] = $carry[$external_status]['users_count'] + 1;
                } else {
                    $carry[$external_status] =  [
                        'statusCNJ' => $external_status,
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
                    'Numero' => $user_status['users_count'],
                ];
            },
            $status_data
        );

        return array_values($status_data);
    }

    /**
     * Format unsigned integer
     */
    protected function formatUInt8(?int $value): ?int
    {
        if (is_null($value)) return null;

        return (int) substr($value, 0, self::INTEGER8_LENGTH);
    }

    protected function getPlayerCountPerProfile(): array
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM actions AS a1
            JOIN (
                SELECT target, MAX(created_at) AS max_created_at
                FROM actions
                WHERE tag = 'user_status_changed'
                    AND descr = 'set-flag|mixed - Triggered flag RG65'
                    AND created_at <= '{$this->getPeriodEnd()}'
                GROUP BY target
            ) a2
            ON a1.target = a2.target AND a1.created_at = a2.max_created_at;
        ";

        $res = phive('SQL')->shs('merge', '', null, 'total')->loadArray($sql);
        $intensivePlayerTotal = array_sum(phive()->flatten($res));

        $countPerProfile = [
            'ClientePrivilegiado' => 0, // we don't have VIPs
            'JugadorIntensivo' => $intensivePlayerTotal,
            'ParticipanteJoven' => 0, // TODO
            'ComportamientoRiesgo' => 0, // TODO
            'Otros' => 0, // TODO
        ];

        $result = [];
        foreach ($countPerProfile as $profile => $count) {
            $result[] = [
                'PerfilJugador' => $profile,
                'Numero' => $this->formatUInt8($count)
            ];
        }

        return $result;
    }
}
