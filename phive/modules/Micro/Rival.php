<?php
require_once __DIR__ . '/TypeOne.php';
class Rival extends TypeOne{
  function __construct(){
    parent::__construct();
  }

  function execute($json) {
    $req                = json_decode($json, true);
    $func               = $req['function'];
    if($this->getSetting('test') === true)
      phive()->dumpTbl("rival-$func-req", $json);
    $hash               = $req['hmac'];
    $this->session_data = $this->new_token = json_decode(phMget($req['sessionid']), true);

    unset($req['hmac']);
    $this->action       = $func;
    if($func == "status")
      return $this->$func($req);

    if (!in_array($func, array("rollback", 'updatebalance'))) {
      if (empty($this->session_data))
        return $this->buildError($req, 'No session');
    }

    $res = $this->verifyHash($req, $hash) ? $this->$func($req) : $this->buildError($req, 'Authentication error');

    if($this->getSetting('test') === true)
      phive()->dumpTbl("rival-$func-res", $res);

    if(!empty($res['balance']))
      $res['balance'] = phive()->twoDec($res['balance']);

    return $res;
  }

    function validate($req) {
        if (!$user = $this->getUser($req))
            return $this->buildError($req, 'User not found');

        $balance = $this->_getBalance($user, $req);

        if($balance === false)
            return 'No game ref or id';

        return array('balance' => (int)$balance, 'currency' => $user['currency']);
    }

    function getbalance($req, $from_cache = true){
        if (!$user = $this->getUser($req))
            return $this->buildError($req, 'User not found');
        $balance = $this->_getBalance($user, $req);

        if($balance === false)
            return 'No game ref or id';

        return array('balance' => (int)$balance, 'currency' => $user['currency']);
    }

  function updatebalance($req) {
    $user       = $this->getUser($req);
    $amount     = $req['amount']; // this is the balance update amount not the win amount or lose amount
    $currency   = $user['currency'];
    $min        = empty($req['minbalance'])? 0 : $req['minbalance'];
    $tid        = 'rival'.$req['id'];
    $this->gref = $this->getGameRef($req);
    $result     = $this->getBetByMgId($tid); // check if this balance update has already been handled by us
    $balance    = $this->_getBalance($user, $req);

    if(!empty($result))
      $tid .= '-'.uniqid();

    $cur_game = phive('MicroGames')->getByGameRef($this->gref, $this->session_data['dev_type']);
    if(empty($cur_game))
      return $this->buildError($req, 'Game could not be found');
    $win = (int)(100 * $amount + 100 * $min); // this is always >= 0
    $bet = (int)(100 * $min);
    if($bet > 0){ // is 0 only if minbalance not sent, which happens on only separate win updates without crediting
      $result = $this->_withdraw($req, $user, $bet, $tid, $cur_game, $balance, $currency);
      if ($result === "LIMITS_EXCEEDED" || $result == "INSUFFICIENT_FUNDS")
        return $this->buildError($req, 'Withdraw failed', $result);
      else
        $balance = $result; // new balance
    }

    if ($win > 0) // is 0 only if -amount equals minimum, meaning bet and lost, no win so no deposit
      $result = $this->_deposit($req, $user, $win, $tid, $cur_game, $balance, $currency);

    if(empty($result))
      $result = array('balance' => $balance, 'currency' => $user['currency']);

    return $result;

  }

  function getRollbackRows($req, $tbl){
    $rows = phive('SQL')->shs('merge', '', null, $tbl)->loadArray("SELECT * from $tbl WHERE mg_id LIKE 'rival{$req['id']}%'");
    return array_filter($rows, function($r){ return strpos($r['mg_id'], 'ref') === false; });
  }

  function rollback($req){
    foreach(array('bets', 'wins') as $tbl){
      $rows = $this->getRollbackRows($req, $tbl);
      foreach($rows as $r)
        list($balance, $user) = $this->_rollback($tbl, $r);
    }

    if(empty($balance))
      $balance = $user['cash_balance'];
    return array('balance' => (int)$balance, 'currency' => $user['currency']);
  }

  function _rollback($tbl, $row) {
    $user = $this->getUser($row['user_id']);

    $balance = false;

    if ($tbl == 'bets') {
      $type = 7;
      $amount = $row['amount'];
    } else {
      $type = 1;
      $amount = -$row['amount'];
    }

    $type    = $tbl == 'bets' ? 7 : 1;
    $balance = $this->changeBalance($user, $amount, $result['trans_id'], $type);
    $this->doRollbackUpdate($row['mg_id'], $tbl, $balance, $amount);

    return array($balance, $user);
  }

