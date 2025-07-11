<?php
require_once __DIR__ . '/Casino.php';

class Bsg extends Casino{

    /**
     * Cached session data to prevent multiple unnecessary roundtrips to Redis.
     * @var array
     */
    public $bsg_session_data = [];
    
  function activateFreeSpin(&$entry, $na, $bonus, $ext_id){
      $entry['ext_id'] = $ext_id;
  }

  function __construct(){
    parent::__construct();
    $this->debug = $this->getSetting('test');
  }

    function getBetOpFee($bet_amount, $cur_game, $jp_contrib){
        return $bet_amount * $cur_game['op_fee'];
    }

  function getWinOpFee($amount, $cur_game, $bonus_bet){
    if($bonus_bet == 3)
      return ($amount * 0.15) * $cur_game['op_fee'];
    return $amount * $cur_game['op_fee'];
  }

  function parseJackpots() {

        $ret = array();
        $urls =  $this->getAllJurSettingsByKey('jp_url');

        foreach ($urls as $jur => $url) {
            $xml = phive()->get($url, 10);
            if ($xml) {
               try {
                    $doc = new SimpleXMLElement($xml);
                }catch (Exception $e){
                    phive('Logger')->getLogger('game_providers')->error('microjpcron', [
                        'brand' => 'BSG',
                        'error'=>$e->getMessage()]
                    );
                    return [];
                }
                foreach ($doc->xpath('//jackpotGame') as $jp) {
                    $jp_id = 'bsg' . $jp->gameId;
                    $ret[] = array(
                        "jp_value" => trim($jp->jackpotAmount) * 100,
                        "jp_id" => trim($jp_id) . '_' . trim($jp->coin),
                        "module_id" => trim($jp_id),
                        "network" => 'bsg',
                        "local" => 1,
                        "currency" => trim($jp->currencyCode),
                        "jp_name" => trim($jp->gameName) . ' (' . trim($jp->coin) . ')',
                        "game_id" => trim($jp->gameId),
                        "jurisdiction" => $jur
                    );
                }
            }
        }

        return $ret ?? [];
  }

  function getMobileDeviceInfo(){
    if(phive()->deviceType() == 'android')
      return array('android', 2);
    return array('html5', 1);
  }

  function loadToken(){
      $this->token = array();
      $this->new_token = array();
      foreach(array('userId' => 'user_id', 'gameId' => 'game_ref') as $from => $to){
          $this->token[$to] 	= $_REQUEST[$from];
          $this->new_token[$to] = $_REQUEST[$from];
          $this->$to            = $_REQUEST[$from];
    }
  }

  //RewriteRule ^diamondbet/soap/(.*)\.do$ diamondbet/soap/bsg.php?action=$1&%{QUERY_STRING}
  function executeRequest($func){
    if($func == 'betresult')
      $func = empty($_REQUEST['bet']) ? 'win' : 'bet';

    if($this->debug === true)
      phive()->dumpTbl('bsg_req_url', $_SERVER['REQUEST_URI']);
    $this->dumpTst("bsg_{$func}", $_REQUEST, $_REQUEST['userId']);

    if(!$this->checkHash($func))
      $res = 500;
    else{
      $this->loadToken();
      $res = $this->$func();
    }

    return $this->buildResponse($func, $res);
  }

  function buildResponse($func, $res_params){
    $req_params = $this->getReq($func);
    ob_start();
    ?>
    <EXTSYSTEM>
      <REQUEST>
        <?php foreach($req_params as $param => $value): ?>
          <<?php echo $param ?>><?php echo $value ?></<?php echo $param ?>>
        <?php endforeach ?>
        <HASH><?php echo $this->getHash($func) ?></HASH>
      </REQUEST>
      <TIME><?php echo date('d M y H:i:s') ?></TIME>
      <RESPONSE>
        <?php if(is_numeric($res_params)): ?>
          <RESULT>ERROR</RESULT>
          <CODE><?php echo $res_params ?></CODE>
        <?php else: ?>
          <RESULT>OK</RESULT>
          <?php foreach($res_params as $param => $value): ?>
            <<?php echo $param ?>><?php echo $value ?></<?php echo $param ?>>
          <?php endforeach ?>
        <?php endif; ?>
      </RESPONSE>
    </EXTSYSTEM>
    <?php
    $xml = ob_get_contents();
    ob_end_clean();
    if($this->debug === true)
      phive()->dumpTbl("bsg_answer_$func", $xml);
    return $xml;
  }

