<?php
require_once __DIR__ . '/../../api/PhModule.php';

/**
 * This is the class that is powering the trophies.
 *
 * TODO henrik fix all sh() and shs() calls.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_trophies
 * @link https://wiki.videoslots.com/index.php?title=DB_table_trophy_award_ownership
 * @link https://wiki.videoslots.com/index.php?title=DB_table_trophy_awards
 * @link https://wiki.videoslots.com/index.php?title=DB_table_trophy_events
 *
*/
class Trophy extends PhModule{

    /**
     * @var string The table name in on central place which makes it trivial to change if need be.
     */
    public $tbl = 'trophies';

    /**
     * @var array A cache array with user rows.
     */
    public $uds = [];

    // TODO henrik remove
    function __construct(){
        $this->tbl = 'trophies';
    }

    /**
     * FE helper that returns the URI to the trophy image / thumbnail.
     *
     * @param array $t The trophy row we want the image for.
     *
     * @return string The URI.
     */
    function getTrophyUri($t){
        if(empty($t))
            return fupUri("events/trophy_placeholder.png", true);
        else
            return fupUri("events".(empty($t['finished']) ? '/grey' : '')."/{$t['alias']}_event.png", true);
    }

    /**
     * Gets a trophy event with the help of a user and a trophy.
     *
     * @param array $t The trophy row.
     * @param array $ud The user row.
     *
     * @return array The trophy event row.
     */
    function getEvent($t, $ud){
        return phive('SQL')->sh($ud, 'id')->loadAssoc('', 'trophy_events', array('user_id' => $ud['id'], 'trophy_id' => $t['id']));
    }

    // TODO henrik remove
    function getEvents($t, $finished = null){
        if($finished !== null)
            $where_finished = "AND finished = $finished";
        return phive('SQL')->shs()->loadArray("SELECT * FROM trophy_events WHERE trophy_id = {$t['id']} $where_finished");
    }

    /**
     * Checks if the completed trophy for the specific game has been completed, if that is the case it means
     * that all trophies in that game's group have been completed.
     *
     * TODO henrik rename this to something more specific.
     * TODO henrik return bool instead.
     * TODO henrik sanitize gref.
     *
     * @param int $uid The user id.
     * @param string $gref The game reference / ext game name.
     *
     * @return array
     */
    function hasCompleted($uid, $gref){
        return $this->getEventByType('completed', $uid, 'game_ref', "AND finished = 1 AND game_ref = '$gref'");
    }

    /**
     * Cron logic that resets game related groups of trophies so that they can be completed again.
     *
     * @param string $start_stamp Optional stamp cutoff, no trophies older than this are considered.
     *
     * @return null
     */
    function resetCron($start_stamp = null){

        $start_stamp = $start_stamp ?? phive()->hisMod('-1 day');

        phive('SQL')->loopShardsSynced(function($db) use($start_stamp){

            // We get all finished trophies of type completed
            $str = "SELECT * FROM trophy_events
                    WHERE created_at > '$start_stamp'
                    AND trophy_type = 'completed'
                    AND finished = 1";

            $tes = $db->loadArray($str);

            foreach($tes as &$te){
                $this->resetTrophies($te['user_id'], $te['game_ref']);
            }
        });
    }

    /**
     * Resets game related groups of trophies so that they can be completed again.
     *
     * @param int $uid The user id.
     * @param string $gref The game ref / ext game name.
     *
     * @return null
     */
    function resetTrophies($uid, $gref){
        $uid = intval($uid);
        $evs = phive('SQL')->sh($uid)->load1DArr("SELECT * FROM trophy_events WHERE game_ref = '$gref' AND user_id = $uid", 'trophy_id');
        $evs_in = phive('SQL')->makeIn($evs);
        phive('UserHandler')->logAction($uid, "Reseted all trophies in $gref", 'trophy');

        // No need for delete batched here as the amount of trophies / game isn't that big.
        $events = phive('SQL')->sh($uid)->loadArray("SELECT * FROM trophy_events WHERE user_id = $uid AND trophy_id IN($evs_in)");
        foreach($events as $ev){
            phive('SQL')->delete('trophy_events', ['id' => $ev['id']], $uid);
        }
    }

    /**
     * FE helper that returns the URI to the trophy award image / thumbnail.
     *
     * @param array $a The trophy award row we want the image for.
     * @param DBUser $user The user object to work with.
     *
     * @return string The URI.
     */
    function getAwardUri($a, $user){
        $fname = empty($a) ? 'reward_placeholder' : "{$this->awardEventImg($a, $user)}_event";
        return fupUri("events/$fname.png", true);
    }

    // TODO henrik remove
    function getAllAwards(){
        return phive('SQL')->loadArray("SELECT * FROM trophy_awards");
    }

    /**
     * Deposit event hook in order to determine progress etc of deposit related trophies. Note that
     * it's possible to create trophies that progress only when deposits happen via a specific PSP
     * which makes it possible to incentivize deposits via certain PSPs.
     *
     * Do we have a network connected to the current PSP? If yes we use the PSP as the sub type, NOT the network.
     * This is due to how things look in the calling context as well as the cashier config. Casino::depositCash() might pass
     * adyen as network and VISA as the PSP, in that case we're only interested in VISA.
     *
     * However, if the PSP is missing we can't get the network and have to assume that the PSP / sub PSP is the network, eg paysafecard.
     *
     * @used-by Casino::depositCash()
     *
     * @param DBUser $u_obj The user object.
     * @param string $psp_network Payment Service Provider (PSP) network.
     * @param string $psp Payment Service Provider (PSP).
     *
     * @return null
     */
    function onDeposit($u_obj, $psp_network = '', $psp = ''){
        $ud       = ud($u_obj);
        $sub_type = $psp_network;

        if(!empty(phive('Cashier')->getPspNetwork($u_obj, $psp))){
            $sub_type = $psp;
        }
        $ts = array_filter($this->getActiveTrophies($ud, '', 'deposit'), function($t) use($sub_type){
            // We're looking at a typical deposit trophy or a deposit trophy for the current psp.
            return empty($t['subtype']) || $t['subtype'] == $sub_type;
        });
        foreach($ts as $t){
            $this->awardOrProgress($t, $ud, 1, '', false, 0);
        }
    }

    /**
     * Various generic event trophies are handled via this method, eg on KYC verify or login etc.
     *
     * @param string $type The event type, eg login.
     * @param mixed $uid The user identifying element.
     * @param int $delay A delay in microseconds, this is to allow misc. events to complete before the websocket notification shows.
     *
     * @return null
     */
    function onEvent($type, $uid = '', $delay = 0){
        $ud = empty($uid) ? cu()->data : cu($uid)->data;
        $ts = $this->getActiveTrophies($ud, '', $type);
        foreach($ts as $t){
            $this->awardOrProgress($t, $ud, 1, '', false, $delay);
        }
    }

    /**
     * Deleting events is necessary in order to reset a situation so that the users can start fresh, a prime
     * example are trophies that require several wins in a row, if that does not happen the event is deleted.
     *
     * @param array $ev_ids An array of event ids that should **not** be deleted.
     * @param string $type Trophy type to delete.
     * @param string $end_stamp If given the updated at stamp of the events need to be prior to this stamp.
     * @param string $where Extra WHERE clauses.
     *
     * @return null
     */
    function deleteEvents($ev_ids, $type, $end_stamp = '', $where = "AND in_row = 1"){
        $ev_ids = array_filter($ev_ids);
        if(!empty($end_stamp))
            $end_where = " AND updated_at < '$end_stamp'";
        else if(!empty($ev_ids)){
            $in = phive('SQL')->makeIn($ev_ids);
            $where_in = "AND id NOT IN($in)";
        }
        $now = phive()->hisNow();
        // TODO henrik refactor this into something that does not lock the table
        $str = "UPDATE trophy_events SET progr = 0, updated_at = '$now' WHERE trophy_type = '$type' AND finished = 0 $where $end_where $where_in";
        phive('SQL')->shs('')->query($str);
    }

    /**
     * Helper to generate a unique key for in memory arrays of trophy events that need to be progressed
     * or awarded in cron jobs. For example of the type win X amount of money playing game Y in one hour.
     *
     * @param int $uid User id.
     * @param array $r Typically a bet or win row.
     * @param string $key Concatenation of the type and and category, eg winday.spins.
     * @param string $field An arbitrary trophy event field, eg game_ref.
     *
     * @return string The compound key, eg 1213343.winday.spins.game_ref.mgs_cops_and_robbers
     */
    function getPeriodKey($uid, $r, $key, $field = ''){
        if(empty($field))
            return "$uid.$key";
        return "$uid.$key.$field.{$r[$field]}";
    }


    /**
     * Helper for generating data for cron jobs that work with trophy events that need to be progressed
     * or awarded in cron jobs. For example of the type win X amount of money playing game Y in one hour.
     *
     * @param int $sh_num The id of the shard to work with.
     * @param string|array $sql SQL or rows to work with, if SQL it will be executed to generate the rows.
     * @param string $type The type, eg betday.
     * @param string $field The category to work with, eg spins.
     * @param string $dynamic_field An arbitrary trophy event field, eg game_ref.
     * @param string $suffix Table suffix, eg _mp for doing BoS.
     * @param string|bool $use_key If string we use this key for each row in the array of rows to work with, eg user_id.
     *
     * @return null
     */
    function doPeriod($sh_num, $sql, $type, $field, $dynamic_field = '', $suffix = '', $use_key = false){
        $rows     = is_array($sql) ? $sql : phive('SQL')->sh($sh_num)->loadArray($sql, 'ASSOC', $use_key);

        //if(empty($rows))
        //    return;

        $key = "$type.$field";

        if($suffix == '_mp'){
            $eids = phive()->arrCol(phive()->uniqByKey($rows, 'e_id'), 'e_id');
            if (!empty($eids)) {
                $es   = phive('Tournament')->getEntriesByIds($eids);
                foreach($rows as $r){
                    $period_key = $this->getPeriodKey($r['user_id'], $r, $key, $dynamic_field);
                    if((int)$es[$r['e_id']]['get_trophy'] === 1)
                        $this->mp_rows[$period_key][] = $r;
                }
            }
        }else{
            foreach($rows as $r){
                $period_key = $this->getPeriodKey($r['user_id'], $r, $key, $dynamic_field);
                //print_r([$r, $period_key]);
                $this->normal_rows[$period_key][] = $r;
            }
        }

    }

    /**
     * Main cron entry point for for progression of trophies of for instance the type win X amount of money playing game Y in one hour.
     *
     * @param int $sh_num The id of the shard to work with.
     * @param bool $set_progress Whether or not to set the progress to the actual amount (true) or increase the progress with the amount (false).
     * @param string $now Explicitly set the current timestamp, if empty Phive::hisNow() will be used.
     * @param string $hour_ago Explicitly set the timestamp, if empty Phive::hisNow() will be used.
     * @param string $day_ago Explicitly set the timestamp, if empty the current day with 00:00:00 as the H:i:s part will be used.
     *
     * @return null
     */
    function minuteCron($sh_num, $set_progress = true, $now = '', $hour_ago = '', $day_ago = ''){
        if(hasMp()){
            $mp = phive('Tournament');
            $this->minuteCronInit($sh_num, '_mp', $now, $hour_ago, $day_ago);
        }
        $this->minuteCronInit($sh_num, '', $now, $hour_ago, $day_ago);

        $keys = array_merge((array)array_keys($this->mp_rows), (array)array_keys($this->normal_rows));

        foreach(array_unique($keys) as $key){

            list($uid, $type, $field, $dynamic_field, $dynamic_value) = explode('.', $key);

            $mp_rows = (array)$this->mp_rows[$key];
            if(hasMp()){
                //We need to change money on the battle rows from EUR to target
                foreach($mp_rows as &$mp_row){
                    if($field != 'spins')
                        $mp_row[$field] = $mp->chgToUsr(cu($mp_row['user_id']), $mp_row[$field], 1);
                }
            }

            $rows    = (array)$this->normal_rows[$key];

            phive()->addCol2d($mp_rows, $rows, $field, true, true, true, true);

            $tally   = [];

            foreach($rows as $key => $r){
                $cg = '';
                $ud = ud($r['user_id']);
                if($dynamic_field == 'game_ref'){
                    //We have a mobile game so we fetch the desktop version
                    $cg = !empty($r['device_type'])
                        ? phive('SQL')->loadAssoc("SELECT * FROM micro_games WHERE mobile_id IN (SELECT id FROM micro_games WHERE ext_game_name = '$dynamic_value')")
                        : array('ext_game_name' => $dynamic_value, 'device_type_num' => $r['device_type']);

                    if(empty($cg)){
                        continue;
                    }
                }

                //We need to potentially add upp both mobile and desktop games to get the correct amount
                //If we don't have a trophy targeting a specific game
                $tally[$r['user_id'].$cg['ext_game_name']] += $r[$field];

                foreach($this->getActiveTrophies($ud, $cg, $type, '', true, '', $sh_num) as $t){
                    $thold = $t['subtype'] == 'money' ? mc($t['threshold'], $r['currency']) : $t['threshold'];
                    //$amount = $r[$field];
                    $amount = $tally[$r['user_id'].$cg['ext_game_name']];
                    //We achieved the trophy so we will award it
                    if($thold <= $amount){
                        $this->awardTrophy($t, $ud);
                        $this->updateEvent($t, $ud, $amount, 1);
                    }else{

                        $this->updateEvent($t, $ud, $amount, '', $set_progress);
                    }
                }
            }
        }
    }

    /**
     * Helper for generating data for cron jobs that work with trophy events that need to be progressed
     * or awarded in cron jobs. For example of the type win X amount of money playing game Y in one hour.
     *
     * @used-by Trophy::minuteCron()
     *
     * @param int $sh_num The id of the shard to work with.
     * @param string $suffix Table suffix, eg _mp for doing BoS.
     * @param string $now Explicitly set the current timestamp, if empty Phive::hisNow() will be used.
     * @param string $hour_ago Explicitly set the timestamp, if empty Phive::hisNow() will be used.
     * @param string $day_ago Explicitly set the timestamp, if empty the current day with 00:00:00 as the H:i:s part will be used.
     *
     * @return null
     */
    function minuteCronInit($sh_num, $suffix = '', $now = '', $hour_ago = '', $day_ago = ''){

        $now      = empty($now)      ? phive()->hisNow()          : $now;
        $hour_ago = empty($hour_ago) ? phive()->hisMod('-1 hour') : $hour_ago;
        $day_ago  = empty($day_ago)  ? date('Y-m-d 00:00:00')     : $day_ago;

        //bet hour doesn't exist yet
        //win day with money doesn't exist yet
        //bet day with money doesn't exist yet
        //win hour with spins doesn't exist yet

        //TODO turn this on if we want to show progress for the below trophy types
        //phive('SQL')->query("UPDATE trophy_events SET progr = 0 WHERE finished != 1 AND trophy_type IN('betday', 'winhour', 'winday')");

        //*
        $this->doPeriod(
            $sh_num,
            "SELECT *, COUNT(*) AS spins FROM bets{$suffix} WHERE created_at >= '$day_ago' AND created_at <= '$now' AND bonus_bet = 0 GROUP BY user_id",
            'betday',
            'spins',
            '',
            $suffix,
            'user_id'
        );

        $this->doPeriod(
            $sh_num,
            "SELECT *, SUM(amount) AS amount_sum FROM wins{$suffix} WHERE created_at >= '$hour_ago' AND created_at <= '$now' AND award_type = 2 AND bonus_bet = 0 GROUP BY user_id",
            'winhour',
            'amount_sum',
            '',
            $suffix,
            'user_id'
        );
        //*/

        $grouped = phive()->countGroup(
            phive('SQL')->sh($sh_num)->loadArray("SELECT * FROM wins{$suffix} WHERE created_at >= '$day_ago' AND created_at <= '$now' AND award_type = 2 AND bonus_bet = 0"),
            'user_id',
            'game_ref',
            'spins'
        );

        //Only works with windays connected to specific game, if a general winday is needed we need to add a general call below
        foreach($grouped as $uid => $rows){
            $this->doPeriod($sh_num, $rows, 'winday', 'spins', 'game_ref', $suffix);
        }
    }

