<?php
require_once __DIR__ . '/TypeOne.php';

class Gamesos extends TypeOne{
    
  protected string $logger_name = 'gamesos';
    
  function exec(&$req, $action){
    if($req['merch_id'] != $this->getSetting('merch_id') || $req['merch_pwd'] != $this->getSetting('merch_pwd'))
      return $this->buildResponse(-48);
    $this->req = $req;
    $this->setGameData($req);
    
    $this->logger->debug(__METHOD__, ['request', json_encode($req, true)]);
    
    $response = $this->buildResponse($this->$action($req));

    $this->logger->debug(__METHOD__, ['response' => json_encode($response, true)]);

    return $response;
  }

  function setGameData(&$req){
    $key = !empty($req['ticket']) ? 'ticket' : 'cust_session_id';
    $this->token = $this->new_token = json_decode(phMget($req[$key]), true);
    $this->game_ref = $this->token['game_ref'];
    $this->uid = $this->token['user_id'];
    if(empty($this->token))
      return false;
    return true;
  }

  function buildResponse($res){
    if(is_string($res))
      $arr = array('error_code' => -1, 'error_msg' => $res);
    else if(is_numeric($res))
      $arr = array('error_code' => $res);
    else{
      $arr = $res;
      if(isset($arr['balance']))
        $arr['balance'] = phive()->twoDec($arr['balance']);
      if(!isset($arr['error_code']))
        $arr['error_code'] = 0;
    }
    $ret = '';
    if(empty($arr['cust_session_id']))
      $arr['cust_session_id'] = $this->req['cust_session_id'];
    foreach($arr as $key => $val)
      $ret .= "$key=$val\n";
    return trim($ret);
  }

    function getGameRef(&$req){
        $this->gref = $req['game_code'];
        if(empty($this->gref))
            $this->gref = $this->game_ref;
        $this->gref = "gamesos{$this->game_ref}";
        if($this->gref == 'gamesos'){
            $this->logger->warning('gamesos_missing_game', ['response' => json_encode($req,true)]);
            phive()->dumpTbl('gamesos_missing_game', $req);
            $this->gref = 'gamesos_system';
        }
        return $this->gref;
    }

  function validate_ticket(&$req){
    $uid = $this->uid;
    if(empty($uid))
      return -3;
    $ud = ud($uid);
    phMdel($req['ticket']);
    $sid = uniqid();
    phMset($sid, json_encode(array('user_id' => $uid, 'game_ref' => $this->game_ref)));
    if(empty($ud))
      return -2;
    $test = strpos($ud['username'], 'gamesostest') !== false ? 'true' : 'false';
    return array(
      'cust_id'       => $ud['id'],
      'cust_login'    => $ud['username'],
      'currency_code' => $ud['currency'],
      'language'      => $ud['preferred_lang'],
      'country'       => $ud['country'],
      'test_cust'     => $test,
      'cust_session_id'  => $sid
    );
  }

  function getUsr(&$req){
    $ud 	= ud($req['cust_id']);
    if(empty($ud))
      return -2;
    $this->ud = $ud;
    $GLOBALS['mg_username'] = $ud['username'];
    return $ud;
  }

  function get_balance(&$req){
    $ud = $this->getUsr($req);
    if(is_numeric($ud))
      return $ud;

    $this->getGameByRef($req);
    if(empty($this->game))
      return 'missing game';

    $balance = $this->_getBalance($ud, $req);

    return array(
      'balance' => $balance,
      'currency_code' => $ud['currency']
    );
  }

  function getAmountTid(&$req) {
    return array($req['amount'] * 100, "gamesos" . $req['trx_id']);
  }

  function debit(&$req){

    $ud = $this->getUsr($req);
    if(is_numeric($ud))
      return $ud;
    list($amount, $tid) = $this->getAmountTid($req);
    $result             = $this->getBetByMgId($tid);
    $balance            = $this->_getBalance($ud, $req);
    if(!empty($result)){
      return array('trx_id' => $result['id'], 'balance' => $balance, 'error_code' => 1);
    }else{

      $cur_game = $this->getGameByRef($req);
      if(empty($cur_game))
        return 'missing game';

      if(!empty($amount)) {
        $jp_contrib = round($amount * $cur_game['jackpot_contrib']);

        $balance = $this->lgaMobileBalance($ud, $cur_game['ext_game_name'], $balance, $cur_game['device_type'], $amount);
        if($balance < $amount)
	  return -53;

        $GLOBALS['mg_id'] = $tid;

          $balance          = $this->changeBalance($this->ud, "-$amount", $tid, 1);
          $bonus_bet        = empty($this->bonus_bet) ? 0 : 1;

          $extid           = $this->insertBet($this->ud, $cur_game, 0, $tid, $amount, $jp_contrib, $bonus_bet, $balance);
          if(!$extid)
              return 'db error';

        $balance          = $this->betHandleBonuses($this->ud, $cur_game, $amount, $balance, $bonus_bet, 0, $tid);
        return array('trx_id' => $extid, 'balance' => $balance);
      }else
        return array('trx_id' => 0, 'balance' => $balance);
    }
  }

  function stripGamesos($str){
    return str_replace('gamesos', '', $str);
  }

