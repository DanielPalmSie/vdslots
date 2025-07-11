<?php
namespace IT\Services;

use DateTime;
use DateTimeZone;
use IT\Pgda\Codes\ReturnCode as PgdaReturnCode;
use IT\Services\Traits\InteractWithMail;

/**
 * Class GameSessionsAlignmentCommunicationService
 * @package IT\Services
 */
class GameSessionsAlignmentCommunicationService extends ArchiveManagementService
{
    use InteractWithMail;

    /**
     * indicating status not yet processed
     *
     * @var int
     */
    const STATUS_CODE_NODE = 1;

    /**
     * indicating status 580 already sent
     *
     * @var int
     */
    const STATUS_CODE_SENT = 2;

    /**
     * Stores info about game sessions with freespins
     * @var array
     */
    private $ext_game_participation_bonus = [];

    /**
     * send game session execution
     * @todo here is very hard to have users.. maybe is better to remove e as parameter
     *
     * @param int $ext_game_session_id
     * @param array $users
     * @return false|void
     * @throws \Exception
     */
    public function sendGameSessionAlignment(int $ext_game_session_id, array $users = [])
    {
        // here we always need to determine users if $users array is empty
        if(empty($users)) {
            $users = $this->getUsersInvolvedInExtGameSession($ext_game_session_id);

            // session without users does not have any data to send
            if(empty($users)) {
                return false;
            }
        }

        // if is multiplayer than call getMultiPlayerGameSessionAlignment
        if(count($users) > 1 || empty($users)) {
            return $this->sendMultiPlayerGameSessionAlignment($ext_game_session_id, $users);
        }

        // if is not multiplayer than call getSinglePlayerGameSessionAlignment
        if(count($users) == 1) {
            return $this->sendSinglePlayerGameSessionAlignment($ext_game_session_id, $users[0]);
        }

        return false;
    }

    /**
     * Process single player game execution communication
     *
     * @param int $ext_game_session_id
     * @param int $user_id
     * @return false
     * @throws \Exception
     */
    public function sendSinglePlayerGameSessionAlignment(int $ext_game_session_id, int $user_id)
    {
        $ext_game_session = $this->getExtGameSessionById($ext_game_session_id, ['status_code' => static::STATUS_CODE_NODE]);

        if(empty($ext_game_session)) {
            $this->setGameSessionStatus($ext_game_session_id, static::STATUS_CODE_SENT);
            phive('Logger')
                ->getLogger('pgda_adm')
                ->error('External game session is empty', [
                    'message' => "External game session {$ext_game_session_id} is empty.",
                    'status' => static::STATUS_CODE_SENT
                ]);
            return false;
        }

        $tries = $this->getRetryCounter($ext_game_session);

        if($tries > static::MAX_TRIES) {
            // TODO: should be uncomment after solving infinite processing loop issue and flooding queues
//            phive('Logger')
//                ->getLogger('pgda_adm')
//                ->error('Reached maximum attempts', [
//                    'message' => "Attempt {$tries} for external game session {$ext_game_session_id}. Reached maximum ",
//                    'user' => $user_id,
//                    'ext_game_session' => $ext_game_session,
//                ]);
            return false;
        }

        $data = $this->getSinglePlayerGameSessionAlignemnt($ext_game_session, $user_id);

        if(empty($data)) {
            // TODO: should be uncomment after solving infinite processing loop issue and flooding queues
//            phive('Logger')
//                ->getLogger('pgda_adm')
//                ->warning('Receiving data for sendSinglePlayerGameSessionAlignment.', [
//                    'message' => "Data for user {$user_id} with external game session {$ext_game_session_id} wasnt being received. Attempt {$tries}",
//                    'ext_game_session' => $ext_game_session,
//                ]);
            return false;
        }

        $payload = $this->getSinglePlayerGameSessionAlignemntPayload($ext_game_session, $data);

        // send message
        $response = $this->it->gameSessionsAlignmentCommunication($payload);

        if ($response['code'] !== PgdaReturnCode::SUCCESS_CODE) {
            phive('Logger')
                ->getLogger('pgda_adm')
                ->error('ADM Error on message 590', [
                    'message' => "Response of payload for external game session {$ext_game_session_id} was returned with {$response['code']}. ",
                    'request' => $payload,
                    'response' => $response
                ]);
            $this->notify('ADM Error on message 590', compact('payload', 'response'));
            $this->setGameSessionStatus($ext_game_session_id, static::STATUS_CODE_NODE, [
                'code' => $response['code'],
                'tries' => $tries + 1
            ]);
            return false;
        }

        return $this->setGameSessionStatus($ext_game_session_id, static::STATUS_CODE_SENT);
    }