    /**
     * Gets the count of the trophies in the completed ids column, say we have 1,2,3 there and the user has finished / achieved
     * 1 and 2 then this method will return 2 in the cnt column.
     *
     * @used-by Trophy::completeCron()
     *
     * @param array $t The trophy that needs x other trophies to be completed in order to be achieved itself.
     * @param int $sh_num The shard number to work with.
     *
     * @return array The result array.
     */
    function getCompletedCount($t, $sh_num = false){
        $ids     = phive()->explode($t['completed_ids']);
        $ids_str = phive('SQL')->makeIn($ids);

        $sql     = "SELECT user_id, COUNT(*) AS cnt FROM trophy_events WHERE trophy_id IN($ids_str) AND finished = 1 GROUP BY user_id";
        return phive('SQL')->sh($sh_num)->loadArray($sql, 'ASSOC', 'user_id');
    }

    /**
     * Gets a user from the cache, this caching function is used during cron jobs to avoid unecessary queries to get users.
     *
     * @param int $uid Id of user to get.
     *
     * @return array The user row.
     */
    function usrCacheGet($uid){
        if(!empty($this->uds[$uid]))
            return $this->uds[$uid];
        $this->uds[$uid] = ud($uid);
        return $this->uds[$uid];
    }


    /**
     * This method determines if the final **completed** trophy in any game group of trophies should be awarded.
     *
     * With regards to $delete_comp, pretend we have 10 trophies in a category and the user has completed all and been awarded the completed trophy
     * for that category and then an 11th trophy is added in the cateogry, then the user can't be said to have completed all trophies. This
     * boolean controls whether ot not to remove the completed trophy in that case, typically no (false) as it would mean that users
     * would get the award twice in this scenario simply by managing to complete the single new 11th trophy which could bet
     * expensive very quickly.
     *
     * @param int $sh_num Shard number for correct DB selection.
     * @param bool $delete_cnomp See method description, should typically be false.
     *
     * @return null
     */
    function completeCron($sh_num = false, $delete_comp = false){

		$now = \Carbon\Carbon::now();
	    $cronAlias = 'completeCron_' . $sh_num;
		$executedAt = phive('SQL')->getValue("", 'executed_at' , 'cron_last_execution_times', "cron_alias='{$cronAlias}'");
		$executedAtFilterTime = !$executedAt ? \Carbon\Carbon::now()->subDay() : $executedAt;

		$sql_str   = "SELECT * FROM trophies WHERE type = 'completed' AND completed_ids != '' {$this->getValiditySql()}";
        $ts        = phive('SQL')->lb()->loadArray($sql_str);
        $this->uds = [];
        foreach($ts as &$t){
            $owns = $this->getCompletedCount($t, $sh_num);
            foreach($owns as $uid => &$own){
                $ud = $this->usrCacheGet($uid);
                $cur_cnt = (int)$own['cnt'];
                $this->awardOrProgress($t, $ud, $cur_cnt, '', false, 100, true);
            }
        }

        //We get the count of all game related trophies
        $ts_str = "SELECT COUNT(*) AS cnt, game_ref FROM trophies
            WHERE game_ref IN(SELECT game_ref FROM trophies WHERE type = 'completed' AND game_ref != '')
            AND type != 'completed' AND category LIKE 'games%' GROUP BY game_ref";

        $ts  = phive('SQL')->sh($sh_num)->loadArray($ts_str);

        $test_str = "SELECT te.user_id, COUNT(*) AS cnt, t.sub_category, t.game_ref
                      FROM trophy_events te
                      INNER JOIN trophies t ON te.finished = 1 AND te.trophy_id = t.id
                      WHERE t.type != 'completed' AND te.updated_at > '$executedAtFilterTime'
                      GROUP BY te.user_id, t.game_ref, t.sub_category";

        //Getting the count of all finished non-complete and all non bigwin trophies
        $tes_data = phive('SQL')->sh($sh_num)->loadArray($test_str);
        foreach($ts as $t){
              if(empty($t['cnt']))
                  continue;


              // Filter and get array of data by game_ref and sub_category
              $tes = array_filter($tes_data, function ($v) use($t) {
                 return ($v['sub_category'] == $t['game_ref'] && $v['game_ref'] == $t['game_ref']);
              });

              foreach($tes as $te){
                  $ud = $this->usrCacheGet($te['user_id']);

                  $comp_str = "
                      SELECT t.*, town.finished FROM trophies t
                      LEFT JOIN trophy_events AS town ON town.trophy_id = t.id AND town.user_id = {$te['user_id']}
                      WHERE t.game_ref = '{$t['game_ref']}'
                      AND t.type = 'completed'";

                  $comp = phive('SQL')->sh($sh_num)->loadAssoc($comp_str);

                  //If we already own the completed trophy and we don't want to delete it we move on
                  if(!empty($comp['finished']) && $delete_comp == false)
                      continue;

                  //We compare the count of all finished non-complete and non-bigwin trophies with how many of that type there exists in the trophies table
                  if( (int)$t['cnt'] === (int)$te['cnt'] && !empty($comp)){
                      $this->awardTrophy($comp, $ud);
                      $this->updateEvent($comp, $ud, $te['cnt'], true);
                  }else{
                      if($delete_comp && !empty($comp) && (int)$t['cnt'] !== (int)$te['cnt'])
                          $comp['finished'] = 0;
                      $comp['threshold'] = $t['cnt'];
                      $this->updateEvent($comp, $ud, $te['cnt'], '', true);
                  }
              }
          }

		phive('SQL')->insertOrUpdate('cron_last_execution_times', [
			'cron_alias' => "{$cronAlias}"
		], [
			'cron_alias' => "{$cronAlias}",
			'executed_at' => $now
		]);
      }

    /**
     * Gets events of a certain type for a specific user.
     *
     * TODO henrik sanitize uid.
     *
     * @param string $type The type, eg **completed**.
     * @param int $uid The user id.
     * @param string $key Which trophy_events column to use as the main array key.
     * @param string $where_ext Optional extra WHERE clauses.
     *
     * @return array The result array.
     */
    function getEventByType($type, $uid, $key, $where_ext = ''){
        $str = "SELECT * FROM trophy_events WHERE trophy_type = '$type' AND user_id = $uid $where_ext";
        return phive('SQL')->sh($uid)->loadArray($str, 'ASSOC', $key);
    }

    // TODO henrik remove
    function getWithEvent($te){
        return phive('SQL')->arrayWhere("trophies", array('id' => $te['trophy_id']));
    }

    // TODO henrik replace into the calling context.
    function getWithTypeThold($type, $thold = null){
        return phive('SQL')->arrayWhere("trophies", array('type' => $type, 'threshold' => $thold), false, true);
    }

    /**
     * Logic to handle awarding race related trophies, should run when a race finishes.
     *
     * @param array &$race The race to work with.
     *
     * @return null
     */
    function raceFin(&$race){
        $ev_ids = array();
        foreach(phive('Race')->leaderBoard($race, true) as $e){
            $ud = cu($e['user_id'])->data;
            foreach($this->getActiveTrophies($ud, '', 'race') as $t){
                $ev_id = 0;
                if((int)$t['subtype'] === 0){
                    $ev_id = $this->awardOrProgress($t, $ud);
                }else{
                    $interv = explode('-', $t['subtype']);
                    if(count($interv) == 2 && in_array($e['spot'], range($interv[0], $interv[1])))
                        $ev_id = $this->awardOrProgress($t, $ud, 1);
                    else if($e['spot'] == $interv[0])
                        $ev_id = $this->awardOrProgress($t, $ud, 1);
                }
                if(!empty($t['in_row']))
                    $ev_ids[] = empty($t['event_id']) ? $ev_id : $t['event_id'];
            }
        }
        $this->deleteEvents($ev_ids, 'race');
    }

    /**
     * Helper for dayCron(), will award or progress trophies and also delete progression in case of
     * failing to complete X events in a row trophies.
     *
     * @used-by Trophy::dayCron()
     *
     * @param string $uid_sql SQL statement that will be used to get an array of user ids.
     * @param string $type The trophy type (trophies.type).
     * @param int $in_row 0 if we do not want to work with in row trophies 1 otherwise (ie trophies.in_row).
     * @param array $uids An array of user ids to work with.
     *
     * @return array The array of user ids that was worked with.
     */
    function dayCronCommon($uid_sql, $type, $in_row = 0, $uids = array()){
        if(empty($uids))
            $uids = phive('SQL')->load1DArr($uid_sql, 'user_id');
        $in_row_sql    = "AND t.in_row = $in_row";
        $ev_ids        = array();
        $updated_stamp = phive()->hisNow();
        foreach($uids as $uid){
            $ud = ud($uid);
            foreach($this->getActiveTrophies($ud, array(), $type, $in_row_sql) as $t){
                $this->awardOrProgress($t, $ud, 1);
            }
        }
        if($in_row === 1)
            $this->deleteEvents($ev_ids, $type, $updated_stamp);
        return $uids;
    }

    /**
     * Daily cron to determine trophy progress.
     *
     * @param string $date The date to calculate for, typically today.
     *
     * @return null
     */
    function dayCron($date){
        $this->dayCronCommon("SELECT * FROM bets_tmp GROUP BY user_id", 'play', 1);
        error_log("TROPHY 1");
        $uids = $this->dayCronCommon("SELECT * FROM users_daily_stats WHERE gen_loyalty > 0 AND `date` = '$date'", 'cashback');
        error_log("TROPHY 2");
        $this->dayCronCommon('', 'cashbackearn', 1, $uids);
        error_log("TROPHY 3");
        $this->dayCronCommon('', 'cashbackearn', 0, $uids);
        error_log("TROPHY 4");
        $str = "
            SELECT user_id, gen_loyalty FROM users_daily_stats
            WHERE `date` = '$date'
            AND gen_loyalty > 0";
        $earned = phive('SQL')->lb()->loadArray($str);
        error_log("TROPHY 5");
        foreach($earned as $r){
            $ud = ud($r['user_id']);
            foreach($this->getActiveTrophies($ud, array(), 'cashbackmoney-earn') as $t){
                $this->awardOrProgress($t, $ud, $r['gen_loyalty'], $ud['currency']);
            }
        }
        error_log("TROPHY 6");
    }

    /**
     * Handles progression of trophies that are connected to a payout of for instance loyalty, ie
     * earn loyalty 3 weeks in a row or something like that.
     *
     * @param array $tids An array of queued transaction ids that are to be paid out.
     *
     * @return null
     */
    function payoutEvent($tids){
        $ev_ids = array();
        foreach($tids as $tid){
            $trans = phive('Cashier')->getTrans($tid, 'queued_transactions');
            if(empty($trans))
                continue;
            if($trans['transactiontype'] != 31)
                return false;
            $ud = ud($trans['user_id']);
            foreach($this->getActiveTrophies($ud, '', 'cashback') as $t){
                $ev_id = 0;
                switch($t['subtype']){
                    case 'paid':
                        $ev_id = $this->awardOrProgress($t, $ud);
                        break;
                    case 'money-paid':
                        $ev_id = $this->awardOrProgress($t, $ud, $trans['amount'], $ud['currency']);
                        break;
                    default:
                        break;
                }
                if(!empty($t['in_row']))
                    $ev_ids[] = empty($t['event_id']) ? $ev_id : $t['event_id'];
            }
        }
        $this->deleteEvents($ev_ids, 'cashback');
    }

    /**
     * Resets in row trophies in case the in row requirement failed, ie win 3 times in a row and we only got
     * two wins. Typically the trophy progression needs to reset immediately if that happens, we can't wait
     * for a cron to pick up on it.
     *
     * @param array &$ud User data array / row.
     * @param array &$cg Current game.
     * @param string $type Trophy type.
     *
     * @return null
     */
    function resetInRow(&$ud, &$cg, $type = 'win'){
        $device_num = min($cg['device_type_num'], 1);
        $where_game = !empty($cg) ? "AND game_ref = '{$cg['ext_game_name']}'" : "AND game_ref = ''";
        $now = phive()->hisNow();
        $str = "SELECT id, trophy_id FROM trophy_events
                WHERE
                    trophy_type = '$type'
                    AND finished = 0
                    AND in_row = 1
                    $where_game
                    AND user_id = {$ud['id']}
                    AND progr != 0
                    ";
        $rows = phive('SQL')->sh($ud, 'id')->loadArray($str);
        foreach($rows as $te){
            phive('SQL')->sh($ud, 'id')->updateArray('trophy_events', array('progr' => 0, 'updated_at' => $now), array('id' => $te['id']));
            $this->wsOnUpdate($te['trophy_id'], $ud, 0);
        }
    }

    /**
     * This logic is part of the base functionality that makes the win X amount of times in game Y possible.
     *
     * We run this on every bet and we store **bet** as the current action and if the prior action
     * also was bet it means there was no win in between the bets so all the win X trophies for the game
     * in question need to be reset to start over.
     *
     * @param array &$ud The user row to work with.
     * @param array &$cg The game the bet is wagered in.
     *
     * @return null
     */
    function memOnBet(&$ud, &$cg){
        //We don't support win in row in all games
        $cur_action = $this->memGet('curGameAction', $ud, $cg);
        $prior_action = $this->memGet('priorGameAction', $ud, $cg);

        if(!empty($cg) && $cur_action == 'bet' && $prior_action != 'bet') {
            $this->resetInRow($ud, $cg);
        }
        $this->memSetGameAction($ud, $cg, 'bet');
    }

    // TODO henrik remove this.
    function pexecOnBetWinData($uid, $gid){
        return array(ud($uid), phive('MicroGames')->getById($gid));
    }

    // TODO henrik remove this.
    function pexecOnBet($uid, $gid, $amount){
        list($ud, $cg) = $this->pexecOnBetWinData($uid, $gid);
        $this->onBet($ud, $cg, (int)$amount);
    }

    // TODO henrik remove this.
    function pexecOnWin($uid, $gid, $amount){
        usleep(100000);
        list($ud, $cg) = $this->pexecOnBetWinData($uid, $gid);
        $this->onWin($ud, $cg, (int)$amount);
    }

    /**
     * This logic is part of the base functionality that makes the win X amount of times in game Y possible.
     *
     * @uses Trophy::memOnBet() for the Redis storage and in row reset.
     *
     * @param array &$ud The user row to work with.
     * @param array &$cg The game the bet is wagered in.
     * @param int $amount The wager / bet amount.
     */
    function onBet(&$ud, $cg, $amount) {
        $this->isGamePlay = true;
        $cg = phive('MicroGames')->getDesktopGame($cg);
        $this->memOnBet($ud, $cg);
        $this->memSet('curBetAmount', $ud, $cg, $amount);
        if(!empty($min_bet = $this->getSetting('min_bet_eur', 10))) {
            if ((float)chgToDefault($ud['currency'], $amount, 1) < $min_bet) {
                return;
            }
        }
        //TODO if more subtypes than spins this has to change
        foreach ($this->getActiveTrophies($ud, $cg, 'bet') as $t) {
            $this->awardOrProgress($t, $ud, 1, '', false, 500);
        }
    }

    // TODO henrik remove this.
    function getAwardTypes(){
        return phive('SQL')->loadKeyValues("SELECT * FROM trophy_awards GROUP BY type", 'type', 'type');
    }

