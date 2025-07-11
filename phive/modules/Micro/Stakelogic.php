<?php
require_once __DIR__ . '/Gp.php';

class Stakelogic extends Gp
{
    /**
     * Name of GP. Used to prefix the bets|wins::game_ref (which is the game ID) and bets|wins::mg_id (which is transaction ID)
     * @var string
     */
    protected $_m_sGpName = __CLASS__;

    /**
     * Flag indicating whether to use round ID (trans_id) or trans ID (mg_id) when checking a win has a matching bet
     * Only applies if $_m_bConfirmBet == true.
     * Default true. Make sure that the round ID send by GP is an integer
     * @var boolean
     */
    protected $_m_bByRoundId = true;
    
    /**
     * Is the bonus_entries::balance updated after each FRB request with the FRW or does the GP keeps track and send the total winnings at the end of the free rounds.
     * Default: true (balance is updated per bet)
     */
    protected $_m_bFrwSendPerBet = true;
    
    /**
     * Update the status in bonus_entries table by the last FRW request from the GP in the _win() method. Default: true.
     * If set to false make sure that $this->_handleFspinWin() is called from the extended class.
     * @var bool
     */
    protected $_m_bUpdateBonusEntriesStatusByWinRequest = true;
    
    /**
     * Insert frb into bet table so in case a frw comes in we can check if it has a matching frb
     * @var bool
     */
    protected $_m_bConfirmFrbBet = false;
    
    /**
     * The header content type for the response to the GP
     * @var string
     */
    protected $_m_sHttpContentType = Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON;
    
    /**
     * Do we force respond with a 200 OK response to the GP
     * @var bool
     */
    protected $_m_bForceHttpOkResponse = true;
    
    /**
     * Bind GP method requests to the correct class method name
     * Keys are the method names received by the GP request and the values are the class method names
     * @var array
     */
    private $_m_aMapGpMethods = [
        'start-game-session' => '_init',
        'start-game-round' => '_bet',
        'cancel-game-round' => '_cancel',
        'get-funds-balance' => '_balance',
        'close-game-session' => '_end',
        'finish-game-round' => '_win'
    ];

    // See https://apidoc.stakelogic.com/api-files/general-response-error-codes
    private $_m_aErrors = [
        'ER08' => [
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => true,
            'code' => 'ER08',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ],
        'ER16' => array(
            'responsecode' => 200,
            'status' => 'INCORRECT_REQUEST',
            'return' => 'default',
            'code' => 'ER16',
            'message' => 'Invalid request.'
        ),
    ];
    
    private $_m_sSecureToken = null;

    private $log_info = [];

    private $log_player_id = 0;

    /**
     * Set the defaults
     * Seperate function so it can be called also from the classes that extend TestGp class
     * @return Gp
     */
    public function setDefaults()
    {
        $this
            ->_mapGpMethods($this->_m_aMapGpMethods)
            ->_whiteListGpIps($this->getSetting('whitelisted_ips'))
            ->_overruleErrors($this->_m_aErrors)
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
        return !($this->isTournamentMode() || $this->_isFreespin());
    }
    