  function checkHash($func){
    return $this->checkGetHash($func, true);
  }

  function getHash($func){
    return $this->checkGetHash($func, false);
  }

  function checkGetHash($func, $check = true){
    $arr = is_array($func) ? $func : $this->getReq($func);
    $str = implode('', $arr).$this->getLicSetting('pass_key');
    if($check)
      return md5($str) == $_REQUEST['hash'];
    else
      return md5($str);
  }

    function getSessToken($uid){
        $uid = uid($uid);
        return "bsg-{$uid}";
    }

    function getSessKey($uid){
        return mKey($uid, $this->getSessToken($uid));
    }

    function setSessData($uid, $data){
        $uid = uid($uid);
        $this->bsg_session_data = $data;
        return phMsetArr($this->getSessKey($uid), $data);
    }

    function getSessData($uid){
        if(!empty($this->bsg_session_data)){
            return $this->bsg_session_data;
        }
        $uid = uid($uid);
        $u_obj = cu($uid);
        $this->bsg_session_data = phMgetArr($this->getSessKey($uid));
        
        // We're looking at a player requiring ext game session logic and the session_entry variable has not been set so we do it.
        if($this->useExternalSession($u_obj) && empty($this->session_entry)){
            $this->setSessionById($uid, $this->bsg_session_data['ext_session_id']);
        }
        return $this->bsg_session_data;
    }
    
  function getDepUrl($gid, $lang, $game = null,  $show_demo = false){
        $game 	= phive("MicroGames")->getByGameId($gid);
        $ext_game_name_not_overriden = $game['ext_game_name'];
        // Here we override the external launch and game ids if we have a record in the game_country_overrides table.
        $game   = phive('MicroGames')->overrideGame(null, $game);
        $gid	= $game['ext_game_name'];
        $lang 	= phive('MicroGames')->getGameLang($game['game_id'], $lang);
        $base_url = $this->getLicSetting('base_url');
        $bank_id = $this->getLicSetting('bank_id');
        
        if(isLogged()){
            $user = cu();
            $player_login_time = strtotime($user->getCurrentSession()['created_at']); // TODO check if we can replace with (int)cu()->getSessionLength('s', 2) /Paolo
            // set RC interval on BSG to show RC pop 
            $interval = phive('Casino')->startAndGetRealityInterval($user->userId, $game['ext_game_name']);
            if (!empty($interval)){
            $this->setRealityCheckInterval($user->userId, $interval * 60); // change to seconds
                // get the login time in unix timestamp
                if(!empty($this->getLicSetting('player_login_time'))) {
                    $extra = ['playerLoginTime' => $player_login_time * 1000];
                }
            }

            $this->setSessData($_SESSION['mg_id'], ['original_game_ref' => $ext_game_name_not_overriden, 'device_type' => 'flash']);
            $_SESSION['bsg_token'] = mKey($_SESSION['mg_id'], phive()->uuid());
            phMset($_SESSION['bsg_token'], empty($_SESSION['token_uid']) ? $_SESSION['mg_id'] : $_SESSION['token_uid']);
            $url = $base_url.str_replace(array('%1', '%2', '%3', '%4'), array($bank_id, $gid, $_SESSION['bsg_token'], $lang), $this->getLicSetting('real_play_url'));
            $url = (empty($extra))? $url : $url . '&playerLoginTime=' . $extra['playerLoginTime'];
        } else {
            $url = $base_url.str_replace(array('%1', '%2', '%3'), array($bank_id, $gid, $lang), $this->getLicSetting('demo_play_url'));
        }
        $this->dumpTst('bsg_game_launch', ['url' => $url, 'game_ref' => $gid, 'original_game_ref' => $ext_game_name_not_overriden, 'device_type' => 'flash'], $_SESSION['mg_id']);
        return $url;
  }

