<?php
require_once __DIR__ . '/Gp.php';

class Edict extends Gp
{
    
    /**
     * Name of GP
     * @var string
     */
    protected $_m_sGpName = __CLASS__;
    
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
     * Bind GP method requests to the correct class method name
     * Keys are the method names received by the GP request and the values are the class method names
     * @var array
     */
    private $_m_aMapGpMethods = array(
        'authorizePlayer' => '_init',
        'authorizeAnonymous' => '_init',
        'withdraw' => '_bet',
        'deposit' => '_win',
        'rollbackTransaction' => '_cancel',
        'getBalance' => '_balance',
        'markGameSessionClosed' => '_end',
        'getPlayerCurrency' => '_currency'
    );
    
    private $_m_aErrors = array(
        'ER03' => array(
            'responsecode' => 401,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => '7',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER06' => array(
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => '1',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        'ER08' => array(
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => true,
            'code' => 'ER08',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ),
        'ER18' => array(
            'responsecode' => 200,
            'status' => 'IDEMPOTENCE',
            'return' => 'default',
            'code' => 'ER18',
            'message' => 'Duplicate Transaction ID with same amount does exist already.'
        ),
        'ER25' => array(
            'responsecode' => 200,
            'status' => 'PLAYER_BLOCKED',
            'return' => 'default',
            'code' => '8',
            'message' => 'Player is blocked.'
        ),
        'ER26' => array(
            'responsecode' => 200,
            'status' => 'PLAYER_BANNED',
            'return' => 'default',
            'code' => '8',
            'message' => 'Player is banned.'
        ),
        'ER27' => array(
            'responsecode' => 200,
            'status' => 'INVALID_USER_ID',
            'return' => 'default',
            'code' => '8',
            'message' => 'Session player ID doesn\'t match request Player ID.'
        ),
        'ER19' => array(
            'responsecode' => 200,
            'status' => 'STAKE_TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => '3',
            'message' => 'Stake transaction not found.'
        ),
    );

    protected string $logger_name = 'edict';
    
    /**
     * Set the defaults
     * Seperate function so it can be called also from the classes that extend TestGp class
     * @return Gp
     */
    public function setDefaults()
    {
        $this
            ->_mapGpMethods($this->_m_aMapGpMethods)
            ->_whiteListGpIps($this->getLicSetting('whitelisted_ips'))
            ->_overruleErrors($this->_m_aErrors)
            ->_setWalletActions();
        return $this;
    }

    /**
     * Set the return function to true if we are going to use the rounds table
     * @return bool true or false
     */
    public function doConfirmByRoundId()
    {
        return true;
    }
    
    /**
     * Pre process data received from GP
     * @return object
     */
    public function preProcess()
    {
        
        $this->setDefaults();
        
        $sRequest = $this->_m_sInputStream;
        $this->_setGpParams($sRequest);
        
        if (isset($_GET['wsdl']) && in_array($_GET['wsdl'], array('auth', 'end', 'wallet')) && empty($sRequest)) {
            $wsdl = file_get_contents(realpath(dirname(__FILE__)) . '/../../../diamondbet/soap/edict-' . $_GET['wsdl'] . '.wsdl');
            echo str_replace(array('{{action}}', '{{environment}}'), array($_GET['wsdl'], $this->getLicSetting('domain')),
                $wsdl);
            $this->logger->info(__METHOD__, ['EDICT WSDL REQUEST']);
            $this->_logIt([__METHOD__, 'EDICT WSDL REQUEST']);
            die();
        } else {
            $this->logger->debug(__METHOD__, ['EDICT ACTION REQUEST' => $sRequest]);
            $this->_logIt([__METHOD__, 'EDICT ACTION REQUEST', $sRequest]);
        }
        
        // Loads the SOAP XML
        $oXml = simplexml_load_string($sRequest);
        
        if (!($oXml instanceof SimpleXMLElement)) {
            // method to execute not found
            $this
                ->_setHttpContentType(Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML)
                ->_forceHttpOkResponse(false)
                ->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }
        
        $oXml = $oXml->children($this->getLicSetting('namespace'), true)->Body->children($this->getLicSetting('service'),
            true);
        
        $aJson = $aAction = array();
        $sSessionId = $method = null;

        $this->logger->info(__METHOD__, ['EDICT METHOD' => $oXml->getName()]);
        
        $this->_logIt([__METHOD__, 'EDICT METHOD: ' . (string)$oXml->getName()]);
        
        // Define which service/method is requested/to use
        if (array_key_exists((string)$oXml->getName(), $this->_getMappedGpMethodsToWalletMethods())) {
            $method = (string)$oXml->getName();
            $this->_setGpMethod($method);
        } else {
            // method to execute not found
            $this
                ->_setHttpContentType(Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML)
                ->_forceHttpOkResponse(false)
                ->_setResponseHeaders($this->_getError(self::ER02));
            die();
        }
        
        $oXml = $oXml->$method->children();
        $submethod = $oXml->getName();
        
        if ($this->getLicSetting('subtag') === true) {
            $oXml = $oXml->$submethod;
        }
        
        if (isset($oXml->sessionId)) {
            $sSessionId = (string)$oXml->sessionId;
        } elseif (isset($oXml->sessionToken)) {
            $sSessionId = (string)$oXml->sessionToken;
        }
        
        if (!empty($sSessionId)) {
            // we are inside an active game where Init, Play, GetPlayerBalance only have a secureToken
            $mSessionData = $this->fromSession($sSessionId);
            // check if what we stored in the session does match up with the request coming from GP
            if ($mSessionData !== false) {
                // we get the userId and gameId from session
                $aJson['sessionId'] = $this->_m_sSecureToken = $sSessionId;
                $aJson['playerid'] = $mSessionData->userid;
                $aJson['skinid'] = $this->stripPrefix($mSessionData->gameid);
                $aJson['device'] = $mSessionData->device ?? null;
            }
        }
        
        // playerid must be set trough session data for bets
        if (!isset($aJson['playerid']) && isset($oXml->playerName) && $method !== 'withdraw') {
            $aJson['playerid'] = (string)$oXml->playerName;
        }
        
        if (!isset($aJson['skinid']) && isset($oXml->gameId) && !empty((string)$oXml->gameId)) {
            $aJson['skinid'] = (string)$oXml->gameId;
        }
        
        if (isset($oXml->currency) && !empty((string)$oXml->currency)) {
            $aJson['currency'] = (string)$oXml->currency;
        }

        // set the user in order to get the transaction prefix
        $this->user = cu($aJson['playerid']);


        if (isset($oXml->gameRoundRef) && !empty((string)$oXml->gameRoundRef)) {
            $aJson['roundid'] = (string)$oXml->gameRoundRef;
        }

        // we have a single action in 1 request
        $aAction['command'] = $this->getWalletMethodByGpMethod($method);
        if (isset($oXml->transactionRef)) {
            $aParams = array();
            $aParams['transactionid'] = (string)$oXml->transactionRef;
            $aParams['roundid'] = (string)$oXml->gameRoundRef;
            if (isset($oXml->amount)) {
                $aParams['amount'] = $this->convertFromToCoinage((string)$oXml->amount, self::COINAGE_UNITS,
                    self::COINAGE_CENTS);
            }
            $aAction['parameters'] = $aParams;
        }



        $aJson['state'] = 'single';
        $aJson['action'] = $aAction;

        $this->logger->debug(__METHOD__, ['EDICT JSON' => json_encode($aJson, true)]);
        
        $this->_logIt([__METHOD__, 'EDICT JSON', print_r($aJson, true)]);
        
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
        
        if (in_array($this->getGpMethod(), array('withdraw'))) {
            // Only bet has required sessionId
            if (!isset($this->_m_oRequest->sessionId)) {
                // no match for sessionId when bet comes in
                $this->_response($this->_getError(self::ER03));
            }
        }
        
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
        $aUrl = array();
        $aUrl['gameKey'] = $p_mGameId;
        $aUrl['templateName'] = 'default';
        $aUrl['gameMode'] = 'fun';
        $aUrl['playerName'] = '';
        $aUrl['sessionToken'] = '';
        $aUrl['casino'] = $this->getLicSetting('casino');
        $aUrl['homeUrl'] = $this->getLobbyUrl(false, $p_sLang, $p_sTarget);

        if (isLogged()) {
            $ud = cuPl()->data;
            $iUserId = $ud['id'];
            $country = $ud['country'];
            $sSessionToken = $this->getGuidv4($iUserId);
            $aUrl['sessionToken'] = $sSessionToken;
            $aUrl['gameMode'] = 'money';
            $aUrl['playerName'] = $iUserId;
            $aUrl['lang'] = substr(strtolower($ud['preferred_lang']), 0, 2);

            if ($this->getRcPopup($p_sTarget, $iUserId) == 'ingame') {
                $aUrl['referrerurl'] = phive()->getSiteUrl();
                $rcInterval = phive('Casino')->startAndGetRealityInterval($iUserId, $p_mGameId);
                if (!empty($rcInterval)) {
                    $aUrl['realitySessionTime'] = $rcInterval * 60;
                    $aUrl['realityElapsedTime'] =  1;
                    $aUrl['realityCheckLinkUrl'] = $this->getHistoryUrl(false, $iUserId, $p_sLang);
                }
            }
            $this->toSession($sSessionToken, $iUserId, $p_mGameId, $p_sTarget);
        } else {
            $country = cuCountry();
            $aUrl['lang'] = cLang();
        }
        $aUrl['casino'] = $this->getLicSetting('casino');
        return $this->getLicSetting('flash_play' . (($p_sTarget == 'mobile') ? '_mobile' : '')) . '?' . http_build_query($aUrl);
    }
    
    /**
     * Import new games into database from GP provided by a XML file.
     * It will check if the game does exist and if not found it will insert it.
     *
     * @param bool $p_bUseDefaultBkg Do we use the default background on game loading or a specific background for each game. Default: true
     * @param int $p_iActive Set the game to active. Default: 1
     * @param bool $p_bImport Do we import it directly to database or do we output to browser for review first
     * @param array $p_aIds Only import games with these GP-ids
     * @return void
     */
    public function importNewGames(
        $p_bUseDefaultBkg = true,
        $p_iActive = 1,
        $p_bImport = false,
        array $p_aIds = array()
    ) {
    }
    
    public function getNsUrl()
    {
        
        switch ($this->_getMethod()) {
            case '_bet':
            case '_win':
            case '_cancel':
            case '_currency':
            case '_balance':
                $ns = 'wallet';
                break;
            
            case '_end':
                $ns = 'end';
                break;
            
            case '_init':
            default:
                $ns = 'auth';
        }
        
        return $this->getLicSetting('xmlnsurl-' . $ns);
    }
    
    /**
     * Send a response to Edict
     *
     * @param mixed $p_mResponse true if command was executed succesfully or an array with the error details
     * @return mixed The headers and depending on other response params a json_encoded string, which is the answer to edict
     */
    protected function _response($p_mResponse)
    {
        // Check for duplicate
        $log_out_error_DB = $this->checkDuplicateTransaction($p_mResponse);
        $aResponse = array();

        // Or we log errors out into trans_log table or else we send error to Edict
        if ($p_mResponse !== true && $log_out_error_DB === false) {
            $aResponse[]['errorCode'] = $p_mResponse['code'];
            $aResponse[]['message'] = $p_mResponse['message'];
        } else {
            
            $aUserData = $this->_getUserData();
            
            switch ($this->_getMethod()) {
                case '_init':
                    $aResponse[]['sessionId'] = $this->_m_sSecureToken;
                    break;
                
                case '_currency':
                    $aResponse[]['currencyIsoCode'] = $aUserData['currency'];
                    break;
                
                case '_bet':
                case '_win':
                    $aResponse[]['balance'] = $this->convertFromToCoinage($this->_getBalance(), Gp::COINAGE_CENTS,
                        Gp::COINAGE_UNITS);
                    $aResponse[]['transactionRef'] = $this->_getTransaction('txn');
                    if($log_out_error_DB) {
                        phive()->dumpTbl('edict-duplicate-transaction-entry', [json_encode($aResponse)]);
                    }
                    break;

                case '_balance':
                    $aResponse[]['balance'] = $this->convertFromToCoinage($this->_getBalance(), Gp::COINAGE_CENTS,
                        Gp::COINAGE_UNITS);
                    break;
                
                case '_end':
                case '_cancel':
                    break;
            }
        }
        
        $aParams = array(
            '{{namespace}}' => $this->getLicSetting('namespace'),
            '{{service}}' => $this->getLicSetting('service'),
            '{{xmlnsurl}}' => $this->getNsUrl(),
            '{{return}}' => implode(PHP_EOL, array_map(function ($a) {
                $key = key($a);
                return "<{$key}>" . $a[$key] . "</{$key}>";
            }, $aResponse)),
            '{{action}}' => $this->getGpMethod() . (($p_mResponse !== true) ? 'Fault' : 'Response')
        );
        
        $sXml = file_get_contents(realpath(dirname(__FILE__)) . '/../Test/Test' . $this->_m_sGpName . '/response/response.xml');
        $this->_setResponseHeaders($p_mResponse);
        $response = str_replace(array_keys($aParams), array_values($aParams), $sXml);
        
        $this->logger->debug(__METHOD__, ['RESPONSE' => json_encode($response, true)]);
        $this->_logIt([__METHOD__, $response]);
        echo $response;
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }


    /**
     * Since we still have to send true even though there is a duplicate transaction, we log it in our trans_log table
     * Sending normal bet / win request will avoid having the GP retrying the bet request
     * This was asked by Edict
     *
     * @param $p_mResponse
     * @return bool
     */
    protected function checkDuplicateTransaction($p_mResponse)
    {
        if ($p_mResponse !== true && $p_mResponse['code'] == 'ER18') {
            return true;
        }
        return false;
    }
}

class EdictWsdl
{
    
