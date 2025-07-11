<?php
require_once __DIR__ . '/QuickFire.php';

class Nyx extends QuickFire{

  function __construct(){
    parent::__construct();
  }

  function activateFreeSpin(&$entry, $na, $bonus){
    $entry['status'] = 'approved';
  }

  function buildError($code, $method, $msg){
    if(empty($method))
      $method = $this->method;
    $str = '<?xml version="1.0" encoding="UTF-8" ?>';
    $str .= '<RSP request="'.$method.'" rc="'.$code.'" msg="'.$msg.'"></RSP>';
    phive()->dumpTbl('nyx-error', [ 'request' => $_REQUEST, 'response' => $str]);
    return $str;
  }

    /**
     * Creates the OGS API session
     *
     * {
     *  "apiversion": "1.0",
     *  "request": "authenticate",
     *  "username": "nyxuser",
     *  "password": "nyxpassword"
     * }
     *
     * @param DBUser $user
     * @return mixed
     */
    public function sapiAuth($user)
    {
        $req = [
            'apiversion' => $this->getLicSetting('osapi_v', $user),
            'request' => 'authenticate',
            'username' => $this->getLicSetting('osapi_user', $user),
            'password' => $this->getLicSetting('osapi_pwd', $user)
        ];
        $res = phive()->post($this->getLicSetting('osapi_url', $user) . '/auth/', json_encode($req), 'application/json', '', 'nyx-auth');
        return json_decode($res)->RSP->sapisession;
    }

    /**
     * @param $uid
     * @param int $gids
     * @param $rounds
     * @param $bonus_name
     * @param array $entry
     * @return mixed
     */
    public function awardFRBonus($uid, $gids, $rounds, $bonus_name, $entry)
    {
        $user = cu($uid);

        $req = [
            'apiversion' => $this->getLicSetting('osapi_v', $user),
            'request' => 'assignpromotions',
            'ogsoperatorid' => $this->getLicSetting('op_id', $user),
            'sapisession' => $this->sapiAuth($user),
            'promotions' => [
                [
                    'campaignid' => $entry['bonus_id'],
                    'accountid' => $uid,
                    'activationid' => $entry['id']
                ]
            ]
        ];
        $res = phive()->post($this->getLicSetting('osapi_url', $user) . '/promotions/', json_encode($req), 'application/json', '', 'nyx-addfrb');
        return !empty($res);
    }

    function loadToken($token){
        $token             = parent::loadToken($token);
        if(strpos($token['game_ref'], 'nyx') !== 0)
            $token['game_ref'] = 'nyx'.$token['game_ref'];
        return $token;
    }

    function executeRequest($method){

        $_REQUEST['game_action'] = $method;
        $this->dumpTst('nyx-req', $_REQUEST);


        $this->logger->debug(__METHOD__, [
            'method' => $method,
            'request'=> $_REQUEST,
        ]);

        $whitelisted_ips = $this->getSetting('whitelisted_ips');
        if (!isCli() && !empty($whitelisted_ips) && !in_array(remIp(), $whitelisted_ips)) {
            error_log("NYX request IP blocked. IP: ". remIp());
            return $this->buildError(110, '', 'IP Blocked.');
        }

        if($method == 'getbalance'){
            $method = 'getNyxBalance';
            $this->method = 'getbalance';
        }else
        $this->method = $method;

        if($_GET['loginname'] != $this->getSetting('loginname') && $_GET['password'] != $this->getSetting('password'))
            return $this->buildError(1008, $method, "Wrong or no API credentials");

        $sid = empty($_GET['gamesessionid']) ? $_GET['sessionid'] : $_GET['gamesessionid'];
        $this->token = $this->new_token = $this->loadToken($sid);
        $this->uid = $this->token['user_id'];

            $this->dumpTst('nyx-token', $this->token);

        if((!isset($_GET['sessionid']) && !isset($_GET['gamesessionid'])) && !in_array($method, array('ping', 'result', 'rollback')))
            return $this->buildError(1008, $method, "Missing session id");

        if($this->token == false && !in_array($method, array('ping', 'result', 'rollback')))
            return $this->buildError(1000, $method, "Invalid session id");

        $user = cu($this->token['user_id']);
        if ($this->method !== 'getaccount' && $this->useExternalSession($user) && !$this->hasSessionBalance()) {
            lic('endOpenParticipation', [$user], $user);
            phive('Casino')->finishGameSession($user->getId(), $this->token['game_ref']);
            toWs(['popup' => 'error_starting_session', 'msg' => t('game-session-balance.init-error')], 'extgamesess', $user->getId());

            return $this->ggiBuildError(6001, 'Error starting session');
        }

        return $this->$method();
    }

