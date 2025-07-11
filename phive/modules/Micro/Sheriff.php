<?php
require_once __DIR__ . '/TypeOne.php';
//TODO if this company ever makes a comeback the below needs extensive testing, _getBalance for instance doesn't return false anymore if the game is missing
class Sheriff extends TypeOne{
  function __construct(){
    parent::__construct();
  }

  function parseJackpots(){
    $json 	= file_get_contents($this->getSetting('jackpot_url'));
    $arr = json_decode($json, true);
    $jps = array(
      array(
        'jp_id'      => 'sheriff_mega',
        'jp_name'    => 'Sheriff Mega Money',
        'module_id'  => 'sheriff_mega',
        'network'    => 'sheriff',
        'jp_value'   => $arr['kingscastle']['amount']),
      array(
        'jp_id'      => 'sheriff_slotoffortune',
        'jp_name'    => 'Sheriff Slot of Fortune',
        'module_id'  => 'sheriff_slotoffortune',
        'network'    => 'sheriff',
        'jp_value'   => $arr['slotoffortune']['amount']));
    return $this->fxJps($jps, 'EUR');
  }

  function checkHash($req, $hash){
    return $hash == sha1($this->getSetting('salt').http_build_query($req));
  }

  function buildResponse($res){
    if(is_string($res))
      return array('statuscode' => 0, 'message' => $res);
    if(is_numeric($res))
      return array('statuscode' => $res, 'message' => 'OK');
    return array_merge(array('statuscode' => 1, 'message' => "OK"), $res);
  }

  function executeRequest($func, $json){
    $req = json_decode($json, true);
    $hash = $req['signature'];
    unset($req['signature']);
    $this->action = $func;
    return json_encode($this->buildResponse($this->checkHash($req, $hash) ? $this->$func($req) : "Wrong signature"));
  }

  function getUsr($uid){
      //This is stupid, get raw user instead
      $user = cu(array($uid) ? $uid['player_reference'] : $uid);
      if(!is_object($user))
          return false;

      $this->user             = $user;
      $this->user_data        = $user->data;
      $this->uid              = $user->data['id'];
      $GLOBALS['mg_username'] = $user->data['username'];
      return $user->data;
  }

