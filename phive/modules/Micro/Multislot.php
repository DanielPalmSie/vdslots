<?php
require_once __DIR__ . '/Sheriff.php';
class Multislot extends Sheriff{

  function __construct(){
    parent::__construct();
  }

  function activateFreeSpin(&$entry, $na, $bonus){
  }
  
  function getBetOpFee($bet_amount, $cur_game, $jp_contrib){
    return $bet_amount * $cur_game['op_fee'];
  }

  function getWinOpFee($amount, $cur_game, $bonus_bet){
    if($bonus_bet == 3)
      return ($amount * 1) * $cur_game['op_fee'];
    return $amount * $cur_game['op_fee'];
  }
  
  function execute($req){
    if(!empty($req['token'])){
      $this->token_arr	= phM('hgetall', $req['token'], $this->exp_time);
    }
    $action = $req['action'];
    $this->dumpTst("multislot_{$action}", $req);
    unset($req['action']);
    $res = $this->$action($req);
    $this->dumpTst("multislot_{$action}_res", $res);
    if(!empty($req['token'])){
      if(empty($this->token_arr))
        return json_encode($this->buildResponse("Token does not exist."));
      phM('hmset', $req['token'], $this->token_arr, $this->exp_time);
    }
    return json_encode($this->buildResponse($res));
  }

    function getUsr($req = ''){
        $user_id = empty($req['user_id']) ? $this->token_arr['user_id'] : $req['user_id'];  

        $user = cu($user_id );
        if(!is_object($user))
          return false;

        $this->user = $user;
        $this->user_data = $user->data;
        $GLOBALS['mg_username'] = $user->data['username'];
        $this->uid = $user->data['id'];
        return $user->data;
    }

  function incGameRunId(&$req){
    $this->token_arr['gamerun_id']++;
    $req['gamerun_id'] = $this->token_arr['gamerun_id'];
  }

  /*
  function _getBalance($user, $req){
    $res = parent::_getBalance($user, $req);
    $res = array_sum($res);
    if(empty($res))
      return 0;
    return $res;
  }
   */
  
  function getGameRef($req){
    return $this->token_arr['gref'];
  }

    function authenticate($req){
        if($req['password'] == phMget(mKey($req['user_id'], 'multislot-password'))){
            if($user = $this->getUsr($req))
                return array('currency' => $user['currency']);
            return "User not found";
        }
        return "Wrong password";
    }

  function getBalance($req){
    if(!$user = $this->getUsr($req))
      return "User not found";    
    $balance = $this->_getBalance($user, $req);
    if($balance === false)
      return 'No game ref or id';
    return $this->retCommon($balance);
  }

  function betResultGetUser($req){

    $user = $this->getUsr($req);
    if(!$user)
      return false;

    $amount = abs($req['amount']);
    $id  	= "mslot{$req['transaction_id']}";
    $this->setParams($amount, $id, $req['gamerun_id']);
    $this->gref = $this->new_token['game_ref'] = $this->token['game_ref'] = $this->getGameRef($req);
    return array($user, $amount, $id);
  }

  function getFspinArr($u, $req, $game = '', $action = ''){
    return array();
  }

  function attachFspins($res, $user, $req, $fspins = ''){
    return $res;
  }

  function retCommon($ret){
    if(is_numeric($ret))
      return array('balance' => $ret);
    else
      return $ret;
  }
  
  function bet($req){
    $this->incGameRunId($req);
    return $this->retCommon(parent::debit($req, true, 'multislot_system'));
  }

  function win($req){
     //$this->incGameRunId($req);
     return $this->retCommon(parent::credit($req, true, 'multislot_system'));
  }

  //if parent rollback returns array we are OK, if string not ok
  function rollback($req){
    $bet = $this->getBetByMgId("mslot{$req['transaction_id']}");
    //$this->token_arr['gref'] = $bet[''];
    $req['user_id'] = $bet['user_id'];
    return parent::rollback($req);
  }

  function remMslot($str){
    return str_replace('mslot', '', $str);
  }

  function getMobilePlayUrl($gref, $lang, $lobby_url = '', $g = null, $args = [], $show_demo = false){
    return $this->getDepUrl($gref, $lang, null,false, $gref, 0);
  }
  
  function getDepUrl($gid, $lang, $game = null, $show_demo = false, $gref = '', $noexit = 1){
    $base_url 	= $this->getSetting('base_url');
    if(empty($gref))
      $gref = $gid;
    if(isLogged()){
      $udata = ud();
      $pwd = uniqid();
      phMset(mKey($udata, 'multislot-password'), $pwd);

      $token_req = array(
        'UserId' => $udata['id'],
        'UserKey' => $pwd,
        'Provider' => $this->getSetting('provider'),
        'AccountId' => 1,
        'target' => 'none');

      
      $this->dumpTst('multislot_token_req', $token_req);

      $log_url = $this->getSetting('token_url').http_build_query($token_req);
      $this->dumpTst('multislot_token_req_url', $log_url);
      
      $tres = phive()->get($log_url);

      $this->dumpTst('multislot_token_req_answer', $tres);
      
      //list($key, $token)
      $tmp = phive()->decUrl($tres, false);
      $token = $tmp['Token'];
      $this->dumpTst('multislot_token', $token);
      
      //$token = '123';
      phM('hmset', $token, array('gref' => $gref, 'user_id' => $udata['id'], 'password' => $pwd), $this->exp_time);
      return $base_url.str_replace(array('%1', '%2', '%3', '%4'), array($token, 1, $lang, $this->remMslot($gref)), $this->getSetting('url')."&noexit=$noexit");
    }else
      return $base_url.str_replace(array('%1', '%2', '%3'), array(-1, $lang, $this->remMslot($gref)), $this->getSetting('demo_url')."&noexit=$noexit");    
  }
}