  function getUsr(){
    if(empty($this->token['user_id']) && !empty($_GET['accountid']))
      $this->token['user_id'] = $_GET['accountid'];

    // Battle, here we set the Battle info
    $this->setTournament($this->token['user_id']);

    $user = cu($this->token['user_id']);
    if(!is_object($user))
      return $this->buildError(1008, $this->method, 'No user');
    else
      $this->user = $user;
    $GLOBALS['mg_username'] = $user->data['username'];
    return $user->data;
  }

  function buildResponse($params, $method = '', $token = ''){
    $token 	= empty($token) ? $this->token['token'] : $token;
    if($this->is1p2())
      $token = '';
    $method = empty($method) ? $this->method : $method;
    if($this->is1p2())
      unset($params['alreadyprocessed']);
    ob_start();
    echo '<?xml version="1.0" encoding="UTF-8" ?>';
    ?>
    <RSP request="<?php echo $method ?>" rc="0">
      <?php if(!empty($token)): ?>
        <SESSIONID><?php echo $token ?></SESSIONID>
      <?php endif ?>
      <?php foreach($params as $key => $value): ?>
        <<?php echo strtoupper($key) ?>><?php echo $value ?></<?php echo strtoupper($key) ?>>
      <?php endforeach ?>
    </RSP>
    <?php
    $xml = ob_get_contents();
    ob_end_clean();

    $this->logger->debug(__METHOD__, [
       'method' => $method,
       'response'=> $xml,
    ]);

    return $xml;
  }

  function ping(){
    return $this->buildResponse(array(), '', '');
  }

  function remNyx($str){
    return str_replace('nyx', '', $str);
  }

    //http://nogs-gl.nyxinteractive.eu/game/
    //?nogsgameid=_NOGSGAMEID_
    //&nogsoperatorid=_NOGSOPERATORID_
    //&sessionid=_SESSION_
    //&nogscurrency=_NOGSCURRENCY_
    //&nogslang=_NOGSLANG_
    //&nogsmode=_NOGSMODE_
    //&accountid=_ACCOUNTID_
    //lobbyurl
    //clienttype
    /*
     * TODO this needs to be done properly using lic, going for manual as I have 5 minutes to fix this
     */
    function getDepUrl($gid, $lang, $game = null, $show_demo = false, $clienttype = 'flash')
    {
        /** @var MicroGames $mg */
        $mg = phive('MicroGames');
        /** @var DBUser|null $user */
        $user = cu();

        //$lang has to be on the form en_us
        $lang = $mg->getGameLocale($gid, $lang, $clienttype);

        $game = $mg->getByGameId($gid, $clienttype, $user);
        $gref = $this->remNyx(phive('MicroGames')->overrideGame($user, $game)['ext_game_name']);

        if (!empty($user)) {
            $user_data = $user->getData();

            $cur_uid = empty($_SESSION['token_uid']) ? $user->getId() : $_SESSION['token_uid'];
            $uid = $user->getId();
            $this->getUsrId($cur_uid);
            $mode = 'real';
            $this->url_token = $this->insertToken($uid, $this->remNyx($game['ext_game_name']));

            $usr_country = $user->getCountry() ?? licJur();

            $op_id = $this->getLicSetting('op_id', $user);

            $base_url = $this->getLicSetting('dep_url', $user);

            $this->dumpTst('nyx-token', $this->url_token);

            $this->updateToken($this->url_token['token'], 'device_type', $clienttype);
            if ($this->isTournamentMode()) {
                $jur = $this->getLicSetting('bos-country', $user);
            } else {
                $jur = $this->getLicSetting('jurisdiction', $user);
            }
            return $base_url . str_replace(
                    array('%1', '%2', '%3', '%4', '%5', '%6', '%7', '%8', '%9'),
                    array($gref, $op_id, $this->url_token['token'], $this->getPlayCurrency($user_data), $lang, $mode, $cur_uid, $clienttype, $jur),
                    $this->getSetting('real_play_url'));
        } else {
            $op_id = $this->getLicSetting('op_id', $user);
            $base_url = $this->getLicSetting('dep_url');
            $mode = 'demo';
            return $base_url . str_replace(
                    array('%1', '%2', '%3', '%4', '%5', '%6'),
                    array($gref, $op_id, ciso(), $lang, $mode, $clienttype),
                    $this->getSetting('demo_play_url'));
        }
    }

