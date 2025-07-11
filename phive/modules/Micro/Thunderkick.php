<?php
require_once __DIR__ . '/Gp.php';

class Thunderkick extends Gp
{

    /**
     * Name of GP
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
    protected $_m_bConfirmFrbBet = true;

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
    protected $_m_sHttpContentType = Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON;

    /**
     * Do we force respond with a 200 OK response to the GP
     * @var bool
     */
    protected $_m_bForceHttpOkResponse = false;

    /**
     * Print to screen/terminal during debugging instead of to /tmp/xxx.txt
     * @var bool
     */
    protected $_m_bToScreen = false;

    /**
     * Skip the check for bets in _hasBet function
     * @var bool
     */
    protected $_m_bSkipBetCheck = true;

    /**
     * Bind GP method requests to the correct class method name
     * Keys are the method names received by the GP request and the values are the class method names
     * @var array
     */
    private $_m_aMapGpMethods = array(
        'playersessiontoken' => 'testGpGetPlayerSessionToken',
        'balances' => '_balance',
        'rollbackBetAndWin' => '_cancel',
        'rollbackBet' => '_cancel',
        'betAndWin' => '_bet_win',
        'bet' => '_bet',
        'win' => '_win',
    );

    private $_m_sSecureToken = null;

    private $_m_aErrors = array(
        'ER03' => array(
            'responsecode' => 532,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => '100',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER06' => array(
            'responsecode' => 533,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => '200',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        'ER08' => array(
            'responsecode' => 533,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => true,
            'code' => '251',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ),
        'ER09' => array(
            'responsecode' => 532,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => '210',
            'message' => 'Player not found.'
        ),
        'ER10' => array(
            'responsecode' => 532,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => '101',
            'message' => 'Game is not found.'
        ),
        'ER11' => array(
            'responsecode' => 532,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => '100',
            'message' => 'Token not found.'
        ),
        'ER12' => array(
            'responsecode' => 532,
            'status' => 'FREESPIN_NO_REMAINING',
            'return' => 'default',
            'code' => '250',
            'message' => 'No freespins remaining.'
        ),
        'ER13' => array(
            'responsecode' => 532,
            'status' => 'FREESPIN_INVALID',
            'return' => 'default',
            'code' => '250',
            'message' => 'Invalid freespin bet amount.'
        ),
        'ER14' => array(
            'responsecode' => 532,
            'status' => 'FREESPIN_UNKNOWN',
            'return' => 'default',
            'code' => '260',
            'message' => 'Freespin stake transaction not found.'
        ),
        'ER17' => array(
            'responsecode' => 532,
            'status' => 'FREESPIN_NOT_FOUND',
            'return' => 'default',
            'code' => '250',
            'message' => 'This free spin bonus ID is not found.'
        ),
        'ER18' => array(
            'responsecode' => 532,
            'status' => 'IDEMPOTENCE',
            'return' => true,
            'code' => '270',
            'message' => 'Duplicate Transaction ID with same amount does exist already.'
        ),
        'ER19' => array(
            'responsecode' => 532,
            'status' => 'STAKE_TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => '260',
            'message' => 'Stake transaction not found.'
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

    public function doConfirmByRoundId()
    {
        return !($this->isTournamentMode() || $this->_isFreespin());
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
        $oData = json_decode($sData, false);

        if ($oData === null) {
            // request is unknown
            $this->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }

        $aJson = $aAction = array();
        $method = null;
        $aMethods = $this->_getMappedGpMethodsToWalletMethods();

        // Define which service/method is requested/to use
        $aUrlMethod = explode('/', $_SERVER['REQUEST_URI']);
        $aUrlMethod = array_slice($aUrlMethod, -2);
        if ($aUrlMethod[0] == 'thunderkick.php') {
            $aUrlMethod = array_slice($aUrlMethod, -1);
        }

        foreach ($aMethods as $key => $value) {
            if ($key == $aUrlMethod[0]) {
                $method = $value;
                $this->_setGpMethod($key);
                break;
            }
        }

        $this->_logIt([__METHOD__, print_r($aUrlMethod, true), print_r($aMethods, true)]);

        if (empty($method)) {

            // method to execute not found
            $this->_setResponseHeaders($this->_getError(self::ER02));
            $this->_logIt([__METHOD__, $method]);
            die();
        }

        // operatorSessionToken should be there in all requests
        if (isset($oData->operatorSessionToken)) {
            $this->_m_sSecureToken = $oData->operatorSessionToken;
        }

        if ($this->_m_sSecureToken !== null) {
            $mSessionData = $this->fromSession($this->_m_sSecureToken);
            if ($mSessionData !== false) {
                // we get the userId and gameId from session
                $aJson['playerid'] = $mSessionData->userid;
                $aJson['skinid'] = $this->stripPrefix($mSessionData->gameid);
            } else {
                // session doesnt exist anymore. could be a delayed win
            }
        }

        // playerId should be there in all requests except balance request. So balance request must happen in active session
        if (!isset($aJson['playerid'])) {

            if (isset($oData->playerExternalReference)) {
                $aJson['playerid'] = $this->getPlayerIdFromExternalReference($oData->playerExternalReference); // this ref is set during players registration process during gamelaunch
            }

            if (!isset($aJson['playerid'])) {
                // failed to set the playerID
                $this->_setResponseHeaders($this->_getError(self::ER09));
                $this->_logIt([__METHOD__, 'player ID is not found in request']);
                die();
            }
        }

        $aJson['playerid'] = $this->_enableTournamentByToken($aJson['playerid']);
        if (isset($oData->distributionChannel)) {
            $aJson['device'] = (strtoupper($oData->distributionChannel) == 'MOBILE') ? 'mobile' : 'desktop';
        }

        $key = 0;
        // gameRound with gameName should be there in all requests (even delayed win) except balance and cancel request
        if (isset($oData->gameRound)) {

            // we are in a bet, win, betwin, if skinid isn't set than it's a delayed win
            if (!isset($aJson['skinid'])) {

                if (isset($oData->gameRound->gameName)) {
                    $aJson['skinid'] = $oData->gameRound->gameName;
                }

                if (!isset($aJson['skinid'])) {
                    // failed to set the gameID
                    $this->_setResponseHeaders($this->_getError(self::ER10));
                    $this->_logIt([__METHOD__, 'game ID is not found in request']);
                    die();
                }
            }

            $aJson['roundid'] = $oData->gameRound->gameRoundId;

            if (isset($oData->bets)) {
                $aAction[$key]['command'] = '_bet';
                $aAction[$key]['parameters']['transactionid'] = (isset($aUrlMethod[1]) ? $aUrlMethod[1] : '');
                // GP confirmed there will be always only 1 bet in the bets array
                $aAction[$key]['parameters']['amount'] = $this->convertFromToCoinage($oData->bets[0]->bet->amount,
                    self::COINAGE_UNITS, self::COINAGE_CENTS);
                $aAction[$key]['parameters']['roundid'] = $oData->gameRound->gameRoundId;

                // detect for freespin
                if ($oData->bets[0]->accountType === 'FREE_ROUND') {
                    $a = explode('-', $oData->bets[0]->accountId);
                    $aJson['freespin'] = array('id' => $a[1]);
                }
                $key = 1;
            }

            if (isset($oData->wins)) {
                $aAction[$key]['command'] = '_win';
                $aAction[$key]['parameters']['transactionid'] = (($key === 0) ? (isset($aUrlMethod[1]) ? $aUrlMethod[1] : '') : $oData->winTransactionId);
                // GP confirmed there will be always only 1 win in the bets array
                $aAction[$key]['parameters']['amount'] = $this->convertFromToCoinage($oData->wins[0]->win->amount,
                    self::COINAGE_UNITS, self::COINAGE_CENTS);
                $aAction[$key]['parameters']['roundid'] = $oData->gameRound->gameRoundId;

                // detect for freespin
                if ($oData->wins[0]->accountType === 'FREE_ROUND') {
                    $b = explode('-', $oData->wins[0]->accountId);
                    $aJson['freespin'] = array('id' => $b[1]);
                }
            }

            if (count($aAction) > 1) {
                // multiple transactions to process
                $aJson['state'] = 'multi';
                $aJson['actions'] = $aAction;
            } else {
                // single transaction to process
                $aJson['state'] = 'single';
                $aJson['action'] = $aAction[0];
            }

        } else {

            if ($method == '_cancel') {
                // rollbackBetAndWin request will never happen during a freespin.
                // Only separate bet, win and cancel requests will happen because we cant cancel a last freespin
                // as the winning would have been added to players wallet already
                if (isset($oData->betTransactionId)) {
                    $aAction[$key]['command'] = '_cancel';
                    $aAction[$key]['parameters']['transactionid'] = $oData->betTransactionId;
                    $key = 1;
                }

                if (isset($oData->winTransactionId)) {
                    $aAction[$key]['command'] = '_cancel';
                    $aAction[$key]['parameters']['transactionid'] = $oData->winTransactionId;
                }

                if ($oData->accountType === 'FREE_ROUND' && isset($oData->externalAccountId)) {
                    // it's a freespin that need to be cancelled
                    $b = explode('-', $oData->externalAccountId);
                    $aJson['freespin'] = array('id' => $b[1]);
                }

                if (count($aAction) > 1) {
                    // multiple transactions to process
                    $aJson['state'] = 'multi';
                    $aJson['actions'] = $aAction;
                } else {
                    // single transaction to process
                    $aJson['state'] = 'single';
                    $aJson['action'] = $aAction[0];
                }

            } else {
                $aAction['command'] = $method;
                $aJson['state'] = 'single';
                $aJson['action'] = $aAction;
            }
        }

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

        if ($this->getSetting('test') === true && $_SERVER['HTTP_AUTHORIZATION'] === base64_encode($this->getSetting('vs_username') . ':' . $this->getSetting('vs_passwd'))) {
            // secret key not valid
            $mResponse = $this->_getError(self::ER03);
            $this->_response($mResponse);
        }

        $mResponse = false;

        // check if the commands requested do exist
        $this->_setActions();

        $input_stream = json_decode($this->_m_sInputStream, true);
        if(isset($input_stream['gameName'])) {
            $this->_m_oRequest->skinid = $input_stream['gameName'];
        }
        if(isset($input_stream['operatorSessionToken'])) {
            $this->_m_sSessionKey = $input_stream['operatorSessionToken'];
        }

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

        $u_obj = cu($this->_m_oRequest->playerid);
        $this->_m_sSessionData->userid = $u_obj->userId;
        $this->_m_sSessionData->gameid = $this->_m_oRequest->skinid;
        if (empty($this->_m_sSessionData->ext_session_id)) {
            $this->setNewExternalSession($u_obj, $this->_m_sSessionData);
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
     * Inform the GP about the amount of freespins available for a player.
     * The player can open the game anytime. The GP already 'knows' about the freespins available to the player.
     * Often if the GP keeps track itself and send the wins at the end when all FRB are finished like with Netent.
     *
     * @param int $p_iUserId The user ID
     * @param string $p_sGameIds The internal gameId's pipe separated
     * @param int $p_iFrbGranted The frb given to play
     * @param string $bonus_name
     * @param array $p_aBonusEntry The entry from bonus_types table
     * @return bool|string|int If not false than empty string is returned otherwise false (freespins are not activated)
     */
    public function awardFRBonus($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry, $secondTry = false)
    {
        if($this->getSetting('no_out') === true) {
            $ext_id = (!empty($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->randomNumber(6));
            $this->attachPrefix($ext_id);
            return $ext_id;
        }

        $user = cu($p_iUserId);
        $ud = ud($user);

        $this->_logIt([__METHOD__, 'user-data:' . print_r($ud, true), 'Freespin:' . print_r($p_aBonusEntry, true)]);

        if (!empty($p_aBonusEntry)) {
            // WE CREATE THE TEMPLATE
            // page 12 => 4.2.2. Creation of a free rounds bonus template.

            $game_list = [];
            foreach (explode('|', $this->stripPrefix($p_sGameIds)) as $game_id) {
                $game_list[] = $this->stripPrefix(phive('MicroGames')->overrideGameRef($user, $this->getGamePrefix().$game_id));
            }

            $oData = $this->_getJsonData($this->loadUrlByKey('gp_url_freeround_template') . sprintf('/%d-%d', $ud['id'], $p_aBonusEntry['id']),
                [
                    'name' => $ud['id'] . '-' . $p_aBonusEntry['id'],
                    'description' => '',
                    'numberOfFreeRounds' => $p_iFrbGranted,
                    'gameGroups' => [
                        [
                            'games' => $game_list,
                            'distributionChannels' => ['WEB', 'MOBILE'],
                            'betConfigurations' => [
                                [
                                    'amount' => $this->convertFromToCoinage($this->_getFreespinValue($ud['id'],
                                        $p_aBonusEntry['id']), self::COINAGE_CENTS, self::COINAGE_UNITS),
                                    'currency' => $ud['currency']
                                ]
                            ]
                        ]
                    ]
                ]);
            // log response for debugging
            $this->_logIt([__METHOD__, print_r($oData, true)]);
            $status= $oData->http_status ?? phive()->res_headers[0];
            if (strpos($status, '204') !== false || (strpos($status,'524') !== false && $oData->errorCode === '1100')) {
                // TEMPLATE CREATED OR HAS BEEN CREATED BEFORE
                // ASSIGN THE USER TO THE TEMPLATE SO PLAYER CAN USE ITS FREEROUNDS "2013-06-21T08:59:58.456+0200"
                // page 27 => 4.2.8. Assign free rounds bonus program to a player.
                $username = $ud['id'];
                if ($secondTry !== true) {
                    $username .= $this->getSetting('internal_brand', '_vs');
                }
                $oData = $this->_getJsonData($this->loadUrlByKey('gp_url_freeround_template_assign_by_external_ref') . '/' . $username . '/' . $ud['id'] . '-' . $p_aBonusEntry['id'],
                    array(
                        'freeRoundsBonusTemplateReference' => $ud['id'] . '-' . $p_aBonusEntry['id'],
                        'validFrom'                        => phive()->hisMod('-48 hour', "{$p_aBonusEntry['start_time']} 00:00:01", 'Y-m-d\TH:i:s\.\0\0\0O'),
                        'validTo'                          => phive()->hisMod('+48 hour', "{$p_aBonusEntry['end_time']} 23:59:59", 'Y-m-d\TH:i:s\.\0\0\0O'),
                    ));

                // log response for debugging
                $this->_logIt([__METHOD__, print_r($oData, true)]);
                if (strpos($status, '204') !== false) {
                    // user has been assigned to template, they only return header not content.
                    return '';
                } elseif (strpos($status, '524') !== false  && $secondTry === false) {
                    return $this->awardFRBonus($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry, true);
                }
            }
        }
        return false;
    }

    /**
     * TODO This most probably needs to be deleted / refactored!!!
     *  _gpGetPlayerSessionToken is not defined anymore in the code /Paolo
     *
     * Test if the GP is returning a players session token which is required for the launchurl
     * This is only for testing purpose and will only work from allowed ips and if log_errors === true
     * @return string json encoded response
     */
    public function testGpGetPlayerSessionToken(){
        $sPlayerSessionToken = 'failed check trans_log table';

        if($this->getSetting('log_errors') === true && in_array(remIp(), $this->_getWalletIps())){
            // request is coming from valid videoslots/office ip
            $sPlayerSessionToken = $this->_gpGetPlayerSessionToken(array_merge(ud((int)$this->_m_oRequest->playerid), array('operatorSessionToken' => $this->getGuidv4((int)$this->_m_oRequest->playerid))));
        }
        $this->_setResponseHeaders(array('responsecode' => 200, 'status' => ''));
        echo json_encode(array('playerSessionToken' => $sPlayerSessionToken));
        die;
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
        $this->getUsrId($_SESSION['token_uid']);
        $force_iso = $this->isTournamentMode() ? $this->getLicSetting('bos-country') : null;
        $this->initCommonSettingsForUrl($force_iso);
        $aUrlParameters = $this->getLaunchParameters($p_mGameId, $p_sLang, $p_sTarget);
        $sLaunchUrl = $this->getLicSetting('gp_url_launch') . '?' . http_build_query($aUrlParameters);
        return $sLaunchUrl;
    }


    /**
     * Returns params for both mobile and desktop, rc and no rc
     * @param $p_mGameId
     * @param string $p_sLang
     * @param string $p_sTarget
     * @return array
     */
    private function getLaunchParameters($p_mGameId, $p_sLang = '', $p_sTarget = '')
    {
        $isLogged = isLogged();

        $user = $isLogged ? cu() : null;
        $uData = $user ? $user->getData() : [];

        $aCommonParameters = [
            'gameId'                    => $p_mGameId,
            'device'                    => $p_sTarget,
            'operatorId'                => $this->getLicSetting('operator_id'),
            'allowFullscreen'           => 'true',
            'depositUrl'                => $this->getCashierUrl(false, $p_sLang, $p_sTarget),
            'freeAccountCurrencyIso'    => lic('getForcedCurrency', [], $user) ?: 'EUR',
            'langIso'                   => $p_sLang,
            'loginUrl'                  => $this->getLobbyUrl(false, $p_sLang, $p_sTarget),
            'playMode'                  => $isLogged ? 'real' : 'demo',
            'playerSessionId'           => $isLogged ? $this->getPlayerSessionId($uData) : null,
            'supportsBackToLobby'       => 'true',
            'lobbyUrl'                  => $this->getLobbyUrl(false, $p_sLang, $p_sTarget),
            'devMode'                   => $this->getSetting('test') ? 'true' : null
        ];

        $aCommonParameters = array_filter($aCommonParameters);

        $launch_params = ($this->getRcPopup($p_sTarget, $user) == 'ingame') ?
            array_merge($aCommonParameters, (array)$this->getRealityCheckParameters($user, false,
                ['regulator', 'useReallityCheck', 'rcHistoryUrl', 'rcInterval', 'rcIdleResetInterval',
                    'elapsed_session_time', 'rcElapsedTime', 'rcTotalBet', 'rcTotalWin', 'sga-show', 'cma-show'])) :
            $aCommonParameters;

        $launch_params['regulator'] = $this->getRegulator($user);

        $this->dumpTst('launch params', $launch_params);

        return $launch_params;
    }

    public function addCustomRcParams($regulator, $rcParams)
    {
        $regulator = $this->getRegulator();
        $regulator_params = [
            'regulator'             => $regulator,
            'useReallityCheck'      => 'true',
            'rcIdleResetInterval'   => true,
        ];

        return array_merge($rcParams, $regulator_params, (array)$this->lic($regulator, 'addCustomParams'));
    }

    public function addCustomRcParamsMT()
    {
        return $this->addCustomRcParamsGB();
    }

    public function addCustomRcParamsGB()
    {
        $gb_params = [
            'cma-show' => $this->getLicSetting('cma-show'),
        ];

        return $gb_params;
    }

    public function addCustomRcParamsSE()
    {
        $se_params = [
            'sga-show' => $this->getLicSetting('sga-show'),
        ];

        return $se_params;
    }

    public function mapRcParameters($regulator, $rcParams)
    {
        $rcParams['rcInterval'] = $rcParams['rcInterval'] * 60;
        $rcParams['rcIdleResetInterval'] = $rcParams['rcInterval'] - 5;

        return $rcParams;
    }

    public function getRegulator($user = null)
    {
        $regulator = !empty($this->t_eid) ? $this->getLicSetting('bos-country', $user) : $this->getLicSetting('regulator', $user);

        return $regulator ?? licJur($user);
    }

    /**
     * Get a playerSessionId by login the user at the GP and if not registered than register and login
     * @param array $user Array with the user data
     * @return bool|string false on failure or string sessionId on success
     */
    public function getPlayerSessionId($user)
    {
        $sSecureToken = $this->getGuidv4($user['id']);
        $p_aParams = array_merge($user, array('operatorSessionToken' => $sSecureToken));

        // login 1st try
        $login_data = $this->loginPlayer($p_aParams);

        if(!empty($login_data->playerSessionToken)) {
            // player exists in Thunderkick system
            return $login_data->playerSessionToken;
        }else if ($login_data->errorCode == '99') {
            // if invalid player error (player not found) then we need to register the player into Thunderkick system
            $register_data = $this->registerPlayer($p_aParams);

            if (!empty($register_data->playerId)){
                // once player is successfully registered then we try to log in the player again
                // login 2nd try
                $login_data = $this->loginPlayer($p_aParams);

                if (!empty($login_data->playerSessionToken)){
                    // if player logged in successfully return session token
                    return $login_data->playerSessionToken;
                } else {
                    return false;
                }
            }
            return false;
        } else {
            // Possible errors:
            // 1002 - Invalid operator, 1005 (server maintenance), 1006 (Player account locked)
            // we just stop trying to log in because it will be useless
            return false;
        }
    }


    private function _getJsonData($p_sUrl, $p_aData)
    {

        try {
            if (!$this->getSetting('async_request')) {
                $result = phive()->post(
                    $p_sUrl, json_encode($p_aData), 'application/json',
                    $this->_getAuthHeader(), null, 'POST'
                );
                $oData = json_decode($result, false);
            }else {
                $headers = $this->_getAuthHeader(true);
                $headers["Content-Type"] = "application/json";
                $options = [
                    'timeout' => $this->getSetting('async_request_timeout'),
                    'connect_timeout' => $this->getSetting('async_request_connect_timeout'),
                ];
                $p_http_request_version = $this->getSetting('async_http_version');
                $result = phive('HttpClient')->requestAsync(
                    'POST', $p_sUrl, $p_aData, $headers, $options, false, $p_http_request_version
                )->then(
                    function ($res) {
                        return $res;
                    },
                    function ($e) use ($p_sUrl, $p_aData, $headers, $options) {
                        $response = json_decode($e->getResponse()->getBody()->getContents());
                        $response->http_status = $e->getCode();
                        $response->error = true;
                        $this->logger->error("Http Async log get json data error", [
                            "error"=>$e,
                            "url"=>$p_sUrl,
                            "params"=>$p_aData,
                            "headers"=>$headers,
                            "options"=>$options]);
                        return $response;
                    }
                )->wait();
                if (isset($result->error) && $result->error) {
                    return $result;
                } else {
                    $oData = json_decode($result->getBody()->getContents());
                    $oData->http_status = $result->getStatusCode();
                }

            }

            $this->_logIt([
                    __METHOD__,
                    'base64:' . base64_encode($this->getSetting('gp_username') . ':' . $this->getSetting('gp_passwd')) . ' => ' . $this->getSetting('gp_username') . ':' . $this->getSetting('gp_passwd'),
                    'url: ' . $p_sUrl . ' POST params: ' . json_encode($p_aData),
                    'Result:' . print_r($result, true),
                    'Headers: ' . print_r(phive()->res_headers, true)
                ]
            );
            return $oData;
        } catch(Exception $e){
            phive('Logger')->error("thunderkick_getJsonData", [$e->getMessage(), $e->getTraceAsString()]);
        }
    }

    private function _getAuthHeader($return_as_array = false)
    {
        $auth_token = base64_encode($this->getSetting('gp_username') . ':' . $this->getSetting('gp_passwd'));
        if ($return_as_array) {
            return ["Authorization" => "Basic " . $auth_token] ;
        }else {
            return "Authorization: Basic " . $auth_token . "\r\n";
        }
    }


    private function _gpUpdatePlayerAccount()
    {

        $ud = cuPl()->data;

        $sUrl = $this->loadUrlByKey('gp_url_player_update') . $ud['username'];
        $data = array(
            'externalReference' => $this->_getTournamentToken($ud['id']), // user_id important as this is send during all GP request to us
            'password' => $ud['username'],
            'currencyCode' => strtoupper($ud['currency']),
            'gender' => strtoupper(substr($ud['sex'], 0, 1)),
            'countryCode' => $ud['country'],
            'birthdate' => $ud['dob'],
            //'email' => $ud['email'],
            'city' => $ud['city'],
        );
        $result = phive()->post($sUrl, json_encode($data), 'application/json', $this->_getAuthHeader(), null, 'PUT');

        $this->_logIt([
            __METHOD__,
            'url: ' . $sUrl,
            'PUT DATA: ' . print_r($data, true),
            'result: ' . print_r($result, true)
        ]);
    }

    /**
     * Game session close request. We destroy the session and return true
     * @see Gp::_end()
     */
    protected function _end()
    {
        $this->deleteToken($this->_m_sSecureToken);
        return true;
    }

    /**
     * Send a response to gp
     *
     * @param mixed $p_mResponse true if command was executed succesfully or an array with the error details from property $_m_aErrors
     * @return mixed The headers and depending on other response params a json_encoded string, which is the answer to gp
     */
    protected function _response($p_mResponse)
    {

        $aUserData = $this->_getUserData();
        $aResponse = array();

        if ($p_mResponse !== true) {

            $aResponse['errorCode'] = (($this->getGpMethod() === 'betAndWin' && $p_mResponse['code'] === '250') ? '270' : $p_mResponse['code']);
            $aResponse['errorMessage'] = $p_mResponse['message'];

        } else {

            $aResponse['balances']['moneyAccounts'] = array(
                array(
                    'balance' => array(
                        'amount' => $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS,
                            self::COINAGE_UNITS),
                        'currency' => strtoupper($this->getPlayCurrency($aUserData))
                    ),
                    'accountId' => (!empty($aUserData) ? $aUserData['id'] : 0),
                    'accountType' => 'REAL'
                )
            );

            switch ($this->getGpMethod()) {

                case 'balances':
                    if ($p_mResponse === true) {
                        $aResponse = $aResponse['balances'];
                    }
                    break;

                case 'bet':
                    $aResponse['extBetTransactionId'] = (($this->_m_bIsFreespin === true && $this->_m_bConfirmFrbBet === false) ? uniqid() : $this->_getTransaction('txn'));
                    break;

                case 'win':
                    $aResponse['extWinTransactionId'] = (($this->_getTransaction('txn') === null) ? uniqid() : $this->_getTransaction('txn'));
                    break;

                case 'betAndWin':
                    $aResponse['extBetTransactionId'] = (($this->_m_bIsFreespin === true && $this->_m_bConfirmFrbBet === false) ? uniqid() : $this->_getTransaction('txn',self::TRANSACTION_TABLE_BETS));
                    $aResponse['extWinTransactionId'] = (($this->_getTransaction('txn',self::TRANSACTION_TABLE_WINS) === null) ? uniqid() : $this->_getTransaction('txn',self::TRANSACTION_TABLE_WINS));
                    break;

                case 'rollbackBet':
                    $aResponse['extRollbackTransactionId'] = $this->_getTransaction('txn',self::TRANSACTION_TABLE_BETS);
                    break;

                case 'rollbackBetAndWin':
                    $aResponse['extRollbackTransactionId'] = $this->_getTransaction('txn',self::TRANSACTION_TABLE_BETS) . '-' . $this->_getTransaction('txn',self::TRANSACTION_TABLE_WINS);
                    break;

            }

            $p_mResponse = array(
                'responsecode' => 200,
                'status' => '',
            );
        }


        $this->_setResponseHeaders($p_mResponse);
        $result = json_encode($aResponse);
        $this->_logIt([__METHOD__, print_r($p_mResponse, true), $result]);
        echo $result;
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }

    /* External reference can be in the form of USERNAME or 543434_vs*/
    public function getPlayerIdFromExternalReference($externalReference='')
    {
        $tmp = explode('_', $externalReference);
        return $tmp[0];
    }


    /**
     * @param array $p_aParams Array with the user data plus the operatorSessionToken
     * @return json $result contains data from API
     */
    private function registerPlayer(array $p_aParams) {
        $username = $p_aParams['id'] . $this->getSetting('internal_brand', '_vs');
        $p_sUrl = $this->loadUrlByKey('gp_url_registration');

        $aParams = array(
            // either <userID>e<tournamentID> or only integer userID
            'externalReference' => $this->_getTournamentToken($username), // important as this is send during all GP request to us
            'userName' => $this->_getTournamentToken($username),
            'password' => $username,
            'currencyCode' => strtoupper($this->getPlayCurrency($p_aParams, $this->_hasTournamentToken())),
            'gender' => strtoupper(substr($p_aParams['sex'], 0, 1)),
            'countryCode' => $p_aParams['country'],
            'birthdate' => $p_aParams['dob'],
            'city' => $p_aParams['city'],
        );

        $result = $this->_getJsonData($p_sUrl, $aParams);
        return $result;
    }

    /**
     * @param array $p_aParams Array with the user data plus the operatorSessionToken
     * @return json $result contains data from API
     */
    private function loginPlayer(array $p_aParams) {
        $username = $p_aParams['id'] . $this->getSetting('internal_brand', '_vs');
        $p_sUrl = $this->loadUrlByKey('gp_url_session_login');

        $aParams = array(
            'userName' => $this->_getTournamentToken($username),
            'password' => $username,
            'operatorSessionToken' => $p_aParams['operatorSessionToken']
        );

        $maxBetLimit = phive('Gpr')->getMaxBetLimit(cu($p_aParams['id']));
        if($maxBetLimit){
            $aParams['maxBet'] = [
                'amount' => bcadd((string)$maxBetLimit, '0', 6),
                'currency' => $p_aParams['currency']
            ];
        }

        $result = $this->_getJsonData($p_sUrl, $aParams);
        return $result;
    }

    /**
     * @param string $configKey The config setting to fetch.
     * @return string The config setting.
     */
    private function loadUrlByKey(string $configKey): string {
        $config = $this->getSetting($configKey);
        $operator_id = '{operator-id}';
        $port = '{port}';
        if(!is_string($config)) {
            return '';
        }
        return str_replace([$operator_id, $port], [$this->getLicSetting('operator_id'), $this->getLicSetting('port')], $config);
    }
}
