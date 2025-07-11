<?php

require_once __DIR__ . '/Gp.php';

class Oryx extends Gp
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

    // This is so we have a place to store the sessionid and have access to it in the preprocess and
    private $_m_sToken = '';

    private $_cancel_all_trans_in_round = false;
    
    private $_m_aMapGpMethods = [
        'authenticate' => '_init',
        'balance' => '_balance',
        'betwin' => '_bet_win',
        'bet' => '_bet',
        'win' => '_win',
        'transaction-change' => '_cancel'
    ];

    /**
     * valid token error.
     * @var string
     */
    const ER29 = 'ER29';

    /**
     * ROUNDID NON EXISTANT ERROR
     * @var string
     */
    const ER30 = 'ER30';

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
        'ER01' => [
            'responsecode' => 500, // used to send header to GP if not enforced 200 OK
            'status' => 'SERVER_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '-1', // change this to whatever the GP likes to receive as code
            'message' => 'Server processing general error.' // change this to whatever the GP likes to receive as message
        ],
        'ER04' => [
            'responsecode' => 500,
            'status' => 'ERROR',
            'return' => 'default',
            'code' => 'ER04',
            'message' => 'Bet transaction ID has been cancelled previously.'
        ],
        'ER03' => [
            'responsecode' => 401,
            'status' => 'API_AUTHENTICATION_ERROR',
            'return' => 'default',
            'code' => '128',
            'message' => 'Authentication (username, password) for api call not correct.'
        ],
        'ER05' => [
            'responsecode' => 498,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => 'default',
            'code' => 'ER05',
            'message' => 'Duplicate Transaction ID.'
        ],
        'ER06' => [
            'responsecode' => 200,
            'status' => 'OUT_OF_MONEY',
            'return' => 'default',
            'code' => 'ER06',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ],
        'ER08' => [
            'responsecode' => 400,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER08',
            'message' => 'Transaction provided by Aggregator was not found in platform (interesting for TransactionChange method)'
        ],
        'ER09' => [
            'responsecode' => 404,
            'status' => 'API_AUTHENTICATION_ERROR',
            'return' => 'default',
            'code' => 'ER09',
            'message' => 'Authentication (username, password) for api call not correct.'
        ],
        'ER11' => [
            'responsecode' => 500,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => '100',
            'message' => 'Token provided in request not found in Wallet.'
        ],
        'ER16' => [
            'responsecode' => 400,
            'status' => 'REQUEST_DATA_FORMAT',
            'return' => 'default',
            'code' => 'ER16',
            'message' => 'Data format of request not as expected.'
        ],
        'ER28' => [
            'responsecode' => 500,
            'status' => 'TRANSACTION_ALREADY_CANCELLED',
            'return' => 'default',
            'code' => 'ER28',
            'message' => 'Transaction ID has been cancelled already.'
        ],
        'ER29' => [
            'responsecode' => 500,
            'status' => 'TOKEN_NOT_VALID',
            'return' => 'default',
            'code' => 'ER29',
            'message' => 'Token provided in request not valid in Wallet.'
        ],
        'ER30' => [
            'responsecode' => 400,
            'status' => 'ROUND_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER30',
            'message' => 'RoundId provided in request not found in Wallet.'
        ],

    ];
    
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
     * Inform the GP about the amount of freespins available for a player.
     * The player can open the game anytime. The GP already 'knows' about the freespins available to the player.
     * Often if the GP keeps track itself and send the wins at the end when all FRB are finished like with Netent.
     *
     * @param int $p_iUserId The user ID
     * @param string $p_sGameIds The internal gameId's pipe separated Example: vs25a, vs9c, vs20s
     * @param int $p_iFrbGranted The frb given to play
     * @param string $bonus_name
     * @param array $p_aBonusEntry The entry from bonus_types table
     * @return bool|string|int If not false than bonusId is returned otherwise false (freespins are not activated)
     */
    public function awardFRBonus($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry)
    {
        if ($this->getSetting('no_out') === true) {
            $ext_id = (!empty($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->getGuidv4($p_iUserId));
            $this->attachPrefix($ext_id);
            return $ext_id;
        }
        $this->_logIt([ __METHOD__,'parameters (($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry)": ' .$p_iUserId.' - '.$p_sGameIds.' - '.$p_iFrbGranted.' - '.$bonus_name.' - '.print_r($p_aBonusEntry, true)]);

        $start_date = new DateTime($p_aBonusEntry['start_time'], new DateTimeZone('UTC'));
        $end_date = new DateTime($p_aBonusEntry['end_time'], new DateTimeZone('UTC')); // might not be necessary

        $p_sGameIds = explode(',', $this->stripPrefix($p_sGameIds)); // weather it's simply one id in the string or multiple (GAM_NAR, ORYX_HTML_BBB) we send them id(s) in array

        $coin_size = $this->_getFreespinValue($p_iUserId, $p_aBonusEntry['id']);

        $a = [
            'playerId' => (string) $p_iUserId,
            "externalId" => (string) $p_aBonusEntry['id'],
            "endDate" => $end_date->format(DateTime::ATOM),
            'games' => $p_sGameIds, // array of gamesCodes (gameIds)
            "type" => "NUM_ROUNDS",
            "numAwarded" => (int)$p_iFrbGranted,
            'betAmount' => (int)$coin_size, // size in cents with which free round will be played.
            "startDate" => $start_date->format(DateTime::ATOM),
        ];

        $authorizationHeader = "Authorization: Basic " . base64_encode($this->getLicSetting('username', $p_iUserId) . ':' . $this->getLicSetting('password', $p_iUserId)) . "\r\n";
        $url = $this->getLicSetting('service_api', $p_iUserId);
        $res = phive()->post($url, json_encode($a), 'application/json', $authorizationHeader, 'oryx-curl', 'POST', 60);

        $this->_logIt([
                __METHOD__,
                'base64:' . base64_encode($this->getLicSetting('username', $p_iUserId) . ':' . $this->getLicSetting('password', $p_iUserId)) . ' => ' . $this->getLicSetting('username', $p_iUserId) . ':' . $this->getLicSetting('password', $p_iUserId),
                'url: ' . $url . ' POST params: ' . json_encode($a),
                'Result:' . print_r($res, true),
                'Headers: ' . print_r(phive()->res_headers, true)
            ]
        );

        $res = json_decode($res, true); // decode as array

        return isset($res['error']) ? false : true;
    }

    /**
     * Since Oryx only sends the token on the authenticate call we need to create our own token
     * for the rest of the calls, using stuff that they're always sending, like game and player id.
     *
     * @return string The key.
     */    
    public function getSessionKey(){
        $game_id = $this->_m_oRequest->skinid ?? $this->_m_oRequest->gameCode;
        return "oryx-{$game_id}-{$this->_m_oRequest->playerid}";
    }
    
    protected function _init(){
        // After the parent _init call we will have the ext session id added to the session data in case
        // we're looking at a jurisdiction that need it.
        parent::_init();
        // Here we can change the key for the session data as we already have the ext session id added.
        $this->changeSessionKey($this->_m_sSessionKey, $this->getSessionKey());
        return true;
    }
    
    /**
     * Pre process data received from GP
     *
     * @return object
     */
    public function preProcess()
    {
        /**
         * Find a bet by transaction ID or by round ID.
         * Mainly when a win comes in to check if there is a corresponding bet. If the transaction ID used for win is the
         * same as bet set to false otherwise true. Default true. Make sure that the round ID send by GP is an integer
         * this needs to be set true as we check if there is even a roundId and if there isn't we set it to false
         * @var boolean
         */
        $this->_m_oRequest = true;

        $this->setDefaults();

        $fileGetContent = $this->_m_sInputStream;
        $this->_logIt([__METHOD__, 'fileGetContent: ', print_r($fileGetContent, true)]);

        $params = (empty($fileGetContent) ? $_REQUEST : $fileGetContent);
        $oData = (empty($fileGetContent) ? json_decode(json_encode($params), false) : json_decode($params, false));

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
        $casinoMethod = $oData->method;

        // Set the external Oryx method
        $this->_setGpParams($casinoMethod);
        
        $request = $_REQUEST;
        $requestParts = explode('/', $request['action']);

        $authenticate = in_array('authenticate', $requestParts);
        $balance = in_array('balance', $requestParts);
        $gameTransaction = in_array('game-transaction', $requestParts);
        $freespin_win = in_array('free-rounds', $requestParts) && in_array('finish', $requestParts); // free-round/finish

        $action = "NONE";

        if(isset($oData->action)) {
            $action = $oData->action;
        } else if ($oData->roundAction) {
            $action = $oData->roundAction;
        } else {
            unset($action);
        }

        // game-transaction/ is used for both transactions and cancelling a round, this is totally dependant on the action of the request
        if($authenticate || $balance || $gameTransaction) {
            if(!empty(end($requestParts))) {
                $casinoMethod = end($requestParts);
            } else {
                $casinoMethod = $requestParts[count($requestParts)-2]; // in case it might read game-transaction/ with the last being an empty space
            }
        } else if ($freespin_win) { // here we want to do some custom logic since the requset body is quite different, we will return the object here to exec
            $this->_m_oRequest = $this->preProcessFreespinWin($oData); // later a freespin object is created
            return $this;
        }

        if(isset($action) && $action == 'CANCEL') {
            $casinoMethod = 'transaction-change';
        }

        if (empty($casinoMethod)) {
            // method to execute not found
            $this->_setResponseHeaders($this->_getError(self::ER02));
            die();
        } else {
            $this->_setGpMethod($casinoMethod);
        }


        $mSessionData = null;

        switch($casinoMethod) {
            case 'authenticate':

                // basically if the request looks something like /players/token
                if(sizeof($requestParts) < 3 && $authenticate && in_array('players', $requestParts)) {
                    $this->_m_oRequest = json_decode(json_encode($this->_getError(self::ER11)), false);
                    return $this;
                }


                $mSessionData = $this->fromSession($requestParts[1]); // token from gp
                // we need to return to the aggregator an error message regarding the token

                // if no session was found then, the token they sent is incorrect
                if (!isset($mSessionData->sessionid)) {
                    $this->_m_oRequest = json_decode(json_encode($this->_getError(self::ER29)), false);
                    return $this;
                } else {
                    $this->_m_sToken = $mSessionData->sessionid;
                }

                $aJson['playerid'] = $playerId = $mSessionData->userid;
                $aJson['skinid'] = $this->stripPrefix($mSessionData->gameid);
                break;

            case 'balance':
                // basically if the request looks something like /players/balance
                if(sizeof($requestParts) < 3 && $balance && in_array('players', $requestParts)) {
                    $this->_m_oRequest = json_decode(json_encode($this->_getError(self::ER09)), false);
                    return $this;
                }
                $userId = $requestParts[1];
                $aJson['playerid'] = $playerId = $userId;
                $aJson['skinid'] = $this->stripPrefix($oData->gameCode);
                break;

            case 'game-transaction':                
                if(isset($oData->bet) && isset($oData->win)) {
                    $casinoMethod = 'betwin';
                } else if(isset($oData->bet)) {
                    $casinoMethod = 'bet';
                } else if (isset($oData->win)) {
                    $casinoMethod = 'win';
                }
                $aJson['playerid'] = $oData->playerId;
                $aJson['skinid'] = $this->stripPrefix($oData->gameCode);
                break;

            case 'transaction-change':
                $aJson['playerid'] = $oData->playerId;
                $aJson['skinid'] = $this->stripPrefix($oData->gameCode);
        }

        if(!isset($aJson['playerid']) || empty($aJson['playerid'])) {
            $this->_setResponseHeaders($this->_getError(self::ER09));
            die();
        }
        if(!isset($aJson['skinid'])) {
            $aJson['skinid'] = $gameId = '';
        }
        if (in_array($casinoMethod, ['authenticate', 'balance', 'game-transaction', 'bet', 'win', 'betwin', 'transaction-change'])) {

            switch($casinoMethod){

                case 'authenticate':
                    $aAction['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                    if(isset($mSessionData)) {
                        $aJson['token'] = $mSessionData->sessionid;
                    }

                    if (isset($mSessionData->gameid)) {
                        $aJson['gameCode'] = $this->stripPrefix($mSessionData->gameid);
                    }
                    $aJson['state'] = 'single';
                    $aJson['action'] = $aAction;
                    break;

                case 'balance':
                    $aAction['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                    $aJson['state'] = 'single';
                    $aJson['action'] = $aAction;
                    break;

                case 'game-transaction':
                case 'bet':
                case 'win':
                case 'betwin':

                    // $oData->playerId is a required param that they always send for any game-transaction (bet, win, betwin)
                    $this->user = cu($oData->playerId);

                    $aJson = array_merge($aJson, $this->preProcessGameTransaction($oData, $casinoMethod));

                    break;

                case 'transaction-change':
                    $aJson['state'] = 'single';
                    $this->user = cu($oData->playerId);

                    if(isset($oData->transactionIds) && (is_array($oData->transactionIds) || is_object($oData->transactionIds) )) {
                        $this->_cancel_all_trans_in_round  = true;
                        $aJson['state'] = 'multi';
                        $trans_count = 1;
                        foreach ($oData->transactionIds as $trans_id) {

                            $aAction[$trans_count]['command'] = $this->getWalletMethodByGpMethod('transaction-change');

                            $aAction[$trans_count]['parameters'] = [
                                'transactionid' => $trans_id,
                                'gameCode' => $this->stripPrefix($oData->gameCode)
                            ];

                            $trans_count++;

                        }

                        $aJson['actions'] = $aAction;

                    } else {
                        $aAction[0]['command'] = $this->getWalletMethodByGpMethod('transaction-change');


                        $aAction[0]['parameters'] = [
                            'transactionid' => $oData->transactionId,
                            'gameCode' => $this->stripPrefix($oData->gameCode)
                        ];

                        $aJson['action'] = $aAction[0];
                    }

                    break;

            }
        } else {
            $aAction['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
            $aJson['state'] = 'multi';
            $aJson['action'] = $aAction;
        }


        // detect for freespin
        if (isset($oData->freeRoundExternalId)) {
            $aJson['freespin'] = [
                'id' => $oData->freeRoundExternalId,
            ];
        }

        $this->_m_oRequest = json_decode(json_encode($aJson), false);
        $this->_logIt([__METHOD__, 'final output: ',  print_r($this->_m_oRequest, true)]);
        return $this;
    }
    
    /**
     * Execute the requested command from gaming provider
     * @return void
     */
    public function exec()
    {
        $mResponse = false;
        if(isset($this->_m_oRequest->responsecode)) {
            $errorResponse = (array) $this->_m_oRequest;
            $this->_response($errorResponse);
        } else {
            // check if the commands requested do exist
            $this->_setActions();
            // Set the game data by the received skinid (is gameid) balance doesn't recieve gameid
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

            $this->_setUserData();
            
            if($this->getGpMethod() != 'authenticate' && $this->useExternalSession($this->_getUserData()['id'])){
                // This is just to make sure that the ext session data is being set.
                $session_data = $this->fromSession($this->getSessionKey());
            }
            
            // execute all commands
            foreach ($this->_getActions() as $key => $oAction) {

                // Update the user data before each command
                // here we are checking that playerid was passed into request
                // we set teh property _m_mUserData and property uid
                $this->_setUserData();

                $sMethod = $oAction->command;

                $this->_setWalletMethod($sMethod);
                // command call return either an array with errors or true on success
                if (property_exists($oAction, 'parameters')) {
                    $mResponse = $this->$sMethod($oAction->parameters); // here we're entering bet
                    // when we're cancelling a round by the individual transaction we want to continue cancelling the rest of the transaction if either error was caught and return OK
                    if($this->_cancel_all_trans_in_round && is_array($mResponse)) {
                        // only with trans not found and trans already cancelled
                        if($mResponse['code'] == 'ER28' || $mResponse['code'] == 'ER08') {
                            $mResponse = true;
                        }
                    }
                } else {
                    $mResponse = $this->$sMethod(); // this can be _init, _balance, _bet, _end,

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

        }

        $this->_response($mResponse);
    }
    
    protected function _response($p_mResponse)
    {
        $this->_logIt([__METHOD__, print_r($p_mResponse, true)]);
        $aResponse = $aResp = [];
        $balance = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_UNITS, self::COINAGE_UNITS);

        if ($p_mResponse === true) {

            $aUserData = $this->_getUserData();
            $aResponse['balance'] = $balance;

            if(in_array($this->getGpMethod(), ['authenticate']) ){
                $user = cu($aUserData['id']);
                $aResponse['playerId']          = $aUserData['id'];
                $aResponse['currencyCode']      = strtoupper($this->getPlayCurrency($aUserData));
                $aResponse['languageCode']      = $this->getThreeLetterLanguageCode($aUserData['preferred_lang']);
                $aResponse['brand']             = 'videoslots';

                $maxbetLimit = phive('Gpr')->getMaxBetLimit($user);
                if (!empty($maxbetLimit)) {
                    $aResponse['maxBet'] = $this->convertFromToCoinage($maxbetLimit, self::COINAGE_UNITS, self::COINAGE_CENTS);
                }

                $session = $this->fromSession($this->_m_sToken);

                $device = isset($session) && !empty($session) ? $session->device : null;

                $u_obj = cu($aUserData['id']);

                if($this->getRcPopup($device, $aUserData['id']) == 'ingame') {
                    $this->dumpTst('hit here', 'rc ingame');
                    $rc_params = $this->getRealityCheckParameters($u_obj, false,
                        ['loginSessionDuration', 'loginTimestamp', 'realityCheckFrequency']);
                    $aResponse = array_merge($aResponse, (array)$rc_params);
                }
            }

            if(in_array($this->getGpMethod(), ['game-transaction', 'transaction-change'])) {
                $aResponse['responseCode'] = 'OK';
            }


            $aResp = $aResponse;
        } else {
            $responseCode = $p_mResponse['responsecode'];
            $aResp = [
                'responseCode' => $p_mResponse['status'],
                'errorDescription' => $p_mResponse['message']
            ];
        }

        if (!array_key_exists('errorDescription', $aResp)) {
            $this->_m_bForceHttpOkResponse = true;
            $this->_setResponseHeaders($aResp);
        }
        // if there's any kind of error we want to send them anything that isn't a status 200
        else if(array_key_exists('errorDescription', $aResp) && $aResp['responseCode'] != 'OUT_OF_MONEY' ) {
            $this->_m_bForceHttpOkResponse = false;
            $this->_setOryxResponseHeaders($responseCode, $aResp);

        }
        // case of out of mnaey where we need to also send balance
        else if(array_key_exists('errorDescription', $aResp) && $aResp['responseCode'] == 'OUT_OF_MONEY') {
            $aResp = [
                'balance' => $balance,
                'responseCode' => 'OUT_OF_MONEY'
            ];
            $this->_m_bForceHttpOkResponse = false;
            $this->_setOryxResponseHeaders($responseCode, $aResp);
        }

        $result = json_encode($aResp);
        $this->_logIt([__METHOD__, print_r($p_mResponse, true), $result]);

        echo $result;
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();

    }

    /**
     * overriding Gp.php method since we cannot use $p_mResponse['status'] in the response body
     * Set correct response headers for the response to GP
     * Depending on $p_mResponse, choose to response always with a 200 OK or different response header depending on GP needs
     * @param mixed $p_mResponse On failure: an array with message and code about what failed or true on success
     * @param bool $p_bReturnStatusError Return the status from the array $this->_m_aErrors or the code message from $this->_m_aHttpStatusCodes. Default: false (so $this->_m_aHttpStatusCodes)
     * @return void
     */
    protected function _setOryxResponseHeaders($responseCode, $p_mResponse = null)
    {
        $this->_delayResponseTime();
        if ((!empty($p_mResponse) && $this->_m_bForceHttpOkResponse === false) || $p_mResponse['status'] == 'UNAUTHORIZED') {
            if (in_array(substr(php_sapi_name(), 0, 3), array('cgi', 'fpm'))) {
                header('Status: ' . $responseCode . ' ' . $p_mResponse['responseCode']);
            } else {
                $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
                header($protocol . ' ' . $p_mResponse['responseCode'] . ' ' . $responseCode);
            }
        } else {
            // Send a 200 OK response
            header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK');
        }

        if ($p_mResponse['status'] !== 'UNAUTHORIZED') {
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Content-type: ' . $this->_m_sHttpContentType . '; charset=utf-8');
        }
    }

    /**
     * Lic router for GP specific methods
     *
     * @param string $country The country / jurisdiction
     * @param string $base_method The method name without the country postfix.
     * @param array $args=[] The args to call the method with.
     *
     * @return mixed The result of the method invocation or null if no method was found.
     */
    public function lic($country, $base_method, $args = []){
        $method = $base_method.$country;
        if(!method_exists($this, $method)){
            return null;
        }
        return call_user_func_array([$this, $method], $args);
    }

    public function filterParameters($key)
    {
        $keys_needed = ['jurisdiction', 'realityCheck', 'loggedInTime'];

        return in_array($key, $keys_needed);
    }

    public function addCustomRcParams($regulator, $rcParams)
    {
        $this->dumpTst('hit here', 'addCustom rc params');

        // set true to the params that need to be mapped
        $provider_rc_params = array_merge([
                'loginSessionDuration'          => true,
                'realityCheckFrequency'         => true,
            ],
            (array)$this->lic($regulator, 'addCustomRcParams'));


        return array_merge($rcParams, $provider_rc_params);
    }

    public function addCustomRcParamsSE()
    {
        $jurisdiction_rc_params = [
            'loginSessionDuration'      => true,
            'loginTimestamp'            => true
        ];

        return $jurisdiction_rc_params;
    }

    /**
     * This will modify the params of se based on the specifications of the gp
     * @return array
     */
    public function manageCustomRcParamsSE($params, $current_user)
    {
        // seconds, between the time of player login (start of login session) time and midnight, January 1, 1970 UTC.
        $params['loginTimestamp'] = (int)round(strtotime($current_user->getCurrentSession()['created_at'])); // TODO check if we can replace with (int)cu()->getSessionLength('s', 2) /Paolo

        return $params;
    }

    public function mapRcParameters($regulator, $rcParams)
    {
        $cu = cu($this->_getUserData()['id']);

        $mapping = [
            'loginSessionDuration' => 'rcElapsedTime',
            'realityCheckFrequency' => 'rcInterval',
        ];

        $mapping = array_merge($mapping, (array)$this->lic($regulator, 'getMapRcParameters'));

        // apply mapping
        $rcParams = phive()->mapit($mapping, $rcParams, [], false);
        // common params that need modification
        $rcParams['realityCheckFrequency'] = $rcParams['realityCheckFrequency'] * 60;

        // jurisdiction params that need modification
        $jur_mods = $this->lic($regulator, 'manageCustomRcParams', [$rcParams, $cu]);

        $rcParams = (!empty($jur_mods)) ? $jur_mods : $rcParams;

        return $rcParams;
    }

    private function getThreeLetterLanguageCode($lang = '')
    {
        if(empty($lang)) {
            $lang = phive('Localizer')->getCurLang();
        }

        $language_code = $this->getSetting('languageCodes')[$lang];

        return (empty($language_code)) ? $lang : $language_code;
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
        $this->initCommonSettingsForUrl();
        $is_logged = isLogged();
        $lobby_url = $this->getLobbyUrl(false, $p_sLang, $p_sTarget);
        $cu = null;

        if($is_logged) {
            $cu = cu();
            $uid = $cu->getId();
            $this->_m_sToken = $this->getGuidv4($uid);
            $this->toSession($this->_m_sToken, $uid, $p_mGameId, $p_sTarget);
        }

        // query strings
        $params = [
            'token' => $is_logged ? $this->_m_sToken : null,
            'playMode' => $is_logged ? 'REAL' : 'FUN',
            'languageCode' => $this->getThreeLetterLanguageCode($p_sLang),
            'lobbyUrl' => (licJur() == 'SE') ? $this->wrapUrlInJsForRedirect($lobby_url) : $lobby_url,
        ];

        $base_url = str_replace(['%0'], [$p_mGameId], $this->getLicSetting('launchurl'));
        $full_url = $base_url . '?' . http_build_query($params);
        
        return $full_url;
    }

    // helper method to construct json data for a game-transaction
    private function preProcessGameTransaction($oData, $casinoMethod) {
        $isBet = false;
        $isWin = false;

        $aJson['roundAction'] = $oData->roundAction;

        // first check that the aggregator isn't sending us a game-transaction to simply close the round
        if($aJson['roundAction'] === 'CLOSE' && !isset($oData->bet) && !isset($oData->win)) {
            $aAction[0]['command'] = '_end';
            $aJson['state'] = 'single';
            $aJson['action'] = $aAction[0];
            return $aJson;
        }

        // we check if it's a bet or win or both and depending on the roundAction we construct the following json objects
        if(isset($oData->bet)) {
            $aJson['state'] = 'single';
            $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
            $aAction[0]['parameters'] = [
                'transactionid' => $oData->bet->transactionId,
                'amount' => $this->convertFromToCoinage($oData->bet->amount, self::COINAGE_UNITS, self::COINAGE_UNITS),
                'timestamp' => $oData->bet->timestamp,
                'roundid' => $oData->roundId
            ];
            $isBet = true;
        }

        if(isset($oData->win) && !isset($oData->freeRoundExternalId)) {
            $aJson['state'] = 'single';
            $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
            $aAction[0]['parameters'] = [
                'transactionid' => $oData->win->transactionId,
                'amount' => $this->convertFromToCoinage($oData->win->amount, self::COINAGE_UNITS, self::COINAGE_UNITS),
                'timestamp' => $oData->win->timestamp,
                'roundid' => $oData->roundId
            ];

            $isWin = true;

        }

        if($isBet && $isWin) {
            $aJson['state'] = 'multi';
            $aAction[0]['command'] = (($casinoMethod === 'betwin') ? '_bet' : '_cancel' );
            $aAction[0]['parameters'] = [
                'transactionid' => $oData->bet->transactionId,
                'amount' => $this->convertFromToCoinage($oData->bet->amount, self::COINAGE_UNITS, self::COINAGE_UNITS),
                'timestamp' => $oData->bet->timestamp,
                'roundid' => $oData->roundId
            ];
            $aAction[1]['command'] = (($casinoMethod === 'betwin') ? '_win' : '_cancel' );
            $aAction[1]['parameters'] = [
                'transactionid' => $oData->win->transactionId,
                'amount' => $this->convertFromToCoinage($oData->win->amount, self::COINAGE_UNITS, self::COINAGE_UNITS),
                'timestamp' => $oData->win->timestamp,
                'roundid' => $oData->roundId
            ];
        }

        $sizeOfAction = sizeof($aAction);

        switch($aJson['roundAction']) {
            case 'CANCEL':
            case 'CLOSE':
                $aJson['state'] = 'multi';
                $aAction[$sizeOfAction + 1]['command'] = '_end';
                break;
            default:
                break;
        }

        if($aJson['state'] == 'multi') {
            $aJson['actions'] = $aAction;
        } else {
            $aJson['action'] = $aAction[0];
        }

        return $aJson;
    } // end of preProcessGameTransaction

    /**
     * @param $oData
     * @return object
     * this will directly return the request for the freespin win as a json object to be executed but Gp.php methods
     * N.B. reason there are ternary conditions on some variables as there were discussions on what kind of request body we'll be receiving
     * original:
     * {
        "token" : "walletPlayerId",
        "freeRoundId": "cc9cfe9e-12e5-4e01-999f-b99d726310d6",
        "freeRoundExternalId" : "abcd1234",
        "wins" : 750000
        }
     *
     * videoslots:
     * {
        "playerId" : "walletPlayerId",
        "gameCode" : "GAM_ANM",
        "freeRoundId": "cc9cfe9e-12e5-4e01-999f-b99d726310d6",
        "freeRoundExternalId" : "abcd1234",
        "wins" : 750000
        }
     */
    private function preProcessFreespinWin($oData)
    {

        $freeround_ext_id = $oData->freeRoundExternalId;
        $player_id = isset($oData->token) ? $oData->token : $oData->playerId; // according to oryx, their token in this request is the 'walletplayerid'
        $transaction_id = isset($oData->transactionId) ? $oData->transactionId : phive()->uuid();
        $amount = $oData->wins;

        // if they don't send us the gamecode in the freeround finish so we'll get it from the bonus_id they send
        $game_code = isset($oData->gameCode) ? $this->stripPrefix($oData->gameCode) : $this->stripPrefix($this->_getBonusEntryBy($player_id, $freeround_ext_id)['game_id']);
        $json_request = [
            'playerid' => $player_id,
            'skinid' => $game_code,
            'state' => 'single',
            'action' => [
                'command' => 'finalFreespinWin',
                'parameters' => [
                    'amount' => $amount,
                    'roundid' => $oData->freeRoundId,
                    'transactionid' => $transaction_id
                ],
            ],
            'freespin' => [
                'id' => $freeround_ext_id
            ],
        ];

        return json_decode(json_encode($json_request));
    }

    /**
     * Here we are using the logic to assume that there is the last freespin win where we insert an win in the wins, empty the bonus entry
     * and finally, credit the player with the winnings
     *
     * @param stdClass $processed_request
     * @return bool|mixed
     */
    private function finalFreespinWin(stdClass $processed_request)
    {
        if($this->_getFreespinData('frb_remaining') < 1) {
            return $this->_getError(self::ER17);
        }

        $iAmount = $processed_request->amount;
        $iRoundId = $processed_request->roundid;

        // check if we have already a win with the same transaction ID
        $result = $this->_getTransactionById($processed_request->transactionid, self::TRANSACTION_TABLE_WINS);

        if (!empty($result)) {
            if ($iAmount == $result['amount']) {
                return $this->_getError(self::ER18);
            } else {
                return $this->_getError(self::ER05);
            }
        }

        $this->frb_win = true;

        if (!empty($iAmount)) {
            $this->attachPrefix($processed_request->transactionid);

            // we insertWin first so if the playChgBalance() below doesn't happen we have the win and won't double credit repeatedly
            $iRoundId_win = (ctype_digit($iRoundId) ? $iRoundId : 0);
            $this->attachPrefix($iRoundId);

            $balance = $this->_getBalance();
            // we only insert win if not a freespin
            // or when a freespin but the remaining_frb is 0 or more
            $wallet_txn_win = $this->insertWin($this->_getUserData(), $this->_getGameData(),
                $balance, $iRoundId_win, $iAmount, $this->_getBonusBetCode(),
                $processed_request->transactionid, $this->_getAwardTypeCode($processed_request),null);

            $this->setMIWalletTxnWins($wallet_txn_win);

            if ($this->getMIWalletTxnWins() === false) {
                return $this->_getError(self::ER01);
            }

            // if a freespin we don't want to change user cash balance with the winning only the bonus_entries::balance.
            $balance = $this->_getBalance();

            $this->handlePriorFail($this->_getUserData(), $processed_request->transactionid, $balance,
                $iAmount);
        }

        // the last FRB should be followed up with a last FRW by the GP even if the win amount is 0 so the status can be updated
        if ($this->_m_bUpdateBonusEntriesStatusByWinRequest === true) {
            $this->_handleFspinWin($iAmount);
        }

        return true;
    }

} // end of class

