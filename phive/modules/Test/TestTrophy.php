<?php
class TestTrophy extends TestPhive{
    
    function jpAward($aid , $u){
     
        $this->th = phive('Trophy');
        $this->th->giveAward($aid, $u->data);
    }
    
    function setup($uid, $gid = null, $tid = null, $comp_tid = null){
        $this->db = $sql = phive('SQL');
        $this->th = phive('Trophy');
        if($gid){
            $this->cg = $sql->loadAssoc("select * from micro_games where game_id = '$gid'");
        }
        $this->ud = ud($uid);
        $this->uid = $uid;
        if($tid){
            $this->t = $this->th->get($tid);
        }

        if($comp_tid){
            $this->completed_t = $this->th->get($comp_tid);
        }
        //print_r($this->cg);
        $this->sstamp = phive()->hisMod('-1 day');
        $this->estamp = phive()->hisNow();        
        return $this;
    }

    function testDeposit($aid, $u_obj = null){
        $this->ud = empty($u_obj) ? $this->ud : $u_obj->data;
        $this->uid = $this->ud['id'];
        $this->th->delCurAward($this->uid);
        $this->th->giveAward($aid, $this->ud);
        $this->th->useAward($aid, $this->uid);
        phive('Casino')->depositCash($this->ud, 10000, 'neteller', uniqid(), '', '', '', false, 'approved', null, rand(1000000, 2000000));
        //$this->th->execDepositAward($this->uid, ['cents' => 10000]);
    }
    
    function setInfo($type){
        //TODO resetTrophies, deleteEvents, completeCron
        $this->test_methods = [
            'getEvent'          => [$this->t, $this->ud],
            'getEvents'         => [$this->t],
            'getCompletedCount' => [$this->completed_t],
        ];
        return $this;
    }
    
  function changeUser($uid){
    $this->ud = cu($uid)->data;
  }
  
  function generateEvent($alias, $tag = 'trophyaward', $name = 'trophyname'){
    uEvent($tag, '', "t:$name.$alias", '', $this->ud, $alias);
  }
  
  function truncateTables(){
    $this->db->query("TRUNCATE TABLE `trophy_ownership`");
    $this->db->query("TRUNCATE TABLE `trophy_award_ownership`");
    $this->db->query("TRUNCATE TABLE `trophy_events`");
    $this->db->query("TRUNCATE TABLE `bonus_entries`");
    $this->db->query("TRUNCATE TABLE `users_notifications`");
    $this->db->query("TRUNCATE TABLE `users_settings`");
    //$this->db->query("TRUNCATE TABLE `first_deposits`");
  }

  function arbComplete($to_complete, $completed, $from = '', $to = ''){
    $this->db->query("DELETE FROM trophies WHERE alias = 'test-arb-complete'");
    $ins = array(
      'alias'         => 'test-arb-complete',
      'type'          => 'completed',
      'threshold'     => count($to_complete),
      'category'      => 'activity',
      'sub_category'  => 'trophies',
      'completed_ids' => implode(',', $to_complete)
    );
    if(!empty($from)){
      $ins['valid_from'] = $from;
      $ins['valid_to'] = $to;
    }
    $this->db->insertArray('trophies', $ins);
    foreach($completed as $cid){
      $t = $this->th->get($cid);
      $this->th->awardTrophy($t, $this->ud);
    }
  }
  
  function showFinished(){
    $events = $this->db->loadArray("SELECT * FROM trophy_events WHERE finished = 1");
    foreach($events as $e){
      $trophy = $this->db->loadAssoc("SELECT * FROM trophies WHERE id = {$e['trophy_id']}");
      print_r($trophy);
    }
  }

  function awardXawards($x){
    $awards = $this->db->loadArray("SELECT * FROM trophy_awards GROUP BY type ORDER BY RAND() LIMIT 0, $x");
    foreach($awards as $a)
      $this->th->giveAward($a, $this->ud, 5000000);
  }

  function giveAward($aid){
    $a = $this->th->getAward($aid);
    $this->th->giveAward($a, $this->ud, 5000000);
  }
  
  function awardAllTrophies(){
    foreach($this->db->loadArray("SELECT * FROM trophies") as $t){
      $this->th->awardTrophy($t, $this->ud);
    }
  }
  
