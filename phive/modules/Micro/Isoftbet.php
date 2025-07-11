<?php
require_once __DIR__ . '/QuickFire.php';

class Isoftbet extends Casino
{
    const PREFIX_MOB_RC_TIMEOUT = 'mobile_rc_timeout_';
    const PREFIX_MOB_RC_PLAYTIME = 'mobile_rc_playtime_';
    const PREFIX_MOB_RC_DEVICE = 'isoftbet_device_';
    const PREFIX_MOB_RC_LANG = 'isoftbet_lang_';
    const ER01 = 'ER01';
    const ER02 = 'ER02';
    const OPERATOR = 'iSoftBet';

    private $_m_aErrors = array(
        'ER01' => 'Internal Server Error',
        'ER02' => 'Command not found',
        'ER08' => 'Playing time limit has been exceeded for a player.'
    );

    /**
     * The current method to execute
     *
     * @var string
     */
    private $_m_sMethod;

    /**
     * The actions received by the request and to execute
     *
     * @var array
     */
    private $_m_oActions = array();

    /**
     * Array with data coming from the JSON post request
     *
     * @var array
     */
    private $_m_aPostRequest;

    /**
     * Will have the user data as an array if user is found otherwise it will be false
     *
     * @var mixed bool|array
     */
    private $_m_mUserData = null;

    /**
     * Will have the game data as an array if game is found otherwise it will be false
     *
     * @var mixed bool|array
     */
    private $_m_mGameData = null;

    /**
     * The start microtime.
     * Used for logging.
     * @var int
     */
    private $_m_iStart = 0;

    /**
     * The end microtime.
     * Used for logging.
     * @var int
     */
    private $_m_iEnd = 0;

    /**
     * The execution duration.
     * Used for logging.
     * @var int
     */
    private $_m_iDuration = 0;

    /**
     * Wallet transaction ID (VS) after a bet has been inserted
     * @var int
     */
    private $_m_iWalletTxnBet = null;

    /**
     * Wallet transaction ID (VS) after a win has been inserted
     * @var int
     */
    private $_m_iWalletTxnWin = null;

    /**
     * Instance of Currencer
     *
     * @var Currencer
     */
    private $_m_oCurrencer;

    /**
     * Instance of Bonuses
     *
     * @var Bonuses
     */
    private $_m_oBonuses;

    /**
     * Instance of MicroGames
     *
     * @var MicroGames
     */
    private $_m_oMicroGames;

    /**
     * Instance of SQL
     *
     * @var SQL
     */
    private $_m_oSQL;

    /**
     * Instance of UserHandler
     *
     * @var UserHandler
     */
    private $_m_oUserHandler;

    /**
     * Instance of Localizer
     *
     * @var Localizer
     */
    private $_m_oLocalizer;

    /**
     * GP IP-addresses. Auto enabled if it has GP ip-addresses
     * @var array
     */
    private $_m_aWhitelistedGpIps = array();

    /**
     * Whitelist of ip adresses from Panda Media Ltd.
     * @var array
     */
    private $_m_aWhitelistedWalletIps = array(
        '127.0.0.1', // localhost
        '212.56.137.74', // office
        '212.56.151.141',
        '195.158.92.198', //office
        '217.168.172.44',    //box1
        '217.168.172.45',    //box2
        '217.168.172.33',    //box3
        '217.168.172.34',    //box4
        '217.168.172.50',    //box5/db
        '217.168.172.36',    //www
        '217.174.248.203',   //london
        '88.208.221.127',    //test2old
        '185.89.238.132', // melita 1
        '185.89.238.134',   //test2new
        '172.16.0.134',    //test2
        '172.16.0.32',     //melita1
        '192.168.30.33'
    );

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Inject class dependencies
     *
     * @param object $p_oDependency Instance of the dependent class
     * @return mixed Isoftbet|bool false if dependency couldn't be set
     */
    public function injectDependency($p_oDependency)
    {
        switch ($p_oDependency) {
            case $p_oDependency instanceof Currencer:
                $this->_m_oCurrencer = $p_oDependency;
                break;

            case $p_oDependency instanceof Bonuses:
                $this->_m_oBonuses = $p_oDependency;
                break;

            case $p_oDependency instanceof MicroGames:
                $this->_m_oMicroGames = $p_oDependency;
                break;

            case $p_oDependency instanceof SQL:
                $this->_m_oSQL = $p_oDependency;
                break;

            case $p_oDependency instanceof UserHandler:
                $this->_m_oUserHandler = $p_oDependency;
                break;

            case $p_oDependency instanceof Localizer:
                $this->_m_oLocalizer = $p_oDependency;
                break;

            default:
                return $this;
        }
        return $this;
    }

    /**
     * Get a prefix which is the lowercase class name which can be used for different purposes
     *
     * @return string
     */
    public function getPrefix()
    {
        return strtolower(__CLASS__);
    }

