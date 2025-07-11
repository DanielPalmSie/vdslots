<?php
require_once __DIR__ . '/../../api/ExtModule.php';
class Race extends ExtModule{

    function runMissingTimeSpan($sdate, $edate){
        phive('SQL')->applyToRows(
            "SELECT * FROM bets WHERE created_at >= '$sdate' AND created_at <= '$edate'",
            function($bet){
                $u        = cu($bet['user_id']);
                $uid      = $u->getId();
                // We don't do get race since we can't easily know if the player was using a bonus or not during the period in question
                //$get_race = phive('Bonuses')->canRace($uid);
                $get_race = true;
                $game     = phive('MicroGames')->getByGameRef($bet['game_ref']);
                if($get_race){
                    $ud = $u->data;
                    // We don't know multi since we can't know if the player had multi during the period
                    phive('Race')->raceBet($ud, $game, $bet['amount'], $bet['currency'], 1, false, true);
                }
            }
        );

    }

  function getCommon($tbl, $now = '', $uid = ''){
    $uid = intval($uid);
    $now = empty($now) ? phive()->hisNow() : $now;
      $where_uid = empty($uid) ? '' : " AND user_id = $uid";
      //test shard ok, if we don't have uid it refers to races which will be global anyway
    return phive('SQL')->sh($uid, '', $tbl)->arrayWhere($tbl, "start_time <= '$now' AND end_time >= '$now'".$where_uid);
  }

    function getActiveRaces($now = ''){
        $now = empty($now) ? phive()->hisNow() : $now;
        $disable_clash = $this->getSetting('clash_of_spins') !== true;

        return phive('SQL')->arrayWhere('races', "start_time <= '$now' AND end_time >= '$now' AND closed != 1" . $this->getClashSettingSQL());
    }

    function getTotalPrizePool($race = []){
        $race = empty($race) ? array_shift($this->getActiveRaces()) : $race;
        $prizes = explode(':', $race['prizes']);
        return array_sum($prizes);
    }

  function getActiveEntries($uid = '', $now = '', $create = false){
      $races = $this->getActiveRaces($now);
      $rids  = phive()->arrCol($races, 'id');
      $in    = phive('SQL')->makeIn($rids);
      $res   = phive('SQL')->sh($uid, '', 'race_entries')->loadArray("SELECT * FROM race_entries WHERE user_id = $uid AND r_id IN($in)");

      if(empty($res) && $create){
          $ud = ud($uid);
          foreach($races as $r) {
              if ($race_entry = $this->createRaceEntry($ud, $r)) {
                  $res[] = $race_entry;
              }
          }
      }
      return $res;
  }

    function raceEntries(&$r){
        return phive('SQL')->shs('merge', '', null, 'race_entries')->arrayWhere('race_entries', array('r_id' => $r['id']));
    }

    function curSpotPrize(&$es, &$ps, $uid = ''){
        $uid = empty($uid) ? uid() : $uid;
        if(empty($uid))
            return [];
        for($i = 0; $i < count($ps); $i++){
            if($uid == $es[$i]['user_id'])
                return array($i+1, $ps[$i]);
        }
        return array();
    }

  function curSpot(&$es){
    if(empty($_SESSION['mg_id']))
      return array();
    $uid = $_SESSION['mg_id'];
    for($i = 0; $i < count($es); $i++){
      if($uid == $es[$i]['user_id'])
        return $i+1;
    }
    return 0;
  }

  function curEntry($es){
    if(empty($_SESSION['mg_id']))
      return false;
    $uid = $_SESSION['mg_id'];
    foreach($es as $e){
      if($uid == $e['user_id'])
        return $e;
    }
    return false;
  }

    function getRace($id){
        $id = intval($id);
        return phive('SQL')->loadAssoc("SELECT * FROM races WHERE id = $id");
    }

    public function getRaceData($template) {
        if (is_numeric($template))
            $template = phive('SQL')->loadAssoc("SELECT * FROM race_templates WHERE id = $template");

        $race['template_id'] = $template['id'];

        // Copy template data
        foreach (['race_type', 'display_as', 'levels', 'prizes', 'prize_type', 'game_categories', 'games'] as $col) {
            $race[$col] = $template[$col];
        }
        return $race;
    }