    function _withdraw($req, $user, $amount, $tid, $cur_game, $balance, $currency) {
        if (!empty($amount)) {
            if (empty($balance))
                return "INSUFFICIENT_FUNDS"; // if called with $balance = 0

            $balance = $this->lgaMobileBalance($user, $cur_game['ext_game_name'], $balance, $cur_game['device_type'], $amount);
            if ($balance === 0)
                return "LIMITS_EXCEEDED";

            if ($balance < $amount)
                return "INSUFFICIENT_FUNDS";

            $balance    = $this->changeBalance($user, "-$amount", $tid, 1);
            if($balance === false)
                return "DB_ERROR";
            $bonus_bet  = empty($this->bonus_bet) ? 0 : 1;

            $jp_contrib = $amount * $cur_game['jackpot_contrib'];
            $result     = $this->insertBet($user, $cur_game, $req['transid'], $tid, $amount, $jp_contrib, $bonus_bet, $balance);
            if($result === false)
                return "DB_ERROR";

            $balance    = $this->betHandleBonuses($user, $cur_game, $amount, $balance, $bonus_bet, $req['transid'], $tid);
            return array('balance' => $balance, 'currency'=> $currency);
        } else
            return array('balance' => $this->_getBalance($req), 'currency'=>$currency);
        return array();
    }

    function _deposit($req, $user, $amount, $tid, $cur_game, $balance, $currency, $award_type = 2, $recur = true) {
        if (!empty($amount)) {
            $bonus_bet = empty($this->bonus_bet) ? 0 : 1;
            $result    = $this->insertWin($user, $cur_game, $balance, $req['transid'], $amount, $bonus_bet, $tid, $award_type);
            if($result === false)
                return "DB_ERROR";
            $balance   = $this->changeBalance($user, $amount, $req['transid'], $award_type);
            if($balance === false)
                return "DB_ERROR";
            $balance   = $this->handlePriorFail($user, $tid, $balance, $amount);
            return array('balance' => $balance, 'currency' => $currency);
        } else
        return array('balance' => $this->_getBalance($req), 'currency' => $currency);

        return array();
    }

  function status() { return array(); }

    function getUser($uid){
      $user = ud(is_array($uid) ? $uid['playerid'] : $uid);
      if(!is_array($user))
        return false;

      $this->user_data = $user;
      $this->uid = $user['id'];
      return $user;
    }

  function verifyHash($req, $hash) {
    $secret    = $this->getSetting('salt');
    $httpQuery = urldecode(http_build_query($req));
    if($this->getSetting('test') === true)
      phive()->dumpTbl("verifyHash", $httpQuery);
    return $hash == hash_hmac('sha256', $httpQuery, $secret);
  }

  /*
  function buildResponse($res){
    if(is_string($res))
      return array('statuscode' => 0, 'message' => $res);
    if(is_numeric($res))
      return array('statuscode' => $res, 'message' => 'OK');
    return array_merge(array('statuscode' => 1, 'message' => "OK"), $res);
  }
  */

  function buildError($req, $error, $user_error = ''){
    $arr = array('error' => $error);
    if(!empty($user_error))
      $arr['user_error'] = $user_error;
    phive()->dumpTbl('rival_error', array($req, $arr, $this->session_data));
    return $arr;
  }

  function getGameRef($req){
    $gref = $this->session_data['game_ref'];
    if(empty($gref))
      $gref = $req['gameid'];
    if (!empty($gref)){
      $this->gref = "rival$gref";
      $this->new_token['game_ref'] = $this->gref;
    }
    return $this->gref;
  }

  function stripRival($str){
    return str_replace('rival', '', $str);
  }

  function getDepUrl($gid, $lang, $game = null, $show_demo = false, $gref = '', $mobile = false){
    if(empty($gref))
      $gref = phive('MicroGames')->getGameRefById($gid);

    $gref     = $this->stripRival($gref);

    if(isLogged()){
      $uid  = cuPlAttr('id');
      $lang = cuPlAttr('preferred_lang');
      $sid = mKey($uid, phive()->uuid());
      $dev_type = $mobile? "html5" : "flash";
      phMset($sid, json_encode(array('user_id' => $uid, 'game_ref' => $gref, 'lang' => $lang, 'device_type' => $dev_type)));
      $pff = ''; // real
    }else {
      $pff = '&anon=1&anonOnly=1'; // play for fun
    }

    $base_url 	= $this->getSetting('base_url');
    if ($mobile == true)
      $target = $this->getSetting('mobile_url_end');
    else
      $target    = $this->getSetting('desktop_url_end');
    $params     = $this->getSetting('url_parameters');

    $url = $base_url.$target.str_replace(array( '%1', '%2', '%3'), array($gref, $uid, $sid), $params).$pff.'&resize=1';
    if($this->getSetting('test') === true)
      phive()->dumpTbl('rival_url', $url);

    return $url;
  }

  function getMobilePlayUrl($gref, $lang, $lobby_url, $g, $args = [], $show_demo = false){
    return $this->getDepUrl('', $lang, null, $show_demo, $gref, true);
  }

}
