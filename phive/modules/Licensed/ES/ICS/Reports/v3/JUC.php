<?php

namespace ES\ICS\Reports\v3;

use Carbon\Carbon;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports\BaseReport;
use ES\ICS\Validation\Traits\DeviceTrait;

/**
 * This report groups user game sessions by game type (AZA, BLJ, RLT).
 * Session info is stored in "ext_game_participations" table.
 * Game session info is stored in "users_game_sessions" table.
 */
class JUC extends BaseReport
{
    public const TYPE = 'JU';
    public const SUBTYPE = 'JUC';
    public const NAME = 'RegistroOtrosJuegos';
    public const GAME_TYPE_ROULETTE = 'RLT';
    public const GAME_TAG_ALIAS_ROULETTE = 'liveroulette.cgames';

    protected static int $internal_version = 1;

    use DeviceTrait;

    public function getRecordHeader(int $subregister_index = 1, int $total_subregisters = 1): array
    {
        $this->setReportId();

        $header = parent::getRecordHeader($subregister_index, $total_subregisters);

        unset($header['OperadorId']);
        unset($header['AlmacenId']);
        return $header;
    }

    protected function setupRecords(array $records, string $group = ''): array
    {
        return array_map(function ($record) {
            //we don't have multiplayer games at the moment, so locking it a 1 subrecord per record in Cabecera
            //and we only generate 1 Jugador
            return array_merge([
                    '_attributes'  => ['xsi:type' => self::NAME],
                    'Cabecera' => $this->getRecordHeader(1, 1),
                ],
                $this->getOtherGames($record)
            );
        }, $records, array_keys($records));
    }

    public function getFrequencyDirectory(): string
    {
        return phive()->fDate($this->getPeriodStart(), ICSConstants::DAY_FORMAT);
    }