  function getMobilePlayUrl($gref, $lang, $lobby_url, $g, $args = [], $show_demo = false){
        $mg			= phive('MicroGames');
        $ext_game_name_not_overriden = $g['ext_game_name'];
        list($dtype, $device_type_num) = $this->getMobileDeviceInfo();
        if($dtype == 'android'){
          $new_g = $this->getAndroidGame($g['game_id']);
          if(!empty($new_g))
            $g = $new_g;
        }
        $g =  phive('MicroGames')->overrideGame(null, $g);
        $gref	= $g['ext_game_name'];
        $ss 	= $this->allSettings();
        $lang 	= $mg->getGameLang($g['game_id'], $lang, $g['device_type']);
        $extra = '';
        $base_url = $this->getLicSetting('base_url');
        $bank_id = $this->getLicSetting('bank_id');
    
        if(isLogged()){
            $user = cu();
            $player_login_time = strtotime($user->getCurrentSession()['created_at']); // TODO check if we can replace with (int)cu()->getSessionLength('s', 2) /Paolo
            // set RC interval on BSG to show RC pop
            $interval = phive('Casino')->startAndGetRealityInterval($user->userId, $ext_game_name_not_overriden);
            if (!empty($interval)){
                $this->setRealityCheckInterval($user->userId, $interval * 60); //change to seconds
                // get the login time in unix timestamp
                if(!empty($this->getLicSetting('player_login_time'))) {
                    $extra = ['playerLoginTime' => $player_login_time * 1000];
                }
            }

            $this->setSessData($_SESSION['mg_id'], ['original_game_ref' => $ext_game_name_not_overriden, 'device_type' => $dtype]);
            $_SESSION['bsg_token'] = mKey($_SESSION['mg_id'], phive()->uuid());
            phMset($_SESSION['bsg_token'], empty($_SESSION['token_uid']) ? $_SESSION['mg_id'] : $_SESSION['token_uid']);
            $bank_url 	= $this->getCashierUrl(true, $lang, 'mobile');
            $launch     = $this->wrapUrlInJsForRedirect($this->getLobbyUrl(true, $lang, 'mobile'));
            $url = $base_url.str_replace(array('%1', '%2', '%3', '%4', '%5', '%6'), array($bank_id, $gref, $_SESSION['bsg_token'], $lang, $bank_url, $launch), $this->getLicSetting('mobile_real_play_url'));

            $url = (empty($extra))? $url : $url . '&playerLoginTime=' . $extra['playerLoginTime'];
        }else{
            $bank_url 	= $this->getCashierUrl(true, $lang, 'mobile');
            $launch 		=  $this->wrapUrlInJsForRedirect($this->getLobbyUrl(true, $lang, 'mobile'));
            $url = $base_url.str_replace(array('%1', '%2', '%3', '%4', '%5'), array($bank_id, $gref, $lang, $bank_url, $launch), $this->getLicSetting('mobile_demo_play_url'));
        }
        $this->dumpTst('bsg_game_launch', ['url' => $url, 'game_ref' => $gref, 'original_game_ref' => $ext_game_name_not_overriden, 'device_type' => $dtype], $_SESSION['mg_id']);
        return $url;
  }

  function getGameByRef($gid = '', $user = null){
    $gid = empty($gid) ? $_REQUEST['gameId'] : $gid;
    $game = phive('MicroGames')->getByGameRef($gid, null, $user);

    if(empty($game))
      $game = phive('MicroGames')->getByGameRef('bsg_system');
    return $game;
  }

  function getUsr(&$req = null){
    $user = cu($this->getUsrId($_REQUEST['userId']));
    if(!is_object($user))
      return 310;
    else
      $this->user = $user;
    $GLOBALS['mg_username'] = $user->data['username'];
    return $user->data;
  }

    function _getBalance($user, $gid = ''){
        if(empty($user))
            $user = $this->getUsr();
        if(empty($this->t_entry)){
            if ($this->hasSessionBalance()) {
                return $this->getSessionBalance($this->user);
            }
            
            $balance = $this->user->getBalance();
            
            $g_env = $this->getSessData($this->user);
            $gid = $g_env['original_game_ref'] ?? '';
            $gref = empty($gid) ? $_REQUEST['gameId'] : $gid;
            $bonus_balances = phive('Bonuses')->getBalanceByRef($gref, $user['id']);
            return $this->lgaMobileBalance($user, $gref, $balance + $bonus_balances);
        }else
            return $this->tEntryBalance();
    }