    /**
     * Strip the prefix from a string
     *
     * @param string $p_sPrefix
     * @return string
     */
    public function stripPrefix($p_sPrefix)
    {
        return str_replace(strtolower(__CLASS__), '', $p_sPrefix);
    }

    /**
     * Get a country suffix for Great Britain
     *
     * @param string $country
     * @return string
     */
    public function getSuffix($p_sCountry)
    {
        return strtoupper($p_sCountry) == 'GB' ? 'GB' : '';
    }

    /**
     * Generate a keyed hash value using the HMAC method
     *
     * @param string $p_sJson The json object
     * @param string $p_sAlgorithm The algorithm to be used. Check PHP manual for available algorithms.
     * @return string The hash_hmac
     */
    public function getHashHmac($p_sJson, $p_sAlgorithm = 'sha256', $user)
    {
        return hash_hmac($p_sAlgorithm, $p_sJson, $this->getLicSetting('secretkey', $user));
    }

    /**
     * Set the start execution time
     *
     * @param int $p_iStart
     * @return Gp
     */
    public function setStart($p_iStart)
    {
        $this->_m_iStart = $p_iStart;
        if ($this->getSetting('log_errors') === true) {
            phive()->dumpTbl('isoftbet-error-log', __METHOD__ . $this->_m_iStart );
        }

        return $this;
    }

    /**
     * Set the end execution time
     *
     * @param int $p_iEnd
     * @return Gp
     */
    protected function _setEnd($p_iEnd)
    {
        $this->_m_iEnd = $p_iEnd;
        $this->_m_iDuration = $this->_m_iEnd - $this->_m_iStart;
        return $this;
    }

    /**
     * Get the bet or win or cancel transaction ID of VS
     * @param string $p_sMethod bet|win. default empty
     * @return string
     */
    protected function _getWalletTxnId($p_sMethod = '')
    {
        if (!empty($p_sMethod)) {
            return $this->{'_m_iWalletTxn' . ucfirst($p_sMethod)};
        } else {
            switch ($this->_m_sMethod) {
                case '_bet':
                    return $this->_m_iWalletTxnBet;
                case '_win':
                    return $this->_m_iWalletTxnWin;
                // case 'cancel': => $this->_m_iWalletTxnBet and $this->_m_iWalletTxnWin are set in the foreach loop inside the cancel method by $this->_getTransactionById()
            }
        }
    }

    /**
     * Log the execution time needed to process a request received from a GP
     * @return void
     */
    protected function _logExecutionTime()
    {
        $a = array('bet','win');
        $aId = array();
        foreach($a as $key => $value){
            $id = $this->_getWalletTxnId($value);
            if(!empty($id)){
                $aId[] = $id;
            }
        }

        $insert = array(
            'duration' => $this->_m_iDuration,
            'username' => (isset($this->_m_oRequest->playerid) ? $this->_m_oRequest->playerid : null),
            'mg_id' => (isset($this->_m_mGameData['id']) ? $this->_m_mGameData['id'] : 0),
            'token' => $this->_m_mGameData['operator'],
            'method' => $this->getGpMethod(),
            'host' => gethostname()
        );

        if ($this->getSetting('log_game_replies') === true) {
            $this->_m_oSQL->insertArray('game_replies', $insert);
        }

        if ($this->getSetting('log_slow_game_replies') === true) {
            phive('MicroGames')->logSlowGameReply($this->_m_iDuration, $insert);
        }
    }

    /**
     * Whitelist game provider ip addresses
     *
     * @param array $p_aWhitelistedGpIps
     * @return object
     */
    protected function _whiteListGpIps(array $p_aWhitelistedGpIps = array())
    {
        $this->_m_aWhitelistedGpIps = $p_aWhitelistedGpIps;

        if (!isCli() && !empty($this->_m_aWhitelistedGpIps) && !in_array(remIp(),
                array_merge($this->_m_aWhitelistedGpIps, $this->_m_aWhitelistedWalletIps))) {
            $this->_logIt([__METHOD__, print_r($_SERVER,true)], __CLASS__ . '-error-ipblock', true);
            // secret key not valid
            $mResponse = array(
                'code' => 'ER01',
                'message' => 'IP-BLOCK'
            );
            return $this->_response($mResponse);
        }
        return $this;
    }