    /**
     * Method authorizes the player by its playerName and sessionToken.
     *
     * @soap
     * @param string $callerId Caller Id
     * @param string $callerPassword Caller password
     * @param int $playerName Player identification
     * @param string $sessionToken Short living token the get active session
     * @return string Soap response with the sessionID
     */
    public function authorizePlayer($callerId, $callerPassword, $playerName, $sessionToken)
    {
    }
    
    /**
     * Method authorizes an anonymous player by its sessionToken.
     *
     * @soap
     * @param string $callerId Caller Id
     * @param string $callerPassword Player identification
     * @param string $sessionToken Short living token the get active session
     * @return string Soap response with the sessionID
     */
    public function authorizeAnonymous($callerId, $callerPassword, $sessionToken)
    {
    }
    
    /**
     * Informs about the end of the players game session
     *
     * @soap
     * @param string $callerId Caller Id
     * @param string $callerPassword Player identification
     * @param string $sessionId The session Id of the players session
     * @return string Soap response with empty string
     */
    public function markGameSessionClosed($callerId, $callerPassword, $sessionId)
    {
    }
    
    /**
     * Get the currency of
     *
     * @soap
     * @param string $callerId Caller Id
     * @param string $callerPassword Player identification
     * @param int $playerName Player identification
     * @param string $sessionId The session Id of the players session
     * @return string Soap response with the currencyIsoCode
     */
    public function getPlayerCurrency($callerId, $callerPassword, $playerName, $sessionId = null)
    {
    }
    
