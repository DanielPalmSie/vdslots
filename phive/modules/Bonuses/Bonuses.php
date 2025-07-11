<?php
require_once __DIR__ . '/../../api/PhModule.php';

/**
 * The basic bonus class, it is the base class that is powering both Casino
 * and Affiliate logic / sites.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_bonus_types The wiki docs for the bonus_types (AKA bonus table) table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_bonus_entries The wiki docs for the bonus_entries table.
 */
class Bonuses extends PhModule {

    /**
     * Wrapper around updateArray().
     *
     * @uses SQL::updateArray()
     * @see SQL::updateArray()
     *
     * @param int $entry_id The entry id, used in the WHERE clause of the UPDATE statement.
     * @param array $updates The columns and values to update.
     * @param int $uid The user id.
     *
     * @return bool True if the SQL query was sucessful, false otherwise.
     */
    public function editBonusEntry($entry_id, $updates, $uid){
        
        $updates = $this->addGameSessionId($entry_id, $updates, $uid);
        return phive("SQL")->sh($uid, '', 'bonus_entries')->updateArray('bonus_entries', $updates, "id = ".(int)$entry_id);
    }

    /**
     * Delets a bonus entry.
     *
     * @uses SQL::delete()
     *
     * @param array $entry The entry to delete.
     *
     * @return bool True if the SQL query was sucessful, false otherwise.
     */
    public function deleteBonusEntry($entry){
        return phive("SQL")->delete("bonus_entries", ['id' => $entry['id']], $entry['user_id']);
    }

    /**
     * @param $entry
     * @param $status
     * @return mixed
     */
    public function changeBonusEntryStatus($entry, $status)
    {
        return phive("SQL")->sh($entry['user_id'])->updateArray("bonus_entries", array( 'status' => $status ), array( 'id' => $entry['id'] ) );
    }


    /**
     * Gets a bonus_types row by way of the bonus id.
     *
     * @param int $bonus_id The bonus id.
     *
     * @return array The bonus.
     */
    function getBonus($bonus_id){
        $bonus_id = intval($bonus_id);
        if(empty($bonus_id))
            return array();
        return phive("SQL")->loadAssoc("SELECT * FROM ".$this->getSetting("types")." WHERE id = $bonus_id");
    }

    // TODO henrik remove?
    function getBonusByBonusName($bonus_name){
        if(empty($bonus_name)){
            return [];
        }
        return phive("SQL")->loadAssoc("SELECT * FROM ".$this->getSetting("types")." WHERE bonus_name = " . phive("SQL")->escape($bonus_name));
    }

    // TODO henrik remove
    public function getBonuses($show_expired = true){
        $where = " WHERE 1 ";
        if(!$show_expired)
            $where .= " AND expire_time >= '".date("Y-m-d")."' ";
        phive("SQL")->query("SELECT * FROM ".$this->getSetting("types").$where);
        return phive("SQL")->fetchArray();
    }

    /**
     * Adds activation information to a bonus entry.
     *
     * This method adds stuff like start time, end time to the entry, plus changes the entry status to active.
     *
     * @param array &$inserts The entry to be activated, passed as a reference.
     * @param array $bonus The entry's bonus.
     * @param int $uid User id.
     * @param string $start_time Start date override, defaults to today if left out.
     *
     * @return bool True if the entry was activated, false otherwise.
     */
    function addActivation(&$inserts, $bonus, $uid, $start_time = ''){
        $res = true;
        if(!$this->canActivate($bonus, $uid))
            $res = false;

        if($res){
            $start_time = empty($start_time) ? date('Y-m-d') : $start_time;
            $inserts['start_time'] 	 = $start_time;
            $inserts['end_time'] 	 = date("Y-m-d",strtotime("+ ".$bonus['num_days']." days"));
            $inserts['activated_time'] = phive()->hisNow();
            $inserts['status'] 	 = "active";
            return true;
        }else{
            $inserts['status'] 	 = "pending";
            return false;
        }
    }

    /**
     * Gets all active bonus entries for a particular user.
     *
     * @param int $uid The user id.
     *
     * @return array The array of entries.
     */
    public function getActiveBonusEntries($uid){
        $uid = intval($uid);
        return phive("SQL")->sh($uid)->loadArray("SELECT * FROM bonus_entries WHERE status = 'active' AND user_id = $uid");
    }

