<?php

require_once __DIR__ . '/Gp.php';

class Ganapati extends Gp
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
    protected $_m_bConfirmFrbBet = false; // Ganapat

    /**
     * Check if incoming win request has a valid bet transaction.
     * Note: this is NOT for freespins but for real money play
     * IMPORTANT: we can not set to true as the game will hang as soon Skywind starts an ingame freespins one by one.
     * eg bets/wins with amount 0 which our system does not accept if its not a freespin given by us.
     *
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
    private $_m_aErrors = [
         'ER01' => [
            'responsecode' => 500,
            'status' => 'SERVER_ERROR',
            'return' => 'default',
            'code' => 101,
            'message' => 'Internal Server Error.'
        ],
        'ER04' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_WAS_CANCELLED',
            'return' => 'default',
            'code' => 200,
            'message' => 'Bet transaction ID has been cancelled previously.'
        ],
        'ER05' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => true,
            'code' => 1,
            'message' => 'Duplicate Transaction ID.'
        ],
        'ER06' => [
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => 201,
            'message' => 'Insufficient funds'
        ],
        'ER07' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_MISMATCH',
            'return' => 'default',
            'code' => 200,
            'message' => 'Transaction details do not match.'
        ],
        'ER08' => [
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 200,
            'message' => 'Invalid refund, transaction ID does not exist.'
        ],
        'ER09' => [
            'responsecode' => 200,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => 200,
            'message' => 'Player not found.'
        ],
        'ER10' => [
            'responsecode' => 404,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => 200,
            'message' => 'Game is not found.'
        ],
        'ER11' => [
            'responsecode' => 200,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => 103,
            'message' => 'Session Expired'
        ],
        'ER12' => [
            'responsecode' => 200,
            'status' => 'FREESPIN_NO_REMAINING',
            'return' => 'default',
            'code' => -5,
            'message' => 'No freespins remaining.'
        ],
        'ER15' => [
            'responsecode' => 403,
            'status' => 'FORBIDDEN',
            'return' => 'default',
            'code' => 200,
            'message' => 'IP Address forbidden.'
        ],
        'ER16' => [
            'responsecode' => 400,
            'status' => 'REQUEST_INVALID',
            'return' => 'default',
            'code' => 102,
            'message' => 'Invalid input.'
        ],
        'ER18' => [
            'responsecode' => 200,
            'status' => 'IDEMPOTENCE',
            'return' => 'default',
            'code' => 1,
            'message' => 'IDEMPOTENCE'
        ],
                'ER23' => [
            'responsecode' => 200,
            'status' => 'INSERT_FAILED',
            'return' => 'default',
            'code' => 200,
            'message' => 'The insert failed!'
        ],
        'ER24' => [
            'responsecode' => 200,
            'status' => 'UPDATE_FAILED',
            'return' => 'default',
            'code' => 200,
            'message' => 'The update failed!'
        ],
        'ER25' => [
            'responsecode' => 200,
            'status' => 'PLAYER_BLOCKED',
            'return' => 'default',
            'code' => 200,
            'message' => 'Player is blocked.'
        ],
        'ER26' => [
            'responsecode' => 200,
            'status' => 'PLAYER_BANNED',
            'return' => 'default',
            'code' => 200,
            'message' => 'Player is banned.'
        ],
        'ER27' => [
            'responsecode' => 200,
            'status' => 'INVALID_USER_ID',
            'return' => 'default',
            'code' => 200,
            'message' => 'Session player ID doesn\'t match request Player ID.'
        ],
        'ER28' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_ALREADY_CANCELLED',
            'return' => true,
            'code' => 200,
            'message' => 'Transaction ID has been cancelled already.'
        ],
        'ER39' => [
            'responsecode' => 200,
            'status' => 'IDEMPOTENCE',
            'return' => 'default',
            'code' => 1,
            'message' => 'Duplicate Transaction ID with same amount does exist already.'
        ],


    ];

    private $_m_aMethodsMappedApi = [
        'createfrbGanapati' => '_createFrb',
        'insertGame' => '_insertGame',
        'authenticate' => '_init',
        'fetchBalance' => '_balance',
        'withdraw' => '_bet',
        'deposit' => '_win',
        'rollback' => '_cancel'
    ];

    private $_m_sToken = '';

    private $b_is_multi = false;
    
    /*
    * $s_method: string method called by gp
    * $s_message: JSON formatted with the request body
    * $s_token: token received from GP
    * @return bool: if request hash matches with request token
    */
    public function checkMessageIntegrity($s_method='', $s_message="", $s_token="")
    {
        $s_token = isset($_SERVER['HTTP_HASH']) ? $_SERVER['HTTP_HASH'] : "";
        $s_base = $this->getGpMethod() . $this->_m_sInputStream;
        $hash = hash_hmac('sha1', $s_base, $this->getSetting('HMAC_digest'));

        return $hash === $s_token;
    }
    // The request auth header
    public function getAuthHeader($s_method='', $s_message="")
    {
        $s_base = $s_method.$s_message;
        $hash = hash_hmac('sha1', $s_base, $this->getSetting('HMAC_digest'));
        return 'hash: '.$hash;
    }

    /**
     * Set the defaults
     * Separate function so it can be called also from the classes that extend TestGp class
     *
     * @return Gp
     */
    public function setDefaults()
    {
        $this
            ->_mapGpMethods($this->_m_aMethodsMappedApi)
            ->_overruleErrors($this->_m_aErrors)
            ->_supportInhouseFrb($this->_m_sGpName)
            ->_whiteListGpIps($this->getSetting('whitelisted_ips'))
            ->_checkDeclaredProperties()
            ->_setWalletActions();

        return $this;
    }

    /**
     * Pre process data received from GP
     *
     * @return object
     */
    public function preProcess()
    {
        // prepare the data so it can be executed
        $this->setDefaults();
        
        $this->preProcessParams();

        $a_request = [
            'playerid' => $this->preProcessUser(),
            'skinid' => $this->preProcessSkinId(),
            'action' => $this->preProcessAction(),
            'state' => $this->b_is_multi ? 'multi' : 'single',
            'freespin' => $this->preProcessFreeSpin()
        ];
        if ($this->b_is_multi) { // weird thing we have to do here to make it work with GP.php
            $a_request['actions'] = $a_request['action'];
            $a_request['action'] = null;
        }
        // filter out NULL values
        $a_request = array_filter($a_request, function ($value) {
            return !is_null($value);
        });
        
        // the json_decode-encode is to transform the inner array in an object
        $this->_m_oRequest = json_decode(json_encode($a_request, false));
       
        $this->_logIt([__METHOD__, print_r($this->_m_oRequest, true)]);
        return $this;
    }


    /**
     * Execute the requested command from gaming provider
     * @return void
     */
    public function exec()
    {
        if (!$this->checkMessageIntegrity()) {
            // secret key not valid
            $this->_logIt([__METHOD__, print_r($_SERVER, true), 'Check the HMAC digest: '.$this->getSetting('HMAC_digest')]);
            $this->_response($this->_getError(self::ER03));
        }

        $mResponse = false;
        // check if the commands requested do exist
        $this->_setActions();

        // // Set the game data by the received skinid (is gameid)
        if (isset($this->_m_oRequest->skinid)) {
            $this->_setGameData();
        }
        // execute all commands
        foreach ($this->_getActions() as $key => $oAction) {
            $mResponse = $this->runAction($oAction);
            
            if ($mResponse !== true) {
                break;
            }
        }

        $this->_response($mResponse);
    }

    protected function _response($p_mResponse)
    {
        $aResp =  [];
        $sGpMethod = $this->getGpMethod();
        $aUserData = $this->_getUserData();

        if ($p_mResponse === true || (in_array($sGpMethod, ['withdraw', 'deposit']) && $p_mResponse['message'] === 'IDEMPOTENCE')) {
            $aResp = $this->buildResponseOk($sGpMethod, $aUserData);
        } else {
            $aResp = $this->buildResponseError($p_mResponse, $aUserData);
        }
        $result = json_encode($aResp);

        header($this->getAuthHeader($sGpMethod.$result));
        $this->_setResponseHeaders($p_mResponse);

        echo $result;

        $this->_logIt([__METHOD__, $sGpMethod . $result, true]);
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $p_mGameId The game_ext_name from the microgames table without the prefix so basically the game ID as provided by the GP
     * @param string $p_sLang
     * @param string $p_sTarget The target. desktop|mobile
     * @param bool $show_demo
     * @return string The url to open the game
     */
    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        $this->initCommonSettingsForUrl();

        $is_logged = isLogged();
        $user = cu();

        if(!empty($user)) {
            $uid = empty($_SESSION['token_uid']) ? $user->getId() : $_SESSION['token_uid'];
            $ud = $user->getData();

            $token = $this->getGuidv4($uid);
            $this->toSession($token, $uid, $p_mGameId, $p_sTarget);
        }

        $url_params = [
            'game'          => $p_mGameId,
            'mode'          => $is_logged ? 'real' : 'fun',
            'operator'      => $this->getLicSetting('operator', $uid),
            'lobbyURL'      => $this->getLobbyUrl(false, $p_sLang, $p_sTarget),
            'currency'      => $is_logged ? $ud['currency'] : ciso(),
            'locale'        => $p_sLang,
            'launchToken'   => $is_logged ? $token : null
        ];

        $launch_url = $this->getLaunchUrl($url_params);
        $this->_logIt([__METHOD__, $launch_url]);
        return $launch_url;
    }

    private function runAction($oAction)
    {
        if (isset($this->_m_oRequest->playerid) && !is_array($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }

        $sMethod = $oAction->command;
        $this->_setWalletMethod($sMethod);

        // command call return either an array with errors or true on success
        if (!property_exists($oAction, 'parameters')) {
            return $this->$sMethod();
        }
        return  $this->$sMethod($oAction->parameters);
    }

    private function buildResponseOk($sGpMethod, $aUserData)
    {
        $response_body = [];

        switch ($sGpMethod) {
            case 'authenticate':
                $response_body = [
                    'balance' => $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS),
                    'currency' => strtoupper($this->getPlayCurrency($aUserData)),
                    'playerId' => $aUserData['id'],
                    'sessionId' => base64_encode($this->_m_sToken),
                    'account' => new stdClass()
                ];
                break;

            case 'rollback':
            case 'deposit':
            case 'withdraw':
            case 'fetchBalance':

                $response_body = [
                    'balance' => $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS),
                    'currency' => strtoupper($this->getPlayCurrency($aUserData))
                ];
                break;
            default:
                break;
        }

        return $response_body;
    }

    private function buildResponseError($p_mResponse, $aUserData)
    {
        return [
            'errorCode' => $p_mResponse['code'],
            'description' => $p_mResponse['message'],
            'details' => [
                'balance' => $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS, self::COINAGE_UNITS),
                'currency' => strtoupper($this->getPlayCurrency($aUserData))
            ]
        ];
    }



    /*
    * Preprocess the method
    * @return String casino method - as received in the request
    */
    protected function preProcessCasinoMethod()
    {
        $gp_a_params = $this->getGpParams();
        $s_casino_method = (isset($_GET['action']) ? $_GET['action'] : null);
        
        if (empty($s_casino_method)) {
            // method to execute not found
            $this->_setResponseHeaders($this->_getError(self::ER02));
            $this->_logIt([__METHOD__, 'method not found']);
            die();
        } else {
            $this->_setGpMethod($s_casino_method);
        }

        return $s_casino_method;
    }

    /**
     * Reads received params
     *
     * @return object
     */
    protected function preProcessParams()
    {
        $o_data = json_decode($this->_m_sInputStream, false);
        $this->_setGpParams($o_data);
        $this->_logIt([__METHOD__, print_r($o_data, true)]);

        if ($o_data === null) {
            // request is unknown
            $this->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }
        $this->preProcessCasinoMethod();
        return $o_data;
    }

    /**
     * Pre process user id
     * We receive the token stored in redis and we extract the game, session and userId information
     * @return int User id
     */
    protected function preProcessUser()
    {
        $o_gp_params = $this->getGpParams();
        $s_method = $this->getGpMethod();
        // Get user from partner identifier provided by gp
        if ($s_method === 'authenticate' && isset($o_gp_params->launchToken)) {
            $this->_m_sToken = $o_gp_params->launchToken; // parameter sended with launch URL
            $a_json = $this->fromSession($o_gp_params->launchToken);// extract info

            $this->_s_gameId = $a_json->gameid;
            $this->_s_sessionId = $a_json->sessionid;
            $this->_i_userId = $a_json->userid;
        } elseif ($s_method === 'rollback') { // probably the session died and this is a call in the future
            $this->_s_gameId = $o_gp_params->game;
            $this->_s_sessionId = $o_gp_params->sessionId;
            $this->_i_userId = $o_gp_params->playerId;
        } else {
            // Get the token as stored in redis
            $this->_m_sToken = isset($o_gp_params->sessionId) ? base64_decode($o_gp_params->sessionId) : null;
            $a_json = $this->fromSession($this->_m_sToken); // extract info
            $this->_s_gameId = $a_json->gameid;
            $this->_s_sessionId = $a_json->sessionid;
            $this->_i_userId = $a_json->userid;
        }

        if (empty($this->_i_userId) || is_null($this->_i_userId)) {
            $this->_setResponseHeaders($this->_getError(self::ER09));
        }

        return $this->_i_userId ;
    }

    /**
     * Pre process Game id "skinid"
     *
     * @return int GameId
     */
    protected function preProcessSkinId()
    {
        $o_gp_params = $this->getGpParams();
        if (isset($o_gp_params->skinid)) {
            return $o_gp_params->skinid;
        }
        return $this->_s_gameId;
    }

    /**
     * Pre process action
     *
     * @return String action or NULL
     */
    protected function preProcessAction()
    {
        $o_gp_params = $this->getGpParams();
        $s_method = $this->getGpMethod();
        $s_command = $this->getWalletMethodByGpMethod($s_method);

        // They send gameround as String, but we want an int here
        if (isset($o_gp_params->gameRound)) {
            $o_gp_params->gameRound = (int)  str_replace('-', '', $o_gp_params->gameRound);
        }

        $a_action = $this->getAction($s_method, $o_gp_params, $s_command);

        return $a_action;
    }

    private function getActionApplyTransaction($o_gp_params, $s_command)
    {
        // phive()->dumpTbl('ganapati_transaction_'.$s_command, $o_gp_params, $this->_i_userId);
        // The unique identifier sended by ganapati is gameRound, they also send transaction but is redundant
        // and they don't send it when it's a deposit so gameRound is the good one
        $a_action = ['command' => $s_command, 'parameters' => []];
        if (isset($o_gp_params->gameRound)) {
            $a_action['parameters']['transactionid'] = $o_gp_params->transactionId;
            $a_action['parameters']['roundid'] = $o_gp_params->gameRound;
        }
        if (isset($o_gp_params->amount)) {
            $a_action['parameters']['amount'] = $this->convertFromToCoinage($o_gp_params->amount, self::COINAGE_UNITS, self::COINAGE_CENTS);
        }
        // Jackpot processing
        $bIsJackpot = $this->isJackpot();
        if ($bIsJackpot && $s_command == 'withdraw') { // if it's a bet then is a contribution
            $a_action['parameters']['jpc'] = $o_gp_params->extra->jackpot->contributionAmount;
        } elseif ($bIsJackpot && $s_command == 'deposit') { // if it's a win then it's a jackpot win
            $a_action['parameters']['jpw'] = $o_gp_params->extra->jackpot->winAmount;
        }

        return $a_action;
    }

    /*
    *  @returns array of rollback actions to execute based on the round id
    */
    private function getActionRollback($o_gp_params, $s_command)
    {
        // In case this is a rollback we need to cancell all transactions associated with a round,
        // it could be multiple win transactions associated with a game round, so we perform an action per each transaction
        // Ganapati doesn't send the amount parameter because it can be related to multiple transaction, but it's mandatory for us (per transaction)
        $sPrefixedGame = $this->getGamePrefix() . $o_gp_params->game;
        $s_query = "SELECT w.mg_id,w.amount FROM wins w WHERE w.trans_id = $o_gp_params->gameRound AND w.user_id = $o_gp_params->playerId AND w.game_ref = '$sPrefixedGame'";
        $a_transactionIds = phive('SQL')->sh($o_gp_params->playerId)->loadArray($s_query);
        $a_action = []; // we return an array of actions
        $this->b_is_multi = true; // set parent GP class to know that has to exec multiple actions
        
        foreach ($a_transactionIds as $a_transaction) {
            $a_action[] = [
                'command' => $s_command,
                'parameters' => [
                    'transactionid' => explode('_', $a_transaction['mg_id'])[1],
                    'amount' => $this->convertFromToCoinage($a_transaction['amount'], self::COINAGE_CENTS, self::COINAGE_CENTS)
                ]
            ];
        }
        
        // we need the bet transactionId as we need the amount parameter to match the one we have stored
        $s_query = "SELECT b.mg_id,b.amount FROM bets b WHERE b.trans_id = $o_gp_params->gameRound AND b.user_id = $o_gp_params->playerId AND b.game_ref = '$sPrefixedGame'";
        $a_transaction = phive('SQL')->sh($o_gp_params->playerId)->loadAssoc($s_query);
        $a_action[] = [
            'command' => $s_command,
            'parameters' => [
                'transactionid' => explode('_', $a_transaction['mg_id'])[1],
                'amount' => $this->convertFromToCoinage($a_transaction['amount'], self::COINAGE_CENTS, self::COINAGE_CENTS)
            ]
        ];
        return $a_action;
    }

    private function getAction($s_method, $o_gp_params, $s_command)
    {
        $action = [];

        if (in_array($s_method, ['authenticate', 'fetchBalance'])) {
            $action = [
                'command' => $s_command,
                'parameters' => []
            ];
        }
        if (in_array($s_method, ['withdraw', 'deposit'])) {
            $action = $this->getActionApplyTransaction($o_gp_params, $s_command);
        }

        if ($s_method == 'rollback') {
            return $this->getActionRollback($o_gp_params, $s_command);
        }

        if ($s_method == 'insertGame') {
            $action = [
                'command' => $s_command,
                'parameters' => [
                    'skinid' => $o_gp_params->skinid
                ]
            ];
        }

        return $action;
    }



    /*
    *  We accept freespins but this feature will not be used
    *  NOT USED: Ganapati doesn't have a Freespins nor Jackpots API so this is unused
    *  This implementation is working however with their manual help
    *  In case the spin is a freespin they will send the promotionId and remaining freespins
    *  in the extra parameter
    */
    public function preProcessFreeSpin()
    {
        $o_gp_params = $this->getGpParams();
        if (isset($o_gp_params->extra) && isset($o_gp_params->extra->promospins)) {
            $s_promotionId = $o_gp_params->extra->promospins->promotionId;

            $this->_setFreespin($this->_i_userId, $s_promotionId, 'game_id');
            if (!$this->_isFreespin()) { // Check if user has Bonus
                $this->_response($this->_getError(self::ER17));
            }
            return ['id' => $this->_getFreespinData('id')];
        }
        return null;
    }

    /*
    * In case someday someone needs to find the user that have freespins for ganapati
    */
    public function getAllFreespins()
    {
        $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
        $str = "SELECT be.bonus_id  promotion_id, bt.game_id AS promotionId, be.user_id AS userId, be.frb_remaining AS spinsRemaining
            FROM bonus_types bt, bonus_entries be
            WHERE be.bonus_id = bt.id AND bt.game_id LIKE 'ganapati_%' AND bt.brand_id = {$brandId} AND be.frb_remaining > 0
            ORDER BY be.bonus_id DESC";
        echo json_encode(phive('SQL')->query($str)->fetchArray());
    }

    /**
     * Unfinished Implementation
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
        if ($this->getSetting('no_out') === true) {
            $ext_id = (!empty($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->randomNumber(6));
            $this->attachPrefix($ext_id);
            return $ext_id;
        }
        return false;
    }

    /**
     * Unfinished Implementation
     * Delete a free spin bonus entry from the bonus_entries table by bonus entries ID
     * @example {
     *  playerid: <userid:required>,
     *  id: <bonus_entries_id>
     * }
     * @param stdClass $p_oParameters
     * @return bool
     */
    protected function _deleteFrb(stdClass $p_oParameters)
    {
        return false;
    }

    public function isJackpot()
    {
        $o_gp_params = $this->getGpParams();
        return (isset($o_gp_params->extra) && isset($o_gp_params->extra->jackpot));
    }

    // Unfinished Implementation
    // JACKPOTS: add the line to Micro/MicroGames.php in the function parseJps
    // $sql->insertTable('micro_jps', phive('Ganapati')->parseJackpots());
    public function parseJackpots()
    {
        // all currencies
        // $currencies = phive('Currencer')->getAllCurrencies();
        $bTest = true;
        $insert = [];
        $map = [
            'MegaJackpot PPAP'  => $this->getGamePrefix() . 'ppap',
            'MegaJackpot PPAP2' => $this->getGamePrefix() . 'ppap',
            'MegaJackpot PPAP3' => $this->getGamePrefix() . 'ppap'
        ];
        if ($bTest) {
            $sJson = "{
                \"jackpotId\": \"megajackpot\",
                \"currency\": \"EUR\",
                \"balance\": 1235.34,
                \"lastWin\": {
                \"winAmount\": 1234.03,
                \"timestamp\": 1519371927251
                }
                }";
        }

        foreach ($map as $gamename => $ext_game_name) {
            if (!$bTest) {
                $sJson = phive()->post($this->getSetting('jackpot_url'), '', Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML, '', '', 'GET', 10);
            }
            $aResponse = json_decode($sJson, true);
            if (! isset($aResponse['jackpotId'])) {
                return null; // we didn't receive anything, WOOOOOPS!
            }
            $insert[] = [
                'jp_value' => $this->convertFromToCoinage($aResponse['balance'], self::COINAGE_UNITS, self::COINAGE_CENTS),
                'jp_id' => $aResponse['jackpotId'],
                'jp_name' => $gamename,
                'network' => $this->getGpName(),
                'module_id' => $ext_game_name,
                'currency' => $aResponse['currency'],
                'local' => 0
            ];
        }
        // echo json_encode($insert, JSON_PRETTY_PRINT);
        // exit;
        if ($bTest && count($insert) > 0) {
            echo "To insert: \n";
            echo json_encode($insert, JSON_PRETTY_PRINT);
            $sql = phive('SQL');
            $sql->query("DELETE FROM micro_jps WHERE network = '$this->getGpName()'");
            echo $sql->insertTable('micro_jps', $insert) ? "inserted" : "Not inserted";
            echo json_encode(mysqli_error($sql->getHandle()));
            exit;
        } elseif ($bTest) {
            echo "Nothing to insert";
            exit;
        }

        return $insert;
    }
}
