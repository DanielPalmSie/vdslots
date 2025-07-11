<?php
require_once __DIR__ . '/QuickFire.php';
class Wi extends QuickFire{

  function __construct(){
    parent::__construct();
  }

    //TODO test with SEK player
    function awardFRBonus($uid, $gids, $rounds, $bonus_name, $entry) {
        if($this->getSetting('no-out') === true) {
            phive()->dumpTbl('wi_test_frb_awarded', $entry);
            return true;
        }

        $bonus = phive('Bonuses')->getBonus($entry['bonus_id']);

        $ss = $this->allSettings();

        $body = array(
            "partnerAccountIds"    => [$uid],
            'balanceRequired'      => false,
            'partnerCode'          => $ss['partner_code'],
            'maxPlayers'           => 1,
            'totalFreeRounds'      => (int)$rounds,
            'freeRoundRef'         => $entry['id'],
            'expirationDate'       => strtotime($entry['end_time'] . ' 23:59:58') * 1000,
            'startDate'            => time() * 1000,
            'gameBetPerLine'       => [$gids => (int)$bonus['frb_denomination']]
        );

        $gids = $this->stripWi($gids);
        $url = $ss['frb_base_url']."/freerounds/players";

        $auth = base64_encode("{$ss['frb_login']}:{$ss['frb_pwd']}");
        $headers = "Authorization: Basic $auth\r\nAccept: application/vnd.com.sginteractive.backoffice-v1+json\r\n";

        $res = phive()->post($url, json_encode($body), 'application/json', $headers, 'wi-frb');
        if($res === false)
            return false;
        return 'ok';
    }

  function activateFreeSpin(&$entry, $na, $bonus) {
    $entry['status'] = 'approved';
  }

  function actionMap($action){
    $map = array(
      'authenticate'         => 'playerAuthentication',
      'getBalance'           => 'balance',
      'transferToGame'       => 'transferToGame',
      'transferFromGame'     => 'transferFromGame',
      'cancelTransferToGame' => 'cancelTransferToGame',
    );
    return $map[$action];
  }

  function parseXml($action, $xml){
    $xml_action = $this->actionMap($action);
    try{
      $res = new SimpleXMLElement($xml);
      return (array)$res;
    }catch (Exception $e){ }
  }

  function buildResponse($res){
    if(is_string($res)){
      $alias = $res;
      $res = array();
      $res['result'] = 'FAILURE';
      if(in_array($alias, array('limit.reached', 'not.enough.money')))
        $res['reconcile'] = 'FALSE';
      if($alias == 'limit.reached')
        $res['requiredUserAction'] = 'OK';
      $res['message'] = t($alias, empty($this->ud) ? 'en' : $this->ud['preferred_lang']);
    }else{
      $res['result'] = 'SUCCESS';
      $res['replacementTicket'] = $this->new_ticket;
    }

    $xml_action = $this->actionMap($this->action).'Response';
    list($xml_type, $xml_ns) = $this->getTypeNs($this->action);

    foreach($res as $key => $val)
      $xml .= "<$key>$val</$key>";

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><'.$xml_ns.':'.$xml_action.' xmlns:ns2="http://williamsinteractive.com/integration/vanilla/api/common" xmlns:ns3="http://williamsinteractive.com/integration/vanilla/api/player" xmlns:ns4="http://williamsinteractive.com/integration/vanilla/api/transaction">'.$xml.'</'.$xml_ns.':'.$xml_action.'>';

    if($this->test)
      phive()->dumpTbl('wi-xml-out', $xml);

    return $xml;
  }

  function isTransaction($action){
    return in_array($action, array('transferToGame', 'transferFromGame', 'cancelTransferToGame'));
  }

  function getTypeNs($action){
    $xml_type = in_array($action, array('authenticate', 'getBalance')) ? 'player' : 'transaction';
    $xml_ns = $xml_type === 'player' ? 'ns3' : 'ns4';
    return array($xml_type, $xml_ns);
  }

  function execute($action, $xml){
    header('Content-Type: application/xml');
    $this->test = $this->getSetting('test');
    $this->action = $action;
    if($this->test)
      phive()->dumpTbl('wi-xml-in', $xml);
    $req = $this->parseXml($action, $xml);
    if($this->test)
      phive()->dumpTbl('wi-req-in', $req);
    $res = $this->setData($req);
    $this->new_ticket = $this->getTicket($this->ud);
    if($this->test === true && $req['ticket'] != 'SUCCESS' && in_array($action, array('getBalance', 'transferToGame', 'authenticate')))
      $res = 'invalid.ticket';
    else if($this->test !== true && in_array($action, array('getBalance', 'transferToGame', 'authenticate')) && $req['ticket'] != $this->new_ticket)
      $res = 'invalid.ticket';

    if($this->isTransaction($action) && (empty($req['gameRoundId']) || empty($req['transactionId'])))
      $res = 'round.or.trid.missing';

    if($this->isTransaction($action) && $this->ud['currency'] != $req['currency'])
      $res = 'wrong.currency';

    if(!empty($req['amount']) && (int)$req['amount'] < 0)
      $res = 'negative.amount';

    return $this->buildResponse(empty($res) ? $this->$action($req) : $res);
  }