    function execAward($aid, $u_obj = null){
        if(!empty($u_obj)){
            $this->ud = $u_obj->data;
        }
        $this->th->useAward($aid, $this->ud['id']);
        $this->th->execDepositAward($this->ud['id'], array('cents' => 100000));
    }

  function giveFreespin(){
    //1808 bonus id
    $this->db->query("DELETE FROM trophy_awards WHERE type = 'freespin-bonus'");
    $insert = array(
      'type'     => 'freespin-bonus',
      'amount'   => 11,
      'bonus_id' => 1808,
      'alias'    => '11_freespins'
    );
    $aid = phive('SQL')->insertArray('trophy_awards', $insert);
    $a = $this->th->getAward($aid);
    $this->th->giveAward($a, $this->ud);
  }
  
  function expireAwards($exp_date){
    $this->db->query("UPDATE trophy_award_ownership SET expire_at = '$exp_date'");
    $this->db->query("UPDATE users_settings SET value = '$exp_date' WHERE setting LIKE 'awardexp-%'");
    $this->th->expireAwards();
  }
  
  function insertRaceEntry($uid, $amount, $spot){
    $this->db->insertArray('race_entries', array(
      'r_id' => 1,
      'user_id' => $uid,
      'prize' => $amount,
      'spot' => $spot,
      'race_balance' => $amount,
      'firstname' => $uid,
      'start_time' => phive()->hisNow(),
      'levels' => '25:1|100:2|200:3',
      'end_time' => phive()->hisMod('+7 day')
    ));
  }

  //Run
  function raceFin($fail, $amount, $spot, $do_fin = true){
    $this->db->query("TRUNCATE TABLE races");
    $this->db->query("TRUNCATE TABLE race_entries");
    $this->db->insertArray('races', array(
      'levels' => '25:1|100:2|200:3',
      'prizes' => '300:200:100:10:5:1:1:1:1',
      'start_time' => phive()->hisNow(),
      'end_time' => phive()->hisMod('+7 day')
    ));
    for($i = 2; $i < 12; $i++)
      $this->insertRaceEntry($i, $i * 100, $i-1);
    if(!$fail)
      $this->insertRaceEntry($this->ud['id'], $amount, $spot);
    $race = $this->db->loadAssoc("SELECT * FROM races WHERE id = 1");
    if($do_fin)
      $this->th->raceFin($race);
  }
  
  function getDaily(){
    return array(
      'username' => $this->ud['username'],
      'user_id' => $this->ud['id'],
      'gen_loyalty' => rand(10, 10000),
      'date' => phive()->today()
    );
  }

  function insertQtrans($uid, $type = 31){
    $this->db->insertArray('queued_transactions', array('user_id' => $uid, 'amount' => rand(100, 10000), 'transactiontype' => 31));
  }

  //Run
  function testPayout($fail = false){
    $this->db->query("TRUNCATE TABLE queued_transactions");
    if(!$fail)
      $this->insertQtrans($this->ud['id']);
    $this->insertQtrans(20916);
    $this->th->payoutEvent(array(1,2));
  }
  
  //to test fail, first run this with false and times less than thold, then with true
  //Run
  function testEvents($times = 1, $test_fail = false, $types = array(), $do_cron = true){
    if(empty($types))
      $types = array('login', 'verify', 'deposit', 'withdraw');
    for($i = 0; $i < $times; $i++){
      foreach($types as $type){
        $this->th->onEvent($type, $this->ud['id']);        
      }
      if(!$do_cron)
        return;
      if(!$test_fail){
        $this->db->insertArray('bets_tmp', $this->getBet());
        $this->db->insertArray('users_daily_stats', $this->getDaily());
      }else{
        $this->db->query('TRUNCATE TABLE bets_tmp');
        $this->db->query('TRUNCATE TABLE users_daily_stats');
      }
      $this->th->dayCron(phive()->today());
    }
  }

  //Run
  function xp($points = 196, $truncate = true){
    if($truncate)
      $this->db->query("TRUNCATE TABLE users_settings");
    $user = cu($this->ud['id']);
    //$user->setSetting('xp-level', 0);
    $user->setSetting('xp-points', $points);
    $this->th->xpCron();
  }

