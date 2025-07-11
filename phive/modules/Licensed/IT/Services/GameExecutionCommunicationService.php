<?php
namespace IT\Services;

use DateTime;
use DateTimeZone;
use IT\Pgda\Codes\ReturnCode as PgdaReturnCode;
use Exception;
use IT\Services\Traits\InteractWithMail;

/**
 * Class GameExecutionCommunicationService
 * @package IT\Services
 */
class GameExecutionCommunicationService extends ArchiveManagementService
{
    use InteractWithMail;

    /**
     * indicating status not yet processed
     *
     * @var int
     */
    const STATUS_CODE_NONE = 0;

    /**
     * indicating status 580 already sent
     *
     * @var int
     */
    const STATUS_CODE_SENT = 1;

    /**
     * The stages count limit
     *
     * @var int
     */
    const LIMIT = 1000;

    /**
     * Stores info about game sessions with freespins
     */
    private $ext_game_participation_bonus = [];

    /**
     * send game session execution
     *
     * @param int $ext_game_session_id
     * @param array $users
     * @return false|void
     * @throws Exception
     */
    public function sendGameSessionStages(int $ext_game_session_id, array $users = [])
    {
        // here we always need to determine users if $users array is empty
        if(empty($users)) {
            $users = $this->getUsersInvolvedInExtGameSession($ext_game_session_id);

            // session without users does not have any data to send
            if(empty($users)) {
                return false;
            }
        }

        // if is multiplayer than call getMultiPlayerGameSessionStages
        if(count($users) > 1) {
            return $this->sendMultiPlayerGameSessionStages($ext_game_session_id, $users);
        }

        // if is not multiplayer than call getSinglePlayerGameSessionStages()
        if(count($users) == 1) {
            return $this->sendSinglePlayerGameSessionStages($ext_game_session_id, $users[0]);
        }

        return false;
    }

    /**
     * Process single player game execution communication
     *
     * @param int $ext_game_session_id
     * @param int $user_id
     * @return false
     * @throws Exception
     */
    public function sendSinglePlayerGameSessionStages(int $ext_game_session_id, int $user_id)
    {
        $ext_game_session = $this->getExtGameSessionById($ext_game_session_id, ['status_code' => static::STATUS_CODE_NONE]);

        if(empty($ext_game_session)) {
            $this->setGameSessionStatus($ext_game_session_id, static::STATUS_CODE_SENT);
            return false;
        }

        $stages_count = $this->getSinglePlayerGameSessionStagesCount($ext_game_session, $user_id);
        $chunks = (int)ceil($stages_count / static::LIMIT);

        for($chunk = $this->getStartingChunk($ext_game_session); $chunk < $chunks; $chunk++) {
            $offset = $chunk * static::LIMIT;
            $ext_game_session['game_code'] = $this->getGameCode($ext_game_session['ext_game_id']);
            $ext_game_session['game_type'] = $this->getGameType($ext_game_session['ext_game_id']);
            $ext_game_session['stages'] = $this->getSinglePlayerGameSessionStages($ext_game_session_id, $user_id, $offset);
            $ext_game_session['stages_start'] = $offset + 1;
            $ext_game_session['stages_end'] = $offset + count($ext_game_session['stages']);
            $ext_game_session['close'] = ($chunk < $chunks - 1) ? 0 : 1;

            // generate payload
            $payload = $this->generateSinglePlayerPayload($ext_game_session);

            // send message
            $response = $this->it->gameExecutionCommunication($payload);

            if (in_array($response['code'], PgdaReturnCode::retryStatusCodes())) {
                $ext_game_session['status_reason'] = [
                    'code' => $response['code'],
                    'chunk' => $chunk,
                    'tries' => $this->getRetryCounter($ext_game_session) + 1
                ];

                phive('Logger')
                    ->getLogger('pgda_adm')
                    ->error('Error gameExecutionCommunication', [
                        'message' => "Game's {$ext_game_session['game_type']} with code {$ext_game_session['game_code']} execution communication was processed with error: {$ext_game_session['status_reason']['code']} on a stage {$ext_game_session['stages']}. Attempt{$ext_game_session['tries']} ext_game_session: {$ext_game_session['id']}",
                        'user' => $user_id,
                        'ext_game_session' => $ext_game_session,
                    ]);
                break;
            }
            $ext_game_session['status_reason']['code'] = $response['code'];
        }

        // only when certain status code we should retry to send the request
        if(in_array($response['code'], PgdaReturnCode::retryStatusCodes())) {
            // we have to try just a predefined number of times to avoid that this job will be process forever
            if($ext_game_session['status_reason']['tries'] < static::MAX_TRIES) {
                $this->setGameSessionStatus($ext_game_session_id, static::STATUS_CODE_NONE, $ext_game_session['status_reason']);

                phive('Logger')
                    ->getLogger('pgda_adm')
                    ->error('Error gameExecutionCommunication on message 580', [
                        'message' => "Game execution communication was processed with error:
                            {$ext_game_session['status_reason']['code']}. Attempt{$ext_game_session['status_reason']['tries']}
                            ext_game_session: {$ext_game_session['id']}",
                        'user' => $user_id,
                        'ext_game_session' => $ext_game_session,
                    ]);

                phive('Site/Publisher')->single(
                    'pgda',
                    'Licensed',
                    'doLicense',
                    ['IT', 'sendGameSessionCommunication', [$ext_game_session_id, [$user_id]]]
                );

                return false;
            }
            $this->notify('ADM Error on message 580', $ext_game_session);
            return false;
        }

        if ($response['code'] === PgdaReturnCode::SUCCESS_CODE) {
            return $this->setGameSessionStatus($ext_game_session_id, static::STATUS_CODE_SENT);
        }

        return false;
    }

