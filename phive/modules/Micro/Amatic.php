<?php
require_once __DIR__ . '/Gp.php';

class Amatic extends Gp
{

    /**
     * Name of GP
     * @var string
     */
    protected $_m_sGpName = __CLASS__;

    /**
     * Find a bet by transaction ID or by round ID.
     * Mainly when a win comes in to check if there is a corresponding bet. If the transaction ID used for win is the same as bet set to false otherwise true.
     * Default null. Make sure that the round ID send by GP is an integer when set to true
     * @var boolean
     */
    protected $_m_bByRoundId = true;

    /**
     * Is the bonus_entries::balance updated after each FRB request with the FRW or does the GP keeps track and send the total winnings at the end of the free rounds.
     * Default: null (balance is updated per bet)
     */
    protected $_m_bFrwSendPerBet = false;

    /**
     * Update the status in bonus_entries table by the last FRW request from the GP in the _win() method. Default: true.
     * If set to false make sure that $this->_updateBonusEntriesStatus() is called from the extended class.
     * @var bool
     */
    protected $_m_bUpdateBonusEntriesStatusByWinRequest = false;

    /**
     * Insert frb into the bet table so in case a frw comes in we can check if it has a matching frb
     * @var bool
     */
    protected $_m_bConfirmFrbBet = false;

    /**
     * Check if incoming win request has a valid bet transaction.
     * Note: this is NOT for freespins but for real money play
     * @var bool
     */
    protected $_m_bConfirmBet = true;

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

    /**
     * Print to screen/terminal during debugging instead of to /tmp/xxx.txt
     * @var bool
     */
    protected $_m_bToScreen = false;

    /**
     * Bind GP method requests to the correct class method name
     * Keys are the method names received by the GP request and the values are the class method names
     * @var array
     */
    private $_m_aMapGpMethods = array(
        'getUserIdByToken' => '_init',
        'withdraw' => '_bet',
        'deposit' => '_win',
        'rollbackTransaction' => '_cancel',
        'getBalance' => '_balance',
    );

    private $_m_aErrors = array(
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
    );

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
            ->_setWalletActions();
        return $this;
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

        $oXml = $oXml->children($this->getSetting('namespace'),
            true)->Body->children($this->getSetting('service_request'), true);

        $aJson = $aAction = array();
        $sSessionId = $method = null;

        $this->_logIt([__METHOD__, 'METHOD: ' . (string)$oXml->getName()]);

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

        if (isset($oXml->token)) {
            $sSessionId = (string)$oXml->token;
        }

        if (!empty($sSessionId)) {
            // we are inside an active game where Init, Play, GetPlayerBalance only have a secureToken
            $mSessionData = $this->fromSession($sSessionId);
            // check if what we stored in the session does match up with the request coming from GP
            if ($mSessionData !== false) {
                // we get the userId and gameId from session
                $aJson['token'] = $sSessionId;
                $aJson['playerid'] = $mSessionData->userid;
                $aJson['skinid'] = $this->stripPrefix($mSessionData->gameid);
            }
        }

        // playerName in the request is set by the GP request getUserIdByToken()
        if (!isset($aJson['playerid']) && isset($oXml->playerName)) {
            $aJson['playerid'] = (string)$oXml->playerName;
        }

        if (!isset($aJson['skinid']) && isset($oXml->gameId) && !empty((string)$oXml->gameId)) {
            $aJson['skinid'] = (string)$oXml->gameId;
        }

        if (isset($oXml->gameIDNumber) && !empty((string)$oXml->gameIDNumber)) {
            $aJson['gameIDNumber'] = (string)$oXml->gameIDNumber;
        }

        if (isset($oXml->currency) && !empty((string)$oXml->currency)) {
            $aJson['currency'] = (string)$oXml->currency;
        }

        if (isset($oXml->clientType)) {
            $aJson['device'] = (($oXml->clientType == '0') ? 'desktop' : 'mobile');
        }

        if (isset($oXml->reason) && (string)$oXml->reason == 'FREESPIN_BONUS') {
            $aFrbId = explode('-', $oXml->bonusDescription);
            if (isset($aFrbId[1])) {
                $aJson['freespin'] = array(
                    'id' => $aFrbId[1]
                );
            }
        }

