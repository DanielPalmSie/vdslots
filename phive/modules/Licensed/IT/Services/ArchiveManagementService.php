<?php


namespace IT\Services;

use IT;
use Exception;
use SQL;

abstract class ArchiveManagementService
{
    /**
     * The game type used
     *
     * @todo we should store this parameter somewhere else maybe config or db
     * @var int
     */
    const GAME_TYPE = 2;

    /**
     * indicating how many times we have to try to send the message
     *
     * @var int
     */
    const MAX_TRIES = 3;

    /**
     * The timezone to be used to calculate proper CST + DST time for 580 and 590
     *
     * @see GameExecutionCommunicationService
     * @see GameSessionsAlignmentCommunicationService
     * @var string
     */
    const TIMEZONE = 'Europe/Rome';

    /**
     * Licensed IT
     *
     * @var IT
     */
    protected IT $it;

    /**
     * The phive('SQL') instance
     *
     * @var SQL
     */
    protected SQL $db;

    /**
     * GameExecutionCommunicationService constructor.
     *
     * @param IT $it
     */
    public function __construct(IT $it)
    {
        $this->it = $it;
        $this->db = phive('SQL');
    }

    /**
     * Return the game code from adm point of view
     *
     * @param int $ext_game_id
     * @return int|string
     * @throws Exception
     */
    public function getGameCode($ext_game_id)
    {
        return $this->db->getValue(NULL,'game_regulatory_code', 'game_country_versions', [
            'ext_game_id' => $ext_game_id,
            'country' => 'IT'
        ]);
    }

    /**
     * Return the game type from adm point of view
     * TODO: try to understand from where we have to take this parameter instead of using hardcoded one
     *
     * @param int $ext_game_id
     * @return int|string
     * @throws Exception
     */
    public function getGameType($ext_game_id)
    {
        return static::GAME_TYPE;
    }

    /**
     * Get the external session record by id
     *
     * @param int $ext_game_session_id
     * @return mixed
     */
    public function getExtGameSessionById(int $ext_game_session_id, array $where = [])
    {
        $ext_game_session = $this->db->loadAssoc(NULL, 'ext_game_sessions', array_merge($where, ['id' => $ext_game_session_id]));

        if(!empty($ext_game_session)) {
            $ext_game_session['status_reason'] = json_decode($ext_game_session['status_reason'], true);

            return $ext_game_session;
        }

        return [];
    }

    /**
     * Get an array of users belongs to the session
     *
     * @param int $ext_game_session_id
     * @return array|string
     */
    public function getUsersInvolvedInExtGameSession(int $ext_game_session_id)
    {
        return $this->db->shs()
            ->load1DArr("SELECT user_id FROM ext_game_participations WHERE external_game_session_id = {$ext_game_session_id}", 'user_id');
    }

    /**
     * Set the game session status code and reason
     *
     * @param int $id
     * @param int $status
     * @param array|null $status_reason
     * @return bool
     */
    public function setGameSessionStatus(int $id, int $status_code, array $status_reason = null)
    {
        if(is_array($status_reason)) {
            $status_reason = json_encode($status_reason);
        }

        return $this->db->save('ext_game_sessions', compact('status_code', 'status_reason'), compact('id'), null, false, false);
    }

    /**
     * Get the starting chunk checking the status reason column first
     *
     * @param array $ext_game_session
     * @return int|mixed
     */
    protected function getStartingChunk(array $ext_game_session)
    {
        return (!empty($ext_game_session['status_reason'])) ? $ext_game_session['status_reason']['chunk'] : 0;
    }

    /**
     * Get the retry counter checking the status reason column first
     *
     * @param array $ext_game_session
     * @return int|mixed
     */
    protected function getRetryCounter(array $ext_game_session)
    {
        return (!empty($ext_game_session['status_reason'])) ? $ext_game_session['status_reason']['tries'] : 0;
    }

    /**
     * Get the necessary information about Free Play bonuses for reporting to ADM
     *
     * @param int $ext_game_session_id
     * @param int $user_id
     * @return array|false|string
     */
    protected function getPlayGameSessionByExtSessionId(int $ext_game_session_id, int $user_id)
    {
        $sql = "SELECT bon.*,  DATE_ADD(part.created_at, INTERVAL 1 second) AS datetime, part.participation_id 
                FROM ext_game_participations_bonuses bon, ext_game_participations part 
                WHERE  part.external_game_session_id = {$ext_game_session_id} AND bon.ext_game_participation_id = part.id LIMIT 1";

        return $this->db->sh($user_id)->loadAssoc($sql);
    }
}