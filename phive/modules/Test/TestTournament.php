<?php
class TestTournament extends TestPhive{

  /*
     select * from cash_transactions where transactiontype in(38,39)
   */
    function __construct(){
        $this->t = phive('Tournament');
        $this->db = phive('SQL');
    }

    function reviveEntrysTournament($eid, $uid){
        $entry = $this->t->entryById($eid, $uid);
        $t = $this->t->byId($entry['t_id']);
        $t['status'] = 'in.progress';
        $t['duration_minutes'] = 60;
        $t['end_time'] = phive()->getZeroDate();
        $t['start_time'] = phive()->hisNow();
        $this->db->save('tournaments', $t);
    }
    
    public function setupRegQueueTest($tpl_id, $uids = [], $make_full = true){
        $this->db->delete('tournaments', ['tpl_id' => $tpl_id]);
        $tpl = $this->t->getTplById($tpl_id);
        $tid = $this->t->insertTournament($tpl);

        echo "First tid: $tid\n";
 
        $t = $this->t->byId($tid);
        if($make_full){
            $t['registered_players'] = $t['max_players'];
            $this->db->save('tournaments', $t);
        }
        $this->truncateLog();

        echo "Queue results:\n";
        foreach($uids as $uid){
            $u_obj = cu($uid);
            $u_obj->verify();
            $u_obj->setAttr('cash_balance', 1000000);
            $u_obj->setAttr('logged_in', 1);
            $u_obj->setSetting('freeroll-tester', 1);
            $this->clearTable($u_obj, ['tournament_entries', 'rg_limits']);
            // This should put them in the queue.
            $res = $this->t->queueReg($tid, $u_obj, []);
            print_r($res);
        }
    }
    
    public function regQueueTest($tpl_id, $uids = []){
        $this->setupRegQueueTest($tpl_id, $uids);

        $tpl = $this->t->getTplById($tpl_id);

        sleep(2);

        echo "This should show an empty array:\n";
        $this->prLog();
        
        $tid = $this->t->insertTournament($tpl);

        echo "Second tid: $tid\n";

        sleep(5);

        echo "This should show data:\n";
        $this->prLog();
    }

    public function regOrQueue($t_id, $uid, $args){
        $res = $this->t->regRebuyCommon('register', $t_id, '', $uid, $args);
        if($res['status'] == 'queue_yes_no'){
            $this->t->queueReg($t_id, $uid, $args);
            phive()->dumpTbl('test-queue', 'queued', $uid);
        } else if(is_string($res)){
            phive()->dumpTbl('test-queue', $res, $uid);
        } else {
            phive()->dumpTbl('test-queue', $res, $uid);
        }
    }

    public function sngQueueStressTestPostAnalysis($tpl_ids, $num_players){
        $entries         = $this->db->shs()->loadArray("SELECT * FROM tournament_entries WHERE t_id IN( SELECT id FROM tournaments WHERE tpl_id IN( {$this->db->makeIn($tpl_ids)} ) )");

        $tournaments     = $this->db->loadArray("SELECT id FROM tournaments WHERE tpl_id IN( {$this->db->makeIn($tpl_ids)} )");
        $players         = $this->db->loadArray("SELECT * FROM users WHERE country = 'FI' LIMIT $num_players", 'ASSOC', 'id');
        $registered_uids = array_column($entries, 'user_id');
        // echo "Not registered users:\n";
        foreach($players as $ud){

            /*
            $dump_row = $this->db->loadAssoc("SELECT * FROM trans_log WHERE user_id = {$ud['id']} AND tag = 'test-queue'");
            if(!empty($dump_row)){
                echo "{$ud['id']} {$dump_row['dump_txt']}\n";
            }            
            */
            
            //*
            if(!in_array($ud['id'], $registered_uids)){
                echo "Not registered: {$ud['id']}\n";
                // print_r($ud);
            } else {
                echo "Registered: {$ud['id']}\n";
            }

            $dump_row = $this->db->loadAssoc("SELECT * FROM trans_log WHERE user_id = {$ud['id']} AND tag = 'reg-start'");
            if(!empty($dump_row)){
                echo "{$ud['id']} was queued\n";
            }

            $dump_row = $this->db->loadAssoc("SELECT * FROM trans_log WHERE user_id = {$ud['id']} AND tag = 'reg-queue'");
            if(!empty($dump_row)){
                echo "{$ud['id']} was registered in queue, res: {$dump_row['dump_txt']} \n";
            }
            //*/
        }
        print_r(['user count' => count($entries), 'mp count' => count($tournaments)]);
    }

    public function setupTplsForStressTest($tpl_ids){
        $this->db->truncate('trans_log');
        $tpls = $this->db->loadArray("SELECT * FROM tournament_tpls WHERE id IN({$this->db->makeIn($tpl_ids)})");
        $ts = [];
        foreach($tpls as $tpl){
            $this->t->purgeTemplateQueue($tpl);
            $tpl['queue'] = 'bos0';
            $tpl['included_countries'] = '';
            $tpl['reg_lim_excluded_countries'] = '';
            $tpl['excluded_countries'] = '';
            $tpl['min_players'] = 3;
            $tpl['max_players'] = 3;
            $tpl['recur'] = 4;
            $tpl['recur_end_date'] = '2100-12-12 22:00:00';
            $this->db->save('tournament_tpls', $tpl);
            $this->db->delete('tournaments', ['tpl_id' => $tpl['id']]);
            $tid = $this->t->insertTournament($tpl);
            $ts[] = $this->t->byId($tid);
        }
        return $ts;
    }
    