    /**
     * Execute the requested command from gaming provider
     *
     * @param string $p_sJson The json_encoded string with all data coming from gaming provider
     * @param array $p_aGetParams All the url get params received from isoftbet
     * @return mixed The headers and depending on other response params a json_encoded string, which is the answer to gaming provider
     */
    public function exec($p_sJson, array $p_aGetParams = array())
    {
        $mResponse = false;
        $this->_whiteListGpIps($this->getSetting('whitelisted_ips'));

        $this->_m_aPostRequest = json_decode($p_sJson, false);

        // check if playerid is in BOS format and extract player id part
        $this->_m_aPostRequest->playerid = $this->getUsrId($this->_m_aPostRequest->playerid);

        // check if the received secret key does exist
        if ($this->getHashHmac($p_sJson, 'sha256' , cu($this->_m_aPostRequest->playerid)) === $p_aGetParams['hash']) {


            if($this->getSetting('debug_enabled')) {
                switch ($this->_m_aPostRequest->state) {
                    case 'single':
                        if ($this->_m_aPostRequest->action->command === 'bet') {
                            $this->_logIt(['bet', 'received: ' . date('Y-m-d H:i:s'), print_r($this->_m_aPostRequest, true)], 'isoftbet-bet-error-log', true, (int)$this->_m_aPostRequest->playerid);
                        }
                        break;

                    case 'multi':
                        if ($this->_m_aPostRequest->actions[0]->command === 'bet') {
                            $this->_logIt(['bet', 'received: ' . date('Y-m-d H:i:s'), print_r($this->_m_aPostRequest, true)], 'isoftbet-bet-error-log', true, (int)$this->_m_aPostRequest->playerid);
                        }
                        break;
                }
            }

            // check if the commands requested do exist
            $this->_setActions();

            // Set the game data by the received skinid (is gameid, see document history page III)
            $this->_setGameData();

            // execute all commands
            foreach ($this->_m_oActions as $key => $oAction) {

                $this->setStart(microtime(true));
                // Update the user data befor each command
                if (isset($this->_m_aPostRequest->playerid)) {
                    $this->uid = $this->_m_aPostRequest->playerid;
                    $this->_setUserData();
                }

                $this->_m_sMethod = $sMethod = '_' . $oAction->command;
                $user = cu($this->uid);
                if (!empty($user)) {
                    $rg = rgLimits();
                    $rc_interval = $rg->getRcLimit($user)['cur_lim'];
                }
                $device = phMgetShard(self::PREFIX_MOB_RC_DEVICE, $this->uid) == 1 ? 'mobile' : 'desktop';
                $jurisdiction = licJur($user);
                if ($jurisdiction != "GB" && $this->_m_sMethod === '_bet' && $this->getRcPopup($device, $user) == 'ingame' && $rc_interval > 0 && empty(phMgetShard(self::PREFIX_MOB_RC_TIMEOUT, $this->uid))) {
                    $lang = phMgetShard(self::PREFIX_MOB_RC_LANG, $this->uid);
                    $message = lic('getRealityCheck',[$user, $lang, $this->_m_mGameData['ext_game_name']], $user)['message'];
                    // reality check popup trigger
                    return $this->_response(array(
                        'code' => 'B_08',
                        'display' => true,
                        'action' => 'buttons',
                        'message' => $message,
                        'buttons' => [
                            "buttons" => [
                                [
                                    'text' => 'Continue',
                                    'action' => 'continue'
                                ],
                                [
                                    'text' => 'History',
                                    'action' => 'history'
                                ] ,
                                [
                                    'text' => 'Quit',
                                    'action' => 'void'
                                ]
                            ]
                        ]
                    ));
                }

                // command call return either an array with errors or true on success
                $mResponse = (property_exists($oAction,
                    'parameters') ? $this->$sMethod($oAction->parameters) : $this->$sMethod());
                // phive()->dumpTbl(__CLASS__,[__METHOD__, "response: ",$oAction, $this->_m_sMethod, $mResponse]);
                if ($mResponse !== true) {
                    // some error occurred
                    break;
                }

                $this->_setEnd(microtime(true));
                $this->_logExecutionTime();
            }

        } else {

            // secret key not valid
            $mResponse = array(
                'code' => 'R_03',
                'message' => 'Invalid HMAC.'
            );
        }

        // We return error or only return the response from the last command, see page 6 manual.
        return $this->_response($mResponse);
    }

    public function _continue()
    {
        $user = cu($this->uid);
        if (!empty($user)) {
            $rg = rgLimits();
            $rc_interval = $rg->getRcLimit($user)['cur_lim'];
        }
        $this->resetRealitycheckClock($this->uid, $rc_interval);
        return true;
    }

    /**
     * Log messages to the trans_log table in the database.
     * This method only logs to the database on staging and not on production server, except if force parameter is overruled.
     *
     * @param array  $p_aData   The data that should be logged
     * @param bool   $p_bForce  Overrule config setting 'log_errors' which is only true on staging and not production
     * @param string $p_sKey    Which key to use as a reference for this message
     * @return void
     */
    protected function _logIt($p_aData, $p_sKey = 'isoftbet-error-log', $p_bForce = false, $p_iUserId = 0)
    {
        if ($this->getSetting('log_errors') === true || $p_bForce === true) {
            phive()->dumpTbl($p_sKey, implode(PHP_EOL, $p_aData), (!empty($this->_m_mUserData) ? $this->_m_mUserData : $p_iUserId));
        }
    }

    /**
     * Get the GP requested method
     * @return string
     */
    public function getGpMethod()
    {
        return $this->_m_sMethod;
    }