  function validEntry(&$e, &$g){
    if(!empty($e['game_categories'])){
      if(in_array(strtolower($g['tag']), explode(',', strtolower($e['game_categories']))) || $g['tag'] == 'casino-playtech')
        return true;
    }else{
      if(in_array(strtolower($g['ext_game_name']), explode(',', strtolower($e['games']))))
        return true;
    }
    return false;
  }

  function raceBetWs($uid, $gid, $bet_amount, $currency, $multi = 1, $do_ws = true, $create = false){
    $u = ud($uid);
    $g = phive('MicroGames')->getById($gid);
    $this->raceBet($u, $g, $bet_amount, $currency, $multi, $do_ws, $create);
  }

    /**
     *  Check if a country is excluded from races/clashes/etc
     *
     * @param DBUser $user
     * @return bool False if the country is not excluded
     */
    public function countryIsExcluded($user)
    {
        $excluded_countries = phive('Config')->valAsArray('exclude-countries', 'clash-of-spins');
        if (in_array(cu($user)->getCountry(), $excluded_countries)) {
            return true;
        }
        return false;
    }

  function raceBet(&$u, &$g, $bet_amount, $currency, $multi = 1, $do_ws = true, $create = false){
    if(empty($bet_amount))
        return false;
      phive('Trophy')->memSet('lastBetAmount', $u, $g, $bet_amount, 600);
      $bet_amount = mc($bet_amount, $currency, 'div');

    foreach($this->getActiveEntries($u['id'], '', $create) as $e){
      if($e['race_type'] == 'spins' && $this->validEntry($e, $g)){
        $spins = 0;
        if(is_numeric($e['levels'])){
          if($bet_amount >= $e['levels'])
            $spins = 1;
        }else{
          $thold = phive()->fromDualStr($e['levels']);
          foreach($thold as $lvl => $num_spins){
            if($bet_amount >= $lvl)
              $spins = $num_spins;
          }
        }

          if(!empty($spins)){
              $inc_with = round($spins * $multi);
              $race_balance = $e['race_balance'] + $inc_with;
              // TODO store in Redis if the race tab is open or not, do not run wsOnBet if it isn't.
              // TODO don't run this as a separate fork if turned on again.
              if($do_ws)
                  phive()->pexec('Race', 'wsOnBet', array($u['id'], $race_balance, cleanShellArg($u['firstname'])));
              $this->incRaceBalance($inc_with, $e['id'], $e['user_id']);
          }
      }
    }
    return $spins;
  }

  function wsOnBet($uid, $spins, $fname){
    toWs(array('spins' => $spins, 'user_id' => $uid, 'fname' => ucfirst(strtolower($fname))), 'racetab', 'na');
  }

  function isActive(&$race){
    return (time() > strtotime($race['start_time']) && time() < strtotime($race['end_time']));
}

    private function getRecurringDatesInInterval($start, $end, $recur_type, $recurring_days)
    {
        $start = phive()->fDate($start);
        $end = phive()->fDate($end);
        $res = [];

        $done = false;

        for ($i = 0;!$done; $i++) {
            $done = true;

            switch ($recur_type) {
                case 'weekly':
                    $base_date = date('Y-m-d', strtotime('previous monday', strtotime($start)));
                    $base_date = phive()->modDate($base_date, " +$i weeks");
                    break;
                case 'monthly':
                    $base_date = date('Y-m-01', strtotime($start));
                    $base_date = phive()->modDate($base_date, " +$i months");
                    break;
                case 'yearly':
                    $base_date = date('Y-01-01', date('Y-m-01', strtotime($start)));
                    $base_date = phive()->modDate($base_date, " +$i years");
                    break;
                default:
                    return [];
            }

            foreach ($recurring_days as $recurring_day) {
                $recurring_day--;
                $recur_date = phive()->modDate($base_date, " +$recurring_day days");
                if ($recur_date <= $end) {
                    if ($recur_date >= $start) {
                        $res[] = $recur_date;
                    }
                    $done = false;
                }
            }

        }
        return $res;
    }