    // TODO henrik remove this.
    function getAwardsByType($type){
        return phive('SQL')->loadArray("SELECT * FROM trophy_awards WHERE type = '$type'");
    }

    /**
     * Gets a trophy award by primary key / id.
     *
     * @param int $id The id.
     *
     * @return array The award.
     */
    function getAward($id){
        if(is_array($id))
            return $id;
        $id = intval($id);
        return phive('SQL')->loadAssoc("SELECT * FROM trophy_awards WHERE id = $id");

    }

    /**
     * Gets a trophy award with extended information by way of a join with bonus_types and
     * micro_games.
     *
     * @param int $id The id.
     *
     * @return array The result.
     */
    function getExtAward($id){
        $id = (int)$id;
        if(empty($id)){
            error_log('Caught error: id missing for Trophy::getExtAward()');
            // We need an award id, without it we return right away.
            return [];
        }
        $str = "SELECT a.*, bt.rake_percent, bt.reward, mg.game_name FROM trophy_awards a
                LEFT JOIN bonus_types AS bt ON bt.id = a.bonus_id
                LEFT JOIN micro_games AS mg ON mg.game_id = bt.game_id
                WHERE a.id = $id";
        return phive('SQL')->loadAssoc($str);
    }

    /**
     * Gets all of a user's awards by type.
     *
     * @param int $uid User id.
     * @param string $type The type, eg cash, top-up, freespin-bonus.
     *
     * @return array The result array.
     */
	function getAwardOwnershipByType($uid, $type){
        $str = "SELECT town.*, a.* FROM trophy_award_ownership town, trophy_awards a
                WHERE town.user_id = $uid
                    AND town.award_id = a.id
                    AND a.type = '$type'
                    AND town.status = 0
                    ORDER BY town.created_at";
        return phive('SQL')->sh($uid)->loadArray($str);
    }

    /**
     * Gets an award ownership row by way of user id and award id.
     *
     * @param int $uid The user id.
     * @param int $aid The award id.
     * @param string $status Optional status to filter on.
     *
     * @return array The result row.
     */
    function getAwardOwnership($uid, $aid, $status){
        $aid = intval($aid);
        $uid = intval($uid);
        $where_status = $status === null ? '' : " AND status = $status";
        $str = "SELECT * FROM trophy_award_ownership WHERE user_id = $uid AND award_id = $aid $where_status";
        return phive('SQL')->sh($uid)->loadAssoc($str);
    }

    /**
      * Progresses multiplicator awards like loyalty and race multipliers.
      *
      * These type of awards only last for X amount of spins and method is responsible
      * for keeping track of that progress and then retire the award when the spins
      * have been used up.
      *
      * TODO henrik initiate $multi with 1.
      *
      * @param int $uid TODO henrik refactor this method and all invocations to remove the $uid arg, just do $uid = uid($uobj); at the top.
      * @param DBUser &$uobj The user object to progress for.
      * @param string $type The type we want to progress, only one award (and type) at a time can be progressed.
      *
      * @return float The multiplicator to multiply with.
      */
    function progressAward($uid, &$uobj, $type){
        $ss = $uobj->getAllSettings("setting LIKE '%-multiply%'", true);
        if($type != $ss['current-multiply-award-type']['value'])
            return $multi;
        $aid = $ss['current-multiply-award-id']['value'];
        $spinss = $ss["spins-$aid-multiply"];

        if(!empty($spinss['value'])){
            $multi = $ss["$aid-multiply"]['value'];
            $new_val = $spinss['value'] - 1;
            if(empty($new_val))
                phive('Trophy')->deleteActiveAward($uid, $aid, 2);
            else{
                $uobj->setSetting($spinss['setting'], $new_val);
                phive()->pexec('Trophy', 'wsOnBet', array($uid, $aid));
            }
        }
        return $multi;
    }

    /**
     * Gets the trophy progress.
     *
     * @param array $t The trophy whose progress we want to get.
     *
     * @return float A number between 0 and 1 where 1 equals 100%.
     */
    function getTrophyProgress($t){
        if(!empty($t['cnt']) && empty($t['repeatable']))
            return 1;
        $progr = $t['progr'] / $t['evthold'];
        if(empty2($progr))
            return 0;
        return min(max($progr, 0.01), 1);
    }

    /**
     * Gets the current deposit related award's id (typically cash or top-up).
     *
     * Used for deposit awards (ie deposit and get X) in order to get the used award in a context where the user
     * session does not exist (ie PSP notifications of successful deposit).
     *
     * @param mixed $u User identifying entity.
     *
     * @return int The currently used top up award's id.
     */
    function curAwardId($u){
        return mKey($u, 'cur_trophy_award_id');
    }

    /**
     * Sets the current deposit related award's id (typically cash or top-up).
     *
     * Used for deposit awards (ie deposit and get X) in order to get the used award in a context where the user
     * session does not exist (ie PSP notifications of successful deposit).
     *
     * @param int $aid The award id to set.
     * @param mixed $u User identifying entity.
     *
     * @return null.
     */
    function setCurAward($aid, $u = false){
        $u = empty($u) ? uid() : $u;
        phMset($this->curAwardId($u), $aid);
    }

    /**
     * Deletes the current deposit related award's id (typically cash or top-up).
     *
     * Used for deposit awards (ie deposit and get X) in order to get the used award in a context where the user
     * session does not exist (ie PSP notifications of successful deposit).
     *
     * @param mixed $u User identifying entity.
     *
     * @return null.
     */
    function delCurAward($u){
        phMdel($this->curAwardId($u));
    }

    /**
     * Gets the current deposit related award (typically cash or top-up).
     *
     * Used for deposit awards (ie deposit and get X) in order to get the used award in a context where the user
     * session does not exist (ie PSP notifications of successful deposit).
     *
     * @param mixed $u User identifying entity.
     * @param bool $as_arr If true we get and return the whole award, if false we return just the id.
     *
     * @return int|array The award id or the award array.
     */
    function getCurAward($u = null, $as_arr = false){
        $u = empty($u) ? uid() : $u;
        $award_id = phMget($this->curAwardId($u));
        if($as_arr){
            return $this->getAward($award_id);
        }
        return $award_id;
    }

    /**
     * Finishes an active award (eg get X more race progress over Y spins) and deletes its setting.
     *
     * @param int $uid The user id.
     * @param int $aid The trophy award id.
     * @param int $status The new status, typically 2 for finished / used, 1 for inUse
     *
     * @return null
     */
    function deleteActiveAward($uid, $aid, $status = 2){
        if(empty($uid))
            return;
        $data = $this->arrangeDataForDeleteAward($uid, $aid);
        $ao = $this->getAwardOwnership($uid, $aid, 1);
        $this->deleteAwardAndSettings($ao, $data, $aid, $uid, $status);
    }

    /**
     * Arranges data for delete active or non active award
     *
     * @param int $uid The user id.
     * @param int $aid The trophy award id.
     *
     * @return array $data
     */
    function arrangeDataForDeleteAward($uid, $aid){
        $data['u']       = cu($uid);
        $data['info']    = json_decode($data['u']->getSetting("$aid-info", '', true), true);
        $data['type']    = empty($data['info']['main_type']) ? 'multiply' : $data['info']['main_type'];
        $data['sub_key'] = $aid."-".$data['type'];
        return $data;
    }

    /**
     * Delete award and settings
     *
     * @param int $ao award ownership data.
     * @param array $data arranged data by arrangeDataForDeleteAward function.
     * @param int $aid The trophy award id.
     * @param int $uid The user id.
     * @param int $status The new status, typically 0 for noneused, 1 for inUse, 2 for finished / used, 3 for expired.
     *
     * @return null
     */
	function deleteAwardAndSettings($ao, $data, $aid, $uid, $status){
        if (empty($ao)){
            phive()->dumpTbl('expire-error-no-ownership', ['u' => $data['u'], 'aid' => $aid, 'sub_key' => $data['sub_key'], 'info' => $data['info']]);
        } else {
            if(!empty($status))
                $ao['status'] = $status;
            $ao['finished_at'] = phive()->hisNow();
            phive('SQL')->sh($uid)->save('trophy_award_ownership', $ao);
        }

        phive('UserHandler')->deleteSettingsWhere("user_id = $uid AND (setting LIKE '%-{$data['sub_key']}' OR setting='{$data['sub_key']}')" , $uid);
        foreach(array("current-".$data['type']."-award-id", "current-".$data['type']."-award-type", "$aid-info") as $part)
            phive('UserHandler')->deleteSettingsWhere("user_id = $uid AND setting = '$part'", $uid);
        phive()->pexec('Trophy', 'wsOnBet', array($uid, $aid));
    }
    /**
     * Finishes a not activated award.
     *
     * @param int $uid The user id.
     * @param int $aid The trophy award id.
     * @param int $status The new status, typically 2 for finished / used, 0 for non used
     *
     * @return null
     */
    function deleteNonActiveAward($uid, $aid, $status = 2){
        if(empty($uid))
            return;
        $data = $this->arrangeDataForDeleteAward($uid, $aid);
        $ao = $this->getAwardOwnership($uid, $aid, 0);
        $this->deleteAwardAndSettings($ao, $data, $aid, $uid, $status);
    }

    /**
     * Expires all awards whose expiry date is lower than now, a typical cron job task.
     *
     * The statuses are:
     * 0 = unused
     * 1 = in use
     * 2 = used
     * 3 = expired
     *
     * @param int $sh_num Shard id / number to work with.
     * @param string $now Timestamp to use instead of now, useful when testing.
     *
     */
    public function expireAwards($sh_num = false, $now = ''){
        $now = empty($now) ? phive()->hisNow() : $now;
        phive('SQL')->sh($sh_num)->query("UPDATE trophy_award_ownership SET status = 3 WHERE expire_at < '$now' AND `status` NOT IN (2, 3)");
    }

    /**
     * Finishes / closes all active award with the help of the award expiry setting they have.
     *
     * @uses Trophy::deleteActiveAward() in order to finish every individual award.
     *
     * @param string $now Timestamp to use instead of now (can be useful when testing).
     *
     * @return null
     */
    function expireActiveAwards($now = ''){
        $now = empty($now) ? phive()->hisNow() : $now;
        foreach(phive('UserHandler')->rawSettingsWhere("setting LIKE 'awardexp-%' AND value < '$now'") as $s){
            $arr = explode('-', $s['setting']);
            if(empty($arr[0])){
                phive()->dumpTbl('expire-error-user-setting', $s);
                continue;
            }

            $this->deleteActiveAward($s['user_id'], $arr[1], 3);
        }
    }

    /**
     * Determines if a trademark should be displayed in the given game section,
     * ie if the game is trademarked or not.
     *
     * @param array &$sub The trophies connected to the game.
     *
     * @return bool True if trademark should show, false otherwise.
     */
    function subHasTm(&$sub){
        foreach($sub as &$t){
            if(!empty($t['trademark']))
                return true;
        }
        return false;
    }

    /**
     * Gets the setting related to a specific award for a specific user with the help of an extra
     * sub string / key.
     *
     * @param DBUser &$user The user object.
     * @param int $aid The award id.
     * @param string $key The extra string to match at the end of the setting alias, eg **awardexp**.
     *
     * @return string The setting value.
     */
    function awardSetting(&$user, $aid, $key){
        return $user->getSettingsByRegex("$key-$aid-.*")[0]['value'];
    }

    /**
     * Gets an award by bonus code, being able to connect awards to bonus codes enables
     * for instance affiliate specific deposit top ups.
     *
     * @param string $bcode The bonus code.
     *
     * @return array The award.
     */
    function awardByBonusCode($bcode){
        if(empty($bcode))
            return array();
        return phive('SQL')->loadAssoc('', 'trophy_awards', array('bonus_code' => $bcode));
    }

    /**
     * Convert the award data into a string that can be used to extract meaningful award information inside the notifications.
     *
     * @uses Phive::toDualStr() in order to format / serialize the data into a string.
     *
     * @param array $a The award.
     * @param string $currency Needed to properly convert the amount in certain type of awards (Ex. cash)
     * @return string The formatted string.
     */
    function awardAsStr($a, $currency = null)
    {
        if (is_numeric($a)) {
            $a = $this->getAward($a);
        }

        // TODO improve this to get the full list of all the "type" that require a conversion.
        $amount = $a['type'] === 'cash' ? mc($a['amount'], $currency) : $a['amount'];

        return phive()->toDualStr(array(
            'tt' => 'rewardtype.' . $a['type'],
            'amount' => $amount,
            'multiplicator' => $a['multiplicator'],
            'rake_percent' => $a['rake_percent'],
            'game_name' => $a['game_name'],
            'valid_days' => $a['valid_days'],
            'reward' => $a['reward']));
    }

    /**
     * Wrapper around uEvent().
     *
     * @uses uEvent() in order to send the websocket event ot the websocket server.
     *
     * @param int|array $uid If int ud() will be used to get the user row.
     * @param int|array $aid If int Trophy::getAward() will be used to get the award row.
     *
     * @return null
     */
    function giveAwardEvent($uid, $aid){
        $a = is_array($aid) ? $aid : $this->getAward($aid);
        $ud = is_array($uid) ? $uid : ud($uid);
        uEvent('trophyreward', '', $this->awardAsStr($a, $ud['currency']), $ud['username'], $ud, $this->awardEventImg($a, cu($uid)));
    }

    /**
     * In the GUI / FE we have a small award count icon with a number of the total number of awards in the
     * "treasure chest". This method is used to update this number in real time via WS.
     *
     * @uses toWs() in order to send the WS update.
     *
     * @param array &$ud The user row.
     *
     * @return null
     */
    function wsUpdateRewardCount(&$ud){
        if($this->do_ws === false)
            return;

        $where = ['status' => 0];
        // when mobile we add an extra condition cause not all rewards type can be displayed on mobile
        if(phive()->isMobile()) {
            $where['mobile_show'] = 1;
        }

        toWs(array('status0' => phive('Trophy')->getUserAwardCount($ud, $where)), 'rewardcount', $ud['id']);
    }

    /**
     * Determines if an award can be given to the currently logged in player. It does this by way of
     * the bonus_id column to get the bonus which in turn is used to get the game and finally we
     * check if the bame is blocked for the logged in user.
     *
     * @param int $award_id The id of the award to check.
     *
     * @return bool True if the award can ge given, false otherwise.
     */
    function canGiveAward($award_id){
        $a = is_array($award_id) ? $award_id : $this->getAward($award_id);
        if(!empty($a['bonus_id'])){
            $b    = phive('Bonuses')->getBonus($a['bonus_id']);
            $game = phive('MicroGames')->getByGameId($b['game_id']);
            if(!empty($game) && phive('MicroGames')->isBlocked($game)){
                return false;
            }
        }
        return true;
    }