    /**
     * Return the session alignment data
     * // TODO only report rounds finished ch106978
     * @param array $ext_game_session_id
     * @param int $user_id
     * @return array|string
     */
    public function getSinglePlayerGameSessionAlignemnt(array $ext_game_session, int $user_id)
    {
        if ($ext_game_session['bonus_type'] == 'F') {
            $this->ext_game_participation_bonus = $this->getPlayGameSessionByExtSessionId($ext_game_session['id'], $user_id);
            return $this->ext_game_participation_bonus['balance_start'] != $this->ext_game_participation_bonus['balance_end'] ? $this->ext_game_participation_bonus : [];
        }

        $sql = "SELECT
            ext_game_participations.user_id,
            ext_game_participations.stake,
	        COUNT(ext_game_participations_rounds.id) AS bets_count,
            IFNULL(SUM(bets.amount), 0) AS bet,
            IFNULL(SUM(wins.amount), 0) AS win,
            IFNULL(SUM(bets.amount), 0) - IFNULL(SUM(wins.amount), 0)  AS net
        FROM ext_game_participations
        JOIN ext_game_participations_rounds ON ext_game_participations.id = ext_game_participations_rounds.ext_game_participation_id
        JOIN rounds ON ext_game_participations_rounds.round_id = rounds.id
        JOIN bets ON rounds.bet_id = bets.id
        LEFT JOIN wins ON rounds.win_id = wins.id
        WHERE ext_game_participations.external_game_session_id = {$ext_game_session['id']}
        AND rounds.is_finished = true
        GROUP BY ext_game_participations.id";

        return $this->db->sh($user_id)->loadAssoc($sql);
    }

    /**
     * Process multiplayer player game execution communication
     *
     * @param int $ext_game_session_id
     * @param array $users
     * @return false
     * @throws \Exception
     */
    public function sendMultiPlayerGameSessionAlignment(int $ext_game_session_id, array $users = [])
    {
        // not yet implemented
        return false;
    }

    /**
     * Generate the payload for Game Session Alignment
     *
     * @param $ext_game_session
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function getSinglePlayerGameSessionAlignemntPayload($ext_game_session, array $data)
    {
        if (!empty($this->ext_game_participation_bonus)) {
            return $this->getSinglePlayerFreespinGameSessionAlignemntPayload($ext_game_session);
        }

        // ADM JUST FOR 580 AND 590 WANT THE DATE IN CET.
        $cet = new DateTime($ext_game_session['created_at']);
        $cet->setTimezone(new DateTimeZone(static::TIMEZONE));

        $amount_returned_as_real_bonus = 0;

        return [
            'game_code' => $this->getGameCode($ext_game_session['ext_game_id']),
            'game_type' => $this->getGameType($ext_game_session['ext_game_id']),
            'central_system_session_id' => $ext_game_session['ext_session_id'],
            'reference_date' => $cet->format('dmY'),
            'total_number_stages_played' => $data['bets_count'],
            'number_stages_completed' => $data['bets_count'],
            'round_up_list' => [
                [
                    'license_code' => $this->it->config()['id_cn'],
                    'total_amounts_waged' => $data['bet'],
                    'total_amounts_returned' => $data['win'] + $amount_returned_as_real_bonus,
                    'total_taxable_amount' => $data['net'] - $amount_returned_as_real_bonus,
                    'total_mount_returned_resulting_jackpot' => 0,
                    'total_mount_returned_resulting_additional_jackpot' => 0,
                    'jackpot_amount' => 0,
                    'total_amount_waged_real_bonuses' => ($ext_game_session['has_play_bonus'] == 'B') ? $data['bet'] : 0,
                    'total_amount_waged_play_bonuses' => 0,
                    'total_amount_returned_real_bonuses' => ($ext_game_session['has_play_bonus'] == 'B') ? $data['bet'] : 0,
                    'total_amount_returned_play_bonuses' => 0,
                ]
            ]
        ];
    }

    /**
     * Generate the payload for Play Bonus Game Session Alignment
     *
     * @param $ext_game_session
     * @return array
     * @throws \Exception
     */
    private function getSinglePlayerFreespinGameSessionAlignemntPayload($ext_game_session)
    {
        $bets_count = $this->ext_game_participation_bonus['balance_start'] != $this->ext_game_participation_bonus['balance_end'] ? 1 : 0;
        $bet_amount = $this->ext_game_participation_bonus['balance_start'] - ($this->ext_game_participation_bonus['balance_end'] - $this->ext_game_participation_bonus['won_amount']);

        return [
            'game_code' => $this->getGameCode($ext_game_session['ext_game_id']),
            'game_type' => $this->getGameType($ext_game_session['ext_game_id']),
            'central_system_session_id' => $ext_game_session['ext_session_id'],
            'reference_date' => date('dmY', strtotime($ext_game_session['created_at'])),
            'total_number_stages_played' => $bets_count,
            'number_stages_completed' => $bets_count,
            'round_up_list' => [
                [
                    'license_code' => $this->it->config()['id_cn'],
                    'total_amounts_waged' => $bet_amount,
                    'total_amounts_returned' => $this->ext_game_participation_bonus['won_amount'],
                    'total_taxable_amount' => 0,
                    'total_mount_returned_resulting_jackpot' => 0,
                    'total_mount_returned_resulting_additional_jackpot' => 0,
                    'jackpot_amount' => 0,
                    'total_amount_waged_real_bonuses' => 0,
                    'total_amount_waged_play_bonuses' => $bet_amount,
                    'total_amount_returned_real_bonuses' => 0,
                    'total_amount_returned_play_bonuses' => $this->ext_game_participation_bonus['won_amount'],
                ]
            ]
        ];
    }
}
