<?php
require_once __DIR__ . '/Casino.php';
class QuickFire extends Casino{

    /** @var string External game reference */
    public $gref;

    /**
    * @var string
    */
    private $fs_token;

    /**
     * @var array
     */
     public $url_token;

    /**
     * @var Logger $logger
     */
    protected Logger $logger;
  
  /**
   * URLs relatives to the Account API and Free Games API. needs API Key
   * @var array
   */
  private $fs_api = [
    'get_fs_game_uri' => 'Casino/FreeGames/v1/offers/product/%0/offerId/%1',
    'create_fs_game_uri' => 'Casino/FreeGames/v1/offers/nearestCost/product/%0',
    'check_user_exists_uri' => 'Account/v1/accounts/checkUserExists',
    'register_user_uri' => 'Banking/registration/v2/registrations/basic',
    'add_user_fs_game_uri' => 'Casino/FreeGames/v1/offers/product/%0/user/%1/offer/%2',
    'remove_user_fs_game_uri' => 'Casino/FreeGames/v1/offers/product/%0/user/%1/offer/%2/instance/%3',
    'get_offer_by_name' => 'Casino/FreeGames/v1/offers/product/%0/offerName/%1',
    'get_offer_by_id' => 'Casino/FreeGames/v1/offers/product/%0/offerId/%1'
  ];
  
  protected string $logger_name = 'quickfire';
  
  function phAliases()	{ return array('Microgaming'); }

    function __construct(){
        parent::__construct();
        $this->logger     = phive('Logger')->getLogger($this->logger_name);
        $this->logger->addIntrospectionProcessor();
    }

  function getOrion(){
    require_once 'Orion.php';
    return new Orion();
  }

  function activateFreeSpin(&$entry, $na, $bonus) {
    $entry['status'] = 'approved';
  }


  function ggiParseReq($xml){
    $this->xml = $xml;
    $r = new SimpleXMLElement($xml);

    $res = array();
    $call = array_pop($r->xpath('//call'));
    foreach($call->attributes() as $key => $value){
      $tmp = (array)$value;
      $res[$key] = $tmp[0];
    }
    $this->params = $res;
    $method_call = array_pop($r->xpath('//methodcall'));
    $this->method = $method_call['name'];
    $this->ggi_stamp = $method_call['timestamp'];
    //file_put_contents('mg.log', "{$this->method}|{$this->ggi_stamp}|".implode('|', $res)."\n", FILE_APPEND);
    return true;
  }

  function ggiBuildError($code, $msg){
    ob_start();
    ?>
    <pkt>
      <methodresponse name="<?php echo $this->method ?>" timestamp="<?php echo $this->ggi_stamp; //date('Y/m/d H:i:s.000') ?>">
          <result seq="<?php echo $this->params['seq'] ?>" errorcode="<?php echo $code ?>" errordescription="<?php echo $msg ?>">
          <extinfo/>
        </result>
      </methodresponse>
    </pkt>
    <?php
    $xml = ob_get_contents();
    ob_end_clean();


    $this->logger->warning('quickfire_error_call', [$this->xml]);
    $this->logger->warning('quickfire_error', [$xml]);
      
    phive()->dumpTbl('quickfire_error_call', $this->xml);
    phive()->dumpTbl('quickfire_error', $xml);
    return $xml;
  }

  function ggiExecuteRequest($xml){
    $result = $this->ggiParseReq($xml);
    if($result !== true)
      return $this->ggiBuildError(6003, 'auth error');

    //echo "method: ".$this->method;

    $method = 'ggi'.ucfirst($this->method);

    if(strpos($this->params['token'], '_') !== false){
      //loginname_gameid_actionid
      list($user_id, $tr_id, $mg_id) = explode('_', $this->params['token']);
      $user = cu($this->getUsrId($user_id));
      $this->gref = $this->getOriginalGameRef($user, $this->params['gamereference']);
      $this->token = $this->new_token = array('user_id' => $user_id, 'game_ref' => $this->gref, 'token' => $this->params['token']);
    }else {
        $this->token = $this->loadToken($this->params['token']);
        $this->gref = $this->token['game_ref'];
    }

    if($this->params['offline'] !== 'true'){
        if($this->token == false){
            if($this->method == 'login') {
                $ret = $this->ggiLogin(true);

                $this->logger->info('playcheck_call', [$this->xml]);
                $this->logger->debug('playcheck_response', [$ret]);

                phive()->dumpTbl('playcheck_call', $this->xml);
                phive()->dumpTbl('playcheck_response', $ret);
                return $ret;
            }
            return $this->ggiBuildError(6001, 'invalid token');
       }
    }

    if($this->method == 'login'){
        $this->new_token = $this->insertToken($this->token['user_id'], $this->token['game_ref'], $this->method);
        if($this->useExternalSession(cu($this->new_token[$user_id])) && empty($this->new_token['ext_session_id']) && empty($this->session_entry['participation_id'])) {
            return $this->ggiBuildError(6001, 'Error starting session');
        }
    } else {
        $this->new_token = $this->token;

        $user = null;
        if($this->params['offline'] !== 'true'){
            $user = cu($this->token['user_id']);
        }
        else{
            $user = cu($this->params['token']);
        }

        if ($this->useExternalSession($user) && empty($this->session_entry['user_game_session_id'])) {
            lic('endOpenParticipation', [$user], $user);
            phive('Casino')->finishGameSession($user->getId(), $this->gref);

            toWs([
                'popup' => 'error_starting_session',
                'msg' => t('game-session-balance.init-error')
            ], 'extgamesess', $user->getId());

            return $this->ggiBuildError(6001, 'Error starting session');
        }
    }

    $this->logger->info('new token', ['data' => $this->new_token]);
      
    return $this->$method();
  }