    private function _validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param int $p_iGameId The game ID as provided by the GP
     * @param string $p_sLang The language code
     * @param $device
     * @param bool $show_demo
     * @return string The url to open the game
     */
    private function _getUrl($p_iGameId, $p_sLang, $device, $show_demo = false)
    {
        $game = phive("MicroGames")->getGameOrOverrideByGid($p_iGameId);
        $p_iGameId = $this->stripPrefix($game['game_id']);
        $user = cuPl();
        if (isLogged()) {
            $ud = $user->data;
            $sid = uniqid() . "-{$ud['id']}";
            $sCurrency = $ud['currency'];
            $sUsername = $ud['username'];
            $sUserId = ! empty($_GET['eid']) ? $this->mkUsrId($ud['id'], $_GET['eid']) : $ud['id'];
            $money_mode = 1; // real
            $baseUrl = $this->getLicSetting('flash_play', $user);
            $rc_interval = $this->startAndGetRealityInterval($sUserId, $game['ext_game_name']);
            $jurisdiction  = licJur($user);

            if ($jurisdiction != 'GB' && $rc_interval > 0) {
                $this->startRealityCheck($sUserId, $rc_interval);
            }

            phMsetShard(self::PREFIX_MOB_RC_DEVICE, $device == 'desktop' ? 0 : 1, $sUserId);
            phMsetShard(self::PREFIX_MOB_RC_LANG, $p_sLang, $sUserId);

        } else {
            $sUserId = '';
            $sCurrency = ciso();
            $sUsername = '';
            $money_mode = 0; // fun
            $baseUrl = $this->getLicSetting('flash_play');
        }

        $lobby_url = $this->getLobbyUrl(false);
        $launch_url = $baseUrl . $this->getLicSetting('licenseid', $user) . "/$p_iGameId?lang=$p_sLang&cur=$sCurrency&mode=$money_mode&user=$sUsername&uid=$sUserId&token=$sid&lobbyUrl={$lobby_url}";

        if ($jurisdiction == 'GB' && $rc_interval > 0) {
            $launch_url .= "&rci=$rc_interval";
        }

        $this->dumpTst("isoftbet-gamelaunch-{$device}", compact('launch_url'), $sUserId ?: 0);
        return $launch_url;
    }

    public function startRealityCheck($uid, $interval)
    {
        phMsetShard(self::PREFIX_MOB_RC_PLAYTIME, time(), $uid);
        $this->resetRealitycheckClock($uid,$interval);
    }

    public function resetRealitycheckClock($uid, $interval)
    {
        phMdelShard(self::PREFIX_MOB_RC_TIMEOUT. $uid);
        phMsetShard(self::PREFIX_MOB_RC_TIMEOUT, '1', $uid, $interval * 60);
    }

    /**
     * Get the desktop game launcher URL
     *
     * @param mixed $p_mGameId The game_id from the micro_games table
     * @param string $p_sLang The language code
     * @param $game
     * @param bool $show_demo
     * @return string
     */
    public function getDepUrl($p_mGameId, $p_sLang, $game = null, $show_demo = false)
    {
        $history_link = $this->getHistoryUrl(false);
        return $this->_getUrl($p_mGameId, $p_sLang, 'desktop', $show_demo) . "&historyURL={$history_link}";
    }

    /**
     * Get the mobile game launcher URL
     *
     * @param string $p_sGameRef The game_ext_name from the micro_games table
     * @param string $p_sLang The language code
     * @param string $p_sLobbyUrl The lobby url
     * @param array $p_aGame Array with game data received from MicroGames object
     * @return string
     */
    public function getMobilePlayUrl($p_sGameRef, $p_sLang, $p_sLobbyUrl, $p_aGame, $args = [], $show_demo = false)
    {
        $user = ud();
        $uid = $user['id'];
        $reality_check_interval = phive('Casino')->startAndGetRealityInterval($uid, $p_aGame['ext_game_name']);

        if (!empty($reality_check_interval) && phive("Config")->getValue('reality-check-mobile', 'isoftbet') === 'on') {
            $siteUrl = phive()->getSiteUrl();
            $history_link = "{$siteUrl}/account/{$uid}/game-history/";
            $rcparams = "&rci={$reality_check_interval}&historyURL={$history_link}";
            return $this->_getUrl($p_aGame['game_id'], $p_sLang, 'mobile') . $rcparams;
        }
        return $this->_getUrl($p_aGame['game_id'], $p_sLang, 'mobile');
    }

    /**
     * This is the first call of every session. Requires token from the Game Start Communication Process.
     * Required parameters are mandatory to start Game Client.
     *
     * @param object An json object with the parameters received from gaming provider
     * @return bool true on success
     */
    private function _init(stdClass $p_oParameters)
    {
        return true;
    }

    /**
     * Gets current Player’s balance.
     * @return bool true on success
     */
    private function _balance()
    {
        return true;
    }