    function setData($req){
        $this->ud = ud($req['accountRef']);
        if(empty($this->ud))
            return 'no.user';
        if(empty($req['gameCode']))
            return 'no.game';
        $this->uid = $this->ud['id'];
        $this->device_type = strtolower($req['context']) != 'desktop' ? 1 : 0;
        $this->g = phive('MicroGames')->getByGameRef('wi'.$req['gameCode'], $this->device_type);
        $this->tid = 'wi'.$req['transactionId'];
        if(empty($this->g)){
            $this->g = phive('MicroGames')->getByGameRef('widefault', $this->device_type);
            phive()->dumpTbl('wi-nogame', $req, $this->ud['id']);
        }
        $this->new_token['game_ref'] = $this->g['ext_game_name'];
        return '';
    }

  function getBonusBalance(){
    return phive('Bonuses')->getBalanceByRef($this->g['ext_game_name'], $this->ud['id']);
  }

  function _getBalance($fresh = false){
    $balance = $fresh === true ? cuPlAttr('cash_balance', $this->ud['id']) : $this->ud['cash_balance'];
    return $balance + $this->getBonusBalance();
  }

  function setTicket($ud, $ticket = null){
    if($this->getSetting('test') === true)
      $ticket = 'SUCCESS';
    else
      $ticket = empty($ticket) ? $this->mkTicket($ud) : $ticket;
    phMset("{$ud['id']}-wi-ticket", $ticket);
    return $ticket;
  }

  function getTicket($ud){
    return phMget("{$ud['id']}-wi-ticket", 7200);
  }

  function mkTicket($ud){
    if($this->getSetting('test') === true)
      return 'SUCCESS';
    return phive()->uuid()."-".$ud['id'];
  }

  function authenticate($req){
    $locale = phive('Localizer')->getLocale($this->ud['preferred_lang']);
    $this->new_ticket = $this->test ? 'SUCCESS' : $this->mkTicket($this->ud);
    $this->setTicket($this->ud, $this->new_ticket);
    return array(
      'accountRef'        => $this->ud['id'],
      'currency'          => $this->ud['currency'],
      'language'          => $locale,
      'balance'           => $this->_getBalance(),
      'userName'          => $this->ud['username']
    );
  }

  function getBalance($req){
    return array('balance' => $this->_getBalance());
  }

  function transferToGame($req){
    $amount = (int)$req['amount'];
    $balance = $this->_getBalance();

    $result = $this->getBetByMgId($this->tid);
    if(!empty($result))
      return array('partnerTransactionRef' => $result['id'], 'balance' => $balance);

    if($balance < $amount)
      return 'not.enough.money';

    if (!empty($amount)) {

      $balance    = $this->lgaMobileBalance($this->ud, $this->g['ext_game_name'], $balance, $this->g['device_type'], $amount);
      if($balance < $amount)
	return 'limit.reached';

      $jp_contrib = $amount * $this->g['jackpot_contrib'];

        $balance    = $this->changeBalance($this->ud, "-$amount", $this->tid, 1);
        $bonus_bet  = empty($this->bonus_bet) ? 0 : 1;

        $extid     = $this->insertBet($this->ud, $this->g, $req['gameRoundId'], $this->tid, $amount, $jp_contrib, $bonus_bet, $balance);
        if($extid === false)
            return 'db.error';

      $balance    = $this->betHandleBonuses($this->ud, $this->g, $amount, $balance, $bonus_bet, $req['gameRoundId'], $this->tid);
      return array('partnerTransactionRef' => $extid, 'balance' => $balance);
    } else {
      //Start of FRB etc
      return array('partnerTransactionRef' => uniqid(), 'balance' => $this->_getBalance(true));
    }
  }

