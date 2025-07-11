<?php

namespace ES\ICS\Reports\v2;

use DateTime;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Validation\Traits\DeviceTrait;
use Exception;
use ES\ICS\Reports\BaseReport;

class JUD extends BaseReport
{
    use DeviceTrait;

    public const TYPE = 'JU';
    public const SUBTYPE = 'JUD';
    public const NAME = 'RegistroJUD';

    protected static int $internal_version = 4;

    /**
     * Return sessions grouped by game type
     *
     * @return array
     * @throws Exception
     */
    public function getGroupedRecords(): array
    {
        $sql = "
             SELECT
                ugs.id,
                ugs.user_id,
                ugs.start_time,
                ugs.end_time,
                ugs.game_ref,
                ugs.ip,
                ugs.bet_amount,
                ugs.win_amount,
                ugs.session_id,
                ugs.bets_rollback,
                ugs.bet_cnt,
                users_sessions.equipment,
                micro_games.tag AS game_tag,
                micro_games.id AS game_id,
                a.descr AS uagent,
                slimits.stake AS spend_limit,
                slimits.time_limit AS time_limit
            FROM users_game_sessions AS ugs
            LEFT JOIN users_sessions ON users_sessions.id = ugs.session_id
            LEFT JOIN micro_games ON micro_games.ext_game_name = ugs.game_ref
            LEFT JOIN ext_game_participations AS slimits ON ugs.id = slimits.user_game_session_id
            LEFT JOIN actions AS a
                   ON a.id = (
                       SELECT a.id
                       FROM actions AS a
                       WHERE tag = 'uagent'
                         AND (a.actor = users_sessions.user_id)
                         AND a.created_at < ugs.end_time
                       ORDER BY a.created_at DESC
                       LIMIT 1
                   )
            WHERE ugs.end_time BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
            AND ugs.user_id IN (SELECT id FROM users WHERE country = '{$this->getCountry()}')
            AND (slimits.stake != 0 OR slimits.time_limit != 0 OR ugs.bet_amount != 0 OR ugs.win_amount != 0)
            {$this->filterUsers('ugs.user_id')}
            AND ugs.id NOT IN (
            SELECT id
            FROM
                users_game_sessions
            WHERE end_time BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
              AND bet_amount = 0
              AND win_amount != 0
              AND user_id IN (SELECT id FROM users WHERE country = '{$this->getCountry()}')
               {$this->filterUsers('user_id')}
               )
            GROUP BY ugs.id;
        ";

        $items = $this->db->shs()->loadArray($sql);
        return array_reduce($items, function ($carry, $session) {
            $game_type = $this->getGameType($session['game_tag'], $session['game_id']);
            if (empty($carry[$game_type])) {
                $carry[$game_type] = [];
            }

            $carry[$game_type][] = $session;

            return $carry;
        }, []);
    }


    /**
     * Map session to required Registro structure
     *
     * @param array $records
     * @param string $group CJ* - batch number, Other - game type
     * @return array
     * @throws Exception
     */
    public function setupRecords(array $records, string $group = ''): array
    {
        return array_map(function ($record) {
            //we don't have multiplayer games at the moment, so locking it a 1 subrecord per record in Cabecera
            //and we only generate 1 Jugador
            return [
                '_attributes' => ['xsi:type' => self::NAME],
                'Cabecera' => $this->getRecordHeader(1, 1),
                // Unique game session id need to match JUT
                'JuegoId' => $record['id'],
                // User game session start date need to match JUT
                'FechaInicio' => date(ICSConstants::DATETIME_TO_GMT_FORMAT, (new DateTime($record['start_time']))->getTimestamp()),
                // User game session end date need to match JUT
                'FechaFin' => date(ICSConstants::DATETIME_TO_GMT_FORMAT, (new DateTime($record['end_time']))->getTimestamp()),
                'Jugador' => $this->getSessionData($record),
            ];
        }, $records, array_keys($records));
    }


    public function getRecordHeader(int $subregister_index = 1, int $total_subregisters = 1): array
    {
        // We need to have unique report id per game session
        $this->setReportId();

        return parent::getRecordHeader($subregister_index, $total_subregisters);
    }


