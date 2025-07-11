<?php

require_once __DIR__ . '/Gp.php';

class Greentube extends Gp
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
     *
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
    protected $_m_bUpdateBonusEntriesStatusByWinRequest = false;
    
    /**
     * Insert frb into bet table so in case a frw comes in we can check if it has a matching frb
     *
     * @var bool
     */
    protected $_m_bConfirmFrbBet = true;
    
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
    protected $_m_bForceHttpOkResponse = false;   
    
    /**
     * Skip the check for bets in _hasBet function
     * @var bool
     */
    protected $_m_bSkipBetCheck = true;
    
    private $_m_aMapGpMethods = array(
        'CasinoRound_Balance' => '_balance',
        'CasinoRound_Stake' => '_bet',
        'CasinoRound_Win' => '_win',
        'CasinoRound_CancelStake' => '_cancel',
        'CasinoRound' => '_roundEnd',
    );

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
            'responsecode' => 500,
            'status' => 'InternalError',
            'return' => 'default',
            'code' => 'ER01',
            'message' => 'Internal Server Error.'
        ),
        'ER02' => array(
            'responsecode' => 404,
            'status' => 'TransactionTypeUnknown',
            'return' => 'default',
            'code' => 'ER02',
            'message' => 'Command not found.'
        ),
        'ER03' => array(
            'responsecode' => 401,
            'status' => 'Unauthorized',
            'return' => 'default',
            'code' => 'ER03',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER06' => array(
            'responsecode' => 412,
            'status' => 'InsufficientFunds',
            'return' => 'default',
            'code' => 'ER06',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        'ER08' => array(
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'true',
            'code' => 'ER08',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ),
        'ER09' => array(
            'responsecode' => 404,
            'status' => 'UserUnknown',
            'return' => 'default',
            'code' => 'ER09',
            'message' => 'Player not found.'
        ),
        'ER10' => array(
            'responsecode' => 404,
            'status' => 'GameUnknown',
            'return' => 'default',
            'code' => 'ER10',
            'message' => 'Game is not found.'
        ),
        'ER11' => array(
            'responsecode' => 404,
            'status' => 'TokenInvalid',
            'return' => 'default',
            'code' => 'ER11',
            'message' => 'Token not found.'
        ),
        // FREESPIN_NO_REMAINING
        'ER12' => array(
            'responsecode' => 200,
            'status' => 'InsufficientFunds',
            'return' => 'default',
            'code' => 'ER12',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        // FREESPIN_INVALID
        'ER13' => array(
            'responsecode' => 200,
            'status' => 'InsufficientFunds',
            'return' => 'default',
            'code' => 'ER13',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        // FREESPIN_UNKNOWN
        'ER14' => array(
            'responsecode' => 200,
            'status' => 'InsufficientFunds',
            'return' => 'default',
            'code' => 'ER14',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        'ER15' => array(
            'responsecode' => 403,
            'status' => 'Forbidden',
            'return' => 'default',
            'code' => 'ER15',
            'message' => 'IP Address forbidden.'
        ),
        'ER16' => array(
            'responsecode' => 400,
            'status' => 'BadRequest',
            'return' => 'default',
            'code' => 'ER16',
            'message' => 'Invalid request.'
        ),
    );
    
    private $_m_aRoundEnd = array();
    
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
            ->_setWalletActions()
        ;
        
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
        $this->dumpTst('greentube-test', $this->_m_sInputStream);
        $fileGetContent = $this->_m_sInputStream;
        
        $params = (empty($fileGetContent) ? $_REQUEST : $fileGetContent);
        $this->_setGpParams($params);
        $oData = (empty($fileGetContent) ? json_decode(json_encode($params), false) : json_decode($params, false));
        $this->_logIt([
            __METHOD__,
            print_r($oData, true),
            print_r($_REQUEST, true),
            print_r($_POST, true),
            print_r($_GET, true),
            print_r($_SERVER, true)
        ]);
        if ($oData === null) {
            // request is unknown
            $this->_logIt([__METHOD__, 'unknown request']);
            $this->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }
        
        $aJson = $aAction = array();
        $casinoMethod = null;
        $aMethods = $this->_getMappedGpMethodsToWalletMethods();

        // Define which service is requested
        $aUrlPart = explode('/', $_SERVER['REQUEST_URI']);
        $aUrlPart2 = array_pop($aUrlPart);
        $aUrlPart = array_filter(array_merge($aUrlPart, explode('?', $aUrlPart2)));
        $this->_logIt([__METHOD__, print_r($aUrlPart, true)]);
        
        if (in_array('Transactions', $aUrlPart)) {
            // it's a bet/win/cancel request using POST
            $this->_logIt([__METHOD__, $oData->TransactionType, print_r($aMethods, true)]);
            if (isset($oData->TransactionType) && isset($aMethods[$oData->TransactionType])) {
                $casinoMethod = $oData->TransactionType;
            }
        } else {
            if ($aUrlPart[5] === 'CasinoRound') {
                $casinoMethod = 'CasinoRound';
            } else {
                if ($aUrlPart[4] === 'Cash' && $aUrlPart[5] === 'Users' && ctype_digit($aUrlPart[6])) {
                    // its the userId, it's a balance/status request using GET
                    $casinoMethod = 'CasinoRound_Balance';
                }
            }
        };
        
        if (empty($casinoMethod)) {
            // method to execute not found
            $this->_setResponseHeaders($this->_getError(self::ER02));
            $this->_logIt([__METHOD__, 'method not found']);
            die();
        } else {
            $method = $aMethods[$casinoMethod];
            $this->_setGpMethod($casinoMethod);
        }

        if (isset($_GET['PartnerUserSessionKey'])) {
            $this->_m_sSessionKey = $_GET['PartnerUserSessionKey'];
            $this->_m_sSessionData = $this->fromSession($this->_m_sSessionKey);
            $this->user = cu($this->_m_sSessionData->userid);
            $this->_logIt([__METHOD__, 'Session data: ', print_r($this->_m_sSessionData, true)]);
        }

        $this->dumpTst('user-property', $this->user);
        
        if ($method !== '_roundEnd' && isset($this->_m_sSessionData->userid)) {
            $aJson['playerid'] = $this->_m_sSessionData->userid;
            $this->_logIt([__METHOD__, 'UID by session', print_r($this->_m_sSessionData, true)]);
        } else {
            if (isset($oData->InitiatorUserId)) {
                $aJson['playerid'] = $oData->InitiatorUserId;
                $this->_logIt([__METHOD__, 'UID by $oData->InitiatorUserId', $oData->InitiatorUserId]);
            } else {
                if (ctype_digit($aUrlPart[8])) {
                    $aJson['playerid'] = $aUrlPart[8];
                    $this->_logIt([__METHOD__, 'UID by urlPart', $aUrlPart[8]]);
                } else {
                    $this->_setResponseHeaders($this->_getError(self::ER09));
                    $this->_logIt([__METHOD__, 'UID not found.']);
                    die();
                }
            }
        }
        
        if ($method !== '_roundEnd' && isset($this->_m_sSessionData->gameid)) {
            $aJson['skinid'] = $this->stripPrefix($this->_m_sSessionData->gameid);
            $this->_logIt([__METHOD__, 'GID by session', print_r($this->_m_sSessionData, true)]);
        } else {
            if (isset($oData->Game->GameId)) {
                $aJson['skinid'] = $oData->Game->GameId;
                $this->_logIt([__METHOD__, 'GID by $oData->Game->GameId', $oData->Game->GameId]);
            } else {
                if (isset($_GET['GameId'])) {
                    $aJson['skinid'] = $_GET['GameId'];
                    $this->_logIt([__METHOD__, 'GID by $_GET[\'GameId\']', $_GET['GameId']]);
                } else {
                    $aJson['skinid'] = '';
                    $this->_logIt([__METHOD__, 'GID not found']);
                }
            }
        }
        
        $aJson['device'] = $this->string2DeviceTypeNum($this->_m_sSessionData->device ?? null);

        if (isset($_GET['CurrencyCode'])) {
            $aJson['currency'] = $_GET['CurrencyCode'];
        } else {
            if (isset($oData->currencyCode)) {
                $aJson['currency'] = $oData->currencyCode;
            }
        }
        
        // single transaction to process
        $aJson['state'] = 'single';
        
        $aAction[0]['command'] = $method;
        
        if (in_array($method, array('_bet', '_win', '_cancel', '_roundEnd'))) {
            $aAction[0]['parameters']['roundid'] = 0;
            if (isset($oData->EntityReferences)) {
                foreach ($oData->EntityReferences as $entityReferenceKey => $entityReferenceObj) {
                    if ($entityReferenceObj->EntityType === 'CasinoRound') {
                        // is unique according Alon from greentube so it can be used to confirm wins
                        // 24-07-2017 [2:08:07 PM] Alon B - greentube: btw game round id is unique
                        $aAction[0]['parameters']['roundid'] = $entityReferenceObj->EntityId;
                        $aAction[0]['parameters']['userid'] = $oData->InitiatorUserId;
                    }
                    break;
                }
            }
            
            if (in_array($method, array('_bet', '_win', '_cancel'))) {
                $aAction[0]['parameters']['transactionid'] = $oData->TransactionId;
                $aAction[0]['parameters']['transactionCreationDate'] = $oData->TransactionCreationDate;
                $aAction[0]['parameters']['amount'] =
                    $this->convertFromToCoinage(
                        abs($oData->Amount),
                        self::COINAGE_CENTS,
                        self::COINAGE_CENTS
                    );
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

//		if ($sHash === $this->getHash(http_build_query($aGetParams), self::ENCRYPTION_MD5)) {
        // check if the commands requested do exist
        $this->_setActions();
        
        // Set the game data by the received skinid (is gameid)
        if (isset($this->_m_oRequest->skinid)) {
            $this->_setGameData();
            $aGameData = $this->_getGameData();
            if ($this->_isInhouseFrb() && isset($this->_m_oRequest->playerid)) {
                $this->_setFreespin($this->_m_oRequest->playerid, $aGameData['game_id'], 'game_id');
                if ($this->_isFreespin()) {
                    $this->_m_oRequest->freespin = json_decode(json_encode(array('id' => $this->_getFreespinData('id'))),
                        false);
                }
            }
        }

        $this->setNewExternalSession($this->user, $this->_m_sSessionData);
        
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
//		} else {
//			// secret key not valid
//			$mResponse = $this->_getError(self::ER03);
//		}
        
        // Update the user data after each command
        if (isset($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }
        $this->_response($mResponse);
    }


    protected function _roundEnd(stdClass $p_oParameters)
    {
        $this->_m_aRoundEnd = $this->_hasBet($p_oParameters, true);

        return true;
    }

    
    protected function _response($p_mResponse)
    {
        
        $aUserData = $this->_getUserData();
        $userInfo = $aResponse = array();
        
        if (!empty($aUserData)) {
            $user = cu($aUserData['id']);
            
            if ($user->isSuperBlocked()) {
                $state = 'Blocked';
            } else {
                if ($user->isPlayBlocked()) {
                    $state = 'Banned';
                } else {
                    $state = 'Active';
                }
            }
            
            $userInfo = array(
                'UserId' => $aUserData['id'],
                'State' => $state,
                'LanguageCode' => strtoupper($aUserData['preferred_lang']),
                'CountryCode' => strtoupper($aUserData['country']),
                'CurrencyCode' => strtoupper($aUserData['currency']),
                'Amount' => $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_CENTS),
                // users wallet amount
            );
        }
        
        if ($p_mResponse === true) {
            switch ($this->_getMethod()) {
                case '_balance':
                    $aResponse = $userInfo;
                    $p_mResponse = array(
                        'responsecode' => 200,
                        'status' => '',
                    );
                    break;
                
                case '_roundEnd':
                    $p_mResponse = array(
                        'responsecode' => 200,
                        'status' => '',
                    );
                    $gpParams = json_decode($this->getGpParams(), false);
                    $this->dumpTst('greentube-response-test', $gpParams);

                    $aResponse['EntityType'] = $gpParams->EntityType;
                    $aResponse['EntityId'] = $gpParams->EntityId;
                    $aResponse['EntityReferenceId'] = $this->_m_aRoundEnd['id'];
                    $aResponse['State'] = 'Finished';
                    $aResponse['StartDate'] = date("Y-m-d\TH:i:s.Z\Z", strtotime($this->_m_aRoundEnd['created_at']));
                    break;
                
                case '_bet':
                case '_win':
                case '_cancel':
                    $p_mResponse = array(
                        'responsecode' => 201,
                        'status' => '',
                        'return' => 'default',
                        'message' => 'Created'
                    );
                    
                    $aResponse['TransactionType'] = $this->getGpMethod();
                    $aResponse['TransactionId'] = $this->_m_oRequest->action->parameters->transactionid;
                    $aResponse['TransactionCreationDate'] = $this->_m_oRequest->action->parameters->transactionCreationDate;
                    $aResponse['TransactionReferenceId'] = $this->_getTransaction('txn');
                    // its not the actual mysql insert date but its just a bit off and safe an query to db
                    $aResponse['TransactionReferenceCreationDate'] = $this->_getDateTimeInstance($this->_getTransaction('date'))->format("m-d-Y H:i:s.u");
                    // amount affected by transaction
                    $aResponse['Amount'] = (($this->_getMethod() === '_bet') ? '-' : '') . $this->_m_oRequest->action->parameters->amount;
                    $aResponse['Rake'] = 0;
                    $aResponse['CurrencyCode'] = $userInfo['CurrencyCode'];
                    $aResponse['User'] = $userInfo;
                
            }
        }
        
        $this->_setResponseHeaders($p_mResponse);
        $dateUtc = 'DateUtc: ' . $this->_getDateTimeInstance()->format("Y-m-d\TH:i:s.Z\Z");
        $refId = 'NRGS-RequestReferenceId: nrgs' . $_SERVER['HTTP_NRGS_REQUESTID'];
        $refDate = 'NRGS-ProcessedDateUtc: ' . $_SERVER['HTTP_DATEUTC'];
        
        header($dateUtc); // we need to generate
        header($refId); // come from header received in the request
        header($refDate); // come from header received in the request
        
        $log = array(
            'date-utc: ' . $dateUtc,
            'refId: ' . $refId,
            'refdate: ' . $refDate,
        );
        
        $result = json_encode($aResponse);
        $this->_logIt([__METHOD__, print_r($log, true), print_r($p_mResponse, true), $result]);
        echo $result;
        
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
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
    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        $this->dumpTst('game_id', $p_mGameId);
        $this->initCommonSettingsForUrl();
        //		Content-MD5 := "" | UpperCase( HexadecimalString( MD5( Request-Body ) ) )
        //		Request-HTTP-Verb := "GET" | "POST" | "PUT" | "DELETE"
        //		StringToSign := Request-HTTP-Verb + "\n" + Request-DateUtc-Header + "\n" + Request-URI + "\n" + Content-MD5
        //		Signature := UpperCase( HexadecimalString( HMAC-SHA1( NrgsSecretApiKey, StringToSign ) ) )

        $date = $this->_getDateTimeInstance()->format("Y-m-d\TH:i:s.Z\Z");
        $utcDateHeader = "DateUtc: " . $date;

        $is_logged      = isLogged();
        $user           = cu();
        $ud             = $user ? $user->getData() : null;
        $userID         = $user ? $ud['id'] : uniqid();
        $token          = $user ? $this->getGuidv4($ud['id']) : null;
        $launch_uri     = $is_logged ? $this->getSetting('realplay_launchuri') : $this->getSetting('demoplay_launchuri');
        $rc_popup       = $this->getRcPopup($p_sTarget, $user);

        $url_params = [
            'clientType'             => $p_sTarget == 'mobile' ? 'mobile-device' : null,
            'languageCode'           => strtoupper($p_sLang),
            'PartnerUserSessionKey'  => $is_logged ? $token : null,
            'button.fullscreen.show' => 'false',
            'UserId'                 => $is_logged ? null : $userID, // it's be the uniqid set when there's no player
        ];

        $game_client_parameters = [
            'closeurl'                      => $this->getSetting('redirect_prefix').(string)$this->getLobbyUrl(false),
            'accounthistoryurl'             => $is_logged ? $this->getSetting('redirect_prefix').(string)$this->getHistoryUrl(false, $user) : null,
        ];

        if($rc_popup == 'ingame') {
            $game_client_parameters = array_merge($game_client_parameters, (array)$this->getRealityCheckParameters($user, false,
                ['ukgc.autoplayfeature.enabled', 'realitycheckintervalminutes']));
        }

        // filter null values
        $filtered_url_params =  array_filter($url_params);
        $filtered_game_client_params =  array_filter($game_client_parameters);

        $filtered_url_params['GameClientParameters'] = $filtered_game_client_params;

        //max bet limit param
        $maxBetLimit = phive('Gpr')->getMaxBetLimit($user);
        if (!empty($maxBetLimit)) {
            $filtered_url_params['MaximumBet'] = $maxBetLimit * 100;
        }

        $jsonData = json_encode($filtered_url_params, JSON_UNESCAPED_SLASHES);

        $this->dumpTst('greentube', $jsonData);

        $full_uri = sprintf($launch_uri, $p_mGameId, $userID);
        $this->_logIt([__METHOD__, 'url: ' . $full_uri]);

        $is_forced_game =  key_exists($p_mGameId, $this->getLicSetting('forced_games'));
        $this->dumpTst('is forced game?', $is_forced_game);
        $forced_env = $is_forced_game ? $this->getLicSetting('forced_games')[$p_mGameId] : null;
        $this->dumpTst('forced env', $forced_env);


        $secret     = $is_forced_game ? $this->getSetting('licensing')[$forced_env]['secret'] : $this->getLicSetting('secret');
        $apikey     = $is_forced_game ? $this->getSetting('licensing')[$forced_env]['apikey'] : $this->getLicSetting('apikey');
        $base_url   = $is_forced_game ? $this->getSetting('licensing')[$forced_env]['launch_url'] : $this->getLicSetting('launch_url');


        $stringToSign = 'POST' . "\n" . $date . "\n" . $full_uri . "\n" . strtoupper(md5($jsonData));
        $signature = strtoupper(hash_hmac("sha1", $stringToSign, $secret));
        $auth_header = "Authorization: V1 " . $apikey . ' ' . $signature . "\r\n" . $utcDateHeader;


        $result = phive()->post(
            $base_url . $full_uri,
            $jsonData,
            Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON,
            $auth_header,
            $this->getGamePrefix() . 'out',
            'POST'
        );

        $oData = json_decode($result, false);

        $log = array(
            'headers: ' . $auth_header,
            'stringToSign: ' . $stringToSign,
            'uri: ' . $full_uri,
            'secret: ' . $secret,
            'launchurl: ' . $base_url,
            'json: ' . $jsonData,
            'response: ' . print_r($oData, true)
        );
        $this->_logIt([__METHOD__, print_r($log, true)]);

        if (!empty($result) && !empty($oData->GamePresentationURL)) {
            if($is_logged) {
                $this->toSession($token, $ud['id'], $p_mGameId, $p_sTarget);
            }

            return $oData->GamePresentationURL;
        }
        
        return false;
    }


    /**
     * Here we're encapsulating the logic that's common for game launch, games list and jackpots
     * We're using a couple of parameters to ready the post url and the authentication to it
     *
     * @param $base_url
     * @param $full_uri
     * @param $api_key
     * @param $secret
     * @return array
     */
    private function prepareAndPost($base_url, $full_uri, $api_key, $secret): array
    {
        $launch_url = $base_url . $full_uri;

        $date = $this->_getDateTimeInstance()->format("Y-m-d\TH:i:s.Z\Z");
        $utc_date_header = "DateUtc: " . $date;

        $string_to_sign = 'GET' . "\n" . $date . "\n" . $full_uri . "\n" . '';
        $signature = strtoupper(hash_hmac("sha1", $string_to_sign, $secret));
        $auth_header = "Authorization: V1 " . $api_key . ' ' . $signature . "\r\n" . $utc_date_header;

        $result = phive()->post(
            $launch_url,
            '',
            Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON,
            $auth_header,
            $this->getGamePrefix() . 'jp_out',
            'GET',
            20
        );

        $response_data = json_decode($result, true);

        $log = [
            'headers: ' . $auth_header,
            'stringToSign: ' . $string_to_sign,
            'secret: ' . $secret,
            'launchurl: ' . $launch_url,
            'response: ' . print_r($response_data, true)
        ];

        $this->_logIt([__METHOD__, print_r($log, true)]);

        return $response_data['Jackpots'] ?? [];
    }

    public function addCustomRcParams($regulator, $rcParams)
    {
        $params = [
            'ukgc.autoplayfeature.enabled' => true,
            'realitycheckintervalminutes' => true
        ]; // as of now 23/04/2019 there are no other rc settings

        $jur_params = $this->lic($regulator, 'addCustomParams');

        return array_merge($rcParams, (array)$params, (array)$jur_params);
    }

    public function mapRcParameters($regulator, $rcParams)
    {
        $mappers = [
            'realitycheckintervalminutes' => 'rcInterval'
        ]; // as of now 23/04/2019 there are no default configs

        $mappers = array_merge((array)$mappers, (array)$this->lic($regulator, 'getMapForCustomParams'));

        $rcParams = phive()->mapit($mappers, $rcParams, [], false);

        return $rcParams;
    }

    public function getGamesList($lang, $uid, $launch_cred = null)
    {
        $launch_cred = ($launch_cred == null) ? $this->getLicSettings('licensing', $uid) : $launch_cred;

        $base_url   = $launch_cred['launch_url'];
        $uri        = sprintf($this->getSetting('get_games_uri'), $lang);

        $date = $this->_getDateTimeInstance()->format("Y-m-d\TH:i:s.Z\Z");
        $utcDateHeader = "DateUtc: " . $date;

        $stringToSign = 'GET' . "\n" . $date . "\n" . $uri . "\n";
        $signature = strtoupper(hash_hmac("sha1", $stringToSign, $launch_cred['secret']));
        $authorization = 'Authorization:  V1 ' . $launch_cred['apikey'] . ' ' . $signature . "\r\n" . $utcDateHeader;

        $res = phive()->get($base_url.$uri, 60, $authorization);

        $res = json_decode($res, true); // decode as array
        phive()->dumpTbl('greentube-get-games-parsed-response',$res);

        if($res == null || isset($res['Error'])) {
            return false;
        }

        // reached here, we're good
        return $res['GameList'];
    }

    /**
     * Some points should be made about this provider,
     * As of 09/03/2020 The jackpots are the same across devices for the same game
     * The other currencies are just conversions from their base currency
     *
     * This works by first preparing all the configs required for all the jurisdictions, going
     * through those jurisdictions (and currencies) and calling a post using the unique settings for those jurs, once
     * we receive data from them we add to the list of micro_jps to insert.
     *
     * @return array|void
     */
    public function parseJackpots()
    {
        $jackpots = [];
        $base_urls = $this->getAllJurSettingsByKey('launch_url');
        $all_secrets = $this->getAllJurSettingsByKey('secret');
        $all_api_keys = $this->getSpecificJurSettingsByKey('apikey', ['IT', 'DE', 'DK']);
        $jp_uri = $this->getSetting('launchuri_jp');

        $network_games = phive('MicroGames')->getByNetwork(strtolower($this->getGpName()), true);

        $current_list_of_games = [];

        foreach($network_games as $row) {
            $current_list_of_games[$row['ext_game_name']] = $row;
        }

        foreach ($all_api_keys as $jur => $api_key) {
            $secret = $all_secrets[$jur];
            $currency = phive('Currencer')->getCurrencyByCountryCode($jur)['code'] ?? 'EUR';
            $base_url = $base_urls[$jur] ?? $base_urls['DEFAULT'];
            $uri = sprintf($jp_uri, $currency);

            $jackpots_data = $this->prepareAndPost($base_url, $uri, $api_key, $secret);

            if(empty($jackpots_data)) {
                continue;
            }

            $jackpots = array_merge($jackpots, $this->getAndManageJackpots($current_list_of_games, $jackpots_data, $currency, $jur));
        }

        $this->_logIt([__METHOD__, print_r($jackpots, true)]);
        return $jackpots;
    }

    /**
     * Here we loop through the greentube jackpots and check if we have that game stored and we create the micro_jp
     * rows to add and return a list of them this will be based on currency and jurisdiction
     *
     *
     * @param $our_games
     * @param $greentube_jps
     * @param $currency
     * @param $jur
     * @return array
     */
    private function getAndManageJackpots($our_games, $greentube_jps, $currency, $jur): array
    {
        $micro_jps = [];

        foreach ($greentube_jps as $jp_game) {

            $ext_game_ref = $this->getGpName() . '_' . $jp_game['GameId'];

            if (!key_exists($ext_game_ref, $our_games)) {
                continue;
            }

            $game = $our_games[$ext_game_ref];
            
            $micro_jps[] = [
                'jp_value'      => $this->convertFromToCoinage($jp_game['TotalWinAmount']),
                'jp_id'         => $jp_game['JackpotId'],
                'jp_name'       => $game['game_name'] .' '. ucfirst(strtolower($jp_game['JackpotDescription'])),
                'network'       => $this->getGpName(),
                'module_id'     => $ext_game_ref,
                'currency'      => $jp_game['JackpotCurrency'],
                'local'         => 0,
                'game_id'       => $jp_game['GameId'],
                'jurisdiction'  => $jur
            ];
        }

        return $micro_jps;
    }
}