  function transferFromGame($req){

    $result = $this->getBetByMgId($this->tid, 'wins');
    if(!empty($result))
      return array('partnerTransactionRef' => $result['id'], 'balance' => $this->ud['cash_balance']);

    $amount = (int)$req['amount'];

    //if(!empty($req['partnerTransactionRef']))
    //  $this->frb_win = true;

    if(!empty($amount)){
      if ($this->frb_win === true)
	$bonus_bet = 3;
      else
	$bonus_bet = empty($this->bonus_bet) ? 0 : 1;

        $extid = $this->insertWin($this->ud, $this->g, $balance, $req['gameRoundId'], $amount, $bonus_bet, $this->tid, 2);

        if($extid === false)
            return 'db.error';

        $balance = $this->changeBalance($this->ud, $amount, $req['gameRoundId'], 2);

      $balance = $this->handlePriorFail($this->ud, $this->tid, $balance, $amount);
      return array('partnerTransactionRef' => $extid, 'balance' => $balance);

    }else{
      return array('partnerTransactionRef' => uniqid(), 'balance' => $this->_getBalance(true));
    }
  }

  function cancelTransferToGame($req){
    $cancel_tid = 'wi' . $req['canceledTransactionId'];
    $result     = $this->getBetByMgId($cancel_tid);
    if(empty($result)) {
      $already_id = $cancel_tid . 'ref';
      $result     = $this->getBetByMgId($already_id);
      if(!empty($result))
        return array('partnerTransactionRef' => $result['id'], 'balance' => $result['balance']);
    }

    if(!empty($result)) {
      if(empty($result['amount']))
        return array('partnerTransactionRef' => $result['id'], 'balance' => $result['balance']);
      $type    = 7;
      $amount  = $result['amount'];
      $balance = $this->changeBalance($this->ud, $amount, $result['trans_id'], $type);
      $this->doRollbackUpdate($cancel_tid, 'bets', $balance, $amount);
      return array('partnerTransactionRef' => $result['id'], 'balance' => $result['balance'] + $amount);
    }else{
      $balance = $this->_getBalance();
      $extid = phive('SQL')->sh($this->ud, 'id', 'bets')->insertArray('bets', array(
        'user_id'  => $this->ud['id'],
        'mg_id'    => $cancel_tid,
        'balance'  => $balance,
        'currency' => $this->ud['currency'],
        'game_ref' => $this->g['ext_game_name']
      ));
      return array('partnerTransactionRef' => $extid, 'balance' => $balance);
    }
  }

  function stripWi($str){
    return preg_replace('/^wi/', '', $str);
  }

  function getDepUrl($gid, $lang, $game = null, $show_demo = false){
    $game     = phive("MicroGames")->getByGameId($gid);
    $locale   = phive('MicroGames')->getGameLocale($game['game_id'], $lang);
    if(!empty($game['multi_channel']))
      return $this->getMobilePlayUrl($game, $locale, urlencode(phive()->getSiteUrl()), null, '', $show_demo);
    $base     = $this->getSetting('dep_url');
    $pcode    = $this->getSetting('partner_code');
    $gcode    = $this->stripWi($game['ext_game_name']);
    $base    .= "partnerCode=$pcode&gameCode=$gcode";
    if(isLogged()){
      $ticket = $this->setTicket($_SESSION['local_usr']);
      $base  .= "&accountId={$_SESSION['mg_id']}&ticket=$ticket&locale=$locale&realMoney=true";
    }
    return $base;
  }

  //https://mresrc.stage.casinarena.com/resource-service/game.html?game=zeus&locale=en&realmoney=true&partnercode=videoslots&partnerticket=xxxxxx
  function getMobilePlayUrl($gref, $lang, $lobby_url, $g = null, $args = [], $show_demo = false){
    $game     = is_array($gref) ? $gref : phive("MicroGames")->getByGameRef($gref);
    $base     = $this->getSetting('mobile_dep_url');
    $pcode    = $this->getSetting('partner_code');
    $gcode    = $this->stripWi($game['ext_game_name']);
    $base    .= "partnercode=$pcode&game=$gcode&lobbyurl=$lobby_url/$lang/mobile/";
    //$locale   = phive('MicroGames')->getGameLocale($game['game_id'], $lang);
    if(isLogged()){
      $ticket = $this->setTicket($_SESSION['local_usr']);
      $base  .= "&partneraccountid={$_SESSION['mg_id']}&partnerticket=$ticket&locale=$lang&realmoney=true";
    }
      $reality_check_interval = phive('Casino')->startAndGetRealityInterval(null, $game['ext_game_name']);
    if (!empty($reality_check_interval) && phive("Config")->getValue('reality-check-mobile', 'wi') === 'on') {
      $user = ud();
      $username = $user['username'];
      unset($user);
      $siteUrl = urlencode(phive()->getSiteUrl());
      $history_link = "{$siteUrl}%2Faccount%2F{$username}%2Fgame-history%2F";
      $reality_check_param = "&realityCheck={$reality_check_interval}&realityButtons=QUITCONT&realityLink={$history_link}";
      $base .= $reality_check_param;
    }

    return $base;
  }



}
