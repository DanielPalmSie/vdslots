<?php
require_once __DIR__ . '/Gp.php';

class Igt extends Gp
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
    protected $_m_bByRoundId = false;
    
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
     * The header content type for the response to the GP
     * @var string
     */
    protected $_m_sHttpContentType = Gpinterface::HTTP_CONTENT_TYPE_TEXT_XML;
    
    /**
     * Do we force respond with a 200 OK response to the GP
     * @var bool
     */
    protected $_m_bForceHttpOkResponse = true;
    
    protected $_m_bFinished = true;
    
    private $_m_sSecureToken = null;
    
    /**
     * Bind GP method requests to the correct class method name
     * Keys are the method names received by the GP request and the values are the class method names
     * @var array
     */
    private $_m_aMapGpMethods = array(
        'Init' => '_init',
        'Play' => '_play', // will be forwarded to bet and win
        'Void' => '_cancel',
        'GetPlayerBalance' => '_balance',
        'EndSession' => '_end',
        'HeartBeat' => '_heartbeat',
        'Recon' => '_recon',  // will be forwarded to bet and win
        'Notify' => '_notify' // for reality checks
    );
    
    private $_m_aErrors = array(
        
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
            'return' => true,
            'code' => 'REJECTED',
            'message' => 'Duplicate Transaction ID.'
        ),
        'ER06' => array(
            'responsecode' => 402,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => 'INSUFFICIENT_FUNDS',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
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
            ->_checkDeclaredProperties()
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
        
        $sData = $this->_m_sInputStream;
        // phive()->dumpTbl(__CLASS__,[__METHOD__, $sData]);
        $this->_setGpParams($sData);
        
        $oXml = simplexml_load_string($sData);
        
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
            if (isset($oXml->$key)) {
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
        
        if (isset($oXml->Header->Customer)) {
            
            if (isset($oXml->Header->Customer->attributes()->secureToken)) {
                $this->_m_sSecureToken = (string)$oXml->Header->Customer->attributes()->secureToken;
            }
            
            if ($this->_m_sSecureToken !== null && $this->_m_sSecureToken !== 'null' && $this->_m_sSecureToken !== '1234567890') {
                // we are inside an active game where Init, Play, GetPlayerBalance only have a secureToken
                $mSessionData = $this->fromSession(strtolower($this->_m_sSecureToken));
                
                // check if what we stored in the session does match up with the request coming from IGT
                if ($mSessionData === false) {
                    
                    // token not found
                    $this->_response($this->_getError(self::ER11));
                    
                } else {
                    
                    // we get the userId and gameId from session
                    $aJson['playerid'] = $mSessionData->userid;
                    $aJson['skinid'] = $this->stripPrefix($mSessionData->gameid);
                    
                }
                
            } else {
                
                if (isset($oXml->Header->Customer->attributes()->userId)) {
                    
                    $aJson['playerid'] = (string)$oXml->Header->Customer->attributes()->userId;
                    
                }
            }
        }
        
        if (isset($oXml->Header->Customer)) {
            
            $aJson['currency'] = (string)$oXml->Header->Customer->attributes()->ccyCode;
            
        }
        
        if (isset($oXml->Header->GameDetails)) {
            // only EndSession doesn't have gameid
            $aJson['skinid'] = (!isset($aJson['skinid']) ? (string)$oXml->Header->GameDetails->attributes()->gameId : $aJson['skinid']);
            $aJson['gamename'] = (string)$oXml->Header->GameDetails->attributes()->name;
        }
        
        if (isset($oXml->$method->RGSGame)) {
            
            // only those can have multiple actions
            
            $aJson['txnId'] = (string)$oXml->$method->RGSGame->attributes()->txnId;
            
            if ($oXml->$method->RGSGame->attributes()->finished == 'N') {
                // can only be bet because finished == N
                $aCommands = array('STAKE');
                $this->_m_bFinished = false;
            } else {
                // can be bet and or win because finished == Y
                $aCommands = array('STAKE', 'WIN', 'REFUND');
                $this->_m_bFinished = true;
            }
            
            $iCount = 0;
            foreach ($oXml->$method->RGSGame->children() as $child) {
                
                if (in_array((string)$child->attributes()->action, $aCommands)) {
                    // the actionId comes with the format {round_id}-{00|01} 00 means bet, and 01 means win.
                    // So, it's needed to explode the string and get only the round_id from the first position.
                    $round_id = (string) $child->attributes()->actionId;
                    $round_id = explode('-', $round_id)[0];

                    $aAction[$iCount]['command'] = (($method == 'Void') ? '_cancel' : (((string)$child->attributes()->action == 'STAKE') ? '_bet' : '_win'));
                    $aAction[$iCount]['parameters'] = array(
                        'amount' => $this->convertFromToCoinage((string)$child->attributes()->amount,
                            self::COINAGE_UNITS, self::COINAGE_CENTS),
                        'transactionid' => (string)$oXml->$method->RGSGame->attributes()->txnId,
                        'roundid' => $round_id
                    );
                } else {
                    // command not found
                    $this->_response($this->_getError(self::ER02));
                }
                $iCount++;
            }
            
            if ($iCount > 1) {
                // multiple transactions to process
                $aJson['state'] = 'multi';
                $aJson['actions'] = $aAction;
            } else {
                // single transaction to process
                $aJson['state'] = 'single';
                $aJson['action'] = $aAction[0];
            }
        } else if(isset($oXml->$method->RealityCheck)) {
            $aJson['state'] = 'single';
            $aJson['parameters'] = $oXml->$method->RealityCheck->action;
            $aJson['action'] = '_notify';
        } else {
            $aAction['command'] = $this->getWalletMethodByGpMethod($method);
            $aJson['state'] = 'single';
            $aJson['action'] = $aAction;
        }
        
        // detect for freespin
        if (isset($oXml->Header->FundMode)) {
            $aJson['freespin'] = array(
                'id' => (string)$oXml->Header->FundMode->attributes()->id,
                'num_lines' => (string)$oXml->Header->FundMode->attributes()->num_lines,
                'stake_per_line' => (string)$oXml->Header->FundMode->attributes()->stake_per_line,
            );
        }
        
        $this->_m_oRequest = json_decode(json_encode($aJson), false);
        // phive()->dumpTbl(__CLASS__,[__METHOD__, $this->_m_oRequest]);
        //print_r($this->_m_oRequest);
        //die();
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
        //}
        
        //} else {
        // secret key not valid
        //  $mResponse = $this->_getError(self::ER03);
        //}
        
        // Update the user data after each command
        if (isset($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }
        $this->_response($mResponse);
        
    }
    
    public function parseJackpots()
    {
        $currencies = phive('Currencer')->getAllCurrencies();
        $insert = array();
        $map = array(
            'Mega Jackpots Cleopatra' => $this->getGamePrefix() . '200-1250-001',
            'Mega Jackpots Siberian Storm' => $this->getGamePrefix() . '200-1251-001',
            'Gong Xi Fa Cai' => $this->getGamePrefix() . '200-1337-001'
        );
        $bTest = false;
        
        if ($bTest === true) {
            $sXml = '
            <jackpotMeter xmlns="http://www.igt.com/jackpot/meter/model">
            <meterBands>
                <meterBand bandid="M01-01-01-1">
                    <jackpot id="M01-01-01-13" level="1"/>
                </meterBand>
            </meterBands>
            <jackpots>
                <jackpot id="M01-01-01-13" showNow="54997373" payNow="54997373" hotness="0" numHotness="0" nextLevelProgress="0.0" currency="GBP" cycleId="21" type="MJP"/>
            </jackpots>
            <messageId>20</messageId>
            <status>SUCCESS</status>
            <responseTimestamp>1467711766512</responseTimestamp>
            </jackpotMeter>';
        }

        $urls =  $this->getAllJurSettingsByKey('jp_url');

        foreach ($urls as $jur => $url) {
            foreach (array_keys($currencies) as $cur) {
                foreach ($map as $gamename => $ext_game_name) {
                    if ($bTest === false) {
                        $sXml = phive()->post($url . '?currencycode=' . $cur, '',
                            Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML, '', '', 'GET', 15);
                    }
                    $oXml = simplexml_load_string($sXml);
                    if (isset($oXml->status) && (string)$oXml->status == 'SUCCESS') {
                        $insert[] = array(
                            "jp_value" => $this->convertFromToCoinage((string)$oXml->jackpots->jackpot->attributes()->payNow,
                                self::COINAGE_CENTS, self::COINAGE_CENTS),
                            "jp_id" => $gamename,
                            "jp_name" => $gamename,
                            "network" => $this->getGpName(),
                            "module_id" => $ext_game_name,
                            "currency" => $cur,
                            "local" => 0,
                            "jurisdiction" => $jur,
                            "game_id" => $ext_game_name,
                        );
                    }
                }
            }
        }

        $this->_logIt([__METHOD__, print_r($insert, true)]);
        return $insert;
    }

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $p_mGameId The micro_games:game_ext_name without the prefix so basically the game ID as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @param bool $show_demo
     * @return string The url to open the game
     */
    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        $user = cuPl();
        $jurisdiction = licJur($user);
        $aGameData = $this->_getGameData();
        $aUrl['nscode'] = strtoupper($this->getLicSetting('nscode'));
        $aUrl['skincode'] = strtoupper($this->getLicSetting('skincode'));
        $aUrl['softwareid'] = $p_mGameId;
        $aUrl['channel'] = (($p_sTarget === 'mobile') ? 'MOB' : 'INT');
        $aUrl['lobbyURL'] = $this->getLobbyUrl(false, $p_sLang);
        $aUrl['responsibleURL'] = phive('Licensed')->getRespGamingUrl($user, $p_sLang);
        $aUrl['presenttype'] = 'STD';
        $aUrl['technology'] = 'HTML';

        
        if (isLogged()) {           
            $ud = $user->data;
            $sSecureToken = $this->getGuidv4($ud['id']);
            
            $aUrl['uniqueid'] = $ud['id'];
            $aUrl['language'] = strtolower($p_sLang);
            $aUrl['currencycode'] = strtoupper($ud['currency']);
            $aUrl['countrycode'] = strtoupper($ud['country']); // o/r
            $aUrl['securetoken'] = $sSecureToken; // r
            $aUrl['gender'] = strtolower($ud['sex']); // r male/female
            $aUrl['dateofbirth'] = $ud['dob']; // r yyyy-mm-dd
            //$aUrl['minbet'] = mc(0.1, $ud['currency'], 'multi', false);
            //$aUrl['denomamount'] = mc(0.1, $ud['currency'], 'multi', false);
            //$aUrl['terminalid'] = ''; // o
            //$aUrl['affiliateid'] = ''; // o
            //$aUrl['promotionalcode'] = ''; // o Y/N
            $aFreespins = $this->getBonusEntryByGameIdAndFRbRemaining($ud['id'], $aGameData['game_id']);
            $this->_logIt([__METHOD__, 'freespin: ' . print_r($aFreespins,true)]);
            if (!empty($aFreespins) && $aFreespins['frb_remaining'] > 0) {
                $aUrl['playMode'] = 'freespin';
                $aUrl['freespin_tokenID'] = $aFreespins['id'];
                $aUrl['freespin_num'] = $aFreespins['frb_remaining'];
                $aUrl['freespin_bet'/*||'freespin_stake_per_line'*/] = $this->convertFromToCoinage(
                    mc($aFreespins['frb_denomination'], $ud['currency'],'multi', false),
                    self::COINAGE_CENTS,
                    self::COINAGE_UNITS);
                $aUrl['freespin_lines'] = $aFreespins['frb_lines'];
            }
            if ($this->getRcPopup($p_sTarget, $user) == 'ingame') {     
                $rg = phive('Licensed')->rgLimits();
                $rg_limits = $rg->getRcLimit($user);        
                if (!empty($rg_limits['cur_lim'])) {
                    $sSkinCode = strtoupper($this->getLicSetting('skincode',$user));
                    $aUrl[$sSkinCode . '_sessionLimit'] = $rg_limits['cur_lim'];
                    $aUrl[$sSkinCode . '_historyURL'] = $this->getHistoryUrl(false, $user, $p_sLang);
                }
            }

            $this->toSession($sSecureToken, $ud['id'], $p_mGameId, $p_sTarget);
        } else {
            $aUrl['currencycode'] = $this->getSetting('freeplaycurrency');
            $aUrl['language'] = cLang();
        }
        $url = $this->getLicSetting('flash_play' . (($p_sTarget == 'mobile') ? '_mobile' : '')) . '?' . http_build_query($aUrl);
        // phive()->dumpTbl(__CLASS__,[__METHOD__, $aUrl]);
        $this->_logIt([__METHOD__, 'url: ' . $url]);
        return $url;
    }

    /**
     * Get bonus entry data by user ID and either gameId (with GP prefix) or bonusEntryId
     *
     * @param int $p_iUserId The user ID
     * @param mixed $game_id bonus_entries:game_id (with GP prefix), bonus_entries:ext_id (with GP prefix) or the bonus_entries:id
     * @param string $p_sFilter game_id|ext_id|''
     * @param string $p_sGpName The game Providers name
     * @return object the query result
     */
    public function getBonusEntryByGameIdAndFRbRemaining($p_iUserId, $game_id)
    {
        $query = "
              SELECT
                be.*,
                bt.frb_denomination,
                bt.frb_lines,
                bt.rake_percent,
                bt.frb_coins,
                bt.game_id
              FROM bonus_entries be
              INNER JOIN bonus_types bt ON bt.id = be.bonus_id AND bt.bonus_type = 'freespin'
              AND bt.game_id = " . phive("SQL")->escape($game_id) . " WHERE if(bt.rake_percent > 0, be.status = 'active', be.status = 'approved') AND frb_remaining > 0
              AND be.user_id = " . phive("SQL")->escape($p_iUserId) . " ORDER BY id DESC LIMIT 1";

      return phive('SQL')->sh($p_iUserId)->loadAssoc($query);
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
    
    /**
     * Send a response to gp
     *
     * @param mixed $p_mResponse true if command was executed succesfully or an array with the error details from property $_m_aErrors
     * @return mixed The headers and depending on other response params a json_encoded string, which is the answer to gp
     */
    protected function _response($p_mResponse)
    {
        
        $bConvert = true;
        
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
        
        $iFrbCount = $sFrbBalance = $sCashBalance = '';
        $iBalance = (($bConvert === false) ? $this->_getBalance() : $this->convertFromToCoinage($this->_getBalance(),
            self::COINAGE_CENTS, self::COINAGE_UNITS));
        
        if ($this->_isFreespin()) {
            $iFrbCount = ' count="' . $this->_getFreespinData('frb_remaining') . '"';
            $iFrbBalance = (($bConvert === false) ? ($this->_getFreespinValue() * $this->_getFreespinData('frb_remaining')) : $this->convertFromToCoinage(($this->_getFreespinValue() * $this->_getFreespinData('frb_remaining')),
                self::COINAGE_CENTS, self::COINAGE_UNITS));
            $sFrbBalance = PHP_EOL . '<Balance amount="' . $iFrbBalance . '" type="FREESPIN"' . $iFrbCount . ' />';
            if ($this->_getFreespinData('frb_remaining') == 0 && $this->_getMethod() === '_end') {
                $sFrbBalance = $iFrbCount = '';
            }
        } else {
            // only set during _end request in EndSession.xml as the placeholder is only in that file
            // only if not a frb it should be set
            $sCashBalance = '<Balance amount="' . $iBalance . '" type="CASH" />';
        }
        
        $aResponse = array(
            'userId' => $iUserId,
            'type' => 'CASH',
            'balance' => $iBalance,
            //FRB logic
            'count' => $iFrbCount,
            'msg' => (isset($p_mResponse['message']) ? ' msg="' . $p_mResponse['message'] . '"' : ''),
            'status' => $p_mResponse['status'] ?? 'FAILURE',
            'userName' => '',
            'userPassword' => '',
            'betAmount' => '', // set below
            'winAmount' => '', // set below
            'cancelAmount' => '', // set below
            'txn' => $iTxnId,
            'finished' => (($this->_m_bFinished === false) ? 'N' : 'Y'),
            'secureToken' => (!empty($this->_m_sSecureToken) ? ' secureToken="' . strtoupper($this->_m_sSecureToken) . '"' : ''),
            'gameId' => ' gameId="' . $sGameId . '"',
            'gameName' => $sGameName,
            'currency' => $sCurrency,
            'action' => (($this->getGpMethod() == 'Recon') ? ' action="COMPLETE"' : ''),
            'method' => $this->getGpMethod(),
            'frbBalance' => $sFrbBalance,
            'cashBalance' => $sCashBalance
        );
        
        if ($p_mResponse === true) {
            switch ($this->_getMethod()) {
                case '_bet':
                case '_win':
                case '_cancel':
                    $aResponse['status'] = 'SETTLED';
                    break;
                default:
                    $aResponse['status'] = 'SUCCESS';
            }
        }
        
        if (in_array($this->_getMethod(), array('_bet', '_win', '_cancel'))) {
            if($this->_hasMultiTransactions() || $this->_getMethod() === '_bet') {
                $aResponse['betAmount'] = $this->_rgsAction(
                    $this->_getTransaction('amount', self::TRANSACTION_TABLE_BETS, (($this->_getMethod() === '_cancel') ? false : true)), // on cancel it will set the amount to 0, idempotence would break
                    $this->_getTransaction('txn', self::TRANSACTION_TABLE_BETS),
                    $this->_getTransaction('txn', self::TRANSACTION_TABLE_BETS, false),
                    $aResponse['type'],
                    'STAKE',
                    $bConvert
                );
            }
            if($this->_hasMultiTransactions() || $this->_getMethod() === '_win') {
                $aResponse['winAmount'] = $this->_rgsAction(
                    $this->_getTransaction('amount', self::TRANSACTION_TABLE_WINS, (($this->_getMethod() === '_cancel') ? false : true)), // on cancel it will set the amount to 0, idempotence would break
                    $this->_getTransaction('txn', self::TRANSACTION_TABLE_WINS),
                    $this->_getTransaction('txn', self::TRANSACTION_TABLE_WINS, false),
                    $aResponse['type'],
                    'WIN',
                    $bConvert
                );
            }
            if($this->_getMethod() === '_cancel') {
                $aResponse['cancelAmount'] = $this->_rgsAction(
                    $this->_getTransaction('amount', $this->_getCancelTbl(), false),
                    $this->_getTransaction('txn', $this->_getCancelTbl()),
                    $this->_getTransaction('txn', $this->_getCancelTbl(), false),
                    $aResponse['type'],
                    'REFUND',
                    $bConvert
                );
            }
        }

        $this->_setResponseHeaders($p_mResponse);

        $sResults = $this->_parseFile($aResponse);

        $this->_logIt([__METHOD__, print_r($aResponse, true), $sResults]);
        echo $sResults;
        
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }
    
    /**
     * Get an XML RGSAction string for bet, win, cancel requests
     * @param int $p_iAmount The bet, win or cancel amount
     * @param string $p_sIntTxnId The internal transaction ID
     * @param string $p_sExtTxnId The external transaction ID
     * @param string $p_sResponseType FREESPIN|CASH
     * @param string $p_sAction For bet|win|cancel resp. STAKE|WIN|REFUND
     * @param bool $p_bConvert Do we convert the amount from cents to unit. Default true
     * @return string
     */
    private function _rgsAction($p_iAmount, $p_sIntTxnId, $p_sExtTxnId, $p_sResponseType, $p_sAction, $p_bConvert = true)
    {
        
        if ($p_iAmount !== null) {
            
            $iAmount = (($p_bConvert === false) ? $p_iAmount : $this->convertFromToCoinage($p_iAmount,
                self::COINAGE_CENTS, self::COINAGE_UNITS));
            $iFrbAmount = (($p_bConvert === false) ? $this->_getFreespinValue() : $this->convertFromToCoinage($this->_getFreespinValue(),
                self::COINAGE_CENTS, self::COINAGE_UNITS));
            $frb = '';
            
            if ($this->_isFreespin()) {
                switch ($p_sAction) {
                    case 'STAKE':
                        // the amount of 1 free spin bet
                        $frb = '<FundType amount="' . $iFrbAmount . '" type="FREESPIN" />';
                        break;
                    
                    case 'WIN':
                        // the amount of 1 free spin win
                        $frb = '<FundType amount="' . $iAmount . '" type="FREESPIN" />';
                        break;
                }
            }
            
            return str_replace(
                array(
                    ':action',
                    ':reference',
                    ':amount',
                    ':txnId',
                    ':type',
                    ':frb',
                ),
                array(
                    $p_sAction,
                    $p_sIntTxnId,
                    $iAmount,
                    $p_sExtTxnId,
                    $p_sResponseType,
                    $frb,
                ),
                '<RGSAction action=":action" actionRef=":reference" amount=":amount" actionId=":txnId">' . PHP_EOL . '<FundType amount="0.00" type="BONUS" />' . PHP_EOL . '<FundType amount=":amount" type=":type" />' . PHP_EOL . ':frb</RGSAction>' . PHP_EOL);
        }
        return '';
    }
    
    /**
     * Parse xml file and replace the placeholders with their value.
     *
     * @param array $p_aParams
     * @return string
     */
    private function _parseFile($p_aParams)
    {
        phive()->dumpTbl(__CLASS__,[__METHOD__, $this->getGpMethod()]);
        foreach ($p_aParams as $key => $val) {
            unset($p_aParams[$key]);
            $p_aParams['{{' . $key . '}}'] = $val;
        }
        
        $sXml = file_get_contents(realpath(dirname(__FILE__)) . '/../Test/TestIgt/response/' . (in_array($this->getGpMethod(),
                array('Play', 'Recon', 'Void')) ? 'Play' : $this->getGpMethod()) . '.xml');
        
        return str_replace(array_keys($p_aParams), array_values($p_aParams), $sXml);
    }

    public function _notify($action)
    {
        return true;
    }

    /**
     * Enables round table
     *
     * @return bool
     */
    public function doConfirmByRoundId(): bool
    {
        return !($this->isTournamentMode() || $this->_isFreespin());
    }
}