  function ggiBuildResponse($params, $method = '', $token = ''){
    $token  = empty($token) ? $this->new_token['token'] : $token;
    $method = empty($method) ? $this->method : $method;
    ob_start();
    ?>
    <pkt>
      <methodresponse name="<?php echo $method ?>" timestamp="<?php echo $this->ggi_stamp; // date('Y/m/d H:i:s.000') ?>">
      <result seq="<?php echo $this->params['seq'] ?>"
        <?php if(!empty($token)): ?>
          token="<?php echo $token ?>"
        <?php endif ?>
        <?php if(!empty($params)): ?>
          <?php foreach($params as $key => $value): ?>
            <?php if($key != 'freegames' && $key != 'maxBet'): ?>
                <?php echo $key ?>="<?php echo $value ?>"
            <?php endif ?>
          <?php endforeach ?>
        <?php endif ?>
        >
        <?php if(!empty($params['freegames'])): ?>
          <freegames>
            <?php foreach($params['freegames'] as $fgame): ?>
                <freegame name="<?php echo $fgame['name'] ?>" action="<?php echo $fgame['action'] ?>" />
            <?php endforeach ?>
          </freegames>
        <?php endif ?>
        <extinfo/>
          <?php if(!empty($params['maxBet'])): ?>
              <usersettings>
                  <regulatedmarket>
                      <key id="stakelimit" value="<?php echo($params['maxBet']) ?>"/>
                  </regulatedmarket>
              </usersettings>
          <?php endif ?>
      </result>
      </methodresponse>
    </pkt>
    <?php
    $xml = ob_get_contents();
    if(!empty($params['freegames']))

        $this->logger->info('quickfire_freegames_login_xml', [$xml]);
      phive()->dumpTbl('quickfire_freegames_login_xml', $xml);
    ob_end_clean();
    return $xml;
  }

  function ggiLogin($playcheck = false){
    $user =  $this->ggiGetUsr();
    if($playcheck || is_string($user)){
      $token = $this->params['token'];
      $str = "SELECT u.* FROM users u, playcheck_tokens pt WHERE pt.token = '$token' AND u.id = pt.user_id";
      $puser = phive("SQL")->shs('merge', '', null, 'playcheck_tokens')->loadAssoc($str);
      if(empty($puser)){
        return $this->ggiBuildError(6001, 'invalid token');
      } else {
          phive("SQL")->delete("playcheck_tokens", ['token' => $this->params['token']], $puser['id']);
        $user = $puser;
        $balance = $this->hasSessionBalance() ? $this->getSessionBalance($user) : $user['cash_balance'];
        $GLOBALS['mg_username'] = $user['username'];
        $this->new_token['token'] = phive()->uuid();
      }
    }else if(!empty($this->t_entry)){
      $balance = $this->tEntryBalance();
      $currency = 'EUR';
    }else{
      $es = phive("SQL")->sh($user, 'user_id', 'bonus_entries')->loadArray("SELECT * FROM bonus_entries WHERE bonus_type = 'freespin' AND bonus_tag = 'microgaming' AND user_id = ".$user['id']);
      foreach($es as $e){
        if($e['status'] == 'pending'){
          $fs[] = array('name' => $e['ext_id'], 'action' => 'add');
          $e['status'] = 'approved';
          phive('SQL')->sh($e, 'user_id', 'bonus_entries')->save('bonus_entries', $e);
        }else if($e['status'] == 'failed' && !empty($e['cash_progress'])){
          $fs[] = array('name' => $e['ext_id'], 'action' => 'remove');
        }
      }
      if ($this->hasSessionBalance()) {
        $balance =  $this->getSessionBalance($user);
      } else {
        $bonus_balances = !empty($this->new_token['game_ref']) ? phive('Bonuses')->getBalanceByRef($this->new_token['game_ref'], $this->token['user_id']) : 0;
        $balance = $user['cash_balance'] + $bonus_balances;
      }
      $currency = $user['currency'];
    }
    
    $ret = array(
      //'loginname'    => $this->token['user_id'],
      //'loginname'    => $user['username'],
      'loginname'    => $this->getExtUsername($user),
      'currency'     => $currency,
      'country'      => phive('Cashier')->getIso3FromIso2($this->getCountry($user)),
      'city'         => $this->getCity($user),
      'balance'      => $balance,
      'bonusbalance' => 0,
      'wallet'       => 'vanguard'
    );

    if(!empty($fs)){
      $ret['freegames'] = $fs;
    }

    $maxBet = phive('Gpr')->getMaxBetLimit(cu($user));
    if($maxBet){
        $casProv = new CasinoProvider();
        $ret['maxBet'] = $casProv->convertCoinage($maxBet, 'units', 'cents');
    }

    return $this->ggiBuildResponse($ret);
  }

  function ggiEndgame(){
    $user =  $this->ggiGetUsr();
    if(empty($this->t_entry)){
      $balance = $user['cash_balance'];
      $bonus_balances = phive('Bonuses')->getBalanceByRef($this->new_token['game_ref'], $this->token['user_id']);

      $game = phive('MicroGames')->getByGameRef($this->params['gamereference'] , null, $user);
      $round_ext_id = $this->getRoundExtId($game, $this->params, cu($user['id']));
      $this->updateRound($user['id'], $round_ext_id);
    }else
    $balance = $this->tEntryBalance();

    $this->checkNextRound($user);
    return $this->ggiBuildResponse(array('balance' => $balance + $bonus_balances, 'bonusbalance' => 0));
  }


