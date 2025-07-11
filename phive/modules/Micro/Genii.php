<?php

require_once __DIR__ . '/Gp.php';

class Genii extends Gp
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
    protected $_m_bForceHttpOkResponse = true;
    
    private $_m_aMapGpMethods = array(
        'getaccount' => '_init',
        'getbalance' => '_balance',
        'wager' => '_bet', // will be forwarded to bet and win
        'result' => '_win',
        'cancelwager' => '_cancel'
    );
    
    /**
     * Array with errors to overrule the default ones in Gp class
     * @var array
     */
    private $_m_aErrors = array(
        'ER01' => array(
            'responsecode' => 500,
            'code' => 1,
            'status' => 'SERVER_ERROR',
            'message' => 'Internal Server Error.'
        ),
        'ER02' => array(
            'responsecode' => 405,
            'code' => 1,
            'status' => 'COMMAND_NOT_FOUND',
            'message' => 'Command not found.'
        ),
        'ER03' => array(
            'responsecode' => 401,
            'code' => 1,
            'status' => 'UNAUTHORIZED',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER06' => array(
            'responsecode' => 200,
            'code' => 1006,
            'status' => 'INSUFFICIENT_FUNDS',
            'message' => 'Out Of Money'
        ),
        'ER08' => array(
            'responsecode' => 404,
            'code' => 102,
            'status' => 'TRANSACTION_NOT_FOUND',
            'message' => 'Wager Not Found'
        ),
        'ER09' => array(
            'responsecode' => 404,
            'code' => 1003,
            'status' => 'PLAYER_NOT_FOUND',
            'message' => 'Authentication Failure'
        ),
        'ER10' => array(
            'responsecode' => 404,
            'code' => 3,
            'status' => 'GAME_NOT_FOUND',
            'message' => 'Config Error'
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
    
    public function preProcess()
    {
        
        $aJson = $aAction = array();
        $method = $key = null;
        
        $this->setDefaults();
        
        $params = $_REQUEST;
        
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
        foreach ($aMethods as $key => $value) {
            if ($params['request'] == $key) {
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
        
        // check if session does exist if so get the data from the sesssion
        if (!empty($params['sessionid'])) {
            $mSessionData = $this->fromSession($params['sessionid']);
            $aJson['playerid'] = $mSessionData->userid;
            $aJson['skinid'] = $mSessionData->gameid;
            $aJson['device'] = $mSessionData->device;
        }
        
        if (!empty($params['callerauth'])) {
            $aJson['callerauth'] = $params['callerauth'];
        }
        
        if (!empty($params['callerpassword'])) {
            $aJson['callerpassword'] = $params['callerpassword'];
        }
        
        if (!isset($aJson['playerid']) && !empty($params['accountid']) && $method !== 'wager') {
            $aJson['playerid'] = $params['accountid'];
        }
        
        if (!isset($aJson['skinid']) && !empty($params['gameid'])) {
            $aJson['skinid'] = $params['gameid'];
        }
        
        if (!isset($aJson['device']) && !empty($params['gameclienttype'])) {
            $aJson['device'] = (($params['gameclienttype'] == 'Flash') ? 'desktop' : 'mobile');
        }
        
        switch ($method) {
            case 'wager':
                $k = 'betamount';
                break;
            case 'result':
                $k = 'result';
                break;
            case 'cancelwager':
                $k = 'cancelwageramount';
                break;
        }
        
        // single transaction to process
        $aJson['state'] = 'single';
        
        $aAction[0]['command'] = $this->getWalletMethodByGpMethod($method);
        
        if (!empty($k)) {
            $aAction[0]['parameters'] = array(
                'amount' => $this->convertFromToCoinage($params[$k], self::COINAGE_UNITS, self::COINAGE_CENTS),
                'transactionid' => $params['transactionid'] . '-' . $aJson['callerauth'],
                'roundid' => $params['roundid'],
            );
        }
        
        $aJson['action'] = $aAction[0];
        
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
        
        if (in_array($this->_m_oRequest->callerauth,
                $this->getSetting('callerauth')) && sha1($this->_m_oRequest->callerauth . $this->_m_oRequest->callerpassword) == sha1($this->_m_oRequest->callerauth . $this->getSetting('callerpassword'))) {
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
    
    protected function _response($p_mResponse)
    {
        
        $aUserData = $this->_getUserData();
        
        $this->_setResponseHeaders($p_mResponse);
        
        if ($this->_getMethod() !== '_init') {
            $sXml = '<DETAILS><REALBALANCE>' . $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS,
                    self::COINAGE_UNITS) . '</REALBALANCE><BONUSBALANCE>0.00</BONUSBALANCE></DETAILS>';
        } else {
            $sXml = '<ACCOUNTID>' . $aUserData['id'] . '</ACCOUNTID><CURRENCY>' . $aUserData['currency'] . '</CURRENCY><COUNTRY>' . $aUserData['country'] . '</COUNTRY><DETAILS><FIRSTNAME>' . $aUserData['firstname'] . '</FIRSTNAME></DETAILS>';
            
            $aRc = $this->_getRc();
            if (!empty($aRc) && $this->_m_oRequest->device == 'mobile') {
                $sXml .= '<REALITYCHECKPERIODMINUTES>' . ($aRc['reality_check_interval'] / 60) . '</REALITYCHECKPERIODMINUTES><GAMEPLAYSUMMARYURL>' . $aRc['history_link'] . '</<GAMEPLAYSUMMARYURL>';
            }
        }
        
        echo '<?xml version="1.0" encoding="UTF-8" ?><RSP request="' . strtolower($this->getGpMethod()) . '" rc="' . (($p_mResponse === true) ? '0' : (isset($p_mResponse['code']) ? $p_mResponse['code'] : $p_mResponse['responsecode'])) . '"' . (($p_mResponse !== true) ? ' msg="' . $p_mResponse['message'] . '"' : '') . '>' . $sXml . '</RSP>';
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
        $aUrl = array();
        $aUrl['GameId'] = $p_mGameId;
        $aUrl['ReturnUrl'] = $this->wrapUrlInJsForRedirect($this->getLobbyUrl(false, $p_sLang, $p_sTarget));
        $bIsUk = false;
        if (isLogged()) {
            $ud = cuPl()->data;
            $iUserId = $ud['id'];
            $sSecureToken = $this->getGuidv4($iUserId);
            $aUrl['Locale'] = phive('Localizer')->getCountryValue('langtag', $ud['preferred_lang']);
            $aUrl['SessionId'] = $sSecureToken;
            $bIsUk = (($ud['country'] == 'GB') ? true : false);
            switch ($p_sTarget) {
                case 'mobile':
                    $aRc = $this->_getRc();
                    if (!empty($aRc)) {
                        $aUrl['RealityCheckPeriodMinutes'] = ($aRc['reality_check_interval'] / 60);
                    }
                    break;
                default:
                    // own solution
                    break;
            }
            $this->toSession($sSecureToken, $iUserId, $p_mGameId, $p_sTarget);
        } else {
            $aUrl['Locale'] = phive('Localizer')->getLocale($p_sLang, 'langtag');
        }
        
        if ($aUrl['Locale'] == 'en-GB') {
            $aUrl['Locale'] = 'en-US';
        }
        
        $aUrl['Locale'] = str_replace('_', '-', $aUrl['Locale']);
        return $this->getSetting('flash_play' . (($p_sTarget == 'mobile') ? '_mobile' : '') . (($bIsUk === true) ? '_uk' : '')) . ((isLogged()) ? '' : '/Demo') . '?' . http_build_query($aUrl);
    }
    
    public function importNewGames(
        $p_bUseDefaultBkg = true,
        $p_iActive = 1,
        $p_bImport = false,
        array $p_aIds = array()
    ) {
    }
}