  //Run
  function completeGame($fail = false, $complete = true){
    $str = "SELECT * FROM trophies WHERE game_ref = '{$this->cg['ext_game_name']}' AND device_type = {$this->cg['device_type_num']}  AND type != 'completed' AND category LIKE 'games%'";
    //echo $str;
    $trophies = $this->db->loadArray($str);
    foreach($trophies as $t){
      if($fail)
        break;
      $this->th->awardTrophy($t, $this->ud);
      $this->th->updateEvent($t, $this->ud, $t['threshold']);
    }

    if(!$complete){
      $progr = $t[0]['threshold'] - 1;
      $this->db->query("UPDATE trophy_events SET progr = '$progr'");
      $this->db->query("DELETE FROM trophy_ownership WHERE id = 1");
    }
    
    if($complete)
      $this->th->completeCron();
  }

  //call a second time with fail -> true to fail win x times in row
  //Run
  function playGame($times, $fail = false, $bamount = 10, $wamount = 90, $minute_cron = false){
    for($i = 0; $i < $times; $i++){
      echo "Bet $i\n";
      $this->th->onBet($this->ud, $this->cg, $bamount);
      if(!$fail)
        $this->th->onWin($this->ud, $this->cg, $wamount);
      if($minute_cron){
        $this->th->minuteCron();
        $this->th->xpCron();
        $this->th->completeCron();
      }        
    }
  }

    function testMinuteCron($uid1 = 20916, $uid2 = 5179326, $game_ref = 'netent_twinspin_not_mobile_sw', $insert_wins = true){
        $this->db->truncate('wins', 'wins_mp', 'bets', 'trophy_events');
        foreach([$uid1, $uid2] as $uid){
            $u = cu($uid);
            if($insert_wins){
                foreach(range(1, 1) as $i){
                    $mg_id = rand(1, 99999999999);
                    foreach(['wins', 'wins_mp'] as $tbl){
                        $insert = [
                            'game_ref'   => $game_ref,
                            'user_id'    => $uid,
                            'amount'     => 100,
                            'mg_id'      => $mg_id,
                            'award_type' => 2,
                            'bonus_bet'  => 0,
                            'currency'   => $u->getCurrency()];
                        if($tbl == 'wins_mp')
                            $insert['e_id'] = 102;
                        $this->db->insertArray($tbl, $insert);
                    }
                }
            }
        }
        sleep(1);
        $this->th->minuteCron(false, true);
    }
    
  function getWin(){
    return array(
      'trans_id' => rand(1, 1000),
      'game_ref' => $this->cg['ext_game_name'],
      'user_id' => $this->ud['id'],
      'amount' => 100, // rand(10, 10000),
      'mg_id' => rand(1, 99999999999999999),
      'award_type' => 2,
      'currency' => $this->ud['currency'],
      'device_type' => $this->cg['device_type_num']
    );
  }
  
  function getBet(){
    return array(
      'trans_id' => rand(1, 1000),
      'game_ref' => $this->cg['ext_game_name'],
      'user_id' => $this->ud['id'],
      'amount' => 100, // rand(10, 10000),
      'mg_id' => rand(1, 99999999999999999),
      'currency' => $this->ud['currency'],
      'device_type' => $this->cg['device_type_num'],
      'loyalty' => rand(1, 100)
    );
  }

    function truncate(){
        $this->db->query('TRUNCATE TABLE bets');
        $this->db->query('TRUNCATE TABLE bets_mp');
        $this->db->query('TRUNCATE TABLE wins');
        $this->db->query('TRUNCATE TABLE wins_mp');
        $this->db->query('TRUNCATE TABLE tournament_entries');        
    }
    
  //Run
    function winPeriod($times, $t_e_id){
        $this->db->insertArray('tournament_entries', ['id' => $t_e_id, 'user_id' => $this->ud['id'], 'get_trophy' => 1]);
        for($i = 0; $i < $times; $i++){
            $win = $this->getWin();
            $bet = $this->getBet();
            $this->db->insertArray('wins', $win);
            $this->db->insertArray('bets', $bet);

            $win         = $this->getWin();
            $win['e_id'] = $t_e_id;
            $win['currency'] = 'EUR';
            $bet         = $this->getBet();
            $bet['e_id'] = $t_e_id;
            $bet['currency'] = 'EUR';
            $this->db->insertArray('wins_mp', $win);
            $this->db->insertArray('bets_mp', $bet);
        }
    }
    
}
