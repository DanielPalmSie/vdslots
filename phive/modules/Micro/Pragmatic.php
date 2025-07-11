<?php

require_once __DIR__ . '/Gp.php';

class Pragmatic extends Gp
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
    protected $_m_bFrwSendPerBet = false;

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
    protected $_m_bConfirmFrbBet = false;

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
     * Transaction id from cash_transactions tables entry, which is a result of Promowin Deposit to a user.
     * @var int
     */
    protected $_m_iPromoWinTransactionId = 0;
    protected $_m_iJackPotWinTransactionId = 0;
    protected $_m_iAdjustTransactionId = 0;


    private $_m_aMapGpMethods = array(
        'authenticate' => '_init',
        'balance' => '_balance',
        'getBalancePerGame' => '_getGameBalance',
        'bet' => '_bet',
        'result' => '_win',
        'bonusWin' => '_frbStatus',
        'refund' => '_cancel',
        'endRound' => '_roundDetails',
        'promoWin'  =>  '_promoWin',
        'jackpotWin'  =>  '_jackpotWin',
        'adjustment' => '_adjustment',
        'roundDetails' => '_roundDetails',
        'session/expire' => '_sessionExpire'
    );

    private $_m_aErrors = array(
        'ER01' => array(
            'responsecode' => 500, // used to send header to GP if not enforced 200 OK
            'status' => 'SERVER_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '100', // change this to whatever the GP likes to receive as code
            'message' => 'Internal Server Error.' // change this to whatever the GP likes to receive as message
        ),
        'ER02' => array(
            'responsecode' => 405,
            'status' => 'COMMAND_NOT_FOUND',
            'return' => 'default',
            'code' => '7',
            'message' => 'Command not found.'
        ),
        'ER03' => array(
            'responsecode' => 401,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => '5',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER06' => array(
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => '1',
            'message' => 'Insufficient balance.'
        ),
        'ER08' => array(
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => true,
            'code' => 'ER08',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ),
        'ER09' => array(
            'responsecode' => 404,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => '2',
            'message' => 'Player not found.'
        ),
        'ER10' => array(
            'responsecode' => 404,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => '8',
            'message' => 'Game is not found.'
        ),
        'ER11' => array(
            'responsecode' => 498,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => '4',
            'message' => 'Token not found.'
        ),
        'ER14' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_UNKNOWN',
            'return' => 'default',
            'code' => '110',
            'message' => 'Freespin stake transaction not found.'
        ),
        'ER16' => array(
            'responsecode' => 400,
            'status' => 'REQUEST_INVALID',
            'return' => 'default',
            'code' => '7',
            'message' => 'Invalid request.'
        ),
        'ER30' => array(
            'responsecode' => 500,
            'status' => 'REQUEST_INVALID',
            'return' => 'default',
            'code' => '120',
            'message' => 'Invalid request.'
        ),
    );
    private $_m_sToken = '';

    //store transaction id
    private $_m_sExisting_transaction = '';

    private const COMMUNITY_JP_WIN = 4;
    private const JACKPOT_WIN =12;
    private const ADJUST_TYPE =91;
    /**
     * Set the defaults
     * Seperate function so it can be called also from the classes that extend TestGp class
     *
     * @return Gp
     */
    public function setDefaults()
    {
        $this->_logIt([__METHOD__, 'testing']);
        $this
            ->_mapGpMethods($this->_m_aMapGpMethods)
            ->_whiteListGpIps($this->getSetting('whitelisted_ips'))
            ->_overruleErrors($this->_m_aErrors)
            ->_checkDeclaredProperties()
            ->_setWalletActions();

        return $this;
    }

    /**
     * @return bool
     */
    public function doConfirmByRoundId() {
        return !($this->isTournamentMode() || $this->_isFreespin());
    }

    public function preProcess()
    {

        $aJson = $aAction = array();
        $method = $key = null;

        $this->setDefaults();

        $params = $_POST;

        $this->_logIt([__METHOD__, print_r($_GET,true)]);
        $this->_setGpParams($params);

        if (empty($params)) {
            // request is empty
            $this
                ->_setHttpContentType(Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML)
                ->_forceHttpOkResponse(false)
                ->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }

        $aMethods = $this->_getMappedGpMethodsToWalletMethods();


        // map the requested method to an internal 1
        $sAction = str_replace('.html', '', $_GET['action']);
        foreach ($aMethods as $key => $value) {
            if ($sAction == $key) {
                $method = $key;
                $this->_setGpMethod($method);
                break;
            }
        }

        if (empty($method)) {
            $this->_logIt([__METHOD__, $method]);
            // method to execute not found
            $this
                ->_setHttpContentType(Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML)
                ->_forceHttpOkResponse(false)
                ->_setResponseHeaders($this->_getError(self::ER02));
            die();
        }

        // check if session does exist if so get the data from the session
        if (!empty($params['token'])) {
            $mSessionData = $this->fromSession($params['token']);
            $this->_logIt([__METHOD__, print_r($mSessionData,true)]);
            if(!empty($mSessionData)){
                $this->_m_sToken = $params['token'];
                $aJson['playerid'] = $mSessionData->userid;
                $aJson['skinid'] = $mSessionData->gameid;
                $aJson['device'] = $mSessionData->device;
            }
        }
        if (!empty($params['hash'])) {
            $aJson['hash'] = $params['hash'];
        }

        if (!isset($aJson['playerid']) && !empty($params['userId'])) {
            $aJson['playerid'] = $params['userId'];
        }

        // used in realitycheck
        if (!isset($aJson['playerid']) && !empty($params['playerId'])) {
            $aJson['playerid'] = $params['playerId'];
        }

        if (!isset($aJson['skinid'])) {
            if(!empty($params['gameId'])) {
                $aJson['skinid'] = $params['gameId'];
            } else if(!empty($params['gameIdList'])) {
                $aJson['skinid'] = $params['gameIdList'];
            }
        }

        if (!isset($aJson['device']) && !empty($params['platform'])) {
            $aJson['device'] = (($params['platform'] == 'MOBILE') ? 'mobile' : 'desktop');
        }

        // single transaction to process
        $aJson['state'] = 'single';

        $aAction[0]['command'] = $this->getWalletMethodByGpMethod($method);

        if (!empty($params['reference'])) {
            $aAction[0]['parameters'] = array(
                'transactionid' => $params['reference'],
            );

            if (isset($params['amount'])) {
                $aAction[0]['parameters']['amount'] =
                    $this->convertFromToCoinage($params['amount'], self::COINAGE_UNITS, self::COINAGE_CENTS);
            }

            if (isset($params['roundId'])) {
                $aAction[0]['parameters']['roundid'] = $params['roundId'];
            }
        }

        if (isset($params['roundId'])) {
            $aAction[0]['parameters']['roundid'] = $params['roundId'];
        }

        // detect for freespin
        if (!empty($params['bonusCode'])) {
            $aJson['freespin'] = array('id' => $params['bonusCode']);
            if (!isset($aJson['skinid'])) {
                // its a bonusWin request ... lets get the gameId from the bonus_entries
                // as this param is not provided within this request
                $aFreespinData = $this->_getBonusEntryBy($aJson['playerid'], $params['bonusCode']);
                if (empty($aFreespinData)) {
                    // the frb doesnt exist so no gameId to retrieve
                    return $this->_response($this->_getError(self::ER17));
                }
                $aJson['skinid'] = trim($this->stripPrefix($aFreespinData['game_id']));
            }
        }
        if (!empty($params['userAction'])) {
            $aJson['rc_action'] = $params['userAction'];
        }

        $aJson['action'] = $aAction[0];

        $this->_logIt([__METHOD__, print_r($aJson, true)]);

        // Setup BoS
        $this->getUsrId($aJson['playerid']);

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
        $aGetParams = $this->getGpParams();
        $sHash = '';
        if (isset($aGetParams['hash'])) {
            $sHash = $aGetParams['hash'];
            unset($aGetParams['hash']);
        }

        ksort($aGetParams);
        if ($sHash === $this->getHash(urldecode(http_build_query($aGetParams)), self::ENCRYPTION_MD5)) {
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
        } else {
            // secret key not valid
            $mResponse = $this->_getError(self::ER03);
        }

        // Update the user data after each command
        if (isset($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }
        $this->_response($mResponse);
    }

    protected function _getGameBalance()
    {
        return parent::_balance();
    }

    /**
     * Update the status in the bonus entries table when a FRB round has finished
     * Pragmatic sends frw one by one and also a final frw with total sum, we only process the final frw
     * @param stdClass $p_oParameters
     * @return bool
     */
    protected function _frbStatus(stdClass $p_oParameters)
    {
        $this->_logIt([__METHOD__, print_r($p_oParameters, true)]);
        // we must insert the final win having the total sum of all free round wins
        $this->_m_bFrwSendPerBet = true;
        if ($this->_win($p_oParameters)) {
            $this->_m_bFrwSendPerBet = false;
            return $this->_handleFspinWin($p_oParameters->amount);
        }
        return false;
    }

    protected function _response($p_mResponse)
    {
        $uid = $this->getUsrId($this->_m_oRequest->playerid);
        $user = cu($uid);
        $is_tournament = $this->isTournament($this->_m_oRequest->playerid);
        $aUserData = $this->_getUserData();
        $sCurrency = 'FUN';
        $iCashBalance = 0;
        $iUserId = '';
        $aResponse = array(
            'error' => 0,
            'description' => 'Success',
        );


        if (!empty($aUserData)) {
            $sCurrency = $is_tournament ? phive('Tournament')->curIso() : $aUserData['currency'];
            $iUserId = $this->_m_oRequest->playerid;
            $this->getUsrId($iUserId); // to refresh the t_entry data
            $iCashBalance = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS);
        }

        if ($p_mResponse !== true) {
            $aResponse['error'] = $p_mResponse['code'];
            $aResponse['description'] = $p_mResponse['message'];
            if (in_array($this->getGpMethod(), array('bet', 'result', 'refund', 'bonusWin', 'jackpotWin','adjustment', 'promoWin','session/expire'))) {
                $aResponse['transactionId'] = 0;
                if (in_array($this->getGpMethod(), array('promoWin','jackpotWin','adjustment'))){
                    $aResponse = $this->setidempotentAResponse($aResponse, $sCurrency, $iCashBalance, $this->_m_sExisting_transaction['id'], $p_mResponse);
                }
            }

        } else {
            if (!in_array($this->getGpMethod(), array('refund', 'realitycheck', 'promoWin','jackpotWin','sessionExpire','session/expire'))) {
                if($this->getGpMethod() === 'getBalancePerGame'){
                    $aResponse['gamesBalances'] = array(
                        array(
                            'gameID' => $this->_m_oRequest->skinid,
                            'cash' => $iCashBalance,
                            'bonus' => 0
                        )
                    );
                }else if(!in_array($this->getGpMethod() ,[ 'roundDetails','session/expire'])){
                    $aResponse = $this->getAResponse($iCashBalance, $aResponse);

                }
            }

            if (!in_array($this->getGpMethod(), array('refund', 'endRound', 'realitycheck', 'getBalancePerGame','roundDetails','session/expire'))) {
                $aResponse['currency'] = $sCurrency;
            }

            switch ($this->getGpMethod()) {
                case 'authenticate':
                    $aResponse['userId'] = $iUserId;
                    $aResponse['token'] = $this->_m_sToken;
                    $aResponse['jurisdiction'] = $this->getJurisdiction($iUserId, 'jurisdiction');

                    $maxBetLimit = phive('Gpr')->getMaxBetLimit($user);
                    if (!empty($maxBetLimit)) {
                        $aResponse['extraInfo']['jurisdictionMaxBet'] = $maxBetLimit;
                    }
                    break;
                case 'bet':
                    $bReturnTxn = true;

                    if ( $this->getRcPopup($this->_m_oRequest->device, $user) === 'ingame' ) {
                        $rg = rgLimits();
                        $intv = $rg->getRcLimit($user)['cur_lim'];
                        if (!empty($intv) && empty(phMgetShard(self::PREFIX_MOB_RC_TIMEOUT, $iUserId))) {
                            $bReturnTxn = false;
                            // Gp send bet => we detect rc expired but we will insert the bet and
                            // we send a popup message
                            // player presses continue => GP will send ajax to diamondbet to reset timer and refund request either way,
                            // so we r able to cancel the bet.
                            // This bet will be invalidated with a refund request regardless of what player clicked.
                            $lang = phMgetShard(self::PREFIX_MOB_RC_LANG, $iUserId);
                            $platform = $this->_m_oRequest->device;
                            $rcmsg = lic('getRealityCheck', [$user, $lang, $this->getGamePrefix() . $this->_m_oRequest->skinid], $user);

                            // Custom message
                            $aResponse['messageTriggers'] = [[
                                "title"=> t('reality-check.label.title', $lang),
                                "text"=> $rcmsg['message'],
                                "nonIntrusive"=> false,
                                "options"=> [
                                    [
                                        "action"=> "link",
                                        "label"=> t('reality-check.label.closeAndResumeGame', $lang),
                                        "linkType" => "ajax",
                                        "url" => phive()->getSiteUrl() . $this->getSetting('new_launchurl') . "?continue=true&userid={$iUserId}"
                                    ],
                                    [
                                        "action"=> "link",
                                        "label"=> t('reality-check.label.gameHistory', $lang),
                                        "linkType"=> "redirect",
                                        "url"=> $this->wrapUrlInJsForRedirect($this->getHistoryUrl(false,$user, $lang, $platform))
                                    ],
                                    [
                                        "action"=> "link",
                                        "label"=> t('reality-check.label.leaveGame', $lang),
                                        "linkType"=> "redirect",
                                        "url"=> $this->wrapUrlInJsForRedirect($this->getLobbyUrl(false, $lang, $platform))
                                    ]
                                ]
                            ]];
                            $this->_logIt(['response', print_r($rcmsg, true), print_r($aResponse, true)]);
                        }
                    }

                    if ($bReturnTxn === true) {
                        $aResponse['transactionId'] = (
                        ($this->_isFreespin() && $this->_m_bConfirmFrbBet === false)
                            ? uniqid()
                            : $this->_getTransaction('txn',self::TRANSACTION_TABLE_BETS)
                        );
                        $aResponse['usedPromo'] = 0;
                    }

                    break;

                case 'bonusWin':
                    $aFreespinData = $this->_getFreespinData();
                    $aResponse['transactionId'] = (
                    ($this->_m_bIsFreespin === true) ? $aFreespinData['id'] . '-' . $aFreespinData['bonus_id'] : 0);
                    break;

                case 'result':
                    if(isset($aResponse['promoWinAmount'])){
                        $aResponse['amount'] +=$aResponse['promoWinAmount'];
                    }
                case 'jackpotWin':
                    $aResponse['transactionId'] = (
                    ($this->_isFreespin() && $this->_m_bFrwSendPerBet === false)
                        ? uniqid()
                        : $this->_getTransaction('txn',self::TRANSACTION_TABLE_WINS)
                    );
                    $iCashBalance = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS);
                    // id from cash_transactions table
                    $aResponse['transactionId'] = $this->_m_iJackPotWinTransactionId;
                    $aResponse = $this->getAResponse($iCashBalance, $aResponse);
                    break;
                case 'promoWin':
                    //get cashBalance for the user
                    $iCashBalance = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS);
                    // id from cash_transactions table
                    $aResponse['transactionId'] = $this->_m_iPromoWinTransactionId;
                    $aResponse = $this->getAResponse($iCashBalance, $aResponse);
                    break;
                case 'adjustment':
                    //get cashBalance for the user
                    $iCashBalance = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS);
                    // id from cash_transactions table
                    $aResponse['transactionId'] = $this->_m_iAdjustTransactionId;
                    $aResponse = $this->getAResponse($iCashBalance, $aResponse);
                    break;
                case 'refund':
                    $aRefund = array();
                    if (!empty($this->_getTransaction('txn',self::TRANSACTION_TABLE_BETS))) {
                        $aRefund[] = $this->_getTransaction('txn',self::TRANSACTION_TABLE_BETS);
                    }
                    if (!empty($this->_getTransaction('txn',self::TRANSACTION_TABLE_WINS))) {
                        $aRefund[] = $this->_getTransaction('txn',self::TRANSACTION_TABLE_WINS);
                    }
                    $aResponse['transactionId'] = implode('-', $aRefund);
                    break;
                case 'balance':
                    $aResponse['totalBalance'] = $this->convertFromToCoinage($this->_getBalance([], [], true), self::COINAGE_CENTS, self::COINAGE_UNITS);
                    break;
            }
        }

        $this->_setResponseHeaders($p_mResponse);
        $result = json_encode($aResponse);
        $this->_logIt([__METHOD__, print_r($p_mResponse, true), $result]);
        echo $result;
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
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
        $iEndTime = strtotime("{$p_aBonusEntry['end_time']} 23:59:59");
        $a = array(
             'secureLogin' => $this->getBrand($user),
            // User name for authentication in the Casino Game API service
            'playerId' => (string)$p_iUserId,
            // Id of the player within the Operator system.
            'currency' => $user->data['currency'],
            // Currency of the player.
            'gameIDList' => implode(',', explode('|', $this->stripPrefix($p_sGameIds))),
            // List of symbolic unique identifiers of the game that the FR is awarded for, comma separated.
            // Example: vs25a, vs9c, vs20s.
            'rounds' => $p_iFrbGranted,
            // Number of free game rounds awarded.
            'bonusCode' => $p_aBonusEntry['id'],
            // Bonus id within the Operator system. Should be unique within the brand.
            'expirationDate' => $iEndTime,
            //DateTime when the free rounds will expire and not more than 30 days.
        );

        if ($iEndTime > (time() + 2592000)) {
            // end date is more than 30 days which is not allowed according manual
            $a['expirationDate'] = time() + 2505600;
        }

        ksort($a);

        $a['hash'] = $this->getHash(http_build_query($a), self::ENCRYPTION_MD5);

        $result = phive()->post(
            $this->getSetting('frb_apiurl') . '/createFRB/',
            http_build_query($a),
            'application/x-www-form-urlencoded',
            "Cache-Control: no-cache",
            $this->getGamePrefix() . 'out',
            'POST'
        );
        $oData = json_decode($result, false);

        // log response for debugging
        $this->_logIt([
            __METHOD__,
            'URL: ' . $this->getSetting('frb_apiurl') . '/createFRB/',
            print_r($a, true),
            print_r($p_aBonusEntry, true),
            print_r($oData, true)
        ]);

        if (empty($oData->error)) {
            $this->attachPrefix($oData->providerPlayerID);
            return $oData->providerPlayerID;
        }

        return false;
    }

    /**
     * Get players free spin bonuses list from provider
     *
     * @param int $p_iUserId
     * @return mixed
     */
    public function getPlayersFRB($p_iUserId)
    {

        $user = cu($p_iUserId);

        $a = array(
            'secureLogin' => $this->getBrand($user), // should be videoslots-se videoslots-uk or videoslots-de($user) ,
            'playerId' => $p_iUserId,
        );

        ksort($a);

        $a['hash'] = $this->getHash(http_build_query($a), self::ENCRYPTION_MD5);
        $result = phive()->post(
            $this->getSetting('frb_apiurl') . '/getPlayersFRB/',
            http_build_query($a),
            'application/x-www-form-urlencoded',
            "Cache-Control: no-cache",
            $this->getGamePrefix() . 'out',
            'POST'
        );
        $oData = json_decode($result, false);
        $this->_logIt([
            __METHOD__,
            'URL: ' . $this->getSetting('frb_apiurl') . '/getPlayersFRB/',
            print_r($oData, true)
        ]);
        if (empty($oData->error)) {
            return $oData;
        }

        return false;
    }

    /**
     * Handle promoWin request from GP
     * At the moment, this function replicates the process which was being done manually in Backoffice to award Players cash balance.
     * TODO: Need to figure out proper steps to be perrformed when this request comes and implement those in this function
     * @return true|array()
     *
     * Example of response sent to GP, after processing the request
     * { "transactionId": 1482492905503,  "currency": "USD",  "cash": 99815.04,  "bonus": 99.99,  "error": 0,  "description":"Success"}
     */

    public function _promoWin()
    {
        // putting this in variable so it becomes easier to modify if we decide to change it, or decide to get predefined value from DB or config
        $transaction_type = self::COMMUNITY_JP_WIN;
        $response = [];
        $aUserData = $this->_getUserData();
        // Player not found check
        if (empty($aUserData)) {
            return $this->_m_aErrors['ER09'];
        }
        $GpParams = $this->getGpParams();
        // since we donot have a game reference in this request, and we are not able to use wins or rounds to uniquely identify this transaction
        // So we save $GpParams['reference'] in a formatted string in description field, and then use it with user_id, bonus_id, amout and transactiontype fields to establish uniqueness of this record
        $description = sprintf('PragmaticPromoWin:%d:%s:%s', $GpParams['campaignId'], $GpParams['campaignType'], $GpParams['reference']);
        $amount = $this->convertFromToCoinage($GpParams['amount'], self::COINAGE_UNITS, self::COINAGE_CENTS);
        // check if a transaction already exists against this PromoWin, to avoid adding balance multiple times
        $this->_m_sExisting_transaction = phive('Cashier')->getTransaction(cu($aUserData), [
            'bonus_id' => $GpParams['campaignId'],
            'transactiontype' => $transaction_type,
            'amount' => $amount,
            'description' => $description
        ]);

        if (!empty($this->_m_sExisting_transaction)) {
            $response = $this->_m_aErrors['ER30'];
        }else {
            $transaction_id = phive('Cashier')->transactUser(
                $aUserData,
                $amount,
                $description,
                null,
                null,
                $transaction_type,
                false,
                $GpParams['campaignId']
            );

            if ($transaction_id) {
                $this->_m_iPromoWinTransactionId = $transaction_id;
                return true;
            } else {
                $response = $this->_m_aErrors['ER01'];
            }
        }
        return $response;
    }


    /**
     * Handle jackpotWin request from GP
     * @return array|bool
     */
    public function _jackpotWin()
    {
        $transaction_type = self::JACKPOT_WIN;
        $aUserData = $this->_getUserData();
        // Player not found check
        if (empty($aUserData)) {
            return $this->_m_aErrors['ER09'];
        }
        $GpParams = $this->getGpParams();
        $description = sprintf('PragmaticJackpotWin:%d:%d:%s:%s:%s:%s:%s',
            $GpParams['jackpotId'], $GpParams['roundId'], $GpParams['gameId'],
            $GpParams['providerId'], $GpParams['platform'], $GpParams['reference'], $aUserData["id"]
        );
        $amount = $this->convertFromToCoinage($GpParams['amount'], self::COINAGE_UNITS, self::COINAGE_CENTS);

        $p_oParameters = new stdClass;
        $p_oParameters->amount = $amount;
        $p_oParameters->jpw = $amount;
        $p_oParameters->roundid = $GpParams['roundId'];
        $p_oParameters->transactiondate = phive()->hisNow();
        $p_oParameters->transactionid = $GpParams['reference'];
        $win_id = $this->_win($p_oParameters);
        if ($win_id) {
            $this->_m_iJackPotWinTransactionId = $GpParams['reference'];
            phive('UserHandler')->logAction($aUserData["id"], $description, 'PragmaticJackpotWin',true, $aUserData["id"]);
            return true;
        }

        return $this->_m_aErrors['ER01'];
    }

    /*
     *
     */
    public function _roundDetails(){
        if (!$this->doConfirmByRoundId()) {
            return true;
        }
        // Player not found check
        $aUserData = $this->_getUserData();
        // Player not found check
        if (empty($aUserData)) {
            return $this->_m_aErrors['ER09'];
        }
        $GpParams = $this->getGpParams();
        $userId = $GpParams["userId"];
        $this->attachPrefix($GpParams["roundId"]);
        $round = phive('Casino')->getRound($userId,$GpParams["roundId"]);
        $this->updateRound($userId, $GpParams["roundId"], $round['win_id']);

        return true;
    }

    /*
     * Using this method the Pragmatic Play system will send to Casino Operator the amount player's balance to be adjusted with
     *
     */
    public function _adjustment(){
        $transaction_type = self::ADJUST_TYPE;
        $aUserData = $this->_getUserData();
        // Player not found check
        if (empty($aUserData)) {
            return $this->_m_aErrors['ER09'];
        }

        $response = [];
        $GpParams = $this->getGpParams();
        $description = sprintf('PragmaticAdjustBalance:%d:%d:%d:%s:%d:%s',
            $GpParams['gameId'], $GpParams['roundId'], $GpParams['amount'],
            $GpParams['providerId'], $GpParams['validBetAmount'], $GpParams['reference']);
        $amount = $this->convertFromToCoinage($GpParams['amount'], self::COINAGE_UNITS, self::COINAGE_CENTS);


        // check if a transaction already exists against this JackpotWin, to avoid adding balance multiple times
        $this->_m_sExisting_transaction = phive('Cashier')->getTransaction(cu($aUserData), [
            'bonus_id' => $GpParams['roundId'],
            'transactiontype' => $transaction_type,
            'amount' => $amount,
            'description' => $description
        ]);

        if(($amount<0 && (abs($amount) > $aUserData['cash_balance']))){
            $response =  $this->_m_aErrors['ER06'];
        }else if (!empty($this->_m_sExisting_transaction)) {
            $response = $this->_m_aErrors['ER30'];
        }else {
            $transaction_id = phive('Cashier')->transactUser(
                $aUserData,
                $amount,
                $description,
                null,
                null,
                $transaction_type,
                false,
                $GpParams['roundId']
            );

            if ($transaction_id) {
                $this->_m_iAdjustTransactionId = $transaction_id;
                return true;
            } else {
                $response = $this->_m_aErrors['ER01'];
            }
        }
        return $response;

    }

    /*
     * Using this method the Pragmatic Play system will send to Casino Operator the amount player's balance to be adjusted with
     *
     */
    public function _sessionExpire(){
        $GpParams = $this->getGpParams();

        $aUserData = cu($GpParams["playerId"]);
        // Player not found check
        if (empty($aUserData)) {
            return $this->_m_aErrors['ER09'];
        }
        return $aUserData->endGameSessionByGameSessionId($GpParams["sessionId"],$aUserData->userId);
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
    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false, ?int $user_id = null)
    {
        $aUrl = array();
        $aUrl['language'] = $p_sLang;
        $aUrl['lobbyUrl'] = $this->getLobbyUrl(true, $p_sLang);
        $aUrl['symbol'] = $p_mGameId;

        if (isLogged($user_id)) {
            $cu = cu();
            $ud = $cu->data;
            $iUserId = $ud['id'];
            if (empty($_SESSION['token_uid'])) {
                $sSecureToken = $this->getGuidv4($iUserId);
            } else {
                $iUserId = $sSecureToken = $_SESSION['token_uid'];
                $this->getUsrId($iUserId);
            }

            $this->checkRealityInterval($cu, $p_sTarget, $p_sLang, $p_mGameId);

            $aUrl['externalPlayerId'] = $iUserId;
            $aUrl['secureLogin'] = $this->getBrand($cu); // should be videoslots-se videoslots-uk or videoslots-de
            $aUrl['token'] = $sSecureToken;
            // Since we have x1 turnover logic for IT and we force all ES players to verify so all good, we can just send 'y' here.
            $aUrl['promo'] = 'y';
            $aUrl['technology'] = 'H5';
            $aUrl['platform'] = (($p_sTarget === 'mobile') ? 'MOBILE' : 'WEB');
            $aUrl['cashierUrl'] = urlencode($this->getBasePath() . '/cashier/deposit/');
            $aUrl['currency'] = $ud['currency'];
            $aUrl['playMode'] = 'REAL';
        } else {
            $aUrl['currency'] = lic('getForcedCurrency', []) ?: $this->getSetting('freeplaycurrency');
            $aUrl['secureLogin'] = $this->getBrand();
            $aUrl['playMode'] = 'DEMO';
        }

        ksort($aUrl);
        $aUrl['hash'] = $this->getHash(urldecode(http_build_query($aUrl)), self::ENCRYPTION_MD5);

        $this->dumpTst('Pragmatic-Launch-url-Request', $aUrl);

        $url = phive()->post(
            $this->getSetting('new_launchurl'),
            http_build_query($aUrl),
            'application/x-www-form-urlencoded',
            'Cache-Control: no-cache',
            'Pragmatic-Launch-url-Request',
            'POST',
            $this->getSetting('soap_timeout')
        );

        $url = json_decode($url, TRUE);

        if ((int)$url['error'] === 0) {
            if (isLogged($user_id)) {
                $this->toSession($aUrl['token'], $iUserId ?? 0, $p_mGameId, $p_sTarget);
            }

            return $url['gameURL'];
        }

        return false;
    }

    /**
     * Return the brand using the following priority (high to low):
     * - if is BoS then we always enforce the "bos-country" brand if exist, otherwise DEFAULT
     * - if we have the user country specified in "rtp-country" that brand will be set for the player
     * - if we don't have the user country in "rtp-country" the "DEFAULT" brand will be used instead
     * - if no setting exist, we return '' (should happen only in demo mode)
     *
     * @param $user
     * @return string
     */
    public function getBrand($user = null)
    {
        $brand_map = $this->getSetting('brand');

        if(!empty($user)) {
            if (!empty($this->t_eid)) {
                $country = $this->getLicSetting('bos-country', $user);
            } else {
                $brand = $this->getLicSetting('brand', $user);
                if(!empty($brand)){
                    return $brand;
                }
                $country = phive('Licensed')->getLicCountryProvince($user);
            }
            return $brand_map[$country] ?? $brand_map['DEFAULT'];
        } else {
            return  $brand_map['DEFAULT'];
        }
    }


    /**
     * @param $sCurrency
     * @param array $aResponse
     * @param $iCashBalance
     * @return array
     */
    protected function setidempotentAResponse($aResponse, $sCurrency, $iCashBalance, $transaction_id, $p_mResponse)
    {
        if($p_mResponse["status"] != "UNAUTHORIZED" && $p_mResponse["status"] != "INSUFFICIENT_FUNDS") {
            if ($p_mResponse["status"] == 'REQUEST_INVALID') {
                $aResponse['error'] = 0;
                $aResponse['description'] = "Success";
            }
            $aResponse['transactionId'] = $transaction_id;
            $aResponse['currency'] = $sCurrency=="FUN"?"":$sCurrency;
            $aResponse = $this->getAResponse($iCashBalance, $aResponse);
        }
        return $aResponse;
    }

    /**
     * @param $iCashBalance
     * @param array $aResponse
     * @return array
     */
    protected function getAResponse($iCashBalance, array $aResponse): array
    {
        $aResponse['cash'] = $iCashBalance;
        $aResponse['bonus'] = 0;
        return $aResponse;
    }

    /**
     * Get URL to launch game
     *
     * @param string $gameId
     * @param int $userId
     * @param string $lang
     * @param string $target
     *
     * @return string|null
     *
     * @api
     */
    public function getApiUrl(
        string $gameId,
        int $userId,
        string $lang,
        string $target = 'mobile'
    ): ?string {
        $url = $this->_getUrl(trim($this->stripPrefix($gameId)), $lang, $target, false, $userId);

        return $url === false ? null : $url;
    }

    /**
     * @param User $user
     * @param string $target
     * @param string $lang
     * @param string|null $game_ext_name
     *
     * @return void
     */
    private function checkRealityInterval(
        User $user,
        string $target,
        string $lang,
        ?string $game_ext_name = null
    ): void {
        if ($this->getRcPopup($target, $user) !== 'ingame') {
            return;
        }

        $rcInterval = phive('Casino')->startAndGetRealityInterval($user, $game_ext_name);

        if (! empty($rcInterval)) {
            $timeRemaining = $this->getTimeRemaining($rcInterval, null, $user);

            phMsetShard(self::PREFIX_MOB_RC_LANG, $lang, $user->userId);
            phMsetShard(self::PREFIX_MOB_RC_TIMEOUT , '1', $user->userId, $timeRemaining);
            phMsetShard(self::PREFIX_MOB_RC_PLAYTIME, time(), $user->userId);
        }
    }

    /**
     * This function is used by us in order to close rounds that are 'stuck' on GP side
     *
     * @param string $secureLogin    should be videoslots-uk or similar value defined in pragmatic.config.php
     * @param int $externalPlayerId  player id within our system (example: 123456)
     * @param string $gameId         id of the game (example: vs25wolfgold)
     * @param int $roundId           id of the round we will cancel which can be acquired from the ext_round_id column
     *                               in the rounds table (example: 12345678)
     * @return string
     */
    public function _cancelRound(string $secureLogin, int $externalPlayerId, string $gameId, int $roundId){
        $params = ['secureLogin' => $secureLogin, 'externalPlayerId' => $externalPlayerId, 'gameId' => $gameId,
                   'roundId' => $roundId];

        ksort($params);
        $params['hash'] = $this->getHash(urldecode(http_build_query($params)), self::ENCRYPTION_MD5);

        $response = phive()->post(
            $this->getSetting('cancel_round_url'),
            http_build_query($params),
            'application/x-www-form-urlencoded',
            'Cache-Control: no-cache',
            '',
            'POST',
            $this->getSetting('soap_timeout')
        );

        phive()->dumpTbl('pragmatic-response', $response);
        return $response;
    }

    public function parseJackpots(): array
    {
        $jackpots = [];
        $brands =  array_diff_key($this->getSetting('brand'), array_flip(['IT', 'DE', 'DK']));

        foreach ($brands as $jur => $brand) {
            $currency = phive('Currencer')->getCurrencyByCountryCode($jur)['code'] ?? 'EUR';
            $request_params = [
                'login' => $brand,
                'currency' => $currency
            ];
            ksort($request_params);
            $request_params['hash'] = $this->getHash(urldecode(http_build_query($request_params)), self::ENCRYPTION_MD5);

            $jackpot_values = $this->getJackpotValues($request_params);

            foreach ($jackpot_values as $jackpot) {
                $games = explode(",", $jackpot['games']);

                foreach ($games as $game_id) {
                    $game = phive("MicroGames")->getByGameRef("{$this->getGpName()}_{$game_id}");
                    if (empty($game)) {
                        continue;
                    }

                    $jackpots[] = [
                        'local' => 0,
                        'network' => $this->getGpName(),
                        'jurisdiction' => $jur,
                        'game_id' => $this->getGpName().'_'.$game_id,
                        'jp_name' => $jackpot['name'],
                        'module_id' => $this->getGpName().'_'.$game_id,
                        'jp_id' => $jackpot['mainJackpotID'],
                        'currency' => $currency,
                        'jp_value' => $this->convertFromToCoinage((string)array_sum(array_column($jackpot['tiers'], 'amount')), self::COINAGE_UNITS),

                    ];
                }
            }
        }

        return $jackpots;
    }


    /**
     * Get the jackpots.
     * DOC. https://marketing.pragmaticplay.com/index.php/s/xmYuh6A3hLUk6Zu#pdfviewer
     *
     * @return array[] An array of jackpot values where each jackpot contains:
     *                 - 'mainJackpotID' => Unique identifier (parent/main) of the Jackpot within Pragmatic Play system.
     *                 - 'name' => Name of the Jackpot.
     *                 - 'level' => Level of the Jackpot: G – Global Jackpot, N – Network jackpot, O– Jackpot for particular Operation, B – Jackpot for particular casino Brand
     *                 - 'games' => The list of the games participating in the Jackpot. It contains gameId (game symbols), comma separated.
     *                 - 'status' => Current status of the Jackpot. Possible values:A – ActiveS – Shut downed
     *                 - 'tiersNumber' => Parameter indicating the total number of progressive tiers (active and won) configured for a Jackpot. For Single-tier jackpots the value “1” will be specified. The non-progressive tier should NOT be included.
     *                 - 'tiers' => This list displays information only for active/open (not won) progressive tiers. An array of =>
     *                      - 'jackpotTierID' - Unique identifier of the Jackpot Tier within the Pragmatic Play system
     *                      - 'tier' - Jackpot tier name identifier. The tier index (0 - 3) that operator receives in API should be mapped with the appropriate tier in the game:0 – the 1st tier (the lowest).1 – the 2nd tier.2 – the 3rd tier,3 – the 4th tier (the highest).
     *                      - 'amount' - Jackpot fund (for specific tier) for the moment of request, in USD by default. Or values can be returned in the requested currency.
     */
    private function getJackpotValues(array $request_params): array
    {
        $api_endpoint = $this->getSetting('jp_url') .'?'. http_build_query($request_params);
        $this->logger->debug(__METHOD__ . '/request', [$api_endpoint, $request_params]);

        $response = phive()->get($api_endpoint, '', '', "{$this->getGpName()}_parse_jackpots",);

        $jackpots = json_decode($response, true);

        $this->logger->debug(__METHOD__.'/response', [$jackpots]);

        if (!empty($jackpots['jackpots']) && $jackpots['error'] == 0) {
            return $jackpots['jackpots'];
        }

        return [];
    }
}