    /**
     * Used to give an award to a user.
     *
     * If the award can be given we insert a new trophy award ownership row and send misc.
     * WS notifications to the user.
     *
     * @see Trophy::canGiveAward() for the basic logic that determines if the award can be given or not.
     *
     * @param array $a The award row.
     * @param array $ud The user row.
     * @param int $delay Delay in microseconds before socket call goes out.
     *
     * @return int The id of the new trophy award ownership row, 0 if the award was not given.
     */
    function giveAward($a, $ud, $delay = 0, $max_per_day = null){
        $ud = ud($ud);
        if(empty($a))
            return 0;
        $a = is_numeric($a) ? $this->getAward($a) : $a;
        if(empty($a))
            return 0;

        $award_check = null;
        $today       = phive()->today();
        $u_obj       = cu($ud);
        
        // TODO, put this in a new column in the trophy awards table? So that we can do e.g: $max_per_day = $max_per_day ?? $a['max_per_day'] /Henrik
        if(!empty($max_per_day)){
            $award_check = $u_obj->getJsonSetting('trophy-daily-awards');

            if(empty($award_check) || $award_check['date'] != $today){
                // The setting is not applicable for today and needs to be reset.
                $award_check = ['date' => $today, 'awards' => [$a['id'] => 0]];
            }

            // Not needed for e.g PHP 7.*, but needed for PHP 8.*
            if(!isset($award_check['awards'][$a['id']])){
                $award_check['awards'][$a['id']] = 0;
            }
            
            if($award_check['awards'][$a['id']] >= $max_per_day){
                // Too many times, we abort.
                return 0;
            }
        }
        
        // We exclude BoS tickets from the bonus checks as bonus_id here refers to the BoS tpl id.
        if(!empty($a['bonus_id']) && !in_array($a['type'], ['mp-freeroll-ticket', 'mp-ticket'])){
            $b = phive('Bonuses')->getBonus($a['bonus_id']);
            if(empty($b)){
                phive()->dumpTbl('reward_missing_bonus', $a, $ud);
                return 0;
            }
            //only works with flash games atm, freespins in exclusive mobile games can't be given
            $g = phive('MicroGames')->getByGameId($b['game_id']);
            if(empty($g) && $a['type'] == 'freespin-bonus'){
                phive()->dumpTbl('reward_missing_game', array($a, $b), $ud);
                return 0;
            }
            if(phive('MicroGames')->isBlocked($g, $ud['country']))
                return 0;
        }

        $new_id = 0;
        if(!empty($a)){
            $expire_at = $a['valid_days'] + lic('getAwardExpiryExtension', [$ud, $a], $ud);
            $new_id = phive('SQL')->sh($ud, 'id')->insertArray('trophy_award_ownership', array(
                'user_id'   => $ud['id'],
                'award_id'  => $a['id'],
                'expire_at' => phive()->hisMod("+$expire_at day")));
        }

        if($this->do_ws !== false){
            if(empty($delay))
                $this->giveAwardEvent($ud, $a);
            else
                phive()->pexec('Trophy', 'giveAwardEvent', array($ud['id'], $a['id']), $delay);

            $this->wsUpdateRewardCount($ud);
        }

        if(!empty($max_per_day) && !empty($new_id)){
            // The award has now been successfully given and we need to handle the bookkeeping.
            $award_check['awards'][$a['id']]++;
            $u_obj->setJsonSetting('trophy-daily-awards', $award_check);
        }
        
        return $new_id;
    }

    /**
     * This method is responsible for increasing internal jackpot values when bets are made.
     *
     * @param array $cur_game The game the bet / wager is on. Its RTP is used to modify the amount the
     * jackpots are increased with, lower RTP results in higher progress.
     * @param DBUser $u User object.
     * @param int $betValue The wager / bet amount.
     *
     * @return null
     */
    function giveJackpotContribution($cur_game, $u, $betValue = 0 ){

        if(empty($betValue))
            return false;

        // get the game RTP value
        $game_rtp = empty($cur_game['payout_percent']) ? 0.995 : $cur_game['payout_percent'];
        $jackpots = phive('DBUserHandler/JpWheel')->getWheelJackpots($u, "AND contribution_share > 0");

        if(empty($jackpots))
            return false;

        // get the jackpot contribution value
        $jp_contribution = (float)phive('Config')->getValue('jp-contribution', 'jackpot-contribution', 0.01);
        $chg_amount_for_jackpots = (1 - $game_rtp) * $jp_contribution * chg($u, phive("Currencer")->baseCur(), $betValue, 1);

        // each jackpot has its own contribution, and gets its share
        foreach ($jackpots as $jackpot) {
            $amount =  ($chg_amount_for_jackpots * $jackpot['contribution_share']);
            // Update value in jackpot table
            phive('SQL')->incrValue('jackpots', 'amount', array('id' => $jackpot['id']), $amount);
        }
    }

    // TODO henrik remove.
    function removeAward($tao_id, $user_id){
        phive('SQL')->delete('trophy_award_ownership', ['id' => $tao_id], $user_id);
    }

    /**
     * In the GUI / FE we have a small award count icon with a number of the total number of awards in the
     * "treasure chest".
     *
     * @see Trophy::wsUpdateRewardCount() wsUpdateRewardCount for how the count is updated in real time.
     *
     * @param DBUser|array $user User object or user row.
     * @param array $where Key => value array used in order to build the WHERE filter.
     *
     * @return int The count.
     */
    function getUserAwardCount($user, $where){
        if(empty($user))
            return false;

        if(!is_string($where)){
            $where['user_id'] = is_object($user) ? $user->getId() : $user['id'];
        }

        $where = phive('SQL')->makeWhere($where);
        $query = "SELECT COUNT(*) FROM trophy_award_ownership LEFT JOIN trophy_awards ON trophy_award_ownership.award_id = trophy_awards.id {$where}";
        return phive('SQL')->sh($user, 'id')->getValue($query);
    }

    /**
     * Gets a user's awards.
     *
     * Note that we join on several tables in order to get auxiliary information that is needed in order to display
     * the awards correctly.
     *
     * @param DBUser $user The user object.
     * @param int $status The award status to show, typically unused awards.
     * @param string $order_by ORDER BY clause.
     * @param int $aid Award id if we only want a specific award.
     * @param $mobile 0 / 1, some rewards should not show in the mobile account view (for instance if they give an FRB in a game which does not have a mobile version).
     * @param array $dates Optional date interval.
     * @param bool $add_comments Whether or not to add related comments to the result or not.
     * @param int $limit The amount of rows to fetch, if empty / 0 the LIMIT clause will not be there at all.
     * @param int $offset The offset in the LIMIT clause in order to enable pagination for instance.
     *
     * @return array The result.
     */
    function getUserAwards($user, $status = " = 0", $order_by = "rewarded_at DESC", $aid = '', $mobile = '', $dates = '', $add_comments = false, $limit = 0, $offset = 0){
        $offset = (int)$offset;
        if(empty($user))
            return [];
        if (!empty($dates) && !empty($dates[1])) $where_time = " AND tao.created_at <= '{$dates[1]}' AND tao.created_at >= '{$dates[0]}' ";
        $where_aid = empty($aid) ? '' : "AND tao.award_id = $aid";
        $where_status = empty($status) ? '' : "AND tao.status $status";
        $where_mobile = $mobile === '' ? '' : "AND a.mobile_show = $mobile";
        $order_by_str = empty($order_by) ? '' : "ORDER BY $order_by";
        $limit_string = phive('SQL')->getLimit($limit, $offset);
        $str = "SELECT tao.id AS tao_id, tao.status, tao.expire_at, tao.finished_at, tao.activated_at, tao.created_at AS rewarded_at, a.*, mg.game_name, bt.reward, bt.rake_percent, be.id as be_id, be.status as be_status FROM trophy_award_ownership tao
                INNER JOIN trophy_awards AS a ON a.id = tao.award_id
                LEFT JOIN bonus_types AS bt ON bt.id = a.bonus_id AND bt.game_id IS NOT NULL
                LEFT JOIN (
                    SELECT game_id, game_name, MIN(id) AS id
                    FROM micro_games
                    GROUP BY game_id
                ) mg ON bt.game_id = mg.game_id
                LEFT JOIN (SELECT * FROM bonus_entries WHERE activated_time <> '0000-00-00 00:00:00' AND user_id = {$user->getId()})
                    AS be ON (be.activated_time = tao.activated_at AND be.bonus_id = bt.id)
                WHERE tao.user_id = {$user->getId()}
                    $where_time
                    $where_mobile
                    $where_aid
                    $where_status
                    $order_by_str
                    $limit_string";


        $arr = phive('SQL')->sh($user, 'id')->loadArray($str, 'ASSOC', 'tao_id');

        if($add_comments){
            $tao_ids  = phive('SQL')->makeIn(array_keys($arr));
            $comments = phive('SQL')->lb()->loadArray("SELECT * FROM users_comments WHERE foreign_id IN($tao_ids) AND user_id = {$user->getId()}", 'ASSOC', 'foreign_id');
            foreach($arr as &$tao)
                $tao['comment'] = $comments[$tao['tao_id']]['comment'];
        }

        $currency = $user->getCurrency();
        foreach($arr as &$row){
            $row['rake_percent'] /= 100;
            if(in_array($row['type'], array('top-up', 'deposit', 'cash')))
                $row['amount'] = mc($row['amount'], $currency);
        }
        return $arr;
    }

    /**
     * @param int $userId
     * @param string $status
     * @param string $order_by
     * @param $mobile
     * @param int $limit
     * @param int $page
     *
     * @return array
     *
     * @api
     */
    public function getUserAwardsForApi(int $userId, string $status = " = 0", string $order_by = "rewarded_at DESC",  $mobile = ' ',  int $limit = 10, int $page = 1): array
    {
        $user = cu($userId);
        $offset = $page <= 1 ? 0 : ($page - 1) * $limit;

        $awards = $this->getUserAwards(
            $user,
            $status,
            $order_by,
            '',
            $mobile,
            '',
            false,
            $limit,
            $offset);

        foreach ($awards as $k => $item) {
            $awards[$k]['image'] = $this->getAwardUri($item, $user);
        }

        return $awards;
    }

    /**
     * @api
     *
     * @param int $userId
     *
     * @return array
     */
    public function getUserActivatedAwards(int $userId): array
    {
        $result = [];
        $user = cu($userId);
        $settings = $this->getAllCurrent($user);

        foreach ($settings as $setting) {
            $awards = $this->getUserAwards($user, ' != 0', '', $setting['value']);
            $award = array_pop($awards);
            if (count($award) > 0) {
                $details = $this->getRewardInUseDetails($user, $award);
                $award['image'] = $this->getAwardUri($award, $user);
                $award['spins'] = $details['spins'];
                $award['progress'] = (int) $details['progress'];
                $award['expire_date'] = $details['exp_date'];
                $result[] = $award;
            }
        }

        return $result;
    }

    /**
     * @api
     *
     * @param int $userId
     *
     * @return array
     */
    public function getUserActivatedBonus(int $userId): array
    {
        $result = [];
        $user = cu($userId);
        $current_bonus = phive('Bonuses')->getCurrentActive($user);
        $trophy_box = phive('BoxHandler')->getRawBox('TrophyListBox');
        $trophy_box->init($user, true);

        if(!empty($current_bonus)) {
            $data = $trophy_box->printBonus($current_bonus, true, true);
            $result = $data['b'];
            $result['bonus_name'] = pt(empty($result['bonus_name']) ? phive('Bonuses')->nameById($result['bonus_id']) : $result['bonus_name']);
            $result['image'] = phive('Bonuses')->doPic($current_bonus, true);
            $result['show_retry'] = $data['show_retry'] ?? false;
            $result['progress'] = (string) $data['progress'];
            $result['cash_progress'] = (string) $data['cash_progress'];
            $result['has_ongoing_session'] = $data['has_ongoing_session'];
        }

        return $result;
    }

    /**
     * @param int $userId
     * @param int $limit
     * @param int $page
     *
     * @return array
     *
     * @api
     */
    public function getUserAwardsHistoryForApi(int $userId, int $limit = 0, int $page = 1): array
    {
        $user = cu($userId);
        $offset = $page <= 1 ? 0 : ($page - 1) * $limit;

        $awards = $this->getUserAwards(
            $user,
            ' != 0',
            'tao.created_at DESC',
            '',
            '1',
            '',
            false,
            $limit,
            $offset
        );

        foreach ($awards as $k => $item) {
            $awards[$k]['image'] = $this->getAwardUri($item, $user);
            $awards[$k]['status_text'] = t("reward.status.{$item['status']}");
            $awards[$k]['info'] = rep(tAssoc("rewardtype.{$item['type']}", $item));
        }

        return $awards;
    }

    /**
     * @param $user
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function getUserAwardsHistory($user, int $limit = 0, int $offset = 0): array
    {
        return $this->getUserAwards(
            $user,
            ' != 0',
            'tao.created_at DESC',
            '',
            '',
            '',
            false,
            $limit,
            $offset
        );
    }

    /**
     * Updates the game page trophy tab in real time based on game play, ie progression bars, sorting etc.
     *
     * Params will be ints if called via pexec.
     *
     * @param int|array &$t Trophy id or row.
     * @param int|array &$ud User id or row.
     * @param int $delay Delay in microseconds.
     *
     * @return null
     */
    function wsOnUpdate(&$t, &$ud, $delay = 1) {
        if ($this->do_ws === false || $this->getSetting('trophy_tab_enabled') !== true || phMget(mKey($ud, 'trophy-tab-open')) != 'yes') {
            return;
        }

        if (curPlaying($ud['id'])) {
            $t              = is_array($t) ? $t : $this->get($t);
            if(!empty($t['repeatable'])){
                return;
            }
            if($t['threshold'] > 100000 || !empty($t['hidden'])) {
                return;
            }

            $ud             = ud($ud);
            $te             = $this->getEvent($t, $ud);
            $percent        = (int) (( $te['progr'] / $te['threshold'] ) * 100.0);
            phive('Localizer')->setLanguage($ud['preferred_lang']);
            $name           = t('trophyname.' . $t['alias']);
            $game           = phive('MicroGames')->getByGameRef($t['game_ref']);
            $t['game_name'] = $game['game_name'];
            $desc           = rep(tAssoc('trophy.' . phive('Trophy')->getDescrStr($t) . '.descr', $t), $user, true);
            $arr            = array(
                'progress'              => $te['progr'],
                'progr'                 => $te['progr'],
                'teid'                  => $te['id'],
                'trophyname'            => $name,
                'threshold'             => $t['threshold'],
                'alias'                 => $t['alias'],
                'trophydescription'     => $desc,
                'progress_percent'      => $percent,
                'progress_for_sorting'  => (int)bcmul(bcdiv($te['progr'], $te['threshold'], 5), 100000),
                'finished'              => $percent == 100 ? true : false);

            if ($this->isGamePlay){
                toWs($arr, 'trophytab', $ud['id']);
            }
        }
    }

    /**
     * WS notification whenever an award is awarded by way of completing a trophy.
     *
     * @param array &$t The trophy row.
     * @param array &$ud The user row.
     * @param int $delay Delay in microseconds.
     *
     * @return null
     */
    function awardTrophyEvent(&$t, &$ud, $delay = 1) {
        if ($this->do_ws === false)
            return;
        $args = ['trophyaward', 'na', "t:trophyname.{$t['alias']}", $ud['username'], $ud['id'], $t['alias']];
        $fire_delay = ceil($delay / 1000); // microseconds to miliseconds
        phive()->fire('trophy', 'trophyAwardEvent', $args, $fire_delay >= 0 ? $fire_delay : 0 , function() use ($args, $delay) {
            $args[3] = cleanShellArg($args[3]); // only shell scape for pexec
            phive()->pexec('na', 'uEvent', $args, $delay);
        }, $ud['id']);
    }

