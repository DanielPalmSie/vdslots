<?php

require_once __DIR__ . '/Gp.php';

class Spigo extends Gp
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
    protected $_m_bByRoundId = false;

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
    protected $_m_bConfirmFrbBet = true;

    /**
     * Check if incoming win request has a valid bet transaction.
     * Note: this is NOT for freespins but for real money play
     * IMPORTANT: we can not set to true as the game will hang as soon Skywind starts an ingame freespins one by one.
     * eg bets/wins with amount 0 which our system does not accept if its not a freespin given by us.
     *
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
    private $_m_aErrors = [
        'ER05' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => true,
            'code' => 1,
            'message' => 'Duplicate Transaction ID.'
        ],
        'ER06' => [
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => -4,
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ],
        'ER09' => [
            'responsecode' => 200,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => -2,
            'message' => 'Player not found.'
        ],
        'ER11' => [
            'responsecode' => 200,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => -3,
            'message' => 'Token not found.'
        ],
        'ER12' => [
            'responsecode' => 200,
            'status' => 'FREESPIN_NO_REMAINING',
            'return' => 'default',
            'code' => -5,
            'message' => 'No freespins remaining.'
        ],
        'ER18' => [
            'responsecode' => 200,
            'status' => 'IDEMPOTENCE',
            'return' => 'default',
            'code' => 1,
            'message' => 'Duplicate Transaction ID with same amount does exist already.'
        ],
        'ER39' => [
            'responsecode' => 200,
            'status' => 'IDEMPOTENCE',
            'return' => 'default',
            'code' => 1,
            'message' => 'Duplicate Transaction ID with same amount does exist already.'
        ]
    ];

    private $_m_aMethodsMappedApi = [
        'getPlayerBalance' => '_balance',
        'gameSessionRequestBuy' => '_bet',
        'gameRequestBuy' => '_bet',
        'requestRefund' => '_cancel',
        'checkValidRequest' => '_bet', // will be send after 1st fails
        'gameSessionDeposit' => '_win',
        'gameSessionBuyRefund' => '_cancel',
        'gameSessionRequestEnd' => '_end',
        'gameSessionEnd' => 'gameSessionEnd',
        'isPlayerLoggedIn' => '_init',
        'jackpotPayout' => '_win'
    ];

    private $_m_sToken = '';
    private $_i_userId = '';
    // The request auth header
    public function getAuthHeader()
    {
        return 'X-Spigo-Token: ' . $this->getSetting('token');
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

        // prepare the data so it can be executed

        $this->setDefaults();
        $this->preProcessParams();
        
        $a_request = [
            'playerid' => $this->preProcessUser(),
            'skinid' => $this->preProcessSkinId(),
            'hash' => $this->preProcessHash(),
            'platform' => $this->preProcessPlatform(),
            'action' => $this->preProcessAction(),
            'state' => 'single',
            'freespin' => $this->preProcessFreeSpin()
        ];

        // delete null elements from request
        // the json_decode-encode is to transform the inner array in an object
        $this->_m_oRequest = json_decode(
            json_encode(
                array_filter(
                    $a_request,
                    function ($value) {
                        return !is_null($value);
                    }
                ),
                false
            )
        );
         
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
        
        if ($_SERVER['HTTP_X_SPIGO_TOKEN'] === $this->getSetting('token')) {
            // check if the commands requested do exist
            $this->_setActions();

            // Set the game data by the received skinid (is gameid)
            if (isset($this->_m_oRequest->skinid)) {
                $this->_setGameData();
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
                if ($sMethod == 'gameSessionEnd') {
                    $mResponse = $this->getSetting('freespins') ? $this->gameSessionEnd($oAction) : true; 
                }else if (property_exists($oAction, 'parameters')) {
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
            $this->_logIt([__METHOD__, print_r($_SERVER, true), 'token: '.$this->getSetting('token')]);
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
        $aMethods = [
            'getPlayerBalance' => 'playerBalance',
            'gameSessionRequestBuy' => 'withdrawal',
            'gameRequestBuy' => 'buy',
            'checkValidRequest' => 'valid', // will be send after 1st fails
            'gameSessionDeposit' => 'deposit',
            'gameSessionBuyRefund' => 'refund',
            'requestRefund' => 'refund',
            'isPlayerLoggedIn' => 'loggedIn',
            'gameSessionRequestEnd' => 'end',
            'gameSessionEnd' => 'end',
            'jackpotPayout' => 'payout'
        ];
        $sGpMethod = $this->getGpMethod();
        $aResp = [
            $aMethods[$sGpMethod] => ['success' => true, 'errorMessage' => '']
        ];


        if ($p_mResponse === true) {
            $aUserData = $this->_getUserData();

            switch ($sGpMethod) {
                case 'getPlayerBalance':
                    $aFreespins = $this->_getFreespinData('frb_remaining');
                    $iFreeSpins = 0;
                    if (!empty($aFreespins) && $aFreespins['frb_remaining'] >= 0) {
                        $iFreeSpins = $this->_m_aFreespins['frb_remaining'];
                    }
                    $aResp[$aMethods[$sGpMethod]] = [
                        'money' => $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_CENTS),
                        'currencyISO4217' => strtoupper($this->getPlayCurrency($aUserData)),
                        'coins' => 0,
                        'loyalty' => $iFreeSpins,
                        "errorMessage" => ""
                    ];
                    break;
                case 'gameSessionRequestEnd':
                case 'gameSessionEnd':
                    $aResp[$aMethods[$sGpMethod]]['players'][0] = [
                        'playerPartnerIdentifier' => $this->_m_sToken,
                        'money' => $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_CENTS),
                        'currencyISO4217' => strtoupper($this->getPlayCurrency($aUserData))
                    ];
                    break;                
                               
                case 'checkValidRequest':
                    $iTxnId = $this->_getTransaction('txn');
                    if (remIp() === '127.0.0.1') {
                        $iTxnId = "123456789";
                    } else {
                        // TODO
                        $iTxnId = (empty($iTxnId) ? 'a' . $this->randomNumber(10) : $iTxnId);
                    }
                    
                    $aResp[$aMethods[$sGpMethod]] = array_merge(
                        $aResp[$aMethods[$sGpMethod]],
                        [
                            'currencyISO4217' => strtoupper($this->getPlayCurrency($aUserData)),
                            'partnerGameRequestId' => $iTxnId,
                            'partnerGameRequestIdentifier' => $iTxnId,
                        ]
                    );
                    break;
                case 'requestRefund':
                    $iTxnId = $this->_getTransaction('txn');
                    if (remIp() === '127.0.0.1') {
                        $iTxnId = ("123456789");
                    } else {
                        // TODO
                        $iTxnId = (empty($iTxnId) ? 'a' . $this->randomNumber(10) : $iTxnId);
                    }
                    $aResp[$aMethods[$sGpMethod]] = array_merge(
                        $aResp[$aMethods[$sGpMethod]],
                        [
                            'partnerGameRequestId' => $iTxnId,
                            'partnerGameRequestIdentifier' => $iTxnId
                        ]
                    );
                    break;
                case 'jackpotPayout':

                    break;
                case 'gameRequestBuy':
                case 'gameSessionRequestBuy':
                case 'gameSessionDeposit':
                    $iTxnId = $this->_getTransaction('txn');
                    if (remIp() === '127.0.0.1') {
                        $iTxnId = '123456789';
                    }
                    $aResp[$aMethods[$sGpMethod]] = array_merge(
                        $aResp[$aMethods[$sGpMethod]],
                        [
                            'money' => $this->convertFromToCoinage(
                                $this->_getBalance(),
                                self::COINAGE_CENTS,
                                self::COINAGE_CENTS
                            ),
                            'currencyISO4217' => strtoupper($this->getPlayCurrency($aUserData)),
                            'partnerGameRequestId' => $iTxnId,
                            'partnerGameRequestIdentifier' => $iTxnId,
                            "errorMessage" => ""
                        ]
                    );
                    break;
                case 'gameSessionBuyRefund':
                    break;
            }
        } else {
            $aResp[$aMethods[$sGpMethod]]['success'] = false;
            $aResp[$aMethods[$sGpMethod]]['errorMessage'] = $p_mResponse['code'] . ' ' . $p_mResponse['message'];
        }

        header($this->getAuthHeader());
        $this->_setResponseHeaders($p_mResponse);

        $result = json_encode($aResp);
        $this->_logIt([__METHOD__, print_r($p_mResponse, true), $result]);
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
        $aUrl = [];
        $launchurl = $this->getSetting('launchurl_demo');
        $aUrl['siteId'] = $this->getSetting('operator');
        $aUrl['gameId'] = $p_mGameId;

        if (isLogged()) {
            $launchurl  = $this->getSetting('launchurl_real');
            $ud = cuPl()->data;
            $token = $this->getGuidv4($ud['id']);

            $this->toSession($token, $ud['id'], $p_mGameId, $p_sTarget);
            $aUrl['sessionId'] = $token;
            //$aUrl['partnerSessionIdentifier'] = $token;
            $aUrl['alias'] = $ud['username'];
            $aUrl['playerPartnerIdentifier'] = $ud['id'];
            $aUrl['currencyISO4217'] = $ud['currency'];
            $aUrl['localeISO639_ISO3166'] = $ud['preferred_lang'] . '_' . strtoupper($ud['preferred_lang']);
        }
        header($this->getAuthHeader());
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
        if ($this->getSetting('no_out') === true) {
            return $this->getPrefix() . (!empty($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->randomNumber(6));
        }

        $url = $this->getSetting('launchurl_frb');
        //$token = $this->getGuidv4($p_iUserId);
        $token = $p_iUserId;

        $a = [
            'action' => 'setPlayer',
            'playerPartnerIdentifier' => $token,
            'siteId' => $this->getSetting('operator'),
            'alias' => $p_iUserId,
            'sessionIdentifier' => $token,
        ];

        $aPostJson = json_encode($a);
        $result = phive()->post(
            $url,
            $aPostJson,
            self::HTTP_CONTENT_TYPE_APPLICATION_JSON,
            ["Cache-Control: no-cache", $this->getAuthHeader()],
            $this->getPrefix() . 'out',
            'POST'
        );
        $this->_logIt([
                __METHOD__,
                'URL: ' . $url,
                $result
            ]);
        $oData = json_decode($result, false);
        if (!empty($oData->playerSet->success) && $oData->playerSet->success == true) {
            $a = [
                'action' => 'addFreeSpins',
                'gameId' => (int)$this->stripPrefix($p_sGameIds),
                // can be only 1
                'playerPartnerIdentifiers' => [$token],
                'numberOfSpins' => (int)$p_iFrbGranted,
                'startEpochMillis' => (int)($this->_getDateTimeInstance($p_aBonusEntry['start_time'])->format("U") * 1000),
                'expirationEpochMillis' => (int)($this->_getDateTimeInstance($p_aBonusEntry['end_time'])->format("U") * 1000),
                // can be only set currently using their Partners BO
                //'CoinValue' => (string)$this->convertFromToCoinage($this->_getFreespinValue($user->data['id'], $p_aBonusEntry['id']), self::COINAGE_CENTS,self::COINAGE_UNITS),
            ];

            $aPostJson = json_encode($a);
            $result = phive()->post(
                $url,
                $aPostJson,
                self::HTTP_CONTENT_TYPE_APPLICATION_JSON,
                ["Cache-Control: no-cache", $this->getAuthHeader()],
                $this->getPrefix() . 'out',
                'POST'
            );
            $oData = json_decode($result, false);
            $this->_logIt([
                __METHOD__,
                'URL: ' . $url,
                print_r($a, true),
                print_r($p_aBonusEntry, true),
                print_r($oData, true),
                $aPostJson
            ]);
            // response looks like: '{"freeSpinsAdded":{"batchId":3,"success":true,"errorMessage":""}}'
            if (!empty($oData->freeSpinsAdded->success) && !empty($oData->freeSpinsAdded->batchId)) {
                // we return here the bonus ID of the GP so it will get inserted
                // into bonus_entries:ext_id
                return $this->getPrefix() . $oData->freeSpinsAdded->batchId;
            }
        }

        return false;
    }

    /**
     * Delete a free spin bonus entry from the bonus_entries table by bonus entries ID
     * @example {
     *  playerid: <userid:required>,
     *  id: <bonus_entries_id>
     * }
     * @param stdClass $p_oParameters
     * @return bool
     */
    protected function _deleteFrb(stdClass $p_oParameters)
    {
        if (isset($p_oParameters->playerid) && isset($p_oParameters->id)) {
            $user = cu($p_oParameters->playerid);

            if (isset($user->data['id'])) {
                $aBonusEntry = $this->_getBonusEntryBy($user->data['id'], $p_oParameters->id);

                if (!empty($aBonusEntry['ext_id'])) {
                    $a = [
                        'batchId' => $aBonusEntry['ext_id'],
                        'action' => 'removeFreeSpins'
                    ];

                    $url = $this->getSetting('launchurl_frb');
                    $aPostJson = json_encode($a);

                    $result = phive()->post(
                        $url,
                        $aPostJson,
                        self::HTTP_CONTENT_TYPE_APPLICATION_JSON,
                        "Cache-Control: no-cache" . "\r\n" . $this->getAuthHeader() . "\r\n",
                        $this->getPrefix() . 'out',
                        'POST'
                    );
                    $oData = json_decode($result, false);
                    $this->_logIt([
                        __METHOD__,
                        'URL: ' . $url,
                        $aPostJson,
                        print_r($oData, true)
                    ]);
                }
                return parent::_deleteFrb($p_oParameters);
            }
        }

        return false;
    }

    /*
    * Preprocess the method
    * @return String casino method - as received in the request
    */
    protected function preProcessCasinoMethod()
    {
        $gp_a_params = $this->getGpParams();
        $s_casino_method = (isset($gp_a_params->event) ? $gp_a_params->event : null);
        
        if (empty($s_casino_method)) {
            // method to execute not found
            $this->_setResponseHeaders($this->_getError(self::ER02));
            $this->_logIt([__METHOD__, 'method not found']);
            die();
        } else {
            $this->_setGpMethod($s_casino_method);
        }

        return $s_casino_method;
    }

    /**
     * Reads received params
     *
     * @return object
     */
    protected function preProcessParams()
    {
        $o_data = json_decode($this->_m_sInputStream, false);
        
        $this->_setGpParams($o_data);

        if ($o_data === null) {
            // request is unknown
            $this->_logIt([__METHOD__, 'unknown request']);
            $this->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }
        $this->preProcessCasinoMethod();
        return $o_data;
    }

    /**
     * Pre process user id
     *
     * @return int User id
     */
    protected function preProcessUser()
    {
        $o_gp_params = $this->getGpParams();
        // Get user from partner identifier provided by gp
        if (isset($o_gp_params->playerPartnerIdentifier)) {
            $this->_m_sToken = $o_gp_params->playerPartnerIdentifier;
        } else {
            if (isset($o_gp_params->players[0]->playerPartnerIdentifier)) {
                $this->_m_sToken = $o_gp_params->players[0]->playerPartnerIdentifier;
            }
        }
        // Check the session to check if user is logged in
        if (isset($o_gp_params->playerPartnerIdentifier) && isset($o_gp_params->sessionId)) {
            $mSessionData = $this->fromSession($o_gp_params->sessionId);
            if ($mSessionData->userid != $o_gp_params->playerPartnerIdentifier) {
                $this->_setResponseHeaders($this->_getError(self::ER09));
                $this->_logIt([__METHOD__, 'UID by session token not found.',print_r($mSessionData, true)]);
            }
        }

        if (empty($this->_m_sToken)) {
            $this->_setResponseHeaders($this->_getError(self::ER09));
            $this->_logIt([__METHOD__, 'UID not found.']);
        }
        $this->_logIt([__METHOD__, 'UID by cust_id', print_r($o_gp_params->playerPartnerIdentifier, true)]);
        $this->_i_userId = $this->_m_sToken;
        return $this->_m_sToken;
    }

    /**
     * Pre process user id
     *
     * @return int GameId
     */
    protected function preProcessSkinId()
    {
        $o_gp_params = $this->getGpParams();
        if (isset($o_gp_params->gameId)) {
            $this->_logIt([__METHOD__, 'GID by $oData->gameId', $oData->gameId]);
            return $o_gp_params->gameId;
        } else {
            $this->_logIt([__METHOD__, 'GID not found']);
            if ($this->getGpMethod() === "getPlayerBalance") {
                // gameid is required to include bonus balance correctly
                // will enter this condition when multi session is tried to be played and session got deleted because of it
                $this->_logIt([__METHOD__, 'Can not include bonus balance in balance request because of missing GameId.']);
                $this->deleteToken($o_gp_params->playerPartnerIdentifier);
                $this->_response($this->_getError(self::ER11));
            }
            return null;
        }
    }
    /**
     * Pre process hash
     *
     * @return String hash or NULL
     */
    protected function preProcessHash()
    {
        $o_gp_params = $this->getGpParams();
        if (isset($o_gp_params->merch_id)) {
            return getHash($o_gp_params->merch_id.$o_gp_params->merch_pwd, self::ENCRYPTION_SHA1);
        }
        return null;
    }

    /**
     * Pre process platform
     *
     * @return String platform or NULL
     */
    protected function preProcessPlatform()
    {
        $o_gp_params = $this->getGpParams();
        if (isset($o_gp_params->isMobile) && $o_gp_params->isMobile  ||  isset($o_gp_params->players)  && $o_gp_params->players[0]->isMobile) {
            return 'mobile';
        }
        return 'desktop';
    }

    /**
     * Pre process platformaction
     *
     * @return String action or NULL
     */
    protected function preProcessAction()
    {
        $o_gp_params = $this->getGpParams();
        $s_method = $this->getGpMethod();
        $s_command = $this->getWalletMethodByGpMethod($s_method);
        $a_actions_with_params = [
            'gameSessionRequestBuy','gameRequestBuy','checkValidRequest',
            'gameSessionDeposit','gameSessionBuyRefund','requestRefund',
            'jackpotPayout'
        ];
        
        $a_action = ['command' => $s_command];
        // Action specific parameters
        if (in_array($s_method, $a_actions_with_params)) {
            $a_action['parameters'] = [];
            $a_action['parameters']['transactionid'] = $o_gp_params->transactionId;
            if (isset($o_gp_params->buyTransactionId)) {
                $a_action['parameters']['transactionid'] = $o_gp_params->buyTransactionId;
                $a_action['parameters']['roundid'] = $o_gp_params->buyTransactionId;
            } else {
                $a_action['parameters']['roundid'] = $o_gp_params->transactionId;
            }
            if (isset($o_gp_params->money)) {
                $a_action['parameters']['amount'] = $this->convertFromToCoinage($o_gp_params->money, self::COINAGE_CENTS, self::COINAGE_CENTS);
            }
            if (isset($o_gp_params->moneyWon)) { // JACKPOT
                $a_action['parameters']['amount'] = $this->convertFromToCoinage($o_gp_params->moneyWon, self::COINAGE_CENTS, self::COINAGE_CENTS);
            }
            if ($s_method == 'gameSessionDeposit' || $s_method == 'jackpotPayout') {
                $a_action['parameters']['bettransactionid'] = $o_gp_params->buyTransactionId;
            }
        }
        return $a_action;
    }

    /**
     * detect for freespin either ingame or given by vs
     *
     * @return object
     */
    protected function preProcessFreeSpin()
    {
        $o_gp_params = $this->getGpParams();
        // detect for freespin 
        if ( $this->getSetting('freespin') && isset($o_gp_params->players) && isset($o_gp_params->players[0]->freespinsBatchId) ) {
            $ext_id = $this->getPrefix() . $o_gp_params->players[0]->freespinsBatchId;

            $this->_setFreespin($this->_i_userId, $ext_id, 'ext_id');
            if (!$this->_isFreespin()) { // Check if user has Bonus
                $this->_response($this->_getError(self::ER17));
            }
            return ['id' => $this->_getFreespinData('id')];
        }
        return null;
    }

    private function userHasFreeSpins()
    {
        $a_bonusEntries = $this->getBonusEntryByGameId();
        return isset($a_bonusEntries['frb_remaining']) && $a_bonusEntries['frb_remaining'] > 0;
    }

    /*
        Freespins are handled on game session end for spigo
        There's no implementation for this in GP and we use directly the methods from Casino to implement this
        We will insert a 0 amount bet with a matching win
        This method respects idempotence and received freespins left must be lower than remaining 
    */
    public function gameSessionEnd($oAction)
    {
        $oGpParams = $this->getGpParams();
        $player = $oGpParams->players[0];
        $aFrData = $this->_getFreespinData();
        $userData = $this->_getUserData();
        $gameData = $this->_getGameData();

        /* Spigo only sends win transactions with freespins */
        // 1. Check that we have freespins remaining 
        if ($aFrData['frb_remaining'] < $player->freespinsSpent) {
            return $this->_getError(self::ER17); // no frb remaining
        }
        // 2. Create a fake bet 
        $p_oParameters_bet = [
            roundid => $player->buyTransactionId,
            transactionid => $player->buyTransactionId,
            amount => 0
        ];

        if (! $this->_bet(json_decode(json_encode($p_oParameters_bet)))) {
            return $this->_getError(self::ER05); // duplicate transaction
        }
        // 3. Create a fake win        
        if( ! $this->insertWin(
                    $userData,
                    $gameData,
                    $userData['cash_balance'],
                    $player->buyTransactionId,
                    $player->freespinsValueOfWinnings,
                    $this->_getBonusBetCode(),
                    $this->getPrefix() . $player->buyTransactionId,
                    $this->_getAwardTypeCode($oGpParams)
                )) {
            return $this->_getError(self::ER01);
        }
        
        // 4. update remaining freespins
        $query = "UPDATE bonus_entries
        SET frb_remaining = frb_remaining - " . $player->freespinsSpent . "
        WHERE id = " . phive("SQL")->escape($aFrData['id']) . "
        AND user_id = " . phive("SQL")->escape($userData['id']) . "
        AND bonus_type = 'freespin' LIMIT 1";
        $result = phive('SQL')->sh( $userData['id'], '', 'bonus_entries')->query($query);
        $this->_logIt([__METHOD__, 'UPDATING BONUS ENTRIES', $query, $result]);
        if($result){            
            // 5. update user balance
            $this->changeBalance(
                    $userData['id'],
                    $player->freespinsValueOfWinnings,
                    'Freespin win',
                    2
                );
            
        }

        return true;
    }
}