    /**
     * Check if user can get XP points based on active bonuses
     * 
     * @param int $uid The user id.
     * @return bool 
     */
    public function isUserAllowToGetXP(int $uid) {
        return (bool)phive("SQL")->sh($uid)->loadAssoc(
            "SELECT 
                CASE
                    WHEN COUNT(*) = 0 THEN true
                    WHEN COUNT(*) = 1 THEN MAX(bt.allow_xp_calc)
                    WHEN SUM(bt.allow_xp_calc) = COUNT(*) THEN true
                    WHEN SUM(bt.allow_xp_calc) > 0 AND 
                         MAX(CASE 
                             WHEN bt.allow_xp_calc = 1 AND bt.bonus_type = 'casinowager' AND bt.rake_percent > 0 
                             THEN 1 
                             ELSE 0 
                         END) = 1 THEN true
                    ELSE false
                END AS can_get_xp
            FROM bonus_entries AS be
            JOIN bonus_types AS bt ON be.bonus_id = bt.id
            WHERE be.status = 'active' AND be.user_id = $uid"
        )['can_get_xp'];
    }

    /**
     * Gets a bonus entry by id.
     *
     * @param int $entry_id The bonus entry id.
     * @param int $uid The user id, is needed to correctly select the node to query.
     *
     * @return array The entry.
     */
    public function getBonusEntry($entry_id, $uid = ''){
        $uid = (int)$uid;
        $entry_id = intval($entry_id);
        $str = "SELECT * FROM bonus_entries WHERE `id` = $entry_id ";
        if(empty($uid))
            return phive("SQL")->shs('merge', '', null, 'bonus_entries')->loadAssoc($str);
        return phive('SQL')->sh($uid, '', 'bonus_entries')->loadAssoc($str);
    }

    /**
     * Gets all entries with bonus types inner joined for a user.
     *
     * @param int $user_id The user id.
     *
     * @return array The result array.
     */
    function getUserBonuses($user_id){
        $user_id = intval($user_id);
        return phive("SQL")->sh($user_id, '', 'bonus_entries')->loadArray("SELECT be.*, bt.bonus_name FROM bonus_entries be, bonus_types bt WHERE user_id = $user_id AND be.bonus_id = bt.id");
    }

    /**
     * Gets all entries with bonuses for specific period of time
     *
     * @param $user_id
     * @param $start_date
     * @param $end_date
     * @return mixed
     */
    function getUserBonusesForPeriod($user_id, $start_date = '', $end_date = ''){
        if(!$start_date){
            $start_date = 'DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
        }

        if(!$end_date){
            $end_date = 'CURDATE()';
        }

        $sql = "SELECT            be.id                                       AS entry_id,
                                  bt.id                                       AS bonus_id,
                                  bt.bonus_type                               AS bonus_type,
                                  bt.bonus_name                               AS bonus,
                                  IF(be.status = 'failed', 0, SUM(ct.amount)) AS bonus_amount,
                                  be.reward                                   AS bonus_reward,
                                  be.activated_time                           AS activation_time,
                                  be.last_change                              AS last_change,
                                  be.status                                   AS bonus_status,
                                  be.cost                                     AS wager_req,
                                  be.progress / be.cost * 100                 AS progress
                                    FROM bonus_entries be
                                      LEFT JOIN bonus_types bt ON be.bonus_id = bt.id
                                      LEFT JOIN cash_transactions ct ON be.id = ct.entry_id
                                    WHERE be.user_id = $user_id AND be.activated_time BETWEEN
                                    '$start_date' AND '$end_date' AND be.status = 'approved'
                                    GROUP BY be.id
                                    ORDER BY be.last_change DESC";

        return phive("SQL")->sh($user_id, '', 'bonus_entries')->loadArray($sql);
    }