    public function sngQueueStressTest($tpl_ids, $num_players){
        $ts = $this->setupTplsForStressTest($tpl_ids);
        $players = $this->db->loadArray("SELECT * FROM users WHERE country = 'FI' LIMIT $num_players");
        shuffle($players);
        
        foreach($players as $p){
            foreach($tpls as $tpl){
                phMdelShard('tplqueue'.$tpl['id'], $p['id']);
            }
            $u_obj = cu($p['id']);
            $u_obj->verify();
            $u_obj->setAttr('cash_balance', 1000000);
            $u_obj->setAttr('logged_in', 1);
            $u_obj->setAttr('active', 1);
            $u_obj->deleteSetting("super-blocked");
            $u_obj->setSetting('freeroll-tester', 1);
            $this->clearTable($u_obj, ['tournament_entries', 'rg_limits']);
        }

        foreach($players as $p){
            $key = array_rand($ts);
            phive()->pexec('Test:Tournament', 'regOrQueue', [$ts[$key]['id'], $p['id'], []], 1);
        }
        
    }


    function testCanRegister($tpl_ids, $num_players){
        $ts = $this->setupTplsForStressTest($tpl_ids);
        $players = $this->db->loadArray("SELECT * FROM users WHERE country = 'GB' LIMIT $num_players");
        shuffle($players);
        foreach($players as $player){
            $u = cu($player);
            foreach($ts as $tournament){
                echo "Uid: {$u->getId()}, Tid: {$t['id']} Result: {$this->t->canRegister($tournament, $u)} \n";
            }
        }
        
    }
    
    function testSngTicket($u_obj, $tids){
        $this->clearTable($u_obj, ['trophy_award_ownership', 'bonus_entries']);
        foreach($tids as $tid){
            $t = $this->t->byId($tid);
            if($t['start_format'] != 'sng'){
                continue;
            }            
            $award = $this->db->loadAssoc('', 'trophy_awards', ['bonus_id' => $t['tpl_id'], 'type' => 'mp-ticket']);
            if(empty($award)){
                $award = [
                    'type'        => 'mp-ticket',
                    'amount'      => $this->t->getBuyin($t, true),
                    'bonus_id'    => $t['tpl_id'],
                    'description' => 'SnG ticket test',
                    'alias'       => 'sng_test_award',
                    'action'      => 'instant'
                ];
                $aid = $this->db->insertArray('trophy_awards', $award);
                $award = $this->db->loadAssoc('', 'trophy_awards', ['id' => $aid]);
            }

            print_r($award);
            
            phive('Trophy')->giveAward($award, ud($u_obj));

            // $this->t->register($u_obj, $tid, ['use_ticket' => true]);
            $ticket = $this->t->getTicket($t, $u_obj, $this->t->getBuyin($t, true));
            print_r($ticket);
            
        }
    }

    function giveFreerollTicket($u_obj){
        $award = $this->db->loadAssoc("SELECT * FROM trophy_awards WHERE type = 'mp-freeroll-ticket' AND own_valid_days = 7");
        phive('Trophy')->giveAward($award, ud($u_obj));
    }
    
    function testCdown($tid){
        $t = $this->t->byId($tid);
        $start_time = $this->t->getStartStamp($t);
        $end_time   = $start_time + ($t['duration_minutes'] * 60);
        $intv       = prettyTimeInterval($end_time - time());
        return ['hours' => $intv['hours'], 'mins' => $intv['mins'], 'secs' => $intv['seconds']];
    }
    
    function testJoker($tpl_id = 2, $tid = 4, $unames = []){
        $this->resetTablesLight();
        $this->populate('normal', 60);
        $this->startMtt($tpl_id, 'registration.open');
        foreach($unames as $uname){
            $u = cu($uname);
            $this->registerUser($u->getId(), $tid);
        }  
        $this->everyMinCron();
        $this->t->statusesCron(time() + 180);
        //$this->startMtt($tpl_id, 'late.registration', false);
      
    }
    