    /**
     * Return the total number of stages made by the player
     *
     * @param array $ext_game_session
     * @param int $user_id
     * @return array|string
     */
    public function getSinglePlayerGameSessionStagesCount(array $ext_game_session, int $user_id)
    {
        if ($ext_game_session['bonus_type'] == 'F') {
            $this->ext_game_participation_bonus = $this->getPlayGameSessionByExtSessionId($ext_game_session['id'], $user_id);
            $stages_count = $this->ext_game_participation_bonus['balance_start'] != $this->ext_game_participation_bonus['balance_end'] ? 1 : 0;

        } else {
            $sql = "SELECT COUNT(*) AS stages_count
            FROM ext_game_participations 
            JOIN ext_game_participations_rounds ON ext_game_participations.id = ext_game_participations_rounds.ext_game_participation_id
            JOIN rounds ON ext_game_participations_rounds.round_id = rounds.id
            WHERE ext_game_participations.external_game_session_id = {$ext_game_session['id']}
            AND rounds.is_finished = true
            LIMIT 1";
            $stages_count = $this->db->sh($user_id)->getValue($sql, 'stages_count');
        }

        return $stages_count;
    }

    /**
     * Return the stages made by the player
     * * Balance not taken directly from participations table as a 1 cent rounding problem was detected on some sessions
     * // TODO only report rounds finished ch106978
     *
     * @param int $ext_game_session_id
     * @param int $user_id
     * @param int $offset
     * @return array|string
     */
    public function getSinglePlayerGameSessionStages(int $ext_game_session_id, int $user_id, int $offset = 0)
    {
        if (!empty($this->ext_game_participation_bonus)) {
            return [$this->ext_game_participation_bonus];
        }

        $sql = "SELECT 
            ext_game_participations.id, 
            ext_game_participations.participation_id, 
            ext_game_participations.stake,
            bets.created_at,
            (bets.balance + bets.amount) AS bet_balance,
            bets.amount AS bet,
            bets.bonus_bet AS bet_bonus,
            (bets.balance + IFNULL(wins.amount, 0)) AS win_balance,
            IFNULL(wins.amount, 0) AS win,
            IFNULL(wins.bonus_bet, 0) AS win_bonus,
            (bets.amount - IFNULL(wins.amount, 0)) * 10000 AS net
        FROM ext_game_participations 
        JOIN ext_game_participations_rounds ON ext_game_participations.id = ext_game_participations_rounds.ext_game_participation_id
        JOIN rounds ON ext_game_participations_rounds.round_id = rounds.id
        JOIN bets ON rounds.bet_id = bets.id
        LEFT JOIN wins ON rounds.win_id = wins.id
        WHERE ext_game_participations.external_game_session_id = {$ext_game_session_id}
        AND rounds.is_finished = true
        ORDER BY ext_game_participations_rounds.id
        LIMIT " . static::LIMIT . " OFFSET {$offset}";

        return $this->db->sh($user_id)->loadArray($sql);
    }

    /**
     * Process multiplayer player game execution communication
     *
     * @param int $ext_game_session_id
     * @param array $users
     * @return false
     * @throws Exception
     */
    public function sendMultiPlayerGameSessionStages(int $ext_game_session_id, array $users = [])
    {
        // not yet implemented
        return false;
    }