  function ggiGetUsr(){
      if($this->params['offline'] !== 'true'){
          $user = cu($this->getUsrId($this->token['user_id']));
      }
      else{
          $user = cu($this->params['token']);
      }

    if(!is_object($user))
      return $this->ggiBuildError(6103, 'nouser');
      else{
          $this->user = $user;
          $this->uid = $user->data['id'];
      }
    $GLOBALS['mg_username'] = $user->data['username'];
    return $user->data;
  }

  function ggiGetbalance($as_string = true){
    $user     = $this->ggiGetUsr();
    if(is_string($user))
      return $user;
    $total_balance = $this->_getBalance();
    if(!$as_string)
      return (int)$total_balance;
    return $this->ggiBuildResponse(array('balance' => $total_balance, 'bonusbalance' => 0));
  }

    /**
     * Starts and stores the external session into the session token
     * @param $token
     * @param $method
     * @return mixed
     */
    public function setNewExternalSession($token, $method)
    {
        $user = cu($token['user_id']);
        if (in_array($method, ['login', 'getaccount']) && empty($token['t_eid']) && !empty($user) && lic('hasGameplayWithSessionBalance', [], $user) === true) {
            $game = $this->getGameByRef($token['game_ref']);
            $token['ext_session_id'] = lic('initGameSessionWithBalance', [$user, $token['token'], $game], $user);
        }

        $this->logger->debug(__METHOD__, [
            'user_id' => $token['user_id'] ?? NULL,
            'token' => $token,
            'game' => $game,
            'method' => $method,
        ]);

        return $token;
    }

    function setNewSessionToken($token, $uuid) {
        phMdel($_SESSION['mg_token']);
        $_SESSION['mg_token'] = $uuid;
        phM('hmset', $_SESSION['mg_token'], $token, $this->exp_time);

        $token['sid'] = session_id();
        $token['token'] = $_SESSION['mg_token'];

        return $token;
    }

    function updateSessionToken($game_ref, $method, $token, $uuid) {
        $this->logger->debug(__METHOD__, [
            'user_id' => $token['user_id'] ?? NULL,
            'uuid' => $uuid,
            'token' => $token,
            'ext_game_name' => $game_ref,
            'method' => $method,
        ]);

        if (in_array($method, ['getAccountDetails', 'login', 'getaccount']) && !$this->isTournament($token['user_id'])) {
            $token = $this->token;
            phMdel($token['token']);
            $token['game_ref'] = $game_ref;
            $token = $this->setNewExternalSession($token, $method);
            phM('hmset', $uuid, $token, $this->exp_time);
            $token['token'] = $uuid;
        } else {
            phM('hset', $this->token['token'], 'game_ref', $game_ref);
            $token['token'] = $this->token['token'];
        }

        return $token;
    }

    /**
     * Save the session balance data if the user has gameplay with session balance enabled
     *
     * @param $token
     * @param $method
     *
     * @return mixed
     */
    public function setExternalSession($token, $method)
    {
        $user = cu($token['user_id']);

        if ($method == 'login' && empty($token['t_eid']) && !empty($user) && lic('hasGameplayWithSessionBalance', [], $user) === true) {
            $game = $this->getGameByRef($token['game_ref']);
            $token['ext_session_id'] = lic('initGameSessionWithBalance', [$user, $token['token'], $game], $user);
        }
        return $token;
    }

    /**
     * Stores in memory the current game and tournament_entry the user is playing
     * and returns the token that will be sent to QuickFire to keep the user session
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
        $uuid = empty($token) ? $this->uuid() . '-' . $user_id : $token;
        $token = ['user_id' => $user_id, 'game_ref' => $game_ref, 't_eid' => $this->t_eid];

        $this->logger->debug(__METHOD__, [
            'user_id' => $user_id,
            'uuid' => $uuid,
            'token' => $token,
            'ext_game_name' => $game_ref,
            'method' => $method,
        ]);

        return empty($this->token) ? $this->setNewSessionToken($token, $uuid) : $this->updateSessionToken($game_ref, $method, $token, $uuid);
    }

  function updateToken($token, $key, $value){
    $token_arr 			= phM('hgetall', $token, $this->exp_time);
    $token_arr[$key] 	= $value;
    phM('hmset', $token, $token_arr, $this->exp_time);
  }

  function loadToken($token){
    $token_arr 				= phM('hgetall', $token, $this->exp_time);
    if(empty($token_arr))
      return false;
    $token_arr['token'] = $token;
    phM('expire', $token_arr['session_id'], $this->exp_time);      
    if (isset($token_arr['ext_session_id'])) {
      $this->setSessionById($token_arr['user_id'], $token_arr['ext_session_id']);
    }
    return $token_arr;
  }

  function refreshTokenCommon($token_id){
    return $this->loadToken($token_id);
  }

  function ggiRefreshtoken(){
    $result = $this->refreshTokenCommon($this->token['token']);
    if($result === false)
      return $this->ggiBuildError(6001, 'invalid token');
    return $this->ggiBuildResponse(array(), '', $result['token']);
  }

    /**
     * @param $ud
     * @param int $aid
     * @return string
     */
    public function getActionId(&$ud, $aid = 0)
    {
        $aid = abs(empty($aid) ? $this->params['actionid'] : $aid);
        $op_id = $this->getLicSetting('server_id', $ud['id']);
        return "$aid-$op_id";
    }

    function ggiPlay(){
      $action = $this->params['playtype'];
      $amount = $this->params['amount'];
      switch($action){
        case 'bet':
          return $this->ggiBet();
          break;
        case 'win':
          return $this->ggiWin();
          break;
        case 'progressivewin':
          return $this->ggiWin($action, 4);
          break;
        case 'refund':
          return $this->ggiRefund();
          break;

              /*
        case 'transfertomgs':
          return $this->ggiTournament(44, -$amount);
          break;
        case 'tournamentpurchase':
          return $this->ggiTournament(45, -$amount);
          break;
        case 'transferfrommgs':
          return $this->ggiTournament(46, $amount);
          break;
        case 'admin':
          return $this->ggiTournament(47, $amount);
              break;
              */

      }
    }