        // we have a single action in 1 request
        $aAction['command'] = $this->getWalletMethodByGpMethod($method);
        if (isset($oXml->transactionRef)) {
            $aParams = array();
            $aParams['transactionid'] = (string)$oXml->transactionRef;
            if (isset($oXml->amount)) {
                $aParams['amount'] = $this->convertFromToCoinage((string)$oXml->amount, self::COINAGE_CENTS,
                    self::COINAGE_CENTS);
            }
            if (isset($oXml->gameRoundRef)) {
                $aParams['roundid'] = (string)$oXml->gameRoundRef;
            }

            $aAction['parameters'] = $aParams;
        }
        $aJson['state'] = 'single';
        $aJson['action'] = $aAction;

        $this->_logIt([__METHOD__, print_r($aJson, true)]);

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
            $this->_setUserData();

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
     * Inform the GP about the amount of freespins available for a player.
     * The player can open the game anytime. The GP already 'knows' about the freespins available to the player.
     * Often if the GP keeps track itself and send the wins at the end when all FRB are finished like with Netent.
     * Overrule this function in the GPxxx class itself
     *
     * @param int $p_iUserId The user ID
     * @param string $p_sGameIds The internal gameId's pipe separated
     * @param int $p_iFrbGranted The frb given to play
     * @param string $bonus_name
     * @param array $p_aBonusEntry The entry from bonus_types table
     * @return bool true if bonus was created succesfully. false otherwise (freespins are not actived)
     */
    public function awardFRBonus($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry)
    {
        if($this->getSetting('no_out') === true) {
            $ext_id = (!empty($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->randomNumber(6));
            $this->attachPrefix($ext_id);
            return $ext_id;
        }

        $ud = ud($p_iUserId);

        $this->_logIt([__METHOD__, 'user-data:' . print_r($ud, true), 'Freespin:' . print_r($p_aBonusEntry, true)]);

        $aRequest = array();

        if (!empty($p_aBonusEntry)) {
            $b = phive('Bonuses')->getBonus($p_aBonusEntry['bonus_id']);

            $aRequest[]['callerId'] = $this->getSetting('callerId');
            $aRequest[]['callerPassword'] = $this->getSetting('frb_api_passwd');
            $aRequest[]['promoName'] = $b['bonus_name'];
            $aRequest[]['promoDescription'] = $ud['id'] . '-' . $p_aBonusEntry['id'];
            //$aRequest[]['promoCode'] = '';
            $aRequest[]['startTime'] = (strtotime("{$p_aBonusEntry['start_time']} 00:00:01") * 1000);
            $aRequest[]['endTime'] = (strtotime("{$p_aBonusEntry['end_time']} 23:59:59") * 1000);
            $aRequest[]['daysValid'] = 0;
            $aRequest[]['numberFreespins'] = $p_iFrbGranted;
            $aRequest[]['freespinBet'] = $this->convertFromToCoinage($this->_getFreespinValue($ud['id'],
                $p_aBonusEntry['id']), self::COINAGE_CENTS, self::COINAGE_CENTS);
            $aRequest[]['gamesList'] = implode(',', explode('|', $this->stripPrefix($p_sGameIds)));
            $aRequest[]['playersList'] = $ud['id'];
            $aRequest[]['cid'] = $this->getSetting('client_id');
            $aRequest[]['curr'] = strtoupper($ud['currency']);

            // for cancel no response has to be returned
            $aParams = array(
                '{{namespace}}' => $this->getSetting('namespace'),
                '{{service}}' => $this->getSetting('service_frb'),
                '{{xmlnsurl}}' => $this->getNsUrl(),
                '{{return}}' => implode(PHP_EOL, array_map(function ($a) {
                    $key = key($a);
                    return "<{$key}>" . $a[$key] . "</{$key}>";
                }, $aRequest)),
                '{{action}}' => 'createFreespinsBonus'
            );

            $sXml = file_get_contents(realpath(dirname(__FILE__)) . '/../Test/Test' . $this->_m_sGpName . '/request/frb.xml');
            $sRequest = str_replace(array_keys($aParams), array_values($aParams), $sXml);
            $this->_logIt([__METHOD__, print_r($aRequest, true), $sRequest]);

            $result = phive()->post($this->getSetting('frb_api_url'), $sRequest, 'text/xml', '',
                $this->getGamePrefix() . 'out', 'POST');

            // log response for debugging
            $this->_logIt([__METHOD__, $result]);

            // Loads the SOAP XML
            $oXml = simplexml_load_string($result);

            if (!($oXml instanceof SimpleXMLElement)) {
                // method to execute not found
                return false;
            }

            $oXml = $oXml->children($this->getSetting('namespace'),true)->Body->children($this->getSetting('service_frb'), true);

            $method = null;

            $this->_logIt([__METHOD__, 'METHOD: ' . (string)$oXml->getName()]);

            // Define which service/method is requested/to use
            if ((string)$oXml->getName() === 'createFreespinsBonusResponse') {
                $method = (string)$oXml->getName();
                $oXml = $oXml->$method->children();
                //$submethod = $oXml->getName();

                if (isset($oXml->programId)) {
                    $this->attachPrefix($oXml->programId);
                    return $oXml->programId;
                }
            }
        }
        return false;
    }

    /**
     * Do a player win and get the player balance after it
     * Overrules the win in gp so we can support/insert the freespin win received after last freespin is played
     * Amatic doesn't send frw one by one but only one final frw call
     * @param stdClass $p_oParameters A json object with the parameters received from gaming provider
     * @return bool|array true on success or array with error details
     */
    protected function _win(stdClass $p_oParameters)
    {

        if ($this->_isFreespin()) {
            $this->_m_bFrwSendPerBet = true;
        }

        // process normal wins and frw
        $result = parent::_win($p_oParameters);

        // if win inserted and if it was the final frw
        if ($result === true && $this->_isFreespin()) {
            $this->_m_bFrwSendPerBet = false;
            return $this->_handleFspinWin($p_oParameters->amount);
        }

        return $result;
    }

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $p_mGameId The game_ext_name from the microgames table without the prefix so basically the game ID as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @return string The url to open the game
     */
    protected function _getNewUrl($p_mGameId, $p_sLang = '', $p_sTarget = '')
    {

        $aUrl = array();

        $aUrl['game'] = $p_mGameId;
        $aUrl['hash'] = '';
        $aUrl['wallet'] = $this->getSetting('w_param');
        $aUrl['currency'] = 'EUR';
        $aUrl['config'] = $this->getSetting('c_param');
        $aUrl['isFreeplay'] = 'true';
        $aUrl['language'] = phive('Localizer')->getLocale($p_sLang);
        $aUrl['exit'] = urlencode($this->getSetting('domain'));
        $aUrl['type'] = $p_sTarget;

        if (isLogged()) {
            $ud = cuPl()->data;
            $iUserId = $ud['id'];
            $sSessionToken = $this->getGuidv4($iUserId);
            $aUrl['hash'] = $sSessionToken;
            $aUrl['currency'] = strtoupper($ud['currency']);
            $aUrl['isFreeplay'] = 'false';
            $aUrl['language'] = $ud['preferred_lang'];

            if ($p_sTarget === 'mobile') {
                $countryCode = (in_array($ud['country'], array('GB')) ? 'gb' : '');
                if ($countryCode == 'gb') {
                    $aRc = $this->_getRc();
                    $aUrl['sessionTime'] = 'true';
                    $aUrl['realityCheckURL'] = $aRc['history_link'];
                    $aUrl['realityCheckTime'] = ($aRc['reality_check_interval'] / 60);
                }
            }

            $this->toSession($sSessionToken, $iUserId, $p_mGameId, $p_sTarget);
        }

        $aUrl['language'] = strtolower(substr($aUrl['language'], 0, 2));

        return $this->getSetting('flash_play' . (($p_sTarget == 'mobile') ? '_mobile' : '')) . '?' . http_build_query($aUrl);

    }

    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {

        if($this->getSetting('use_new_launchurl') === true){
            return $this->_getNewUrl($p_mGameId, $p_sLang, $p_sTarget);
        }

        $aUrl = array();

        $aUrl['exit'] = urlencode($this->getSetting('domain'));
        $aUrl['lang'] = phive('Localizer')->getLocale($p_sLang);
        $aUrl['hash'] = 'freeplay';

        if (isLogged()) {
            $ud = cuPl()->data;
            $iUserId = $ud['id'];
            $sSessionToken = $this->getGuidv4($iUserId);
            $aUrl['w'] = $this->getSetting('w_param');
            $aUrl['lang'] = $ud['preferred_lang'];
            $aUrl['curr'] = strtoupper($ud['currency']);
            $aUrl['hash'] = $sSessionToken;
            $this->toSession($sSessionToken, $iUserId, $p_mGameId);
        }

        $aUrl['lang'] = strtolower(substr($aUrl['lang'], 0, 2));

        return sprintf($this->getSetting('flash_play' . (($p_sTarget == 'mobile') ? '_mobile' : '')), $p_mGameId) . '?' . http_build_query($aUrl);

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

        return $this->getSetting('xmlnsurl-' . $ns);
    }

    /**
     * Send a response to GP
     *
     * @param mixed $p_mResponse true if command was executed succesfully or an array with the error details
     * @return void
     */
    protected function _response($p_mResponse)
    {

        $aResponse = array();

        if ($p_mResponse !== true) {
            $aResponse[]['errorCode'] = str_replace('ER', '', $p_mResponse['code']);
            $aResponse[]['message'] = $p_mResponse['message'];
        } else {

            $aUserData = $this->_getUserData();

            switch ($this->_getMethod()) {
                case '_init':
                    $aFreespins = $this->getBonusEntryByGameId();

                    $aResponse[]['uid'] = $aUserData['id'];
                    $aResponse[]['cid'] = $this->getSetting('client_id');
                    if (!empty($aFreespins['id'])) {
                        // this will trigger the freespins on gamelaunch
                        $aResponse[]['promocode'] = $aUserData['id'] . '-' . $aFreespins['id'];
                    }
                    break;

                case '_bet':
                case '_win':
                    $aResponse[]['balance'] = $this->convertFromToCoinage($this->_getBalance(), Gp::COINAGE_CENTS,
                        Gp::COINAGE_CENTS);
                    $aResponse[]['transactionId'] = $this->_getTransaction('txn');
                    $aResponse[]['gameIDNumber'] = $this->_m_oRequest->gameIDNumber;
                    break;

                case '_balance':
                    $aResponse[]['balance'] = $this->convertFromToCoinage($this->_getBalance(), Gp::COINAGE_CENTS,
                        Gp::COINAGE_CENTS);
                    break;

                case '_end':
                case '_cancel':
                    break;
            }
        }

        if (!in_array($this->_getMethod(), array('_cancel'))) {
            // for cancel no response has to be returned
            $aParams = array(
                '{{namespace}}' => $this->getSetting('namespace'),
                '{{service}}' => $this->getSetting('service_response'),
                '{{xmlnsurl}}' => $this->getNsUrl(),
                '{{return}}' => implode(PHP_EOL, array_map(function ($a) {
                    $key = key($a);
                    return "<{$key}>" . $a[$key] . "</{$key}>";
                }, $aResponse)),
                '{{action}}' => $this->getGpMethod() . (($p_mResponse !== true) ? 'Fault' : 'Response')
            );

            $sXml = file_get_contents(realpath(dirname(__FILE__)) . '/../Test/Test' . $this->_m_sGpName . '/response/' . (($p_mResponse !== true) ? 'fault' : 'response') . '.xml');
            $this->_setResponseHeaders($p_mResponse);
            $response = str_replace(array_keys($aParams), array_values($aParams), $sXml);
            $this->_logIt([__METHOD__, $response]);
            echo $response;
            $this->_setEnd(microtime(true))->_logExecutionTime();
            die();

        }
    }
}

class AmaticWsdl
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