    /**
     * Awards a trophy.
     *
     * This method will give any award pointed to by trophy.award_id as well as award the trophy.
     *
     * @uses Trophy::giveAward() in order to give the potential award.
     *
     * @param array &$t The trophy row.
     * @param array &$ud The user row.
     * @param int $delay Delay in microseconds.
     * @param bool $do_reward If false we don't give the award even if one is connected.
     *
     * @return null
     */
    function awardTrophy(&$t, &$ud, $delay = 0, $do_reward = true) {
        if ($t['amount'] == '0' || !$this->checkValidity($t))
            return false;

        $a = $this->getAward($t['award_id']);

        if(empty($a)){
            $do_reward = false;
        }

        //we can't rely on this, we have to respect the method call and use what we have in $t, but $t will have a lot of columns we don't need so we can't just do save
        $own = phive('SQL')->sh($ud, 'id')->loadAssoc("SELECT * FROM trophy_events WHERE trophy_id = {$t['id']} AND user_id = {$ud['id']}");

        if (!empty($own) && !empty($t['repeatable'])) {
            $own['cnt'] ++;
            phive('SQL')->sh($own)->save('trophy_events', $own);
            $this->awardTrophyEvent($t, $ud, $delay);
            return false;
        } else if(!empty($own)) {
            //Not repeatable and we already have it so we exit here.
            if((int)$own['finished'] === 1)
                return false;
            $own['finished'] = 1;
            $res = phive('SQL')->sh($ud, 'id')->save('trophy_events', $own);
        }else{
            //It was given by admin, so we call updateEvent with finished set to true
            $res = $this->updateEvent($t, $ud, $t['threshold'], 1);
            //$te_id = $this->updateEvent($t, $ud, 0, true);
            $this->awardTrophyEvent($t, $ud, $delay);
        }

        if ($res === false)
            return false;

        $this->awardTrophyEvent($t, $ud, $delay);

        phive('UserHandler')->logAction($ud, "Trophy Awarded: " . $t['alias'], 'trophy_awarded');

        // We don't give awards to countries who should not get them. (but we still assign the trophy)
        if ($do_reward && !in_array($ud['country'], explode(' ', $a['excluded_countries']))) {
            $this->giveAward($t['award_id'], $ud);
        }

        if ($t['amount'] != '0' && !empty($t['amount'])) {
            phive('SQL')->save('trophies', array('id' => $t['id'], 'amount' => (int) $t['amount'] - 1));
        }
    }

    /**
     * Gets the user's XP level.
     *
     * @param DBUser $user The user object.
     * @param string $key Optional key to differentiate between xp level and points.
     *
     * @return int The level or points.
     */
    function getUserXpInfo($user, $key = 'xp-level'){
        $lvl = $user->getSetting($key);
        return empty($lvl) ? 0 : $lvl;
    }

    /**
     * This method is responsible for displaying the categories section in the user account.
     *
     * TODO slow query, to be optimized CH17949
     *
     * @param DBUser $user The user object. // TODO henrik remove pass by ref.
     * @param string $col Column to GROUP BY.
     * @param string $where_cat Category to filter on.
     * @param string $loc_suffix Localization suffix for the localized string.
     * @return array The trophy categories.
     */
    public function getCategories(&$user, $col = 'category', $where_cat = '', $loc_suffix = '')
    {

        if ($col == 'sub_category') {
            $where_cat = empty($where_cat) ? 'mg.active = 1' : "mg.active = 1 AND t.category = '$where_cat'";
        } else {
            $where_cat = empty($where_cat) ? '1 ' : "t.category = '$where_cat'";
        }

        $whereProvince = phive('MicroGames')->addWhereProvinceClousure('AND ');
        $str = "SELECT t.*, mg.*
        FROM trophies t
        LEFT JOIN micro_games AS mg ON mg.ext_game_name = t.sub_category
        WHERE $where_cat
          AND (mg.included_countries IS NULL OR
                    (mg.blocked_countries NOT LIKE '%{$user->data['country']}%' {$whereProvince}
                         AND (mg.included_countries = '' OR mg.included_countries LIKE '%{$user->data['country']}%'))
              ) AND (t.excluded_countries NOT LIKE '%{$user->data['country']}%' AND (t.included_countries = '' OR t.included_countries LIKE '%{$user->data['country']}%'))
        GROUP BY $col";

        $res = phQget($str);
        if (!empty($res)) {
            return $res;
        }

        $res = phive('SQL')->lb()->loadArray($str);
        $ret = array();
        if (!empty($loc_suffix)) {
            foreach ($res as &$v) {
                $key = $v[$col];
                $ret[$key] = (empty($v['game_name']) || $col == 'category') ? t("$loc_suffix.{$v[$col]}") : $v['game_name'];
            }
        } else {
            $ret = phive()->arrCol($res, $col);
        }

        asort($ret);

        phQset($str, $ret);

        return $ret;
    }

    // TODO henrik remove, ie is addtrophy.php or addtrophy2 still used?
    function getByCategory($cat){
        $sql = "SELECT * FROM trophies WHERE category = '$cat'";
        return phive('SQL')->loadArray($sql);
    }

  // TODO paolo check this >>> Not sure if this was improved code or old code coming from branch "others_flag_change", need to be reviewed properly..
  /* version from others_flag_change branch... (is this better??)
  function countUserCategoryOwnership($cat, &$user){
      $str = "SELECT COUNT(*) FROM trophy_events WHERE user_id = {$user->getId()} AND finished = 1 AND trophy_id IN({$this->countByCategory($cat, true, 'id')})";
      return phive('SQL')->sh($user, 'id')->getValue($str);
  }

  function getAchievementStatus($cat, &$user){
    $user_count = $this->countUserCategoryOwnership($cat, $user);

    $tot_count_number = $this->countByCategory($cat);
    if(in_array($cat, array('secret', 'rare'))){
      $tot_count = '&#8734;';
      $progress = 0;
    }else{
      $tot_count = $tot_count_number;
      $progress = round(16 * ($user_count / $tot_count));
    }
    return array('tot_count' => $tot_count, 'tot_count_number' => $tot_count_number, 'user_count' => $user_count, 'user_prog' => $progress);
  }
  function getAchievementStatuses(&$user, &$categories){
    $ret = array();
    if(empty($categories))
      $categories = $this->getCategories();
    foreach(array_keys($categories) as $cat)
      $ret[$cat] = $this->getAchievementStatus($cat, $user);
    return $ret;
  }
  */
  // TODO paolo check this <<<


    /**
     * Gets the number of trophies in each trophy category.
     *
     * @param mixed &$u_obj Identifying user element.
     * @param $categories // TODO henrik remove and refactor the call.
     *
     * @return array The array of counts.
     */
    function getCountByCategories(&$u_obj, $categories)
    {
        $query = "SELECT * FROM trophies as t
            WHERE (t.excluded_countries NOT LIKE '%{$u_obj->getCountry()}%'
                    AND (t.included_countries = '' OR t.included_countries LIKE '%{$u_obj->getCountry()}%'))";

        $grouped = phive()->group2d($this->filterTrophies($u_obj, phive('SQL')->lb()->loadArray($query)), 'category', false);
        $ret = [];
        foreach($grouped as $cat => $ts){
            $ret[$cat] = count($ts);
        }
        return $ret;
    }

    /**
     * This method is behind the category progress in the trophy section of the account view. It uses
     * the trophy_events table in order to get the achieved count for the user in question.
     *
     * @uses Trophy::getCategories() in order to get the total category count.
     *
     * @param DBUser &$user The user object.  TODO henrik remove pass by ref.
     * @param array &$categories An array of categories to work with.
     *
     * @return array The result array.
     */
    function getAchievementStatuses(&$user, &$categories){
        if(isset($this->status_data)){
            return $this->status_data;
        }

        $ret = [];

        if(empty($categories)){
            $categories = $this->getCategories($user);
        }

        $total_cat_counts = $this->getCountByCategories($user, $categories);

        $in_cats = phive('SQL')->makeIn(array_keys($categories));

        $sql = "SELECT COUNT(*) AS cnt, t.category
                FROM trophy_events te, trophies t
                WHERE te.trophy_id = t.id
                    AND te.user_id = {$user->getId()}
                    AND te.finished = 1
                    AND t.category IN($in_cats)
                    AND (t.excluded_countries NOT LIKE '%{$user->getCountry()}%' AND (t.included_countries = '' OR t.included_countries LIKE '%{$user->getCountry()}%'))
                GROUP BY t.category";

        $user_cat_counts = phive('SQL')->sh($user)->loadKeyValues($sql, 'category', 'cnt');

        foreach($total_cat_counts as $cat => $total_cat_count){
            $user_complete_count = (int)$user_cat_counts[$cat];
            if(in_array($cat, ['secret', 'rare'])){
                $total_count_string  = '&#8734;';
                $progress_bar        = 0;
            }else{
                $total_count_string  = $total_cat_count;
                $progress_bar        = round(16 * ($user_complete_count / $total_cat_count));
            }

            $ret[$cat] = ['user_prog' => $progress_bar, 'user_count' => $user_complete_count, 'tot_count' => $total_count_string, 'tot_count_number' => $total_cat_count];
        }

        ksort($ret);

        $this->status_data = $ret;

        return $ret;
    }

    /**
     * Wraps getAchievemntStatuses() in order to display total user progress in the trophy section of the account view.
     *
     * @uses Trophy::getAchievementStatuses()
     *
     * @param DBUser &$user The user object. TODO henrik remove pass by ref.
     * @param array &$categories The categories to work with.
     *
     * @return float The progress.
     */
    function getOverallProgress(&$user, &$categories){
        $achievement_statuses = $this->getAchievementStatuses($user, $categories);
        $total_count = 0;
        $user_count = 0;

        foreach($achievement_statuses as $achievement_status) {
            $total_count += $achievement_status['tot_count_number'];
            $user_count += $achievement_status['user_count'];
        }

        return ceil($user_count / $total_count * 1000) / 10 ;
    }

    /**
     * Takes XP points and returns the XP level that corresponds to that many points.
     *
     * @param int $points The number of points.
     *
     * @return int The XP level.
     */
    function getXpLevel($points){
        $xp_levels = $this->getSetting('xp_levels');
        if(!empty($xp_levels)){
            $points_max = $xp_levels[count($xp_levels) - 1];
            if($points > $points_max){
                // We're out of bounds and will therefore assume that the difference between the
                // last and second to last configured entry will form the basis for all subsequent levels.
                $second_to_last = $xp_levels[count($xp_levels) - 2];
                $difference     = $points_max - $second_to_last;
                return floor($points / $difference);
            }
            return phive()->tholdLvl($points, array_flip($xp_levels), 0);
        }

        $points = (int)$points;
        if($points < 100)
            return 0;
        if($points < 2000)
            return 1;
        if($points >= 20000 && $points <= 210000)
            return 20;
        if($points < 20000)
            return floor($points / 1000);
        else if($points > 210000)
            return floor($points / 10000);
    }

    /**
     * This method is behind display of how many XP points to the next XP level.
     *
     * In case we want to switch to a progressive and non-linear formula at some point: (round(atan($x) * 100 * pow($x, 2) / 100) * 100)
     *
     * @param int $lvl The XP level.
     *
     * @return int The amount of XP points needed for the passed in XP level.
     */
    function getXpThold($lvl){
        $xp_levels = $this->getSetting('xp_levels');
        if(!empty($xp_levels)){
            $thold = $xp_levels[$lvl] ?? null;
            if(empty($thold)){
                // We're out of bounds and will therefore assume that the difference between the
                // last and second to last configured entry will form the basis for all subsequent levels.
                $second_to_last = $xp_levels[count($xp_levels) - 2];
                $last           = $xp_levels[count($xp_levels) - 1];
                $difference     = $last - $second_to_last;
                return $lvl * $difference;
            }
            return $thold;
        }

        $lvl = (int)$lvl;
        if(empty($lvl) || $lvl < 0)
            return 0;
        if($lvl === 1)
            return 100;
        $multi = $lvl <= 20 ? 10 : 100;
        return $lvl * 100 * $multi;
    }

    /**
     * Wrapper around getXpThold() in order to get the number of XP points needed for the next XP level.
     *
     * @uses Trophy::getXpThold()
     *
     * @param DBUser $user The user object.
     *
     * @return in The number of needed XP points.
     */
    function getUserNextXpThold($user){
        $cur_lvl = $user->getSetting('xp-level');
        return $this->getXpThold($cur_lvl + 1);
    }

    // TODO henrik remove
    function getUserPriorXpThold(&$user){
        $cur_lvl = $user->getSetting('xp-level');
        return $this->getXpThold($cur_lvl - 1);
    }

    /**
     * Wrapper around getXpThold() in order to get the number of XP points needed for the current XP level.
     *
     * @uses Trophy::getXpThold()
     *
     * @param DBUser $user The user object.
     *
     * @return in The number of needed XP points.
     */
    function getUserCurXpThold($user){
        $cur_lvl = $user->getSetting('xp-level');
        return $this->getXpThold($cur_lvl);
    }

    /**
     * Increases the user's XP point "balance".
     *
     * @uses User::incSetting() for the actual increase.
     *
     * @param DBUser &$uobj The user object. TODO henrik remove pass by ref.
     * @param int $bet_amount The wager / bet amount.
     * @param string $currency ISO2 currency code.
     * @param array &$cur_game The current game row.
     *
     * @return mixed
     */
    public function incXp(&$uobj, $bet_amount, $currency, &$cur_game)
    {
        $xp_multi = max($uobj->getSetting('xp-multiply'), 1);
        $amount = mc(($bet_amount / 100) * $xp_multi, $currency, 'div', false);
        $progress = phive('Casino')->getRtpProgress($amount, $cur_game);
        $uobj->incSetting('xp-points', $progress);

        return $progress;
    }

    /**
     * Gets common XP related information.
     *
     * @param DBUser $user The user object.
     *
     * @return array An array with current XP points "balance", the amount of points to reach the next XP level
     * and progress towards the next level as a float.
     */
    function getXpData($user){
        $xp_points = $this->getUserXpInfo($user, 'xp-points');
        $xp_prior = $this->getUserCurXpThold($user);
        $xp_next = $this->getUserNextXpThold($user);
        $user_diff = $xp_points - $xp_prior;
        $lvl_diff = $xp_next - $xp_prior;
        if(empty($xp_points) || empty($user_diff) || empty($lvl_diff))
            $progress = 0;
        elseif($xp_points >= $xp_next){
            $progress = 1;
            $xp_points = $xp_next - 1;
        }
        else{
            $progress = $user_diff / $lvl_diff;
        }

        return array($xp_points, $xp_next, $progress);
    }

    /**
     * Progresses XP level and awards potential awards connected to reaching a certain level.
     *
     * @return null
     */
    function xpCron(){
        $usr_levels = phive('UserHandler')->rawSettingsWhere("setting = 'xp-level'", 'user_id');
        foreach(phive('UserHandler')->rawSettingsWhere("setting = 'xp-points'", 'user_id') as $uid => $s){
            $cur_lvl    = max($usr_levels[$uid]['value'], 0);
            if(empty($cur_lvl))
                $usr_levels[$uid] = array('user_id' => $uid, 'setting' => 'xp-level', 'value' => 0);
            $cur_points = $s['value'];
            $new_lvl    = $this->getXpLevel($cur_points);
            if($new_lvl > $cur_lvl){
                $ud = cu($uid)->data;
                uEvent('climbedlevel', $new_lvl, '', '', $ud);
                $usr_levels[$uid]['value'] = $new_lvl;
                phive('SQL')->sh($ud, 'id')->save('users_settings', $usr_levels[$uid]);
                //It's possible to jump more than one level per cron invocation so we just loop all of them
                foreach($this->getWithTypeThold('xp') as $t)
                    $this->awardOrProgress($t, $ud, $new_lvl);
            }
        }
    }

    /**
     * Gets a alias used for localization.
     *
     * @link https://wiki.videoslots.com/index.php?title=DB_table_localized_strings
     *
     * @param array &$t The trophy.
     *
     * @return string The localized string alias.
     */
    function getDescrStr(&$t){
        if(!empty($t['game_ref']))
            $has_game = 'game';
        if(!empty($t['in_row']))
            $in_row = 'inrow';
        return "{$t['type']}{$t['subtype']}{$t['time_span']}$has_game$in_row";
    }

    /**
     * Sets the expiry date of an award.
     *
     * @param DBUser &$user The user object. TODO henrik remove ref.
     * @param array $a The award.
     * @param string $type Award type.
     *
     * @return null
     */
    function setActiveExpire(&$user, $a, $type = 'multiply'){
        $user->setSetting("awardexp-{$a['id']}-$type", phive()->hisMod("+{$a['valid_days']} day"));
    }

