<?php

require_once __DIR__ . '/Gp.php';

class Skywind extends Gp
{
    ###############################################################
    ## SKYWIND HAS NO PROMOTIONAL FRB IMPLEMENTED YET 19-01-2018 ##
    ###############################################################
    
    /**
     * Name of GP. Used to prefix the bets|wins::game_ref (which is the game ID) and bets|wins::mg_id (which is
     * transaction ID)
     *
     * @var string
     */
    protected $_m_sGpName = __CLASS__;
    
    /**
     * Find a bet by transaction ID or by round ID.
     * Mainly when a win comes in to check if there is a corresponding bet. If the transaction ID used for win is the
     * same as bet set to false otherwise true. Default true. Make sure that the round ID send by GP is an integer
     * Confirmed by GP Skywind, 30-11-2017 4:45PM bet txnid === win txnid? Yes, it is the same ID. (skype)
     * @var boolean
     */
    protected $_m_bByRoundId = true;
    
    /**
     * Is the bonus_entries::balance updated after each FRB request with the FRW or does the GP keeps track and send
     * the total winnings at the end of the free rounds. Default: true (balance is updated per bet)
     */
    protected $_m_bFrwSendPerBet = true;
    
    /**
     * Update the status in bonus_entries table by the last FRW request from the GP in the _win() method. Default: true.
     * If set to false make sure that $this->_handleFspinWin() is called from the extended class.
     *
     * @var bool
     */
    protected $_m_bUpdateBonusEntriesStatusByWinRequest = true;
    
    /**
     * Insert frb into bet table so in case a frw comes in we can check if it has a matching frb
     *
     * @var bool
     */
    protected $_m_bConfirmFrbBet = true;
    
    /**
     * Check if incoming win request has a valid bet transaction.
     * Note: this is NOT for freespins but for real money play
     * IMPORTANT: we can not set to true as the game will hang as soon Skywind starts an ingame freespins one by one.
     * eg bets/wins with amount 0 which our system does not accept if its not a freespin given by us.
     *
     * @var bool
     */
    protected $_m_bConfirmBet = false;
    
    /**
     * The header content type for the response to the GP
     *
     * @var string
     */
    protected $_m_sHttpContentType = Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON;
    
    /**
     * Do we force respond with a 200 OK response to the GP
     *
     * @var bool
     */
    protected $_m_bForceHttpOkResponse = true;

    /**
     * Specific logger channel for Skywind
     *
     * @var string
     */
    protected string $logger_name = 'skywind';

    /**
     * The type of the current free spins request (two possible values):
     *
     * normal: free spins given by videoslots to a user.
     * freegame or bonusgame: free spins given at any moment during gameplay by GP.
     *
     * Detect for freespin either ingame or given by videoslots.
     *
     * Skype clarification:
     * a) event_type: bet, game_type: normal - regular game, simple spin
     * b) event_type: bet, game_type: freegame/bonusgame - when during regular game player got free spins or bonus game
     * c) event_type: free-bet, game_type: normal - regular free bet, as described in documentation
     * d) event_type: free-bet, game_type: freegame/bonusgame - situation, when during free-bets player got in-game
     * freegames/bonusgame. At this moment you should not charge free bets, and think of this game as a free-spins
     * (case b.) when this in-game freegame will end - free bets (marketing one) should continue
     * (in order to handle this case, you need to return amount of free bets during free spins/bonus games
     * for each request) to test these situations open a game on staging and notice the blue and red dot in the
     * bottom right corner to trigger ingame freespins/bonuses during play which makes testing faster.
     *
     * @var string
     */
    private string $gameType = '';

    /**
     * The event of the current request (4 possible values):
     *
     * bet: normal gameplay bet (also it is used for ingame freespins)
     * win: normal gameplay win (also it is used for ingame freespins)
     * free-bet: free spins gameplay bet (give by videoslots)
     * free-bet-win: free spins gameplay win (give by videoslots)
     *
     * @var string
     */
    private string $eventType = '';