    function ggiRefund(){
      //doesn't work in case the token has expired'
      if($this->params['offline'] !== 'true'){
          $user = $this->ggiGetUsr();
      }
      else{
          $user = cu($this->params['token'])->data;
      }

      if(is_string($user))
        return $user;

        $mg_id = $this->getActionId($user); //abs($this->params['actionid']);
        //No need to pass in user id as we set it in ggiGetUsr anyway
        $result = $this->getBetByMgId($mg_id);
        $refunded = $this->getBetByMgId($mg_id.'ref');

        if(!empty($refunded)){
            return $this->ggiBuildError(6501, 'Already processed with different details.');
        }

        $extid = $result['id'];
        $balance = $this->playChgBalance($user, $this->params['amount'], $result['trans_id'], 7);
        $this->new_token['game_ref'] = $result['game_ref'];

        if(empty($result)){
            return $this->ggiBuildResponse(
                array(
                    'balance'            => $balance,
                    'exttransactionid'   => $extid,
                    'bonusbalance'       => 0
                )
            );
        }

        if($balance === false)
          return $this->ggiBuildError(6001, 'Database error, could not change balance on rollback');

        $this->doRollbackUpdate($mg_id, 'bets', $balance, $this->params['amount']);

      return $this->ggiBuildResponse(
        array(
          'balance'            => $balance,
          'exttransactionid'   => $extid,
          'bonusbalance'       => 0
        ));

    }

  function ggiWin($action = 'win', $award_type = 2){
    $this->game_action = 'win';
    $user     = $this->ggiGetUsr();
    $ext_id   = $this->getActionId($user); //abs($this->params['actionid']);
    $tr_id    = $this->params['gameid'];
    $amount   = $this->params['amount'];
    $tr_type  = in_array($award_type, array(2, 7)) ? $award_type : 2;
    if(is_string($user))
      return $user;
    $GLOBALS['mg_id'] = $ext_id;
    $result   = $this->getBetByMgId($ext_id, 'wins');
    if(empty($result)){
      $cur_game = $this->getCurGame($user);
      if(empty($cur_game))
        $cur_game = phive('MicroGames')->getByGameRef('microgaming_system');
      if (isset($this->params['freegame'])) {
        $be_id = $this->params['freegameofferid'].'-'.$this->params['freegameofferinstanceid'];
        $bonus_entry = phive('Bonuses')->getBonusEntryBy($user['id'], $be_id , 'ext_id', $this->params['freegameofferid']);
        if (!empty($bonus_entry) && $bonus_entry['frb_remaining'] > 0) {
            $this->frb_win = true;
            $award_type = 3;  // setting award type to 3 because win was from FS
        } else {
            // Retry of not existing fs win, we just send a positive response
            if(!empty($bonus_entry)) {
                phive('CasinoBonuses')->fail($bonus_entry['id'], "Freespin bonus {$bonus_entry['bonus_id']} failed by internal error.", $user['id']);
            }
            $balance  = $this->_getBalance();
            return $this->ggiBuildResponse(
              array(
                'balance'            => $balance,
                'exttransactionid'   => 0,
                'bonusbalance'       => 0
              ));
        }

          $this->logger->info('microgaming-freespin', [
                  'bonus_entry' => json_encode($bonus_entry, true),
                  'user_id' => $user['id'] 
          ]);
        
        $this->logger->info('microgaming-freespin', [
                'bonus_entry' => $bonus_entry,
                'user_id' => $user['id']
        ]);
        $this->dumpTst('microgaming-freespin', $bonus_entry, $user['id']);
      }
      $bonus_bet  = $this->bonusBetType();
      $balance = $this->_getBalance();
      $cur_game['ext_game_name'] = empty($cur_game['ext_game_name']) ? $this->params['gamereference'] : $cur_game['ext_game_name'];
      $win_id  = $this->insertWin($user, $cur_game, $balance, $tr_id, $amount, $bonus_bet, $ext_id, $award_type);
        if (!$win_id)
          return $this->ggiBuildError(6000, 'dberror');

        // TODO make a DDBB transaction with the whole process of inserting a win + round + change balance
        $round_ext_id = $this->getRoundExtId($cur_game, $this->params, cu($user['id']));
        $this->updateRound($user['id'], $round_ext_id, $win_id);

        $balance = !$this->frb_win ? $this->playChgBalance($user, $amount, '', $tr_type) : $this->_getBalance();
        if($balance === false)
            return $this->ggiBuildError(6000, 'dberror');

        if ($this->frb_win) {
            $be =  phive('Bonuses')->getBonusEntry($bonus_entry['id'], $user['id']);
            $this->handleFspinWin($be, $amount, $user['id'], 'Freespin win');
        }
      $balance = $this->handlePriorFail($user, $tr_id, $balance, $amount);
    }else{
      $win_id   = $result['id'];
      $balance  = $this->_getBalance();
    }
    return $this->ggiBuildResponse(
      array(
        'balance'            => $balance,
        'exttransactionid'   => $win_id,
        'bonusbalance'       => 0
      ));
  }


    /**
     * TODO the current-client redis var is not reliable, find a different solution /Ricardo
     *
     * @param $ud
     * @return mixed
     */
    function getGameByRef($ud)
    {
        $cur_game = phive('MicroGames')->getByGameRef($this->gref, phMget(mKey($ud, 'current-client')), $this->user);
        if (empty($cur_game)) {
            list($module_id, $client_id) = explode('_', $this->gref);
            if (is_numeric($module_id) && is_numeric($client_id)) {
                $cur_game = phive('SQL')->loadAssoc("SELECT * FROM micro_games WHERE module_id = $module_id AND client_id = $client_id");
            }
        }

        return $cur_game;
    }