    public function getRacesInInterval($start, $end, $reverse = false)
    {
        $race_templates = phive('SQL')->arrayWhere('race_templates', "start_date <= '$end' AND (recurring_end_date >= '$start' OR recurring_end_date = '')");
        $res = $races = phive('SQL')->loadArray("SELECT * FROM races WHERE start_time <= '$end' AND end_time >= '$start' ORDER BY start_time DESC");

        foreach ($race_templates as $race_template) {
            $recurring_days = explode(',', $race_template['recurring_days']);
            $recur_type = $race_template['recur_type'];
            $duration = $race_template['duration_minutes'];
            $start_date = max($start, $race_template['start_date']);

            if (empty($race_template['recurring_end_date']) || $race_template['recurring_end_date'] == '0000-00-00 00:00:00') {
                $end_date = $end;
            } else  {
                $end_date = min($end, $race_template['recurring_end_date']);
            }

            foreach ($this->getRecurringDatesInInterval($start_date, $end_date, $recur_type, $recurring_days) as $occurance) {
                $start_time = phive()->hisNow(strtotime($occurance . " " . $race_template['start_time']));
                $end_time = phive()->hisNow(strtotime($start_time . " +$duration minutes"));

                // Also need to make sure the occurence is in the time intervall
                if ($start > $end_time || $end < $start_time) {
                    break;
                }

                $addRace = true;

                // Check if the race already is in the database.
                foreach ($races as $race) {
                    if (phive()->fDate($race['start_time']) == $occurance && $race['template_id'] == $race_template['id']) {
                        $addRace = false;
                        break;
                    }
                }

                // Adding the race to the list if its not in the database.
                if ($addRace) {
                    $race = $this->getRaceData($race_template);
                    $race['start_time'] = $start_time;
                    $race['end_time'] = $end_time;
                    $res[] = $race;
                }
            }
        }


        usort($res, function ($a, $b) use ($reverse) {
            return strcmp($a['start_time'], $b['start_time']) * ($reverse ? -1 : 1);
        });

        // Reordering: we always want to have the finished races last.
        $not_finished = [];
        $finished = [];
        foreach ($res as $r) {
            if ($r['closed'] == 1) {
                $finished[] = $r;
            } else {
                $not_finished[] = $r;
            }
        }
        $res = array_merge($not_finished, $finished);

        return $res;
    }

    function leaderBoard($r, $only_entries = false, $limit = '', $uid = ''){
        if(empty($r)){
            error_log('Caught error: race array missing for Race::leaderboard()');
            // We need a race in order to get its race, entries, if we don't have one we return immediately.
            return [];
        }
        $prizes = explode(':', $r['prizes']);
        $this->all_prizes = $prizes;
        $limit  = empty($limit) ? count($prizes) : (int)$limit;
        $prizes = array_slice($prizes, 0, $limit);
        // We don't want to do this if we're in a cron job such as the calc prizes job
        if (!isCli() && $r['prize_type'] == 'cash') {
            foreach ($prizes as &$prize) {
                $prize = dedPc($prize);
            }

        }

        if (!empty($r['id'])) {
            $str     = "SELECT * FROM race_entries WHERE r_id = {$r['id']} AND race_balance > 0 ORDER BY race_balance DESC LIMIT 0, $limit";
            $entries = phive('SQL')->shs('merge', 'race_balance', 'desc', 'race_entries')->loadArray($str);
        } else {
            error_log('Caught error: race id missing for Race::leaderboard()');
            $entries = [];
        }

        // We're passing in a user id so we want the subset around that user
        if(!empty($uid)){
            list($cspot, $cprize) = $this->curSpotPrize($entries, $prizes, $uid);
            // If current user is not on the board we just grab top 20
            if(empty($cspot)){
                $cspot  = 0;
                $offset = 1;
                $length = 20;
            }else{
                $offset  = 10;
                $length  = $offset * 2;
            }

            // We might need to preserve keys here
            $entries = array_slice($entries, max(($cspot - $offset), 0), $length);

            $prizes  = array_slice($prizes, max(($cspot - $offset), 0), $length);

            if($offset > $cspot) {
              $offset = $cspot - 1;
            }

            $i = $cspot - $offset;
            foreach($entries as &$e){
                $e['spot'] = $i;
                $i++;
            }
        }
        //phMset('lboard-'.$r['id'], json_encode (array($entries, $prizes)));
        if($only_entries)
            return $entries;
        return array($entries, $prizes);
    }