    /**
     * Generate message payload
     *
     * @param array $ext_game_session
     * @return array
     * @throws Exception
     */
    public function generateSinglePlayerPayload(array $ext_game_session)
    {
        // ADM JUST FOR 580 AND 590 WANT THE DATE IN CET.
        $cet = new DateTime($ext_game_session['created_at']);
        $cet->setTimezone(new DateTimeZone(static::TIMEZONE));

        $payload = [
            'game_code' => $ext_game_session['game_code'],
            'game_type' => $ext_game_session['game_type'],
            'session_id' => $ext_game_session['ext_session_id'],
            'initial_progressive_number' => $ext_game_session['stages_start'],
            'last_progressive_number' => $ext_game_session['stages_end'],
            'stage_date' => $cet->format('Ymd'),
            'flag_closing_day' => $ext_game_session['close'],
            'game_stages' => $this->generateSinglePlayerStagesPayload($ext_game_session)
        ];

        return $payload;
    }

    /**
     * Generate single player stages payload
     *
     * @param array $ext_game_session
     * @return array
     */
    public function generateSinglePlayerStagesPayload(array $ext_game_session)
    {
        if (!empty($this->ext_game_participation_bonus)) {
            return $this->generateFreespinSinglePlayerStagesPayload();
        }

        return array_map(fn($stage, $j) => [
            'total_taxable_amount' => $stage['net'],
            'stage_progressive_number' => $ext_game_session['stages_start'] + $j,
            'datetime' => date('YmdHis', strtotime($stage['created_at'])),
            'players' => [
                [
                    'identifier' => $stage['participation_id'],
                    'amount_available' => $stage['win_balance'],
                    'amount_returned' => $stage['win'],
                    'bet_amount' => $stage['bet'],
                    'taxable_amount' => $stage['net'],
                    'license_code' => $this->it->config()['id_cn'],
                    'jackpot_amount' => 0,
                    'amount_available_real_bonuses' => ($ext_game_session['bonus_type'] == 'B') ? $stage['bet_balance'] : 0,
                    'amount_available_play_bonuses' => 0,
                    'amount_waged_real_bonuses' => ($ext_game_session['bonus_type'] == 'B') ? $stage['bet'] : 0,
                    'amount_staked_resulting_play_bonuses' => 0,
                    'amount_returned_real_bonuses' => ($ext_game_session['bonus_type'] == 'B') ? $stage['win'] : 0,
                    'amount_returned_play_bonuses' => 0,
                    'amount_returned_resulting_jackpots' => 0, // ($ext_game_session['jackpot'] == 1) ? $stage['jackpot'] : 0
                    'amount_returned_resulting_additional_jackpots' => 0, // ($ext_game_session['jackpot_extra'] == 1) ? $stage['jackpot'] : 0
                ]
            ]],
            $ext_game_session['stages'], array_keys($ext_game_session['stages']));
    }

    /**
     * Generate Play Bonus single player stages payload
     *
     * @return array
     */
    public function generateFreespinSinglePlayerStagesPayload()
    {
        $bet_amount = $this->ext_game_participation_bonus['balance_start'] - ($this->ext_game_participation_bonus['balance_end'] - $this->ext_game_participation_bonus['won_amount']);
        return [
            [
                'total_taxable_amount' => 0,
                'stage_progressive_number' => 1,
                'datetime' => date('YmdHis', strtotime($this->ext_game_participation_bonus['datetime'])),
                'players' => [
                    [
                        'identifier' => $this->ext_game_participation_bonus['participation_id'],
                        'amount_available' => $this->ext_game_participation_bonus['balance_end'],
                        'amount_returned' => $this->ext_game_participation_bonus['won_amount'],
                        'bet_amount' => $bet_amount,
                        'taxable_amount' => 0,
                        'license_code' => $this->it->config()['id_cn'],
                        'jackpot_amount' => 0,
                        'amount_available_real_bonuses' => 0,
                        'amount_available_play_bonuses' => $this->ext_game_participation_bonus['balance_end'],
                        'amount_waged_real_bonuses' => 0,
                        'amount_staked_resulting_play_bonuses' => $bet_amount,
                        'amount_returned_real_bonuses' => 0,
                        'amount_returned_play_bonuses' => $this->ext_game_participation_bonus['won_amount'],
                        'amount_returned_resulting_jackpots' => 0,
                        'amount_returned_resulting_additional_jackpots' => 0,
                    ]
                ]
            ]
        ];
    }
}