    public function getGroupedRecords(): array
    {
        $sql = "
             SELECT
                s.id AS session_id, -- SesionId
                s.user_id, -- JugadorId
                s.created_at AS session_start_at, -- FechaInicioSesion
                s.ended_at AS session_end_at, -- FechaFinSesion
                TIMESTAMPDIFF(MINUTE, s.created_at, s.ended_at) as session_minutes,
                s.time_limit AS time_limit, -- DuracionLimite
                s.stake AS spend_limit, -- GastoLimite
                s.restrict_future_session, -- PeriodoExclusion
                s.limit_future_session_for, -- TiempoExclusion
                us.equipment, -- Dispositivo
                ugs.session_id AS game_session_id, -- JuegoId
                ugs.game_ref,
                ugs.ip, -- IP
                ugs.bet_amount, -- Participacion
                ugs.bets_rollback, -- ParticipacionDevolucion
                ugs.start_time AS game_start_at, -- Juego.FechaInicio
                ugs.end_time AS game_end_at, -- Juego.FechaFin
                micro_games.tag AS game_tag,
                micro_games.id AS game_id,
                micro_games.game_name,
                ugs.bet_cnt AS rounds_played, -- PartidasJugadas
                (SELECT SUM(amount)
                 FROM wins
                WHERE user_id = s.user_id
                AND created_at BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                AND game_ref = ugs.game_ref
                GROUP BY user_id, game_ref) AS win_amount -- Premios
            FROM ext_game_participations AS s
            INNER JOIN users_game_sessions AS ugs ON ugs.id = s.user_game_session_id
            INNER JOIN micro_games ON micro_games.ext_game_name = ugs.game_ref
                AND micro_games.device_type_num = ugs.device_type_num
            INNER JOIN users_sessions us ON us.id = ugs.session_id
            WHERE s.ended_at BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
            AND ugs.user_id IN (SELECT id FROM users WHERE country = '{$this->getCountry()}')
            AND (s.stake != 0 OR s.time_limit != 0 OR ugs.bet_amount != 0 OR ugs.win_amount != 0)
            AND ugs.id NOT IN (
                SELECT id
                FROM users_game_sessions
                WHERE end_time BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                  AND bet_amount = 0
                  AND win_amount != 0
            )
            {$this->filterUsers('ugs.user_id')}
        ";

        $items = $this->db->shs()->loadArray($sql);

        $result = $user_ids = [];

        foreach ($items as $item) {
            $session_id = $item['session_id'];

            if (!isset($result[$session_id])) {
                $result[$session_id] = [
                    'session_id' => $session_id,
                    'session_start_at' => $item['session_start_at'],
                    'session_end_at' => $item['session_end_at'],
                    'spend_limit' => $item['spend_limit'],
                    'time_limit' => $item['time_limit'],
                    'restrict_future_session' => $item['restrict_future_session'],
                    'limit_future_session_for' => $item['limit_future_session_for'],
                    'user_id' => $item['user_id'],
                    'ip' => $item['ip'],
                    'equipment' => $item['equipment'],
                    '_game_sessions' => []
                ];
            }

            $game_type = $this->getGameType($item['game_tag'], $item['game_id']);

            $game_aliases = $this->getGameSubTags($item['game_id']);
            $is_roulette = $game_type === self::GAME_TYPE_ROULETTE;

            $result[$session_id]['_game_sessions'][$game_type][] = [
                'game_session_id' => $item['game_session_id'],
                'description' => $item['game_name'],
                'start_at' => $item['game_start_at'],
                'end_at' => $item['game_end_at'],
                'bet_amount' => $item['bet_amount'],
                'bets_rollback' => $item['bets_rollback'],
                'win_amount' => $item['win_amount'],
                'game_tag' => $item['game_tag'],
                'game_id' => $item['game_id'],
                'game_name' => $item['game_name'],
                'is_live_roulette' => $is_roulette && in_array(self::GAME_TAG_ALIAS_ROULETTE, $game_aliases, true)
            ];

            $user_ids[] = $item['user_id'];
        }

        $this->addUserAgents($result, $user_ids);

        return array_chunk($result, ICSConstants::RECORD_PER_BATCH);
    }

    /**
     * $sessions is an array like [session_id => [game_type => [...]]]
     */
    private function getOtherGames(array $session): array
    {
        $result = [];

        foreach ($session['_game_sessions'] as $game_type => $game_sessions)
        {
            $entry = [
                'JuegoId' => $this->getUserGameIds($game_sessions),
                'JuegoDesc' => $this->getGameNames($game_sessions),
                'TipoJuego' => $game_type,
                'FechaInicio' => $this->getGameStart($game_sessions),
                'FechaFin' => $this->getGameEnd($game_sessions),
                'Participacion' => $this->getBetAmount($game_sessions),
                'ParticipacionDevolucion' => $this->getBetsRollbackAmount($game_sessions),
                'Premios' => $this->getWinAmount($game_sessions),
                'Botes' => [
                    'Total' => $this->formatAmount(0)
                ],
                'Variante' => $this->getGameVariants($game_sessions),
                'VarianteComercial' => $this->getGameCommercialVariants($game_sessions),
                'JuegoEnVivo' => $this->getLivePlay($game_sessions),
                'PartidasJugadas' => $this->getRoundsPlayed($game_sessions)
            ];

            if ($game_type === 'AZA') {
                unset($entry['Variante']);
            }

            if ($game_type !== 'RLT') {
                unset($entry['JuegoEnVivo']);
            }

            $result['Juego'][] = $entry;
        }

        $result['Jugador'] = [
            'JugadorId' => $session['user_id'],
            'Sesion' => [
                'SesionId' => $session['session_id'],
                'FechaInicioSesion' => phive()->fDate($session['session_start_at'], ICSConstants::DATETIME_TO_GMT_FORMAT),
                'FechaFinSesion' => phive()->fDate($session['session_end_at'], ICSConstants::DATETIME_TO_GMT_FORMAT),
                'FechaInicioPrimerJuego' => $this->getFirstGameStart($session['_game_sessions']),
                'FechaFinUltimoJuego' => $this->getLastGameEnd($session['_game_sessions']),
                'PlanificacionSesion' => [
                    'DuracionLimite' => date(ICSConstants::TIME_FORMAT, mktime(0, $session['time_limit'], 0)),
                    'GastoLimite' => $this->format2Decimal($session['spend_limit']),
                    'PeriodoExclusion' => $session['restrict_future_session'] ? 'S' : 'N',
                    'TiempoExclusion' => $this->getExclusionTime($session['limit_future_session_for']),
                ],
                'SesionCompleta' => 'S',
                'SesionNueva' => 'S',
                'MotivoFinSesion' => $this->getEndSessionReason($session)
            ],
            'IP' => $session['ip'],
            'Dispositivo' => $session['equipment'] ? $this->getDeviceType($session['equipment']) : '',
            'IdDispositivo' => $session['uagent'] ? $this->limitLength($this->getDeviceId($session['uagent']), 100) : '',
        ];

        return $result;
    }