    /**
     *  Process a player bet
     *
     * @param object An json object with the parameters received from gaming provider
     * @return bool|array true on success or array with error details
     */
    private function _bet(stdClass $p_oParameters)
    {

        $iAmount = $p_oParameters->amount;
        $sRoundId = (ctype_digit($p_oParameters->roundid) ? (int)$p_oParameters->roundid : 0);

        // prefix the transaction ID to prevent collision
        $sTransactionId = $this->getPrefix() . $p_oParameters->transactionid;
        // $sSessionKey look like "user:[<uid>]:sha1(<gp_prefix_uid_txnID>)" eg.: 'user:[5207828]:fgdhjg5jhgkj4gkjh24gjh4gh4jg4jhbtnmnghfghdd'
        $sSessionKey = mKey($this->_m_mUserData['id'], sha1($this->getPrefix() . '_' . $this->_m_mUserData['id'] . '_' . $p_oParameters->transactionid));

        // has the transaction been cancelled before
        if ($this->_isCancelled($sTransactionId, 'bets')) {
            return array(
                'code' => 'B_05',
                'message' => 'Transaction has been cancelled',
                'display' => false,
                'action' => 'continue'
            );
        }

        // check if we have already a bet with the same transaction ID
        $result = $this->_getTransactionById($sTransactionId, 'bets');
        if (!empty($result)) {
            phMdel($sSessionKey);
            if ($iAmount == $result['amount']) {
                return true;
            } else {
                return array(
                    'code' => 'B_04',
                    'message' => 'Duplicate Transaction Id',
                    'display' => false,
                    'action' => 'continue'
                );
            }
        }

        if (!empty($iAmount)) {

            $balance = $this->_getBalance();
            if ($balance <= 0) {
                return array(
                    'code' => 'B_03',
                    'message' => t('insufficient.funds'),
                    'display' => true,
                    'action' => 'continue'
                );
            }

            $jp_contrib = (!empty($p_oParameters->jpc) ? $p_oParameters->jpc : 0);
            $balance = $this->lgaMobileBalance($this->_m_mUserData, $this->_m_mGameData['ext_game_name'], $balance, $this->_m_mGameData['device_type'], $iAmount);
            if ($balance < $iAmount) {
                return array(
                    'code' => 'B_03',
                    'message' => 'Insufficient Funds',
                    'display' => false,
                    'action' => 'void'
                );
            }

            $oSessionBet = json_decode(phMget($sSessionKey), false);

            if(!empty($oSessionBet)){
                // we had this request before but insertBet failed
                return $this->_insertBet(
                    $sSessionKey,
                    $sRoundId,
                    $sTransactionId,
                    $iAmount,
                    $jp_contrib,
                    $oSessionBet->bonus_bet,
                    $oSessionBet->balance
                );
            } else {
                // $this->bonus_bet => is created in playChgBalance
                // normally on 1st bet request we first debit users:cash_balance and than we insert the bet
                // if insertBet failed the above code will be executed instead and the users:cash_balance isnt debited
                // multiple times because of retries where the bet is not found.
                $balance = $this->playChgBalance($this->_m_mUserData, "-$iAmount", $sRoundId, 1);
                if ($balance === false) {
                    return array(
                        'code' => 'B_03',
                        'message' => 'Insufficient Funds',
                        'display' => false,
                        'action' => 'void'
                    );
                }

                return $this->_insertBet(
                    $sSessionKey,
                    $sRoundId,
                    $sTransactionId,
                    $iAmount,
                    $jp_contrib,
                    (empty($this->bonus_bet) ? 0 : 1),
                    $balance
                );
            }

        }

        return true;
    }

    private function _insertBet($sSessionKey, $sRoundId, $sTransactionId, $iAmount, $jp_contrib, $bonus_bet, $balance){
        $GLOBALS['mg_id'] = $sTransactionId;
        // we register to redis so when request comes again before insert happened we prevent double or more debits on cash_balance user
        phMset($sSessionKey, json_encode(array('bonus_bet' => $bonus_bet, 'balance' => $balance)), 300);
        $iBetTxnId = $this->insertBet($this->_m_mUserData, $this->_m_mGameData, $sRoundId, $sTransactionId, $iAmount, $jp_contrib, $bonus_bet, $balance);
        if ($iBetTxnId === false){
            // Check for concurrent bet requests.
            $bet = $this->_getTransactionById($sTransactionId, 'bets');
            if (!empty($bet) && ($bet['amount'] == $iAmount)) {
                $this->_m_iWalletTxnBet = $bet['id'];
                $this->dumpTst("isoftbet-warning-idempotent-bet", compact('sTransactionId'), $this->_m_mUserData['id'] ?? 0);
                return true;
            }
            return $this->_dbError();
        }
        $this->_m_iWalletTxnBet = $iBetTxnId;
        $this->betHandleBonuses($this->_m_mUserData, $this->_m_mGameData, $iAmount, $balance, $bonus_bet, 0, $sTransactionId);
        return true;
    }