    function getMobilePlayUrl($gid, $lang, $lobby_url, $g, $args = [], $show_demo = false)
    {
        $mg = phive('MicroGames');
        $g = $mg->getByGameRef($gid, 'html5');
        $url = $this->getDepUrl($g['game_id'], $lang, null,$show_demo, 'html5');
        $rcparams = "";
        $user = cu();
        if (!empty($user)) {
            $reality_check_interval = phive('Casino')->startAndGetRealityInterval($user->getId(), $g['ext_game_name']);

            if (!empty($reality_check_interval) && phive("Config")->getValue('reality-check-mobile', 'nyx') === 'on') {
                $reality_check_interval = $reality_check_interval * 60;

                $siteUrl = phive()->getSiteUrl();
                $history_link = "{$siteUrl}/account/{$user->getId()}/game-history/";
                $rcparams .= "&realitycheck=uk&realitycheck_uk_elapsed=1&realitycheck_uk_limit={$reality_check_interval}&realitycheck_uk_history={$history_link}";
                unset($user);
            }
        }

        if (cuCountry() == 'SE') {
            $base_params = lic('getBaseGameParams', [$user], $user);
            if (!empty($base_params['elapsed_session_time'])) {
                $rcparams .= "&elapsedtime={$base_params['elapsed_session_time']}";
            }
            $rcparams .= "&selfassessment_url={$base_params['selfassessment_url']['url']}";
            $rcparams .= "&depositlimit_url={$base_params['accountlimits_url']['url']}";
            $rcparams .= "&selfexclusion_url={$base_params['selfexclusion_url']['url']}";
        }

        $lobby_url = "$lobby_url/$lang/mobile/".$this->getHomeRedirectInIframe(true, "?", $gid);
        return "$url&lobbyurl=$lobby_url{$rcparams}";
    }

    function is1p2(){
      return ($this->getSetting('ogsv') === '1.2' || $_GET['apiversion'] === '1.2');
    }

    function getaccount(){
        $user = $this->getUsr();
        if(is_string($user))
            return $user;
        $bcountry = phive("UserHandler")->userBankCountry($this->user);
        $response = array(
            'accountid' => $this->getPlayUid($this->token['user_id']),
            'currency'  => $this->getPlayCurrency($user),
            'city' 	  => $user['city'],
            'country'   => $bcountry['iso3'],
            'jurisdiction' => $this->isTournamentMode() ? $this->getLicSetting('bos-country') : strtoupper($this->getLicSetting('jurisdiction', $user))
        );

        $response['playermaxstake'] = phive('Gpr')->getMaxBetLimit(cu($user));
        
        if($this->is1p2()){
            $gsid = mKey($user['id'], $this->uuid());
            $response['gamesessionid'] = $gsid;
            // Since we already have a token assigned updateSessionToken() will be called in insertToken() in order to
            // create the new ext_game_participations row. Subsequent calls will be handled by the setSessionById() call
            // in loadToken().
            $this->insertToken($user['id'], $this->remNyx($this->token['game_ref']), 'login', $gsid);
        }

        return $this->buildResponse($response);
    }