  function getReq($func){
    if($func == 'bet' || $func == 'win'){
      return array(
        'USERID' 		=> $this->retUid($_REQUEST['userId']),
        strtoupper($func)	=> $_REQUEST[$func],
        'ISROUNDFINISHED' 	=> $_REQUEST['isRoundFinished'],
        'ROUNDID'		=> $_REQUEST['roundId'],
        'GAMEID'		=> $_REQUEST['gameId']
      );
    }

    switch($func){
      case 'authenticate':
        return array('TOKEN' 	=> $_REQUEST['token']);
        break;
      case 'refundBet':
        return array(
          'USERID' 		=> $this->retUid($_REQUEST['userId']),
          'CASINOTRANSACTIONID'	=> $_REQUEST['casinoTransactionId']
        );
        break;
      case 'getBalance':
        return array(
          'USERID' 		=> $this->retUid($_REQUEST['userId'])
        );
        break;
      case 'accountinfo':
        return array(
          'USERID' 		=> $this->retUid($_REQUEST['userId'])
        );
        break;
      case 'bonuswin':
        return array(
          'USERID' 		=> $this->retUid($_REQUEST['userId']),
          'BONUSID' 		=> $_REQUEST['bonusId'],
          'AMOUNT'		=> $_REQUEST['amount']
        );
        break;
    }
  }

  function authenticate(){
    $uid = $this->getUsrId(phMget($_REQUEST['token']));
    if(empty($uid))
      return 400;
    $this->user = cu($uid);
    $user = $this->user->data;
    if(empty($user))
        return 310;

      if($this->useExternalSession($this->user)){
          $sess_data = $this->getSessData($this->user);
          $game_data = phive('MicroGames')->getByGameRef($sess_data['original_game_ref'], $sess_data['device_type'] ?? 'flash', $this->user->getId());
          $sess_data['ext_session_id'] = lic('initGameSessionWithBalance', [$this->user, $this->getSessKey($this->user), $game_data], $this->user);
          if (!empty($sess_data['ext_session_id'])) {
              phive()->dumpTbl('bsg-auth', [$sess_data, $game_data]);
              $this->setSessData($this->user, $sess_data);
              $this->setSessionById($this->user->getId(), $sess_data['ext_session_id']);
          }
      }
      
    $balance 	= $this->_getBalance($user);
    return array(
        'USERID' 	=> $this->retUid($uid),
        'USERNAME' 	=> $user['username'],
        'FIRSTNAME'     => $user['firstname'],
        'LASTNAME' 	=> $user['lastname'],
        'EMAIL' 	=> $user['email'],
        'CURRENCY' 	=> empty($this->t_entry) ? $user['currency'] : 'EUR',
        'BALANCE' 	=> $balance
    );
  }

    function retUid($uid){
      if(strpos($uid, 'e') !== false)
        return $uid;
      return empty($this->t_entry) ? $uid : "{$uid}e{$this->t_eid}";
    }

  //$bsg_id without the bsg suffix
  function betResultGetUser($key, $amount = '', $bsg_id = ''){
    $user = $this->getUsr();
    if(is_numeric($user))
      return $user;
    if(empty($amount))
      list($amount, $bsg_id) = explode('|', $_REQUEST[$key]);
    $bsg_id = "bsg{$bsg_id}";
    //$this->setParams($amount, $bsg_id, $_REQUEST['roundId']);
    return array($user, $amount, $bsg_id);
  }