    /**
     * Sets user settings needed that are needed in order to keep track of awards that are active
     * for a period of time.
     *
     * @param DBUser &$user The user object. TODO henrik remove ref.
     * @param array &$a The award.
     * @param string $type The award type.
     *
     * @return null
     */
    function setActiveInfo(&$user, &$a, $type = 'multiply'){
        $user->setSetting("current-$type-award-id", $a['id']);
        $user->setSetting("current-$type-award-type", $a['type']);
        $user->setSetting("{$a['id']}-info", json_encode(['main_type' => $type, 'award' => $a]));
    }

    /**
     * Runs when a user decides to activate a multiplier award (eg get more loyalty on the next X spins).
     *
     * @param DBUser &$user User object. TODO henrik remove ref.
     * @param array $a The award row.
     *
     * @return null
     */
    function useMultiplyAward(&$user, $a){
        $this->setActiveInfo($user, $a);
        $user->setSetting($a['id'].'-multiply', $a['multiplicator']);
        $user->setSetting('spins-' . $a['id'].'-multiply', $a['amount']);
        $this->setActiveExpire($user, $a);
        toWs(array('start' => 'true'), 'rewardprogress', $user->getId());
    }

	/**
	 * Trophy award related logic to run on each bet, this typically happens in a forked process to
	 * allow the main process to return ASAP and not have to wait for less importang stuff like trophies.
	 *
	 * @param int $uid The user id.
	 * @param int $aid The award id.
	 * @return null
	 */
    function wsOnBet($uid, $aid)
	{
		$user = cu($uid);
		$a = $this->getAward($aid);
		if(empty($user) || empty($a))
			return;
		$details = $this->getRewardInUseDetails($user, $a, false);
        
        toWs($details, 'rewardprogress', $uid);
    }

    // TODO henrik remove
    function hasMultiplyAward(&$user){
        $res = $this->curMultiplyAid($user);
        return !empty($res);
    }

    /**
     * Checking if the user has a reward
     *
     * @param DBUser $user The user object.
     * @param bool $check_bonuses Whether or not to include bonuses in the check or not.
     * @return bool True if a reward is active, false otherwise.
     */
    function hasAnyReward($user, $check_bonuses = true){
        if(empty($user))
            return false;
        $all_current = $this->getAllCurrent($user);
        // Freeroll tickets are exempt
        if(!empty($all_current) && $user->getSetting('current-normal-award-type') != 'mp-freeroll-ticket')
            return true;
        if($check_bonuses && !empty(phive('Bonuses')->getUserBonuses($user->getId(), 1, "IN('active','pending')")))
            return true;
        return false;
    }

    /**
     * Wrapper around getting the current multiply award id setting.
     *
     * @param DBUser $user The user object.
     *
     * @return string The award id, actually an int but the type will be string.
     */
    function curMultiplyAid($user){
        if(empty($user))
            return false;
        return $user->getSetting('current-multiply-award-id');
    }

    /**
     * A wrapper around getSettingsByRegex() with some extra caching logic.
     *
     * @param DBUser $user The user object.
     * @return array The array of settings.
     */
    function getAllCurrent($user){
        if(empty($user))
            return false;

        if(!empty($this->current_awards)) {
            return $this->current_awards;
        }
        $this->current_awards = $user->getSettingsByRegex('current-.*-award-id');
        return $this->current_awards;
    }

    // TODO henrik remove, move logic to invocation instead.
    function mainTypeSetting(&$setting){
        list($nu1, $type, $nu2, $nu3) = explode('-', $setting['setting']);
        return $type;
    }

    /**
     * Gets the currently active trophy award (only one at a time can be active).
     *
     * @param DBUser $user The user object.
     * @param string $type The award type.
     *
     * @return array The award row.
     */
    function getActiveCurrent($user, $type = 'normal'){
        if(empty($user))
            return [];
        $aid = $user->getSetting("current-$type-award-id");
        if(empty($aid))
            return [];
        return $this->getAward($aid);
    }

    /**
     * Gets the currently active multiply award.
     *
     * @param DBUser &$user The user object. TODO henrik remove ref.
     *
     * @return array The award.
     */
    function getMultiplyAward(&$user){
        return $this->getActiveCurrent($user, 'multiply');
    }

    /**
     * Gets misc. info about the currently active reward.
     *
     * @param DBUser &$user The user object. TODO henrik remove ref.
     * @param array &$a The award.
     * @param bool $add_exp Whether or not the expiry date is needed.
     *
     * @return array The result.
     */
    function getRewardInUseDetails(&$user, &$a, $add_exp = true){
        $spins = $this->awardSetting($user, $a['id'], 'spins');
        $res = array(
            'spins' => $spins,
            'progress' => ($a['amount'] - $spins)  / $a['amount']
        );
        if($add_exp)
            $res['exp_date'] = $this->awardSetting($user, $a['id'], 'awardexp');
        return $res;
    }

    /**
     * Atm active multiply rewards are considered exclusive and will prevent further activation of
     * more multiply rewards. This method is part of the logic preventing that.
     *
     * @used-by Trophy::canUseAward()
     *
     * @param DBUser $user The user object.
     *
     * @return bool True if the user has an exclusive award active, false otherwise.
     */
    function hasExclusiveActive($user){
        foreach($this->getAllCurrent($user) as $s){
            if($this->mainTypeSetting($s) == 'multiply')
                return true;
        }
        return false;
    }

    /**
     * Used in TropyListBoxBase in order to cache error messages in case the player can not use awards he is clicking on.
     *
     * NOTE that this method can not be used to pre determine if a player can use the award in question anymore due to the fact
     * that we have to respect the CasinoBonuses::handleExclusive() logic in order to be 100% sure and we're not doing that in this
     * method.
     *
     * @param &$user DBUser object.
     * @param &$a Array, the trophy award.
     *
     * @return string|bool True if the award can be used, error string otherwise.
     */
    function canUseAward(&$user, &$a){
        if($a['action'] == 'instant')
            return true;

        // We block in case the award we're looking at is NOT a bonus related award and the user has active bonuses of any kind.
        // We defer to the server side call to Trophy::useAward() in case the player is trying to activate a bonus related award.
        if(empty($a['bonus_id'])){
            if(!isset($this->b_entries)) {
                $this->b_entries = phive('Bonuses')->getActiveBonusEntries($user->getId());
            }
            if(!empty($this->b_entries)) {
                if($a['type'] == 'wheel-of-jackpots'){
                    return true;
                }
                return 'bonus';
            }
        }

        $cur_main_type = $this->getAwardMainType($a);
        switch($cur_main_type){
            case 'normal':
                return true;
                break;
            case 'multiply':
                if($this->hasExclusiveActive($user))
                    return 'multiply';
                break;
        }
        return true;
    }

    /**
     * This is the final stage of the deposit related awards flow. It will fetch the current award for the user
     * that got stored at the beginning of the flow and then use it and finally clear it out.
     *
     * @uses Trophy::useAward()
     * @uses Trophy::delCurAward() in order to remove the memory caching of the current award.
     * @used-by Casino::depositCash()
     *
     * @param int $uid The user id.
     * @param array $extra Potentially auxiliary information needed by Trophy::useAward().
     *
     * @return bool True if useAward() was actually called, false otherwise. Note that this return
     * is not 100% reliable for whether or not the award was actually successfully used.
     */
    function execDepositAward($uid, $extra){
        $award = $this->getCurAward($uid, true);
        // We ignore WoJ awards in this context.
        if(!empty($award) && $award['type'] != 'wheel-of-jackpots'){
            $this->useAward($award['id'], $uid, $extra);
            $this->delCurAward($uid);
            return true;
        }
        return false;
    }

    /**
     * Simple mapping to get the main award type from the sub award type.
     *
     * @param array &$a The award.
     *
     * @return string The main type.
     */
    function getAwardMainType(&$a){
        $map = [
            'race-multiply' => 'multiply',
            'cashback-multiply' => 'multiply',
            'mp-freeroll-ticket' => 'normal'
        ];
        return $map[$a['type']];
    }

    /**
     * Saves a trophy award ownership row, typically used when an award has been activated in order to
     * update status and various time stamps.
     *
     * @param array $tao The trophy award ownership row.
     * @param int $status The status.
     * @param string $activated_stamp Activation stamp.
     * @param string $finished_stamp The finish stamp, will be ignored if not given, ie we assume the award is not finished.
     *
     * @return bool True if the database save was successful, false otherwise.
     */
    function saveOwnerShip($tao, $status = 2, $activated_stamp = null, $finished_stamp = null){
        $tao['status']       = $status;
        $tao['activated_at'] = $activated_stamp ?? phive()->hisNow();
        if(!empty($finished_stamp)){
            $tao['finished_at']  = $finished_stamp;
        }
        return phive('SQL')->sh($tao)->save('trophy_award_ownership', $tao);
    }

    /**
     * The main logic running when a player clicks a reward.
     *
     * This function will convert rewards to various other things like BoS registrations, bonuses, multipliers  or pure cash.
     *
     * This is what happens:
     * 1. If we're looking at a WoJ award we set it as the current award in Redis and return immediately (it will actually be activated when the wheel spin is executed).
     * 2. Next we check if there is already a reward or bonus active, NOTE though that we ONLY do this in case the current award is NOT connected to a bonus.
     * This is due to the fact that type 3 bonuses should be able to be activated regardless of the general bonus / award status.
     *
     * If the award type is not a BoS ticket and it is a multiplier OR a bonus reward and we already has an award active we return an error message.
     * 3. If the type is a top up or deposit and we've passed in a cents amount in $extra we check if we have a prior reward active (bonuses with type 3 still excluded),
     * if we do we return an error message. Otherwise we set it as the current reward and return success (the top up and deposit bonus will be activated later, ie upon
     * subsequent successful deposit).
     * 4. Now we first check if we're looking at a bonus reward, if we do we check if it is an FRB or otherwise a bonus that should be given  right away, if it is we do.
     * Otherwise we cehck if it is a deposit bonus and execute adding such a bonus. The bonus activation can succeed or not (not becuase of exclusivity settings for instance), if
     * it did not succeed we return an appropriate error message to the player.
     *
     * If we have a connection error to the GP we set the awards as successfully activated because we can't know where the issue is / was,
     * if the actual call was not received or if we didn't receive the answer, if we didn't get the answer then NOT marking the award as a success
     * would allow the player to potentially repeatedly activate the FRB over and over again.
     *
     * If we did not have a connection error we don't save the success status which will allow the player to redo activation again when he has finished
     * existing bonuses.
     * 5. If we're not looking at a bonus reward we can now finally start executing the activation of the reward and that depends on the type, but we first save the award as activvated to
     * prevent repeat activation issues.
     * 6. Finally we execute the websocket based notification logic for the reward.
     *
     * @param int $aid Award id.
     * @param int $uid The user id to activate for.
     * @param array $extra Potential extra information needed for certain awards, a deposit bonus needs the deposit amount in the form of ex: ['cents' => 100]
     * @param int $event_delay Activating rewards can result in various notifications, this is the delay between activation and notification, default 5 seconds.
     * @param bool $translate
     *
     * @return mixed Returns the award on success or an error string if the award could not be activated.
     */    
    function useAward(
        $aid,
        $uid = '',
        $extra = [],
        $event_delay = 5000000,
        bool $translate = true, 
        ?bool $returnMobileLaunchUrl = false
    ){
        $aid  = is_numeric($aid) ? $aid : $aid['id'];
        $uid  = empty($uid) ? $_SESSION['mg_id'] : $uid;
        $user = cu($uid);

        $instant_bonuses = array('instant-bonus', 'freespin-bonus');
        if(empty($user)) {
            return false;
        }

        $need_to_deposit = phive('UserHandler')->doForceDeposit($user);
        if($need_to_deposit){
            $alias = 'prevent.award.usage.before.deposit';

            return $translate ? t($alias) : $alias;
        }

        //Get the award ownership row for the user which is not yet activated
        $tao = phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM trophy_award_ownership WHERE award_id = $aid AND user_id = $uid AND status = 0");

        if (empty($tao)){
            $alias = 'award.not.used';

            return $translate ? t($alias) : $alias;
        }

        $a   = $this->getExtAward($tao['award_id']);

        if(empty($a)){
            $alias = 'award.not.used';

            return $translate ? t($alias) : $alias;
        }

        $lic_can_use = lic('handleUseAward', [$user, $a, $translate], $user);
        if(is_string($lic_can_use)){
            return $lic_can_use;
        }

        // This gets executed when the award is clicked, we just set it to be the current award and return it.
        // Frontend code will then redirect to the wheel page.
        if($a['type'] == 'wheel-of-jackpots' && empty($extra)){
            $this->setCurAward($aid, $uid);
            return $a;
        }

        // If player has a reward active or a bonus active or pending we return error message right away if the reward to be activated is a multiply
        // or is connected to a bonus. BoS tickets are exempted though as bonus_id is there NOT pointing to a bonus but the BoS tpl id.
        $has_reward = $this->hasAnyReward($user, empty($a['bonus_id']));
        if(!in_array($a['type'], ['mp-ticket']) && (in_array($a['type'], ['race-multiply', 'cashback-multiply']) || !empty($a['bonus_id'])) && $has_reward){
            $alias = 'award.not.used';

            return $translate ? t($alias) : $alias;
        }

        // We initiate a top up that will not be executed until the deposit has happened so we cache it and return immediately
        // but only if we're at the start of the flow, not the end where the deposit is a fact.
        if(in_array($a['type'], ['top-up', 'deposit']) && empty($extra['cents'])){

            // If there is already something pending or active we return error message.
            if($has_reward) {
                $alias ='award.not.used';

                return $translate ? t($alias) : $alias;
            }

            //error_log("Before setCurAwatd in use award. Aid: $aid, uid: $uid");
            $this->setCurAward($aid, $uid);
            //error_log("After setCUrA. Aw: ". $this->getCurAward($uid));
            return $a;
        }

        $ud              = $user->data;
        $status          = 2;
        $activated_stamp = phive()->hisNow();
        $finished_stamp  = phive()->hisNow();

        // We're not interested in executing the bonus logic if we're looking at a BoS ticket as
        // the bonus_id is pointing to the BoS tpl id there.
        if(!empty($a['bonus_id']) && !in_array($a['type'], ['mp-ticket'])){
            if(in_array($a['type'], $instant_bonuses)){
                if ($a['type'] === 'freespin-bonus' && $returnMobileLaunchUrl) {
                    $lang = $user->data['preferred_lang'];
                    $data = $this->getGameUrl($uid, $a['bonus_id'], $lang);
                    $a = array_merge($a, $data);
                }
				// TODO henrik this call is not correct, should be $tao['user_id'], $a['bonus_id'], true, false, null, 14, false
				$entry_id = phive('Bonuses')->addUserBonus($tao['user_id'], $a['bonus_id'], true, false, null, false);
            }else if($a['type'] == 'deposit'){
                $entry_id = phive('Bonuses')->addDepositBonus($uid, $a['bonus_id'], $extra['cents'], true);
            }
            if(phive('Bonuses')->isAddedError($entry_id)){
                if($entry_id == 'connection.error'){
                    $this->saveOwnerShip($tao, $status, $activated_stamp, $finished_stamp);
                }
                $alias = phive('Bonuses')->addErrorSuffix('award.not.used', $entry_id);

                return $translate ? t($alias) : $alias;
            } else {
                $this->saveOwnerShip($tao, $status, $activated_stamp, $finished_stamp);
            }
        }else{
            $this->saveOwnerShip($tao, $status, $activated_stamp, $finished_stamp);
            // get transaction type with action shift
            switch($a['type']){
                case 'cash':
                    $amount = mc($a['amount'], $user);
                    phive('Cashier')->transactUser($user, $amount, "Free cash reward of $amount from '{$a['description']}'", null, null, 77);
                    break;
                case 'top-up':
                    $cents = $extra['cents'];
                    $tu_amount = min($a['multiplicator'] * $cents, mc($a['amount'], $ud['currency']));
                    if(!empty($tu_amount))
                        phive('Cashier')->transactUser($user, $tu_amount, "Trophy reward top up on $cents deposit, rate: {$a['multiplicator']}", null, null, 80);
                    break;
                case 'race-spins':
                    foreach(phive('Race')->getActiveEntries($uid, '', true) as $re){
                        $spins = $re['race_balance'] + $a['amount'];
                        phive('Race')->incRaceBalance($a['amount'], $re['id'], $uid);
                        phive('UserHandler')->logAction($ud['id'], "Used award id $aid, added {$a['amount']} spins to race entry id {$re['id']}, spins before: {$re['race_balance']} and after: $spins", 'race-spins');
                        phive('Race')->wsOnBet($uid, $spins, $ud['firstname']);
                    }
                    break;
                case 'mp-ticket':
                    //do nothiing atm, the tournament logic is resposible for handling the transactions
                    break;
                case 'race-multiply':
                    $this->useMultiplyAward($user, $a);
                    $finished_stamp = '';
                    $status = 1;
                    break;
                case 'cashback-multiply':
                    $this->useMultiplyAward($user, $a);
                    $finished_stamp = '';
                    $status = 1;
                    break;
                case 'xp-multiply':
                    $user->setSetting('xp-multiply', $a['multiplicator']);
                    break;
                case 'mp-freeroll-ticket':
                    $this->setActiveExpire($user, $a, 'normal');
                    $this->setActiveInfo($user, $a, 'normal');
                    $finished_stamp = '';
                    $status = 1;
                    break;
            }
        }

        $evstr = $this->awardAsStr($a, $user->getCurrency());
        $args = ['usereward', 'na', $evstr, $ud['username'], $ud['id'], $this->awardEventImg($a, $user)];
        phive()->fire('trophy','trophyAwardEvent', $args, 0, function() use ($args, $event_delay) {
            $args[2] = escapeshellarg($args[2]); // shell escape only for pexec
            $args[3] = cleanShellArg($args[3]);
            phive()->pexec('na', 'uEvent', $args, $event_delay);
        }, $ud['id']);

        $this->wsUpdateRewardCount($ud);
        return $a;
    }