  function ggiBet(){
    $tr_id = $this->params['gameid'];
    $user = $this->ggiGetUsr();
    $ext_id = $this->getActionId($user); //abs($this->params['actionid']);
    if(is_string($user))
      return $user;
    $result = $this->getBetByMgId($ext_id);
    if(!empty($result)){
      $balance = $this->_getBalance();
      $extid    = $result['id'];
    }else{
      $balance  = $this->_getBalance();
      $cur_game = $this->getCurGame($user);
      if(empty($cur_game))
        return $this->ggiBuildError(6511, 'nogame');
      $bet_amount = $this->params['amount'];
      $balance = $this->lgaMobileBalance($user, $cur_game['ext_game_name'], $balance, $cur_game['device_type'], $bet_amount);
      $jp_contrib = round($bet_amount * $cur_game['jackpot_contrib']);
      if($balance < $bet_amount)
        return $this->ggiBuildError(6503, 'lowbalance');
      $GLOBALS['mg_id'] = $ext_id;

        $balance = $this->playChgBalance($user, "-$bet_amount", $ext_id, 1);
        if($balance === false)
            return $this->ggiBuildError(6000, 'dberror');
        $bonus_bet = empty($this->bonus_bet) ? 0 : 1;

        $extid = $this->insertBet($user, $cur_game, $tr_id, $ext_id, $bet_amount, $jp_contrib, $bonus_bet, $balance);
        if(!$extid)
          return $this->ggiBuildError(6000, 'dberror');

        $round_ext_id = $this->getRoundExtId($cur_game, $this->params, cu($user['id']));
        $this->insertRound($user['id'], $extid, $round_ext_id);

        $balance = $this->betHandleBonuses($user, $cur_game, $bet_amount, $balance, $bonus_bet, $tr_id, $ext_id);
    }

    return $this->ggiBuildResponse(
      array(
        'balance'           => $balance,
        'exttransactionid'  => $extid,
        'bonusbalance'      => 0
      ));
  }

  function _getBalance(){
    return $this->getBalance(false);
  }

    function getBalance($as_string = true){
        return parent::getBalance($as_string);
    }


  function endGame(){
    $user = $this->getUsr();
    if(is_string($user))
      return $user;
    return $this->buildResponse(array('Balance' => $this->_getBalance()));
  }

    /**
     * TODO REMOVE this function Once NYX/Orion are refactored to use licSetting /Paolo
     * NYX done, only Orion left but do we even use Orion these days? /Henrik
     *
     * @param string $country
     * @return mixed|string
     */
    public function getOpId($country = '')
    {
        return $this->getMgConfig('op_id', $country);
    }

    /**
     * TODO REMOVE this function Once NYX is refactored to use licSetting /Paolo
     *
     * @param $key
     * @param $country
     * @return mixed|string
     */
    public function getMgConfig($key, $country)
    {
        if (empty($country)) {
            $country = cuCountry('country');
        }
        $country = strtoupper($country);
        $val = $this->getSetting("{$key}_{$country}");
        if (empty($val)) {
            return $this->getSetting($key);
        }
        return $val;
    }

    /**
     * @param $gref
     * @param $lang
     * @param $lobby_url
     * @param $game
     * @param array $args
     * @param false $show_demo
     * @return string
     */
    public function getMobilePlayUrl($gref, $lang, $lobby_url, $game, $args = [], $show_demo = false)
    {
        $user_obj = cu();

        if ($_SESSION['token_uid'] ?? false) {
            $game = phive('MicroGames')->overrideGameForTournaments($user_obj, $game);
        } else {
            $game = phive("MicroGames")->overrideGame($user_obj, $game);
        }

        $mob_game = phive('MicroGames')->getMobileGame($game);
        $lang = strpos($mob_game['languages'], $lang) !== false ? $lang : 'en';

        if (!empty($user_obj)) {
            $this->url_token = $this->insertToken($_SESSION['token_uid'], $gref);
        }

        if ($this->isTournament($_SESSION['token_uid'])) {
            $extra_params = [
                'lobbyurl' => 'lobbyIframeLaunch',
                'bankingURL' => 'bankingIframeLaunch'
            ];
        } else {
            $extra_params = [];
        }

        $launch_url = $this->getUrlCommon($user_obj, $game, $lang, $extra_params, $extra_params);

        $this->logger->info('microgaming-launch-url-mobile', ['url' => $launch_url, 'original_gref' => $gref]);
        $this->dumpTst('microgaming-launch-url-mobile', ['url' => $launch_url, 'original_gref' => $gref]);

        return $launch_url;
    }

    /**
     * @param $uid
     * @param $token
     * @return string
     */
    public function getPlayCheckUrl($uid, $token)
    {
        if (empty($token)) {
            $uuid = phive()->uuid();
            if (empty($uuid)) {
                return '';
            }
            phive("SQL")->sh($uid, '', 'playcheck_tokens')->insertArray('playcheck_tokens',
                array('token' => $uuid, 'user_id' => $uid));
        } else {
            $uuid = $token;
        }

        $sid = $this->getLicSetting('server_id', $uid);
        return "https://playcheck2.gameassists.co.uk/playcheck/default.asp?serverid=$sid&lang=en&usertoken=$uuid";
    }