  //http://bsgsystem.com/betresult.do?userId=12345&bet=123407|12344546&roundId=12345&gameId=12&isRoundFinished=true&hash=8cb54b1924dbbd626a3b079a47527d17
  function bet(){
    $start = $this->betResultGetUser('bet');
    if(is_numeric($start))
      return $start;
    list($user, $tmp_bet_amount, $bsg_id) = $start;
    $result = $this->getBetByMgId($bsg_id);
    if(!empty($result)){
      $balance 	= $result['balance'];
      $extid 		= $result['id'];
    }else{
      $balance 	= $this->_getBalance($user);

      $g_env = $this->getSessData($user);
      $device_type = $g_env['device_type'] ?? null;
      $cur_game = phive('MicroGames')->getOriginalGame($_REQUEST['gameId'], $user, null, $device_type);
      if (empty($cur_game)) {
          $cur_game = phive('MicroGames')->getByGameRef('bsg_system');
      }
      if(empty($cur_game))
        return 399;
      $this->game = $cur_game;

      $bet_amount = $tmp_bet_amount;
      $jp_contrib = round($bet_amount * $cur_game['jackpot_contrib']);
      if(empty($this->t_entry))
        $balance = $this->lgaMobileBalance($user, $cur_game['ext_game_name'], $balance, $cur_game['device_type'], $bet_amount);
      if($balance < $tmp_bet_amount)
        return 300;
      $GLOBALS['mg_id'] = $bsg_id;

        $balance = $this->playChgBalance($user, -$bet_amount, $_REQUEST['roundId'], 1);
        if($balance === false)
            return 399;
        $bonus_bet = empty($this->bonus_bet) ? 0 : 1;

        $extid = $this->insertBet($user, $cur_game, $_REQUEST['roundId'], $bsg_id, $bet_amount, $jp_contrib, $bonus_bet, $balance);
        if(!$extid)
          return 399;

      $balance 		= $this->betHandleBonuses($user, $cur_game, $bet_amount, $balance, $bonus_bet, $_REQUEST['roundId'], $bsg_id);
    }
    return $this->betWinReturn($extid, $balance);
  }

  function betWinReturn($extid, $balance){
    return array(
      'EXTSYSTEMTRANSACTIONID'	=> $extid,
      'BALANCE' 					=> $balance
    );
  }

  //http://bsgsystem.com/betresult.do?userId=12345&bet=123407|12344546&roundId=12345&gameId=12&isRoundFinished=true&hash=8cb54b1924dbbd626a3b079a47527d17
  function win(){
    $this->game_action = 'win';
    if($this->debug === true)
      phive()->dumpTbl('bsg_win', $_REQUEST);
    $start = $this->betResultGetUser('win');
    if(is_numeric($start))
      return $start;
    list($user, $amount, $bsg_id) = $start;
    $GLOBALS['mg_id'] 		  = $bsg_id;
    $result 			  = $this->getBetByMgId($bsg_id, 'wins');
    $award_type 		  = 2;
    if(empty($result)){
      $g_env = $this->getSessData($this->user);
      $device_type = $g_env['device_type'] ?? null;
      $cur_game = phive('MicroGames')->getOriginalGame($_REQUEST['gameId'], $user, null, $device_type);
      if (empty($cur_game)) {
          $cur_game = phive('MicroGames')->getByGameRef('bsg_system');
      }
      if (empty($cur_game)) {
          return 399;
      }
      $this->game = $cur_game;

      $bonus_bet 		  = empty($this->bonus_bet) ? 0 : 1;
      $cur_game['ext_game_name']  = empty($cur_game['ext_game_name']) ? $_REQUEST['gameId'] : $cur_game['ext_game_name'];
      if(!empty($_REQUEST['negativeBet']))
        $amount += $_REQUEST['negativeBet'];
      if(!empty($amount)){
          $balance = $this->_getBalance($user);
          $extid 	= $this->insertWin($user, $cur_game, $balance, $_REQUEST['roundId'], $amount, $bonus_bet, $bsg_id, $award_type);
          if(!$extid)
            return 399;

          $balance 	= $this->playChgBalance($user, $amount, $_REQUEST['roundId'], $award_type);
          if ($balance === false)
            return 399;
      }
      if(empty($extid)){
        $balance	= $this->_getBalance($user);
        $extid 		= uniqid();
      }else
        $balance 	= $this->handlePriorFail($user, $_REQUEST['roundId'], $balance, $amount);
    }else{
      $balance = $this->_getBalance($user);
      $extid 		= $result['id'];
    }
    return $this->betWinReturn($extid, $balance);
  }