    /**
     * Return report file name
     *
     * @return string
     */
    protected function getFileName(): string
    {
        $type = self::TYPE;
        $sub_type = self::SUBTYPE;
        $date_time = $this->getDate()->format(ICSConstants::DATETIME_FORMAT);

        return "{$this->getOperatorId()}_{$this->getStorageId()}_{$type}_{$sub_type}_{$this->type}_{$date_time}_{$this->getBatchId()}";
    }

    /**
     * Map session entry to required format
     *
     * @param array $session
     *
     * @return array
     * @throws Exception
     */
    private function getSessionData(array $session): array
    {
        $session_data = [
            'ID' => [
                'OperadorId' => $this->getOperatorId(),
                'JugadorId' => $session['user_id'],
            ],
            // Player bets in that gaming sessions
            'Participacion' => $this->formatAmount($session['bet_amount'] * -1),
            // users_game_sessions.bet_amount
            // Player rollback bets in that gaming sessions
            'ParticipacionDevolucion' => $this->formatAmount($session['bets_rollback']),
            // users_game_sessions.bets_rollback
            'Premios' => $this->formatAmount($session['win_amount']), // users_game_sessions.win_amount
            // We dont have "Price in Kind" but the field is mandatory
            'PremiosEspecie' => $this->formatAmount(0),
            //'DesgloseEspecie' => 'CAN BE OMITTED  IF WE DONT HAVE "PremiosEspecie"',
            // TODO replace `FechaApuesta` with `FechaInicio` when 2.14 version is implemented
            'FechaApuesta' => (new DateTime($session['start_time']))->format(ICSConstants::DATETIME_TO_GMT_FORMAT),
            // User gaming session limits
            'PlanificacionAzar' => null,
            // User gaming session IP
            'IP' => $session['ip'],
            // users_game_sessions.ip
            // User gaming device type
            'Dispositivo' => $this->getDeviceType($session['equipment']),
            // User gaming device id
            'IdDispositivo' => $this->getDeviceId($session['uagent']),
        ];

        if ($this->type === 'AZA') {
            $session_data['PlanificacionAzar'] = $this->formatSessionLimits($session);
        } else{
            unset($session_data['PlanificacionAzar']);
        }

        return $session_data;

    }


    /**
     * Format session limits
     *
     * @param array $session_limits
     * @return string[]
     */
    private function formatSessionLimits(array $session_limits)
    {
        return [
            'DuracionLimite' => date(ICSConstants::TIME_FORMAT, mktime(0, $session_limits['time_limit'], 0)),
            'ParticipacionLimite' => $this->format2Decimal($session_limits['spend_limit']),
        ];
    }

    public function getFrequencyDirectory(): string
    {
        return phive()->fDate($this->getPeriodStart(), ICSConstants::DAY_FORMAT);
    }

    public function getExtraDirectory() {
        return $this->type;
    }

    /**
     * Detect if we should generate report
     *
     * @param DateTime $now
     * @param string $default_start
     * @return bool
     */
    public function shouldReportNow(DateTime $now, string $default_start): bool
    {
        $now_formatted = $now->format(ICSConstants::DATETIME_DBFORMAT);

        $entries_since_last_report = $this->db->shs('sum')->loadCol($sql = "
            SELECT COUNT(*) AS count
            FROM users_game_sessions AS ugs
            WHERE ugs.end_time BETWEEN '{$default_start}' AND '{$now_formatted}'
                AND ugs.user_id IN (SELECT id FROM users WHERE country = '{$this->getCountry()}')
                {$this->filterUsers('ugs.user_id')}
        ", 'count')[0];

        // Don't try to report when there are no entries because it'll query DB for nothing
        if (empty($entries_since_last_report)) {
            return false;
        }

        if ($entries_since_last_report >= ICSConstants::REAL_TIME_ENTRIES) {
            return true;
        }

        $minutes_since_last_report = phive()->subtractTimes(
            $now->getTimestamp(),
            DateTime::createFromFormat(ICSConstants::DATETIME_DBFORMAT, $default_start)->getTimestamp(),
            'm'
        );


        if ($minutes_since_last_report >= ICSConstants::REAL_TIME_MINUTES) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function shouldGenerateEmptyReport(): bool
    {
        return false;
    }
}