    /**
     * @return mixed
     */
    public function getGameIdFromOverriden()
    {
        if ($this->isTournamentMode()) {
            $jurisdiction = $this->getLicSetting('bos-country');
        } else {
            $jurisdiction = licJur($this->cur_user);
        }

        $game_id = phive('SQL')->getValue("
            SELECT mg.game_id
            FROM game_country_overrides gco
              LEFT JOIN micro_games mg on gco.game_id = mg.id
            WHERE gco.ext_game_id = '{$this->token['game_ref']}' AND gco.country = '{$jurisdiction}'
        ");

        return !empty($game_id) ? $game_id : null;
    }

    function getNyxBalance(){
        $user = cu($_REQUEST['accountid']);
        $balance = $this->_getBalance();

        if ($this->isTournamentMode()) {
            $balance = $this->tEntryBalance();
        } elseif ($this->hasSessionBalance()) {
            $balance = $this->getSessionBalance($user);
        }

        if (is_string($balance) && !$this->hasSessionBalance()) {
            return $this->buildResponse(['balance' => phive()->twoDec($balance)]);
        }

        // @todo use request parameters to get the game_id instead of the token
        $game_id = $this->getGameIdFromOverriden() ?? $this->token['game_ref'];
        $bonus_balance = empty($this->gref) ? 0 : phive('Bonuses')->getBalanceByRef($game_id, $user);


        return $this->buildResponse([
            'balance' => phive()->twoDec($this->getTotalBalance(
                $user->getData(),
                $balance,
                $bonus_balance
            )),
        ]);
    }

  function betResultGetUser($key = 'betamount', $amount = '', $ext_id = ''){

    $user = $this->getUsr();
    if(is_string($user))
      return $user;

    if(empty($ext_id))
      $ext_id = $_GET['gpid'].'-'.(empty($_GET['transactionid']) ? uniqid() : $_GET['transactionid']);

    if(empty($amount))
      $amount = $this->fAmount($_GET[$key]);

    $ext_id = "nyx{$ext_id}";
    $this->setParams($amount, $ext_id, $_REQUEST['roundid']);
    return array($user, $amount, $ext_id);
  }

  function getGameByRef($gid = ''){
      $gid = empty($gid) ? $_REQUEST['nogsgameid'] : $gid;
      if (!preg_match('/^nyx/', $gid)) {
          $gid = "nyx".$gid;
      }
      $device_type_num = $_REQUEST['device'] == 'desktop' ? 0 : 1;
      return phive('MicroGames')->getByGameRef($gid, $device_type_num, $this->user);
  }

  function insertWin($user, $cur_game, $balance, $tr_id, $amount, $bonus_bet, $ext_id, $award_type){
    if(!empty($amount))
      return parent::insertWin($user, $cur_game, $balance, $tr_id, $amount, $bonus_bet, $ext_id, $award_type);
    return true;
  }

  function result(){


    $this->game_action = 'win';

    $start = $this->betResultGetUser('result');

    if(is_string($start))
      return $start;

    list($user, $amount, $nyx_id) = $start;

    $result = $this->getBetByMgId($nyx_id, 'wins');

    $GLOBALS['mg_id'] = $nyx_id;

    if(empty($result)){
        $cur_game 		 = $this->getGameByRef();

      if(empty($cur_game))
        return $this->buildError(1, '', 'Game missing.');

        $cur_game['ext_game_name'] = empty($cur_game['ext_game_name']) ? $_GET['nogsgameid'] : $cur_game['ext_game_name'];

        if (!empty($_GET['activationid']) && $_GET['gamestatus'] == 'completed') {
            $bonus_bet = 3;
            $this->frb_win = true;
            $fspin = phive('Bonuses')->getBonusEntry($_GET['activationid'], $user['id']);
            if (!empty($fspin)) {
                $bonus_type = phive('Bonuses')->getBonus($fspin['bonus_id']);
                $award_type = ($bonus_type["rake_percent"] > 0) ? 5 : 3;
                $this->handleFspinWin($fspin, $amount, $user, 'Freespin win');
                $balance = $this->_getBalance();
                if (!empty($amount)) {
                    $extid = $this->insertWin($user, $cur_game, $balance, $_GET['roundid'], $amount, $bonus_bet, $nyx_id, $award_type);
                    if ($extid === false) {
                        return $this->buildError(1, '', 'Database error, could not log result');
                    }
                    phive('Bonuses')->resetEntries();
                }
            }
            $balance = $this->_getBalance();
        } else {
            $bonus_bet = empty($this->bonus_bet) ? 0 : 1;
            $balance = $this->_getBalance();
            $extid = $this->insertWin($user, $cur_game, $balance, $_GET['roundid'], $amount, $bonus_bet, $nyx_id, 2);
            if ($extid === false) {
                return $this->buildError(1, '', 'Database error, could not log result');
            }
            $balance = $this->playChgBalance($user, $amount, $_GET['roundid'], 2);
            if ($balance === false) {
                return $this->buildError(1, '', 'Database error, could not change balance');
            }
        }

      if(empty($extid))
        $extid = uniqid();

      if ($this->doConfirmByRoundId()) {
          $this->updateRound($user['id'], "nyx_{$_REQUEST['roundid']}", $extid === true ? null : $extid);
      }

      $already 	= 'false';

      $balance 	= $this->handlePriorFail($user, $_GET['roundid'], $balance, $amount);

    }else{
      $already 	= 'true';
      $extid 		= $result['id'];
      $balance 	= $this->getBalance(false);
    }

    return $this->buildResponse(array(
      'balance' 		=> phive()->twoDec($balance),
      'accounttransactionid' 	=> $extid,
      'alreadyprocessed' 	=> $already,
    ), '', $_GET['sessionid']);
  }

  function fAmount($amount){
    if($amount < 0)
      return 0;
    return round($amount * 100);
  }

  function rollback(){
    $user 		= $this->getUsr();
    if(is_string($user))
      return $user;

    $tbl    = 'bets';
    $nyx_id = 'nyx'.$_GET['gpid'].'-'.$_GET['transactionid'];
    $result = $this->getBetByMgId($nyx_id, 'bets', 'mg_id', $user['id']);

    if(empty($result) && !$this->is1p2()){
      $result = $this->getBetByMgId($nyx_id, 'wins', 'mg_id', $user['id']);
      $tbl = empty($result) ? '' : 'wins';
    }

    $already = 'false';

    if(empty($result)){
      $nyx_already_id = "$nyx_id".'ref';
      $result = $this->getBetByMgId($nyx_already_id);
      if(empty($result))
        $result = $this->getBetByMgId($nyx_already_id, 'wins');
      if(!empty($result))
        $already = 'true';
      else
        return $this->buildError(102, '', 'No transaction to rollback exists');
    }

    $amount = $this->fAmount($_GET['rollbackamount']);

    if($tbl == 'wins')
      $amount = -$amount;

    $this->new_token['game_ref'] = $result['game_ref'];

    if($already == 'false'){
      $balance = $this->playChgBalance($user, $amount, $result['trans_id'], 7);

      if($balance === false)
        return $this->buildError(1, '', 'Database error, could not change balance on rollback');

      $this->doRollbackUpdate($nyx_id, $tbl, $balance, $amount);
    }else
      $balance = $this->_getBalance();

    return $this->buildResponse(array(
      'balance' 				=> phive()->twoDec($balance),
      'accounttransactionid' 	=> $result['id'],
      'alreadyprocessed' 		=> $already,
    ), '', $_GET['sessionid']);
  }

  function wager(){
    $start = $this->betResultGetUser();

    if(is_string($start))
      return $start;

    list($user, $tmp_bet_amount, $nyx_id) = $start;

    $result = $this->getBetByMgId($nyx_id);
    $GLOBALS['mg_id'] = $nyx_id;

    if(!empty($result)){
      $balance 	= $result['balance'];
      $extid 	= $result['id'];
      $already	= 'true';
    }else{
      $already		= 'false';
      $balance 	= $this->_getBalance($user);

      if(empty($tmp_bet_amount)){
        //we have an frb "bet"
        $extid = uniqid();
      }else{
          $cur_game = $this->getGameByRef();

        if(empty($cur_game) || empty($cur_game['active'])) {
            return $this->buildError(110, '', "Game {$cur_game['id']} not allowed.");
        }
          //$cur_game['device_type'] 	= $this->token['device_type'];

        $bet_amount = $tmp_bet_amount;
        //$jp_contrib 	= $tmp_bet_amount - $bet_amount;
        $jp_contrib = round($bet_amount * $cur_game['jackpot_contrib']);
        $balance = $this->lgaMobileBalance($user, $cur_game['ext_game_name'], $balance, $cur_game['device_type'], $bet_amount);
        if($balance < $tmp_bet_amount)
          return $this->buildError(1006, '', "Low balance");

        $GLOBALS['mg_id'] = $nyx_id;

          $balance = $this->playChgBalance($user, -$bet_amount, $_REQUEST['roundid'], 1);
          if($balance === false)
              return $this->buildError(1, '', "Could not update balance");
          $bonus_bet = empty($this->bonus_bet) ? 0 : 1;

          $extid 			= $this->insertBet($user, $cur_game, $_REQUEST['roundid'], $nyx_id, $bet_amount, $jp_contrib, $bonus_bet, $balance);
          if(!$extid)
              return $this->buildError(1, '', "Could not log bet");

          if ($this->doConfirmByRoundId()) {
              $this->insertRound($user['id'], $extid, "nyx_{$_REQUEST['roundid']}");
          }

        $balance 		= $this->betHandleBonuses($user, $cur_game, $bet_amount, $balance, $bonus_bet, $_REQUEST['roundid'], $nyx_id);
      }
    }

    return $this->buildResponse(array(
      'balance' 		=> phive()->twoDec($balance),
      'accounttransactionid' 	=> $extid,
      'alreadyprocessed' 	=> $already,
      'bonusmoneybet' 		=> phive()->twoDec($bonus_bet == 1 ? $tmp_bet_amount : 0),
      'realmoneybet' 		=> phive()->twoDec($bonus_bet == 0 ? $tmp_bet_amount : 0)
    ));
  }

    /**
     * Enables rounds table
     *
     * @return bool
     */
    public function doConfirmByRoundId(): bool
    {
        return !($this->isTournamentMode());
    }

    public function getNetworkLibraries(): array
    {
        return array_map('getFileWithCacheBuster', $this->getSetting('nyx_network_libraries'));
    }

    /**
     * Stores in memory the current game and tournament_entry the user is playing
     * and returns the token that will be sent to QuickFire to keep the user session.
     *
     * @override QuickFire::insertToken()
     *
     * @param string $user_id
     * @param string $game_ref
     * @param string $method
     * @param string $token
     * @return false
     */
    function insertToken($user_id = '', $game_ref = '', $method = '', $token = '') {
        $user_id = empty($user_id) ? $_SESSION['mg_id'] : $user_id;
        if (empty($user_id)) {
            return false;
        }

        $uuid = empty($token) ? "{$this->uuid()}-{$user_id}" : $token;
        $token = [
            'user_id' => $user_id,
            'game_ref' => $game_ref,
            't_eid' => $this->t_eid,
        ];

        if ($this->isTournamentMode()) {
            $token['uuid'] = $uuid;
        }

        $this->logger->debug(__METHOD__, [
            'user_id' => $user_id,
            'game_ref' => $game_ref,
            'method' => $method,
            'token' => $token,
            'uuid' => $uuid,
        ]);

        return empty($this->token)
            ? $this->setNewSessionToken($token, $uuid)
            : $this->updateSessionToken($game_ref, $method, $token, $uuid);
    }

    /**
     * This function is triggered when a bet is placed outside the limits when the user is
     * in tournament mode.
     *
     * It is called from Casino::lgaMobileBalance();
     *
     * @param array $context
     * @return void
     */
    function handleBetOutsideLimits(array $context = []) {
        if (!$this->isTournamentMode()) {
            return;
        }

        phM('hmset', $this->token['uuid'], $this->token, $this->exp_time);
    }

    private function sapiAuthCli($osapi_url, $osapi_v, $osapi_user, $osapi_pwd)
    {
        $req = [
            'apiversion' => $osapi_v,
            'request' => 'authenticate',
            'username' => $osapi_user,
            'password' => $osapi_pwd,
        ];
        $res = phive()->post($osapi_url . '/auth/', json_encode($req), 'application/json', '', 'nyx-auth');
        return json_decode($res)->RSP->sapisession;
    }

    public function parseJackpots()
    {
        $parsed_jackpots = [];

        $osapi_urls = $this->getAllJurSettingsByKey('osapi_url');
        $osapi_version = $this->getSetting('osapi_v');
        $auth_usernames = $this->getAllJurSettingsByKey('osapi_user');
        $auth_passwords = $this->getAllJurSettingsByKey('osapi_pwd');
        $ogs_operator_ids = $this->getAllJurSettingsByKey('op_id');

        foreach ($osapi_urls as $jur => $url) {
            $currency = phive('Currencer')->getCurrencyByCountryCode($jur)['code'] ?? 'EUR';
            // Jackpot request needs an Auth request
            $sapi_session = $this->sapiAuthCli($url, $osapi_version, $auth_usernames[$jur], $auth_passwords[$jur]);

            $payload = json_encode([
                'apiversion' => $osapi_version,
                'request' => 'jackpotoverview',
                'ogsoperatorid' => $ogs_operator_ids[$jur],
                'sapisession' => $sapi_session,
                'currency' => $currency,
            ]);

            $response = phive()->post($url . '/jackpots/', $payload, 'application/json', '','nyx-jackpot-curl', 'POST', 10);

            $jsonResponse = json_decode($response, true);

            foreach ($jsonResponse['RSP']['jackpots'] as $jackpot) {
                foreach ($jackpot['ogsgameids'] as $game_id) {
                    $game = phive("MicroGames")->getByGameRef("nyx{$game_id}");

                    if (empty($game)) {
                        continue;
                    }

                    $parsed_jackpots[] = [
                        'local' => 0,
                        'network' => 'nyx',
                        'jurisdiction' => $jur,
                        'game_id' => $game['game_id'],
                        'jp_name' => $game['game_name'],
                        'module_id' => $game['ext_game_name'],
                        'jp_id' => $jackpot['name'],
                        'currency' => $jsonResponse['RSP']['currency'],
                        'jp_value' => $jackpot['value'] * 100,
                    ];
                }
            }
        }

        return $parsed_jackpots;
    }
}