    /**
     * Informs about the end of the players game session
     *
     * @soap
     * @param string $callerId Caller Id
     * @param string $callerPassword Player identification
     * @param int $playerName Player identification
     * @param string $currency The currency used by the player
     * @param string $gameId The game Id
     * @param string $sessionId The session Id of the players session
     * @return string Soap response with the balance
     */
    public function getBalance($callerId, $callerPassword, $playerName, $currency, $gameId, $sessionId = null)
    {
    }
    
    /**
     * Withdraws the amount from the players account
     *
     * @soap
     * @param string $callerId Caller Id
     * @param string $callerPassword Player identification
     * @param int $playerName Player identification
     * @param string $amount The amount to withdraw
     * @param string $currency The currency used by the player
     * @param string $transactionRef The transaction ID
     * @param string $gameRoundRef The game round reference
     * @param string $gameId The game Id
     * @param string $reason Indicates the reason for withdraw
     * @param string $sessionId The session Id of the players session
     * @return string Soap response with the balance & transactionID
     */
    public function withdraw(
        $callerId,
        $callerPassword,
        $playerName,
        $amount,
        $currency,
        $transactionRef,
        $gameRoundRef,
        $gameId,
        $reason,
        $sessionId = null
    ) {
    }
    
    /**
     * Deposit the amount to the players account
     *
     * @soap
     * @param string $callerId Caller Id
     * @param string $callerPassword Player identification
     * @param int $playerName Player identification
     * @param string $amount The amount to withdraw
     * @param string $currency The currency used by the player
     * @param string $transactionRef The transaction ID
     * @param string $gameRoundRef The game round reference
     * @param string $gameId The game Id
     * @param string $reason Indicates the reason for deposit
     * @param string $source Reason for clearing a game round
     * @param int $startDate The timestamp for the game round
     * @param string $sessionId The session Id of the players session
     * @return string Soap response with the balance & transactionID
     */
    public function deposit(
        $callerId,
        $callerPassword,
        $playerName,
        $amount,
        $currency,
        $transactionRef,
        $gameRoundRef,
        $gameId,
        $reason = null,
        $source = null,
        $startDate = null,
        $sessionId = null
    ) {
    }
    
    /**
     * Rollback a withdraw and or deposit by transactionID and credits the amount to the players account
     *
     * @soap
     * @param string $callerId Caller Id
     * @param string $callerPassword Player identification
     * @param int $playerName Player identification
     * @param string $transactionRef The transaction ID
     * @param string $gameId The game Id
     * @param string $sessionId The session Id of the players session
     * @return string Soap response with empty string
     */
    public function rollbackTransaction(
        $callerId,
        $callerPassword,
        $playerName,
        $transactionRef,
        $gameId,
        $sessionId = null
    ) {
    }
    
}