    /**
     * Gets the award event image thumbnail.
     *
     * @param array &$a The award row.
     * @param DBUser &$user The user object.
     *
     * @return string The base file name (we always assume .png).
     */
    function awardEventImg(&$a, &$user){
        if(strpos($a['alias'], '-') !== false)
            return array_pop(explode('-', $a['alias']));

        if(in_array($a['type'], array('cash')) && !empty($user)){
            $parts = explode('_', $a['alias']);
            $num = mc(array_shift($parts), $user);
            array_unshift($parts, $num);
            $fname = implode('_', $parts);
        }else if($a['type'] == 'wheel-of-jackpots'){
            // This has to be deactivated if we want to enable different thumbs for different types of wheels
            $fname = 'wheel_of_jackpots';
        }else
        $fname = $a['alias'];

        return $fname;
    }

    /**
     * Used to set data needed for trophy progression due to gameplay, eg: win 3 times in a row in game X.
     *
     * Its main task is to build a fully unique key for the key / action, user id and game combination.
     *
     * @param string $key The differentiator, eg curGameAction.
     * @param array &$ud User row / data.
     * @param array &$cg Current game.
     * @param mixed $val The value to set.
     * @param int $expire Expire time in seconds.
     *
     * @return null
     */
    function memSet($key, &$ud, &$cg, $val, $expire = 18000){
        phMset(mKey($ud, $key.$ud['id'].$cg['ext_game_name']), $val, $expire);
    }


    /**
     * Used to get data needed for trophy progression due to gameplay, eg: win 3 times in a row in game X.
     *
     * Its main task is to build a fully unique key for the key / action, user id and game combination.
     *
     * @param string $key The differentiator, eg curGameAction.
     * @param array &$ud User row / data.
     * @param array &$cg Current game.
     *
     * @return string The value to get.
     */
    function memGet($key, &$ud, &$cg){
        return phMget(mKey($ud, $key.$ud['id'].$cg['ext_game_name']));
    }

    // TODO henrik remove
    public function memDel($key, &$ud, &$cg){
        phMdel(mKey($ud, $key.$ud['id'].$cg['ext_game_name']));
    }

    /**
     * Used to set data needed for trophy progression due to gameplay, eg: win 3 times in a row in game X.
     *
     * @used-by Trophy::memOnBet()
     *
     * @param array &$ud User row / data.
     * @param array &$cg Current game.
     * @param string $action The action to store, ie bet or win.
     *
     * @return null
     */
    function memSetGameAction(&$ud, &$cg = null, $action = 'win'){
        $this->memSet('priorGameAction', $ud, $cg, $this->memGet('curGameAction', $ud, $cg));
        $this->memSet("curGameAction", $ud, $cg, $action);
    }

    /**
     * Checks if the current date is within the validity time span of a trophy.
     *
     * @param array &$t The trophy.
     *
     * @return bool False if not valid anymore, true otherwise.
     */
    function checkValidity(&$t){
        $cur_date = phive()->today();
        if($cur_date <= $t['valid_from'] || $cur_date >= $t['valid_to'])
            return false;
        return true;
    }

    // TODO henrik remove, replace in calling context with contents of method.
    function getValiditySql($prefix = ''){
        $date = phive()->today();
        return "AND {$prefix}valid_from <= '$date' AND {$prefix}valid_to >= '$date'";
    }

    /**
     * This updates an existing record in table trophy_events, or inserts a new record.
     *
     * @param array &$t The trophy row.
     * @param array &$ud The user row.
     * @param int $amount Amount, monetary or some kind of misc. progress.
     * @param bool $finished If true we mark the trophy event row as finished.
     * @param bool $set_progress If true we set the progress with the amount, otherwise we increase with the amount.
     * @param int $delay Delay in microseconds for the WS update.
     *
     * @return int The trophy event id.
     */
    function updateEvent(&$t, &$ud, $amount, $finished = '', $set_progress = '', $delay = 500){
        if(!empty($t['finished']) || !$this->checkValidity($t))
            return 0;

        $arr = array(
            'trophy_type' => $t['type'],
            'in_row'      => $t['in_row'],
            'threshold'   => $t['threshold'],
            'time_period' => $t['time_period'],
            'trophy_id'   => $t['id'],
            'user_id'     => $ud['id'],
            'updated_at'  => phive()->hisNow(),
            'game_ref'    => $t['game_ref']);

        if(!empty($t['event_id']))
            $arr['id'] = $t['event_id'];

        if(!empty($finished)){
            $arr['progr'] = $amount > $t['threshold'] ? $amount : $t['threshold'];
            $arr['finished'] = 1;
        }else{
            if(empty($set_progress))
                $arr['progr'] = $t['progr'] + $amount;
            else
                $arr['progr'] = $amount;
        }

        phive('SQL')->sh($ud, 'id')->save('trophy_events', $arr);
        $this->wsOnUpdate($t, $ud, $delay);

        return empty($arr['id']) ? phive('SQL')->sh($ud, 'id')->insertBigId() : $arr['id'];
    }

    /**
     * Awards or progresses a trophy if the award condition is not fulfilled.
     *
     * @param array &$t The trophy.
     * @param array &$ud User row / data.
     * @param int $amount Amount to progress with.
     * @param string $currency ISO2 currency code.
     * @param bool $only_finish Currently not used.
     * @param int $delay Delay in microseconds, needs to be high in order to avoid spoilers due to the WS notifications signaling
     * wins possibly before they show up in the game (eg after FS).
     * @param bool $set_progress Whether or not to set the progress (true) or increment the progress (false).
     * @param bool $do_reward If false we don't give the award even if one is connected.
     *
     * @return int The trophy event id.
     */
    function awardOrProgress(&$t, &$ud, $amount = 1, $currency = '', $only_finish = false, $delay = 5000000, $set_progress = '', $do_reward = true){
        if(!empty($currency))
            $amount = mc($amount, $currency, 'div');

        $progress_amount = empty($set_progress) ? $t['progr'] + $amount : $amount;

        if($t['threshold'] <= $progress_amount){
            $this->awardTrophy($t, $ud, $delay, $do_reward);
            $finished = 1;
        }

        if(empty($finished) && $only_finish)
            return 0;

        return $this->updateEvent($t, $ud, $progress_amount, $finished, true, $delay);
    }

    /**
     * Awards a big win trophy, big wins are typically repeatable so can be won over and over again with a
     * counter for how many times they've been achieved.
     *
     * @param array &$t The trophy.
     * @param array &$ud User row / data.
     * @param array &$cg The currently played game.
     * @param int $winx The winx amount, if the wager was 1 and the win 10 then this arg. is 10.
     *
     * @return null
     */
    function awardBigWin(&$t, &$ud, &$cg, $winx){
        $this->updateEvent($t, $ud, $winx, 1);
        $this->awardTrophy($t, $ud, 60000000);
    }

    /**
     * Figures out which big win trophy to award depending on the win X amount and then calls Trophy::awardBigWin().
     *
     * @param array &$t The trophy.
     * @param array &$ud User row / data.
     * @param array &$cg The currently played game.
     * @param int $winx The winx amount, if the wager was 1 and the win 10 then this arg. is 10.
     *
     * @return null
     */
    function handleBigWin(&$t, &$ud, &$cg, $winx){
        $map = array(15 => 30, 30 => 60, 60 => 10000000000);
        if($winx >= $t['threshold'] && $winx < $map[$t['threshold']])
            $this->awardBigWin($t, $ud, $cg, $winx);
    }

    /**
     * Wrapper around an SQL statement that gets trophies with a join on trophy_events with misc. filters.
     *
     * @param array &$ud User row / data.
     * @param array &$cg The currently played game.
     * @param string $type Trophy type.
     * @param string $extra Optional extra WHERE clauses.
     * @param bool $resp_game Whether ot not filter on game in a hard way, if false we include trophies that aren't
     * connected to a particular game as well.
     * @param string $subtype Trophy sub type.
     * @param int $sh_num Shard number / id override.
     *
     * @return array The result array.
     */
    function getActiveTrophies(&$ud, $cg, $type, $extra = '', $resp_game = false, $subtype = '', $sh_num = false){
        $and_game      = "t.game_ref = '{$cg['ext_game_name']}'";
        $where_game    = $resp_game      ? $and_game  : "($and_game OR t.game_ref = '')";
        $where_subtype = empty($subtype) ? ''         : " AND subtype = '$subtype'";
        $str = "SELECT t.*, te.id AS event_id, te.progr, te.finished, te.user_id
                FROM trophies t
                LEFT JOIN trophy_events AS te ON te.trophy_id = t.id AND te.user_id = {$ud['id']}
                WHERE t.type = '$type'
                    $where_subtype
                    $extra
                    AND ((te.finished IS NULL OR te.finished = 0) OR t.repeatable = 1)
                    AND (t.amount = '' OR t.amount != '0')
                    AND (t.excluded_countries NOT LIKE '%{$ud['country']}%' AND (t.included_countries = '' OR t.included_countries LIKE '%{$ud['country']}%'))
                    AND $where_game";

        $shard = $sh_num !== false ? phive('SQL')->sh($sh_num) : phive('SQL')->sh($ud, 'id');

        return $shard->loadArray($str);
    }

    /**
     * Gets the most recently completed / achieved trophies, this is achieved by the combination of getting completed
     * trophies and ordering by event id descending.
     *
     * @uses Trophy::getLatestTrophies()
     *
     * @param DBUser &$user The user object. TODO henrik remove the ref.
     * @param int $limit If not empty it will control how many rows are fetched.
     *
     * @return array The result array.
     */
    function getLatestTrophies(&$user, $limit = 16){
        return $this->getUserTrophies($user->getId(), '', '', '', false, 'completed', $limit, "ORDER BY event_id DESC");
    }

    /**
     * Gets a single trophy by primary key / id.
     *
     * @param int $tid The trophy id.
     *
     * @return array The trophy row.
     */
    function get($tid){
        return phive('SQL')->loadAssoc('', 'trophies', array('id' => $tid));
    }

    /**
     * Gets a sub category of trophies, eg **qspinrlx.revolver.revolver.lotto_lucky** or **spinwins**.
     *
     * @param mixed $uid User identifying element.
     * @param string $sub_cat The sub category.
     * @param string $type The type, used by Trophy::getUserTrophiesSql() in order to build the correct SQL statement.
     *
     * @return array An array of trophies.
     */
    function getSub($uid, $sub_cat = '', $type = ''){
        $type = empty($type) ? 'progressed' : $type;
        return phive('Trophy')->getUserTrophies($uid, '', $sub_cat, '', false, $type, '', "ORDER BY t.category, t.sub_category, t.type DESC, t.threshold");
    }

    /**
     * This logic is responsible for fetching the trophies displayed in the trophy tab on the game play page.
     *
     * @param int $uid The user id.
     * @param string $ext_game_name The external game name / id.
     *
     * @return array The array of trophies.
     */
    function getCurrentGameTrophies($uid, $ext_game_name = ''){
        $uid = intval($uid);
        // @todo: make the cron trophies work with the trophy tab
        $where          = "WHERE e.user_id = $uid AND e.finished = 0 AND t.hidden = 0 AND t.subtype NOT LIKE '%money%'";
        $gamestr        = empty($ext_game_name)? " AND e.game_ref LIKE '' " : " AND (e.game_ref LIKE '$ext_game_name' OR e.game_ref LIKE '') ";
        $typestr        = " AND e.trophy_type IN ('win', 'bet')";
        $sortby         = " ORDER BY progress_percent DESC";
        $join           = " INNER JOIN trophies t on t.id = e.trophy_id ";
        $game_sel       = ", mg.game_name";
        $game_join      = "LEFT JOIN micro_games AS mg ON mg.ext_game_name = t.game_ref";
        $sql            = "SELECT e.*, t.* $game_sel, t.alias, e.id as trophy_event_id,
                               ((e.progr / e.threshold) * 100.0) as progress_percent,
                               ((e.progr / e.threshold) * 100000) as progress_for_sorting
                           FROM trophy_events e $join $game_join $where $gamestr $typestr $sortby";

        return phive('SQL')->sh($uid)->loadArray($sql);
    }