    // TODO henrik remove
    // This query has many issues, need to fix or remove. GROUP by is before WHERE clause, join has problem
    function getUserBonusesWithComments($user_id){
        $user_id = intval($user_id);
        return phive("SQL")->sh($user_id, '', 'bonus_entries')->loadArray("
        SELECT be.*, bt.bonus_name, uc.comment as comment
        FROM bonus_entries AS be
        LEFT JOIN bonus_types AS bt ON bt.id = be.bonus_id AND bt.bonus_type = be.bonus_type
        LEFT JOIN users_comments AS uc ON uc.uc.foreign_id = be.id GROUP by bonus_id
        WHERE bt.user_id = $user_id AND uc.tag = 'bonus_entries'");
    }

    // TODO henrik remove
    function getLoyalty($u, $game, $cobj){
        $game = is_string($game) ? phive('MicroGames')->getByGameRef($game) : $game;
        $uid = $u->getId();
        $has_deposited = phive('Cashier')->hasDeposited($uid);
        if(!$has_deposited)
            return 0;
        $loyalty_p = $this->getLoyaltyPercentByRef($game['ext_game_name'], $uid);
        $game_rtp  = empty($game['payout_percent']) ? 0.99 : $game['payout_percent'];
        if(empty($loyalty_p))
            return 0;
        return ($u->getLoyaltyDeal() * (1 - $game_rtp) * 1) * $cobj->getMulti($uid, $u, 'cashback-multiply');
    }

    /**
     * Gets active freespins for a user
     *
     * @param $user_id
     * @param null|string $bonus_id Used to get activated free spins
     * @return array|string
     */
    public function getActiveFreespinsBonusEntry($user_id, $bonus_id = null)
    {
        $status = "AND be.status = 'active'";
        $uid = intval($user_id);
        if (!empty($bonus_id)) {
            $status = "AND be.status in ('active', 'approved') AND frb_remaining > 0";
            $bonus_id = "AND be.bonus_id = $bonus_id";
        } else {
            $bonus_id = '';
        }
        return phive("SQL")->readOnly()->sh($uid, '', 'bonus_entries')->loadArray("
            SELECT
                be.*,
                bt.frb_denomination,
                bt.frb_lines,
                bt.rake_percent,
                bt.frb_coins,
                bt.game_id
            FROM bonus_entries be
            INNER JOIN bonus_types bt ON bt.id = be.bonus_id
            WHERE be.user_id = {$uid} {$bonus_id} {$status} AND be.bonus_type = 'freespin'
        ");
    }

    /**
     * Get bonus entry data by user ID and either gameId (with GP prefix) or bonusEntryId
     * Send last activated free spins when activated-free-spins is set
     *
     * @param int $user_id The user ID
     * @param mixed $game_id bonus_entries:game_id (with GP prefix), bonus_entries:ext_id (with GP prefix) or the bonus_entries:id
     * @return array
     */
    public function getBonusEntryByGameIdAndFreeSpinsRemaining($user_id, $game_id)
    {
        $active_freespins = $this->getActiveFreespinsBonusEntry($user_id, phMgetShard('activated-free-spins', $user_id));

        if (empty($active_freespins)) {
            $active_freespins = $this->getActiveFreespinsBonusEntry($user_id);
        }

        // Check if game_id is string or array
        $game_id = $game_id['game_id'] ?? $game_id;
        foreach ($active_freespins as $bonus_entry) {
            if (($bonus_entry['game_id'] == $game_id || strpos($bonus_entry['ext_id'], $game_id) !== false) && $bonus_entry['frb_remaining'] > 0) {
                return $bonus_entry;
            }
        }
        return [];
    }
    
    private function addGameSessionId($entry_id, $updates, $uid)
    {
        if ((isset($updates['frb_remaining']) && $updates['frb_remaining'] == 0) || (isset($updates['status']) && $updates['status'] == 'failed')) {

            $session_id  = $this->fetchGameSessionIdByEntryId($entry_id, $uid);
            if ($session_id) {
                $updates['game_session_id'] = $session_id;
            }
        }
        
        return $updates;
    }

    public function fetchGameSessionIdByEntryId($entry_id, $uid)
    {
        $entry = $this->getBonusEntry($entry_id);
        $bonus = $this->getBonus($entry['bonus_id']);
        $game = phive('MicroGames')->getByGameId($bonus['game_id'], '');

        return phive('CasinoBonuses')->getLastGameSessionId($uid, $game['ext_game_name']);
    }

    /**
     * Retrieves the appropriate bonus game for a mobile device,
     * as some mobile and desktop games have different game_id for the same game.
     * If no specific mobile game is found, it returns the original game.
     *
     * @param array $game The game data, typically containing details about the game like `device_type_num`.
     *
     * @return array The game data, either the original game or a desktop version of the game if applicable.
     */
    public function getBonusGameForMobileDevice(array $game): array
    {
        if (!empty($game['device_type_num'])) {
            $desktop_game = phive('MicroGames')->getDesktopGame($game);
            return !empty($desktop_game) ? $desktop_game :  $game;
        }
        return $game;
    }

}
