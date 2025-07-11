<?php

require_once __DIR__ . '/Gp.php';
require_once __DIR__ . '/../Test/GpTestingUtils/GpTestingUtils.php';

class Playtech extends Gp
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
     * Confirmed by Nektan that it's unique forever 2017-09-19 12:27 by rameshbabu.kondapalli2 (skype)
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
     * Reality Check Error.
     * @var string
     */
    const ER200 = 'ER200';

    /**
     * The logger channel name for Playtech
     *
     * @var string
     */
    protected string $logger_name = 'playtech';

    /**
     * Mapping GP methods name
     *
     * @var string[]
     */
    private $_m_aMapGpMethods = [
        'authenticate' => '_init',
        'bet' => '_bet',
        'gameroundresult' => '_gameroundresult',
        'logout' => '_end',
        'getbalance' => '_balance',
        'getbalancebycontext' => '_balance',
        'creditDebit' => '_winbet',
        'submitdialog' => '_realityCheck',
        'notifybonusevent' => '_notifyBonusEvent',
        'realitychecksync' => '_realityCheckSync',
        'transferFunds' => '',
        'getGameDetails' => '',
        'getHistory' => '',
        'keepalive' => '',
    ];

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
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'INTERNAL_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '8', // change this to whatever the GP likes to receive as code
            'message' => 'Internal service error.' // change this to whatever the GP likes to receive as message
        ],
        'ER02' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'INVALID_REQUEST_PAYLOAD', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '7', // change this to whatever the GP likes to receive as code
            'message' => 'This code indicates that the service failed to deserialize the request payload.' // change this to whatever the GP likes to receive as message
        ],
        'ER03' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'ERR_AUTHENTICATION_FAILED', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '2', // change this to whatever the GP likes to receive as code
            'message' => 'Authentication failed. External token does not exist.' // change this to whatever the GP likes to receive as message
        ],
        'ER05' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'ERR_REGULATORY_GENERAL', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '5', // change this to whatever the GP likes to receive as code
            //'message' => 'General error code for the regulation-related declination.' // change this to whatever the GP likes to receive as message
        ],
        'ER06' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'ERR_INSUFFICIENT_FUNDS', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '4', // change this to whatever the GP likes to receive as code
            'message' => 'Insufficient funds.' // change this to whatever the GP likes to receive as message
        ],
        'ER09' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'ERR_PLAYER_NOT_FOUND', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '1', // change this to whatever the GP likes to receive as code
            'message' => 'Player not found.' // change this to whatever the GP likes to receive as message
        ],
        'ER10' => [
            'responsecode' => 200,
            'status' => 'ERR_REGULATORY_GENERAL',
            'return' => 'default',
            'code' => 'ER10',
            'message' => 'Game is not found.'
        ],
        'ER13' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'ERR_BONUS_TEMPLATE_NOT_FOUND', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '10', // change this to whatever the GP likes to receive as code
            'message' => 'Bonus template not found.' // change this to whatever the GP likes to receive as message
        ],
        'ER16' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'CONSTRAINT_VIOLATION', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '9', // change this to whatever the GP likes to receive as code
            'message' => 'This error code indicates that the service triggered a failure during the constraints validation on request fields.' // change this to whatever the GP likes to receive as message
        ],
        'ER17' => [
            'responsecode' => 200,
            'status' => 'ERR_TRANSACTION_DECLINED',
            'return' => 'default',
            'code' => 'ER17',
            'message' => 'This free spin bonus ID is not found.'
        ],
        'ER19' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'ERR_TRANSACTION_DECLINED', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '3', // change this to whatever the GP likes to receive as code
            'message' => 'General error code for the declined transaction.' // change this to whatever the GP likes to receive as message
        ],
        'ER22' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'ERR_BONUS_INSTANCE_NOT_FOUND', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '11', // change this to whatever the GP likes to receive as code
            'message' => 'Unknown bonus instance passed.' // change this to whatever the GP likes to receive as message
        ],
        'ER26' => [
            'responsecode' => 200,
            'status' => 'ERR_AUTHENTICATION_FAILED',
            'return' => 'default',
            'code' => '26',
            'message' => 'Player is banned.'
        ],
        'ER200' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'ERR_REGULATORY_REALITYCHECK', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '6', // change this to whatever the GP likes to receive as code
            'message' => 'Player has hit the time limit configured for reality check. Accompanied with reality check dialog data.' // change this to whatever the GP likes to receive as message
        ],
        'ER27' => [
            'responsecode' => 200, // used to send header to GP if not enforced 200 OK
            'status' => 'ERR_TRANSACTION_DECLINED', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '5', // change this to whatever the GP likes to receive as code
            'message' => 'General error code for decline transaction (should not be retried)' // change this to whatever the GP likes to receive as message
        ],
    ];


    private $_m_requestId = '';

    public function doConfirmByRoundId()
    {
        return $this->getSetting('confirm_win', false);
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

        /**
         * When we're using automated testing tools, we need to mock the method _getUrl, because the
         * method is not called during the process, and it is vital to start the game sessions (putting the
         * data on Redis)
         */
         if ($this->getSetting('api_testing_enabled') === true) {
             GpTestingUtils::create($this)->whenGpMethod('authenticate')->mockWalletMethod('_getUrl')->execute();
         }

        $mSessionData = null;

        if (isset($oData->externalToken)) {
            $mSessionData = $this->fromSession($oData->externalToken);
        }

        if ($this->_isGpMethod('notifybonusevent')) {
            $this->_response($this->_notifyBonusEvent());
        }

        $aJson['playerid'] = $this->_getPlayerId();
        $aJson['skinid'] = $this->_getGameId($aJson['playerid']);

        if (isset($mSessionData->device)) {
            $aJson['device'] = $mSessionData->device;
        }

        $target = $mSessionData->device;

        // !empty($this->getCurrentGameId($oData)) ? $this->getCurrentGameId($oData) : $this->stripPrefix($mSessionData->gameid);
        $this->toSession($oData->externalToken, $aJson['playerid'], $aJson['skinid'], $target);

        // at this point we have the user id for sure
        if (isset($oData->externalToken)) {
            phMsetShard("EXTERNALTOKEN", $oData->externalToken, $aJson['playerid']);
        }

        switch ($casinoMethod) {
            case 'gameroundresult':

                // we must always send the win in order to retrieve freespins money accumulated
                // in the bonus entries table when the last free spin is done
                $aAction[0]['command'] = $oData->pay->type == "REFUND" ? '_cancel' : '_win';

                if($aAction[0]['command'] == '_cancel'){
                    if (isset($oData->pay->relatedTransactionCode) && !empty($oData->pay->relatedTransactionCode)) {
                        // new GPAS setup
                        $aAction[0]['parameters'] = [
                            'amount' => $this->convertFromToCoinage($oData->pay->amount, self::COINAGE_UNITS, self::COINAGE_CENTS),
                            'transactionid' =>  $oData->pay->relatedTransactionCode,
                            'roundid' => $this->getCurrentRoundId($oData->gameRoundCode)
                        ];
                    } else {
                        // old setup
                        //TODO:after Playtech change everything to GPAS logic remove the below else condition
                        // need to get the mg_id of the bet from the round_id in order to cancel the actual mg_id bet
                        $round_id = $this->getCurrentRoundId($oData->gameRoundCode);
                        $aAction[0]['parameters'] = [
                            'amount' => $this->convertFromToCoinage($oData->pay->amount, self::COINAGE_UNITS, self::COINAGE_CENTS),
                            'transactionid' =>  $this->stripPrefix($this->getBetFromRoundId($aJson['playerid'], $round_id)),
                            'roundid' => $round_id
                        ];
                    }

                } else {
                    $ext_round_id = isset($oData->parentGameRoundCode) && !empty($oData->parentGameRoundCode) ? $oData->parentGameRoundCode : $oData->gameRoundCode;
                    $aAction[0]['parameters'] = [
                        'amount' => $this->convertFromToCoinage($oData->pay->amount, self::COINAGE_UNITS, self::COINAGE_CENTS),
                        'transactionid' =>  $oData->pay->transactionCode,
                        //TODO: to clean after Playtech change all games to GPAS and remove parentGameRoundCode
                        'roundid' => $this->getCurrentRoundId($ext_round_id)
                    ];
                }
                $aJson['state'] = 'single';
                $aJson['action']= $aAction[0];

                // detect for freespins in win
                if ('_win' == $aAction[0]['command']) {
                    // todo: This could be a single if statement
                    $round_id = $this->getCurrentRoundId($oData->gameRoundCode);
                    $bet_transaction_id = $this->stripPrefix($this->getBetFromRoundId($aJson['playerid'], $round_id));
                    if ($this->getBetBonusType($bet_transaction_id) == 3) {
                        $aJson['freespin'] = $this->getFreeSpinBonusData($aJson['playerid'], $aJson['skinid']);
                    }
                }
                break;
            case 'bet':
                $aJson['state'] = 'single';
                $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);

                $aAction[0]['parameters'] = [
                    'amount' => $this->convertFromToCoinage($oData->amount, self::COINAGE_UNITS, self::COINAGE_CENTS),
                    'transactionid' => $oData->transactionCode,
                    'roundid' => $this->getCurrentRoundId($oData->gameRoundCode)
                ];
                $aJson['action'] = $aAction[0];

                // detect for freespin in bet
                if (!empty($oData->remoteBonusCode) && $oData->amount == 0 ) {
                    $aJson['freespin'] = array('id' => $oData->remoteBonusCode);
                }

                if (empty($oData->transactionCode)) {
                    $this->_response($this->_getError('ER02'));
                }
                break;
            case 'submitdialog':
                $aJson['state'] = 'single';
                $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                $aAction[0]['parameters'] = [
                    'realityCheckChoice' => $oData -> realityCheckChoice
                ];
                $aJson['action'] = $aAction[0];
                break;
            case 'realitychecksync':
                $aJson['state'] = 'single';
                $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                $aAction[0]['parameters'] = [];
                $aJson['action'] = $aAction[0];
                break;
            default:
                $aAction['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                $aJson['state'] = 'single';
                $aJson['action'] = $aAction;
                break;
        }

        $this->_m_oRequest = json_decode(json_encode($aJson), false);
        $this->logger->debug(__METHOD__, [
            'method' => $casinoMethod,
            'request' => $oData
        ]);

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
        if (!empty($this->_m_oRequest->skinid)) {
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

            $sMethod = $oAction->command;

            // Update the user data before each command
            $this->setUserData($sMethod);

            $this->_setWalletMethod($sMethod);

            // REALITY CHECK
            // if time is elapsed then throw reality check error
            // we check that the user has reality check enabled
            if ("_bet" == $sMethod && !empty($this->getRealityCheckParameters(cu($this->_getUserData()['id'])))) {
                if (empty(phMgetShard(self::PREFIX_MOB_RC_TIMEOUT, $this->_getUserData()['id']))) {
                    $this->_response($this->_getError(self::ER200));
                }
            }

            // command call return either an array with errors or true on success
            if (property_exists($oAction, 'parameters')) {
                if ($sMethod == "_bet"){
                    $mResponse = $this->$sMethod($oAction->parameters, false);
                } else {
                    $mResponse = $this->$sMethod($oAction->parameters);
                }
            } else {
                $mResponse = $this->$sMethod();
            }

            // some error occurred
            if ($mResponse !== true) {

                /*
                 * When a user reaches responsible gaming limits a not enough funds error is return
                 * so we filter out if it is actually a not enough funds or responsible gaming error
                 * if responsible gaming error then we display the appropriate error
                 */
                if ($sMethod == "_bet" && $mResponse['code'] == 4){
                    $balance = $this->_getBalance([], [], true);
                    $betAmount = $oAction->parameters->amount;
                    //check if responsible gaming error
                    if ($balance > $betAmount){
                        $this->_response($this->_getError('ER27'));
                    }
                }

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
     * Set the current user data and external session
     * @param $sMethod
     */
    public function setUserData($sMethod)
    {
        if (isset($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }
    }

    protected function _response($p_mResponse)
    {
        $aResponse = $aResp = [];
        $aUserData = $this->_getUserData();

        if ($p_mResponse === true) {
            $aResponse['requestId'] = $this->_m_requestId;

            $balance = [
                "real" =>  $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS),
                "timestamp" =>  $this->_getDateTimeInstance()->format("Y-m-d H:i:s.v")
            ];

            switch ($this->getGpMethod()) {
                case 'authenticate':
                    $aResponse['username'] = ($this->getSetting('test')) ? $this->getLicSetting('prefix', $aUserData['id']).$aUserData['id'] : $aUserData['id'];
                    $aResponse['currencyCode'] = strtoupper($this->getPlayCurrency($aUserData));
                    $aResponse['countryCode'] = strtoupper($this->getCountry($aUserData));

                    $subBrand = $this->getLicSetting('sub_brand', cu($this->_getUserData()));
                    if (!empty($subBrand)) {
                        $aResponse['subBrand'] = $subBrand;
                    }

                    //max bet limit param
                    $maxBetLimit = phive('Gpr')->getMaxBetLimit(cu($aUserData));
                    if (!empty($maxBetLimit)) {
                        $aResponse['playerData'] = [
                            'countryCode'  => strtoupper($this->getPlayCurrency($aUserData)),
                            'currencyCode' => strtoupper($this->getCountry($aUserData)),
                            'regulation' => 'UK',
                            'betLimits' => [
                                [
                                    'gameCategory' => 'SLOTS',
                                    'limit' => $maxBetLimit
                                ]
                            ]
                        ];
                    }
                    break;

                case 'gameroundresult':
                    $aResponse['externalTransactionCode'] = $this->_getTransaction('txn', self::TRANSACTION_TABLE_WINS);
                    $aResponse['externalTransactionDate'] = $this->_getDateTimeInstance($this->_getTransaction('date', self::TRANSACTION_TABLE_WINS))->format("Y-m-d H:i:s.v");
                    $aResponse['balance'] = $balance;
                    break;

                case 'bet':
                    $aResponse['externalTransactionCode'] = $this->_getTransaction('txn', self::TRANSACTION_TABLE_BETS);
                    $aResponse['externalTransactionDate'] = $this->_getDateTimeInstance($this->_getTransaction('date', self::TRANSACTION_TABLE_BETS))->format("Y-m-d H:i:s.v");
                    $aResponse['balance'] = $balance;

                    if ($this->_isFreespin()) {
                        $aResponse['freespin_num'] = $this->_getFreespinData('frb_remaining');
                    }
                    break;
                case 'getbalancebycontext':
                case 'getbalance':
                    $aResponse['balance'] = $balance;
                    break;

                case 'submitdialog':

                    break;
                case 'realitychecksync':
                    // Sending timeInterval=0 and timeElapsed=0 to disable the reality check on GP side.
                    // Our system is responsible for handling the reality check.
                    $aResponse['timeInterval'] = 0;
                    $aResponse['timeElapsed'] = 0;
                    break;
            }

            $aResp = $aResponse;
        } else {

            // Playtech group the error with a qualifier, same error can be in multiple groups
            $map = [    'authenticate' => 'AuthenticateError',
                        'gameroundresult' => 'GameRoundResultError',
                        'bet' => 'BetError',
                        'getbalance' => 'GetBalanceError'
                    ];

            $qualifier = $map[$this->getGpMethod()] ?? 'GeneralError';
            $lang = phMgetShard(self::PREFIX_MOB_RC_LANG, $aUserData['id']);

            if($p_mResponse['code'] == 6 ){
                $dialogId = ($this->getSetting('test')) ? $this->getLicSetting('prefix', $aUserData['id']).$aUserData['id'].time() : $aUserData['id'].time();
                $aResp['playerDialog'] = [
                    "dialogId" => $dialogId,
                    "type" => "REALITYCHECK",
                    "playerMessage" => lic('getRealityCheckWithHistoryLink', [$aUserData['id'], $lang, $this->getGamePrefix() . $this->_m_oRequest->skinid], $aUserData['id']),
                ];
            } elseif ($p_mResponse['code'] == 5 ) {
                $aResp['playerMessage'] = t('week.limit.reached', $lang); //'limits reached' displayed
            }

            $aResp['requestId'] = $this->_m_requestId;
            $aResp['error'] = [
                        'qualifier' => $qualifier,
                        'description' => $p_mResponse['message'],
                        'code' => $p_mResponse['status'],
                    ];
        }

        $this->_setResponseHeaders($p_mResponse);
        $result = json_encode($aResp);
        $this->logger->debug(__METHOD__, [
            'method' => $this->getGpMethod(),
            'request' => $this->getGpParams(),
            'response' => $aResp,
        ]);
        echo $result;

        // if the session limit is reached tthen logout the user, and is done here after sending a successful message
        if ($p_mResponse['code'] == 5) {
            phive()->pexec('Playtech', 'deleteUserSessionData', [$aUserData['id']], null, $aUserData['id']);
        }

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
        $this->initCommonSettingsForUrl();

        // need to get the tag of the game to check if it is live casino or normal slot game
        $desktopOrMobile = $p_sTarget == 'desktop' ? 0 : 1;
        $cur_game = phive('MicroGames')->getByGameId($this->getGamePrefix().$p_mGameId, $desktopOrMobile);

        $launch_params = [
            'gameCodeName' => $p_mGameId,
            'casino' => $this->getLicSetting('casino'),
            'clientType' => ($this->getLicSetting('client_type'))[$cur_game['tag']],
            'clientPlatform' => $p_sTarget == 'desktop' ? 'web' : 'mobile',
            'externalToken' => $this->getGuidv4(),
            'language' => $p_sLang,
            'realMode' => 0,
            'lobbyUrl' => $this->getSetting('lobby_url', phive()->getSiteUrl()),
            'integration' => 'ucip'
            //'depositUrl' => $p_mGameId,
        ];

        if ($cur_game['tag'] == 'live-casino') {
            $launch_params['gameCodeName'] = empty($cur_game['module_id']) ? explode('_', $p_mGameId)[0] : $cur_game['module_id'];
            $launch_params['tableAlias'] = $p_mGameId;
        }

        if (isLogged()) {
            $userId = cu()->getId();
            $launch_params['externalToken'] = $this->getGuidv4($userId);
            $launch_params['username'] =  ($this->getSetting('test')) ? (string) $this->getLicSetting('prefix', $userId).$userId : (string)$userId;
            $launch_params['realMode'] = 1;

            $this->toSession($launch_params['externalToken'], $userId, $p_mGameId, $p_sTarget);

            // Reality Check Initialisation
            $reality_check_interval = phive('Casino')->startAndGetRealityInterval($userId, $cur_game['ext_game_name']);
            if (!empty($reality_check_interval)) {
                phMsetShard(self::PREFIX_MOB_RC_LANG, $p_sLang, $userId);
                phMsetShard(self::PREFIX_MOB_RC_TIMEOUT, '1', $userId, $reality_check_interval * 60); // reality check interval * 60
                phMsetShard(self::PREFIX_MOB_RC_PLAYTIME, time(), $userId);
            }
        }

        $launch_url = $this->getLaunchUrl($launch_params);
        $this->dumpTst('playtech-launch-game', ['url' => $launch_url]);
        return $launch_url;
    }


    /**
     * Inform the GP about the amount of freespins available for a player.
     * The player can open the game anytime. The GP already 'knows' about the freespins available to the player.
     * Often if the GP keeps track itself and send the wins at the end when all FRB are finished like with Netent.
     *
     * @param int $userId The user ID
     * @param string $p_sGameIds The internal gameId's pipe separated
     * @param int $p_iFrbGranted The frb given to play
     * @param string $bonus_name
     * @param array $p_aBonusEntry The entry from bonus_types table
     * @return bool|string|int If not false than bonusId is returned otherwise false (freespins are not activated)
     */
    public function awardFRBonus($userId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry)
    {
        if ($this->getSetting('no_out') === true) {
            $ext_id = (!empty($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->getGuidv4($userId));
            $this->attachPrefix($ext_id);
            return $ext_id;
        }
        $this->_logIt([ __METHOD__,'parameters (($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry)": ' .$userId.' - '.$p_sGameIds.' - '.$p_iFrbGranted.' - '.$bonus_name.' - '.print_r($p_aBonusEntry, true)]);
        $user = cu($userId);
        $playerData = [
            'currencyCode'  =>   $user->data['currency'],
            'countryCode'   =>   $user->data['country'],
        ];
        $bonusDate = $p_aBonusEntry['start_time'] .' '. substr(date('H:i:s.u'),0,-3);

        $a = [
            'requestId' => $this->randomNumber(6),
            'username' => ($this->getSetting('test')) ? (string)$this->getLicSetting('prefix', $userId).$userId : (string)$userId,
            'templateCode' => $this->getLicSetting('template_code', $userId),
            'count' => $p_iFrbGranted,
            'transactionCode' => $p_aBonusEntry['bonus_id'],
            'transactionDate' => $bonusDate,
            'playerData' => $playerData,
            'remoteBonusCode' => $p_aBonusEntry['id'],
            'remoteBonusDate' => $bonusDate,
            'gameCodeNames' => [implode(',', explode('|', $this->stripPrefix($p_sGameIds)))]
        ];

        $url = $this->getLicSetting('service_api', $userId) . 'givefreespins';
        $res = $this->doRequest($userId, $a, $url);
        $res = json_decode($res, true); // decode as array
        phive()->dumpTbl('playtech-bonus-parsed-response',$res);
        return $res['bonusInstanceCode'] ?? false;
    }


    /**
     * Remove freespins for a player on Playtech side.
     *
     * @param int $userId The user ID
     * @param string $bonusCode The bonus code on Playtech side of the bonus to be removed
     * @return string Return successful or Error
     */
    public function removeBonus($userId, $bonusCode)
    {
        if ($this->getSetting('no_out') === true) {
            $ext_id = (!empty($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->getGuidv4($userId));
            $this->attachPrefix($ext_id);
            return $ext_id;
        }

        $a = [
            'requestId' => $this->getGuidv4($userId),
            'username' => ($this->getSetting('test')) ? (string)$this->getLicSetting('prefix', $userId).$userId : (string)$userId,
            'bonusInstanceCode' => $bonusCode
        ];

        $url = $this->getLicSetting('service_api', $userId) . 'removebonus';
        $res = $this->doRequest($userId, $a, $url);
        print_r($res);

        return $res;
    }


    /**
     * Return freespins available for a player on Playtech side.
     *
     * @param int $userId The user ID
     * @return string|array Return all the bonuses for the player on Playtech side.
     */
    public function getPlayerActiveBonuses($userId)
    {
        $this->_logIt([ __METHOD__,'parameters (($p_iUserId)": ' .$userId]);
        $a = [
            'requestId' => $this->getGuidv4($userId),
            'username' => ($this->getSetting('test')) ? (string)$this->getLicSetting('prefix', $userId).$userId : (string)$userId,
        ];
        $url = $this->getLicSetting('service_api', $userId) . 'getplayeractivebonuses';
        $res = $this->doRequest($userId, $a, $url);
        print_r($res);
        return $res;
    }


    /**
     * Post request
     * @param int $userId The user id
     * @param array $a the parameters sent
     * @param string $url
     * @param int $timeout
     * @return string $res
     */
    public function doRequest($userId, $a, $url, $timeout = 60){
        $keyPath = $this->getLicSetting('key_certificate_service_api', $userId);
        $pemPath = $this->getLicSetting('pem_certificate_service_api', $userId);
        $pass = $this->getLicSetting('pwd_certificate_service_api', $userId);

        $extra = [
            CURLOPT_SSLCERT => $pemPath,
            CURLOPT_SSLKEY => $keyPath,
            CURLOPT_SSLKEYPASSWD => $pass
        ];

        try {
            return phive()->post($url, $a, 'application/json', '', 'playtech-curl', 'POST', $timeout, $extra);
        } catch(Exception $e) {
            phive()->dumpTbl("Playtech-POST-Error", [$e->getMessage(), $e->getTraceAsString()]);
            return false;
        }
    }


    /**
     * Gets bonus type of the bet.
     *
     * @param string $transactionId The transaction id
     * @return int bonus type
     */
    protected function getBetBonusType($transactionId){
        return (int) $this->getBetByMgId($this->getGamePrefix() . $transactionId)['bonus_bet'];
    }

    /**
     * Get FreeSpin bonus data.
     *
     * Playtech does not send the FreeSpin bonus id.
     * Instead, the last bonus inserted that has frb_remaining will be used.
     *
     * The original SQL is commented out because it always fails. db.bonus_entries.ext_id is never equal to
     * 'playtech_<game_ref>', e.g. 'playtech_bfb', because when the bonus is activated by the player
     * db.bonus_entries.ext_id stores the Playtech internal ID for that bonus, e.g. 4938998.
     * Playtech sends both their internal bonus ID and our own db.bonus_entries.id in the Bet request but
     * we are not storing them so instead we get the first active bonus entry.
     *
     * @param int $userId The user ID
     * @param string $gameId The game id
     * @return array bonus data
     */
    protected function getFreeSpinBonusData($userId, $gameId)
    {
        $game_id = phive("SQL")->escape($this->getGamePrefix() . $gameId);
        $date = phive("SQL")->escape(date('Y-m-d'));

        $sql = "SELECT be.id, be.bonus_id, be.user_id, be.balance, be.start_time, be.end_time, be.status, be.reward,
                    be.cost, be.frb_remaining, be.frb_granted, bt.frb_denomination, bt.frb_lines, bt.rake_percent,
                    bt.frb_coins, bt.game_id
                FROM bonus_entries AS be
                INNER JOIN bonus_types AS bt ON be.bonus_id = bt.id
                WHERE bt.bonus_type = 'freespin' AND bt.game_id = {$game_id}
                AND be.user_id = {$userId} AND IF(bt.rake_percent > 0, be.status = 'active', be.status = 'approved')
                AND (be.start_time IS NULL OR (be.start_time IS NOT NULL AND be.start_time <= {$date}))
                AND (be.end_time IS NULL OR (be.end_time IS NOT NULL AND be.end_time >= {$date}))
                AND be.frb_remaining >= 0
                ORDER BY be.id DESC";
        $bonus_entry = phive('SQL')->sh($userId)->loadAssoc($sql, null, null, true);
        return $bonus_entry;
    }


    protected function _realityCheck()
    {
        $aUserData = $this->_getUserData();

        switch ($this->_m_oRequest->action->parameters->realityCheckChoice) {
            case 'CONTINUE':
                $rcInterval = $this->getRealityCheckParameters(cu($aUserData['id']))['rcInterval'];
                if (!empty($rcInterval)) {
                    phMsetShard(self::PREFIX_MOB_RC_TIMEOUT, '1', $aUserData['id'], $rcInterval * 60);
                }
                break;
            case 'STOP':
                $this->deleteUserSessionData($aUserData['id']);
                break;
        }
        return true;
    }


    /**
     * Display error message to the user that the player has been logged out on Playtech's Side.
     *
     * @param int $p_iUserId The user ID
     * @return string Display error message to the user from Playtechs side.
     */
    public function endLoginSession($userId)
    {
        $this->_logIt([ __METHOD__,'parameters (($p_iUserId)": ' .$userId]);
        //$user = cu($userId);

        $a = [
            'requestId' => $this->getGuidv4($userId),
            'username' => ($this->getSetting('test')) ? (string)$this->getLicSetting('prefix', $userId).$userId : (string)$userId,
            'externalToken' => phMgetShard("EXTERNALTOKEN", $userId),
        ];

        //Important to check if the externalToken is not empty, cause Playtech will close ALL players sessions
        if(empty($a)){
            return false;
        }

        $url = $this->getLicSetting('service_api', $userId) . 'endloginsession';
        $res = $this->doRequest($userId, $a, $url, 20);
        $this->_logIt([ __METHOD__, print_r($res,true)]);

        return $res;
    }

    /**
     * Delete user session data .
     *
     * @param int $userId The user ID
     * @return boolean true.
     */
    protected function deleteUserSessionData($userId){
        $this->endLoginSession($userId);
        phMdelShard(self::PREFIX_MOB_RC_LANG, $userId);
        phMdelShard(self::PREFIX_MOB_RC_PLAYTIME, $userId);
        phMdelShard(self::PREFIX_MOB_RC_TIMEOUT, $userId);
        phMdelShard("EXTERNALTOKEN", $userId);
        phive('UserHandler')->logoutUser($userId);

        return true;
    }


    /**
     * @param int $userId The user Id
     * @param string $roundId the round id of the bet
     * @return string the bet mg_id
     */
    protected function getBetFromRoundId($userId, $roundId){

        $this->attachPrefix($roundId);
        $sql_str = "SELECT bets.mg_id
                    FROM rounds
                    INNER JOIN bets
                    ON rounds.bet_id = bets.id
                    WHERE ext_round_id = '$roundId'";
        $bet = (phive('SQL')->sh($userId)->loadArray($sql_str));

        $bet_mgid = $bet[0]['mg_id'];

        //remove the ref from the refunded transactions if any
        if (!empty($bet_mgid) && substr($bet_mgid,-3) === 'ref') {
            $bet_mgid = substr($bet_mgid,0,-3);
        }

        return $bet_mgid;
    }

    /*
     * Retrieves all the jackpots for Playtech in deifferent currencies
     */
    public function parseJackpots()
    {
        $cur = phive('Currencer');
        $insert = [];
        $urls = $this->getAllJurSettingsByKey('jp_url');
        $jp_mapping = $this->getJackPotMapping();

        // Playtech can handle send us all the currency by changing
        // the currency at the end of the URL
        // There is only one default Jurisdiction file
        foreach ($urls as $jur => $url) {
            foreach ($cur->getAllCurrencies() as $ciso => $c) {

                $url_lang = $url . $ciso;
                $dom = new DOMDocument;
                $dom->load($url_lang);
                $properties = $dom->getElementsByTagName('gamedata');

                foreach ($properties as $property) {
                    $amount = $property->nodeValue;
                    $game_name = $property->getAttribute('gamegroup');
                    $jp_id = $property->getAttribute('game');
                    $local = $property->getAttribute('local');
                    $currency = $ciso;

                    if (isset($jp_mapping[$game_name])) {
                        $games_to_insert = $jp_mapping[$game_name];
                    } else {
                        $game_id = 'playtech_' . $game_name;
                        $games_to_insert = [$game_id];
                    }


                    foreach ($games_to_insert as $local_game) {
                        $insert[] = [
                            "jp_value"     => $amount * 100,
                            "jp_id"        => $jp_id,
                            "jp_name"      => $jp_id,
                            "network"      => 'playtech',
                            "module_id"    => $local_game,
                            "currency"     => $currency,
                            "local"        => $local,
                            "game_id"      => $local_game,
                            "jurisdiction" => $jur,
                        ];
                    }
                }
            }
        }
        return $insert;
    }

    /**
     * Overrides the base method in Gp.php to check db.rounds for a matching bet.
     *
     * @param stdClass $p_oParameters
     * @param bool $p_bReturnResult
     * @return array|bool
     */
    protected function _hasBet(stdClass $p_oParameters, $p_bReturnResult = false)
    {
        if ($this->_m_bSkipBetCheck !== true) {
            if (!$this->doConfirmByRoundId()) {
                return parent::_hasBet($p_oParameters, $p_bReturnResult);
            }

            $round_id = $p_oParameters->roundid;
            $this->attachPrefix($round_id);
            $rounds = $this->getRoundsByExtRoundId($this->uid, $round_id);
            if (empty($rounds)) {
                return false;
            } elseif (!$p_bReturnResult) {
                return true;
            }
            $table = $this->isTournamentMode() ? 'bets_mp' : 'bets';
            $bets = $this->getTransactionsById($this->uid, $table, [$rounds[0]['bet_id']]);
            return empty($bets) ? false : $bets[0];
        }
    }

    /** Return array of games that apply for the same jackpot
     *  Handles the Special case were 2 different games apply for the same jackpot
     *  or when a Jackpot is related with a different game name than the one that comes
     *  from the parse.
     * We use micro_games table module_id table to store the jackpot mapping
     *
     * @return array string[]
     */
    private function getJackPotMapping()
    {
        $map = [];
        $current = '';
        $games = phive('SQL')->loadArray("SELECT game_id, module_id FROM micro_games WHERE network = 'playtech' AND module_id != '' AND tag != 'live-casino' ORDER BY module_id");

        foreach ($games as $g) {
            if ($g['module_id'] != $current) {
                $current = $g['module_id'];
                $map[$current] = [];
            }
            $map[$current][] = $g['game_id'];
        }

        return $map;
    }

    /**
     * Live casino games have additional information to be able to match the table
     *
     * @param $oData
     * @return mixed
     */
    private function getCurrentGameId($oData)
    {
        if (!empty($oData->liveTableDetails)) {
            $game_ref = 'playtech_' . $oData->liveTableDetails->launchAlias;
            if (!empty(phive('MicroGames')->getByGameRef($game_ref, null))) { // !important: device is null to cache the query
                return $oData->liveTableDetails->launchAlias;
            }
        }

        return $oData->gameCodeName;
    }

    private function getCurrentRoundId($ext_round_id) {
        return strpos($ext_round_id, 'live_') === false ? $ext_round_id : str_replace('live_', '', $ext_round_id);
    }


    /**
     * Overrides the base method in Gp.php to skip initializing external game session for live-casino games.
     *
     * @return bool
     */
    protected function _init()
    {
        if ($this->getLicSetting('hide_balance_popup_for_live_casino') && phive('MicroGames')->isLiveCasinoGame($this->_getGameData())) {
            return true;
        }

        return parent::_init();
    }

    /**
     * Ends the user game session or the ext game participations when receive the call from the game provider.
     *
     * @return bool
     */
    public function _end(): bool
    {
        $user = cu($this->_getUserData());

        if (!$this->getLicSetting('external_game_session_enabled', $user)) {
            phive('Casino')->finishGameSession($user->getId());
            return true;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function _realityCheckSync() {
        return true;
    }

    /**
     * This method allows you to tip the dealer during live casino games.
     *
     * It has been skipped because videoslots doesn't support this functionality yet.
     *
     * @param stdClass $p_oParameters
     * @return bool
     */
    public function _liveTip(stdClass $p_oParameters)
    {
        return true;
    }

    /**
     * This method is used to receive information about bonus instances.
     *
     * It might not be necessary for standard gameplay.
     *
     * @return bool
     */
    public function _notifyBonusEvent(): bool
    {
        return true;
    }


    /**
     * Override parent function.
     *
     * @param array $p_aUserData
     * @param array $p_aGameData
     * @param bool $totalBalance
     * @return int|mixed
     */
    public function _getBalance(array $p_aUserData = array(), array $p_aGameData = array(), bool $totalBalance = false)
    {
        $user = empty($user) ? $this->_getUserData() : $user;
        $game = empty($game) ? $this->_getGameData() : $game;

        if ($this->_isTournamentMode()) {
            return $this->tEntryBalance();
        }

        if ($this->hasSessionBalance() && !phive('MicroGames')->isLiveCasinoGame($game)) {
            return $this->getSessionBalance($user['id']);
        }

        if ($this->hasSessionBalance() && phive('MicroGames')->isLiveCasinoGame($game) && !$totalBalance) {
            return  $this->getSessionBalance($user['id']);
        }

        if (empty($user)) {
            return 0;
        }

        $real_balance = phive('UserHandler')->getFreshAttr($user['id'], 'cash_balance');
        if (empty($real_balance)) {
            phive('Bonuses')->resetEntries();
        }

        $bonus_balance = empty($game['ext_game_name']) ? 0 : phive('Bonuses')->getBalanceByRef($game['ext_game_name'],  $user['id']);
        return $real_balance + $bonus_balance;
    }

    /**
     * Returns the game id present in the request object.
     *
     * The object in which we receive the game id data changes accross different methods.
     *
     * @return mixed
     */
    private function _getGameId($userId): string
    {
        $request = $this->getGpParams();
        $session = $this->fromSession($request->externalToken ?? '');

        $gameId = $this->stripPrefix($request->gameCodeName ?? '');
        $sessionGameId = $this->stripPrefix($session->gameid);

        if ($this->_isGpMethod('getbalancebycontext')) {
            $gameId = $this->stripPrefix($request->gameContext->gameCodeName ?? '');
        }

        $gameAlias = $this->getCurrentGameId($request);
        if (!empty($gameAlias) && isset($request->liveTableDetails)) {
            $gameId = $gameAlias;
        }

        $game = phive('MicroGames')->getByGameRef("{$this->getGamePrefix()}{$gameId}", null, cu($userId));

        if (empty($game) && !empty($sessionGameId) && $gameId !== $sessionGameId) {
            $gameId = $sessionGameId;
        }

        return $gameId;
    }

    /**
     * Returns the player id present in the request object.
     *
     * This method returns an error response if the user id in the request is different from the user id
     * stored in the cache system.
     *
     * @return string
     */
    public function _getPlayerId(): string
    {
        $request = $this->getGpParams();
        $session = $this->fromSession($request->externalToken ?? '');

        $user_prefix = preg_replace('/\d+$/', '', $request->username);
        $user_id = str_replace($user_prefix, '', $request->username);

        $user = cu($user_id);
        // when gameRoundClose request comes, there is a chance that redis session is nor present and this was throwing error, which GP pointed out that it needs to be fixed
        // so modifying condition to handle this situation. GP stated we must NOT return an error in the case the user does not exist in the session AND they send a gameRoundClose request.
        if (is_null($user) || ($user_id != $session->userid && !empty($request->gameRoundClose)) || $user_prefix != $this->getLicSetting('prefix', $user_id)) {
            //could not find the user or user prefix was invalid, we should return the error in this case
            if(is_null($user) || $user_prefix != $this->getLicSetting('prefix', $user_id)){
                $this->_response($this->_getError(self::ER09));
            }

            return $user->getId();
        }

        if (!str_starts_with($request->externalToken, "u{$user_id}")) {
            $this->_response($this->_getError(self::ER03));
        }

        return $user->getId();
    }

    /**
     *
     * @param string $method
     * @return bool
     */
    public function _isGpMethod(string $method): bool {
        return $this->getGpMethod() === $method;
    }

    /**
     * Returns the required data to mock the method _getUrl (Only for automated tests porpuses).
     *
     * @return stdClass
     */
    public function getGpParamsApiTesting(): stdClass
    {
        $gp_params = $this->getGpParams();
        $params = new stdClass();
        $params->apiTesting = [
            'token' => $gp_params->externalToken,
            'game_id' => $this->getSetting('api_testing_game'),
            'user_id' => preg_replace('/(VIDEOS__|MRVEGAS__)/', '', $this->getSetting('api_testing_user')),
        ];

        return $params;
    }
}