  function getActiveFspin($uid){
    if(!isset($this->cur_fspin_id)){
      $fspin = phive("SQL")->loadAssoc("
        SELECT * FROM bonus_entries
        WHERE user_id = $uid
        AND bonus_tag = 'sheriff'
        AND bonus_type = 'freespin'
        AND status IN('active', 'pending')
        AND cash_progress > 0");
      $this->cur_fspin_id = $fspin['id'];
    }else if(empty($this->cur_fspin_id))
      return array();
    else
      $fspin = phive("Bonuses")->getBonusEntry($this->cur_fspin_id, $uid);

    return $fspin;
  }

  function getSpinEntry($u){
    $e = $this->getActiveFspin($u['id']);
    return array($e, 'ext_id', 'cash_progress');
  }

  function notFspin($e){
    return empty($e);
  }

  function getFspinArr($u, $req, $game = '', $action = ''){
    if(empty($game))
      $game = $this->getGameByRef($req);

    $action = empty($action) ? $this->action : $action;

    list ($e, $info_key, $spin_key) = $this->getSpinEntry($u);

    if($this->notFspin($e))
      return array();

    list($gids, $info, $tot_spins) = explode(':', $e[$info_key]);
    $gids = explode('|', $gids);
    if(!in_array($this->remSheriff($game['ext_game_name']), $gids))
      return array();

    list($coinvalue, $wlines, $linebet) = explode('|', $info);

    return array(
      'type' 			=> 'compspinmanager',
      'spins_total' 	=> $tot_spins,
      'spins_left' 	=> $e[$spin_key],
      'jackpot' 		=> false,
      'coinvalue' 	=> $coinvalue,
      'winlines' 		=> $wlines,
      'linebet' 		=> $linebet);
  }

  function attachFspins($res, $user, $req, $fspins = ''){
    if(empty($fspins))
      $fspins = $this->getFspinArr($user, $req);
    if(!empty($fspins))
      $res['feature'] = $fspins;
    return $res;
  }

  function validate($req){
    if($user = $this->getUsr($req))
      return $this->attachFspins(array(), $user, $req);
    return "User not found";
  }

  function balance($req){
    if(!$user = $this->getUsr($req))
      return "User not found";

    if(!$balance = $this->_getBalance($user, $req))
      return 'No game ref or id';

    list($real, $bonus) = $balance;

    return $this->attachFspins(array('balance' => array('real' => $real, 'bonus' => $bonus)), $user, $req);
  }

  function betResultGetUser($req){

    $user = $this->getUsr($req);
    if(!$user)
      return false;

    $amount = abs($req['transaction']['real']) + abs($req['transaction']['bonus']);
    $id  	= "sher{$req['transaction_id']}";
    $this->setParams($amount, $id, $req['gamerun_id']);
    $this->gref = $this->new_token['game_ref'] = $this->token['game_ref'] = $this->getGameRef($req);
    return array($user, $amount, $id);
  }

  function getGameRef($req){
    if(!empty($this->gref))
      return $this->gref;
    $this->gref = empty($req['game_id']) ? $req['custom']['gref'] : 'sheriff'.$req['game_id'];
    return $this->gref;
  }

  function handleFspinBet($u, $action = 'bet'){
    $e = $this->getActiveFspin($u['id']);

    if(empty($e))
      return array();

    $e['cash_progress'] = (int)$e['cash_progress'] + ($action == 'bet' ? -1 : 1);
    phive("SQL")->save('bonus_entries', $e);

    if($e['status'] == 'pending' && $action == 'bet')
      phive('Bonuses')->activatePendingEntry($e["id"], $u['id']);

    return $e;
  }

  function debit($req, $ret_balance = false, $default_gref = ''){
    if(!$start = $this->betResultGetUser($req))
      return 'User not found';
    list($user, $tmp_bet_amount, $id) = $start;
    $orig_result = $this->getBetByMgId($id);
    if(empty($orig_result)){
      $balance = $this->_getBalance($user, $req);

      $fspin = $this->getFspinArr($user, $req);
      if(empty($tmp_bet_amount)){
        $this->handleFspinBet($user);
        $fspin['spins_left']--;
      }

      $cur_game 	= $this->getGameByRef($req, $default_gref);
      if(empty($cur_game))
        return 'No game ref or id';
      //$bet_amount		= floor($tmp_bet_amount * (1 - $cur_game['jackpot_contrib']));
      $bet_amount		= $tmp_bet_amount;
      //$jp_contrib 	= $tmp_bet_amount - $bet_amount;
      $jp_contrib = round($bet_amount * $cur_game['jackpot_contrib']);
      $balance = $this->lgaMobileBalance($user, $cur_game['ext_game_name'], $balance, $cur_game['device_type'], $bet_amount);
      if($balance < $tmp_bet_amount)
        return 'Balance too low';
      $GLOBALS['mg_id'] = $id;

        $balance = $this->changeBalance($user, -$bet_amount, $req['gamerun_id'], 1);
        if($balance === false)
            return 'DB error';
        $bonus_bet = empty($this->bonus_bet) ? 0 : 1;

        $result = $this->insertBet($user, $cur_game, $req['gamerun_id'], $id, $bet_amount, $jp_contrib, $bonus_bet, $balance);
        if(!$result)
          return 'DB error';

      $balance 		= $this->betHandleBonuses($user, $cur_game, $bet_amount, $balance, $bonus_bet, $req['gamerun_id'], $id);
    }

    if($ret_balance){
      if(!empty($orig_result))
        return $this->_getBalance($user, $req);
      return $balance;
    }

    return $this->attachFspins(array(), $user, $req, $fspin);
  }

  function credit($req, $ret_balance = false, $default_gref = ''){
    $this->game_action = 'win';

    if(!$start = $this->betResultGetUser($req))
      return 'User not found';
    list($user, $amount, $id) = $start;

    if(empty($amount))
      return array();

    $orig_result = $this->getBetByMgId($id, 'wins');
    $GLOBALS['mg_id'] = $id;
    if(empty($orig_result)){

      $fspin = $this->getFspinArr($user, $req);
      if(!empty($fspin)){
        $entry 		= $this->getActiveFspin($user['id']);
        $this->handleFspinWin($entry, $amount);
        $balance 	= $this->_getBalance($user, $req);
      }

      $cur_game 	= $this->getGameByRef($req, $default_gref);
      if(!empty($fspin))
        $bonus_bet 	= 3;
      else
        $bonus_bet 	= empty($this->bonus_bet) ? 0 : 1;
        $result 	= $this->insertWin($user, $cur_game, $balance, $req['gamerun_id'], $amount, $bonus_bet, $id, 2);

        if($result === false)
            return 'DB error';

        if(empty($fspin)){
            $balance 	= $this->changeBalance($user, $amount, $req['gamerun_id'], 2);
            if($balance === false)
                return 'DB error';
        }

      if(!$result)
        return 'Database error, could not log result';
      $balance 	= $this->handlePriorFail($user, $req['gamerun_id'], $balance, $amount);
    }

    if($ret_balance){
      if(!empty($orig_result))
        return $this->_getBalance($user, $req);
      return $balance;
    }

    return $this->attachFspins(array(), $user, $req, $fspin);
  }

  function rollback($req){
    if(!$start = $this->betResultGetUser($req))
      return 'User not found';
    list($user, $amount, $id) = $start;
    $tbl = 'bets';
    $result = $this->getBetByMgId($id);

    $already = false;
    if(empty($result)){
      $already_id 	= $id.'ref';
      $result 		= $this->getBetByMgId($already_id);

      if(!empty($result))
        $already 	= true;
      else
        return 2;
    }

    if($already == false){

      if($tbl == 'bets')
        $this->handleFspinBet($user, 'rollback');

      $balance = $this->changeBalance($user, $amount, $result['trans_id'], 7);
      if($balance === false)
        return 'Database error, could not change balance on rollback';
      $this->doRollbackUpdate($id, $tbl, $balance, $amount);
    }

    //if($ret_balance)
    //  return $balance;

    return $this->attachFspins(array(), $user, $req);
  }

  function endsession($req){
    return array();
  }

  function ping($req){
    return array();
  }

  function remSheriff($str){
    return str_replace('sheriff', '', $str);
  }

  //?site_id=%1&player_reference=%2&game_id=%3&mode=%4&locale=%5&currency=%6&session_id=%7&session_id=%7&x_gref=%8
  function getDepUrl($gid, $lang, $game = null, $show_demo = false, $gref = ''){
    $game 		= phive("MicroGames")->getByGameId($gid);
    $gref 		= empty($gref) ? phive('MicroGames')->getGameRefById($gid) : $gref;
    $site_id 	= $this->getSetting('site_id');
    $locale 	= phive('MicroGames')->getGameLocale($game['game_id'], $lang);
    if($locale == 'en_GB')
      $locale = 'en_US';
    $base_url 	= $this->getSetting('base_url');

    if(isLogged()){
      $mode 		= 'real';
      $uid 		= cuPlAttr('id', '', true);
      $currency 	= cuPlAttr('currency', '', true);
      $sid 		= phive()->uuid();
    }else{
      $mode 		= 'free';
      $uid = $sid	= 1;
      $currency 	= ciso();
    }

    return $base_url.str_replace(
      array('%1', '%2', '%3', '%4', '%5', '%6', '%7' , '%8'),
      array($site_id, $uid, $this->remSheriff($gref), $mode, $locale, $currency, $sid, $gref),
      $this->getSetting('normal_url'));
  }

  function getMobilePlayUrl($gref, $lang, $lobby_url, $g, $args = [], $show_demo = false){
    $url 		= $this->getDepUrl('', $lang, null,$show_demo, $gref);
    return $url."&lobby_url=".$lobby_url."/{$lang}".$this->getSetting('home');
  }

  function importGames($arr, $active = 0){
    foreach($arr as $g){
      $name 		= str_replace("'", "&#39;", trim($g['name']));
      $url		= strtolower(str_replace(array('$', "&#39;", ' ', "'", '.', '&'), array('s', '', '-', "", '', 'and'), $name).'-sheriff');
      $id 		= 'sheriff'.trim($g['id']);
      $jp_contrib = trim($g['jackpot contribution']);
      $tag 		= trim($g['tag']);
      $insert = array(
        'game_name' 	=> $name,
        'tag' 			=> $tag,
        'game_id' 		=> $id,
        'languages'		=> 'en,fi,sv',
        'ext_game_name' => $id,
        'game_url'		=> $url,
        'meta_descr'	=> "#game.meta.description.".$url,
        'bkg_pic'		=> $id."_BG.jpg",
        'html_title'	=> $name,
        'device_type'	=> trim($g['id']) < 1000 ? 'flash' : 'html5',
        'op_fee'		=> 0.11,
        'operator'		=> 'Sheriff',
        'network'		=> 'sheriff',
        'active'		=> $active,
        'blocked_countries' => 'US IL DA BE',
        'jackpot_contrib' => empty($jp_contrib) ? 0 : $jp_contrib
      );

      $old = phive("MicroGames")->getByGameRef($id, $type);
      if(!empty($old))
        echo "$name with id $id is already in the database.\n\n";
      else{
        phive("SQL")->insertArray('micro_games', $insert);
        print_r($insert);
      }
    }
  }


}
