<?php

require_once __DIR__ . '/Gp.php';

class Nolimit extends Gp
{

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
     * Confirmed by GP [11:47:36 AM] johanbucht: Yeah the game round id will be shared for the withdraw and deposit calls and not reused (skype)
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
     * Delay the execution of the request processing or response by xx seconds during testing stage
     * so cancel requests and re-sending requests again can be tested.
     * @var string
     */
    protected $_m_iRandomizeWalletTime = 10;

    /**
     * Insert frb into bet table so in case a frw comes in we can check if it has a matching frb
     *
     * @var bool
     */
    protected $_m_bConfirmFrbBet = true;

    protected $_m_bSkipBetCheck = true;

    /**
     * Check if incoming win request has a valid bet transaction.
     * Note: this is NOT for freespins but for real money play
     * @var bool
     */
    protected $_m_bConfirmBet = true;

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
        'ER01' => array(
            'responsecode' => 500, // used to send header to GP if not enforced 200 OK
            'status' => 'SERVER_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '14000', // change this to whatever the GP likes to receive as code
            'message' => 'Internal Server Error.' // change this to whatever the GP likes to receive as message
        ),
        'ER02' => array(
            'responsecode' => 405,
            'status' => 'COMMAND_NOT_FOUND',
            'return' => 'default',
            'code' => '14003',
            'message' => 'Command not found.'
        ),
        'ER03' => array(
            'responsecode' => 401,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => '14002',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER06' => array(
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => '14001',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        'ER08' => array(
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => '14003',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ),
        'ER09' => array(
            'responsecode' => 404,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => '14003',
            'message' => 'Player not found.'
        ),
        'ER10' => array(
            'responsecode' => 404,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => '14003',
            'message' => 'Game is not found.'
        ),
        'ER11' => array(
            'responsecode' => 498,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => '14002',
            'message' => 'Token not found.'
        ),
        'ER12' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_NO_REMAINING',
            'return' => 'default',
            'code' => '14000',
            'message' => 'No freespins remaining.'
        ),
        'ER13' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_INVALID',
            'return' => 'default',
            'code' => '14000',
            'message' => 'Invalid freespin bet amount.'
        ),
        'ER15' => array(
            'responsecode' => 403,
            'status' => 'FORBIDDEN',
            'return' => 'default',
            'code' => '14000',
            'message' => 'IP Address forbidden.'
        ),
    );

    private $_m_aMapGpMethods = array(
        'wallet.balance' => '_balance',
        'wallet.withdraw' => '_bet',
        'wallet.deposit' => '_win',
        'wallet.rollback' => '_cancel',
        'wallet.keep-alive' => '_end',
        'wallet.validate-token' => '_init',
    );

    /**
     * Set the defaults
     * Separate function so it can be called also from the classes that extend TestGp class
     *
     * @return Gp
     */
    public function setDefaults()
    {

        $this
            ->_mapGpMethods($this->_m_aMapGpMethods)
            ->_whiteListGpIps($this->getSetting('whitelisted_ips'))
            ->_overruleErrors($this->_m_aErrors)
            ->_supportInhouseFrb($this->_m_sGpName)
            ->_checkDeclaredProperties()
            ->_setWalletActions();

        return $this;
    }

    /**
     * Set the return function to true if we are going to use the rounds table
     * @return bool true or false
     */
    public function doConfirmByRoundId()
    {
        return $this->getSetting('confirm_win', false);
    }

    /**
     * Pre process data received from GP
     *
     * @return object
     */
    public function preProcess()
    {
        $this->setDefaults();
        $oData = json_decode($this->_m_sInputStream, false);
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
        $casinoMethod = $oData->method;

        if (empty($casinoMethod)) {
            // method to execute not found
            $this->_setResponseHeaders($this->_getError(self::ER02));
            $this->_logIt([__METHOD__, 'method not found']);
            die();
        } else {
            $this->_setGpMethod($casinoMethod);
        }

        $mSessionData = null;
        if (isset($oData->params->token)) {
            // only requested during _init ~ validate.token
            $mSessionData = $this->fromSession($oData->params->token);
        }

        if(empty($mSessionData) && isset($oData->params->extId1)){
            $mSessionData = $this->fromSession($oData->params->extId1);
            if(!empty($mSessionData) && isset($oData->params->information->game) && $oData->params->information->game !== $this->stripPrefix($mSessionData->gameid)){
                // multiple session issue which is not supported by GP
                // it prevent bets under the wrong gameid and a session reload
                $this->_logIt([__METHOD__, 'Not supported multi-session killed.']);
                $this->deleteToken($oData->params->extId1);
                $this->_response($this->_getError(self::ER11));
            }
        }

        $aJson['id'] = $oData->id;

        if (isset($mSessionData->userid)) {
            $aJson['playerid'] = $mSessionData->userid;

            if(!empty($oData->params->token)){
                $aJson['token'] = $oData->params->token;
            } elseif ($oData->params->extId1){
                $aJson['token'] = $oData->params->extId1;
            }

            $this->_logIt([__METHOD__, 'UID by session', print_r($mSessionData, true)]);
        }  elseif(isset($oData->params->userId)) {
            $aJson['playerid'] = $oData->params->userId;
        } elseif($casinoMethod === 'wallet.balance' && isset($oData->params->extId1s)){
            $aJson['playerid'] = $oData->params->extId1s;
            $this->_logIt([__METHOD__, 'UID by extId1s', print_r($oData->params->extId1s, true)]);
        } else {
            $this->_setResponseHeaders($this->_getError(self::ER09));
            $this->_logIt([__METHOD__, 'UID not found.']);
            die();
        }

        if (isset($mSessionData->gameid)) {
            $aJson['skinid'] = $this->stripPrefix($mSessionData->gameid);
            $this->_logIt([__METHOD__, 'GID by session', print_r($mSessionData, true)]);
        } else {
            if (isset($oData->params->information->game)) {
                $aJson['skinid'] = $oData->params->information->game;
                $this->_logIt([__METHOD__, 'GID by $oData->params->information->game', $oData->params->information->game]);
            } else {
                $aJson['skinid'] = '';
                $this->_logIt([__METHOD__, 'GID not found']);
                if($casinoMethod === 'wallet.balance'){
                    // gameid is required to include bonus balance correctly
                    // will enter this condition when multi session is tried to be played and dsession got deleted because of it
                    $this->_logIt([__METHOD__, 'Can not include bonus balance in balance request becasue of missing GameId.']);
                    $this->deleteToken($aJson['token']);
                    $this->_response($this->_getError(self::ER11));
                }
            }
        }
        if(isset($oData->params->identification)){
            $aJson['hash'] = $this->getHash($oData->params->identification->name.$oData->params->identification->key, self::ENCRYPTION_SHA1);
        }

        $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
        $aJson['state'] = 'single';

        if (in_array($casinoMethod, array('wallet.withdraw','wallet.deposit','wallet.rollback'))) {
            $aAction[0]['parameters'] = array();
            $action = (($casinoMethod === 'wallet.withdraw') ? 'withdraw' : (($casinoMethod === 'wallet.deposit') ? 'deposit' : ''));
            if(!empty($action)){
                $aAction[0]['parameters']['amount'] = $this->convertFromToCoinage($oData->params->$action->amount, self::COINAGE_UNITS, self::COINAGE_CENTS);
            }
            $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
            $aAction[0]['parameters']['transactionid'] = $oData->params->information->uniqueReference;
            $aAction[0]['parameters']['roundid'] = $oData->params->information->gameRoundId;

        }
        $aJson['action'] = $aAction[0];

        // detect for freespin
        if (isset($oData->params->promoName)) {
            $aJson['freespin'] = array(
                'id' => $oData->params->promoName
            );
        }

        $this->_m_oRequest = json_decode(json_encode($aJson), false);
        //print_r($this->_m_oRequest);
        $this->_logIt([__METHOD__, print_r($this->_m_oRequest, true)]);

        return $this;
    }

    /**
     * Execute the requested command from gaming provider
     * @return void
     */
    public function exec()
    {
        $cu = cu($this->_m_oRequest->playerid);
        $mResponse = false;

        if ($this->_m_oRequest->hash === $this->getHash($this->getLicSetting('operator', $cu).$this->getLicSetting('secretkey', $cu), self::ENCRYPTION_SHA1)) {
            // check if the commands requested do exist
            $this->_setActions();

            // Set the game data by the received skinid (is gameid)
            if (isset($this->_m_oRequest->skinid)) {
                $this->_setGameData();
                if (isset($this->_m_oRequest->freespin)) {
                    $this->_setFreespin($this->_m_oRequest->playerid, $this->_m_oRequest->freespin->id);
                    if (!$this->_isFreespin()) {
                        // the frb doesn't exist or is missing??
                        $this->_response($this->_getError(self::ER17));
                    }
                }
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
        $this->_response($mResponse);
    }

    protected function _response($p_mResponse)
    {
        $aResponse = $aResp = array();
        $aResp['jsonrpc'] = '2.0';
        $aResp['id'] = $this->_m_oRequest->id;

        if ($p_mResponse === true) {

            if($this->getGpMethod() === 'wallet.balance' && is_array($this->_m_oRequest->playerid)){
                foreach($this->_m_oRequest->playerid as $token){
                    $mSessionData = $this->fromSession($token);
                    if(!empty($mSessionData)){
                        $gameid = $this->getGamePrefix() . $mSessionData->gameid;
                        $userid = $this->stripPrefix($mSessionData->userid);
                        $aUserData = ud((int)$userid);
                        $aGameData = $this->_m_oMicroGames->getByGameRef($gameid);
                        $balance = $this->convertFromToCoinage($this->_getBalance($aUserData, $aGameData), self::COINAGE_CENTS, self::COINAGE_UNITS);
                        $aResponse['balances'][$userid] = array('amount' => "$balance", 'currency' => strtoupper($aUserData['currency']));

                    }
                }
            } else {
                $aUserData = $this->_getUserData();
                $balance = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS);
                $aResponse['balance'] = array('amount' => "$balance", 'currency' => strtoupper($this->getPlayCurrency($aUserData)));
                $aResponse['extId1'] = $this->_m_oRequest->token;
            }



            $aTestUsers = array(
                'devtestmt',
                'devtestnl',
                'devtestde',
                'devtestse',
                'devtestfi',
                'devtestno',
                'devtestde',
                'devtestru',
                'devtestuk',
                'devtestgb',
                'devtestca',
                'devtestau',
            );

            switch($this->getGpMethod()){
                case 'wallet.validate-token':
                    $aResponse['userId'] = $aUserData['id'];
                    $aResponse['username'] = $aUserData['username'];
                    $aResponse['country'] = $aUserData['country'];
                    $aResponse['language'] = $aUserData['preferred_lang'];
                    $aResponse['gender'] = substr($aUserData['sex'],0, 1);
                    $aResponse['ip'] = $aUserData['cur_ip'];
                    $aResponse['test'] = (in_array($aUserData['username'],$aTestUsers) ? 'true' : 'false');

                    $maxBet = phive('Gpr')->getMaxBetLimit(cu($aUserData['id']));
                    if (!empty($maxBet)) {
                        $aResponse['customBetLimits']['maxBet'] = $maxBet;
                    }
                    break;

                case 'wallet.deposit':
                case 'wallet.withdraw':
                case 'wallet.rollback':
                    $thxId = $this->_getTransaction('txn');
                    $aResponse['transactionId'] = (empty($thxId) ? 'a'.$this->randomNumber(16) : $thxId);
                    break;
            }

            $aResp['result'] = (($this->getGpMethod() === 'wallet.keep-alive') ? array() : $aResponse);

        } else {
            $aResp['error'] = array(
                'code' => '-32000',
                'message' => 'Server error',
                'data' => array('code' => ((strpos($p_mResponse['code'], '140') !== false) ? $p_mResponse['code'] : '14000'), 'message' => $p_mResponse['message'])
            );
        }

        $this->_setResponseHeaders($p_mResponse);

        $result = json_encode($aResp);
        $this->_logIt([__METHOD__, print_r($p_mResponse, true), $result]);
        echo $result;

        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }

    /**
     * Delete a free spin bonus entry from the bonus_entries table by bonus entries ID
     * @example {
     *  playerid: <userid:required>,
     *  id: <int:required>
     * }
     * @param stdClass $p_oParameters
     * @return bool
     */
    protected function _deleteFrb(stdClass $p_oParameters){
        if(isset($p_oParameters->playerid) && isset($p_oParameters->id) && ctype_digit($p_oParameters->id)) {
            $user = cu($p_oParameters->playerid);
            $a = array(
                'id' => $this->getGuidv4($p_oParameters->playerid.$p_oParameters->id),
                'jsonrpc' =>'2.0',
                'method' =>'freebets.cancel',
                'params' => array(
                    // User name for authentication in the Casino Game API service
                    'identification' => array('name' => $this->getLicSetting('operator', $user), 'key' => $this->getLicSetting('secretkey', $user)),
                    // Bonus id within the Operator system. Should be unique within the brand.
                    'promoName' => $p_oParameters->id,
                )
            );

            $result = phive()->post(
                $this->getSetting('launchurl_frb'),
                json_encode($a),
                self::HTTP_CONTENT_TYPE_APPLICATION_JSON,
                "Cache-Control: no-cache",
                $this->getGamePrefix() . 'out',
                'POST'
            );

            $oData = json_decode($result, false);

            // log response for debugging
            $this->_logIt([
                __METHOD__,
                'URL: ' . $this->getSetting('launchurl_frb'),
                print_r($a, true),
                json_encode($a),
                print_r($oData, true)
            ]);

            if (!empty($oData->id)) {
                return parent::_deleteFrb($p_oParameters);
            }
        }

        return false;
    }

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $p_mGameId The game_ext_name from the microgames table without the prefix so basically the game ID
     *                          as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @param bool $show_demo
     * @return string The url to open the game
     */
    public function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        $this->initCommonSettingsForUrl();

        $launch_params = $this->getLaunchParameters($p_mGameId, $p_sLang, $p_sTarget);

        $url = $this->getSetting('launchurl') . '?' . http_build_query($launch_params);

        $this->_logIt([__METHOD__, 'launchurl: ' . $url]);

        return $url;
    }



    /*
    *   Returns the parameters for launching the game
    */
    private function getLaunchParameters($p_mGameId, $p_sLang = '', $p_sTarget = '')
    {
        $user       = cu();
        $uData      = $user ? $user->getData() : null;
        $token      = $user ? $this->getGuidv4($uData['id']) : null;
        $isLogged   = isLogged();
        $license    = $this->iso;

        $url_params = [   // Common parameters
            'game'                  => $p_mGameId,
            'operator'              => $this->getLicSetting('operator', $user),
            'environment'           => $this->getLicSetting('environment', $user),
            'lobbyUrl'              => $this->getLobbyUrl(false) ?? $this->getSetting('lobby_url') ,
            'depositUrl'            => $this->getCashierUrl(false, $p_sLang, $p_sTarget) ?? $this->getSetting('cashier_url'),
            'device'                => $p_sTarget,
            'version'               => $this->getLicSetting('version', $user),
            'fullscreen'            => "false",
            'token'                 => $isLogged ? $token : null,
            'currency'              => $isLogged ? $uData['currency'] : ciso(),
            'language'              => $isLogged ? $user->getLang() : cLang(),
            'accountHistoryUrl'     => $isLogged ? $this->getHistoryUrl(false, $user, $p_sLang) : null, // they are intrinsically using javascript.top.window.href=
            'clock'                 => $license == 'SE' ? 'false' : null,
            'jurisdiction'          => json_encode(['name' => $this->getLicSetting('jurisdiction', $user)])
        ];

        $url_params = array_filter($url_params);

        if ($this->getRcPopup($p_sTarget, $user) == 'ingame') {
            $rc_params = $this->getRealityCheckParameters($user, false);
            $url_params = array_merge($url_params, $rc_params);
        }

        if($isLogged) {
            $this->toSession($token, $uData['id'], $p_mGameId, $p_sTarget);
        }

        $this->_logIt([__METHOD__, 'urlparams: ' . print_r($url_params, true)]);

        return $url_params;
    }

    public function filterParameters($key)
    {
        $keys_needed = ['realityCheck', 'loggedInTime'];

        return in_array($key, $keys_needed);
    }

    public function addCustomRcParamsSE(){
        $base_arr = [
            'sessionStart'  => 0,
            'nextTime'      => 0
        ];

        if($this->getLicSetting('show_buttons')){
            return array_merge($base_arr, [
                'target'        => '_top',
                'spelpaus'      => '',
                'sjalvtest'     => '',
                'spelgranser'   => ''
            ]);
        }

        return $base_arr;
    }

    public function addCustomRcParams($regulator, $rcParams)
    {
        // set true to the params that need to be mapped
        $provider_rc_params = [
            'realityCheck' => array_merge([
                    'enabled'       => true,
                    'interval'      => true,
                    'bets'          => true,
                    'winnings'      => true
            ],
            (array)$this->lic($regulator, 'addCustomRcParams'))
        ];

        return array_merge($rcParams, $provider_rc_params);
    }


    /**
     * @param $regulator
     * @return array
     * This is so that when using rc on MT (default jurisdiction) the popup displays integers starting from 0
     */
    public function addExtraLicenseRcParams($regulator)
    {
        return ($regulator == 'MT') ? ['rcTotalBet' => 0,'rcTotalWin' => 0] : [];
    }

    public function getMapRcParametersSE(){
        $base_arr = ['sessionStart' => 'rcElapsedTime'];

        if($this->getLicSetting('show_buttons')){
            return array_merge($base_arr, [
                'spelpaus'      => 'spelpausLink',
                'sjalvtest'     => 'sjalvtestLink',
                'spelgranser'   => 'spelgranserLink'
            ]);
        }

        return $base_arr;
    }

    public function mapRcParameters($regulator, $rcParams, $u_obj = null)
    {
        $mapping = [
            'interval'      => 'rcInterval',
            'bets'          => 'rcTotalBet',
            'winnings'      => 'rcTotalWin',
        ];

        $mapping = array_merge($mapping, (array)$this->lic($regulator, 'getMapRcParameters'));

        // apply mapping
        $rcParams['realityCheck'] = phive()->mapit($mapping, $rcParams, [], false);

        // only want this for SE, GB everthiing is 0 since it's per game session
        $jur_params = $this->lic($regulator, 'generateTriggerParamsForRcPopup', [$rcParams, $u_obj]);

        // In case method doesn't exist we simply use the default.
        $rcParams = $jur_params ?? $rcParams;

        $filtered_params = $rcParams;

        // filter params required
        $filtered_params = array_filter($rcParams, [$this, "filterParameters"], ARRAY_FILTER_USE_KEY);

        $filtered_params['realityCheck'] = json_encode($rcParams['realityCheck']);

        return $filtered_params;
    }

    public function generateTriggerParamsForRcPopupGB($rcParams, $u_obj = null){
        return $this->generateTriggerParamsForRcPopupSE($rcParams, $u_obj);
    }

    /**
     * @param $rcParams
     * @return mixed
     * Converts the se params into the format applicable to nolimit
     * interval in seconds
     * nextTime calculated in millseconds
     * bes & winnings in cents
     */
    public function generateTriggerParamsForRcPopupSE($rcParams, $u_obj = null)
    {
        $current_user = cu($u_obj);

        // we need to know how much tim has passed
        $elapsedTime = lic('rcElapsedTime', [] , $current_user);

        // we want all the times in unix timestamp to the millisecond
        // we need to know the unix timestamp of when the user logged in
        $sessionStart = round(strtotime($current_user->getCurrentSession()['created_at']) * 1000); // TODO check if we can replace with (int)cu()->getSessionLength('s', 2) /Paolo
        $rc_popup_interval = $rcParams['realityCheck']['interval'] * 60; // in seconds

        // time remaining in seconds
        $time_remaining = $rc_popup_interval - ($elapsedTime % $rc_popup_interval);

        // this needs to be a unix timestamp of when the rc should trigger i.e after 10s 10 minutes an minute and hour from etc... game launch
        $rcParams['realityCheck']['nextTime']       = (time() + $time_remaining) * 1000;
        $rcParams['realityCheck']['sessionStart']   = $sessionStart;
        $rcParams['realityCheck']['bets']           = rnfCents($rcParams['realityCheck']['bets']);
        $rcParams['realityCheck']['winnings']       = rnfCents($rcParams['realityCheck']['winnings']);

        return $rcParams;
    }


    /**
     * Inform the GP about the amount of freespins available for a player.
     * The player can open the game anytime. The GP already 'knows' about the freespins available to the player.
     * Often if the GP keeps track itself and send the wins at the end when all FRB are finished like with Netent.
     *
     * @param int $p_iUserId The user ID
     * @param string $p_sGameIds The internal gameId's pipe separated
     * @param int $p_iFrbGranted The frb given to play
     * @param string $bonus_name
     * @param array $p_aBonusEntry The entry from bonus_types table
     * @return bool|string|int If not false than bonusId is returned otherwise false (freespins are not activated)
     */
    public function awardFRBonus($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry)
    {
        if($this->getSetting('no_out') === true) {
            $ext_id = (isset($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->randomNumber(6));
            $this->attachPrefix($ext_id);
            return $ext_id;
        }

        $user = cu($p_iUserId);

        $game_list = [];
        foreach (explode('|', $this->stripPrefix($p_sGameIds)) as $game_id) {
            $game_list[] = $this->stripPrefix(phive('MicroGames')->overrideGameRef($user, $this->getGamePrefix().$game_id));
        }

        $a = array(
            'id' => $this->getGuidv4($user->data['id']),
            'jsonrpc' =>'2.0',
            'method' =>'freebets.add',
            'params' => array(
                // User name for authentication in the Casino Game API service
                'identification' => array('name' => $this->getLicSetting('operator', $user), 'key' => $this->getLicSetting('secretkey', $user)),
                // Id of the player within the Operator system.
                'userId' => (string)$p_iUserId,
                // amount of the frb and the currency to use
                'amount' => array(
                    'amount' => $this->convertFromToCoinage($this->_getFreespinValue($user->data['id'], $p_aBonusEntry['id']), self::COINAGE_CENTS, self::COINAGE_UNITS),
                    'currency' => $user->data['currency']
                ),
                // List of symbolic unique identifiers of the game that the FR is awarded for, comma separated.
                // Example: vs25a, vs9c, vs20s.
                'game' => implode(',', $game_list),
                // Number of free game rounds awarded.
                'rounds' => $p_iFrbGranted,
                // Bonus id within the Operator system. Should be unique within the brand.
                'promoName' => $p_aBonusEntry['id'],
                'expires' => "{$p_aBonusEntry['end_time']}T23:59:59",
            )
        );

        $result = phive()->post(
            $this->getSetting('launchurl_frb'),
            json_encode($a),
            self::HTTP_CONTENT_TYPE_APPLICATION_JSON,
            "Cache-Control: no-cache",
            $this->getGamePrefix() . 'out',
            'POST'
        );
        $oData = json_decode($result, false);

        // log response for debugging
        $this->_logIt([
            __METHOD__,
            'URL: ' . $this->getSetting('launchurl_frb'),
            print_r($a, true),
            print_r($p_aBonusEntry, true),
            print_r($oData, true)
        ]);

        if (!isset($oData->error)) {
            $this->attachPrefix($oData->id);
            return $oData->id;
        }

        return false;
    }

}