    public function getClashSettingSQL() {
        // If setting 'clash_of_spins' is not set to true, dont accept clashes => Only process races with template_id = 0
        return $this->getSetting('clash_of_spins') === true ? '' : ' AND template_id = 0';
    }

    public function payAwards($start = '', $end = '') {
        if (empty($end))
            $end = phive()->modDate(phive()->modDate("", "tomorrow"), "last monday", "Y-m-d H:i:s"); // This monday at 00:00

        if (empty($start))
            $start = phive()->modDate($end, "-7 days", "Y-m-d H:i:s"); // Last weeks monday at 00:00

        $notifications = [];
        $users = [];

        foreach (phive('SQL')->arrayWhere('races', "closed = 1 AND prize_type = 'award' AND end_time >= '$start' AND end_time < '$end'") as $race) {
            list($entries, $prizes) = $this->leaderBoard($race);

            foreach (array_values($prizes) as $i => $prize) {
                $entry = $entries[$i];
                if ($entry['payed_out'] == 0) {
                    $user_id = $entry['user_id'];
                    $user = cu($user_id);
                    if (!empty($user)) {
                        $users[] = $user;
                    }
                    $country = $user->data['country'];

                    list($scheduled, $not_scheduled) = phive('CasinoCashier')->getScheduledCountries(CasinoCashier::SCHEDULE_CLASH_OF_SPINS);

                    if ($scheduled == $country || $scheduled == 'NA' && !in_array($country, $not_scheduled))
                    {
                        $award = $this->getRaceAwardByPrize($prize, $user->userId);
                        phive('Trophy')->giveAward($award, $user->data);
                        phive("SQL")->sh($entry, 'user_id', 'race_entries')->updateArray('race_entries',  array('payed_out' => 1), "id = ".$entry['id']);

                        if (!isset($notifications[$user_id])) {
                            $notifications[$user_id] = [$user, 0];
                        }

                        $notifications[$user_id][1] += $award['amount'];
                    }
                }
            }
        }
        $users = phive('MailHandler2')->filterMarketingBlockedUsers($users, true);
        foreach ($notifications as $user_id => list($user,$amount)) {
            $user->marketing_blocked = !in_array($user_id, $users);
            // Transaction type awardedracepayout => 32
            phive('CasinoCashier')->notifyUserTransaction(32, $user, $amount, false);
        }

    }

    function calcPrizes($now = ''){
        $now = empty($now) ? phive()->hisNow() : $now;
        $init = false;

        foreach(phive('SQL')->arrayWhere('races', "closed != 1 AND start_time <= '$now'" . $this->getClashSettingSQL()) as $r){
            $close = $r['end_time'] <= $now;
            //test shard shs
            // We reset because we're going to calculate this again below with the help of the balance
            if($close)
                phive('SQL')->shs('', '', null, 'race_entries')->query("UPDATE race_entries SET prize = 0, spot = 0 WHERE r_id = {$r['id']}");
            list($entries, $prizes) = $this->leaderBoard($r);
            $i = 1;
            foreach($entries as $e){
                if(empty($e['race_balance']))
                    continue;
                if(($e['spot'] > $i || empty($e['spot'])) && !empty($e['user_id']) && !$close)
                    uEvent('advancedinrace', $i, '', '', $e['user_id']);
                //test shard sh
                if($e['spot'] != $i){
                    phive('SQL')->sh($e, 'user_id', 'race_entries')->updateArray('race_entries', array('spot' => $i, 'prize' => $prizes[$i - 1]), array('id' => $e['id']));
                }

                $i++;
            }

            if($close){
                $init = true;
                $r['closed'] = 1;
                phive('SQL')->save('races', $r);
                $this->qPrizes($r);
                $this->wsOnBet('msg', 'race.closed', 'na');
            }
        }

        if($init) {
            $this->initNewRaces();
        }
    }

    public function initNewRaceTemplates() {
        $now = phive()->hisNow();
        $new_races = false;
        foreach($this->getRacesInInterval($now, $now) as $r) {
            if (empty($r['id'])) {
                phive('SQL')->save('races', $r);
                $new_races = true;
            }
        }
        return $new_races;
    }