    /**
     *  Do a player win and get the player balance after it
     *
     * @param object An json object with the parameters received from gaming provider
     * @return bool|array true on success or array with error details
     */
    private function _win(stdClass $p_oParameters)
    {

        $iAmount = $p_oParameters->amount;
        $sRoundId = (ctype_digit($p_oParameters->roundid) ? (int)$p_oParameters->roundid : 0);
        $iAwardType = (($p_oParameters->jpw > 0) ? 4 : 2);

        // prefix the transaction ID to prevent collision
        $sTransactionId = $this->getPrefix() . $p_oParameters->transactionid;

        // check if we have already a win with the same transaction ID
        $result = $this->_getTransactionById($sTransactionId, 'wins');

        if (!empty($result)) {
            if ($iAmount == $result['amount']) {
                return true;
            } else {
                return array(
                    'code' => 'W_06',
                    'message' => 'Duplicate Transaction Id',
                    'display' => false,
                    'action' => 'continue'
                );
            }
        }

        if (!empty($iAmount)) {
            $balance = $this->_getBalance();
            // $sRoundId set to force 0 because the are sending a hash containing alfanumeric chars. Database only except integer
            $bonus_bet = empty($this->bonus_bet) ? 0 : 1;
            $this->_m_iWalletTxnWin = $this->insertWin($this->_m_mUserData, $this->_m_mGameData, $balance, $sRoundId, $iAmount, $bonus_bet, $sTransactionId, $iAwardType);

            if ($this->_m_iWalletTxnW === false) {
                return $this->_dbError();
            }
            $balance = $this->playChgBalance($this->_m_mUserData, $iAmount, $sRoundId, 2);
            $this->handlePriorFail($this->_m_mUserData, $sTransactionId, $balance, $iAmount);
        }

        return true;
    }

    /**
     * Cancel a player bet or a win by transaction ID
     * Note: transaction ID is unique for each command in a multi-state call (confirmed with Isoftbet).
     *
     * @param object An json object with the parameters received from gaming provider
     * @return bool|array true on success or array with error details
     */
    private function _cancel(stdClass $p_oParameters)
    {

        $sTransactionId = $this->getPrefix() . $p_oParameters->transactionid;
        $aTables = array('bets', 'wins');
        $bHasTransaction = false;

        foreach ($aTables as $sTable) {

            // has the transaction been cancelled before
            if ($this->_isCancelled($sTransactionId, $sTable)) {
                return true;
            }

            // does this transactionid exists in the db?
            $aResult = $this->_getTransactionById($sTransactionId, $sTable);

            if (!empty($aResult)) {

                // we found the transactionid
                $bHasTransaction = true;

                // do the received transaction details match with the transaction details from the db
                if (sha1($aResult['amount'] . $aResult['mg_id']) === sha1($p_oParameters->amount . $sTransactionId)) {

                    if ($sTable == 'bets') {
                        $iType = 7;
                        $amount = $aResult['amount'];
                    } else {
                        $iType = 1;
                        $amount = -$aResult['amount'];
                    }

                    $mBalance = $this->playChgBalance($this->_m_mUserData, $amount, $aResult['trans_id'], $iType);
                    if ($this->doRollbackUpdate($sTransactionId, $sTable, $mBalance, $amount) === false) {
                        return $this->_dbError();
                    } else {
                        return true;
                    }
                } else {
                    return array(
                        'code' => 'C_05',
                        'message' => 'Transaction details do not match.'
                    );
                }
            }
        }

        // transaction was not found in foreach loop
        if ($bHasTransaction === false) {
            return array(
                'code' => 'C_03',
                'message' => 'Invalid cancel, Transaction does not exist.'
            );
        }
        return true;
    }

    /**
     * This method is used to inform the operator about the end session state.
     * It passes all necessary information about a session to the operator which should
     * then internally close the session and return the successful result of this operation.
     *
     * @param object An json object with the parameters received from gaming provider
     * @return bool|array true on success or array with error details
     */
    private function _end(stdClass $p_oParameters)
    {
        return true;
    }

    /**
     * Set a db error response
     */
    private function _dbError()
    {
        return array(
            'code' => self::ER01,
            'message' => $this->_m_aErrors[self::ER01],
            'display' => false,
            'action' => 'void'
        );
    }

    /**
     * Get a bet or win data array by the transaction ID
     *
     * @param int $p_iTransactionId The transaction ID
     * @param string $p_sTable From the bets|wins table
     * @return mixed array|bool False if no entry found
     */
    private function _getTransactionById($p_iTransactionId, $p_sTable)
    {
        return $this->getBetByMgId($p_iTransactionId, $p_sTable, 'mg_id', $this->_m_mUserData['id']);
    }