    function getFreerollTpl($gref, $name, $duration, $queue = ''){
        return array (
            'game_ref'                      => $gref,
            'tournament_name'               => $name,
            'category'                      => 'freeroll',
            'start_format'                  => 'mtt',
            'win_format'                    => 'thw',
            'play_format'                   => 'xspin',
            'cost'                          => '150',
            'pot_cost'                      => '50',
            'xspin_info'                    => '10',
            'min_players'                   => '2',
            'max_players'                   => '10',
            'mtt_show_hours_before'         => '0',
            'duration_minutes'              => $duration,
            'mtt_start_time'                => '00:00:00',
            'mtt_start_date'                => '0000-00-00',
            'mtt_reg_duration_minutes'      => '0',
            'mtt_late_reg_duration_minutes' => '0',
            'mtt_recur_type'                => '',
            'mtt_recur_days'                => '',
            'recur_end_date'                => '2037-12-31 10:00:00',
            'recur'                         => '1',
            'guaranteed_prize_amount'       => '0',
            'prize_type'                    => 'win-prog',
            'created_at'                    => '0000-00-00 00:00:00',
            'max_bet'                       => '500',
            'min_bet'                       => '10',
            'house_fee'                     => '50',
            'get_race'                      => '0',
            'get_loyalty'                   => '0',
            'get_trophy'                    => '1',
            'rebuy_times'                   => '0',
            'rebuy_cost'                    => '0',
            'award_ladder_tag'              => 'sng-sburst-2-people',
            'duration_rebuy_minutes'        => '0',
            'reg_wager_lim'                 => 0,
            'reg_dep_lim'                   => 0,
            'reg_lim_period'                => 0,
            'turnover_threshold'            => 0,
            'ladder_tag'                    => 'default',
            'included_countries'            => '',
            'excluded_countries'            => '',
            'prize_calc_wait_minutes'       => 5,
            'free_pot_cost'                 => 1,
            'total_cost'                    => 90000,
            'rebuy_house_fee'               => 0,
            'spin_m'                        => 1,
            'pwd'                           => '',
            'number_of_jokers'              => 0,
            'bounty_award_id'               => 0,
            'bet_levels'                    => '25,50,100',
            'queue'                         => $queue
        );
    }
    
    function setupMegaFreeroll($users_count = 1000){
        $this->resetTablesLight();
        $tpl                = $this->getFreerollTpl('netent_secretofthestones_not_mobile_sw', 'Secret of the Stones - Mega Freeroll', 3000);
        $tpl['max_players'] = empty($users_count) ? 1000 : $users_count;
        $tpl['cost']        = 25000;
        $tpl['xspin_info']  = 1000;
        $tpl['min_bet']     = 25;
        $tpl['max_bet']     = 25;
        $tpl['bet_levels']  = '';
        if(empty($users_count)){
            $tpl['reg_lim_period'] = 5;
            $tpl['reg_dep_lim'] = 2000;
            $tpl['reg_wager_lim'] = 20000;
        }
        $this->db->insertArray('tournament_tpls', $tpl);
        $this->startMtt(1, 'registration.open');
        if(!empty($users_count)){
            $this->registerNusers(1, $users_count);
            $this->everyMinCron();
            $this->t->statusesCron(time() + 180);
        }
    }
    
    /*
       Implies that something like the follwoing has been run before this is called in order to generate the initial data:
       $u1 = cu('devtestse');
       $u2 = cu('devtestfi');
       $u3 = cu('devtestnl');
       $tpl_id = 3;
       $tid = 1;
       $bos->resetTables();
       $bos->populate('normal', 60);
       $bos->everyMinCron();
       $bos->registerUser($u1->getId(), $tid); 
       $bos->registerUser($u2->getId(), $tid); 
       $bos->registerUser($u3->getId(), $tid); 
       
     */
    function testAmlChipDump($uid_from, $uid_to1, $uid_to2){
        $this->db->truncate('triggers_log');
        $uid_from = uid($uid_from); $uid_to1 = uid($uid_to1); $uid_to2 = uid($uid_to2);
        $ufrom    = cu($uid_from);
        $u1       = cu($uid_to1);
        $u2       = cu($uid_to2);
        $from_entry    = $this->db->sh($uid_from, '', 'tournament_entries')->loadAssoc("SELECT * FROM tournament_entries WHERE user_id = $uid_from");
        $t        = $this->t->byId($from_entry['t_id']);
        // We set spins left to 0 to simulate that the beneficiaries finished the tournament
        $u1_entry = $this->t->entryByTidUid($t, $uid_to1);
        $u2_entry = $this->t->entryByTidUid($t, $uid_to2);

        echo "This should be empty, not enough tournaments or money:\n";
        phive('Cashier/Aml')->everydayCron();
        $log = $this->db->shs('merge', '', null, 'triggers_log')->loadArray("SELECT * FROM triggers_log");
        print_r($log);

        // We duplicate to simulate two different tournaments
        $new_tid            = 100;
        $t['id']            = $new_tid;
        $from_entry['t_id'] = $new_tid;
        $u1_entry['t_id']   = $new_tid;
        $u2_entry['t_id']   = $new_tid;
        
        unset($from_entry['id']);
        unset($u1_entry['id']);
        unset($u2_entry['id']);
        $this->db->save('tournaments', $t);
        $this->db->sh($uid_from, '', 'tournament_entries')->insertArray('tournament_entries', $from_entry);
        $u1_entry_new_id = $this->db->sh($uid_to1, '', 'tournament_entries')->insertArray('tournament_entries', $u1_entry);
        $u2_entry_new_id = $this->db->sh($uid_to2, '', 'tournament_entries')->insertArray('tournament_entries', $u2_entry);

        echo "This should be empty because not enough money has been dumped:\n";
        phive('Cashier/Aml')->everydayCron();
        $log = $this->db->shs('merge', '', null, 'triggers_log')->loadArray("SELECT * FROM triggers_log");
        print_r($log);

        $u1_entry['spins_left']   = 0;
        $u2_entry['spins_left']   = 0;
        $this->db->sh($uid_to1, '', 'tournament_entries')->save('tournament_entries', $u1_entry);
        $this->db->sh($uid_to2, '', 'tournament_entries')->save('tournament_entries', $u2_entry);
        
        echo "This should be empty because not enough money has been dumped again:\n";
        phive('Cashier/Aml')->everydayCron();
        $log = $this->db->shs('merge', '', null, 'triggers_log')->loadArray("SELECT * FROM triggers_log");
        print_r($log);

        // Now we simulate enough money being dumped by upping the min bet level on the tournament 
        $t['min_bet'] = 10000;
        $this->db->save('tournaments', $t);
        echo "This should not be empty:\n";
        phive('Cashier/Aml')->everydayCron();
        $log = $this->db->shs('merge', '', null, 'triggers_log')->loadArray("SELECT * FROM triggers_log");
        print_r($log);
    }
    