    public function initNewRaces(){

        foreach(phive('MicroGames')->getOpenGameSessions('user_id') as $gs){
            $this->initiateEntries(ud($gs['user_id']));
        }

        if(hasMp()){
            foreach(phive('Tournament')->getEntriesByStatus('open', 'user_id') as $te){
                $this->initiateEntries(ud($te['user_id']));
            }
        }

        /*
        // Deprecated, using Redis::asArr() which we want to avoid.
        $uh = phive('UserHandler');
        foreach(phM('asArr', 'curgid-*') as $key => $gid){
            list($txt, $uid) = explode('-', $key);
            $this->initiateEntries(ud($uid));
        }
        */
    }

    //Used to fix fuck ups with the race config by admins
    // TODO broken with sharded races
  function replayBets($stime, $etime){
    $bets = phive('SQL')->arrayWhere('bets_tmp', "created_at >= '$stime' AND created_at <= '$etime'");
    foreach($bets as &$b){
      $u = ud($b['user_id']);
      $g = phive('MicroGames')->getByGameRef($b['game_ref']);
      if(empty($b['loyalty']))
        continue;
      $spins = $this->raceBet($u, $g, $b['amount'], $b['currency'], 1, false);
      //if(!empty($spins))
      //  echo "{$b['user_id']} {$b['game_ref']} $spins\n";
    }
  }

    public function getRaceAwardByPrize($prize, $user_id = '') {
        list($primary, $alternative) = explode(',', $prize);
        $primary_award = phive('Trophy')->getExtAward($primary);

        if ($alternative === null)
            return $primary_award;

        $bonus = phive('Bonuses')->getBonus($primary_award['bonus_id']);
        $game = phive('MicroGames')->getByGameId($bonus['game_id']);

        if (phive('MicroGames')->isBlocked($game, cuCountry($user_id), true))
            return phive('Trophy')->getExtAward($alternative);

        return $primary_award;
    }

    /**
     * Calculate payouts.
     *
     * @param array $r - Race data
     */
    function qPrizes(&$r){
        $c = phive('Cashier');
        $uh = phive('UserHandler');
        list($entries, $prizes) = $this->leaderBoard($r);

        foreach($entries as $e){
            if(empty($e['user_id']))
                continue;
            $user = cu($e['user_id']);
            $prize = $e['prize'];

            if (!empty($prize)) {
                switch ($r['prize_type']) {
                    case 'cash':
                        $prize = dedPc(mc($prize, $user), $user, false);
                        $c->qTrans($e['user_id'], $prize, "Casino race {$r['id']}", 32);
                        break;
                    case 'award':
                        uEvent('finishedclash', $e['spot'], '', '', $user->data);
                        break;
                    default:
                        break;
                }
            }

        }
        if(phive()->moduleExists('Trophy'))
            phive('Trophy')->raceFin($r);
    }

  function raceEntry($rid, $uid){
    $rid = is_array($rid) ? $rid['id'] : $rid;
    return $this->getEntry(array('r_id' => $rid, 'user_id' => $uid));
  }

    function createRaceEntry($u, $r){
        if($this->countryIsExcluded($u)) {
            return false;
        }

        $old = phive('SQL')->sh($u, 'id', 'race_entries')->loadAssoc("SELECT * FROM race_entries WHERE user_id = {$u['id']} AND r_id = {$r['id']}");
        if(!empty($old))
            return $old;
        $ins = array(
            'r_id'            => $r['id'],
            'user_id'         => $u['id'],
            'games'           => $r['games'],
            'game_categories' => $r['game_categories'],
            'race_type'       => $r['race_type'],
            'levels'          => $r['levels'],
            'start_time'      => $r['start_time'],
            'end_time'        => $r['end_time'],
            'firstname'       => $u['firstname'],
            'prize'           => 0
        );

        //test shard sh
        $ins['id'] = phive('SQL')->sh($u, 'id', 'race_entries')->insertArray('race_entries', $ins);
        return $ins;
    }

    //shard todo we need user id here
  function incRaceBalance($spins, $eid, $uid){

	  phive('Logger')->getLogger('casino')->debug('HandleWin->onWin->onAwardBigWin->incRaceBalance->BEGIN',
		  [
			  'increase_with' => $spins,
			  'race_entry_id' => $eid,
			  'user_id' => $uid
		  ]);

      return phive('SQL')->incrValue('race_entries', 'race_balance', array('id' => $eid), $spins, [], $uid);
      //return phive('SQL')->sh($uid, '', 'race_entries')->updateArray('race_entries', array('race_balance' => $spins), array('id' => $eid));
  }