    private function getUserAgents(array $user_ids): array
    {
        if (empty($user_ids)) {
            return [];
        }

        $sql = "
             SELECT a1.target, a1.descr as uagent
                FROM actions a1
                WHERE a1.created_at <= '{$this->getPeriodEnd()}'
                AND a1.tag = 'uagent'
                AND a1.target IN (".implode(',', $user_ids).")
                AND a1.created_at = (
                    SELECT MAX(a2.created_at)
                    FROM actions a2
                    WHERE a2.target = a1.target
                      AND a2.tag = 'uagent'
                      AND a2.created_at <= '{$this->getPeriodEnd()}'
                );
        ";

        $items = $this->db->shs()->loadArray($sql);

        return array_combine(array_column($items, 'target'), array_column($items, 'uagent'));
    }

    private function addUserAgents(array &$result, array $user_ids): void
    {
        $user_agents = $this->getUserAgents($user_ids);

        foreach ($result as &$session) {
            $session['uagent'] = $user_agents[$session['user_id']] ?? '';
        }
    }

    public function shouldReportNow(\DateTime $now, string $default_start): bool
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
            \DateTime::createFromFormat(ICSConstants::DATETIME_DBFORMAT, $default_start)->getTimestamp(),
            'm'
        );

        if ($minutes_since_last_report >= ICSConstants::REAL_TIME_MINUTES) {
            return true;
        }

        return false;
    }

    /**
     * Returns a comma-separated list of user game session ids
     */
    private function getUserGameIds(array $game_sessions): string
    {
        $game_session_ids = array_map(function (array $game_session) {
            return $game_session['game_session_id'];
        }, $game_sessions);

        return implode(',', $game_session_ids);
    }

    /**
     * Returns a comma-separated list of game names
     */
    private function getGameNames(array $game_sessions): string
    {
        $game_names = array_map(function (array $game_session) {
            return $game_session['game_name'];
        }, $game_sessions);


        return implode(',', $game_names);
    }

    private function getGameStart(array $game_sessions): string
    {
        if (empty($game_sessions)) return '';

        $start_time = min(array_column($game_sessions, 'start_at'));

        return phive()->fDate($start_time, ICSConstants::DATETIME_TO_GMT_FORMAT);
    }

    private function getGameEnd(array $game_sessions): string
    {
        if (empty($game_sessions)) return '';

        $end_time = min(array_column($game_sessions, 'end_at'));

        return phive()->fDate($end_time, ICSConstants::DATETIME_TO_GMT_FORMAT);
    }

    private function getBetAmount(array $game_sessions): array
    {
        $sum = array_sum(array_column($game_sessions, 'bet_amount'));
        return $this->formatAmount($sum);
    }

    private function getWinAmount(array $game_sessions): array
    {
        $sum = array_sum(array_column($game_sessions, 'win_amount'));
        return $this->formatAmount($sum);
    }

    private function getRoundsPlayed(array $game_sessions): int
    {
        return array_sum(array_column($game_sessions, 'rounds_played'));
    }

    private function getBetsRollbackAmount(array $game_sessions): array
    {
        $sum = array_sum(array_column($game_sessions, 'bets_rollback'));
        return $this->formatAmount($sum);
    }

    private function getGameVariants(array $game_sessions): array
    {
        $game_variants = [];

        foreach ($game_sessions as $game_session)
        {
            if ($game_variant = $this->getGameVariant(
                $game_session['game_tag'],
                $game_session['game_id']
            )) {
                $game_variants[] = $game_variant;
            }
        }

        return $game_variants;
    }

    private function getGameCommercialVariants(array $game_sessions): array
    {
        return array_unique(array_column($game_sessions, 'game_name'));
    }

    private function getLivePlay(array $game_sessions): string
    {
        foreach ($game_sessions as $game_session) {
            if ($game_session['is_live_roulette']) {
                return 'S';
            }
        }

        return 'N';
    }

    private function getFirstGameStart(array $game_sessions): string
    {
        $dates = [];

        foreach ($game_sessions as $game_session_by_type) {
            $dates = array_merge($dates, array_column($game_session_by_type, 'start_at'));
        }

        $firstGameStart = min($dates);

        return phive()->fDate($firstGameStart, ICSConstants::DATETIME_TO_GMT_FORMAT);
    }

    private function getLastGameEnd(array $game_sessions): string
    {
        $dates = [];

        foreach ($game_sessions as $game_session_by_type) {
            $dates = array_merge($dates, array_column($game_session_by_type, 'end_at'));
        }

        $lastGameEnd = max($dates);

        return phive()->fDate($lastGameEnd, ICSConstants::DATETIME_TO_GMT_FORMAT);
    }

    protected function shouldGenerateEmptyReport(): bool
    {
        return false;
    }

    private function getExclusionTime(int $totalMinutes): string
    {
        if (empty($totalMinutes)) return '000000';

        $days = floor($totalMinutes / (24 * 60));
        $hours = floor(($totalMinutes % (24 * 60)) / 60);
        $minutes = $totalMinutes % 60;

        return sprintf("%02d%02d%02d", $days, $hours, $minutes);
    }

    private function getEndSessionReason(array $session): string
    {
        if ($session['session_minutes'] > $session['time_limit']) {
            return 'Limite';
        }

        return 'Usuario';
    }

    public static function getUsersSessionsDates(Carbon $date, string $country): array
    {
        $start_of_day = (clone $date)->startOfDay();
        $end_of_day = (clone $date)->endOfDay();
        $dateTimeFormat = ICSConstants::DATETIME_DBFORMAT;

        return phive('SQL')->shs()
            ->loadCol("
                SELECT ugs.end_time
                FROM ext_game_participations AS egp
                INNER JOIN users_game_sessions AS ugs ON ugs.id = egp.user_game_session_id
                INNER JOIN micro_games ON micro_games.ext_game_name = ugs.game_ref
                    AND micro_games.device_type_num = ugs.device_type_num
                INNER JOIN users_sessions us ON us.id = ugs.session_id
                WHERE ugs.end_time BETWEEN '{$start_of_day->format($dateTimeFormat)}' AND '{$end_of_day->format($dateTimeFormat)}'
                    AND ugs.user_id IN (SELECT id FROM users WHERE country = '{$country}')
                    AND ugs.user_id NOT IN (
                        SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'test_account' AND u_s.value = 1
                    )
                    AND (egp.stake != 0 OR egp.time_limit != 0 OR ugs.bet_amount != 0 OR ugs.win_amount != 0)
                GROUP BY ugs.id
            ", 'end_time');
    }
}