    /**
     * Creates an SQL statement for fetching trophies with the help of the parameters.
     *
     * Note the join on the games table in order to figure out if the trophies can be displayed or not
     * as some games are not available for certain countries we can't show their connected trophies.
     *
     * The type can be one of:
     * 1. Completed: in which case we only get completed / awarded / finished trophies.
     * 2. Uncompleted: in which case we get only non-finished trophies.
     * 3. Noprogress: in which case we only get trophies that are not finished with zero progress.
     * 4. Progressed: in which case we only get trophies with progress, regardless of unfished / finished status.
     * 5. Default is to simply get everything.
     *
     * @param int $uid The user id.
     * @param string $category The trophy category to fetch.
     * @param string $sub_category The sub category to fetch.
     * @param bool $free_text If true $category will be matched as a sub string against the tropy category, sub_category and the game name.
     * @param string $type See main description.
     * @param int $limit If not empty it will control how many rows are fetched.
     * @param string $order_by Optional order by clause.
     * @param string $group_by Group by clause, if $trophies_names is true it will be overridden.
     * @param string $extra_sel Optional extras to add to the select part.
     * @param bool $trophies_names If true we will return all aliases in each group concatenated with commas and we will group by category and sub_category.
     *
     * @return string The SQL statement.
     */
    function getUserTrophiesSql($uid, $category = '', $sub_category = '', $free_text = false, $type = 'normal', $limit = '', $order_by = '', $group_by = '', $extra_sel = '', $trophies_names = false){
        $ud = ud($uid);

        $device_num = phive()->getCurrentDeviceNum();

        if ($device_num === 1) {
            $where = "WHERE (t.game_ref = '' || mg.mobile_id > 0) ";
        } else {
            $where = "WHERE 1";
        }

        if($type == 'completed'){
            $pr_where   = 'AND ev.finished = 1';
            $ev_join = 'INNER';
        }else if($type == 'uncompleted'){
            $pr_where = "AND ev.finished != 1";
            $having_extra = "OR ev.finished = 0 OR ev.finished IS NULL";
            $ev_join = 'LEFT';
        }else if($type == 'noprogress'){
            $where .= " AND progr = 0 AND finished != 1";
            // $having_extra = "OR ev.finished = 0 OR ev.finished IS NULL";
            $ev_join = 'LEFT';
        }else if($type == 'progressed'){
            $pr_where = "AND progr > 0";
            $ev_join = 'INNER';
        }else{
            $ev_join = 'LEFT';
            $pr_where = "";
        }

        if($free_text){
            $where_extra = "AND (t.category LIKE '%$category%' OR t.sub_category LIKE '%$category%' OR mg.game_name LIKE '%$category%')";
        }else{
            $where_extra = empty($category) ? '' : "AND t.category = '$category'";
            $where_extra .= empty($sub_category) ? '' : "AND t.sub_category = '$sub_category'";
        }

        $whereProvince = phive('MicroGames')->addWhereProvinceClousure('AND ');
        $where_extra .= "AND (mg.included_countries IS NULL OR
                    (mg.blocked_countries NOT LIKE '%{$ud['country']}%' {$whereProvince}
                         AND (mg.included_countries = '' OR mg.included_countries LIKE '%{$ud['country']}%'))
              ) AND (t.excluded_countries NOT LIKE '%{$ud['country']}%' AND (t.included_countries = '' OR t.included_countries LIKE '%{$ud['country']}%'))";


        if(!empty($limit))
            $limit_str = "LIMIT 0, $limit";

        $group_by_select = '';
        if($trophies_names) {
            $group_by_select = ", GROUP_CONCAT(t.alias SEPARATOR ',') as trophy_names";
            $group_by = 'GROUP BY t.category, t.sub_category';
        }


        $game_sel       = ", mg.game_name";
        $game_join      = "LEFT JOIN micro_games AS mg ON mg.ext_game_name = t.sub_category ";
        if ($device_num === 1) {
            $game_join .= " AND mg.mobile_id > 0";
        } else {
            $game_join .= " AND mg.device_type_num = $device_num";
        }

        $sql = "SELECT DISTINCT t.type, t.subtype, t.time_span, t.in_row, t.category,t.sub_category, t.hidden, t.alias, t.repeatable,t.game_ref, t.time_period, ev.cnt AS cnt, ev.user_id,
                    COALESCE(ev.progr, 0) AS progr, COALESCE(ev.finished, 0) AS finished,t.threshold as threshold, ev.threshold AS evthold, ev.id AS event_id,
                    COALESCE(mg.blocked_countries, ' ') AS blocked_countries, mg.active,
                    mg.included_countries AS included_countries
                    $game_sel $extra_sel $group_by_select
                FROM trophies t
                    $ev_join JOIN trophy_events AS ev ON ev.trophy_id = t.id AND ev.user_id = $uid $pr_where
                    $game_join
                    $where $where_extra
                    $group_by
                    $having
                    $order_by
                    $limit_str";

        return $sql;
    }

    /**
     * Gets the translated headline for a trophy section / group.
     *
     * @param array $trophy The trophy row.
     *
     * @return string The content.
     */
    function getTrophySectionHeadline($trophy){
        if(!empty($trophy['game_name']))
            return $trophy['game_name'];
        $trans = t("trophy.{$trophy['sub_category']}.headline");
        if(!empty($trans))
            return $trans;
        return '';
        // In case we have a game name we use is as the headline, otherwise we translate with the help of the sub category, eg: deposits
        //return empty($trophy['game_ref']) ? t("trophy.{$trophy['sub_category']}.headline") : $trophy['game_name'];
    }

    /**
     * This method is responsible for getting data for the initial display of the trophies account page,
     * if all trophies in a game related group have been completed we tack on extra information that can
     * be used by the user in order to reset those trophies which will enable him to complete them again.
     *
     * @see Trophy::getUserTrophiesSql()
     *
     * @param int $uid The user id.
     * @param bool $free_text If true $category will be matched as a sub string against the tropy category, sub_category and the game name.
     * @param string $type See main description of Trophy::getUserTrophiesSql().
     * @param string $category The trophy category to fetch.
     * @param string $sub_cat The sub category to fetch.
     * @param bool $trophies_names If true we will return all aliases in each group concatenated with commas and we will group by category and sub_category.
     *
     * @return array The result array.
     */
    function getUserTrophiesHeadlines($uid, $free_text = false, $type = 'normal', $category = '', $sub_cat = '', $trophies_names = true){
        $res = $this->getUserTrophies($uid, $category, $sub_cat, 'sub_category', $free_text, $type, '', '', $trophies_names);
        $cs  = $this->getEventByType('completed', $uid, 'game_ref', "AND finished = 1");
        $ret = array();
        foreach($res as $r){
            $tmp             = $r[0];
            $tmp['headline'] = $this->getTrophySectionHeadline($tmp);
            if(!empty($tmp['game_name'])){
                $tmp['sub_category'] = $tmp['game_ref'];
                $tmp['can_reset']    = true;
                $tmp['reset_col']    = empty($cs[$tmp['game_ref']]) ? 'grey' : 'green';
                $tmp['completed']    = !empty($cs[$tmp['game_ref']]);
            }
            $tmp['user_id'] = $uid;
            $ret[]          = $tmp;
        }

        $ret = phive()->sort2d($ret, 'headline');
        return $ret;
    }

    /**
     * Used to filter trophies.
     *
     * Is used to filter trophies based on user information, such as which PSPs the user
     * can see. Trophies that are connected to PSPs (for instance deposit 10 times with Swish)
     * that the user should not see should not show for instance.
     *
     * @param mixed $u_info Some kind of user info that can be used with cu() to retrieve a user object.
     * @param array $trophies An array of trophies.
     *
     * @return array The filtered array of trophies.
     */
    function filterTrophies($u_info, $trophies){
        $u_obj = cu($u_info);
        $c     = phive('Cashier');
        return array_filter($trophies, function($t) use($u_obj, $c){
            if(!in_array($t['type'], ['deposit', 'withdraw'])){
                // We ignore all non cashier related trophies.
                return true;
            }

            if(empty($t['subtype'])){
                // We ignore all generic cashier trophies, ie "deposit 3 times".
                return true;
            }

            return $c->doPspByConfig($u_obj, $t['type'], $t['subtype']);
        });
    }

    /**
     * Wrapper around Trophy::getUserTrophiesSql() that has a hardcoded grouping by GP network and GP operator
     * and fix sub category for game connected groups and also remove all groups attached to inactive games.
     *
     * @uses Trophy::getUserTrophiesSql()
     * @see Trophy::getUserTrophiesSql()
     *
     * @param int $uid The user id.
     * @param string $category The trophy category to fetch.
     * @param string $sub_category The sub category to fetch.
     * @param string $group_by Group by clause, if $trophies_names is true it will be overridden.
     * @param bool $free_text If true $category will be matched as a sub string against the tropy category, sub_category and the game name.
     * @param string $type See main description of Trophy::getUserTrophiesSql().
     * @param int $limit If not empty it will control how many rows are fetched.
     * @param string $order_by Optional order by clause.
     * @param bool $trophies_names If true we will return all aliases in each group concatenated with commas and we will group by category and sub_category.
     * @return array The result array.
     */
    function getUserTrophies($uid, $category = '', $sub_category = '', $group_by = '', $free_text = false, $type = 'normal', $limit = '', $order_by = '', $trophies_names = false){
        $sql = $this->getUserTrophiesSql($uid, $category, $sub_category , $free_text, $type, $limit, $order_by, '', ', mg.network, mg.operator', $trophies_names);
        $rows = $this->filterTrophies($uid, phive('SQL')->sh($uid)->loadArray($sql));

        if(empty($group_by))
            return $rows;

        if($group_by == 'sub_category'){
            foreach($rows as $row => &$r){
                if(!empty($r['game_name'])){
                    if (!empty($r['active'])) {
                        $r['sub_category'] = $r['game_name'];
                    } else {
                        unset($rows[$row]);
                    }
                }
            }
        }

        return phive()->group2d($rows, $group_by);
    }

    /**
     * On every win related logic that gets called in a forked process.
     *
     * There are 3 main types that gets handled here:
     * - Win x times the wager amount.
     * - Win x times in game y.
     * - Win x times in a row in game y.
     *
     * @param array &$ud User data / row.
     * @param array $cg Current game row.
     * @param int $amount The win amount.
     *
     * @return null
     */
    function onWin(&$ud, $cg, $amount){
        $cg = phive('MicroGames')->getDesktopGame($cg);
        $current_bet_amount = $this->memGet('curBetAmount', $ud, $cg);
        if(!empty($amount)) {
            $winx = (int)floor($amount / $current_bet_amount);
        }

        if (!empty($current_bet_amount)) {
            if(!empty($min_bet = $this->getSetting('min_bet_eur', 10))) {
                if ((float)chgToDefault($ud['currency'], $current_bet_amount, 1) < $min_bet) {
                    return;
                }
            }
        }

        $this->isGamePlay = true;
        $this->memSetGameAction($ud);
        $this->memSetGameAction($ud, $cg);
        foreach($this->getActiveTrophies($ud, $cg, 'win') as $t){
            switch($t['subtype']){
                case 'bigwin':
                    if($winx >= 15)
                        $this->handleBigWin($t, $ud, $cg, $winx);
                    break;
                    // case 'onetime':
                    //   $this->awardOrProgress($t, $ud, $amount, $ud['currency'], true);
                    //   break;
                case 'spins':
                    $this->awardOrProgress($t, $ud, 1);
                    break;
                default:
                    if(!empty($t['subtype']))
                        break;
                    if(!empty($t['in_row'])) // win x in row
                        $this->awardOrProgress($t, $ud, 1);
                    else
                        $this->awardOrProgress($t, $ud, $amount, $ud['currency']);
                    break;
            }
        }
    }

    /**
     * This method is responsible for using an internal JP award in order to credit the won jackpot amount.
     *
     * It is also doing JP reset work that is associated with any JP win. It is never called directly but
     * queued to 100% avoid a duplicate win scenario.
     *
     * @param int $jp_award_id The trophy award id.
     * @param int $uid The user id.
     * @param int $spin_time JP wheel spin time in microseconds, this is to avoid getting credited the JP amount
     * with associated notifications **before** the wheel has finished spinning.
     * @param int $win_id The wins are stored in the wins table, this is the mg_id value of the win in that table.
     *
     * @return null
     */
    function useJpAward($jp_award_id, $uid, $spin_time, $win_id){
        phive()->dumpTbl("jackpot-trace", "Trophy - useJpAward: $jp_award_id", $uid);

        $u_obj          = cu($uid);
        if(empty($u_obj)) {
            return false;
        }

        $jp_award       = $this->getAward($jp_award_id);

        $this->useAward($jp_award, $u_obj->getId(), [], $spin_time);
        $jpId           = $jp_award['jackpots_id'];
        // This is the only "connection" between the global and master table atm and it NEEDS TO STAY THIS WAY.
        $jp             = phive('SQL')->loadAssoc("SELECT * FROM jackpots WHERE id = $jpId");
        $usrAmt         = $jp['amount'] * (1 - $jp['contribution_next_jp']);
        $jpAmount       = $jp['amount'] - $usrAmt;
        $newAmount      = $jpAmount < $jp['amount_minimum'] ? $jp['amount_minimum'] : $jpAmount;
        phive('SQL')->updateArray('jackpots', array('amount' => $newAmount) , array('id' => $jpId));

        $userCurrAmount = chg(phive("Currencer")->baseCur(), $u_obj, $usrAmt, 1);
        phive()->dumpTbl("jackpot-trace", [$jpId, $newAmount, $userCurrAmount], $uid);
        $cur_game       = phive('MicroGames')->getByGameId('wheelofjackpots', 1, null, false, false);
        $formattedAmt   = phive()->twoDec($userCurrAmount);

        $map = [
            'MINI_JACKPOT'  => 94,
            'MAJOR_JACKPOT' => 95,
            'MEGA_JACKPOT'  => 96
        ];

        $newBalance     = phive('Casino')->changeBalance($u_obj, $userCurrAmount, " '{$jp_award['description']}' cash reward of {$u_obj->getAttr('currency')}$formattedAmt", $map[$jp['jpalias']]);

        $mgid           = "wheelofjackpots" . $win_id;
        phive('Casino')->insertWin($u_obj->data, $cur_game, $newBalance, "0", $userCurrAmount, 0, $mgid, 2);
        // the wheel log is updated to reflect the jackpot winning amount
        phive('SQL')->sh($u_obj)->updateArray('jackpot_wheel_log', array('win_jp_amount' => $userCurrAmount) , array('id' => $win_id));

        if (!empty($userCurrAmount)){
            try {
                foreach(phive('Config')->valAsArray('emails', 'wheel-win') as $email){
                    phive('MailHandler2')->mailLocal(
                        'JP Win',
                        "$uid won {$u_obj->getCurrency()} $userCurrAmount cents on ".$jp['jpalias'],
                        '',
                        $email
                    );
                }
            } catch (Exception $e) {
                error_log("Wheel mail failed: {$e->getMessage()}");
            }

        }
    }


    /**
     * This method is responsible for progressing xp and expiring awards
     */
    public function xpCronAndExpireAwards()
    {
        $this->xpCron();
        $this->expireActiveAwards();
    }

    /**
     * Retrieves URLs to launch or redirect to a specific game based on the given parameters.
     *
     * @param int $uid          The user id to activate for.
     * @param int $bonusId      The unique identifier of the bonus associated with the game.
     * @param string $lang      The preferred_lang of the user.
     * @param string $device    The type of device from which the game is being accessed (default is 'mobile').
     *
     * @return array An associative array containing the following keys:
     *               - 'launch_url'   => string  The URL to launch the game.
     *               - 'redirect_url' => string|null The URL to redirect to or null if no redirect URL is provided.
     *
     */
    private function getGameUrl(int $uid, int $bonusId, string $lang, string $device = 'mobile'): array
    {
        $setSessionUser = false;
        $bonus = phive('Bonuses')->getBonus($bonusId);
        $game = phive('MicroGames')->getByGameId($bonus['game_id'], 1);

        if (empty($_SESSION['user_id'])) {
            // Set session user ID if not already set, as it's required for proper functionality in onPlay()
            // onPlay() -> noDemo() -> isLogged() -> checks $_SESSION['user_id']
            $_SESSION['user_id'] = $uid;
            $setSessionUser = true;
        }
        
        $on_play_result = phive('MicroGames')->onPlay($game,
            [
                'uid' => $uid,
                'lang' => $lang,
                'type' => $device,
                'game_ref' => $bonus['game_id'],
                'show_demo' => false
            ]
        );
        
        if ($setSessionUser) {
            unset($_SESSION['user_id']);
        }
        
        list($launch_url, $redirect_url) = is_array($on_play_result) ? $on_play_result : [$on_play_result, null];

        return [
            'launch_url' => $launch_url,
            'redirect_url' => $redirect_url
        ];
    }
}
