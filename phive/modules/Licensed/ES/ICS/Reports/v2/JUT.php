<?php

namespace ES\ICS\Reports\v2;

use Carbon\Carbon;
use DateTime;
use ES\ICS\Constants\ICSConstants;
use Exception;
use ES\ICS\Reports\BaseReport;

class JUT extends BaseReport
{
    public const TYPE = 'JU';
    public const SUBTYPE = 'JUT';
    public const NAME = 'RegistroSesion';
    public const GAME_TYPE_ROULETTE = 'RLT';
    public const GAME_TAG_ALIAS_ROULETTE = 'liveroulette.cgames';
    public const GAME_IS_LIVE_ROULETTE = 'S';
    public const GAME_IS_NOT_LIVE_ROULETTE = 'N';
    protected static int $internal_version = 5;

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
                micro_games.game_name AS game_desc
            FROM users_game_sessions as ugs
            LEFT JOIN users_sessions ON users_sessions.id = ugs.session_id
            LEFT JOIN micro_games ON micro_games.ext_game_name = ugs.game_ref AND micro_games.device_type_num = ugs.device_type_num
            LEFT JOIN ext_game_participations AS egp ON ugs.id = egp.user_game_session_id
            WHERE ugs.end_time BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
            AND ugs.user_id IN (SELECT id FROM users WHERE country = '{$this->getCountry()}')
            AND (egp.stake != 0 OR egp.time_limit != 0 OR ugs.bet_amount != 0 OR ugs.win_amount != 0)
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

            $game_aliases = $this->getGameSubTags($session['game_id']);
            $is_roulette = $game_type === self::GAME_TYPE_ROULETTE;
            $session['is_roulette'] = $is_roulette;
            $session['is_live_roulette'] = $is_roulette && in_array(self::GAME_TAG_ALIAS_ROULETTE, $game_aliases, true);

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
        return array_map(function ($record) use ($group) {
            //we don't have multiplayer games at the moment, so locking it a 1 subrecord per record in Cabecera
            $res = [
                '_attributes' => ['xsi:type' => self::NAME],
                'Cabecera' => $this->getRecordHeader(1, 1),
                // Gaming session id need to match JUD
                'JuegoId' =>  $record['id'],
                // Description of the gambling activity
                'JuegoDesc' => $record['game_desc'],
                // Game type
                'TipoJuego' => $group,
                // Start date time of game session
                'FechaInicio' => date(ICSConstants::DATETIME_TO_GMT_FORMAT, (new DateTime($record['start_time']))->getTimestamp()),
                // End date time of game session, need to match JUD
                'FechaFin' => date(ICSConstants::DATETIME_TO_GMT_FORMAT, (new DateTime($record['end_time']))->getTimestamp()),
                // Game is part of network "S" yes, "N" no
                'JuegoEnRed' => 'N', // No network games atm
                // Totals
                'Totales' => [
                    // Total bets
                    'Participacion' => $this->formatBreakdownByOperator([
                        [
                            'amount' => $record['bet_amount'] * -1
                        ]
                    ]),
                    // Total rollback bets
                    'ParticipacionDevolucion' => $this->formatBreakdownByOperator([
                        [
                            'amount' => $record['bets_rollback']
                        ]
                    ]),
                    // Total wins
                    'Premios' => $this->formatBreakdownByOperator([
                        [
                            'amount' => $record['win_amount']
                        ]
                    ]),
                    // Prizes in kind, monetary value, We dont have prize in kind but its mandatory
                    'PremiosEspecie' => $this->formatBreakdownByOperator([]),
                    // Transactions relating to jackpots, broken down
                    // at jackpot level. Contribution to a jackpot (+), or the
                    // distribution of a jackpot within this game (-).
                    // We dont have jackpots for release so will "empty" value
                    'Botes' => $this->formatJackpotsTransactions([])
                ],
                // Game subtype used for blackjack and roulette, not need on slots
                'Variante' => $this->getGameVariant($record['game_tag'], $record['game_id']),
                // This shall correspond to the name used by th operator in marketing this gambling version
                'VarianteComercial' => $record['game_desc'],
                // It is only for roulette games, it should report
                // S, when the game is a life roulette
                // N, when is not a life roulette
                'RuletaEnVivo' => $this->getRuletaEnVivo($record['is_roulette'], $record['is_live_roulette']),
                // Number of games played during the session
                'PartidasJugadas' =>  $record['bet_cnt'] == 0 ? 1 : $record['bet_cnt'],
            ];
            if (empty($res['Variante'])) {
                unset($res['Variante']);
            }

            if (empty($res['RuletaEnVivo'])) {
                unset($res['RuletaEnVivo']);
            }

            return $res;
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
     * Format jackpot transactions
     *
     * @param array $jackpot_transactions
     * @return array
     * @noinspection PhpUnusedParameterInspection keeping it for completeness
     */
    private function formatJackpotsTransactions(array $jackpot_transactions): array
    {
        return [
            'Total' => $this->formatAmount(0),
        ];
    }

    public function getFrequencyDirectory(): string
    {
        return phive()->fDate($this->getPeriodStart(), ICSConstants::DAY_FORMAT);
    }

    public function getExtraDirectory(): string {
        return $this->type;
    }

    /**
     * @return bool
     */
    protected function shouldGenerateEmptyReport(): bool
    {
        return false;
    }

    /**
     * @param bool $is_roulette
     * @param bool $is_live_roulette
     * @return string
     */
    private function getRuletaEnVivo(bool $is_roulette,bool $is_live_roulette): string
    {
        if (!$is_roulette) {
            return '';
        }

        return $is_live_roulette ? self::GAME_IS_LIVE_ROULETTE : self::GAME_IS_NOT_LIVE_ROULETTE;
    }

    public static function getUsersSessionsDates(Carbon $date, string $country): array
    {
        $start_of_day = (clone $date)->startOfDay();
        $end_of_day = (clone $date)->endOfDay();
        $dateTimeFormat = ICSConstants::DATETIME_DBFORMAT;

        return phive('SQL')->shs()
            ->loadCol("
                SELECT ugs.end_time
                FROM users_game_sessions AS ugs
                LEFT JOIN ext_game_participations AS egp ON ugs.id = egp.user_game_session_id
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