  //http://bsgsystem.com/refundBet.do?userId=12345&casinoTransactionId=12344546&hash=8cb54b1924dbbd626a3b079a47527d17
  function refundBet(){
    $user = $this->getUsr();
    if(is_numeric($user))
      return $user;
    $bsg_id = "bsg{$_REQUEST['casinoTransactionId']}";
    $result = $this->getBetByMgId($bsg_id, 'bets', 'mg_id', $user['id']);
    if(empty($result)){
        $result = $this->getBetByMgId($bsg_id.'ref', 'bets', 'mg_id', $user['id']); // check if already refunded
        if (empty($result)) {
            return 302;
        }
        return array('EXTSYSTEMTRANSACTIONID' => $result['id']);
    }
    $this->new_token['game_ref'] = $result['game_ref'];
    $amount = $result['amount'] + $result['jp_contrib'];
    $balance = $this->playChgBalance($user, $amount, $result['trans_id'], 7);
    if($balance === false)
      return 399;
    $this->doRollbackUpdate($bsg_id, 'bets', $balance, $amount);
    return array('EXTSYSTEMTRANSACTIONID' => $result['id']);
  }

  //http://bsgsystem.com/accountinfo.do?userId=432&hash=1e3b0ae551b1dfdc48137bc50ad26d1c
  function accountinfo(){
    $user = $this->getUsr();
    if(is_numeric($user))
      return $user;

    return array(
      'USERNAME'  => $user['username'],
      'FIRSTNAME' => $user['firstname'],
      'LASTNAME'  => $user['lastname'],
      'EMAIL' 	  => $user['email'],
      'CURRENCY'  => !empty($this->t_entry) ? 'EUR' : $user['currency']
    );
  }

  //Win during free spins
    //http://bsgsystem.com/bonuswin.do?userId=123423&bonusId=32132&amount=7820&transactionId=7820&hash=1e3b0ae551b1dfdc48137bc50ad26d1c
    //TODO do we get this in case of zero win? If we get zero wins we can refactor BSG to make it work like Netent with all FRBs active
    //until the win comes in.
  function bonuswin(){
    $start = $this->betResultGetUser('win', $_REQUEST['amount'], $_REQUEST['transactionId']);
    if(is_numeric($start))
      return $start;
    list($user, $amount, $bsg_id) = $start;
    $result 			  = $this->getBetByMgId($bsg_id, 'wins');
    if(!empty($result)) {
      $balance = $this->_getBalance($user);
      return array('BALANCE' => $balance);
    }
    $e = phive("Bonuses")->getEntryByExtId($_REQUEST['bonusId'], $user['id']);
    $this->handleFspinWin($e, $amount, $user, $bsg_id);

    $g_env = $this->getSessData($user);
    $cur_game = $this->getGameByRef($g_env['original_game_ref']);
    $cur_game = phive('MicroGames')->overrideGame($user, $cur_game);
    //$cur_game['op_fee']           = 0;
    $this->new_token['game_ref']  = $cur_game['ext_game_name'];
    $balance                      = $this->_getBalance($user);
    $result                       = $this->insertWin($user, $cur_game, $balance, '', $_REQUEST['amount'], 3, $bsg_id, 2);
    if(!$result)
      return 399;
    return array('BALANCE' => $balance);
  }

  function dmy($date){
    return date('d.m.Y', strtotime($date));
  }

    function assoc2Get($arr, $trim = true, $urlencode = false){
        $str = '';
        foreach($arr as $key => $val)
            $str .= "&$key=".($urlencode ? urlencode($val) : $val);
        return $trim ? trim($str, '&') : $str;
    }
    
  function getBsgUrl($ps, $func){
    return $this->getLicSetting('base_url').$func.'?'.$this->assoc2Get($ps);
  }

  function getRes($xml, $field){

    try{
      $r = new SimpleXMLElement($xml);
    }catch(Exception $e){
      return false;
    }

    $res = $r->xpath("//$field")[0];
    return $res[0];
  }

    function callBsg($ps, $func, $ret = 'bool'){
        $xml = phive()->get($this->getBsgUrl($ps, $func), 5, '', "bsgurl_$func");
        if(!$xml){
            phive()->dumpTbl("bsg_$func-res", ['args' => $ps, 'func' => $func]);
        }
        //check hash before to lower if needed
        $xml = strtolower($xml);
        $res = $this->getRes($xml, 'result');
        if($res != 'ok'){
            phive()->dumpTbl("bsg_$func-res", $res);
            return $this->getRes($xml, 'code');
        }
        return $ret == 'xml'? $xml : true;
    }