    function getLeaderBoard($tid){
        $t = $this->t->byId($tid);
        return $this->t->getLeaderBoard($t, false, false);
    }
    
    function resetTransactions(){
        foreach(array('cash_transactions', 'wins_mp', 'bets_mp') as $tbl)
            phive("SQL")->shs()->query("TRUNCATE TABLE `$tbl`");        
    }

    function resetTablesLight(){        
        foreach(array('tournament_tpls', 'tournaments', 'tournament_entries', 'tournament_award_ladder', 'rg_limits') as $tbl)
            phive("SQL")->shs()->query("TRUNCATE TABLE `$tbl`");        
        phive("SQL")->shs()->query("UPDATE users SET alias = username, cash_balance = 1000000");
    }  
    
    function resetTables(){
        phive('SQL')->truncate('cash_transactions', 'tournament_tpls', 'tournaments', 'tournament_entries', 'wins_mp', 'bets_mp', 'tournament_award_ladder');
        phive("SQL")->shs('', '', null, 'users')->query("UPDATE users SET cash_balance = 100000000");
    }

    function populateAwardLadders(){
        phive('SQL')->query("INSERT INTO `tournament_award_ladder` (`start_spot`, `end_spot`, `award_id`, `tag`) VALUES
                                 (1, 1, 1132, 'sng-sburst-2-people'),
                                 (2, 2, 1132, 'sng-sburst-2-people')");
    }
    
  function populate($cat = 'normal', $duration = 30, $queue = ''){

      $this->populateAwardLadders();
    
    $tpls = array();
    
    //#1
    $tpls[] = array (
      'game_ref' => 'netent_megafortune_not_mobile_sw',
      'tournament_name' => 'Mega Fortune - Jackpot Chase - MTT',
      'category' => 'jackpot',
      'start_format' => 'mtt',
      'win_format' => 'tht',
      'play_format' => 'xspin',
      'cost' => '1000',
      'pot_cost' => '0',
      'xspin_info' => '40',
      'min_players' => '2',
      'max_players' => '100',
      'mtt_show_hours_before' => '1',
      'duration_minutes' => $duration,
      'mtt_start_time' => phive()->hisMod('+2 minute', '', 'H:i:s'),
      'mtt_start_date' => date('Y-m-d'),
      'mtt_reg_duration_minutes' => '30',
      'mtt_late_reg_duration_minutes' => '10',
      'mtt_recur_type' => 'day',
      'mtt_recur_days' => '',
      'recur_end_date' => '2037-12-30 10:00:00',
      'recur' => '0',
      'guaranteed_prize_amount' => '',
      'prize_type' => 'win-prog',
      'created_at' => '0000-00-00 00:00:00',
      'max_bet' => '100',
      'min_bet' => '25',
      'house_fee' => '100',
      'get_race' => '0',
      'get_loyalty' => '0',
      'get_trophy' => '1',
      'rebuy_times' => '0',
      'rebuy_cost' => '0',
      'award_ladder_tag' => '',
      'duration_rebuy_minutes' => '',
      'reg_wager_lim' => 0,
      'reg_dep_lim' => 0,
      'reg_lim_period' => 0,
      'turnover_threshold' => 0,
      'ladder_tag' => 'default',
      'included_countries' => '',
      'excluded_countries' => '',
      'prize_calc_wait_minutes' => 10,
      'free_pot_cost' => 0,
      'total_cost' => 1000,
      'rebuy_house_fee' => 0,
      'spin_m' => 1,
      'pwd' => '',
      'number_of_jokers'    => 0,
      'bounty_award_id'     => 0,
      'bet_levels'          => '25,50,100'      
    );

    //#2
      $tpls[] = array (
          'game_ref' => 'netent_secretofthestones_not_mobile_sw',
          'tournament_name' => '10 EUR Guaranteed',
          'category' => $cat,
          'start_format' => 'mtt',
          'win_format' => 'tht',
          'play_format' => 'xspin',
          'cost' => '100',
          'pot_cost' => '0',
          'xspin_info' => '10',
          'min_players' => '2',
          'max_players' => '100',
          'mtt_show_hours_before' => '1',
          'duration_minutes' => $duration,
          'mtt_start_time' => '06,08,12,14,16,18,20,22',
          'mtt_start_date' => '0000-00-00',
          'mtt_reg_duration_minutes' => '30',
          'mtt_late_reg_duration_minutes' => '30',
          'mtt_recur_type' => 'day',
          'mtt_recur_days' => '',
          'recur_end_date' => '2037-12-30 10:00:00',
          'recur' => '1',
          'guaranteed_prize_amount' => '0',
          'prize_type' => 'win-fixed',
          'created_at' => '0000-00-00 00:00:00',
          'max_bet' => '25',
          'min_bet' => '25',
          'house_fee' => '0',
          'get_race' => '1',
          'get_loyalty' => '1',
          'get_trophy' => '1',
          'rebuy_times' => '2',
          'rebuy_cost' => '100',
          'award_ladder_tag' => '',
          'duration_rebuy_minutes' => '45',
          'reg_wager_lim' => 0,
          'reg_dep_lim' => 0,
          'reg_lim_period' => 0,
          'turnover_threshold' => 0,
          'ladder_tag' => 'default',
          'included_countries' => '',
          'excluded_countries' => '',
          'prize_calc_wait_minutes' => 5,
          'free_pot_cost' => 0,
          'total_cost' => 200,
          'rebuy_house_fee' => 0,
          'spin_m' => 1,
          'pwd' => '',
          'number_of_jokers'    => 1,
          'bounty_award_id'     => 0,
          'bet_levels'          => ''
      );

      $tmp = array(
          //'thunderkick_tk-s1-g12' => 'Full Moon Romance - THT',
          // 'nyx240006' => 'Esqueleto - THT',
          //'nyx240009' => 'Fruit Warp - THT',
          // 'nyx240011' => 'Flux - THT',
          // 'nyx240003' => 'Børk The Berzerker - THT',
          // 'nyx240010' => 'Sunny Scoops - THT',
          // 'nyx240005' => '1429 Uncharted Seas - THT',
          // 'nyx240001' => 'Magicious - THT',
          // 'nyx240002' => 'Barber Shop - THT',
          // 'nyx240004' => 'Toki Time - THT',
          // 'nyx240012' => 'Arcader - THT',
          //'playngo304' => 'Samba Carnival', 
          //'playngo302' => 'Wizard of Gems',
          //'playngo282' => 'Pearls of India', 
          //'playngo292' => 'Spin Party',
          //'playngo245' => 'Myth', 
          //'playngo287' => 'Tower Quest - THT', //#3
          //'playngo262' => 'Energoonz - THT', //#3
          //'playngo286' => 'Gemix - THT', //#3
          //'playngo285' => 'Golden Ticket - THT', //#3
          //'playngo291' => 'Pimped - THT', //#3
          //'playngo298' => 'Royal Masquerade - THT', //#3
          //'playngo294' => 'Wild North - THT', //#3
          //'playngo257' => 'Rage to Riches - THT', //#3
          //'playngo300' => 'Cloud Quest - THT', //#3
          'netent_secretofthestones_not_mobile_sw' => 'Secret of the Stones - THT', //#3
          //'netent_invisibleman_not_mobile_sw' => 'Invisible Man - THT', //#3
          //'netent_jackandbeanstalk_sw' => 'Steam Tower - THT - 10 Spins', //#5
          //'netent_tornado_sw' => 'Farm Escape - THT - 10 Spins', //#6
          //'netent_southpark2_sw' => 'South Park 2 - THT - 10 Spins' //#7
      );
      
    foreach($tmp as $gref => $name){
      $tpls[] = array (
        'game_ref' => $gref,
        'tournament_name' => $name,
        'category' => $cat,
        'start_format' => 'sng',
        'win_format' => 'tht',
        'play_format' => 'xspin',
        'cost' => '500',
        'pot_cost' => '0',
        'xspin_info' => '20',
        'min_players' => '3',
        'max_players' => '3',
        'mtt_show_hours_before' => '0',
        'duration_minutes' => $duration,
        'mtt_start_time' => '00:00:00',
        'mtt_start_date' => '0000-00-00',
        'mtt_reg_duration_minutes' => '0',
        'mtt_late_reg_duration_minutes' => '0',
        'mtt_recur_type' => '',
        'mtt_recur_days' => '',
        'recur_end_date' => '2037-12-31 10:00:00',
        'recur' => '1',
        'guaranteed_prize_amount' => '0',
        'prize_type' => 'win-fixed',
        'created_at' => '0000-00-00 00:00:00',
        'max_bet' => '25',
        'min_bet' => '25',
        'house_fee' => '100',
        'get_race' => '1',
        'get_loyalty' => '0',
        'get_trophy' => '1',
        'rebuy_times' => '0',
        'rebuy_cost' => '0',
        'award_ladder_tag' => '',// 'sng-sburst-2-people',
        'duration_rebuy_minutes' => '0',
        'reg_wager_lim' => 0,
        'reg_dep_lim' => 0,
        'reg_lim_period' => 0,
        'turnover_threshold' => 0,
        'ladder_tag' => 'default',
        'included_countries' => '',
        'excluded_countries' => '',
        'prize_calc_wait_minutes' => 0,
        'free_pot_cost' => 0,
        'total_cost' => 90000,
        'rebuy_house_fee' => 0,
        'spin_m' => 1,
        'pwd' => '',
        'number_of_jokers'    => 1,
        'bounty_award_id'     => 0,
        'bet_levels'          => '',
        'queue'               => $queue,
        'desktop_or_mobile'   => 'both'
      );
    }

    $tmp = array(
      'netent_jackandbeanstalk_sw' => 'Jack & the Beanstalk - THW', //#3
      'netent_deadoralive_sw' => 'Dead or Alive - THW - 10 Spins',  //#9
      'netent_steamtower_sw' => 'Steam Tower - THW - 10 Spins', //#10
      'netent_tornado_sw' => 'Farm Escape - THW - 10 Spins', //#11
      'netent_blacklagoon_sw' => 'Creature from the Black Lagoon - THW - 10 Spins' //#12
    );
    
    foreach($tmp as $gref => $name){
        $tpls[] =  $this->getFreerollTpl($gref, $name, $duration, $queue);
    }

      foreach($tpls as $tpl){
          phive('SQL')->insertArray('tournament_tpls', $tpl);
      }
      
    // phive('SQL')->insertTable('tournament_tpls', $tpls);
    
  }

    function testRtpWagerLim($u_obj){
        $stime = phive()->hisMod('-1 day');
        $etime = phive()->hisMod('+1 minute', $stime);

        echo "Result for a 85% payout game:\n";
        $this->deleteByUser($u_obj, 'users_game_sessions');
        $insert = [
            'user_id'    => $u_obj->getId(),
            'start_time' => $stime,
            'end_time'    => $etime,
            'bet_amount' => 10000,
            'game_ref'   => 'nyx950043'
        ];
        $this->db->sh($u_obj)->insertArray('users_game_sessions', $insert);
        $res = $this->getLatest($u_obj, 'users_game_sessions');        
        $res = $this->depWagerLimSums($u_obj, phive()->hisNow(), '-2 day');
        print_r($res);

        echo "Result for a 98% payout game:\n";
        $this->deleteByUser($u_obj, 'users_game_sessions');
        $insert['game_ref'] = 'netent_bloodsuckers_not_mobile_sw';
        $this->db->sh($u_obj)->insertArray('users_game_sessions', $insert);
        $res = $this->depWagerLimSums($u_obj, phive()->hisNow(), '-2 day');
        print_r($res);
    }
    
    function depWagerLimSums($u, $etime, $mod){
        $stime = phive()->hisMod($mod, $etime);
        print_r([$stime, $etime]);
        return $this->t->getDepWagerLimSums($u, $stime, $etime);        
    }
    
    // Will late reg an arbitrary amount of users in the selected MTT
    // Good for setting up testing the front end when you need a tournament
    // with a lot of participants
    function testMtt($tpl_id = 2, $tid = 4, $unames = []){
        phM('delAll', 'mpleaderboard*');
        $this->resetTablesLight();
        $this->populate('normal', 60);
        $this->startMtt($tpl_id, 'registration.open');
        $this->everyMinCron();
        $this->t->statusesCron(time() + 180);
        $this->startMtt($tpl_id, 'late.registration', false);
        foreach($unames as $uname){
            $u = cu($uname);
            $this->registerUser($u->getId(), $tid);
        }
    }
    
    function startMtt($tpl_id, $status = 'late.registration', $activate = true){
        if($activate)
            $this->activateMtt($tpl_id);
        $ts              = $this->t->getAllWhere(['tpl_id' => $tpl_id]);
        //$t               = array_pop($ts);
	foreach($ts as $t) {
	    $t['mtt_start'] = phive()->hisNow();
	    $t['start_time'] = phive()->hisNow();
	    $t['status'] = $status;
	    $this->t->save($t);
	}
    }
    
  
  function testCanReg($tid, $uname, $tstatus, $estatus, $recur){
    $u = cu($uname);
    $t = $this->t->byId($tid);
    phive('SQL')->query("UPDATE tournament_tpls SET recur = '$recur'");
    phive('SQL')->query("UPDATE tournament_entries SET status = '$estatus' WHERE user_id = {$u->getId()}");
    phive('SQL')->query("UPDATE tournaments SET status = '$tstatus'");
    echo $this->t->canRegister($t, $u) ? 'can register' : 'can not register';
  }
  
  function duplicate($tid){
    $mp = $this->t->byId($tid);
    unset($mp['id']);
    phive('SQL')->save('tournaments', $mp);
  }
  
  function simulatePlayAndFinish($tid, $cash_balance = 0){
    $t  = $this->t->byId($tid);
    $es = $this->t->entries($t);
    foreach($es as $e){
      $e['win_amount']   = rand(0, 100);
      $e['cash_balance'] = $cash_balance;
      $e['spins_left']   = 0;
      $e['turnover']     = rand(10, 1000);
      phive('SQL')->save('tournament_entries', $e);
    }
    $this->t->endTournament($t);
  }
  
  function startSng(){
    foreach($this->t->_getTplsWhere(array('start_format' => 'sng')) as $tpl){
      $this->t->insertTournament($tpl);
    }
  }

    // We assume we've already run $this->populate() before we call this one.
    function testInitSng(){
        $now = phive()->hisNow();

        $this->db->query("UPDATE tournament_tpls SET mtt_start_date = '0000-00-00', mtt_start_time = '00:00:00'");
        $this->t->initSng();
        echo "This should show something:\n";
        print_r($this->t->getAllWhere('1 = 1'));

        list($date, $time) = explode(' ', phive()->hisMod('-10 min'));

        $this->db->truncate('tournaments');
        $this->db->query("UPDATE tournament_tpls SET recur_end_date = '$date $time'");
        $this->t->initSng();
        echo "This should not show anything:\n";
        print_r($this->t->getAllWhere('1 = 1'));

        $this->db->query("UPDATE tournament_tpls SET recur_end_date = '0000-00-00 00:00:00'");
        
        $this->db->query("UPDATE tournament_tpls SET mtt_start_date = '$date', mtt_start_time = '$time'");
        $this->db->truncate('tournaments');
        $this->t->initSng();
        echo "This should show something:\n";
        print_r($this->t->getAllWhere('1 = 1'));
        $this->t->initSng();
        echo "This should not show double:\n";
        print_r($this->t->getAllWhere('1 = 1'));
        
        list($date, $time) = explode(' ', phive()->hisMod('+10 min'));
        $this->db->query("UPDATE tournament_tpls SET mtt_start_date = '$date', mtt_start_time = '$time'");
        $this->db->truncate('tournaments');
        $this->t->initSng();
        echo "This should not show something:\n";
        print_r($this->t->getAllWhere('1 = 1'));
        
    }
    
  function everyMinCron(){
    $this->t->initSng();
    $this->t->mttScheduleCron();
    $this->t->statusesCron();
    $this->t->calcPrizesCron();
  }

  function doMinutes($stime, $etime, $tpl_id){
    $stime = strtotime($stime);
    $etime = strtotime($etime);
    $cur_time = $stime;
    while($cur_time <= $etime){
      $this->testMinute($cur_time);
      echo "Time: ".phive()->hisNow($cur_time)."\n";
      $this->printStatuses($tpl_id);
      echo "\n";
      $cur_time += 60;
    }
  }
  
  //cur is a numerical timestamp
  function testMinute($cur){
    $t = phive('Tournament');
    $his = phive()->hisNow($cur);
    //echo "Running schedule cron on $his \n";
    $t->mttScheduleCron($his);
    //$this->testRegistration();
    $t->statusesCron($cur);
    //exit;
    //phive("SQL")->debug = true;
    //phive("SQL")->printDebug();
    //exit;
  }

  function printStatuses($tpl_id){
    $where = empty($tpl_id) ? '1' : array('tpl_id' => $tpl_id); 
    foreach($this->t->getAllWhere($where) as $t){
      echo "{$t['id']}: {$t['status']}\n";
    }
  }

  function playGames(){
    
  }

  function registerNusers($tid, $n = 100){
      foreach(phive("SQL")->loadCol("SELECT id FROM users ORDER BY RAND() LIMIT $n", 'id') as $uid){
          // We set this setting to be able to bypass the wagering / deposit requirement for freerolls
          $u = cu($uid);
          $u->setSetting('freeroll-tester', 1);
          $u->setAttr('active', 1);
          $u->deleteSetting('play_block');
          $u->deleteSetting('restrict');
          $this->registerUser($uid, $tid);
      }
  }
  
  function registerUser($uid, $tid){
      $th = phive('Tournament');
      if(is_numeric($uid))
        $u = cu($uid);
      else
        $u = $uid;
      //$u->incAttr('cash_balance', rand(0, 200000));
      return $th->register($u, $tid, 'cash');
      //$res = $th->regRebuyCommon('register', $tid, '', $uid);
  }
  
  function testRegistration(){
    $th = phive('Tournament');
    foreach($th->getAllWhere("1") as $t){
      if($t['status'] == 'in.progress'){
        exit;
      }
      //exit;
      //echo $t['status']."\n";
      if($t['registered_players'] < $this->num_players && in_array($t['status'], array('registration.open', 'late.registration'))){
	foreach(phive("SQL")->loadArray("SELECT * FROM users ORDER BY RAND() LIMIT 0,{$this->num_players}") as $u){
          $this->registerUser(5129332, 1);      
	  echo "Trying to register {$u['username']}\n";
	  $u 		= cu($u['id']);
	  $u->incAttr('cash_balance', rand(0, 2000));
	  //$u->setSetting('skill_points', rand(0, 2000));
	  //$tmp 	= array('cash', 'points');
	  //$pw 	= $tmp[rand(0, 1)];
	  $th->register($u, $t, 'cash');
	  //phive("SQL")->query("UPDATE tournament_entries SET cash_balance = ROUND(RAND() * 100)");
	}
      }
    }
  }

  function genTournamentsFromTpls($stime, $etime){
    $th = phive('Tournament');
    foreach($th->getActiveMttTpls() as $tpl){
      foreach($th->getMttSchedule($tpl, $stime, $etime) as $start){
        print_r($start);
        if(phive()->isEmpty($th->_getByTpl($tpl['id'], $start))){
          print_r(array($tpl, $start));      
          $th->insertTournament($tpl, $start);
        }
      }
    }
  }

  function activateMtt($tpl_id){
    $tpl = $this->t->getTplById($tpl_id);
    $this->t->insertTournament($tpl, phive()->hisNow());
  }

  function chgTournament($t_id, $chg = array()){
    phive('SQL')->updateArray('tournaments', $chg, array('id' => $t_id));
  }

  function chgEntry($tid, $uid, $chg){
    $entry = $this->t->entryByTidUid($tid, $uid);
    foreach($chg as $key => $val)
      $entry[$key] = $val;
    phive('SQL')->save('tournament_entries', $entry);
  }
  
  function testRegTime($status = 'upcoming', $mod = '-60'){
    foreach($this->t->getAllWhere(array('start_format' => 'mtt')) as $t){
      $t['status']    = $status;
      $t['mtt_start'] = phive()->hisMod("$mod minute");
      if((int)$mod <= 0)
        $t['start_time'] = $t['mtt_start'];
      phive('SQL')->save('tournaments', $t);
    }
  }
  
  function testWinPriority($updates, $tid){
    foreach($updates as $uid => $upd){
      $e = array_merge($this->t->entryByTidUid($tid, $uid), $upd);
      phive('SQL')->save('tournament_entries', $e);
    }
    $this->t->endTournament($this->t->byId($tid));
    $t = $this->t->byId($tid);
    $this->t->calcPrizes($t);
  }

  function testLimits($tid, $uid, $dep_sum){
    $u = cu($uid);
    phive('QuickFire')->depositCash($u, $dep_sum, 3);
    $t = $this->t->byId($tid);
    return $this->t->depWagerLimCheck($t, $u);
  }

  function changeDates($uid, $date){
    phive('SQL')->query("UPDATE cash_transactions SET `timestamp` = '$date 03:00:00' WHERE user_id = $uid");
    phive('SQL')->query("UPDATE bets_mp SET `created_at` = '$date 03:00:00'");
    phive('SQL')->query("UPDATE bets SET `created_at` = '$date 03:00:00'");
    phive('SQL')->query("UPDATE wins_mp SET `created_at` = '$date 03:00:00'");
    phive('SQL')->query("UPDATE wins SET `created_at` = '$date 03:00:00'");
  }

  function controlStats(){
    
  }

    function testPrizeCalc($tid){
        $t                      = $this->t->byId($tid);
        $t['calc_prize_stamp']  = phive()->hisNow();
        $t['prizes_calculated'] = 0;
        $t['status']  = 'in.progress';
        phive('SQL')->save('tournaments', $t);
        $t                      = $this->t->byId($tid);
        $this->t->endTournament($t, phive()->hisNow());
        $t                      = $this->t->byId($tid);
        foreach($this->t->entries($t) as $e){
            $e['won_amount'] = 0;
            $e['status']     = 'finished';
            phive('SQL')->save('tournament_entries', $e);
        }
        $this->t->calcPrizes($t);
    }

  function testStats($affuname){
    $sql = phive('SQL');
    $this->resetTables();
    $currency = $this->t->getSetting('currency');
    $tables = array(
      'bets',
      'bets_mp',
      'wins',
      'wins_mp',
      'users_daily_stats',
      'users_daily_game_stats',
      'users_daily_stats_mp',
      'users_daily_stats_total',
      'network_stats',
      'affiliate_daily_stats',
      'affiliate_daily_bcodestats');
    foreach($tables as $tbl)
      $sql->query("TRUNCATE TABLE `$tbl`");
    $this->populate('normal', 60);
    $this->everyMinCron();
    $user1 = phive('UserHandler')->getUserByUsername('devtest1');
    $user2 = phive('UserHandler')->getUserByUsername('devtest2');
    $user3 = phive('UserHandler')->getUserByUsername('devtest3');
    $affe  = phive('UserHandler')->getUserByUsername($affuname);
    $ud1   = $user1->data;
    $ud1['affe_id'] = $affe->getId();
    $sql->save('users', $ud1);
    $this->registerUser($user1, 1); 
    $this->registerUser($user2, 1); 
    $this->registerUser($user3, 2);
    $e = $this->t->entryByTidUid(2, $user3->getId());
    $this->t->cancelEntry($e, $user3);
    $bb = array('t_id' => 1, 'amount' => 100, 'game_ref' => 'netent_starburst_sw', 'op_fee' => 10, 'loyalty' => 1);
    $bw = array('t_id' => 1, 'amount' => 50, 'game_ref' => 'netent_starburst_sw', 'op_fee' => 5);
    $sql->query("UPDATE tournament_entries SET cash_balance = 500");
    foreach(array($user1, $user1, $user2, $user2) as $u){
      $e = $this->t->entryByTidUid(1, $u->getId());
      $user_currency = $u->getCurrency();
      
      $insert = array_merge(
        $bb,
        array('trans_id' => rand(1, 1000),
              'user_id' => $u->getId(),
              'mg_id' => 'netent'.rand(1, 1000000),
              'currency' => $currency),
        ['e_id' => $e['id']]
      );
      
      $sql->save('bets_mp', $insert);
      unset($insert['e_id']);
      unset($insert['t_id']);
      $sql->save('bets', $insert);
      
      $insert = array_merge(
        $bw,
        array('trans_id' => rand(1, 1000),
              'user_id' => $u->getId(),
              'mg_id' => 'netent'.rand(1, 1000000),
              'currency' => $user_currency),
        ['e_id' => $e['id']]
      );
      
      $sql->save('wins_mp', $insert);
      unset($insert['t_id']);
      unset($insert['e_id']);
      $sql->save('wins', $insert);
    }
    $t = $this->t->byId(1);
    $this->t->endTournament($t, phive()->hisNow());
    phive('Cashier')->recalcDay(phive()->today());
    //phive('MicroGames')->recalcGameUserStats(phive()->today(), false);
  }
  
  
}