    /**
     * @param $gid
     * @param $lang
     * @param null $game
     * @param false $show_demo
     * @return string
     */
    public function getDepUrl($gid, $lang, $game = null, $show_demo = false)
    {
        $user = cu();
        $original_game = phive('MicroGames')->getByGameId($gid, 0, null);
        $original_gref = $original_game['ext_game_name'];
        if ($_SESSION['token_uid'] ?? false) {
            $game = phive('MicroGames')->overrideGameForTournaments($user, $original_game);
        } else {
            $game = phive("MicroGames")->getGameOrOverrideByGid($gid, $user);
        }

        $lang = phive('MicroGames')->getGameLang($game, $lang);

        if (!empty($game['multi_channel'])) {
            $lobby_url = phive('UserHandler')->getSiteUrl();
            return $this->getMobilePlayUrl($original_gref, $lang, $lobby_url, $game, 1, $show_demo);
        }

        if (!empty($user)) {
            $this->url_token = $this->insertToken($_SESSION['token_uid'], $original_gref);
        }

        $launch_url = $this->getUrlCommon($user, $game, $lang);

        $this->logger->info('microgaming-launch-url', [
                'url' => $launch_url, 
            'original_gref' => $original_gref, 
            'original_gid' => $gid
        ]);
        
        $this->dumpTst('microgaming-launch-url', ['url' => $launch_url, 'original_gref' => $original_gref, 'original_gid' => $gid]);

        return $launch_url;
    }

    /**
     * @param $user
     * @param $game
     * @param null $lang In
     * @param array $extra_normal In case we need to add extra parameters for normal launch
     * @param array $extra_demo In case we need to add extra parameters for demo launch
     * @return string
     */
    private function getUrlCommon($user, $game, $lang = null, $extra_normal = [], $extra_demo = [])
    {
        if (empty($lang)) {
            $lang = phive('MicroGames')->getGameLang($game, $lang);
        }
        $base_url = $this->getLicSetting('base_url', $user);
        if (!empty($user)) {
            $parameters = [
                'applicationid' => $this->getLicSetting('application_id', $user),
                'serverid' => $this->getLicSetting('server_id', $user),
                'variant' => $this->getLicSetting('variant', $user),
                'gameid' => $game['game_id'],
                'authtoken' => $this->url_token['token'],
                'ul' => $lang
            ];
            // Spain require 1 extra param to display in game an extra button with game rounds info/history (with graphics)
            if($this->getLicSetting('enable_in_game_history')) {
                $parameters['showva'] = 'help,playcheck';
            }

            $launch_url = $base_url . http_build_query(array_merge($parameters, $extra_normal, $extra_demo));
        } else {
            $parameters = [
                'applicationid' => $this->getLicSetting('application_id', $user),
                'serverid' => $this->getLicSetting('demo_server_id', $user),
                'gameid' => $game['game_id'],
                'ul' => $lang,
                'playmode' => 'demo'
            ];
            $launch_url = $base_url . http_build_query(array_merge($parameters, $extra_normal, $extra_demo));
        }

        return $launch_url;
    }

  function fxJps($jps, $base_cur = 'EUR', $change = true){
    $cur = phive('Currencer');
    $ret = array();
    foreach($cur->getAllCurrencies() as $ciso => $c){
      foreach($jps as $jp){
        $jp['jp_value'] = $change ? $cur->changeMoney($base_cur, $ciso, $jp['jp_value']) : $jp['jp_value'];
        $jp['currency'] = $ciso;
        $ret[] = $jp;
      }
    }
    return $ret;
  }

    function parseJackpots()
    {
        $jur_jp_urls = $this->getAllJurSettingsByKey('jp_url');

        $games_by_module_id = phive('SQL')->load1DArr(
                "SELECT * FROM micro_games WHERE network = 'microgaming' AND module_id != '' AND active = 1 GROUP BY module_id",
                'game_id', 'module_id');

        foreach ($jur_jp_urls as $jur => $jp_url) {
            $arr  = json_decode(file_get_contents($jp_url), true);
            $curs = array_keys(phive('Currencer')->getAllCurrencies(false));


            foreach($arr as $row){
                $module_id = $row['moduleId'];
                if(!in_array($row['currencyIsoCode'], $curs) || !key_exists($row['moduleId'], $games_by_module_id))
                    continue;

                $game_id = $games_by_module_id[$module_id];

                $ret[] = [
                    'module_id'     => $row['moduleId'],
                    'currency'      => $row['currencyIsoCode'],
                    'jp_name'       => $row['friendlyName'],
                    'jp_id'         => "mg_{$row['moduleId']}_{$row['progressiveId']}",
                    'jp_value'      => round($row['endAtValue'] * 100),
                    'game_id'       => $game_id,
                    'jurisdiction'  => $jur
                ];
            }
        }

        return $ret;
    }

    /**
     * Gets a new authorization token to use on the following communication
     *  with the API
     * @param DBUser $user
     */
    public function refreshFsToken($user)
    {
        $request_body = [
            'APIKey' => $this->getLicSetting('token_key', $user)
        ];

        $url = $this->getLicSetting('gen_op_token', $user);
        $gen_op_token_res = $this->requestFsEndpoint($url, $user, true, $request_body, false);

        if (isset($gen_op_token_res['AccessToken'])){
            $this->setFsToken($gen_op_token_res['AccessToken']);
        }else {
            $this->logger->warning('quickfire_error', ['Error Freespin APIKey or server not available']);
            phive()->dumpTbl('quickfire_error', 'Error Freespin APIKey or server not available');
        }

    }

