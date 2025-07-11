<?php

require_once __DIR__ . '/Gp.php';

class Endorphina extends Gp
{
    
    /**
     * Name of GP. Used to prefix the bets|wins::game_ref (which is the game ID) and bets|wins::mg_id (which is transaction ID)
     * @var string
     */
    protected $_m_sGpName = __CLASS__;
    
    /**
     * The header content type for the response to the GP
     * @var string
     */
    protected $_m_sHttpContentType = Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON;
    
    /**
     * Do we force respond with a 200 OK response to the GP
     * @var bool
     */
    protected $_m_bForceHttpOkResponse = false;

    /**
     * Check if incoming win request has a valid bet transaction.
     * Note: this is NOT for freespins but for real money play
     * @var bool
     */
    protected $_m_bConfirmBet = true;

    /**
     * Bind GP method requests to the correct class method name
     * Keys are the method names received by the GP request and the values are the class method names
     * @var array
     */
    private $_m_aMapGpMethods = [
        'session' => '_init',
        'bet' => '_bet',
        'win' => '_win',
        'refund' => '_cancel',
        'balance' => '_balance',
        'endSession' => '_end'
    ];
    
    private $_m_aParams = [
        'skinid' => 'game',
        'playerid' => 'player',
        'transactionid' => 'id',
        'bet_transactionid' => 'betTransactionId',
        'amount' => 'amount',
        'jpc' => null,
        'jpw' => 'progressive'
    ];
    