  function getUrlCommon($gref){
    $gref = $this->stripGamesos($gref);
    $mid = $this->getSetting('merch_id');
    $login_url = $this->getSetting('login_url');
    if(isLogged()){
      $ticket = phive()->uuid()."-".$_SESSION['mg_id'];
      phMset($ticket, json_encode(array('user_id' => $_SESSION['mg_id'], 'game_ref' => $gref)));
      $mode = 'real';
    }else
      $mode = 'fun';
    return array($mid, $gref, $mode, $ticket, $login_url);
  }

  function getMobilePlayUrl($gref, $lang, $lobby_url = '', $g = '', $args = [], $show_demo = false){
    list($mid, $gref, $mode, $ticket, $login_url) = $this->getUrlCommon($gref);
    return $this->getProxySetting('mobile_launch_url')."?code=$gref&merch_id=$mid&mode=$mode&language=$lang&ticket=$ticket&merch_login_url=$login_url&lockPlaymode=true&singlegame=true&disableLogout=true";
  }

  function getDepUrl($gid, $lang, $game = null, $show_demo = false){
    $gref = phive('MicroGames')->getGameRefById($gid);
    list($mid, $gref, $mode, $ticket, $login_url) = $this->getUrlCommon($gref);
    return $this->getProxySetting('launch_url')."?game_code=$gref&merch_id=$mid&playmode=$mode&lockPlaymode=true&singlegame=true&disableLogout=true&language=$lang&ticket=$ticket&merch_login_url=$login_url";
  }

  function credit(&$req){
    $ud = $this->getUsr($req);
    if(is_numeric($ud))
      return $ud;
    list($amount, $tid) = $this->getAmountTid($req);
    $result             = $this->getBetByMgId($tid, 'wins');
    $balance            = $this->_getBalance($ud, $req);
    if(!empty($result)){
      return array('extid' => $result['id'], 'balance' => $balance, 'error_code' => 1);
    }else{
      $cur_game = $this->getGameByRef($req);
      if(empty($cur_game))
        return 'missing game';

      if(!empty($amount)){
        if($this->frb_win === true)
          $bonus_bet = 3;
        else
          $bonus_bet = empty($this->bonus_bet) ? 0 : 1;

        $extid  = $this->insertWin($this->ud, $cur_game, $balance, 0, $amount, $bonus_bet, $tid, 2);
        if(!$extid)
            return 'db error';

          $balance = $this->changeBalance($this->ud, $amount, 0, 2);

        $balance = $this->handlePriorFail($this->ud, $tid, $balance, $amount);
        return array('trx_id' => $extid, 'balance' => $balance);
      }else
        return array('trx_id' => 0, 'balance' => $balance);
    }
  }
  function realityCheckStatus(&$req){
    $error_code = 0;
    if(phive()->getSetting('ukgc_lga_reality') !== true && phive("Config")->getValue('reality-check-mobile', 'wi') !== 'on'){
      $rc_status = "disabled";
      $aReturn = array(
          'rc_status' => $rc_status,
          'error_code' => $error_code
      );
      return $aReturn;
    }

    $ud = $this->getUsr($req);
    $uid = $ud['id'];
    $username = $ud['username'];
    unset($ud);
    $intv =  cuSetting('cur-reality-check-interval') ? cuSetting('cur-reality-check-interval') : $defaultValue;
    $stime = phMget("$uid-cur-reality-check-stime");
    $action_id = false;
    $now = time();
    $etime = $stime + ($intv * 60);

    $remaining = $etime - $now;
    $in_session = $now - $stime;
    if(!empty($in_session)){
      $in_session_m = (int) ($in_session / 60);
    }
    $rc_status = "running";

    $aReturn = array(
        'rc_status' => $rc_status,
        'time_remaining' => $remaining,
        'time_in_session' => $in_session,
        'error_code' => $error_code
    );
    if ($remaining < 0) {
      $rc_status = "expired";
      $error_code = 7;
      $siteUrl = urlencode(phive()->getSiteUrl());
      $history_link = "{$siteUrl}%2Faccount%2F{$username}%2Fgame-history%2F";
      $message = "You have requested a Reality Check after every {$intv} minutes of play ";
      $message.= "Your gaming session has now reached {$in_session_m} minutes. You can check ";
      $message.= " your <a href='{$history_link}'> betting history </a>";
      $message.= " for more details. To continue playing select Continue playing below or to stop playing click Stop playing.";
      $error_msg = "reality check session timer expiry";
//      $action_id = uniqid('', true);
      $action_id = time();
      $aReturn = array(
          'rc_status' => $rc_status,
          'time_remaining' => $remaining,
          'time_in_session' => $in_session,
          'message' => $message,
          'action_id' => $action_id,
          'error_code' => $error_code,
          'error_msg' => $error_msg
      );
    }
    return $aReturn;
  }
  function notifyRealityCheckChoice(&$req){
    if(phive()->getSetting('ukgc_lga_reality') !== true && phive("Config")->getValue('reality-check-mobile', 'wi') !== 'on'){
      return;
    }
    $ud = $this->getUsr($req);
    $uid = $ud['id'];
    unset($ud);
    if($req['action_type'] === 'reset'){
      phMset("$uid-cur-reality-check-stime");
    }
  }
}
