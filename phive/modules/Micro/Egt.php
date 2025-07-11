<?php
require_once __DIR__ . '/Gp.php';

class Egt extends Gp
{

    /**
     * Name of GP. Used to prefix the bets|wins::game_ref (which is the game ID) and bets|wins::mg_id (which is transaction ID)
     * @var string
     */
    protected $_m_sGpName = __CLASS__;

    /**
     * Find a bet by transaction ID or by round ID.
     * Mainly when a win comes in to check if there is a corresponding bet. If the transaction ID used for win is the same as bet set to false otherwise true.
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
     * Check if incoming win request has a valid bet transaction.
     * Note: this is NOT for freespins but for real money play
     * @var bool
     */
    protected $_m_bConfirmBet = false;

    /**
     * The header content type for the response to the GP
     * @var string
     */
    protected $_m_sHttpContentType = Gpinterface::HTTP_CONTENT_TYPE_TEXT_XML;

    /**
     * Do we force respond with a 200 OK response to the GP
     * @var bool
     */
    protected $_m_bForceHttpOkResponse = true;

    private $_m_sSecureToken = null;

    /**
     * Reality Check Error.
     * @var string
     */
    const ER1300 = 'ER1300';
    const ER3100 = 'ER3100';
    const ER3300 = 'ER3300';
    const ER3400 = 'ER3400';
    const ER3500 = 'ER3500';

    /**
     * Bind GP method requests to the correct class method name
     * Keys are the method names received by the GP request and the values are the class method names
     * @var array
     */
    private $_m_aMapGpMethods = array(
        'AuthRequest' => '_init',
        'WithdrawRequest' => '_bet',
        'DepositRequest' => '_win',
        'WithdrawAndDepositRequest' => '_bet_win', // will be forwarded to bet and win
        'GetPlayerBalanceRequest' => '_balance'
    );



