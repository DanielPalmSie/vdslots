<?php

require_once __DIR__ . '/Gp.php';

class Nektan extends Gp
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
    
    private $_m_aMapGpMethods = array(
        'getBalance' => '_balance',
        'debit' => '_bet',
        'credit' => '_win',
        'creditDebit' => '_winbet',
        'cancelCredit' => '_cancel',
        'cancelDebit' => '_cancel',
        'cancelCreditDebit' => '_cancel',
        'closeGameRound' => '_end',
        'getGameDetails' => '',
        'getHistory' => '',
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
            'responsecode' => 500, // used to send header to GP if not enforced 200 OK
            'status' => 'SERVER_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '-1', // change this to whatever the GP likes to receive as code
            'message' => 'Internal Server Error.' // change this to whatever the GP likes to receive as message
        ),
        'ER02' => array(
            'responsecode' => 405,
            'status' => 'COMMAND_NOT_FOUND',
            'return' => 'default',
            'code' => '2',
            'message' => 'Command not found.'
        ),
        'ER03' => array(
            'responsecode' => 401,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => '128',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER06' => array(
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => '116',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        'ER09' => array(
            'responsecode' => 404,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => '103',
            'message' => 'Player not found.'
        ),
        'ER10' => array(
            'responsecode' => 404,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => '106',
            'message' => 'Game is not found.'
        ),
        'ER11' => array(
            'responsecode' => 498,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => '100',
            'message' => 'Token not found.'
        ),
        'ER12' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_NO_REMAINING',
            'return' => 'default',
            'code' => '124',
            'message' => 'No freespins remaining.'
        ),
        'ER13' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_INVALID',
            'return' => 'default',
            'code' => '???',
            'message' => 'Invalid freespin bet amount.'
        ),
        'ER15' => array(
            'responsecode' => 403,
            'status' => 'FORBIDDEN',
            'return' => 'default',
            'code' => '5',
            'message' => 'IP Address forbidden.'
        ),
    );
    
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
        
        $this->_setGpParams($oData);

        if ($oData === null) {
            // request is unknown
            $this->_logIt([__METHOD__, 'unknown request']);
            $this->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }

        $aJson = $aAction = array();
        $casinoMethod = null;
        
        // Define which service is requested
        $casinoMethod = $oData->method;

        if (empty($casinoMethod)) {
            // method to execute not found
            $this->_setResponseHeaders($this->_getError(self::ER02));
            $this->_logIt([__METHOD__, 'method not found']);
            die();
        } else {
            $this->_setGpMethod($casinoMethod);
        }
        
        $mSessionData = null;
        if (isset($oData->params->sessionToken)) {
            $mSessionData = $this->fromSession($oData->params->sessionToken);
        }

        if (isset($mSessionData->userid)) {
            $aJson['playerid'] = $mSessionData->userid;
            $this->_logIt([__METHOD__, 'UID by session', print_r($mSessionData, true)]);
        }  elseif(isset($oData->params->username)) {
            $aJson['playerid'] = $oData->params->username;
        } else {
            $this->_setResponseHeaders($this->_getError(self::ER09));
            $this->_logIt([__METHOD__, 'UID not found.']);
            die();
        }
        
        if (isset($mSessionData->gameid)) {
            $aJson['skinid'] = $this->stripPrefix($mSessionData->gameid);
            $this->_logIt([__METHOD__, 'GID by session', print_r($mSessionData, true)]);
        } else {
            if (isset($oData->params->gameId)) {
                $aJson['skinid'] = $oData->params->gameId;
                $this->_logIt([__METHOD__, 'GID by $oData->params->gameId', $oData->params->gameId]);
            } else {
                $aJson['skinid'] = '';
                $this->_logIt([__METHOD__, 'GID not found']);
            }
        }
    
        if (in_array($casinoMethod, array('debit','credit','cancelCredit','cancelDebit','creditDebit','cancelCreditDebit'))) {
            switch($casinoMethod){
                case 'debit':
                case 'cancelDebit':
                case 'credit':
                case 'cancelCredit':
                    $aJson['state'] = 'single';
                    $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                    $aAction[0]['parameters'] = array(
                        'amount' => $this->convertFromToCoinage($oData->params->amount, self::COINAGE_UNITS, self::COINAGE_CENTS),
                        'transactionid' => $oData->params->transactionId,
                        'roundid' => $oData->params->gameRoundId
                    );
                    $aJson['action'] = $aAction[0];
                    break;
                    
                case 'creditDebit':
                case 'cancelCreditDebit':
                    $aJson['state'] = 'multi';
                    $aAction[0]['command'] = (($casinoMethod === 'creditDebit') ? '_bet' : '_cancel' );
                    $aAction[0]['parameters'] = array(
                        'amount' => $this->convertFromToCoinage($oData->params->debitAmount, self::COINAGE_UNITS, self::COINAGE_CENTS),
                        'transactionid' => $oData->params->debitTransactionId,
                        'roundid' => $oData->params->gameRoundId
                    );
                    $aAction[1]['command'] = (($casinoMethod === 'creditDebit') ? '_win' : '_cancel' );
                    $aAction[1]['parameters'] = array(
                        'amount' => $this->convertFromToCoinage($oData->params->creditAmount, self::COINAGE_UNITS, self::COINAGE_CENTS),
                        'transactionid' => $oData->params->creditTransactionId,
                        'roundid' => $oData->params->gameRoundId
                    );
                    $aJson['actions'] = $aAction;
                    break;
            }
        } else {
            $aAction['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
            $aJson['state'] = 'single';
            $aJson['action'] = $aAction;
        }
    
        // detect for freespin
//         if (isset($oData->params->type) && $oData->params->type === 'freespin') {
//             $aJson['freespin'] = array(
//                 'id' => $oData->params->freespin_id,
//                 'num_lines' => $oData->params->freespin_lines,
//                 'stake_per_line' => $this->convertFromToCoinage($oData->params->freespin_bet, self::COINAGE_UNITS, self::COINAGE_CENTS),
//             );
//         }

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
            if (isset($this->_m_oRequest->freespin)) {
                $this->_setFreespin($this->_m_oRequest->playerid, $this->_m_oRequest->freespin->id);
                if (!$this->_isFreespin()) {
                    // the frb doesn't exist or is missing??
                    $this->_response($this->_getError(self::ER17));
                }
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
    
    protected function _response($p_mResponse)
    {
        $aResponse = $aResp = array();
        
        if ($p_mResponse === true) {
            
            $aUserData = $this->_getUserData();
            $balance = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS);
            $aResponse['balance'] = $aResponse['cashBalance'] = $balance;
            $aResponse['bonusBalance'] = 0;
            $aResponse['currency'] = strtoupper($this->getPlayCurrency($aUserData));
    
            if (in_array($this->_getMethod(),array('_bet','_win','_cancel'))) {
                if($this->_isFreespin()){
                    $aResponse['freespin_num'] = $this->_getFreespinData('frb_remaining');
                }
                
                if(in_array($this->getGpMethod(), array('creditDebit', 'cancelCreditDebit')) ){
                    $aResponse['externalDebitTransactionId'] = $this->_getTransaction('txn',self::TRANSACTION_TABLE_BETS);
                    $aResponse['externalCreditTransactionId'] = $this->_getTransaction('txn',self::TRANSACTION_TABLE_WINS);
                    $iAmountBet = $this->_getTransaction('amount',self::TRANSACTION_TABLE_BETS, (($this->getGpMethod() === 'cancelCreditDebit') ? false : true)); // amount would be 0 after 1st request so we use the amount from the Gp
                    $iAmountWin = $this->_getTransaction('amount',self::TRANSACTION_TABLE_WINS, (($this->getGpMethod() === 'cancelCreditDebit') ? false : true)); // amount would be 0 after 1st request so we use the amount from the Gp
                    $aResponse['cashDebit'] = $this->convertFromToCoinage($iAmountBet, self::COINAGE_CENTS, self::COINAGE_UNITS);
                    $aResponse['cashCredit'] = $this->convertFromToCoinage($iAmountWin, self::COINAGE_CENTS, self::COINAGE_UNITS);
                    $aResponse['bonusCredit'] = 0;
                    $aResponse['bonusDebit'] = 0;
                    $aResponse['externalTransactionTimeStamp'] = $this->_getDateTimeInstance($this->_getTransaction('date', self::TRANSACTION_TABLE_BETS))->format("m-d-Y H:i:s.vP");
                } else {
                    $TxnId = null;
                    switch($this->getGpMethod()){
                        case 'cancelDebit':
                            $TxnId = $this->_getTransaction('txn',self::TRANSACTION_TABLE_BETS);
                            $iAmount = $this->_getTransaction('amount',self::TRANSACTION_TABLE_BETS, false); // amount would be 0 after 1st request so we use the amount from the Gp
                            $sDate = $this->_getTransaction('date',self::TRANSACTION_TABLE_BETS);
                            break;
                        case 'cancelCredit':
                            $TxnId = $this->_getTransaction('txn',self::TRANSACTION_TABLE_WINS);
                            $iAmount = $this->_getTransaction('amount',self::TRANSACTION_TABLE_WINS, false); // amount would be 0 after 1st request so we use the amount from the Gp
                            $sDate = $this->_getTransaction('date',self::TRANSACTION_TABLE_WINS);
                            break;
                        default:
                            $TxnId = $this->_getTransaction('txn');
                            $iAmount = $this->_getTransaction('amount');
                            $sDate = $this->_getTransaction('date');
                            break;
                            
                    }
                    
                    $aResponse['externalTransactionId'] = $TxnId;
                    $aResponse['cash'] = $this->convertFromToCoinage($iAmount, self::COINAGE_CENTS, self::COINAGE_UNITS);
                    $aResponse['bonus'] = 0;
                    $aResponse['externalTransactionTimeStamp'] = $this->_getDateTimeInstance($sDate)->format("m-d-Y H:i:s.vP");
                }
            }
            
            $aResp['result'] = $aResponse;
            
        } else {
            $aResp['error'] = array(
                'code' => $p_mResponse['code'],
                'message' => $p_mResponse['message']
            );
        }
        
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
     * @param string $p_mGameId The game_ext_name from the microgames table without the prefix so basically the game ID
     *                          as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @param bool $show_demo
     * @return string The url to open the game
     */
    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        
        $aUrl = array();
        $aUrl['platform'] = (($p_sTarget === 'mobile') ? 'M' : 'W');
        $aUrl['playMode'] = 'D';
        $aUrl['gameId'] = $p_mGameId;
        $aUrl['skinCode'] = $this->getLicSetting('skincode');
        $aUrl['lobbyUrl'] = $this->getLobbyUrl(false, $p_sLang, $p_sTarget);
        
        if (isLogged()) {
            $ud = cuPl()->data;
            $token = $this->getGuidv4($ud['id']);
            $countryCode = (($ud['country'] === 'GB') ? strtolower($ud['country']) : '');
            
            if ($p_sTarget === 'mobile') {
                if ($countryCode == 'gb') {
                    $aRc = $this->_getRc();
                    if(!empty($aRc)) {
                        $aUrl['jurisdiction'] = 'uk';
                        $aUrl['sessionElapsed'] = 0;
                        $aUrl['sessionLimit'] = $aRc['reality_check_interval'];
                    }
                }
            }
            
            $aUrl['playMode'] = 'R';
            $aUrl['username'] = $ud['id'] . $this->getSetting('branded_username', '');
            $aUrl['country'] = phive('CasinoCashier')->getIso3FromIso2($ud['country']);
            $aUrl['currency'] = $ud['currency'];
            $aUrl['language'] = strtoupper($ud['preferred_lang']);
            $aUrl['sessionToken'] = $token;
            
            $aGameData = $this->_getGameData();
            $aFreespins = $this->getBonusEntryByGameId($ud['id'], $aGameData['game_id']);
            $this->_logIt([__METHOD__, 'freespin: ' . print_r($aFreespins,true)]);
            if (!empty($aFreespins) && $aFreespins['frb_remaining'] > 0) {
                $aUrl['playMode'] = 'F';
                $aUrl['freespin_id'] = $aFreespins['id'];
                $aUrl['freespin_num'] = $aFreespins['frb_remaining'];
                $aUrl['freespin_bet'/*||'freespin_stake_per_line'*/] = $this->convertFromToCoinage(
                    mc($aFreespins['frb_denomination'], $ud['currency'],'multi', false),
                    self::COINAGE_CENTS,
                    self::COINAGE_UNITS);
                $aUrl['freespin_lines'] = $aFreespins['frb_lines'];
            }
            
            $this->toSession($token, $ud['id'], $p_mGameId, $p_sTarget);
            
        } else {

            $userID = uniqid();
            $aUrl['username'] = $userID;
            $aUrl['language'] = cLang();

        }
        $maxBetLimit = phive('Gpr')->getMaxBetLimit(cuPl(), true);
        if ($maxBetLimit == 5) {
            $aUrl['sl'] = 'h';
        }
        
        $url = $this->getSetting('launchurl') . '?' . http_build_query($aUrl);
        $this->_logIt([__METHOD__, 'launchurl: ' . $url]);
        
        return $url;
    }
 
}