    function frbStatus($entry){
      $bes = $this->getFRBonusInfo($entry['user_id']);
      if(!empty($bes)){
        foreach($bes as $be){
          $e = phive("SQL")->sh($entry, 'user_id', 'bonus_entries')->loadAssoc('', 'bonus_entries', array('ext_id' => $be['bonusid']));
          if(empty($e))
            return 'activate';
        }
      }
      return false;
    }

  //http://lobby.videoslots.discreetgaming.com/frbaward.do?bankId=[BANKID]&userId=12346789&rounds=5&games=210|221&extBonusId=001&hash=12345
  function awardFRBonus($uid, $gids, $rounds, $bonus_name, $entry){
    if($this->getSetting('test') === true)
      return 1000;

    $ps = array(
      'userId' 		=> $uid,
      'bankId' 		=> $this->getLicSetting('bank_id'),
      'rounds' 		=> $rounds,
      'games'		=> trim($gids),
      'description' 	=> $bonus_name,
      'extBonusId' 	=> $entry['id']
    );

    if($this->debug === true)
      phive()->dumpTbl('bsg_award_frb', $ps, $uid);

    $ps['hash']           = $this->getHash($ps);
    $ps['expirationTime'] = $this->dmy($entry['end_time']);
    $ps['description']    = urlencode($ps['description']);
    $res                  = $this->callBsg($ps, 'frbaward.do', 'xml');
    if(is_numeric($res) || empty($res))
      return false;
    return $this->getRes($res, 'bonusid');
  }

  //http://lobby.videoslots.discreetgaming.com/frbcheck.do?bankId=[BANKID]&extBonusId=001&hash=1
  //returns false if the bonus doesnt exist, the bsg bonus id otherwise
  function checkFRBonus($entry){
    $entry = is_numeric($entry) ? phive("Bonuses")->getBonusEntry($entry) : $entry;
    $ps = array(
      'extBonusId' 	=> $entry['id'],
      'bankId' 		=> $this->getLicSetting('bank_id')
    );
    $ps['hash'] = $this->getHash($ps);
    $res 		= $this->callBsg($ps, 'frbcheck.do', 'xml');
    if(is_numeric($res) || empty($res))
      return false;
    return $this->getRes($res, 'bonusid');
  }

  //http://lobby.videoslots.discreetgaming.com/frbcancel.do?bankId=[BANKID]&bonusId=50727361&hash=122345
  function cancelFRBonus($uid, $bsg_id){
    if($this->getFRBonusSpinsLeft($uid, $bsg_id) == 0)
      return false;
    $ps           = array('bonusId' => $bsg_id);
    $ps['hash']   = $this->getHash($ps);
    $ps['bankId'] = $this->getLicSetting('bank_id');
    return $this->callBsg($ps, 'frbcancel.do');
  }

  function cancelAllFRBonuses($uid){
    $res = phive('Bsg')->getFRBonusInfo($uid);
    if(!empty($res)){
      foreach($res as $eb)
        $this->cancelFRBonus($uid, $eb['bonusid']);
    }
  }

  function getFRBonusSpinsLeft($uid, $bsg_id){

    if($this->getSetting('test') === true)
      return 1;

    foreach($this->getFRBonusInfo($uid) as $b){
      if($b['bonusid'] == $bsg_id)
        return (int)$b['roundsleft'];
    }
    return 0;
  }

  //http://lobby.videoslots.discreetgaming.com/frbinfo.do?bankId=[BANKID]&userId=12346789&hash=12345
  function getFRBonusInfo($uid){
    $ps = array(
      'userId' 	=> $uid,
      'bankId' 	=> $this->getLicSetting('bank_id')
    );
    $ps['hash'] = $this->getHash($ps);
    $res        = $this->callBsg($ps, 'frbinfo.do', 'xml');
    if(is_numeric($res) || empty($res))
      return false;
    try{
      $r = new SimpleXMLElement($res);
    }
    catch(Exception $e){
      error_log("Fatal error: content from bsg: $res, error: " . $e);
      return false;
    }
    $res 	= $r->xpath("//bonus");
    $ret 	= array();
    foreach($res as $val)
      $ret[] = (array)$val;
    return $ret;
  }

