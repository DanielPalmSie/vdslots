<?php
require_once 'TestStandalone.php';

class TestNetent extends TestStandalone
{

  function __construct(){
      $this->netent = phive('Netent');
      $this->url = "http://www.videoslots.loc/diamondbet/soap/netent.php";
      $this->caller_id = 'testmerchant';
      $this->caller_pwd = 'testing';
  }

    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }


    function replayPlay($type = 'bets'){

        phive('SQL')->loopShardsSynced(function($db, $shard, $id) use($type){

            echo "$id\n";
            
            // So that they can afford it if we only want to test bets.
            $db->query("UPDATE users SET cash_balance = 100000000");
            
            $rows = $db->loadArray("SELECT * FROM {$type}_tmp WHERE game_ref LIKE 'netent_%'");

            foreach($rows as &$r){
                $gid   = $this->netent->fixGid($r['game_ref'], false);
                $mg_id = str_replace('netent', '', $r['mg_id']);
                $uid = "vs_{$r['user_id']}-WW";

                
                if($type == 'bets'){
                    $this->withdraw($uid, $gid, $mg_id, $r['trans_id'], phive()->twoDec($r['amount']), [], false);
                } else {
                    // Not needed yet.
                }

                // print_r([$r, $gid, $mg_id, phive()->twoDec($r['amount']), $uid]);
                // exit;
            }
            
        });

    }
    
    function prepare($user, $game){
        $sid = uniqid();
        $this->setGame($sid, $game['ext_game_name']);
    }

  function setGame($sid, $gref){
    $this->sid = $sid;
    $this->netent->setSess($sid, array('gref' => "netent_$gref"));
  }

  function getXml($func, $data){
    $xml_body = '';
    foreach($data as $field => $value)
      $xml_body .= "<$field>$value</$field>\n";
    return '<?xml version="1.0" encoding="UTF-8"?>
             <S:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:S="http://types.walletserver.casinomodule.com/3_0/">
             <S:Body>
                <ns2:'.$func.'>
                   <callerId>'.$this->caller_id.'</callerId>
                   <callerPassword>'.$this->caller_pwd.'</callerPassword>
                   <sessionId>'.$this->sid.'</sessionId>
                   '.$xml_body.'
                </ns2:'.$func.'>
             </S:Body>
          </S:Envelope>';
  }

  function post($func, $data, $echo = true, $xml = ''){
    $xml = empty($xml) ? $this->getXml($func, $data) : $xml;
    $options = array(
        'http' => array(
          'method'  	=> 'POST',
          'timeout'     => 2,
          'header'  	=> 'Content-type: soap/xml',
          'content' 	=> $xml));
    if($echo)
      echo "Sending: $xml\n\n To: {$this->url}\n\n";
    $r = file_get_contents($this->url, false, stream_context_create($options));
    return $r;
  }

  function rawPost($xml){
    $options = array(
      'http' => array(
        'method'  	=> 'POST',
        'header'  	=> 'Content-type: soap/xml',
        'content' 	=> $xml));
    echo "Sending: $xml\n\n To: {$this->url}\n\n";
    $r = file_get_contents($this->url, false, stream_context_create($options));
    return $r;
  }

  function getBalance($uid, $gid){
    $data = array('playerName' => $uid, 'gameId' => $gid);
    return $this->post('getBalance', $data);
  }

  function getPlayerCurrency($uid){
    $data = array('playerName' => $uid);
    return $this->post('getPlayerCurrency', $data);
  }

  function rollbackTransaction($uid, $gid, $mg_id){
    $data = array('playerName' => $uid, 'gameId' => $gid, 'transactionRef' => $mg_id);
    return $this->post('rollbackTransaction', $data);
  }

  function withdraw($uid, $gid, $mg_id, $r_id, $amount, $jp_contribs = array(), $echo = true){
    $data = array('playerName' => $uid, 'gameId' => $gid, 'transactionRef' => $mg_id, 'gameRoundRef' => $r_id, 'amount' => $amount);
    $jp_xml = '';
    foreach($jp_contribs as $jpc){
      $jp_xml .= "<jackpot>
               <jackpotId>0</jackpotId>
               <contribution>$jpc</contribution>
            </jackpot>";
    }

    if(!empty($jp_xml))
      $data['jackpotContributions'] = $jp_xml;

    return $this->post('withdraw', $data, $echo);
  }

  function deposit($uid, $gid, $mg_id, $r_id, $amount, $jp_amount = 0, $bonuses = array()){
    $data = array('playerName' => $uid, 'gameId' => $gid, 'transactionRef' => $mg_id, 'gameRoundRef' => $r_id, 'amount' => $amount, 'jackpotAmount' => $jp_amount);

      if(!empty($bonuses)){
          unset($data['gameId']);
          unset($data['jackpotAmount']);
          unset($data['gameRoundRef']);
          $data['reason'] = 'WAGERED_BONUS';
      }

    foreach($bonuses as $b){
      $bonus_xml .= "<bonus>
               <bonusProgramId>45</bonusProgramId>
               <externalReferenceId>$b</externalReferenceId>
               <depositionId>0</depositionId>
            </bonus> ";
    }

    if(!empty($bonus_xml))
      $data['bonusPrograms'] = $bonus_xml;

    return $this->post('deposit', $data);
  }

  function withdrawAndDeposit($uid, $gid, $mg_id, $r_id, $wamount, $damount, $echo = true){
    $data = array('playerName' => $uid, 'gameId' => $gid, 'transactionRef' => $mg_id, 'gameRoundRef' => $r_id, 'withdraw' => $wamount, 'deposit' => $damount);
    return $this->post('withdrawAndDeposit', $data, $echo);
  }

    function testIdempotency($user, $game, $bamount, $wamount){
        $mg_id = $this->randId();
        $r_id  = $this->randId();
        $uid = "vs_{$user->getId()}-{$user->getCountry()}";
        echo $this->withdrawAndDeposit($uid, $game['ext_game_id'], $mg_id, $r_id, $bamount / 100, $wamount / 100);
        echo $this->withdrawAndDeposit($uid, $game['ext_game_id'], $mg_id, $r_id, $bamount / 100, $wamount / 100);
    }

    function testMpSpin($u, $eid, $type, $gid, $bamount, $wamount, $close_ground = true){
        $uid        = $u->getId();
        $netent_uid = "vs_{$uid}e{$eid}-WW";
        switch($type){
            case 'normal':
                toWs('gameRoundStarted', 'mpextendtest', $uid);
                toWs('spinStarted', 'mpextendtest', $uid);
                usleep(100000);
                echo $this->withdrawAndDeposit($uid, $gid, $this->randId(), $this->randId(), $bamount, $wamount);
                usleep(100000);
                toWs('spinEnded', 'mpextendtest', $uid);
                toWs('gameRoundEnded', 'mpextendtest', $uid);
                break;
            case 'spin-open':
                toWs('gameRoundStarted', 'mpextendtest', $uid);
                toWs('spinStarted', 'mpextendtest', $uid);
                toWs('spinEnded', 'mpextendtest', $uid);
                break;
            case 'bonus-start':
                toWs('bonusGameStarted', 'mpextendtest', $uid);
                break;
            case 'bonus-end':
                toWs('bonusGameEnded', 'mpextendtest', $uid);
                if($close_ground)
                    toWs('gameRoundEnded', 'mpextendtest', $uid);
                break;
            case 'frb-start':
                toWs('freeSpinStarted', 'mpextendtest', $uid);
                break;
            case 'bonus-spin':
                toWs('spinStarted', 'mpextendtest', $uid);
                toWs('spinEnded', 'mpextendtest', $uid);
                break;
            case 'frb-end':
                echo $this->deposit($netent_uid, $gid, $this->randId(), $this->randId(), $wamount);
                usleep(100000);
                toWs('gameRoundEnded', 'mpextendtest', $uid);
                toWs('freeSpinEnded', 'mpextendtest', $uid);
                break;
        }
    }

    function simpleStresstest($uid, $range = 200){
        // Requires a login on the site this test is being run on to trigger. 
        foreach(range(0, $range) as $i){
            $str = "SELECT * FROM users_sessions WHERE user_id = $uid ORDER BY id DESC LIMIT 0,1";
            $row = phive('SQL')->loadAssoc($str);
            sleep(1);
        }
    }
    
    function stressTest($num_players = 50, $num_secs = 120, $domain = 'www.videoslots.loc', $ssl = 'yes', $bos_id = ''){
        $ssl       = $ssl == 'yes' ? 's' : '';
        $this->db  = phive('SQL');
        $gids      = $this->db->load1DArr("SELECT ext_game_name FROM micro_games WHERE network = 'netent' AND ext_game_name NOT LIKE '%NOTAVAILABLE%' AND tag = 'videoslots'", 'ext_game_name');
        $gids      = array_map(function($el){ return str_replace('netent_', '', $el); }, $gids);
        $this->url = "http{$ssl}://$domain/diamondbet/soap/netent.php";
        
        // update users set cash_balance = 1000000, active = 1;

        $this->caller_id = 'testmerchant';
        $this->caller_pwd = 'testing';
        if(!empty($bos_id)){
            $entries    = array_slice($this->db->shs()->loadArray("SELECT * FROM tournament_entries WHERE t_id = $bos_id ORDER BY RAND() LIMIT $num_players", 'ASSOC', 'user_id'), 0, $num_players, true);
            $in_uids    = $this->db->makeIn(array_keys($entries));
            $players    = $this->db->shs()->loadArray("SELECT * FROM users WHERE id IN($in_uids)");
            $tournament = phive('Tournament')->byId($bos_id);
            $gid        = str_replace('netent_', '', $tournament['game_ref']);
        }else{
            $players = [];
            for($i = 0; $i < $num_players; $i++){
                $id = rand(5500000, 5536702);
                //$players[] = $this->db->loadAssoc("SELECT * FROM users WHERE id = $id");
                $players[] = $id;
            }
        }
        $uids_gids = [];
        foreach($players as $uid){
            //Needs to exist on the machine(s) we want to stress test in the form of login.php:
            /*
               <?php
               require_once __DIR__ . '/../../phive/phive.php';
               phive()->sessionStart();
               phive('UserHandler')->login($_GET['username'], 'apa', false);
               echo 'ok';
             */
            file_get_contents("http{$ssl}://$domain/diamondbet/test/login.php?uid={$uid}");
            shuffle($gids);
            $uid_gids[$uid] = $gids[0];
        }
        
        for($i = 1; $i <= $num_secs; $i++){
            $smicro = microtime(true);
            $duration = 0;
            echo "Starting $i play\n";
            foreach($players as $p_uid){
                
                //$gid_id = crc32($u['username']);
                //$gid = $gids[mt_rand(0, count($gids) - 1)];
                //$gid = 'secretofthestones_sw';

                if(!empty($entries)){
                    $win_amount = $bet_amount = $tournament['min_bet'] / 100;
                    $uid        = "vs_{$p_uid}e{$entries[$p_uid]['id']}-WW";
                }else{
                    $uid        = "vs_{$p_uid}-WW";
                    $gid        = $uid_gids[$p_uid];
                    $bet_amount = rand(100, 1000) / 100;
                    if(rand(0, 500) == 5)
                        $win_amount = rand(10000, 100000) / 100;
                    else if(rand(0, 5) == 3)
                        $win_amount = $bet_amount * rand(1, 5);
                    else
                        $win_amount = 0;                    
                }

                $sid = uniqid();
                $this->setGame($sid, "netent_$gid");
                $mg_id = rand(1000000, 10000000000);
                $r_id = rand(1000000, 10000000000);

                $res = $this->withdrawAndDeposit($uid, $gid, $mg_id, $r_id, $bet_amount, $win_amount);

                echo "Res:: ".$res;
                
                if($res === false)
                    phive()->dumpTbl('stressTest_noreply', 'yes');
            }
            echo "Ending $i play\n";
            /*
               if($i % 60 == 0){
               echo "Minute cron start.\n";
               phive('Trophy')->minuteCron();
               phive('Trophy')->xpCron();
               phive('Trophy')->completeCron();
               phive('Trophy')->expireAwards();
               echo "Minute cron end.\n";
               }
             */

            $duration = (microtime(true) - $smicro) * 1000000;
            if($duration < 1000000)
                usleep(1000000 - $duration);
        }
    }

}
