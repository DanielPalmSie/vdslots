<?php

class NotificationEventHandler
{
    private Tournament $t;
    private $casino;
    private $db;

    /**
     *
     */
    public function __construct()
    {
        $this->db = phive('SQL');
        $this->t = phive('Tournament');
        $this->casino = phive('Casino');
    }


    /**
     * Updates the tournament list on the Battle of Slots leaderboard.
     * Sends a tournament created message to the stream processing platform.
     *
     * @param int $tid tournament id
     */
    public function onTournamentCreatedEvent($tid)
    {
        $this->t->wsTournamentCreation($tid);
        $this->t->propagateTournamentCreationMessage((int) $tid);
    }

    /**
     * When a user registers, updates the leaderboard and the lobby information (registered players, tournament status)
     *
     * @param $tournament
     * @param $uid
     * @param $new_eid
     * @param $inc_pot_with
     */
    public function onTournamentRegisterUserEvent($tournament, $uid, $new_eid, $inc_pot_with)
    {
        if ($tournament['status'] == 'late.registration') { //
            $this->t->wsOnChange($new_eid, $tournament, 0, 'main', $uid, true);
        }
        if (!empty($new_eid)) {

            $tid = $tournament['id'];
            $this->t->deleteTournamentCache($tid);
            $tournament = $this->t->byId($tid, false);
            $tournament_data = [
              'enrolled_user' => $this->t->displayRegs($tournament),
              'status' => $tournament['status'],
             ];
            if ($tournament['start_format'] === 'sng' && $tournament['status'] === 'in.progress') {
                $tournament_data['start_time'] = $tournament['start_time'];
            }
            $this->t->wsTmainLobby($tid, $tournament_data);
        }
    }

    /**
     * Updates the leaderboard when the tournament entry changes
     *
     * @param string $eid
     * @param string $t
     * @param int $made_rebuy
     * @param string $lobby_tag
     * @param string $uid
     * @param false $force_update
     */
    public function onTournamentEntryUpdatedEvent($eid = "", $t = '', $made_rebuy = 0, $lobby_tag = 'main', $uid = '', $force_update = false, $entry = null, $tourney = null)
    {
        $this->t->wsOnChange($eid, $t, $made_rebuy, $lobby_tag, $uid, $force_update, $entry, $tourney);
    }

    /**
     * Send the start tournament notification
     *
     * @param $tournament
     * @param $t_entry
     */
    public function onTournamentStartEvent($tournament, $t_entry)
    {
        $this->t->startWs($tournament, $t_entry);
        uEvent('mpstart', '', $tournament['tournament_name'], $tournament['id'], $t_entry['user_id']);
    }

    /**
     * Generic event handler for sending updates to the frontend when some playing limit is reached
     *
     * @param $user_id
     * @param $preferred_lang
     * @param $limit
     * @param $game_ref
     * @param $go_home
     * @param $t_eid
     */
    public function onCasinoLimitEvent($user_id, $preferred_lang, $limit, $game_ref, $go_home, $t_eid)
    {
        if (hasWs()) {
            $this->casino->wsLga($user_id, $preferred_lang, $limit, $game_ref, $go_home, $t_eid);
        }
    }

    /**
     * When a trophy is awarded we send a notification to the frontend
     * @param $tag
     * @param string $amount
     * @param string $name
     * @param string $url
     * @param array $ud
     * @param string $img
     * @param bool $show_in_feed
     */
    public function onTrophyAwardEvent($tag, $amount = '', $name = '', $url = '', $ud = [], $img = '', $show_in_feed = true)
    {
        uEvent($tag, $amount, $name, $url, $ud, $img, $show_in_feed);
    }

    /**
     * Log  slow game  request reply (when we take too long to reply to the provider)
     * @param $insert
     */
    public function onCasinoSlowReplyEvent($insert)
    {
        $setting = phive()->getSetting('slow_game_replies_logger', 'db');
        if($setting === 'db' || $setting === 'both') {
            $this->db->insertArray('slow_game_replies', $insert);
        }
        if($setting === 'file' || $setting === 'both'){
            phive('Logger')->getLogger('slow_game_replies')->log(json_encode($insert));
        }
    }

}