    /**
     * Reads an external jackpots feed and updates db.micro_games and db.micro_jps.
     * This method is not called from Phive or Diamondbet. It cannot be called from any games department report either
     * because it's missing some required values for db.micro_jps and therefore fails.
     * We will keep the method for the moment in case it is later decided to use it and insert the required values.
     *
     * See http://lobby.videoslots.discreetgaming.com/jackpots/jackpots_382.xml for an example
     *
     * Example of jackpot feed
    <jackpots>
        <jackpotGame>
            <gameId>507</gameId>
            <gameName>It Came From Venus JP Plus Windows Phone</gameName>
            <coin>0.25</coin>
            <currencyCode>AUD</currencyCode>
            <jackpotAmount>110.78</jackpotAmount>
        </jackpotGame>
        <jackpotGame>
            <gameId>672</gameId>
            <gameName>Charms And Clovers Android</gameName>
            <coin>0.50</coin>
            <currencyCode>CAD</currencyCode>
            <jackpotAmount>121.65</jackpotAmount>
        </jackpotGame>
    </jackpots>
     */
    private function importJps()
    {
        $sql = phive("SQL");
        $sql->query("DELETE FROM micro_jps WHERE network = 'bsg'");

        $xml = phive()->get($this->getLicSetting('base_url') . "jackpots/jackpots_" . $this->getLicSetting('bank_id') . ".xml");
        $jackpots = new SimpleXMLElement($xml);

        foreach ($jackpots->jackpotGame as $jackpot_game) {
            $game_ref = (string)$jackpot_game->gameId;
            if (!$game_ref) {
                continue;
            }
            $sql->updateArray('micro_games', ['tag' => 'videoslots_jackpot', 'jackpot_contrib' => '0.01'], ['ext_game_name' => $game_ref]);

            $game_name = (string)$jackpot_game->gameName;
            if (!$game_name) {
                continue;
            }

            if ((strpos($game_name, 'Mobile') === false) && (strpos($game_name, 'Android') === false)) {
                $insert = [
                    'jp_value' => (int)bcmul((string)$jackpot_game->jackpotAmount, 100),
                    'jp_id' => "bsg{$game_ref}",
                    'jp_name' => $game_name,
                    'network' => 'bsg',
                    'local' => 1,
                    'ext_game_name' => $game_ref,
                ];
                $sql->insertArray('micro_jps', $insert);
            }
        }
    }
  
  // Set Reality Check Timeout for user
  function setRealityCheckInterval($uid, $interval){
          
            $ps = array(
                'userId' 		     => $uid,
                'bankId' 		     => $this->getLicSetting('bank_id'),
                'intervalInSeconds'  => $interval,
            );
          
            if($this->debug === true)
                phive()->dumpTbl('setRealityCheckInterval', $ps, $uid);
            
                $ps['hash']           = $this->getHash($ps);
                $res                  = $this->callBsg($ps, 'setRealityCheckInterval.do', 'xml');
                if(is_numeric($res) || empty($res))
                    return false;
                return $this->getRes($res, 'id');
  }
  
  // Get Reality Check Timeout for user
  function getRealityCheckInterval($uid, $interval){
          
          $ps = array(
              'userId' 		     => $uid,
              'bankId' 		     => $this->getLicSetting('bank_id'),
          );
          
          if($this->debug === true)
              phive()->dumpTbl('getRealityCheckInterval', $ps, $uid);
          
              $ps['hash']           = $this->getHash($ps);
              $res                  = $this->callBsg($ps, 'getRealityCheckInterval.do', 'xml');
              if(is_numeric($res) || empty($res))
                  return false;
                  return $this->getRes($res, 'id');
  }

    /**
     * Returns the active Android game.
     * Some games do not have the 'Android' suffix and others have 4 additional inactive rows for Swedish overrides.
     *
     * @param string $game_id
     * @return array|null
     */
    private function getAndroidGame(string $game_id): ?array
    {
        $gid = preg_replace('/mobile$/i', '', $game_id);
        $sql = sprintf(
            "SELECT * FROM micro_games WHERE active = 1 AND device_type = 'android' AND game_id IN (%s, %s)",
            phive('SQL')->escape($gid),
            phive('SQL')->escape($gid . 'Android')
        );
        return phive('SQL')->loadAssoc($sql);
    }
}
