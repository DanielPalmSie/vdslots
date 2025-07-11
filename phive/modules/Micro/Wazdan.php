<?php

require_once __DIR__ . '/Gp.php';

class Wazdan extends Gp
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
     * @var boolean
     */
    protected $_m_bByRoundId = true;

    /**
     * Is the bonus_entries::balance updated after each FRB request with the FRW or does the GP keeps track and send
     * the total winnings at the end of the free rounds. Default: true (balance is updated per bet)
     */
    protected $_m_bFrwSendPerBet = false;

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
    protected $_m_bConfirmFrbBet = false;

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
     * For Wazdan we have special gameplay in which the user can pay extra money for some rounds with extra
     * probability of win the jackpot.
     *
     * Once the user has started in that special gameplay, the Wazdan will call our wallet
     * on each round. Also, Wazdan will send bet and win request with amount 0.
     *
     * For Wazdan, we need to store those bets with amount 0 (only during jackpot gameplay) to be able to handle
     * properly the rounds in our database.
     *
     * @var bool
     */
    protected $_m_bConfirmZeroAmountBet = true;

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
     * Session device type. Used to store correct device type on bets and wins table
     * @var string
     */
    protected $session_device = 'desktop';
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
    private $_m_aErrors = [
        'ER03' => [
            'responsecode' => 200,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => 1,
            'message' => 'Session not found.',
            'custom' => true
        ],
        'ER06' => [
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => 8,
            'message' => 'Insufficient funds',
            'custom' => true
        ],
        'ER11' => [
            'responsecode' => 498,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => 1,
            'message' => 'Session not found.',
            'custom' => true
        ],
    ];

    private $_m_aMethodsMappedApi = [
        'authenticate' => '_init',
        'getStake' => '_bet',
        'returnWin' => '_win',
        'rollbackStake' => '_cancel',
        'gameClose' => '_end',
        'getFunds' => '_balance',
        'frResult' => '_frbStatus',  //GP is no longer sending this request, but we need it to help map to and execute _frbStatus function
        'rcClose' => '_end',
        'rcContinue' => '_end',
        'ping'=>'_init',
    ];

    private $_m_sToken = '';
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
            ->_checkDeclaredProperties()
            ->_setWalletActions();

        return $this;
    }

    public function doConfirmByRoundId()
    {
      return !($this->isTournamentMode() || $this->_isFreespin());
    }

  /**
     * Pre process data received from GP
     *
     * @return object
     */
    public function preProcess()
    {
        $this->setDefaults();
        
        $fileGetContent = $this->_m_sInputStream;
        
        $params = (empty($fileGetContent) ? $_REQUEST : $fileGetContent);
        
        $oData = (empty($fileGetContent) ? json_decode(json_encode($params), false) : json_decode($params, false));
        $this->_m_requestId = $oData->requestId;
        
        $this->_setGpParams($oData);
        
        if ($oData === null) {
            // request is unknown
            $this->_logIt([__METHOD__, 'unknown request']);
            $this->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }
         
        $aJson = $aAction = [];
        $casinoMethod = null;
        
        // Define which service is requested
        $casinoMethod = substr($_SERVER['REQUEST_URI'], (strrpos($_SERVER['REQUEST_URI'], '/')+1));

        
        if (empty($casinoMethod)) {
            // method to execute not found
            $this->_setResponseHeaders($this->_getError(self::ER02));
            $this->_logIt([__METHOD__, 'method not found']);
            die();
        } else {
            $this->_setGpMethod($casinoMethod);
        }
        
        $mSessionData = null;
            
        // check if session does exist if so get the data from the session.
        //The first check is used for auth request, while second is used for the rest
        if (isset($oData->token) || isset($oData->user->token)) {
            $mSessionData = $this->fromSession($oData->token ?? $oData->user->token);
            if(!empty($mSessionData)){
                $this->_m_sToken = $mSessionData->sessionid;
                $this->session_device = $mSessionData->device;
            }
        }
        
        if (isset($mSessionData->userid)) {
            $aJson['playerid'] = $mSessionData->userid;
            $this->_logIt([__METHOD__, 'UID by session', print_r($mSessionData, true)]);
        } elseif (isset($oData->user->id)) {
            $aJson['playerid'] = $oData->user->id;
        } else {
            $this->_setResponseHeaders($this->_getError(self::ER09));
            $this->_logIt([__METHOD__, 'UID not found.']);
            die();
        }

        
        if (!empty($mSessionData->gameid)) {
            $aJson['skinid'] = $this->stripPrefix($mSessionData->gameid);
            $this->_logIt([__METHOD__, 'GID by session', print_r($mSessionData, true)]);
        } else {
            if (isset($oData->gameId)) {
                $aJson['skinid'] = $oData->gameId;
                $this->_logIt([__METHOD__, 'GID by $oData->gameCodeName', $oData->gameId]);
            } else {
                $aJson['skinid'] = '';
                $this->_logIt([__METHOD__, 'GID not found']);
            }
        }
        

        switch ($casinoMethod) {

            case 'ping':
            case 'getFunds':
            case 'authenticate':
                $aJson['state'] = 'single';
                $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                $aAction[0]['parameters'] = [];
                $aJson['action'] = $aAction[0];
                break;
            
            case 'getStake':
                $roundIdParts = explode("-", $oData->roundId);
                $aJson['state'] = 'single';
                $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                $aAction[0]['parameters'] = [
                    'amount' => $this->convertFromToCoinage($oData->amount, self::COINAGE_UNITS, self::COINAGE_CENTS),
                    'transactionid' => explode("-", $oData->transactionId)[1],
                    'roundid' => $roundIdParts[1] . "-" . $roundIdParts[2]
                ];
                $aJson['action'] = $aAction[0];
         
                // detect for freespin in bet
                if (!empty($oData->freeRoundInfo) && $oData->amount == 0 ) {
                    $aJson['freespin'] = array('id' => $oData->freeRoundInfo->txId);
                }
                break;
                
            case 'returnWin':
                $roundIdParts = explode("-", $oData->roundId);
                $aJson['state'] = 'single';
                $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                $aAction[0]['parameters'] = [
                    'amount' => $this->convertFromToCoinage($oData->amount, self::COINAGE_UNITS, self::COINAGE_CENTS),
                    'transactionid' => explode("-", $oData->transactionId)[1],
                    'roundid' => $roundIdParts[1] . "-" . $roundIdParts[2]
                ];

                // GP now sends win request for FS all at once after last spin is complete in the returnWin request. We
                // must check for the FS and call _frbStatus function mapped to by 'frResult' which is deprecated by GP
                // this replicates the behaviour in the original 'frResult' case statement
                if (!empty($oData->freeRoundInfo)) {
                    $aAction[0]['parameters'] += [
                        'txId' => $oData->freeRoundInfo->txId
                    ];
                    $aJson['freespin'] = array('id' => $oData->freeRoundInfo->txId);
                    $aAction[0]['command'] = $this->getWalletMethodByGpMethod('frResult');
                }
                
                $aJson['action'] = $aAction[0];
                break;
                
            case 'rollbackStake':
                $roundIdParts = explode("-", $oData->roundId);
                
                $aJson['state'] = 'single';
                $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                $aAction[0]['parameters'] = [
                    'amount' => $this->convertFromToCoinage($oData->amount, self::COINAGE_UNITS, self::COINAGE_CENTS),
                    'transactionid' => explode("-", $oData->originalTransactionId)[1],  // we use the original transaction id to avoid inserting a new row
                    'roundid' => $roundIdParts[1] . "-" . $roundIdParts[2]
                ];
                $aJson['action'] = $aAction[0];
                break;

            default:
                $aAction['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                $aJson['state'] = 'single';
                $aJson['action'] = $aAction;
                break;
        }
        
        $this->_m_oRequest = json_decode(json_encode($aJson), false);
        $this->_logIt([__METHOD__, print_r($this->_m_oRequest, true)]);
        
        return $this;
    }
    
    
    
    /**
     * Execute the requested command from gaming provider
     * @return void
     */
    public function exec()
    {

        $mResponse = false;

        // check if the commands requested do exist
        $this->_setActions();

        // check for freespins
        if (isset($this->_m_oRequest->freespin)) {
            $this->_setFreespin($this->_m_oRequest->playerid, $this->_m_oRequest->freespin->id);
            if (!$this->_isFreespin()) {
                // the frb doesn't exist or is missing??
                $this->_response($this->_getError(self::ER17));
            }
        }

        // Set the game data by the received skinid (is gameid)
        if (!empty($this->_m_oRequest->skinid)) {
            $this->_m_oRequest->device = (int)($this->session_device == 'mobile');
            $this->_setGameData(true);
        }

        // execute all commands
        foreach ($this->_getActions() as $key => $oAction) {

            $sMethod = $oAction->command;
          
            // Update the user data before each command
            if (isset($this->_m_oRequest->playerid) && !is_array($this->_m_oRequest->playerid)) {
                $this->_setUserData();
            }

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
       
        // Update the user data after each command
        if (isset($this->_m_oRequest->playerid) && !is_array($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }
        
        $this->_response($mResponse);
    }

    protected function _response($p_mResponse)
    {

        $aResponse = $aResp = [];
        $aUserData = $this->_getUserData();
        
        if ($p_mResponse === true) {
            $aResponse['status'] = 0;
            
            switch ($this->getGpMethod()) {
                case 'authenticate':
                    $aResponse['user'] = [
                        'id' => $aUserData['id'],
                        'currency' => strtoupper($this->getPlayCurrency($aUserData))
                    ];
                    
                    $aResponse['funds'] = [
                        'balance' => $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS)
                    ];

                    $maxBet = phive('Gpr')->getMaxBetLimit(cu($aUserData['id']));
                    if (!empty($maxBet)) {
                        $aResponse['options']['maxStake'] = $maxBet;
                    }

                    $mSessionData = $this->fromSession($this->_m_sToken);
                    $this->_logIt(["RC", print_r($mSessionData->device, true), $aUserData['country']]);
                    if($mSessionData->device == 'mobile' && $aUserData['country'] == 'GB') {         
                        $aRc = $this->_getRcInterval($aUserData['id']);
                        if(!empty($aRc)) {
                            $aResponse['uk'] = [
                                'interval' => $aRc['reality_check_interval']/60,
                                'transactionUrl' => phive()->getSiteUrl() . "/account/" . $aUserData['id'] . "/game-history/",
                                'closeGameUrl' => phive()->getSiteUrl()
                            ];
                        }
                    }
                    break;
                case 'rcClose':
                case 'rcContinue':
                case 'gameClose':
                    break;
                default:
                    $aResponse['funds'] = [
                        'balance' => $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS)
                    ];
            }

            $aResp = $aResponse;
        } else {
            if($p_mResponse['custom'] === true) {
                $aResp = [
                    'status' => $p_mResponse['code'] // custom error status in wazdan
                ];
            } else {
                /*
                 * Small note on the below 20.05.2020
                 * from wazdan: "if you use >100 you have somewhat guarantee for next 10 years that we will not get
                 * even close to your errors"
                 *
                 * This was there conformation that for the current version (2.6) we can use that as a generic error
                 * response
                 */
                $aResp = [
                    'status' => 101
                ];
            }
        }
        
        $this->_setResponseHeaders($p_mResponse);
        
        $result = json_encode($aResp);
        $this->_logIt([__METHOD__, print_r($p_mResponse, true), $result]);
        echo $result;
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }
    
    
    /**
     * Update the status in the bonus entries table when a FRB round has finished
     * Wazdan sends a final frw with total sum, we only process the final frw
     * @param stdClass $p_oParameters
     * @return bool
     */
    protected function _frbStatus(stdClass $p_oParameters)
    {
        // we must insert the final win having the total sum of all free round wins
        $this->_m_bFrwSendPerBet = true;
        if ($this->_win($p_oParameters)) {
            $this->_m_bFrwSendPerBet = false;
            return $this->_handleFspinWin($p_oParameters->amount);
        }
        return false;
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
        $launchurl = $this->getSetting('launch_url') . '/' . $this->getSetting('operator') . '/gamelauncher';
        $aUrl['operator'] = $this->getSetting('operator_brand');
        $aUrl['game'] = $p_mGameId;
        $aUrl['platform'] = $p_sTarget;
        $aUrl['mode'] = 'demo';
        $aUrl['lobbyUrl'] = phive()->getSiteUrl();
       

        if (isLogged()) {
            $ud = cuPl()->data;
            $iUserId = $ud['id'];
            $sSecureToken = $this->getGuidv4($iUserId);
            $aUrl['mode'] = 'real';
            $aUrl['lang'] = $ud['preferred_lang'];
            $aUrl['token'] = $sSecureToken;
            $aUrl['license'] =  $this->getLicSetting('license', cuPl());
            $this->toSession($sSecureToken, $iUserId, $p_mGameId, $p_sTarget);
        }
        
        $url = $launchurl . '?' . http_build_query($aUrl);
        $this->_logIt([__METHOD__, 'launchurl: ' . $url]);

        return $url;
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
            $ext_id = (!empty($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->randomNumber(6));
            $this->attachPrefix($ext_id);
            return $ext_id;
        }
        $user = cu($p_iUserId);
        $bonusStartDate = $p_aBonusEntry['start_time'];
        $bonusEndDate = $p_aBonusEntry['end_time'];

        $a = [
            'playerId' => (string)$p_iUserId,
            'currency' => $user->data['currency'],
            'count' => $p_iFrbGranted,
            'txId' => $p_aBonusEntry['id'],
            'gameId' => $this->stripPrefix($p_sGameIds),
            'type' => 'regular',
            'value' => $this->convertFromToCoinage($this->_getFreespinValue($user->getId(),$p_aBonusEntry['id']), self::COINAGE_CENTS, self::COINAGE_UNITS),
            'operator' => $this->getLicSetting('operator'),
            'license' => $this->getLicSetting('license', $user),
            'startDate' => $bonusStartDate,
            'endDate' => $bonusEndDate,
        ];

        $url = $this->getSetting('freespin_api') . 'add/';
        $secret_key = $this->getLicSetting('secret_key');
        $content = json_encode($a);
        $hash = hash_hmac('sha256', $content, $secret_key);
        $signature = 'Signature:'.$hash;
        $debug_key = $this->getSetting('test', false) ? 'wazdan-fs-curl' : '';
        $res = phive()->post($url, $a, 'application/json', $signature, $debug_key, 'POST', 60);
        $res = json_decode($res, true); // decode as array
        return $res['freeroundId'] ?? 'fail';
    }
    
    
    
    protected function _getRcInterval($uid = '')
    {
        $ud = ud($uid);
        $uid = $ud['id'];
        $a = $reality_check_interval = array();
        
        $this->_logIt(['getRcInterval', $uid]);
        // needed for pragmatic as calling this function in response was returning false
        if (phive()->getSetting('ukgc_lga_reality') === true) {
            $allowedCountries = $this->getRcCountries();

            if (in_array($ud['country'], $allowedCountries)) {
                $reality_check_interval = cuSetting('cur-reality-check-interval', $uid);

                if (empty($reality_check_interval)) {
                    $rc_configs = lic('getRcConfigs');
                    $reality_check_interval = $rc_configs['rc_default_interval'];
                }

                phMset("$uid-cur-reality-check-stime", time());
            }
        }
        
        if (
            !empty($reality_check_interval) &&
            phive("Config")->getValue('reality-check-mobile', $this->getGpName()) === 'on'
            ) {
                $reality_check_interval = $reality_check_interval * 60;
                //$user = ud($uid);
                $a['history_link'] = phive()->getSiteUrl() . "/account/" . $ud['id'] . "/game-history/";
                $a['reality_check_interval'] = $reality_check_interval; // sec
                // confirmed with Henrik: elapsed time is always reset on start of a
                // new game in same login session so basically this is always 0
                //$a['elapsed_time'] = 0;
                unset($user);
            }
            
            return $a;
    }

}