    /**
     * Sends POST and GET request to a json API including an auth bearer token
     * This method is used Microgaming user and freespin API requires authentication on most method
     * calls
     *
     * @param string $url
     * @param DBUser $user
     * @param bool $is_post true we post false we get
     * @param array $request_body
     * @param bool $auth if the endpoint requires authentication
     * @return mixed
     */
    private function requestFsEndpoint($url, $user, $is_post = true, $request_body = [], $auth = true, $is_patch = false)
    {
        $aut_header = $auth ? "Authorization: Bearer " . $this->getFsToken($user) . "\r\n" : '';

        $debug_key = $this->getSetting('debug_fs_requests') ? 'microgaming-fs-curl' : '';
        if($is_post) {
            $res = phive()->post($url, json_encode($request_body), 'application/json', $aut_header, $debug_key, 'POST', 10);
            $this->logger->debug('Post response', ['response' => json_encode($res, true)]);
        } else if ($is_patch) {
            $res = phive()->post($url, json_encode($request_body), 'application/json', $aut_header, $debug_key, 'PATCH', 10);
            $this->logger->debug('Patch response', ['response' => json_encode($res, true)]);    
        } else {
            $res = phive()->get($url, 10, $aut_header, $debug_key);
            $this->logger->debug('get response', ['response' => json_encode($res, true)]);
        }

        return !empty($res) ? json_decode($res, true) : false;
    }

    /**
     * Set's the bearer token needed for each authorized request to the endpoint
     *
     * @param string fs_token is the bearer token
     */
    public function setFsToken($fs_token) {
        $this->fs_token = $fs_token;
    }

    /**
     * Get's the bearer token needed for each authorized request to the endpoint
     *
     * @param DBUser $user
     *
     * @return string fs_token is the bearer token
     */
    public function getFsToken($user) {
        if (empty($this->fs_token)){
            $this->refreshFsToken($user);
        }
        return $this->fs_token;
    }

    /**
     * Builds the URL for the endpoint. It gets the base url that may be
     * different for each licence and inserts the necessary uri parameters
     *
     * @param         $user
     * @param string  $method api method
     * @param array   $uri_params parts to compose the request uri
     *
     * @return string  url of the endpoint
     */
    public function getFsUrl($user, $method, $uri_params = [])
    {
        $base_url = $this->getLicSetting('freegames_base_url', $user);
        $uri      = $this->fs_api[$method];

        if(!empty($uri_params)) {
            $uri = str_replace(['%0', '%1', '%2', '%3'], $uri_params, $uri);
        }

        return $base_url.$uri;
    }

    /**
     * Tries to find the user in mg back office and if doesn't exists
     * it registers it as a new player
     *
     * @param DBUser
     * @returns mixed    mg back office user_id or false if failed
     * @return false|mixed
     */
    public function getExtUserId($user)
    {
        return $this->checkExtUser($user) ?: $this->registerExtUser($user);
    }

     /**
      * Checks if the player exists in Microgaming back office
     *
     * @param  DBUser  $user
     * @return mixed   userId in mg back office or false if it doesn't exists
     */
    public function checkExtUser($user)
    {
        $request_body = [
            'productId' => $this->getProductId($user),
            'username'  => $this->getExtUsername($user->getData()),
        ];

        $this->logger->debug(__METHOD__, ['request_body' => json_encode($request_body, true)]);
        
        $url = $this->getFsUrl($user,'check_user_exists_uri');
        $extUser = $this->requestFsEndpoint($url, $user, true, $request_body);

        return $extUser['userId'] ?? false;
    }

    /**
     * Creates a user(player) in Quickfire back office. This allows us to
     * dynamically add players to the Quickfire platform without waiting
     * them to open the game. This enables to configure bet settings,
     * award free games or place them in different user groups.
     *
     * @param  DBUser $user
     * @return mixed  the user id in mg back office if registered or false
     */
    public function registerExtUser($user)
    {
        $user_data = $user->getData();
        $ext_username = $this->getExtUsername($user_data);
        $request_body = [
            "registeredProductId" => $this->getProductId($user),
            "username"            => $ext_username,
            "password"            => $user_data['id'] . '>T}Fk,/',
            "userTypeId"          => 0,
            "currencyIsoCode"     => strtoupper($this->getPlayCurrency($user_data)),
            "countryLongCode"     => phive('Localizer')->getCountryIso3FromIso2($user->data['country']) ?? 'MLT',
            "idempotencyId"       => phive()->uuid()
        ];
        
        $this->logger->debug(__METHOD__, ['request_body' => json_encode($request_body, true)]);
        $url = $this->getFsUrl($user,'register_user_uri');
        $response = $this->requestFsEndpoint($url, $user, true, $request_body);

        return $response['userId'] ?? false;
    }

    /**
     * Method called when the user clicks on delete freespin, we need to inform the provider so it doesn't show this info to the user
     * @param $user_id
     * @param $ext_id
     */
    public function cancelFRBonus($user_id, $ext_id)
    {
        $user = cu($user_id);
        list($offer_id, $instance_id) = explode('-', $ext_id);
        if (!empty($offer_id)) {
            $ext_user_id = $this->getExtUserId($user);
            $url = $this->getFsUrl($user, 'remove_user_fs_game_uri', [$this->getProductId($user), $ext_user_id, $offer_id, $instance_id]);
            $request_body = [
                "idempotencyId" => phive()->uuid(),
                "freeGameStatusId" => 4  // OperatorCancelledOffer
            ];
            $result = $this->requestFsEndpoint($url, $user, false, $request_body, true, true);
            if (!empty($result)) {
                $this->logger->warning('quickfire_error_forfeitFreespin', ['result' => $result]);
                phive()->dumpTbl('quickfire_error_forfeitFreespin', $result);
            }
        }
    }