    private $_m_aErrors = array(
        'ER01' => array(
            'responsecode' => 500,
            'status' => 'SERVER_ERROR',
            'return' => 'default',
            'code' => '3000',
            'message' => 'INTERNAL_SERVER_ERROR'
        ),

        'ER03' => array(
            'responsecode' => 401,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => 'FAILURE',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER04' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_WAS_CANCELLED',
            'return' => 'default',
            'code' => 'REJECTED',
            'message' => 'Bet transaction ID has been cancelled previously.'
        ),
        'ER05' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => 'default',
            'code' => '1100',
            'message' => 'DUPLICATE'
        ),
        'ER06' => array(
            'responsecode' => 402,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => '3100',
            'message' => 'INSUFFICIENT_FUNDS'
        ),
        'ER07' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_MISMATCH',
            'return' => 'default',
            'code' => 'REJECTED',
            'message' => 'Transaction details do not match.'
        ),
        'ER08' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 'REJECTED',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ),
        'ER09' => array(
            'responsecode' => 404,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => 'FAILURE',
            'message' => 'Player not found.'
        ),
        'ER10' => array(
            'responsecode' => 404,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => 'FAILURE',
            'message' => 'Game is not found.'
        ),
        'ER11' => array(
            'responsecode' => 498,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => 'FAILURE',
            'message' => 'Token not found.'
        ),
        'ER13' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_INVALID',
            'return' => 'default',
            'code' => 'REJECTED',
            'message' => 'Invalid freespin bet amount.'
        ),
        'ER14' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_UNKNOWN',
            'return' => 'default',
            'code' => 'REJECTED',
            'message' => 'Freespin stake transaction not found.'
        ),
        'ER15' => array(
            'responsecode' => 403,
            'status' => 'FORBIDDEN',
            'return' => 'default',
            'code' => 'UNAUTHORIZED',
            'message' => 'IP Address forbidden.'
        ),
        'ER18' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => 'default',
            'code' => '1100',
            'message' => 'DUPLICATE'
        ),
        'ER19' => array(
            'responsecode' => 200,
            'status' => 'STAKE_TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => '3000',
            'message' => 'Stake transaction not found.'
        ),
        'ER25' => array(
            'responsecode' => 200,
            'status' => 'PLAYER_BLOCKED',
            'return' => 'default',
            'code' => '3400',
            'message' => 'LOSS LIMIT REACHED (PLAYER PROTECTION)'
        ),
        'ER26' => array(
            'responsecode' => 200,
            'status' => 'PLAYER_BANNED',
            'return' => 'default',
            'code' => '3400',
            'message' => 'LOSS LIMIT REACHED (PLAYER PROTECTION)'
        ),
        'ER1300' => array(
            'responsecode' => 200,
            'status' => 'REALITY_CHECK',
            'return' => 'default',
            'code' => '1300',
            'message' => 'OK, DO REALITY CHECK (PLAYER PROTECTION)'
        ),
        'ER3100' => array(
            'responsecode' => 200,
            'status' => 'EXPIRED',
            'return' => 'default',
            'code' => '3100',
            'message' => 'EXPIRED'
        ),
        'ER3300' => array(
            'responsecode' => 200,
            'status' => 'BET LIMIT REACHED',
            'return' => 'default',
            'code' => '3300',
            'message' => 'BET LIMIT REACHED (PLAYER PROTECTION)'
        ),
        'ER3400' => array(
            'responsecode' => 200,
            'status' => 'LOSS LIMIT REACHED',
            'return' => 'default',
            'code' => '3400',
            'message' => 'LOSS LIMIT REACHED (PLAYER PROTECTION)'
        ),
        'ER3500' => array(
            'responsecode' => 200,
            'status' => 'SESSION TIME LIMIT REACHED',
            'return' => 'default',
            'code' => '3500',
            'message' => 'SESSION TIME LIMIT REACHED (PLAYER PROTECTION)'
        ),
    );

    protected string $logger_name = 'egt';

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
        return $this->getSetting('confirm_win', false);
    }

    /**
     * Pre process data received from GP
     * @return object
     */
    public function preProcess()
    {

        $this->setDefaults();

        $sData = $this->_m_sInputStream;
        $this->_setGpParams($sData);

        try {
            $xml = new SimpleXMLElement($sData);
        } catch (Exception $e) {
            $this->_response($this->_getError(self::ER16));
            die();
        }

        $xmlMethod = $xml->getName();

        $oXml = simplexml_load_string($sData);

        $this->logger->debug(__METHOD__, ['xml' => json_encode($oXml, true)]);
        $this->_logIt([__METHOD__, print_r($oXml, true)]);

        if (!($oXml instanceof SimpleXMLElement)) {
            // request is unknown
            $this
                ->_setHttpContentType(Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML)
                ->_forceHttpOkResponse(false)
                ->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }

        $aJson = $aAction = array();
        $method = null;
        $aMethods = $this->_getMappedGpMethodsToWalletMethods();

        // Define which service/method is requested/to use
        foreach ($aMethods as $key => $value) {
            if ($xmlMethod == $key) {
                $method = $key;
                $this->_setGpMethod($method);
                break;
            }
        }

        if (empty($method)) {
            // method to execute not found
            $this
                ->_setHttpContentType(Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML)
                ->_forceHttpOkResponse(false)
                ->_setResponseHeaders($this->_getError(self::ER02));
            die();
        }


        // Set method to use for Errors
        $this->_setWalletMethod($this->getWalletMethodByGpMethod($method));



        $aJson['xmlMethod'] = $xmlMethod;

        if (isset($oXml->PlayerId)) {
            if (isset($oXml->SessionId)) {
                $this->_m_sSecureToken = (string)$oXml->SessionId;
            }

            if ($this->_m_sSecureToken !== null && $this->_m_sSecureToken !== 'null' && $this->_m_sSecureToken !== '1234567890' && $xmlMethod !== 'AuthRequest' ) {
                $mSessionData = $this->fromSession(strtolower($this->_m_sSecureToken));
                if ($mSessionData === false && $xmlMethod == 'WithdrawRequest') {
                    // token not found
                    $this->_response($this->_getError(self::ER01));
                }
                $aJson['device'] = $this->string2DeviceTypeNum((string)$oXml->PlatformType);
                $this->toSession(strtolower((string)$oXml->SessionId), (string)$oXml->PlayerId, (string)$oXml->GameId, $aJson['device']);
                $aJson['skinid'] = (string)$oXml->GameId;

            } else {
                if ($xmlMethod == 'AuthRequest') {
                    if (empty (phMgetShard((string)$oXml->DefenceCode, (string)$oXml->PlayerId))) {
                        // need to set the method to select the appropriate XML file
                        $this->_setWalletMethod('_init');
                        $this->_response($this->_getError(self::ER3100));
                    }
                }
            }

            $aJson['playerid'] = (string)$oXml->PlayerId;
            $aJson['currency'] = (string)$oXml->Currency;
        }

        if (isset($oXml->GameNumber)) {
            $aAction['parameters']['roundid']= (string)$oXml->GameNumber;
        }

        if (isset($oXml->TransferId)) {
            $aAction['parameters']['transactionid']= (string)$oXml->TransferId;
        }

        if (isset($oXml->Amount)) {
            $aAction['parameters']['amount'] = (string)$this->convertFromToCoinage($oXml->Amount, self::COINAGE_CENTS, self::COINAGE_CENTS);
        }

        if (isset($oXml->WinAmount)) {
            $aAction['parameters']['winamount'] = (string)$this->convertFromToCoinage($oXml->WinAmount, self::COINAGE_CENTS, self::COINAGE_CENTS);
        }

        if (isset($oXml->Reason)) {
            $aAction['parameters']['reason'] = (string)$oXml->Reason;

            if ((string)$oXml->Reason == 'JACKPOT_END') { // if reason is JACKPOT_END then it is a jackpot
                $aAction['parameters']['jpw'] = $aAction['parameters']['amount'];
                // since for game 999 we do not have a bet, we must skip the bet check since the bet was done on another game
                $this->_m_bSkipBetCheck = true;
            }
        }

        if($xmlMethod == 'WithdrawAndDepositRequest'){
            $m_aAction[0]['parameters'] = phive()->moveit(['roundid', 'transactionid', 'amount'], $aAction['parameters']);
            $m_aAction[1]['parameters'] = phive()->moveit(['roundid', 'transactionid', 'winamount'], $aAction['parameters']);
            // for win you still must pass the win amount in the win parameter
            $m_aAction[1]['parameters']['amount'] = $aAction['parameters']['winamount'];
            $m_aAction[0]['command'] = '_bet';
            $m_aAction[1]['command'] = '_win';


            $aJson['state'] = 'multi';
            $aJson['actions'] = $m_aAction;

        } else {

            $aAction['command'] = $this->getWalletMethodByGpMethod($method);
            $aJson['state'] = 'single';
            // check for rollback here
            if ($aAction['command'] == '_win' && $aAction['parameters']['reason'] == 'ROUND_CANCEL') {
                $aAction['command'] = '_cancel';
            } elseif ($aAction['command'] == '_init') {
                $this->toSession(strtolower((string)$oXml->SessionId), (string)$oXml->PlayerId, (string)$oXml->GameId);
                $this->logger->info('Store to session', [(string)$oXml->SessionId]);
                $this->_logIt(["Store to session", print_r((string)$oXml->SessionId, true)]);
            }

            $aJson['action'] = $aAction;
        }

        $this->_m_oRequest = json_decode(json_encode($aJson), false);

        $this->logger->debug(__METHOD__, ['request' => json_encode($this->_m_oRequest, true)]);
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

                if ($sMethod == "_bet" && $mResponse['code'] == 3100){
                    $balance = $this->_getBalance();
                    $betAmount = $oAction->parameters->amount;
                    //check if responsible gaming error
                    if ($balance > $betAmount){
                        $this->_response($this->_getError(self::ER3500));
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
     * Get the launcher url to launches the game
     *
     * @param string $p_mGameId The micro_games:game_ext_name without the prefix so basically the game ID as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @param bool $show_demo
     * @return string The url to open the game
     */
    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        $url = phive()->getSiteUrl() . "/diamondbet/egt.php?game_id=$p_mGameId&lang=$p_sLang&target=$p_sTarget";
        return $url;
    }

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $p_mGameId The game_ext_name from the microgames table without the prefix so basically the game ID as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @return string The url to open the game
     */
    public function getIframeUrl($p_mGameId, $p_sLang = '', $p_sTarget = '')
    {
        $aUrl['gameId'] = $p_mGameId;
        $aUrl['closeurl'] = $this->getLobbyUrl(true, $p_sLang,$p_sTarget);

        if ($p_sTarget === 'mobile') {
            $aUrl['client'] = 'mobile';
        } else {
            $aUrl['client'] = 'desktop';
        }

        if (isLogged()) {
            $ud = cuPl()->data;
            $aUrl['language'] = $ud['preferred_lang'];
            $defenceCode = $this->getGuidv4($ud['id']);
            $aUrl['defenceCode'] = $defenceCode;
            $aUrl['playerId'] = $ud['id'];
            $aUrl['portalCode'] =  $this->getLicSetting('portal_code') . $ud['currency'];
            $aUrl['country'] = $ud['country'];
            $url = $this->getLicSetting('egturl') . '?' . http_build_query($aUrl);

            // Set the defence code to be used for only 15 seconds
            phMsetShard($defenceCode, '1', $ud['id'], 15);

            // Reality Check Initialisation
            $rcInterval = phive('Casino')->startAndGetRealityInterval($ud['id'], $p_mGameId);

            if (!empty($rcInterval)) {
                phMsetShard(self::PREFIX_MOB_RC_TIMEOUT . $ud['id'], '1', $ud['id'], $rcInterval*60);
                phMsetShard(self::PREFIX_MOB_RC_PLAYTIME . $ud['id'], time(), $ud['id']);
            }

        } else {
            // demo game in here
            $token = $this->getPlayerAuthorization();
            $aUrl['gameLaunchToken'] = $token;
            $url = $this->getLicSetting('freeplayurl') . '?' . http_build_query($aUrl);
        }

        $this->logger->info(__METHOD__, [sprintf('url: %s %s', $url, json_encode($aUrl, true))]);
        $this->_logIt([__METHOD__, 'url: ' . $url, print_r($aUrl,true)]);
        return $url;
    }


    /**
     * Send a response to gp
     *
     * @param mixed $p_mResponse true if command was executed succesfully or an array with the error details from property $_m_aErrors
     * @return mixed The headers and depending on other response params a json_encoded string, which is the answer to gp
     */
    protected function _response($p_mResponse)
    {
        $sGameId = (isset($this->_m_oRequest->skinid) ? $this->_m_oRequest->skinid : 0);
        $sGameName = (isset($this->_m_oRequest->gamename) ? $this->_m_oRequest->gamename : 'unknown');
        $sCurrency = (isset($this->_m_oRequest->currency) ? $this->_m_oRequest->currency : 'FPY');
        $iUserId = (isset($this->_m_oRequest->playerid) ? $this->_m_oRequest->playerid : 0);
        $iTxnId = (isset($this->_m_oRequest->txnId) ? $this->_m_oRequest->txnId : 0);

        $aUserData = $this->_getUserData();
        $aGameData = $this->_getGameData();

        if (!empty($aGameData)) {
            $sGameId = $this->stripPrefix($aGameData['ext_game_name']);
            $sGameName = $aGameData['game_name'];
        }

        if (!empty($aUserData)) {
            $iUserId = $aUserData['id'];
            $sCurrency = $aUserData['currency'];
        }

        $iBalance = $this->_getBalance();

        $aResponse = array(
            'userId' => $iUserId,
            'balance' => $iBalance,
            'err_message' => (isset($p_mResponse['message']) ? $p_mResponse['message'] : ''),
            'err_code' => (isset($p_mResponse['code']) ? $p_mResponse['code'] : 'FAILURE'),
            'txn' => $iTxnId,
            'secureToken' => (!empty($this->_m_sSecureToken) ? strtoupper($this->_m_sSecureToken) . '"' : ''),
            'gameId' => $sGameId,
            'gameName' => $sGameName,
            'currency' => $sCurrency,
            'xmlMethod' => $this->getGpMethod(),
            'tot_bet' => -1,
            'tot_win' => -1,
            'tot_playtime' => -1
        );

        if ($p_mResponse === true) {
            $aResponse['err_message'] = 'OK';
            $aResponse['err_code'] = '1000';
        }

        //Since the rollback response is the same as win request we change the method
        if($this->_getMethod() == '_cancel')
            $aResponse['xmlMethod'] = '_win';
        else
            $aResponse['xmlMethod'] = $this->_getMethod();

        // Extra check if response is not populated and if it is empty we must set it, else there will be no XML return
        if (empty($aResponse['xmlMethod'])) {
            $aResponse['xmlMethod'] = '_bet';
        }

        if (in_array($aResponse['xmlMethod'], array('_bet', '_win', '_bet_win'))) {
            // if it has multiple actions then it is for sure a bet and win
            if($this->_hasMultiTransactions() || $aResponse['xmlMethod'] == '_bet_win'){
                $aResponse['casinotransferid'] = $this->_getTransaction('txn', self::TRANSACTION_TABLE_BETS);
                $aResponse['xmlMethod'] = '_betWin';

                // check for Reality Check pop up here
                // it must be exactly after the win has been awarded
                // if time is elapsed then throw reality check error
                //TODO to refactor / Ricardo
                if (licJur($iUserId) == 'GB') {
                    if (empty(phMgetShard(self::PREFIX_MOB_RC_TIMEOUT .  $iUserId, $iUserId))) {
                        //get current user session
                        $user = cu($iUserId);
                        $sessionBetsAndWins = phive('UserHandler')->sumGameSessions($iUserId, $user->getCurrentSession()['created_at']);
                        $rcTotalBet = $sessionBetsAndWins['bet_amount'];
                        $rcTotalWin = $sessionBetsAndWins['win_amount'];
                        $rcInterval = phive('Casino')->startAndGetRealityInterval($iUserId);
                        phMsetShard(self::PREFIX_MOB_RC_TIMEOUT . $iUserId, '1', $iUserId, $rcInterval * 60);
                        //set error 1300
                        $aResponse['err_code'] = 1300;
                        $aResponse['err_message'] = 'OK, DO REALITY CHECK (PLAYER PROTECTION)';
                        $aResponse['tot_bet'] = $rcTotalBet;
                        $aResponse['tot_win'] = $rcTotalWin;
                        $aResponse['tot_playtime'] = floor((time() - phMgetShard(self::PREFIX_MOB_RC_PLAYTIME . $iUserId, $iUserId)) / 60);
                    }
                }

            } else if ($aResponse['xmlMethod'] === '_bet') {
                $aResponse['casinotransferid'] = $this->_getTransaction('txn', self::TRANSACTION_TABLE_BETS);
            } else if($aResponse['xmlMethod'] === '_win') {
                $aResponse['casinotransferid'] = $this->_getTransaction('txn', self::TRANSACTION_TABLE_WINS);
            }
        }

        $this->_setResponseHeaders($p_mResponse);

        $sResults = $this->_parseFile($aResponse);

        $this->logger->debug(__METHOD__, [
            'response' => json_encode($aResponse, true),
            'results' => $sResults
        ]);

        $this->_logIt([__METHOD__, print_r($aResponse, true), $sResults]);
        echo $sResults;

        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }


    /**
     * Parse xml file and replace the placeholders with their value.
     *
     * @param array $p_aParams
     * @return string
     */
    private function _parseFile($p_aParams)
    {
        $xmlMethod = $p_aParams['xmlMethod'];

        foreach ($p_aParams as $key => $val) {
            unset($p_aParams[$key]);
            $p_aParams['{{' . $key . '}}'] = $val;
        }

        //remove _ from method name
        $method = substr($xmlMethod, 1);
        $sXml = file_get_contents(realpath(dirname(__FILE__)) . '/../Test/TestEgt/response/' . $method. '.xml');

        return str_replace(array_keys($p_aParams), array_values($p_aParams), $sXml);
    }


    /**
     * Return the gameLaunchToken in order to launch a demo game.
     *
     * @return string Return gameLaunchToken.
     */
    public function getPlayerAuthorization()
    {
        $a = [
            'casinoOperatorId' => $this->getLicSetting('casinoOperatorId'),
            'username' => $this->getLicSetting('freeplayuser'),
            'password' => $this->getLicSetting('freeplaypass')
            // 'messageId' => '',
            // 'eventTimestamp => ''
        ];

        $url = $this->getLicSetting('freeplayserverauth');
        $res = phive()->post($url, $a, 'application/json', '', 'egt-auth-curl', 'POST', 60);   //, $extra
        $token = json_decode($res)->gameLaunchToken;
        return $token;
    }
}