    /**
     * Check if a transaction has been cancelled by transaction ID
     *
     * @param int $p_iTransactionId The transaction ID
     * @param string $p_sTable From the bets|wins table
     * @return bool true if was cancelled before
     */
    private function _isCancelled($p_iTransactionId, $p_sTable)
    {
        $mData = $this->getBetByMgId($p_iTransactionId . 'ref', $p_sTable, 'mg_id', $this->_m_mUserData['id']);
        return !empty($mData);
    }

    /**
     * Set the player data array by the player ID received from gp
     *
     * @return mixed
     */
    private function _setUserData()
    {
        $this->_m_mUserData = ud((int)$this->_m_aPostRequest->playerid);

        if (empty($this->_m_mUserData)) {
            return $this->_response(array(
                'code' => 'R_09',
                'message' => 'Player not found'
            ));
        }
        return true;
    }

    public function _dialog()
    {
        return $this->_continue();
    }
    /**
     * Set the game data array by the game ID received from isoftbet
     *
     */
    private function _setGameData()
    {
        if ($this->_m_mGameData === null) {
            $device = phMgetShard(self::PREFIX_MOB_RC_DEVICE, (int)$this->_m_aPostRequest->playerid);
            $this->_m_mGameData = phive('MicroGames')->getByGameId($this->getPrefix()
                . (int)$this->_m_aPostRequest->skinid, (int)$device, $this->_m_aPostRequest->playerid );

            if (empty($this->_m_mGameData)) {
                // the game is missing ???
                return $this->_response(array(
                    'code'    => 'R_10',
                    'message' => 'Game is not configured for this licensee.'
                ));
            }
            $this->new_token['game_ref'] = $this->_m_mGameData['ext_game_name'];
        }
    }

    private function _setActions()
    {

        switch ($this->_m_aPostRequest->state) {
            case 'single':

                array_push($this->_m_oActions, $this->_m_aPostRequest->action);
                break;

            case 'multi':

                foreach ($this->_m_aPostRequest->actions as $oAction) {
                    array_push($this->_m_oActions, $oAction);
                }
                break;
        }

        // if one of the commands do not exist return an error
        foreach ($this->_m_oActions as $key => $oAction) {

            if (!method_exists($this, '_' . $oAction->command)) {
                return $this->_response(array(
                    'code' => self::ER02,
                    'message' => $this->_m_aErrors[self::ER02]
                ));
            }
        }
    }

    /**
     * Set response headers
     */
    private function _setResponseHeaders()
    {
        // we always send a 200 response and pass any errors into the message
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json; charset=utf-8');
    }

    protected function _getBalance()
    {
        if (empty($this->t_entry)) {
            $real_balance = phive('UserHandler')->getFreshAttr($this->_m_mUserData['id'], 'cash_balance');

            if(empty($this->_m_mGameData)){
                // we log if game data could not be set as we need it to add the bonus balance to real balance
                phive()->dumpTbl(
                    'isoftbet-missing-game-data', [
                    'Game data for ' . $this->_m_aPostRequest->skinid . ' could not be set!',
                    'Method: ' . $this->getGpMethod()]);
            }
            $bonus_balance = empty($this->_m_mGameData['ext_game_name']) ? 0 : phive('Bonuses')->getBalanceByRef($this->_m_mGameData['ext_game_name'],
                $this->_m_mUserData['id']);
            return $real_balance + $bonus_balance;
        } else {
            return $this->tEntryBalance();
        }
    }
    /**
     * Send a response to iSoftBet
     *
     * @param mixed $p_mResponse true if command was executed succesfully or an array with the error details
     * @return mixed The headers and depending on other response params a json_encoded string, which is the answer to isoftbet
     */
    private function _response($p_mResponse)
    {

        $aResponse = array();

        $this->_setResponseHeaders();

        if ($p_mResponse !== true) {

            // command is not successfully executed as it is not boolean true
            $p_mResponse['status'] = 'error';

            // action response. Options are: continue|void => manual page 21
            if (!isset($p_mResponse['action'])) {
                $p_mResponse['action'] = 'continue';
            }

            // do we display the message to the user. Options are: true|false
            if (!isset($p_mResponse['display'])) {
                $p_mResponse['display'] = false;
            }

            $aResponse = $p_mResponse;

        } else {

            // refresh with the latest user data
            if (isset($this->_m_aPostRequest->playerid)) {
                $this->_setUserData();
            }

            // command is successfully executed as it is boolean true
            $aResponse['status'] = 'success';

            // in each response balance has to be returned
            $aResponse['balance'] = $this->_getBalance();
            $aResponse['currency'] = $this->_m_mUserData['currency'];

            if ($this->_m_sMethod === '_init') {
                // the only command requested with extra response params
                $aResponse['playerid'] = $this->_m_mUserData['id'];
                $aResponse['sessionid'] = '';
            }
        }
        echo json_encode($aResponse);


        $this->_setEnd(microtime(true));

        $log = [
            'response' => $aResponse,
            'request' => $this->_m_aPostRequest,
            'actions' => $this->_m_oActions,
            'duration' => $this->_m_iDuration
        ];
        $this->dumpTst("isoftbet-response-" . ltrim($this->_m_sMethod ?: '', '_'), $log, $this->_m_aPostRequest->playerid ?? 0);

        die();
    }