    /**
     * Makes a free game offer available to a user. A user can be awarded
     * the same free game offer multiple times
     *
     * @param         $ext_uid
     * @param DBUser  $user
     * @param array   $entry
     * @param         $offer_id
     *
     * @return mixed  the Id of the instance for the user in the offer or false
     */
    public function addUserToFsOffer($ext_uid, $user, $entry, $offer_id)
    {
        $request_body = [
            "idempotencyId"             =>  phive()->uuid(),
            "offerAvailableFromUtcDate" =>  phive()->modTz('UTC', $entry['start_date'], DateTime::ATOM)
        ];

        $this->logger->debug(__METHOD__, ['request_body' => json_encode($request_body, true)]);
        
        $url = $this->getFsUrl($user,'add_user_fs_game_uri', [$this->getProductId($user), $ext_uid, $offer_id]);
        $response = $this->requestFsEndpoint($url, $user, true, $request_body);

        if (isset($response['instanceId'])){
          return $response['offerId'].'-'.$response['instanceId'];
        }
        return false;
    }


    /**
     * Assigns a offer to a user and stores a reference to the external
     * offer-instance into bonus_entries ext_id column that will be used
     * later to match the freespin wins
     *
     * @param   $uid
     * @param string $offers From bonus_types table ext_id column. OfferID in Quickspin backoffice for each country
     *                              The format has to be like MT:123|DE:678|SV:555
     * @param   $rounds
     * @param   $bonus_name
     * @param array $entry
     *
     * @return bool|ext_id    What will be saved on ext_id column in bonus_entries
     */
    function awardFRBonus($uid, string $offers, $rounds, $bonus_name, array $entry) {
         $user = cu($uid);
         $userJurisdiction = licJurOrCountry($user);
         $offers = explode("|",$offers);
         $rowOffer = '';
         foreach ($offers as $offerWithCountry) {
             if (strpos($offerWithCountry, $userJurisdiction) !== false) {
                 $offer_id = explode(":", $offerWithCountry)[1];
                 break;
             }
             if (strpos($offerWithCountry, 'ROW') !== false){
                 $rowOffer = explode(":", $offerWithCountry)[1];
             }
         }
         if (empty($offer_id) && !empty($rowOffer)){
             $offer_id = $rowOffer;
         }

         if (empty($offer_id) || empty($ext_user_id = $this->getExtUserId($user)) || !$this->getFsOfferById($user, $offer_id)) {
             $this->logger->warning('quickfire-awardFRBonus-error-1',
                 [
                     $ext_user_id,
                     $offer_id, $this->getFsOfferById($user, $offer_id)
                 ]
             );

             phive()->dumpTbl('quickfire-awardFRBonus-error-1', [$ext_user_id, $offer_id, $this->getFsOfferById($user, $offer_id)]);
             return false;
         }
         return $this->addUserToFsOffer($ext_user_id, $user, $entry, $offer_id);
    }

    /**
     * Check if offer exists in quickfire backoffice
     *
     * @param DBUser  $user
     * @param   int   $offer_id   From bonus_types table ext_id column. OfferID in Quickspin backoffice
     * @return array   Get offer by offer_id from Microgaming
     *                 This offer_id is stored in bonus_types and bonus_entry
     */
    public function getFsOfferById($user, $offer_id)
    {
        $url = $this->getFsUrl($user,'get_offer_by_id', [$this->getProductId($user), $offer_id]);
        return $this->requestFsEndpoint($url, $user, false, []);
    }

    /**
     * Get the product if for the player jurisdiction
     *
     * @param DBUser  $user
     * @return string Server Id corresponding for the user license
     */
    private function getProductId($user)
    {
        return $this->getLicSetting('server_id', $user->getId());
    }

    /**
     * Username to register and login the user in quickfire
     *
     * @param array  $user_data
     * @return string
     */
    private function getExtUsername($user_data)
    {
        if(!empty($this->t_entry) ){
            $login_name = $this->mkUsrId($user_data['id']);
        }else {
            $login_name = strpos($user_data['username'],'@') !== false ? $user_data['id'] : $user_data['username'];
        }
        return $login_name;
    }

    /**
     * @param DBUser $user
     * @param string|null $game_ref
     * @return mixed|string|null
     */
    private function getOriginalGameRef(DBUser $user, string $game_ref = null)
    {
        if ($this->isTournamentMode()) {
            $iso = $this->getLicSetting('bos-country', $user);
            if (!empty($iso)) {
                $gco = phive('MicroGames')->getGameCountryOverrideByOverride($user, $game_ref, $iso, true);
                if (!empty($gco)) {
                    $game = phive('MicroGames')->getById($gco['game_id']);
                    return $game['ext_game_name'];
                }
            }
            return $game_ref;
        }

        return phive('MicroGames')->getOriginalRefIfOverridden($game_ref, $user);
    }

    /**
     * This function will return the `ext_round_id` to insert in the table rounds,
     * the value should look like `mg-{ext_game_name}-{userid}-{gameid}`.
     *
     * mg: Prefix for Microgaming.
     * ext_game_name: Gp id of the game.
     * user_id: User related to the round.
     * game_id: The gameid attribute identifies the player’s game round. The
     *   gameid is used to link the various game events, such as bets and
     *   wins, to a single game round.
     *
     * Example:
     *
     * ext_round_id = mg-MGS_legacyOfOzV94Desktop-9229376714-542
     *
     * @param array $game
     * @param $request
     * @param object $user
     * @return string
     */
    private function getRoundExtId(array $game, $request, object $user) {
        if (empty($game) || empty($request['gameid']) || empty($user)) {
            return "mg-{$this->params['gameid']}";
        }

        return "mg-{$game['ext_game_name']}-{$user->getId()}-{$request['gameid']}";
    }

    /**
     * @param $user
     * @return mixed
     */
    public function getCurGame($user)
    {
        $game_ref = phive('MicroGames')->getOriginalRefIfOverridden($this->params['gamereference'], $user);
        return phive('MicroGames')->getByGameRef($game_ref);
    }
}