    /**
     * Array with all possible response errors to GP
     *
     * @example
     * 'ER05' => array (
     *   'responsecode' => 200, // used to send header to GP if not enforced 200 OK
     *   'status' => 'REJECTED', // used to send header to GP if not enforced 200 OK
     *   'return' => 'default', // if not string 'default' it will response this to GP
     *   'code' => 'ER01', // set this to whatever error-code the GP wants to receive
     *   'message' => 'Duplicate Transaction ID.' // set this to whatever error-message the GP wants to receive
     * )
     * @var array
     */
    private $_m_aErrors = array(
        'ER05' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => true,
            'code' => 1,
            'message' => 'Duplicate Transaction ID.'
        ),
        'ER06' => array(
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => -4,
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        'ER09' => array(
            'responsecode' => 200,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => -2,
            'message' => 'Player not found.'
        ),
        'ER11' => array(
            'responsecode' => 200,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => -3,
            'message' => 'Token not found.'
        ),
        'ER12' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_NO_REMAINING',
            'return' => 'default',
            'code' => -5,
            'message' => 'No freespins remaining.'
        ),
        'ER18' => array(
            'responsecode' => 200,
            'status' => 'IDEMPOTENCE',
            'return' => 'default',
            'code' => 1,
            'message' => 'Duplicate Transaction ID with same amount does exist already.'
        ),
        'ER39' => array(
            'responsecode' => 200,
            'status' => 'IDEMPOTENCE',
            'return' => 'default',
            'code' => 1,
            'message' => 'Duplicate Transaction ID with same amount does exist already.'
        ),
    );
    
    private $_m_aMethodsMappedApi = array(
        'get_balance' => '_balance',
        'debit' => '_bet',
        'credit' => '_win',
        'rollback' => '_cancel',
        'validate_ticket' => '_init',
        'get_player' => '_playerInfo',
        'get_free_bet' => '_free_bet',
    );
    
    private $_m_sToken = '';

    /**
     * By default we don't confirm by round id
     * @return bool
     */
    public function doConfirmByRoundId() {
        return false;
    }
    
    /**
     * Set the defaults
     * Separate function so it can be called also from the classes that extend TestGp class
     *
     * @return Gp
     */
    public function setDefaults()
    {
        $this
            ->_mapGpMethods($this->_m_aMethodsMappedApi)
            ->_whiteListGpIps($this->getSetting('whitelisted_ips'))
            ->_overruleErrors($this->_m_aErrors)
            ->_supportInhouseFrb($this->_m_sGpName)
            ->_checkDeclaredProperties()
            ->_setWalletActions();
        
        return $this;
    }
    
    /**
     * Pre process data received from GP
     *
     * @return object
     */
    public function preProcess()
    {
        $this->setDefaults();

        $oData = json_decode(json_encode($_POST), false);
        $this->_setGpParams($oData);

        if ($oData === null) {
            // request is unknown
            $this->_logIt([__METHOD__, 'unknown request']);
            $this->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }
        
        $aJson = $aAction = array();
        $casinoMethod = null;

        // Define which service is requested
        $casinoMethod = $_GET['action'];

        if (empty($casinoMethod)) {
            // method to execute not found
            $this->_setResponseHeaders($this->_getError(self::ER02));
            $this->_logIt([__METHOD__, 'method not found']);
            die();
        } else {
            $this->_setGpMethod($casinoMethod);
        }
        
        $mSessionData = null;
        if (isset($oData->ticket) || isset($oData->cust_session_id)) {
            $mSessionData = $this->fromSession((isset($oData->ticket) ? $oData->ticket : $oData->cust_session_id));
            //$this->_m_sToken = $oData->ticket;
            $this->_m_sToken = isset($oData->ticket) ? $oData->ticket : $oData->cust_session_id;
        }
        
        if(!empty($mSessionData) && isset($oData->game_code) && $oData->game_code !== $this->stripPrefix($mSessionData->gameid)){
            // multiple session issue which is not supported by GP
            // it prevent bets under the wrong gameid and a session reload
            $this->_logIt([__METHOD__, 'Not supported multi-session killed.']);
            $this->deleteToken($oData->cust_session_id);
            $this->_response($this->_getError(self::ER11));
        }
             
        if (isset($mSessionData->userid)) {
            $aJson['playerid'] = $mSessionData->userid;
            $this->_logIt([__METHOD__, 'UID by session', print_r($mSessionData, true)]);
        }  elseif(isset($oData->cust_id)) {
            $aJson['playerid'] = $oData->cust_id;
            $this->_logIt([__METHOD__, 'UID by cust_id', print_r($oData->cust_id, true)]);
        } elseif($casinoMethod !== 'rollback') {
            $this->_setResponseHeaders($this->_getError(self::ER09));
            $this->_logIt([__METHOD__, 'UID not found.']);
            die();
        }
        if ($aJson['playerid'] ?? false) {
            $suffix = $this->getSetting('brand_username_suffix', '');
            $aJson['playerid'] = preg_replace("/{$suffix}\$/", '', $aJson['playerid']);
        }

        if (isset($mSessionData->gameid)) {
            $aJson['skinid'] = $this->stripPrefix($mSessionData->gameid);
            $this->_logIt([__METHOD__, 'GID by session', print_r($mSessionData, true)]);
        } else {
            if (isset($oData->game_code)) {
                $aJson['skinid'] = $oData->game_code;
                $this->_logIt([__METHOD__, 'GID by $oData->params->information->game', $oData->game_code]);
            } else {
                $aJson['skinid'] = '';
                $this->_logIt([__METHOD__, 'GID not found']);
                if($casinoMethod === 'get_balance'){
                    // gameid is required to include bonus balance correctly
                    // will enter this condition when multi session is tried to be played and dsession got deleted because of it
                    $this->_logIt([__METHOD__, 'Can not include bonus balance in balance request because of missing GameId.']);
                    $this->deleteToken($oData->cust_session_id);
                    $this->_response($this->_getError(self::ER11));
                }
            }
        }

        

        if(isset($oData->currency_code)){
            $aJson['currency'] = $oData->currency_code;
        }
        
        if(isset($oData->platform)){
            $aJson['target'] = (($oData->platform === 'mobile') ? 'mobile' : 'desktop');
        }
        
        if(isset($oData->merch_id)){
            $aJson['hash'] = $this->getHash($oData->merch_id.$oData->merch_pwd, self::ENCRYPTION_SHA1);
        }

        $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
        $aJson['state'] = 'single';
        
        if (in_array($casinoMethod, array('debit','credit','rollback'))) {
            $aAction[0]['parameters'] = array();
            if(isset($oData->amount)) {
                // rollback doesnt have amount set so we don't want to check it either in the _cancel method
                $aAction[0]['parameters']['amount'] = $this->convertFromToCoinage($oData->amount, self::COINAGE_UNITS, self::COINAGE_CENTS);
            }
            $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
            $aAction[0]['parameters']['transactionid'] = $oData->trx_id;
            $aAction[0]['parameters']['roundid'] = $oData->game_id;
            if(isset($oData->event_type) && $oData->event_type === 'jp-win' && $casinoMethod === 'credit') {
                $aAction[0]['parameters']['jpw'] = 1;
            }
        }
        $aJson['action'] = $aAction[0];    
        
        // detect for freespin either ingame or given by vs
        if ( isset($oData->event_type) ) {
    
            // Skype clarification:
            // a) event_type: bet, game_type: normal - regular game, simple spin
            // b) event_type: bet, game_type: freegame/bonusgame - when during regular game player got free spins or bonus game
            // c) event_type: free-bet, game_type: normal - regular free bet, as described in documentation
            // d) event_type: free-bet, game_type: freegame/bonusgame - situation, when during free-bets player got in-game freegames/bonusgame. At this moment you shout not charge free bets,
            // and think of this game as a free-spins (case b.) when this in-game freegame will end - free bets (marketing one) should continue
            // (in order to handle this case, you need to return amount of free bets during free spins/bonus games for each request)
            // to test these situations open a game on staging and notice the blue and red dot in the bottom right corner to trigger ingame freespins/bonusses during play which makes testing faster.
            
            if (in_array($oData->event_type, array('free-bet', 'free-bet-win'))) {
                // it's a VS wallet freespin
                $aJson['freespin'] = array(
                    'id' => $oData->event_id
                );
            }

            /** @see Skywind::$gameType */
            if (in_array($oData->event_type, ['free-bet', 'free-bet-win'])) {
                $this->setFreeSpinType($oData->event_type, $oData->game_type);
            }
        }

        if ($casinoMethod === 'get_free_bet') {
            $this->setFreeSpinType('get_free_bet', 'normal');
        }
        
        $this->_m_oRequest = json_decode(json_encode($aJson), false); 
        return $this;
    }
    
    /**
     * Execute the requested command from gaming provider
     * @return void
     */
    public function exec()
    {

        $mResponse = false;
        $userId = $this->_m_oRequest->playerid;
    
        if ($this->_m_oRequest->hash === $this->getHash($this->getLicSetting('operator', $userId).$this->getLicSetting('secretkey', $userId), self::ENCRYPTION_SHA1)) {
            // check if the commands requested do exist
            $this->_setActions();

            $u_obj = cu($userId);

            // Set the game data by the received skinid (is gameid)
            if (isset($this->_m_oRequest->skinid)) {
                $this->_setGameData();

                $freeRoundBonus = $this->getFreeRoundBonus($u_obj, $this->_getGameData());
                if (!empty($freeRoundBonus)) {
                    $this->_m_oRequest->freespin->id = $freeRoundBonus['id'];
                }
            }

            if (empty($this->_m_sSessionData->ext_session_id)) {
              $this->setNewExternalSession($u_obj, $this->_m_sSessionData);
            }
            
            // execute all commands
            foreach ($this->_getActions() as $key => $oAction) {
                
                // Update the user data before each command
                if (isset($this->_m_oRequest->playerid) && !is_array($this->_m_oRequest->playerid)) {
                    $this->_setUserData();
                }
                
                $sMethod = $oAction->command;
                $this->_setWalletMethod($sMethod);
                
                // command call return either an array with errors or true on success
                if (property_exists($oAction, 'parameters')) {
                    $mResponse = $this->$sMethod($oAction->parameters);
                    
                } else {
                    $mResponse = $this->$sMethod();
                }
                if ($mResponse !== true) {
                    // some error occurred
                    break;
                }
            }
		} else {
			// secret key not valid
			$mResponse = $this->_getError(self::ER03);
		}
        
        // Update the user data after each command
        if (isset($this->_m_oRequest->playerid) && !is_array($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }
        $this->_logIt([__METHOD__, print_r($mResponse, true)]);
        $this->_response($mResponse);
    }
    
    protected function _response($p_mResponse)
    {
        $aResp = array('error_code' => 0);
        
        if ($p_mResponse === true) {
            
            $aUserData = $this->_getUserData();
            
            switch($this->getGpMethod()){
                
                case 'get_balance':
                    $aResp['balance'] = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS);
                    $aResp['currency_code'] = strtoupper($this->getPlayCurrency($aUserData));
                    if ($this->_isFreespin()) {
                        $aResp['free_bet_count'] = $this->_getFreespinData('frb_remaining');
                    }
                    break;
                    
                case 'get_player':
                    $aResp['cust_id'] = (int)$aUserData['id'];
                    //$aResp['game_group'] = ''; // seems optional
                    $aResp['currency_code'] = strtoupper($this->getPlayCurrency($aUserData));
                    $aResp['language'] = $aUserData['preferred_lang'];
                    $aResp['country'] = $aUserData['country'];
                    $aResp['test_cust'] = (in_array($aUserData['username'],$this->_m_aTestAccounts) ? true : false);
                    break;
                    
                case 'validate_ticket':
                    $user = cu($aUserData['id']);

                    $aResp['cust_id'] = (int)$aUserData['id'];
                    $aResp['cust_session_id'] = $this->_m_sToken;
                    $aResp['currency_code'] = strtoupper($this->getPlayCurrency($aUserData));
                    $aResp['language'] = $aUserData['preferred_lang'];
                    $aResp['country'] = $aUserData['country'];
                    $aResp['test_cust'] = (in_array($aUserData['username'],$this->_m_aTestAccounts) ? true : false);

                    $maxBetLimit = phive('Gpr')->getMaxBetLimit($user);
                    if (!empty($maxBetLimit)) {
                        $aResp['max_total_bet'] = $maxBetLimit;
                    }
                    break;
                case 'get_free_bet':
                    $aResp['free_bet_count'] = $this->_getFreespinData('frb_remaining');
                    $aResp['free_bet_coin'] = $this->_getFreespinData('frb_denomination');
                    break;
                case 'debit':
                case 'credit':
                case 'rollback':
                    $aTxnId = array();
                    if($this->getGpMethod() === 'rollback'){
                        $aTxnId[0] = $this->_getTransaction('txn',self::TRANSACTION_TABLE_BETS);
                        $aTxnId[1] = $this->_getTransaction('txn',self::TRANSACTION_TABLE_WINS);
                    } else {
                        $aTxnId[0] = $this->_getTransaction('txn');
                    }
                
                    $sTxnId =  implode('-', array_filter($aTxnId));
                    $aResp['trx_id'] = (($this->_isFreespin() || empty($sTxnId)) ? 'a'.$this->randomNumber(16) : $sTxnId);
                    $aResp['balance'] = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS);
                    if ($this->_isFreespin()) {
                        $aResp['free_bet_count'] = $this->_getFreespinData('frb_remaining');
                    }
                    break;
            }
            
        } else {
            $aResp['responsecode'] = ((strpos($p_mResponse['code'], 'ER') !== false) ? 400 : $p_mResponse['responsecode']);
            $aResp['error_code'] = ((strpos($p_mResponse['code'], 'ER') !== false) ? (int)'4' . str_replace('ER', '', $p_mResponse['code']) : $p_mResponse['code']);
            $aResp['error_msg'] = $p_mResponse['message'];
        }
        
        $this->_setResponseHeaders($p_mResponse);

        if ($aResp['cust_id'] ?? false) {
            $aResp['cust_id'] .= $this->getSetting('brand_username_suffix', '');
        }

        $result = json_encode($aResp);
        $this->logger->debug(__METHOD__, [
            'method' => $this->getGpMethod(),
            'response' => $result
        ]);
        echo $result;
        
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $p_mGameId The game_ext_name from the microgames table without the prefix so basically the game ID as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @param bool $show_demo
     * @return string The url to open the game
     */
    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        
        $aUrl = array();

        //$aUrl['merchantCode'] = $this->getSetting('operator');
        $aUrl['gameCode'] = $p_mGameId;

        if (isLogged()) {
            $ud = cuPl()->data;
            $user_id = $ud['id'] . $this->getSetting('brand_username_suffix', '');
            $aUrl['playmode'] = 'real';
            $aUrl['ip'] = $ud['cur_ip'];
            $aUrl['ticket'] = $this->getGuidv4($user_id);
            $aUrl['language'] = $ud['preferred_lang'];
            $aUrl['merch_login_url'] = $this->getLicSetting('cashier_url');
            $this->toSession($aUrl['ticket'], $user_id, $p_mGameId, $p_sTarget);
            $launchurl = $this->_getPlayerGameUrl($aUrl['ticket'], $user_id, $aUrl['gameCode'], $aUrl['playmode']);
        } else {
            $aUrl['language'] = cLang();
            $aUrl['ip'] = remIp();
            $launchurl = $this->_getGameUrl($aUrl['gameCode']);
        }

        $launchurl .= '&modules=swmp';

        $this->logger->debug(__METHOD__, [
            'launch_url' => !empty($launchurl) ? 'generated successfully' : null,
            'user_id' => $user_id ?? null,
            'parameters' => $aUrl,
        ]);

        return $launchurl;
    }
    
    
    
    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $ticket The ticket generated
     * @param string $playerId The player code
     * @param string $gameId The code of the game
     * @param string $playMode fun|real
     * @return string The url to open the game
     */
    function _getPlayerGameUrl($ticket, $playerId, $gameId, $playMode)
    {
        $oData = $this->getLoginData();
       
        $url = phive()->post(
            $this->getLicSetting('getlaunchurl') . '/v1/players/'.$playerId.'/games/'.$gameId.'?ticket='.$ticket.'&playmode='.$playMode.'&language=en',
                            '',
                            '',
                            "X-ACCESS-TOKEN:" . $oData->accessToken,
                            //"Cache-Control: no-cache",
                            $this->getGamePrefix() . 'out',
                            'GET'
                         );
       
        $oUrlData = json_decode($url, false);
        $oUrl = $oUrlData->url;
        return $oUrl;
    }
    
 
    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $gameId The code of the game
     * @return string The url to open the game
     */
    function _getGameUrl($gameId)
    {
        $oData = $this->getLoginData();
        
        $url = phive()->post(
            $this->getLicSetting('getlaunchurl') .'/v1/fun/games/'.$gameId,
            '',
            '',
            "X-ACCESS-TOKEN:" . $oData->accessToken,
            //"Cache-Control: no-cache",
            $this->getGamePrefix() . 'out',
            'GET'
            );
        
        $oUrlData = json_decode($url, false);
        $oUrl = $oUrlData->url;
        return $oUrl;
    }


    /**
     * Get the login token required to launch the games
     *
     */
    function getLoginData(){
        $data = array(
            'secretKey'=> $this->getLicSetting('api_secret_key'),
            'username'=> $this->getLicSetting('api_username'),
            'password'=> $this->getLicSetting('api_password')
        );

        $login = phive()->post(
            $this->getLicSetting('getlaunchurl') . '/v1/login',
            json_encode($data),
            self::HTTP_CONTENT_TYPE_APPLICATION_JSON,
            "Cache-Control: no-cache",
            $this->getGamePrefix() . 'out',
            'POST'
        );

        return json_decode($login, false);
    }

    protected function _playerInfo(){
        return true;
    }

    /**
     * This method is used to inform about free spins rounds to Gp.
     *
     * When the user initialize the game, we should send the quantity of free spins for that specific user in the
     * get_balance response.
     *
     * After that, Gp will send to us a request to the method get_free_bet. So, we should reply with the quantity
     * of free spins again and the free bet coin.
     *
     * @return bool true on success
     */
    protected function _free_bet(): bool
    {
        return true;
    }

    /**
     *  @see Skywind::$gameType
     *
     * Sets the type of the free spin. @param string $eventType
     * @param string $gameType
     * @return void
     *
     */
    private function setFreeSpinType(string $eventType, string $gameType): void
    {
        if (!in_array($eventType, ['bet', 'win', 'free-bet', 'free-bet-win', 'get_free_bet'])) {
            return;
        }

        if (!in_array($gameType, ['normal', 'freegame', 'bonusgame'])) {
            return;
        }

        $this->eventType = $eventType;
        $this->gameType = $gameType;
    }

    /**
     * Returns if free spin type is ingame (freegame or bonusgame).
     *
     * @return bool
     */
    protected function isIngameFreeSpin(): bool
    {
        return (in_array($this->gameType, ['freegame', 'bonusgame']) && in_array($this->eventType, ['bet', 'win']));
    }

    /**
     * Returns if free spin type is normal (given by videoslots to the user).
     *
     * @return bool
     */
    protected function isNormalFreeSpin(): bool
    {
        return (in_array($this->gameType, ['freegame', 'normal']) && in_array($this->eventType, ['free-bet', 'free-bet-win', 'get_free_bet']));
    }

    /**
     * {@inheritDoc}
     */
    public function awardFRBonus($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry)
    {
        $ext_id = !empty($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->getGuidv4($p_iUserId);
        $this->attachPrefix($ext_id);
        return $ext_id;
    }

    /**
     * {@inheritDoc}
     *
     * The user can have the following scenario during Free round bonuses.
     *
     * 1) User starts free round bonus (normal free spins - given by videoslots).
     * 2) During free round bonuses, user wins ingame free spins.
     * 3) User plays ingame free spins.
     */
    protected function _handleFspinBet($p_iUserId, $p_iBonusEntryId, $p_bDeduct = true)
    {
        $userData = $this->_getUserData();
        $gameData = $this->_getGameData();
        $bonusEntryData = $this->_getFreespinData();

        if (!$this->_isFreespin() || $this->isIngameFreeSpin() || ($this->isNormalFreeSpin() && $this->gameType === 'freegame')) {
            return true;
        }

        $action = ($p_bDeduct === false) ? '+' : '-';
        $result = phive('SQL')->sh($p_iUserId, '', 'bonus_entries')->query("
            UPDATE bonus_entries SET frb_remaining = frb_remaining {$action} 1
            WHERE id = {$bonusEntryData['id']} AND user_id = {$userData['id']} AND bonus_type = 'freespin' 
            LIMIT 1
        ");

        // set the property as it might be needed in the response method
        $this->_m_aFreespins = $this->_getBonusEntryBy($p_iUserId, $bonusEntryData['id']);
        if ($this->_isInhouseFrb() && $this->_m_aFreespins['frb_remaining'] > -1) {
            // inform the player about remaining inhouse frb in the progress bar shown under the game
            phive('Casino')->wsInhouseFrb($userData['id'], $userData['preferred_lang'],
                'frb.remaining-msg.html',
                array_merge(
                    $gameData,
                    array(
                        'frb_remaining' => $this->_m_aFreespins['frb_remaining'],
                        'frb_granted' =>  $this->_m_aFreespins['frb_granted'],
                    )
                )
            );
        }

        return $result;
    }

    /**
     * Gets the free round bonus (entry from `bonus_entries`) for the current user and game.
     *
     * @param object $user
     * @param array $game
     * @return array|null
     */
    private function getFreeRoundBonus(object $user, array $game): ?array {
        $walletMethod = $this->getWalletMethodByGpMethod($this->getGpMethod());

        if ($this->isTournamentMode() || $this->isIngameFreeSpin() || empty($user) || empty($game)) {
            return null;
        } elseif ($walletMethod !== '_balance' && (!$this->isNormalFreeSpin() && !$this->isIngameFreeSpin())) {
            return null;
        }

        // Find the active entry in (bonus_entries) for $user and $game
        $this->setAvailableFreespin($user->getId(), $game['game_id']);

        $freeSpinsData = $this->_getFreespinData();
        $this->_m_bIsFreespin = !empty($freeSpinsData);

        if ($freeSpinsData['frb_remaining'] > 0 || ($this->_isFreespin() && $walletMethod === 'bet')) {
            return $freeSpinsData;
        }

        // In the last call (when the user spend the last free spin remaining), GP will make a request in the following
        // order:
        // 1. debit -> _bet (we process the bet and change frb_remaining = 0 in bonus_entries table).
        // 2. credit -> _win (we need to search the last entry in bonus_entries with frb_remaining=0 for the current
        // user and game and finish the free spins process).
        $freeSpinsData = $this->_getBonusEntryBy($user->getId(), $game['game_id'], 'game_id');
        $this->_m_aFreespins = $freeSpinsData;
        $this->_m_bIsFreespin = !empty($freeSpinsData);

        return ($freeSpinsData['frb_remaining'] == 0 && $walletMethod === '_win') ? $freeSpinsData : null;
    }

    /**
     * @see Skywind::preProcess()
     *
     * As the amount (bet and win) was converted in preProcesses method, we should calculate
     * the value of the free spins and covert it too.
     *
     * If not, the amount (bet and win) won't match with the free spin value.
     *
     * {@inheritDoc}
     */
    protected function _getFreespinValue($p_iUserId = null, $p_mId = null, $mc = true)
    {
        $freeSpinValue = parent::_getFreespinValue($p_iUserId, $p_mId, false);

        return $this->convertFromToCoinage(
            $freeSpinValue,
            self::COINAGE_UNITS,
            self::COINAGE_CENTS
        );
    }
}