    private $_m_aErrors = [
        'ER01' => [
            'responsecode' => 500,  // used to send header to GP if not enforced 200 OK
            'status' => 'SERVER_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '500', // change this to whatever the GP likes to receive as code
            'message' => 'Internal Server Error.' // change this to whatever the GP likes to receive as message
        ],
        'ER02' => [
            'responsecode' => 405,
            'status' => 'COMMAND_NOT_FOUND',
            'return' => 'default',
            'code' => '500',
            'message' => 'Command not found.'
        ],
        'ER03' => [
            'responsecode' => 401,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => '401',
            'message' => 'The authentication credentials are incorrect.'
        ],
        'ER04' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_WAS_CANCELLED',
            'return' => 'default',
            'code' => '200',
            'message' => 'Bet transaction ID has been cancelled previously.'
        ],
        'ER05' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => true,
            'code' => '200',
            'message' => 'Duplicate Transaction ID.'
        ],
        'ER06' => [
            'responsecode' => 402,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => '402',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ],
        'ER07' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_MISMATCH',
            'return' => 'default',
            'code' => '200',
            'message' => 'Transaction details do not match.'
        ],
        'ER08' => [
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => true,
            'code' => '200',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ],
        'ER09' => [
            'responsecode' => 404,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => '500',
            'message' => 'Player not found.'
        ],
        'ER10' => [
            'responsecode' => 404,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => '500',
            'message' => 'Game is not found.'
        ],
        'ER11' => [
            'responsecode' => 404,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => '404',
            'message' => 'Token not found.'
        ],
        'ER15' => [
            'responsecode' => 403,
            'status' => 'FORBIDDEN',
            'return' => 'default',
            'code' => '403',
            'message' => 'IP Address forbidden.'
        ],
        'ER19' => [
            'responsecode' => 500,
            'status' => 'STAKE_TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => '500',
            'message' => 'Stake transaction not found.'
        ],
    ];
    
    /**
     * The transaction ID which is processed
     * @var int
     */
    private $_m_iTransactionId;

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
        
        if (!empty($_GET)) {
            $vars = [];
            foreach ($_GET as $key => $value) {
                $vars[$key] = urldecode($value);
            }
            $this->_setGpParams(json_encode($vars));
        } else {
            $this->_setGpParams(json_encode($_POST));
        }
        
        // Get the requested command to be executed
        $request = parse_url($_SERVER['REQUEST_URI']);
        
        $iLastSlash = (strrpos($request["path"], '/') + 1);
        $this->_setGpMethod(substr($request["path"], $iLastSlash));
        $this->_logIt([__METHOD__, print_r($request, true), substr($request["path"], $iLastSlash)]);
        
        return $this;
    }
    
    /**
     * Execute the requested command from gaming provider
     * @return void
     */
    public function exec()
    {
        $mResponse = false;
        $sHash = null;
        // We got post params in the form of json string
        $aRequest = json_decode($this->getGpParams(), true);
        
        $this->_logIt([__METHOD__, print_r($aRequest, true)]);
        
        // we need the values but without the sign
        if (isset($aRequest['sign'])) {
            $sHash = $aRequest['sign'];
            unset($aRequest['sign']);
        }
        
        $sCalculatedHash = $this->getHash($aRequest, self::ENCRYPTION_SHA1, []);
        ksort($aRequest);
        
        $this->_logIt([__METHOD__, 'calc-hash: ' . $sCalculatedHash, 'hash: ' . $sHash, print_r($aRequest, true)]);

        if ($sCalculatedHash === $sHash) {
            
            $mSessionData = $this->fromSession(strtolower($aRequest['token']));
            unset($aRequest['token']);
            $sMethod = $this->getWalletMethodByGpMethod($this->getGpMethod());
            $aJson = $aAction = [];
            
            if ($sMethod === '_init' && $mSessionData === false) {
                // token not found
                $mResponse = $this->_getError(self::ER11);
            }
            
            $aJson['state'] = 'single';
            $aAction['command'] = $sMethod;
            
            if ($mSessionData !== false) {
                $aJson['playerid'] = $mSessionData->userid;
                $aJson['skinid'] = $mSessionData->gameid;
                $aJson['device'] = $this->string2DeviceTypeNum($mSessionData->device);
            }
            
            $oRequest = $this->_matchParams($aRequest);
            
            
            if (!isset($aJson['playerid'])) {
                $aJson['playerid'] = $oRequest->playerid;
            }
            
            if (!isset($aJson['skinid'])) {
                $aJson['skinid'] = $oRequest->skinid;
            }
            
            unset($oRequest->playerid, $oRequest->skinid);
            $this->_logIt([__METHOD__, print_r($oRequest, true)]);
            if (!empty($aRequest)) {
                $aAction['parameters'] = $oRequest;
            }
            
            $aJson['action'] = json_decode(json_encode($aAction), false);
            
            $this->_m_oRequest = json_decode(json_encode($aJson), false);
            
            // check if the commands requested do exist
            $this->_setActions();
            
            // Set the game data by the received skinid (is gameid, see document history page III)
            if (!in_array($sMethod, ['_init', '_cancel'])) {
                $this->_setGameData(false);
            }
            
            // execute all commands
            foreach ($this->_getActions() as $key => $oAction) {
                // Update the user data befor each command
                if (isset($this->_m_oRequest->playerid)) {
                    $this->uid = $this->_m_oRequest->playerid;
                    $this->_setUserData();
                }
                
                $sMethod = $oAction->command;
                $this->_setWalletMethod($sMethod);
                
                // command call return either an array with errors or true on success
                if (property_exists($oAction, 'parameters')) {
                    if (isset($oAction->parameters->transactionid)) {
                        $this->_m_iTransactionId = $oAction->parameters->transactionid;
                    }
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
        $this->_response($mResponse);
    }
    
    /**
     * Get a prefix which is the lowercase class name which can be used for different purposes
     *
     * @return string
     */
    public function getGamePrefix()
    {
        return strtolower(preg_replace("/\W|_/", "", $this->_m_sGpName)) . '_';
    }

    /**
     * Adds the game prefix to be able to retrieve it later from database on game requests
     * endorphina2_ is a special case where we don't need to add it
     *
     * @param $game_id
     * @return mixed|string
     */
    public function getGameWithPrefix($game_id)
    {
        if ( strpos($game_id, 'endorphina2_', 0) === false ) {
            $game_id = $this->getGamePrefix() . $game_id;
        }

        return $game_id;
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
        $this->initCommonSettingsForUrl();

        $user = cu();

        $p_mGameId = $this->getGameWithPrefix($p_mGameId);

        if(!empty($user)) {
            $uid = empty($_SESSION['token_uid']) ? $user->getId() : $_SESSION['token_uid'];
            $token =  $this->getGuidv4();
            $this->toSession($token, $uid, $p_mGameId, $p_sTarget);
        }
        $exit_url = $this->getLobbyUrlForGameIframe(false, $p_sLang, $p_sTarget);

        if(isLogged()) {
            $node_id = $this->getLicSetting('node_id', $uid);
            $profile = $this->getLicSetting('profile', $uid);
            $hash   = $this->getHash($exit_url . $node_id . $profile . $token,
                self::ENCRYPTION_SHA1,
                []
            );

            $url_params = [
                'exit'      => $exit_url,
                'nodeId'    => $node_id,
                'profile'   => $profile,
                'token'     => $token,
                'sign'      => $hash
            ];

            $this->_logIt([__METHOD__, print_r($url_params, true)]);
            $launch_url = $this->getLaunchUrl($url_params);
            $this->_logIt([__METHOD__, print_r($url_params, true), $launch_url, $p_mGameId]);

        } else {
            $debug_key = $this->getSetting('log_errors') ? $this->getGpName() . 'demo-call' : '';
            $e_demo_endpoint = $this->launch_url . urlencode($exit_url);

            $demo_links_json = phive()->get($e_demo_endpoint, '', '', $debug_key);
            $demo_links = json_decode($demo_links_json, 1);
            $endorphina_game_key = $this->_stripPostfix($p_mGameId);

            $launch_url = $demo_links['ENDORPHINA'][$endorphina_game_key] ?? '';
        }

        return $launch_url;
    }
    
    /**
     * Re-match the GP key-value pairs with the internal ones
     *
     * @param array $aRequest The GP params to be processed
     * @return array
     */
    private function _matchParams($aRequest)
    {
        $a = [];
        
        foreach ($this->_m_aParams as $sInternalKey => $sGpKey) {
            if (isset($aRequest[$sGpKey])) {
                if ($sInternalKey == 'amount') {
                    $sValue = $this->convertFromToCoinage($aRequest[$sGpKey], self::COINAGE_MILLES,
                        self::COINAGE_CENTS);
                } else {
                    $sValue = $aRequest[$sGpKey];
                }
                $a[$sInternalKey] = $sValue;
            }
        }
        return json_decode(json_encode($a), false);
    }
    
    /**
     * Collect the correct response params for each request depending on the requested method
     * @return array
     */
    private function _getResponse()
    {
        $aResponse = [];
        
        if ($this->_getMethod() === '_init') {
            $aUserData = $this->_getUserData();
            // the only command requested with extra response params
            $aResponse['player'] = $aUserData['id'];
            $aResponse['currency'] = $aUserData['currency'];
            $aResponse['game'] = $this->_m_oRequest->skinid;
        } else if ($this->_getMethod() === '_end') {
            $aResponse = []; // they want an empty body
        } else {
            if ($this->_getMethod() !== '_balance') {
                $aResponse['transactionId'] = (empty($this->_getTransaction('txn')) ? '' : $this->_getTransaction('txn'));
                $this->_m_iTransactionId = null;
            }
            
            $aResponse['balance'] = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS,
                self::COINAGE_MILLES);
            
        }
        
        return $aResponse;
    }
    
    /**
     * Send a response
     *
     * @param mixed $p_mResponse true if command was executed succesfully or an array with the error details
     * @return void
     */
    protected function _response($p_mResponse)
    {
        
        $aResponse = [];
        
        if ($p_mResponse !== true) {
            
            // fail headers
            $this->_setResponseHeaders($p_mResponse);
            $aResponse['code'] = $this->_m_aHttpStatusCodes[$p_mResponse['code']];
            $aResponse['message'] = $p_mResponse['message'];

        } else {
            
            // success headers
            $this->_setResponseHeaders();
            
            // refresh with the latest user data
            if (isset($this->_m_oRequest->playerid)) {
                $this->_setUserData();
            }
            
            $aResponse = $this->_getResponse();
            
        }
        $result = json_encode($aResponse);
        $this->_logIt([__METHOD__, $result]);
        echo $result;
        
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }

    /**
     * Override of parent function gets the hash of a string (salt is added automatically)
     *
     * @param string $p_mValue Value to get the hash from.
     * @param string $p_sEncryption Which encryption method to use
     * @param array $p_aOptions Additional options needed
     * @return string
     */
    public function getHash($p_mValue, $p_sEncryption = self::ENCRYPTION_SHA1, array $p_aOptions = array())
    {

        if (is_array($p_mValue)) {
            // We need to sort the array by keys before create the hash of it
            ksort($p_mValue);
            $p_mValue = implode('', $p_mValue);
        }

        $secret_key = $this->getLicSetting('secret_key');

        switch ($p_sEncryption) {

            case self::ENCRYPTION_SHA1:
                $this->_logIt([__METHOD__, 'SHA1 encrypted']);
                $hash_value = sha1($p_mValue . $secret_key);
                break;

            case self::ENCRYPTION_MD5:
                $this->_logIt([__METHOD__, 'MD5 encrypted']);
                $hash_value = md5($p_mValue . $secret_key);
                break;

            case self::ENCRYPTION_HMAC:
                $this->_logIt([__METHOD__, 'HMAC encrypted']);
                // The algorithm to be used. Check PHP manual for available algorithms.
                $hash_value = hash_hmac((isset($p_aOptions['algorithm']) ? $p_aOptions['algorithm'] : 'sha256'), $p_mValue,
                    $secret_key);
                break;
            default:
                $hash_value = $p_mValue;
                break;
        }

        return $hash_value;
    }

    /**
     * Overrides the base method in Gp.php to check for a matching bet.
     *
     * @param stdClass $parameters
     * @param bool $returnResult
     * @return array|bool
     */
    protected function _hasBet(stdClass $parameters, $returnResult = false)
    {
        if ($this->_m_bSkipBetCheck === true) {
            return true;
        }

        $tag = strtolower(__CLASS__) . '-has-bet';
        if ($this->doConfirmByRoundId()) {
            $ret = parent::_hasBet($parameters, $returnResult);
            $this->dumpTst($tag, ['has-bet' => !empty($ret), 'params' => $parameters]);
            return $ret;
        }

        $ud = $this->_getUserData();
        if (empty($parameters->bet_transactionid) || empty($ud['id'])) {
            $this->dumpTst($tag, ['has-bet' => false, 'params' => $parameters]);
            return false;
        }
        
        $table = $this->isTournamentMode() ? 'bets_mp' : 'bets';
        $bet_transaction_id = $parameters->bet_transactionid;
        $this->attachPrefix($bet_transaction_id);
        $bet = $this->getBetByMgId($bet_transaction_id, $table, 'mg_id', $ud['id']);
        $ret = empty($bet) ? false : ($returnResult ? $bet : true);

        $this->dumpTst($tag, ['has-bet' => !empty($ret), 'params' => $parameters]);
        return $ret;
    }
}