    /**
     * Pre process data received from GP
     * @return object
     */
    public function preProcess()
    {
        $this->log_info = [];
        $this->setDefaults();

        $sData = $this->_m_sInputStream;
        $this->_setGpParams($sData);
        $oData = json_decode($sData, false);
        
        if ($oData === null) {
            $error = $this->_getError(self::ER16);
            if (is_array($error)) {
                $error['message'] = "Empty request.";
            }
            $this->_response($error);
        }

        $this->log_player_id = 0;
        if ($oData->playerId ?? false) {
            $this->log_player_id = $this->getUsrId($oData->playerId);
        }
        $aJson = $aAction = [];
        $method = null;
        $aMethods = $this->_getMappedGpMethodsToWalletMethods();

        // Define which service/method is requested/to use
        $urlMethod = substr(strrchr($_SERVER['REQUEST_URI'], '/'), 1);
        foreach ($aMethods as $key => $value) {
            if ($key == $urlMethod) {
                $method = $value;
                $this->_setGpMethod($key);
                break;
            }
        }
        $this->_logIt([__METHOD__, print_r($urlMethod, true), print_r($aMethods, true)]);

        if (empty($method)) {
            // method to execute not found
            $this->_logIt([__METHOD__, $method]);
            $error = $this->_getError(self::ER16);
            if (is_array($error)) {
                $error['message'] = "Action not found.";
            }
            $this->_response($error);
        }

        /**
         * playerId + gameSessionId is unique and present in all requests (see https://apidoc.stakelogic.com/api-files/start-game-round).
         * Pass '5541343e9' to _setUserData. t_eid (optional) must be loaded before calling _getTransactionById.
         */
        if (isset($oData->playerId) && isset($oData->gameSessionId)) {
            $this->_m_sSecureToken = "stakelogic_" . (string)$oData->playerId . "_" . (string)$oData->gameSessionId;

            $mSessionData = $this->fromSession($this->_m_sSecureToken);
            if ($mSessionData !== false) {
                // we get the userId and gameId from session and pass e.g. '' to the base Phive methods.
                if ($this->log_player_id === 0) {
                    $this->log_player_id = $this->getUsrId($mSessionData->userid);
                }
                $aJson['playerid'] = $mSessionData->userid;
                $aJson['skinid'] = $this->stripPrefix($mSessionData->gameid);
                $aJson['device'] = $mSessionData->device;

                if ($oData->playerId != $aJson['playerid']) {
                    $this->log_info[] = "Warning. Expected {$aJson['playerid']} but received {$oData->playerId}.";
                }
            } else {
                // session doesnt exist anymore. could be a delayed win
                $this->log_info[] = "Session key not found: {$this->_m_sSecureToken}.";
            }
        }

        // Use request->playerId if Redis timed out.
        if (!isset($aJson['playerid']) && $oData->playerId) {
            $this->log_player_id = $this->getUsrId($oData->playerId);
            $aJson['playerid'] = $oData->playerId;
        }

        $transaction_id = null;
        if (isset($aJson['playerid']) && isset($oData->gameRoundId)) {
            // New format for unique transaction ids, uses UID even for BoS. Must be used for new bets.
            $transaction_id = "{$this->log_player_id}_{$oData->gameRoundId}";

            /**
             * Win and Cancel requests might arrive (late) for bets we previously saved to our db with the old format
             * for transaction ids, so check which format to use.
             * 'skinid' (game ID) can be empty if Redis timed out in which case we look for the round's bet.
             */
            if (empty($aJson['skinid']) || in_array($method, ['_win', '_cancel'])) {
                $bet = $this->_getTransactionById($transaction_id, self::TRANSACTION_TABLE_BETS);
                if (empty($bet)) {
                    if ($this->getSetting('accept_simple_transaction_id', false)) {
                        $old_style_transaction_id = $oData->gameRoundId;
                        $bet = $this->_getTransactionById($old_style_transaction_id, self::TRANSACTION_TABLE_BETS);
                    }
                }
                if (!empty($bet)) {
                    $transaction_id = $this->stripPrefix($bet['mg_id']);
                    if (empty($aJson['skinid'])) {
                        $aJson['skinid'] = $this->stripPrefix($bet['game_ref']);
                    }
                    if (!isset($aJson['device'])) {
                        $aJson['device'] = $bet['device_type'];
                    }
                }
            }

            if (empty($aJson['skinid'])) {
                $this->log_info[] = "Game ID not found in session nor matching bet.";
                // Redis timed out so we don't have the gameId yet the cancel-game-round request will continue being sent until we return a Success response.
                if ($method == '_cancel') {
                    $this->_response(true);
                }
                $this->_response($this->_getError(self::ER10));
            }
        }

        if (isset($oData->currencyCode)) {
            $aJson['currency'] = $oData->currencyCode;
        }

        // single transaction to process
        $aJson['state'] = 'single';
        $aAction[0]['command'] = $method;

        if (in_array($method, ['_bet', '_win', '_cancel'])) {
            // playerId + gameRoundId is unique. TransactionId must be unique but it does not really matter for roundId.
            $aAction[0]['parameters']['transactionid'] = $transaction_id;
            $aAction[0]['parameters']['roundid'] = $oData->gameRoundId;

            // detect for freespin
            if ($oData->roundType === 'CASINO_FREE_SPIN') {
                $aJson['freespin'] = [
                    'id' => $oData->casinoFreeSpin->freeSpinsId
                ];
                if ($method == '_bet') {
                    $aAction[0]['parameters']['amount'] = $this->convertFromToCoinage($oData->casinoFreeSpin->stake,
                        self::COINAGE_UNITS, self::COINAGE_CENTS);
                }
                if ($method == '_win') {
                    $aAction[0]['parameters']['amount'] = $this->convertFromToCoinage($oData->casinoFreeSpin->win,
                        self::COINAGE_UNITS, self::COINAGE_CENTS);
                }
            } else {
                if ($oData->roundType === 'NORMAL') {
                    if (in_array($method, ['_bet', '_win'])) {
                        /* case when user win jackpot, jackpot win always goes with simple win,
                           and we calculate together, ['amount'] = win + jackpot win.
                           'jpw' parameter to detect what this is a jackpot win in our DB, award_type = 4 */
                        if ($oData->jackpotType !== 'NONE' && $oData->realMoneyJackpotWinInPlayerCurrency) {
                            // total win = simple win + jackpot win
                            $total_win = $oData->realMoneyWin + $oData->realMoneyJackpotWinInPlayerCurrency;
                            $aAction[0]['parameters']['jpw'] = 1;
                        }
                        $aAction[0]['parameters']['amount'] = $this->convertFromToCoinage(
                            (($method === '_bet') ? $oData->realMoneyStake : $total_win ?? $oData->realMoneyWin),
                            self::COINAGE_UNITS, self::COINAGE_CENTS);
                    }
                }
            }
        }
        
        $aJson['action'] = $aAction[0];

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
        
        // Set the game data by the received skinid (is gameid)
        if (isset($this->_m_oRequest->skinid)) {
            $this->_setGameData();
        }

        if (isset($this->_m_oRequest->freespin)) {
            $this->_setFreespin($this->_m_oRequest->playerid, $this->_m_oRequest->freespin->id);
            if (!$this->_isFreespin()) {
                // the frb doesn't exist or is missing??
                $this->_response($this->_getError(self::ER17));
            }
        }
        
        // execute all commands
        foreach ($this->_getActions() as $key => $oAction) {
            
            // Update the user data before each command
            if (isset($this->_m_oRequest->playerid)) {
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
        
        // Update the user data after each command
        if (isset($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }
        $this->_response($mResponse);
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
        $url_params = ['gid' => $p_mGameId, 'lang' => $p_sLang, 'device' => $p_sTarget, 'uid' => $_SESSION['token_uid']];

        return $this->getSetting('launchurl') . '?' . http_build_query($url_params);
    }

    public function createGameSession($uid, $game_id, $lang, $device)
    {
        $this->initCommonSettingsForUrl();
        $base_url = $this->launch_url;

        $is_logged = isLogged();

        $user = cu();

        if(!empty($user)) {
            $uid = $uid ?: $user->getId();
            $this->getUsrId($uid);
            $ud = $user->getData();
        }

        $url_params = [
            'gameId'        => $game_id,
            'walletType'    => $is_logged ? 'DEEP_RM' : null, // real
            'gamePlatform'  => !$is_logged ? strtoupper($device) : null, // demo
            'platform'      => $is_logged ? strtoupper($device) : null, // real
            'currencyCode'  => !$is_logged ? ciso() : null, // here we need to enforce ciso() when in demo mode
            'languageCode'  => !$is_logged ? cLang() : null, // demo
            'brand'         => !$is_logged ? $this->getSetting('externalCasinoId') : null,
            'playerId'      => $is_logged ? $uid : null,
            'playerInfo'    => !$is_logged ? null : [
                'playerId'          => $uid,
                'countryCode'       => phive('CasinoCashier')->getIso3FromIso2($ud['country']),
                'currencyCode'      => $this->getPlayerCurrencyForGame('FUN', $user),
                'languageCode'      => $lang,
                'username'          => $this->isTournament($uid) ? $ud['username'].'e'.$this->t_eid : $ud['username'],
                'gender'            => strtoupper(substr($ud['sex'], 0, 1)),
                'isTestingUser'     => strpos($ud['username'], 'devtest') !== false ? 'true' : 'false',
                'dateOfBirth'       => $ud['dob']
            ]
        ];

        if ($this->isTournamentMode()) {
            $url_params['regulationId'] = 'IE';
        }

        $maxBet = phive('Gpr')->getMaxBetLimit($user);
        if (!empty($maxBet)) {
            $url_params['regulationId'] = 'UK';
        }

        // removes null values and we're left with array elements we need
        $url_params = array_filter($url_params, function ($e) {
            return !is_null($e);
        });

        $aGet = [
            'uid' => $uid,
            'lang' => $lang
        ];

        $url            = $is_logged ? $base_url . '/generate-game-session-url' : $base_url . '?' . http_build_query($url_params);
        $content        = $is_logged ? json_encode($url_params) : '';
        $type           = $is_logged ? Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON : Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML;
        $extra_headers  = $is_logged ?
            "Authorization: Basic " . base64_encode($this->getLicSetting('realplay_username', $user) . ':' .
                $this->getLicSetting('realplay_passwd', $uid)) . "\r\n"
            :
            "Authorization: Basic " . base64_encode($this->getLicSetting('demoplay_username', $user) . ':' .
                $this->getLicSetting('demoplay_passwd', $uid)) . "\r\n";

        $debug_key      = $this->getGpName() . '_out';
        $method         = $is_logged ? 'POST' : 'GET';

        $result = phive()->post($url, $content, $type, $extra_headers, $debug_key, $method);
        $oData = json_decode($result, false);

        $session_id = '';
        if ($is_logged && $oData->status->code === 'OK') {
            $session_id = (string)$oData->data->gameSessionId;
            $session_key = "stakelogic_{$uid}_{$session_id}";
            $this->toSession($session_key, $uid, $game_id, $device);
            $aGet['gameUrl'] = $oData->data->gameSessionUrl;
            $aGet['jsUrl'] = $oData->data->gameClientJsLibUrl;
        } elseif ($oData->status === 'OK') {
            // TODO: when does this situation occur?
            $aGet['gameUrl'] = $oData->gameServerUrl;
            $aGet['jsUrl'] = $oData->gameJsLibUrl;
        }

        $log_response = json_decode($result, true);
        if (empty($oData) || (($oData->status->code ?? '') != 'OK')) {
            $this->_logIt(["Error creating remote session for {$uid}.", json_encode(['response' => $log_response ?: $result, 'request' => $url_params, 'url' => $url])], "stakelogic-error");
            $this->logInfo(["message" => "Error creating remote session."], $uid, "stakelogic-info-generate-game-session-url");
        } else {
            $this->logInfo(
                ["method" => "createGameSession {$uid}_{$session_id}.", 'response' => $log_response, 'url' => $url, 'request' => $url_params],
                $uid,
                "stakelogic-info-generate-game-session-url"
            );
        }

        return $aGet;
    }
    
    /**
     * Get freespins from a user if there are any
     * @return array|null
     */
    private function _getFreeSpins()
    {
        $aFreespins = null;
        $aUserData = $this->_getUserData();
        $aGameData = $this->_getGameData();

        // For tournaments, do not return the free spins available for normal play.
        if ($this->isTournamentMode()) {
            return null;
        }
        
        if($this->_getMethod() === '_init') {
            // inform the gp on init request about the players freespin bonus so player will be able to play them
            $this->setAvailableFreespin($aUserData['id'], $aGameData['game_id']);
        }
        
        if($this->_isFreespin()) {
            // we received a request which is a frb
            $aFreespins = $this->_getFreespinData();
        }
        
        // include this object only when there is a bonus_entry for frb
        // it should have frb_remaining of > 0 (when freespin play start with first round we inform the gp in the init request)
        // it should return the obj always when its a freespin bet play including the last freespin bet play
        if (!empty($aFreespins) && ($aFreespins['frb_remaining'] > 0 || ($this->_isFreespin() && $this->_getMethod() == '_bet'))) {
            
            return [
                'freeSpinsId' => $aFreespins['id'],
                'spinsAvailable' => $aFreespins['frb_remaining'],
                'spinValue' => $this->convertFromToCoinage($this->_getFreespinValue(), self::COINAGE_CENTS,self::COINAGE_UNITS),
                'totalSpins' => $aFreespins['frb_granted'],
                'moneyWon' => $this->convertFromToCoinage($aFreespins['balance'], self::COINAGE_CENTS,self::COINAGE_UNITS),
                'priority' => '-1'
            ];
        }
        return null;
    }

    /**
     * Get bonusses from an user
     */
    private function _getBonuses()
    {
        return null;
    }

    private function _getFundsBalance()
    {
        $aUserData = $this->_getUserData();
        $iCashBalance = null;

        if (!empty($aUserData)) {
            $iCashBalance = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS);
        }

        $mFreespins = $this->_getFreeSpins();
        $mBonusses = $this->_getBonuses();

        $funds_balance = [
            'currencyCode' => $this->getPlayerCurrencyForGame('FUN', $aUserData),
            'realMoney' => $iCashBalance,
            'funMoney' => null,
            'freeSpins' => (($mFreespins !== null) ? [$mFreespins] : null),
            'bonuses' => (($mBonusses !== null) ? [$mBonusses] : null)
        ];

        return $funds_balance;
    }
    
    /**
     * Game session close request. We destroy the session and return true
     * @see Gp::_end()
     */
    protected function _end()
    {
        if ($this->_m_sSecureToken) {
            $this->deleteToken($this->_m_sSecureToken);
        }
        return true;
    }
    
    /**
     * Send a response to gp
     *
     * @param mixed $p_mResponse true if command was executed succesfully or an array with the error details from property $_m_aErrors
     * @return mixed The headers and depending on other response params a json_encoded string, which is the answer to gp
     */
    protected function _response($p_mResponse)
    {
        
        $aResponse = [
            'status' => [],
        ];
        
        if ($p_mResponse === true) {
            $aResponse['status'] = [
                'code' => 'OK',
                'message' => null
            ];
        } else {
            $aResponse['status'] = [
                'code' => (($p_mResponse['status'] === 'INSUFFICIENT_FUNDS') ? 'OK' : $p_mResponse['status']),
                'message' => $p_mResponse['message']
            ];
        }

        if (!in_array($this->_getMethod(), $this->_m_aMapGpMethods)) {
            $action = $this->_m_aMapGpMethods[$this->getGpMethod()] ?? null;
            $this->_setWalletMethod($action);
            $this->_logIt([__METHOD__, sprintf("Setting method %s (%s).", $this->getGpMethod(), $action)]);
        }
        
        switch ($this->_getMethod()) {
            
            case '_init':
                if ($p_mResponse === true) {
                    $aResponse['data'] = [
                        'fundsBalance' => $this->_getFundsBalance()
                    ];
                }
                break;
            
            case '_bet':
                /**
                 * Alex M. 20/02/2020 Small note about below, refactored the overrideDistributionOfCasinoFunds key
                 * to simply return null as far as this integration goes it always returned null now we're not longer
                 * using a function to call that.
                 */
                $aResponse['data'] = [
                    // only 1 of this 3 can be true: approved, insufficientFunds, fundsStructureChanged
                    'approved' => (($p_mResponse === true) ? true : false),
                    'insufficientFunds' => (($p_mResponse === true) ? false : (($p_mResponse['status'] === 'INSUFFICIENT_FUNDS') ? true : false)),
                    'fundsStructureChanged' => false,
                    'cancelledAndDisplayMessage' => false,
                    'message' => (($p_mResponse === true) ? null : $p_mResponse['message']),
                    'overrideDistributionOfCasinoFunds' => null,
                    'fundsBalance' => $this->_getFundsBalance(),
                    'roundSettings' => [
                        'disableJackpotWinnings' => false,
                        'asyncFinishAllowed' => false
                    ]
                ];
                break;
            
            case '_win':
                $mMessage = null;
                $aFreespins = $this->_getFreespinData();
                if (!empty($aFreespins) && $aFreespins['frb_remaining'] <= 0) {
                    $mMessage = [
                        'type' => 'SIMPLE_MESSAGE',
                        'attributes' => [
                            'message' => 'From this point on, your current account will be charged.'
                        ]
                    ];
                }
                
                $aResponse['data'] = [
                    'fundsBalance' => $this->_getFundsBalance(),
                    'message' => $mMessage
                ];
                break;
            
            case '_balance':
                if ($p_mResponse === true) {
                    $aResponse['fundsBalance'] = $this->_getFundsBalance();
                } elseif ($aResponse['status']['code'] != 'OK') {
                    // Status code recommended by Stakelogic for GetBalance errors (banned, not found etc).
                    $aResponse['status']['code'] = 'UNEXPECTED_SERVER_ERROR';
                }
                break;

            case '_cancel':
                // Status code recommended by Stakelogic for Cancel errors.
                $aResponse['status']['code'] = 'OK';
                break;

            case '_end':
                break;

            default:
                $this->_logIt([__METHOD__, sprintf("Unknown method %s", $this->_getMethod())]);
        }

        if (($p_mResponse !== true) && !empty($p_mResponse['status']) && ($p_mResponse['status'] != 'UNAUTHORIZED')) {
            $p_mResponse['responsecode'] = 200;
        }
        $this->_setResponseHeaders($p_mResponse);
        
        $result = json_encode($aResponse);
        $this->_logIt([__METHOD__, $this->getGpMethod(), print_r($p_mResponse, true), $result]);
        $this->logResponse($aResponse);

        echo $result;
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }

    /**
     * Logs a message.
     *
     * @param array $data
     * @param string|null $user_identifier
     * @param string|null $tag
     */
    protected function logInfo(array $data, string $user_identifier = null, string $tag = null)
    {
        if (!$this->getSetting('log-session-debug')) {
            return;
        }

        $tag = ($tag ?: "stakelogic-info");
        if ($user_identifier) {
            $arr = explode('e', $user_identifier);
            $user_identifier = $arr[0] ?? 0;
        } else {
            $user_identifier = $this->log_player_id;
        }

        phive()->dumpTbl($tag, $data, $user_identifier ?: 0);
    }

    /**
     * @param null $response
     */
    protected function logResponse($response = null)
    {
        $duration = round(microtime(true) - ($this->getStartedAt() ?: 0), 4);
        $log_info = [
            'method' => $this->_getMethod(),
            'response' => $response,
            'actions' => $this->_m_oRequest ? json_decode(json_encode($this->_m_oRequest), true) : null,
            'request' => json_decode($this->getGpParams(), true),
            'duration' => $duration,
            'is_slow_query' => ($duration > 1),
        ];
        if (!empty($this->log_info)) {
            $log_info = array_merge(['log_info' => $this->log_info], $log_info);
        }

        $tag = "stakelogic-info-" . $this->getGpMethod() . (empty($this->log_info) ? '' : '-warning');
        $this->logInfo($log_info, null, $tag);
    }

    public function parseJackpots() {
        $parsed_jackpots = [];

        $api_endpoints = $this->getAllJurSettingsByKey('jp_url');
        $partner_ids = $this->getAllJurSettingsByKey('partnerid');
        $auth_username = $this->getLicSetting('realplay_username');
        $auth_password = $this->getLicSetting('realplay_passwd');
        $api_auth_header = "Authorization: Basic " . base64_encode("{$auth_username}:{$auth_password}") . "\r\n";

        foreach ($api_endpoints as $license_code => $api_endpoint) {
            $api_payload = json_encode([
                "currency" => 'EUR', // EUR hardcoded, the system will convert from EUR to other currencies.
                "credentials" => ["partnerid" => $partner_ids[$license_code]]
            ]);

            $response = phive()->post($api_endpoint, $api_payload, null, $api_auth_header,"{$this->getGpName()}-jackpot-curl");
            $jackpots = json_decode($response, true);

            foreach ($jackpots as $jackpot) {
                foreach ($jackpot['games'] as $game_id) {
                    $game = phive("MicroGames")->getByGameRef("{$this->getGpName()}_{$game_id}");
                    if (empty($game)) {
                        continue;
                    }

                    $parsed_jackpots[] = [
                        'local' => 0,
                        'network' => $this->getGpName(),
                        'jurisdiction' => $license_code,
                        'game_id' => $game['game_id'],
                        'jp_name' => $game['game_name'],
                        'module_id' => $game['game_id'],
                        'jp_id' => $jackpot['name'],
                        'currency' => $jackpot['currency'],
                        'jp_value' => round($jackpot['amount'] * 100),
                    ];
                }
            }
        }

        return $parsed_jackpots;
    }
}