  function initiateEntries($u = array(), $now = '', $check_deposit = true){
    $u = empty($u) ? $_SESSION['local_usr'] : $u;

    if($check_deposit){
      $cnt = phive('Cashier')->hasDeposited($u['id']);
      if(empty($cnt))
        return false;
    }

    foreach($this->getActiveRaces($now) as $r){
      $entry = $this->raceEntry($r['id'], $u['id']);
      if(empty($entry))
        $this->createRaceEntry($u, $r);
    }
  }

    //shard todo we ned user id here if possible
  function getEntry($where = array()){
    return phive('SQL')->sh($where, 'user_id', 'race_entries')->loadAssoc('', 'race_entries', $where);
  }


    /**
     * @param array $ud
     * @param $cg
     * @param $amount
     */
    public function onWin(&$ud, &$cg, $amount) {

		phive('Logger')->getLogger('casino')->debug('HandleWin->onWin->BEGIN',
			[
				'user' => $ud,
				'current_game' => $cg,
				'amount' => $amount,
			]);

        if(!empty($amount)) {
            $cg = phive('MicroGames')->getDesktopGame($cg);
            $min_bet = (int)mc($this->getSetting('min_bet')['bigwin'], $ud['currency']);
            $last_bet = (int)phive('Trophy')->memGet('lastBetAmount', $ud, $cg);

			phive('Logger')->getLogger('casino')->debug('HandleWin->onWin->ongoing',
				[
					'last_bet' => $last_bet,
					'min_bet' => $min_bet,
					'winx' => floor($amount / $last_bet),
					'amount' => $amount,
					'is_big_win' => is_finite(floor($amount / $last_bet)) && floor($amount / $last_bet) >= 15
				]);


            if ($last_bet >= $min_bet) {
                $winx = floor($amount / $last_bet);
                if(is_finite($winx) && $winx >= 15) {
                    if ($this->getSetting('clash_debug')['bigwin'] === true) {
                        phive()->dumpTbl('clash-debug-bigwin', ['winx', $ud['id'], $winx], $ud['id']);
                    }
                    $this->onAwardBigWin($ud, $winx, $cg);
                }
            }
        }
    }


    function onAwardBigWin(&$ud, $winx, &$cg) {

        if ($this->getSetting('clash_of_spins') !== true) {
            return;
        };

        // Global points settings for bigwin races (clash of spins). This is now used
        // as a fallback in case the e['levels'] doesn't contain the appropiate keys.
        $big_win_points = $this->getSetting('fallback_big_win_levels');

		phive('Logger')->getLogger('casino')->debug('HandleWin->onWin->onAwardBigWin->BEGIN',
			[
				'big_win_points' => $big_win_points,
				'active_entries' => $this->getActiveEntries($ud['id'], '', false)
			]);

        foreach ($this->getActiveEntries($ud['id'], '', false) as $entry) {

            $valid_entry = $this->validEntry($entry, $cg);

            if(!$valid_entry) {

                return;
            }

            if ($entry['race_type'] == 'bigwin') {

                $threshold = phive()->fromDualStr($entry['levels']);
                $points = phive()->tholdLvl($winx, $threshold, 0);

				phive('Logger')->getLogger('casino')->debug('HandleWin->onWin->onAwardBigWin->fromEntryLevels`',
					[
						'$threshold' => $threshold,
						'$points' => $points
					]);

                if (empty($points)) {
                    $threshold = phive()->fromDualStr($big_win_points);
                    $points = phive()->tholdLvl($winx, $threshold, 0);
                }

				phive('Logger')->getLogger('casino')->debug('HandleWin->onWin->onAwardBigWin->fromFallbackEntryLevels`',
					[
						'$threshold' => $threshold,
						'$points' => $points
					]);

                if (!empty($points)) {
                    if ($this->getSetting('clash_debug')['bigwin'] === true) {
                        phive()->dumpTbl('clash-debug-bigwin', ['inc', $ud['id'], $points, $threshold, $cg, $entry], $ud['id']);
                    }
                    $this->incRaceBalance($points, $entry['id'], $entry['user_id']);
                }
            }
        }
    }

}