    /**
     * This function parses jackpots read from iSoftBet XML file link defined in config
     *
     * @return array
     */
    public function parseJackpots(): array
    {
        $validatedXMLStructure = false;
        $parsedJackpots = [];

        $xmlConfig = $this->getAllJurSettingsByKey('jp_url');
        $options = [
            "http" => [
                "timeout" => 5 // timeout in seconds
            ],
            "socket" => [
                "connect_timeout" => 5 // Connection timeout in seconds
            ]
        ];
        $context = stream_context_create($options);
        foreach ($xmlConfig as $licenseCode => $xmlFileUrl) {
            $xmlContent = @file_get_contents($xmlFileUrl, false, $context);
            if ($xmlContent !== false) {
                $jackpots = json_decode(json_encode(simplexml_load_string($xmlContent)), true);
                foreach ($jackpots as $jackpot) {

                    foreach ($jackpot as $jpGame) {
                        if(!$validatedXMLStructure){ // we have not yet validated any jackpot XML
                            $validatedXMLStructure = $this->validateJackpotXML($jpGame);

                            if(!$validatedXMLStructure){ // one or more XML elements did not match the structure we are expecting
                                $this->_logIt([__METHOD__, "There was a change in the {$this->getPrefix()} jackpot XML structure, exiting."]);
                                return [];
                            }
                        }

                        $game = phive("MicroGames")->getByGameId("{$this->getPrefix()}{$jpGame['id']}");
                        if (empty($game)) {
                            continue;
                        }

                        $parsedJackpots[] = [
                            'game_id' => $this->getPrefix() . $jpGame['id'],
                            'jurisdiction' => $licenseCode,
                            'jp_value' => (float)$this->getJpValue($jpGame['Jackpotvalue']['level']['level']) * 100,
                            'jp_id' => $jpGame['id'],
                            'jp_name' => $jpGame['name'],
                            'module_id' => $this->getPrefix() . substr($jpGame['id'], 2),
                            'network' => $this->getPrefix(),
                            'currency' => $jpGame['currency'],
                            'local' => 0,
                        ];
                    }
                }
            } else {
                echo("There was an error retrieving the XML data.\n");
            }
        }

        return $parsedJackpots;
    }

    /**
     * This function checks if the current element in the array is the jackpot value we want or not. If not then it's an
     * array, and it will get the last element from it.
     *
     * @param $innerLevel
     * @return string
     */
    private function getJpValue($innerLevel): string
    {
        if(!is_array($innerLevel)){
            return $innerLevel;
        }

        return end($innerLevel);
    }

    /**
     * This function validates jackpot XML element names based on the names we are expecting when this feature was implemented.
     *
     * @param $jackpotGame
     * @return bool
     */
    private function validateJackpotXML($jackpotGame): bool
    {
        $jpGameXMLElements = ['id', 'name', 'operatorId', 'coinValue', 'currency', 'Jackpotvalue'];
        foreach ($jackpotGame as $key => $value){
            if (!in_array($key, $jpGameXMLElements)){
                return false;
            }
        }

        if(!array_key_exists('level', $jackpotGame['Jackpotvalue'])){
            return false;
        }

        return true;
    }
}

/*
// url to import: domain-xxxx/diamondbet/soap/isoftbet.php?import=0&activate=1&bg=1
if(isset($_GET['import'])){
  $bUseDefaultBkg = ((isset($_GET['bg']) && $_GET['bg'] == 0) ? false : true);
  $iActive = ((isset($_GET['activate']) && $_GET['activate'] == 0) ? 0 : 1);
  $bImport = ((isset($_GET['import']) && $_GET['import'] == 1) ? true : false);
  $aIds = array();
  if(isset($_GET['ids'])){
    if(strpos($_GET['ids'], ',') === false){
      if(ctype_digit($_GET['ids'])){
        $aIds[] = $_GET['ids'];
      }
    } else {
      $aIds = explode(',',$_GET['ids']);
      $aIds = array_filter($aIds, 'ctype_digit');
    }
  }
  $oISoftBet->importNewGames($bUseDefaultBkg, $iActive, $bImport, $aIds);
}

// url for report: /diamondbet/soap/isoftbet.php?report&playerid=xxxx&date_from=xxxxx&date_to=xxxxx&operator=xxxxx
if(isset($_GET['report'])){

  if(ctype_digit($_GET['playerid'])){

    $sDateStart = $_GET['date_from'];
    $sDateEnd = $_GET['date_to'];
    $sOperator = (ctype_alpha($_GET['operator']) ? $_GET['operator'] : '');

    $oISoftBet->getReport($_GET['playerid'], $sOperator, $sDateStart, $sDateEnd);
  } else {
    die('Invalid user ID!');
  }
}
*/
