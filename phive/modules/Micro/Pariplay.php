<?php

require_once __DIR__ . '/Gp.php';

class Pariplay extends Gp
{
    /**
     * Name of GP. Used to prefix the bets|wins::game_ref (which is the game ID) and bets|wins::mg_id (which is
     * transaction ID)
     *
     * @var string
     */
    protected $_m_sGpName = __CLASS__;

    /**
     * Flag indicating whether to use db.bets.trans_id (INT Round Id) when confirming that a win has a bet with a
     * matching Round Id. We cannot use it (see $_m_bConfirmBet).
     *
     * @var boolean
     */
    protected $_m_bByRoundId = false;

    /**
     * Is the bonus_entries::balance updated after each FRB request with the FRW or does the GP keeps track and send
     * the total winnings at the end of the free rounds. Default: true (balance is updated per bet)
     */
    protected $_m_bFrwSendPerBet = true;

    /**
     * Update the status in bonus_entries table by the last FRW request from the GP in the _win() method. Default: true.
     * If set to false make sure that $this->_handleFspinWin() is called from the extended class.
     * @var bool
     */
    protected $_m_bUpdateBonusEntriesStatusByWinRequest = true;

    /**
     * Flag indicating whether to use either db.bets.trans_id (INT Round Id) or db.bets.mg_id (STRING Transaction Id)
     * when confirming that a free spin win has a matching free spin bet. We cannot use it (see $_m_bConfirmBet).
     *
     * Insert frb into bet table so in case a frw comes in we can check if it has a matching frb
     * Note: this GP does send non int roundid. Txnid are unique for both bet and win so no way finding bet. They did
     * confirm on Skype they wait with win request until bet request has been confirmed by us.
     * 31-10-2017 [4:57:44 PM] Salvatore Lopes: you only send win after we have confirmed your bet request, guaranteed 100%?
     * 31-10-2017 [4:58:35 PM] shay.mardan.work: yes
     * @var bool
     */
    protected $_m_bConfirmFrbBet = false;

    /**
     * Flag indicating whether to use either db.bets.trans_id (INT Round Id) or db.bets.mg_id (STRING Transaction Id)
     * when confirming that a win has a matching bet. We cannot use either because Pariplay send the same Round Id for
     * a bet and matching win but this Round Id is a string. We therefore need to use db.rounds instead
     * (see doConfirmByRoundId).
     *
     * Check if incoming win request has a valid bet transaction.
     * Note: this is NOT for freespins but for real money play
     * Note: this GP does sent non int roundid. Txnid are unique for both bet and win so no way finding bet. They did
     * confirm on Skype they wait with win request until bet request has been confirmed by us.
     * 31-10-2017 [4:57:44 PM] Salvatore Lopes: you only send win after we have confirmed your bet request, guaranteed 100%?
     * 31-10-2017 [4:58:35 PM] shay.mardan.work: yes
     * @var bool
     */
    protected $_m_bConfirmBet = false;

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
    private $_m_aErrors = array(
        'ER01' => array(
            'responsecode' => 500, // used to send header to GP if not enforced 200 OK
            'status' => 'SERVER_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '900', // change this to whatever the GP likes to receive as code
            'message' => 'Internal Server Error.' // change this to whatever the GP likes to receive as message
        ),
        'ER02' => array(
            'responsecode' => 200,
            'status' => 'COMMAND_NOT_FOUND',
            'return' => 'default',
            'code' => '4',
            'message' => 'Command not found.'
        ),
        'ER03' => array(
            'responsecode' => 200,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => '4',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER04' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_WAS_CANCELLED',
            'return' => 'default',
            'code' => '18',     // TransactionAlreadyCancelled
            'message' => 'Bet transaction ID has been cancelled previously.'
        ),
        'ER05' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => 'default',
            'code' => '11',     // TransactionAlreadySettled
            'message' => 'Duplicate Transaction ID.'
        ),
        'ER06' => array(
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => '1',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        'ER07' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_MISMATCH',
            'return' => 'default',
            'code' => '6',       // UnknownTransactionId
            'message' => 'Transaction details do not match.'
        ),
        'ER08' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => '6',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ),
        'ER09' => array(
            'responsecode' => 200,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => '10',     // InvalidUserId
            'message' => 'Player not found.'
        ),
        'ER10' => array(
            'responsecode' => 200,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => '7',
            'message' => 'Game is not found.'
        ),
        'ER11' => array(
            'responsecode' => 200,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => '8',
            'message' => 'Token not found.'
        ),
        'ER17' => array(
            'responsecode' => 200,
            'status' => 'FREESPIN_NOT_FOUND',
            'return' => 'default',
            'code' => '2',      // FreeRoundsNotAvailable
            'message' => 'This free spin bonus ID is not found.'
        ),
        'ER18' => array(
            'responsecode' => 200,
            'status' => 'IDEMPOTENCE',
            'return' => 'default',
            'code' => '11',     // TransactionAlreadySettled
            'message' => 'Idempotent transaction.'
        ),
        'ER19' => array(
            'responsecode' => 200,
            'status' => 'STAKE_TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => '9',
            'message' => 'Stake transaction not found.'
        ),
        'ER25' => array(
            'responsecode' => 200,
            'status' => 'PLAYER_BLOCKED',
            'return' => 'default',
            'code' => '12',
            'message' => 'Player is blocked.'
        ),
        'ER26' => array(
            'responsecode' => 200,
            'status' => 'PLAYER_BANNED',
            'return' => 'default',
            'code' => '12',
            'message' => 'Player is banned.'
        ),
        'ER27' => array(
            'responsecode' => 200,
            'status' => 'INVALID_USER_ID',
            'return' => 'default',
            'code' => '10',     // InvalidUserId
            'message' => 'Session player ID doesn\'t match request Player ID.'
        ),
        'ER28' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_ALREADY_CANCELLED',
            'return' => 'default',
            'code' => '18',     // TransactionAlreadyCancelled
            'message' => 'Transaction ID has been cancelled already.'
        ),
        'ER51' => array(
            'responsecode' => 200,
            'status' => 'INVALID_ROUND',
            'return' => 'default',
            'code' => '9',
            'message' => 'Invalid round.'
        ),
        'ER52' => array(
            'responsecode' => 200,
            'status' => 'ROUND_CLOSED',
            'return' => 'default',
            'code' => '17',
            'message' => 'Round is closed.'
        ),
        'ER53' => array(
            'responsecode' => 200,
            'status' => 'INVALID_AMOUNT',
            'return' => 'default',
            'code' => '3',
            'message' => 'Invalid amount.'
        ),
    );

    private $_m_aMapGpMethods = array(
        'Authenticate' => '_init',
        'GetBalance' => '_balance',
        // pariplay will not send such request as it conflict with freespins.
        // eg. last freespin bet/win got cancelled can not happen because with win transfer of funds has happened already
        'DebitAndCredit' => 'betwin',
        'Debit' => '_bet',
        'Credit' => '_win',
        'CancelTransaction' => '_cancel',
        'CloseOpenedRounds' => 'closeAllOpenRounds',
        'EndGame' => 'closeRound',
        'CreateToken' => '_createToken',
        'CreateFreeRounds' => '_createFreespin',
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
            ->_setWalletActions();
        $this->clearLogMessages();
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
            $this->_logIt([__METHOD__, 'Unknown request.']);
            $this->_response($this->_getError(self::ER03));
        }

        $aJson = $aAction = array();
        $casinoMethod = null;
        $this->user_identifier = $oData->PlayerId ?? null;

        $aJson['hash'] = $this->validateAuthenticationCredentials();

        // Define which service is requested
        $casinoMethod = substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '/') + 1);

        if (empty($casinoMethod)) {
            $this->_logIt([__METHOD__, 'Unknown request action.']);
            $this->_response($this->_getError(self::ER02));
        }
        $this->_setGpMethod($casinoMethod);

        /**
         * Sets the player, game and platform from Redis if the key exists.
         */
        $aJson = $this->parseRequestToken($aJson);

        /**
         * Gets the player from the request if necessary.
         */
        $aJson = $this->parseRequestPlayer($aJson);

        /**
         * Validates the request GameCode.
         */
        $aJson = $this->parseRequestGame($aJson);
        if (!empty($aJson['skinid'])) {
            $aJson['skinid'] = $this->getOriginalGameRefIfOverridden($aJson['skinid']);
        }

        if (in_array($casinoMethod, ['Debit', 'Credit', 'EndGame'])) {
            $aJson = $this->parseSingleMethodRequest($aJson);
        } elseif ($casinoMethod == 'DebitAndCredit') {
            $aJson = $this->parseRequestDebitAndCredit($aJson);
        } elseif ($casinoMethod == 'CancelTransaction') {
            $aJson = $this->parseRequestRollback($aJson);
        } else {
            $aJson['state'] = 'single';
            $aJson['action'] = [
                'command' => $this->getWalletMethodByGpMethod($casinoMethod),
            ];
        }

        // detect for freespin
        if (isset($oData->Feature) && $oData->Feature === 'BonusWin') {
            $this->attachPrefix($oData->FeatureId);
            $bonus_entry = phive('Bonuses')->getEntryByExtId($oData->FeatureId, $this->uid);
            if (empty($bonus_entry)) {
                $this->_response($this->_getError(self::ER17));     // FREESPIN_NOT_FOUND
            } else {
                $aJson['freespin'] = ['id' => empty($bonus_entry) ? 0 : ($bonus_entry['id'] ?? 0)];
            }
        }

        if ($casinoMethod === 'CreateFreeRounds' && isset($oData->BonusId)) {
            $aJson['ext_id'] = $oData->BonusId;
            $aJson['reward'] = $oData->NumberFreeRounds;
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
        $mResponse = false;

        $gp_method = $this->getGpMethod();
        $this->_setActions();

        // Set the game data by the received skinid (is gameid)
        if (isset($this->_m_oRequest->skinid)) {
            $this->_setGameData();
            if (isset($this->_m_oRequest->freespin)) {
                $this->_setFreespin($this->_m_oRequest->playerid, $this->_m_oRequest->freespin->id, 'ext_id');
                if (!$this->_isFreespin()) {
                    // the frb doesn't exist or is missing??
                    $this->_response($this->_getError(self::ER17));
                }
            }
        }

        // execute all commands
        foreach ($this->_getActions() as $key => $oAction) {
            // Update the user data before each command
            $this->setUserDataWithoutResponse($this->_m_oRequest->playerid ?? $this->user_identifier);

            $sMethod = $oAction->command;
            $this->_setWalletMethod($sMethod);

            $error = $this->validateWalletMethod($oAction);
            if ($error !== true) {
                $this->_response($error);
            }

            // command call return either an array with errors or true on success
            if ($this->getSetting('log-verbose')) {
                $this->_logIt(
                    [
                        __METHOD__ . '::' . __LINE__,
                        "Executing {$sMethod} {$gp_method}",
                        print_r($oAction, true),
                        print_r($this->_getFreespinData(), true),
                    ]
                );
            }
            if (property_exists($oAction, 'parameters')) {
                $mResponse = $this->$sMethod($oAction->parameters);
            } else {
                $mResponse = $this->$sMethod();
            }
            if ($this->getSetting('log-verbose')) {
                $this->_logIt(
                    [
                        __METHOD__ . '::' . __LINE__,
                        "Executed {$sMethod} {$gp_method}",
                        print_r($mResponse, true),
                        print_r($oAction, true),
                        print_r($this->_getFreespinData(), true),
                    ]
                );
            }
            if ($mResponse !== true) {
                // some error occurred
                break;
            }
        }

        $this->_response($mResponse);
    }

    /**
     * @return mixed|string
     */
    private function validateAuthenticationCredentials()
    {
        $request = $this->getGpParams();

        if (
            empty($request)
            || !isset($request->Account)
            || !isset($request->Account->UserName)
            || !isset($request->Account->Password)
        ) {
            $this->_response($this->_getError(self::ER03));
        }

        $hash_request = $this->getHash(
            $request->Account->UserName . $request->Account->Password,
            self::ENCRYPTION_SHA1
        );

        $authentication_username = $this->getLicSetting('username', $this->user_identifier);
        $authentication_password = $this->getLicSetting('password', $this->user_identifier);
        $hash_config = $this->getHash($authentication_username . $authentication_password, self::ENCRYPTION_SHA1);

        if ($hash_request !== $hash_config) {
            $this->_response($this->_getError(self::ER03));
        }

        return $hash_request;
    }

    /**
     * @param array $json
     * @return array
     */
    private function parseRequestToken(array $json): array
    {
        $method = $this->getGpMethod();
        $request = $this->getGpParams();

        if (in_array($method, ['CreateFreeRounds', 'CloseOpenedRounds',])) {
            return $json;
        }

        // Pariplay Automated Test Suite sends 'fakeToken' for some tests.
        if (!isset($request->Token) || (strpos($request->Token, 'fakeToken') !== false)) {
            $this->_response($this->_getError(self::ER11));     // // TOKEN_NOT_FOUND
        }
        $json['token'] = $request->Token;

        if ($method == 'CreateToken') {
            return $json;
        }

        $session_data = $this->fromSession($request->Token);
        if (empty($session_data) || !isset($session_data->userid)) {
            /**
             * This could be a late Win or Cancel request which is sent after Redis expired.
             * CloseOpenedRounds is only for Pariplay's Automated Test Suite and does not have a Token parameter.
             * For all other Actions, assume that Redis expired and return an error.
             */
            $methods_with_gamecode = ['Credit', 'CancelTransaction', 'CreateToken', 'CloseOpenedRounds'];
            if (!in_array($method, $methods_with_gamecode)) {
                $this->_response($this->_getError(self::ER11));     // TOKEN_NOT_FOUND
            }

            // Nothing to do without a Redis entry.
            return $json;
        }

        $json['playerid'] = $this->user_identifier = $session_data->userid;
        $json['skinid'] = $this->stripPrefix($session_data->gameid);
        $json['platform'] = $session_data->device ?? 'desktop';

        $this->setUserDataWithoutResponse($this->user_identifier);
        if (empty($this->uid)) {
            $this->_response($this->_getError(self::ER09));     // PLAYER_NOT_FOUND
        }

        $this->_logIt([__METHOD__, 'UID by session', print_r($session_data, true)]);
        return $json;
    }

    /**
     * @param array $json
     * @return array
     */
    private function parseRequestPlayer(array $json): array
    {
        $request = $this->getGpParams();

        if (!empty($json['playerid'])) {
            if (isset($request->PlayerId) && ($request->PlayerId != $json['playerid'])) {
                $this->_response($this->_getError(self::ER27));     // INVALID_USER_ID
            }
            return $json;
        }

        if (empty($request->PlayerId ?? null)) {
            $this->_response($this->_getError(self::ER27)); // INVALID_USER_ID
        }

        $json['playerid'] = $this->user_identifier = $request->PlayerId;

        $this->setUserDataWithoutResponse($this->user_identifier);
        if (empty($this->uid)) {
            $this->_response($this->_getError(self::ER09));     // PLAYER_NOT_FOUND
        }

        $this->_logIt([__METHOD__, 'UID by request', print_r($request, true)]);
        return $json;
    }

    /**
     * @param array $json
     * @return array
     */
    private function parseRequestGame(array $json): array
    {
        $method = $this->getGpMethod();
        $request = $this->getGpParams();

        $methods_with_gamecode = ['Debit', 'Credit', 'EndGame', 'DebitAndCredit', 'CancelTransaction'];
        if ($this->getSetting('test', false)) {
            $test_methods = ['CreateToken', 'CloseOpenedRounds', 'CreateFreeRounds'];
            $methods_with_gamecode = array_merge($methods_with_gamecode, $test_methods);
        }

        if (!in_array($method, $methods_with_gamecode)) {
            return $json;
        }

        if (empty($json['platform'])) {
            $json['platform'] = (($request->PlatformType ?? 1) == 2) ? 'mobile' : 'desktop';
        }

        if (isset($request->GameCode)) {
            if ($request->GameCode == ($json['skinid'] ?? '')) {
                $this->_logIt([__METHOD__ . '::' . __LINE__, 'GID by session', print_r($json, true)]);
                return $json;
            }
            $this->_logIt([__METHOD__ . '::' . __LINE__, 'GID by oData->GameCode', $request->GameCode]);

            /**
             * Only Debit and DebitAndCredit requests can change the active game.
             * For example the player launches a casino game and a new token is generated for this game. He can
             * access the game lobby from inside the game and open another game. In this case, the token
             * contains the original game but the game changed.
             *
             * Credit and CancelTransaction requests might arrive after Redis timed out so in this case we just
             * accept the 'GameCode' request parameter.
             */
            if (!empty($json['skinid'] ?? '')) {
                $methods_with_gamecode = ['Debit', 'DebitAndCredit'];
                if ($this->getSetting('test', false)) {
                    $test_methods = ['CreateToken', 'CloseOpenedRounds', 'CreateFreeRounds'];
                    $methods_with_gamecode = array_merge($methods_with_gamecode, $test_methods);
                }
                if (!in_array($method, $methods_with_gamecode)) {
                    $this->_response($this->_getError(self::ER10));     // GAME_NOT_FOUND
                }
            }

            $ext_game_name = $request->GameCode;
            $this->attachPrefix($ext_game_name);
            $device = ($json['platform'] == 'mobile') ? 1 : 0;
            $db_game = phive('MicroGames')->getByGameRef($ext_game_name, $device, null);
            if (empty($db_game)) {
                $this->_response($this->_getError(self::ER10));     // GAME_NOT_FOUND
            }

            $prev_skin_id = $json['skinid'] ?? '';
            $json['skinid'] = $this->stripPrefix($db_game['ext_game_name']);

            /**
             * Sets the new game Id in Redis if the game changed and we have a Redis entry.
             * Pariplay Automated Test Suite sends 'fakeToken' for some tests.
             */
            if (($json['token'] ?? false) && (strpos($request->Token, 'fakeToken') === false)) {
                $this->toSession($json['token'], $this->user_identifier, $json['skinid'], $json['platform']);

                if ($method != 'CreateToken') {
                    $msg = "Overriding session game ID from {$prev_skin_id} to {$json['skinid']}.";
                    $this->addLogMessage($msg);
                }
            }
        }

        if (empty($json['skinid'])) {
            $this->_logIt([__METHOD__ . '::' . __LINE__, 'GID not found']);
        }
        return $json;
    }

    /**
     * @param stdClass $action
     * @return array|true Returns true if the validation was successful, otherwise the error array.
     */
    private function validateWalletMethod(stdClass $action)
    {
        $method = $this->_getMethod();
        if (empty($method)) {
            return $this->_getError(self::ER02);            // COMMAND_NOT_FOUND
        }

        if (empty($this->uid)) {
            return $this->_getError(self::ER09);            // PLAYER_NOT_FOUND
        }

        if (in_array($method, ['_init', '_bet', '_win', 'closeRound'])) {
            if ($this->user->isSuperBlocked() || $this->user->isPlayBlocked()) {
                return $this->_getError(self::ER26);       // PLAYER_BANNED
            }
        }

        if ($method == '_bet') {
            return $this->validateBet($action);
        } elseif ($method == '_win') {
            return $this->validateWin($action);
        } elseif ($method == '_cancel') {
            return $this->validateRollback($action);
        } elseif ($method == 'closeRound') {
            return $this->validateCloseRound($action);
        }

        return true;    // SUCCESS
    }

    /**
     * @param stdClass $action
     * @return array|true Returns true if the validation was successful, otherwise the error array.
     */
    private function validateBet(stdClass $action)
    {
        $parameters = $action->parameters ?? null;

        if (empty($parameters) || empty($parameters->roundid ?? null)) {
            return $this->_getError('ER51');      // INVALID_ROUND
        }
        if (empty($parameters->transactionid ?? null)) {
            return $this->_getError('ER08');      // TRANSACTION_NOT_FOUND
        }
        $parameters->amount = (int)($parameters->amount ?? 0);
        if ($parameters->amount < 0) {
            return $this->_getError("ER53");      // INVALID_AMOUNT
        }

        if (!$this->allowTransactionAfterRoundEnded()) {
            $round_transactions = $this->getRoundsByExtRoundId($this->uid, $parameters->roundid);
            if ($this->isRoundEnded($round_transactions)) {
                return $this->_getError("ER52");    // ROUND_CLOSED
            }
        }

        return true;    // SUCCESS
    }

    /**
     * @param stdClass $action
     * @return array|true Returns true if the validation was successful, otherwise the error array.
     */
    private function validateWin(stdClass $action)
    {
        $parameters = $action->parameters ?? null;

        if (empty($parameters) || empty($parameters->roundid ?? null)) {
            return $this->_getError('ER51');      // INVALID_ROUND
        }
        if (empty($parameters->transactionid ?? null)) {
            return $this->_getError('ER08');      // TRANSACTION_NOT_FOUND
        }
        $parameters->amount = (int)($parameters->amount ?? 0);
        if ($parameters->amount < 0) {
            return $this->_getError("ER53");      // INVALID_AMOUNT
        }

        $rounds = $this->getRoundsByExtRoundId($this->uid, $parameters->roundid);

        if (!$this->allowTransactionAfterRoundEnded()) {
            if ($this->isRoundEnded($rounds)) {
                return $this->_getError("ER52");    // ROUND_CLOSED
            }
        }

        $has_winnable_bet = $this->hasWinnableBet($rounds);
        if (!$has_winnable_bet) {
            return $this->_getError("ER51");    // INVALID_ROUND
        }

        return true;    // SUCCESS
    }

    /**
     * @param array $rounds
     * @return bool
     */
    private function hasWinnableBet(array $rounds): bool
    {
        if (empty($rounds)) {
            return false;
        }

        $bets_and_wins = $this->getRoundTransactions($rounds, true, false);
        foreach ($bets_and_wins['bets'] as $bet) {
            if (substr($bet['mg_id'], -strlen('ref')) !== 'ref') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param stdClass $action
     * @return array|true Returns true if the validation was successful, otherwise the error array.
     */
    private function validateRollback(stdClass $action)
    {
        return true;
    }

    /**
     * @param stdClass $action
     * @return array|true Returns true if the validation was successful, otherwise the error array.
     */
    private function validateCloseRound(stdClass $action)
    {
        $gp_method = $this->getGpMethod();
        $parameters = $action->parameters ?? null;

        if (empty($parameters) || empty($parameters->roundid ?? null)) {
            return $this->_getError('ER51');      // INVALID_ROUND
        }
//        if (empty($parameters->transactionid ?? null)) {
//            return $this->_getError('ER08');      // TRANSACTION_NOT_FOUND
//        }

        $rounds = $this->getRoundsByExtRoundId($this->uid, $parameters->roundid);
        if (empty($rounds)) {
            return $this->_getError("ER51");    // INVALID_ROUND
        }
        if ($this->isRoundEnded($rounds)) {
            return $this->_getError("ER52");    // ROUND_CLOSED
        }

        if ($gp_method == 'EndGame') {
            $bets_and_wins = $this->getRoundTransactions($rounds, true, true);
            $bets = array_filter(
                $bets_and_wins['bets'],
                function ($row) {
                    return (substr($row['mg_id'], -strlen('ref')) !== 'ref');
                }
            );
            $wins = array_filter(
                $bets_and_wins['wins'],
                function ($row) {
                    return (substr($row['mg_id'], -strlen('ref')) !== 'ref');
                }
            );
            $uncancelled_bets_and_wins = array_merge($bets, $wins);

            if (empty($uncancelled_bets_and_wins)) {
                return $this->_getError("ER51");    // INVALID_ROUND
            }
        }

        return true;    // SUCCESS
    }

    /**
     * @param array|null $rounds
     * @return bool
     */
    private function isRoundEnded(array $rounds = null): bool
    {
        if (empty($rounds)) {
            return false;
        }

        foreach ($rounds as $round) {
            if (($round['bet_id'] == -1) || ($round['win_id'] == -1)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    private function allowTransactionAfterRoundEnded(): bool
    {
        $raw_request = $this->getGpParams();
        if (!is_array($raw_request->TransactionConfiguration ?? null)) {
            return false;
        }
        return in_array(6 /* AllowAllWagersAfterRoundEnd */, $raw_request->TransactionConfiguration);
    }

    /**
     * Returns an array of all the db.rounds rows with an empty bet_id and/or empty win_id.
     *
     * @param array $round_transactions
     * @param bool $empty_bet
     * @param bool $empty_win
     * @return array
     */
    private function findRoundTransactionsEmpty(
        array $round_transactions,
        bool $empty_bet = true,
        bool $empty_win = true
    ): array {
        $empties = [];

        foreach ($round_transactions as $round) {
            if (($empty_bet && ($round['bet_id'] == 0)) || ($empty_win && ($round['win_id'] == 0))) {
                $empties[] = $round;
            }
        }

        return $empties;
    }

    /**
     * @param array $rounds
     * @param bool $is_bet
     * @param bool $is_win
     * @return array[]
     */
    private function getRoundTransactions(array $rounds, bool $is_bet = true, bool $is_win = true): array
    {
        $bets = $wins = $bet_ids = $win_ids = [];

        foreach ($rounds as $round) {
            if ($is_bet && ($round['bet_id'] > 0)) {
                $bet_ids[] = $round['bet_id'];
            }
            if ($is_win && ($round['win_id'] > 0)) {
                $win_ids[] = $round['win_id'];
            }
        }

        if (!empty($bet_ids)) {
            $table = $this->isTournamentMode() ? 'bets_mp' : 'bets';
            $bets = $this->getTransactionsById($this->uid, $table, $bet_ids);
        }
        if (!empty($win_ids)) {
            $table = $this->isTournamentMode() ? 'wins_mp' : 'wins';
            $wins = $this->getTransactionsById($this->uid, $table, $win_ids);
        }

        return [
            'bets' => $bets,
            'wins' => $wins,
        ];
    }

    /**
     * Creates a users game session so GP can test API
     * @return bool
     */
    protected function _createToken()
    {
        // on production this should be normally not be allowed so only there for debugging on request of GP
        if ($this->getSetting('test') !== true) {
            return $this->_getError(self::ER02);
        }

        $this->toSession(
            $this->_m_oRequest->token,
            $this->_m_oRequest->playerid,
            $this->_m_oRequest->skinid,
            'desktop'
        );
        $this->logResponseInfo(
            [
                'external_token' => $this->_m_oRequest->token,
                'player' => $this->_m_oRequest->playerid,
                'game' => $this->_m_oRequest->skinid,
                'platform' => 'desktop',
            ],
            true
        );
        return true;
    }

    /**
     * Update the status in the bonus entries table when a FRB round has finished
     * Pragmatic sends frw one by one and also a final frw with total sum, we only process the final frw
     * @param stdClass $p_oParameters
     * @return bool
     */
    public function frbStatus(stdClass $p_oParameters)
    {
        $this->_logIt([__METHOD__, print_r($p_oParameters, true)]);
        // we must insert the final win having the total sum of all free round wins
        $this->_m_bFrwSendPerBet = true;
        if ($this->_win($p_oParameters)) {
            $this->_m_bFrwSendPerBet = false;
            return $this->_handleFspinWin($p_oParameters->amount);
        }
        return false;
    }

    /**
     * The method _createFrb in GP is adding a prefix to game names who already have a prefix
     * This method is overrided to handle the situation
     */
    /*public function getGamePrefix()
    {
        return '';
    }*/

    /**
     * Creates a freespin bonus for a specific player so GP can test API
     * @return array|bool|int
     */
    protected function _createFreespin()
    {
        // on production this should be normally not be allowed so only there for debugging on request of GP
        if ($this->getSetting('test') !== true) {
            return $this->_getError(self::ER02);
        }

        // first clone an existing reward bonus
        $r = $this->_cloneBonusTypes(
            json_decode(
                json_encode(
                    array(
                        'skinid' => $this->_m_oRequest->skinid,
                        'reward' => $this->_m_oRequest->reward,
                        'bonustype' => 'reward',
                        'frb_coins' => '0',
                        'frb_denomination' => '10',
                        'frb_lines' => '0',
                        'target' => 'desktop'
                    )
                ),
                false
            )
        );

        if ($r === true) {
            $this->testCloseActiveFreeSpins();

            // create a freespin bonus entry based on the bonus_type create above
            $r = parent::_createFrb(
                json_decode(
                    json_encode(
                        array(
                            'ext_id' => $this->_m_oRequest->ext_id,
                            'skinid' => $this->_m_oRequest->skinid,
                            'bonustype' => 'reward',
                            'playerid' => $this->_m_oRequest->playerid,
                            'reward' => $this->_m_oRequest->reward,
                            'activate' => 'true'
                        )
                    ),
                    false
                )
            );

            if ($r === true) {
                return true;
            }
        }
    }

    /**
     *
     */
    private function testCloseActiveFreeSpins()
    {
        if (($this->getSetting('test') !== true) || empty($this->uid)) {
            return;
        }

        $sql = <<<EOS
SELECT id
FROM bonus_entries
WHERE
    user_id = {$this->uid}
    AND status IN ('active', 'approved')
    AND bonus_type = 'freespin'
    AND frb_remaining > 0
EOS;
        $bonus_entries = phive('SQL')->sh($this->uid, '', 'bonus_entries')->loadArray($sql);
        if (empty($bonus_entries)) {
            return;
        }

        foreach ($bonus_entries as $bonus_entry) {
            phive('Bonuses')->close($bonus_entry['id'], 'failed', [], $this->uid);
        }
    }

    protected function _response($p_mResponse)
    {
        $this->setUserDataWithoutResponse($this->user_identifier);
        $aResponse = [];

        if (is_array($p_mResponse)) {
            if ($p_mResponse['status'] == 'IDEMPOTENCE') {
                $this->addLogMessage("Ignoring idempotent transaction.");
                $p_mResponse = true;
            } else {
                if (!empty($p_mResponse['status']) && ($p_mResponse['status'] != 'INSUFFICIENT_FUNDS')) {
                    $this->addLogMessage($p_mResponse['status']);
                }

                $aResponse['Error']['ErrorCode'] = $p_mResponse['code'];
                if ($this->uid && ($p_mResponse['status'] !== 'PLAYER_NOT_FOUND')) {
                    $aResponse['Error']['Balance'] = $this->getFormattedBalance();
                };
            }
        }

        if ($p_mResponse === true) {
            if (!in_array($this->getGpMethod(), array('CreateToken', 'CreateFreeRounds', 'CloseOpenedRounds'))) {
                $aResponse['Balance'] = $this->getFormattedBalance();
            }
            
            if ($this->getGpMethod() === 'Authenticate') {
                $maxBet = phive('Gpr')->getMaxBetLimit(cu($this->user_identifier));
                if (!empty($maxBet)) {
                    $aResponse['UserSettings']['MaxBet'] = $maxBet;
                }
            }

            if (in_array($this->getGpMethod(), array('Debit', 'Credit', 'DebitAndCredit', 'CancelTransaction', 'EndGame'))) {
                $aTxnId = array();
                if (in_array($this->getGpMethod(), array('DebitAndCredit', 'CancelTransaction'))) {
                    $aTxnId[0] = $this->_getTransaction('txn', self::TRANSACTION_TABLE_BETS);
                    $aTxnId[1] = $this->_getTransaction('txn', self::TRANSACTION_TABLE_WINS);
                } else {
                    $aTxnId[0] = $this->_getTransaction('txn');
                }
                $sTxnId = implode('-', array_filter($aTxnId));
                $aResponse['TransactionId'] = (($this->_isFreespin() || empty($sTxnId)) ? 'a' . $this->randomNumber(
                        16
                    ) : $sTxnId);
            }
        }

        $this->_setResponseHeaders($p_mResponse);

        $result = empty($aResponse) ? json_encode([], JSON_FORCE_OBJECT) : json_encode($aResponse);
        echo $result;

        $this->_logIt([__METHOD__, print_r($p_mResponse, true), $result]);
        $this->logResponseInfo($aResponse);
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }

    /**
     * @return float|int|null
     */
    private function getFormattedBalance()
    {
        if (empty($this->uid) || $this->user->isSuperBlocked()) {
            return null;
        }

        $balance = $this->_getBalance();
        return $this->convertFromToCoinage($balance, self::COINAGE_CENTS, self::COINAGE_UNITS);
    }

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $p_mGameId The game ref override without prefix (db.micro_games.ext_game_name || db.game_country_overrides.ext_game_id)
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @param bool $show_demo
     * @return string The url to open the game
     */
    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        $this->initCommonSettingsForUrl();

        $sUrl = $this->launch_url;
        $aUrl = [
            'GameCode' => $p_mGameId,
            'HomeUrl' => $this->getLicSetting('lobby_url'),
            'CashierUrl' => $this->getLicSetting('cashier_url'),
            'PlayerIP' => remIp(),
            'CurrencyCode' => phive('Tournament')->curIso(),
            'LanguageCode' => $p_sLang,
            'Account' => [
                'UserName' => $this->getLicSetting('username'),
                'Password' => $this->getLicSetting('password')
            ],
        ];

        if (isLogged()) {
            $ud = cu();

            if ($this->getRcPopup($p_sTarget, $ud) == 'ingame') {
                $aRc = $this->getRealityCheckParameters($ud, false, ['RealityCheckInterval', 'HistoryUrl']);
            }

            $uid = empty($_SESSION['token_uid']) ? $ud->getAttr('id') : $_SESSION['token_uid'];
            $this->user_identifier = $uid;
            $is_tournament = $this->isTournament($uid);

            $aUrl['PlayerIP'] = $ud->getAttr('cur_ip');
            $aUrl['PlayerId'] = $uid;
            $aUrl['CountryCode'] = $ud->getAttr('country');
            $aUrl['CurrencyCode'] = $is_tournament ? phive('Tournament')->curIso() : $ud->getAttr('currency');
            $aUrl['LanguageCode'] = $ud->getAttr('preferred_lang');
            if (!empty($aRc)) {
                $aUrl['RealityCheckInterval'] = $aRc['rcInterval'];
                $aUrl['HistoryUrl'] = $aRc['rcHistoryUrl'];
            }
        }

        $result = phive()->post(
            $sUrl,
            json_encode($aUrl),
            Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON,
            '',
            $this->getGamePrefix() . 'out',
            'POST'
        );
        $oData = json_decode($result, false);
        $this->_logIt([__METHOD__, $sUrl, print_r($result, true), print_r($aUrl, true)]);
        $this->logResponseInfo(
            [
                'response' => json_decode($result, true),
                'url' => $sUrl,
                'request' => array_merge($aUrl, ['platform' => $p_sTarget]),
            ],
            true
        );

        if ($oData && !isset($oData->Error)) {
            $this->toSession($oData->Token, $aUrl['PlayerId'] ?? null, $p_mGameId, $p_sTarget);
        }

        return $oData->Url;
    }

    public function addCustomRcParams($regulator, $rcParams)
    {
        // set true to the params that need to be mapped
        $provider_rc_params = [(array)$this->lic($regulator, 'addCustomRcParams')];

        return array_merge($rcParams, $provider_rc_params);
    }

    public function mapRcParameters($regulator, $rcParams, $u_obj = null)
    {
        $mapping = [
            'RealityCheckInterval' => 'rcInterval',
            'HistoryUrl' => 'rcHistoryUrl'
        ];

        $mapping = array_merge($mapping, (array)$this->lic($regulator, 'getMapRcParameters'));

        // apply mapping
        $rcParams = phive()->mapit($mapping, $rcParams, [], false);

        return $rcParams;
    }

    /**
     * Delete a free spin bonus entry from the bonus_entries table by bonus entries ID
     * @param stdClass $p_oParameters
     * @return bool
     * @example {
     *  playerid: <userid:required>,
     *  id: <bonus_entries_id>
     * }
     */
    protected function _deleteFrb(stdClass $p_oParameters)
    {
        if (isset($p_oParameters->playerid) && isset($p_oParameters->id)) {
            $user = cu($p_oParameters->playerid);

            if (isset($user->data['id'])) {
                $aBonusEntry = $this->_getBonusEntryBy($user->data['id'], $p_oParameters->id);

                if (!empty($aBonusEntry['ext_id'])) {
                    $a = array(
                        'PlayerId' => $user->data['id'],
                        'BonusId' => $aBonusEntry['ext_id'],
                        'CountryCode' => $user->data['country']
                    );

                    $result = phive()->post(
                        $this->getSetting('launchurl_frb') . '/FreeRounds/Remove',
                        json_encode($a),
                        self::HTTP_CONTENT_TYPE_APPLICATION_JSON,
                        "Cache-Control: no-cache",
                        $this->getGamePrefix() . 'out',
                        'POST'
                    );

                    $oData = json_decode($result, false);

                    $this->_logIt(
                        [
                            __METHOD__,
                            'URL: ' . $this->getSetting('launchurl_frb'),
                            print_r($a, true),
                            json_encode($a),
                            print_r($oData, true)
                        ]
                    );
                }

                return parent::_deleteFrb($p_oParameters);
            }
        }

        return false;
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
     * @return bool|string|int If not false than bonusId is returned otherwise false (freespins are not activated)
     */
    public function awardFRBonus($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry)
    {
        if ($this->getSetting('no_out') === true) {
            $ext_id = (!empty($p_aBonusEntry['ext_id']) ? $p_aBonusEntry['ext_id'] : $this->randomNumber(6));
            $this->attachPrefix($ext_id);
            return $ext_id;
        }

        $uid = $this->getUsrId($p_iUserId);
        $user = cu($uid);

        $freespin_value = $this->_getFreespinValue($user->data['id'], $p_aBonusEntry['id']);
        $coin_value = $this->convertFromToCoinage($freespin_value, self::COINAGE_CENTS, self::COINAGE_UNITS);

        $a = array(
            'PlayerId' => (string)$p_iUserId,
            'GameCode' => implode(',', explode('|', $this->stripPrefix($p_sGameIds))),
            'NumberFreeRounds' => $p_iFrbGranted,
            'ExpirationDate' => $p_aBonusEntry['end_time'] . "T23:59:59Z",
            'CountryCode' => $user->data['country'],
            'CurrencyCode' => $user->data['currency'],
            'CoinValue' => (string)round($coin_value, 2),
            'Account' => [
                'UserName' => $this->getLicSetting('username'),
                'Password' => $this->getLicSetting('password')
            ],
        );

        $url = rtrim($this->getLicSetting('api_url'), '/') . '/FreeRounds/Add';
        $aPostJson = json_encode($a);

        $result = phive()->post(
            $url,
            $aPostJson,
            self::HTTP_CONTENT_TYPE_APPLICATION_JSON,
            "Cache-Control: no-cache",
            $this->getGamePrefix() . 'out',
            'POST'
        );
        $oData = json_decode($result, false);
        $this->_logIt(
            [
                __METHOD__,
                'URL: ' . $url,
                print_r($a, true),
                print_r($p_aBonusEntry, true),
                print_r($oData, true),
                $aPostJson
            ]
        );

        if (!empty($oData->BonusId)) {
            // we return here the bonus ID of the GP so it will get inserted
            // into bonus_entries:ext_id
            $this->attachPrefix($oData->BonusId);
//            echo "RESPONSE:" . PHP_EOL . print_r($oData, true) . PHP_EOL;
            return $oData->BonusId;
        }

        return false;
    }

    /**
     * @param array $wallet_parameters
     * @return array
     */
    private function parseSingleMethodRequest(array $wallet_parameters): array
    {
        $gp_method = $this->getGpMethod();
        $wallet_method = $this->getWalletMethodByGpMethod($gp_method);
        $request = $this->getGpParams();

        $map = [
            'RoundId' => 'roundid',
            'TransactionId' => 'transactionid',
            'Amount' => 'amount',
            'EndGame' => 'endgame',
        ];

        $parameters = $this->parseRequestParameters($request, $map);
        foreach (['roundid', 'transactionid'] as $k) {
            if ($parameters[$k] ?? false) {
                $this->attachPrefix($parameters[$k]);
            }
        }
        if (isset($parameters['endgame'])) {
            $parameters['endgame'] = boolval($parameters['endgame']);
        }

        $wallet_parameters['state'] = 'single';
        $wallet_parameters['action'] = [
            'command' => $wallet_method,
            'parameters' => $parameters,
        ];

        return $wallet_parameters;
    }

    /**
     * @param array $wallet_parameters
     * @return array
     */
    private function parseRequestDebitAndCredit(array $wallet_parameters): array
    {
        $request = $this->getGpParams();

        $bet_map = ['RoundId' => 'roundid', 'TransactionId' => 'transactionid', 'DebitAmount' => 'amount'];
        $win_map = ['RoundId' => 'roundid', 'TransactionId' => 'transactionid', 'CreditAmount' => 'amount'];

        $bet_parameters = $this->parseRequestParameters($request, $bet_map);
        foreach (['roundid', 'transactionid'] as $k) {
            if ($bet_parameters[$k] ?? false) {
                $this->attachPrefix($bet_parameters[$k]);
            }
        }
        $bet_parameters['endgame'] = false;

        if (($bet_parameters['amount'] ?? 0) < 0) {
            return $this->_response($this->_getError("ER53"));      // INVALID_AMOUNT
        }

        $win_parameters = $this->parseRequestParameters($request, $win_map);
        foreach (['roundid', 'transactionid'] as $k) {
            if ($win_parameters[$k] ?? false) {
                $this->attachPrefix($win_parameters[$k]);
            }
        }
        $win_parameters['endgame'] = true;

        if (($win_parameters['amount'] ?? 0) < 0) {
            return $this->_response($this->_getError("ER53"));      // INVALID_AMOUNT
        }
        if (($win_parameters['amount'] ?? 0) == 0) {
            $actions = [
                [
                    'command' => '_bet',
                    'parameters' => array_merge($bet_parameters, ['endgame' => true]),
                ],
            ];
        } else {
            $actions = [
                [
                    'command' => '_bet',
                    'parameters' => $bet_parameters,
                ],
                [
                    'command' => '_win',
                    'parameters' => $win_parameters,
                ],
            ];
        }

        $wallet_parameters['state'] = 'multi';
        $wallet_parameters['actions'] = $actions;

        return $wallet_parameters;
    }

    /**
     * @param array $wallet_parameters
     * @return array
     */
    private function parseRequestRollback(array $wallet_parameters): array
    {
        $gp_method = $this->getGpMethod();
        $wallet_method = $this->getWalletMethodByGpMethod($gp_method);
        $request = $this->getGpParams();

        $cancel_entire_round = boolval($request->CancelEntireRound ?? false);
        if (!$cancel_entire_round) {
            return $this->parseRequestRollbackTransaction($wallet_parameters);
        }

        /**
         * Gets all the db.rounds rows for the specified user and external round id.
         */
        $ext_round_id = $request->RoundId ?? '';
        $this->attachPrefix($ext_round_id);

        $rounds = $this->getRoundsByExtRoundId($this->uid, $ext_round_id);

        $bets_and_wins = $this->getRoundTransactions($rounds, true, true);
        $bets = array_filter($bets_and_wins['bets'], function ($row) {
            return (substr($row['mg_id'], -strlen('ref')) !== 'ref');
        });
        $wins = array_filter($bets_and_wins['wins'], function ($row) {
            return (substr($row['mg_id'], -strlen('ref')) !== 'ref');
        });
        $uncancelled_bets_and_wins = array_merge($bets, $wins);

        /**
         * Even if there are no transactions to cancel we must still close the round.
         */
        if (empty($uncancelled_bets_and_wins)) {
            $params = json_decode(json_encode(['roundid' => $ext_round_id]), false);
            $this->closeRound($params);

            $this->_response($this->_getError(self::ER05));     // TransactionAlreadySettled
        }

        /**
         * We cannot send the 'amount' parameter to 'Gp::_cancel' because of a design issue.
         * 'Gp::_cancel' cycles through 'db.bets' and 'db.wins' looking for a row with matching 'mg_id' but
         * if we send 'amount' and the row has a different 'amount' then 'Gp::_cancel' returns an error.
         * The Pariplay 'DebitAndCredit' request creates a bet and a win with the same 'mg_id' but with different
         * amounts for each, which can cause 'Gp::_cancel' to incorrectly fail when trying to cancel the transactions.
         *
         * 'mg_id' values must be unique because 'Gp::_cancel' cancels all rows with that 'mg_id' value in both
         * db.bets and db.wins (or in db.bets_mp and db.wins_mp).
         */
        $unique_mg_id = [];
        $actions = [];
        foreach ($uncancelled_bets_and_wins as $bet_or_win) {
            if ($unique_mg_id[$bet_or_win['mg_id']] ?? false) {
                continue;
            }
            $unique_mg_id[$bet_or_win['mg_id']] = true;
            $actions[] = [
                'command' => $wallet_method,
                'parameters' => [
                    'transactionid' => $bet_or_win['mg_id'],
                    'roundid' => $ext_round_id,
                ],
            ];
        }
        $n = count($actions);
        $actions[$n - 1]['parameters']['endgame'] = true;

        $wallet_parameters['state'] = 'multi';
        $wallet_parameters['actions'] = $actions;

        return $wallet_parameters;
    }

    /**
     * @param array $wallet_parameters
     * @return array
     */
    private function parseRequestRollbackTransaction(array $wallet_parameters): array
    {
        $gp_method = $this->getGpMethod();
        $wallet_method = $this->getWalletMethodByGpMethod($gp_method);
        $request = $this->getGpParams();

        /**
         * We ignore the rollback request "Amount" parameter because Gp.php::_cancel() does not support partial
         * rollbacks. If the "Amount" parameter does not match the full bet/win amount then
         * Gp.php::_cancel() returns an error, which is not what we want.
         */
        $map = ['RefTransactionId' => 'transactionid' /*, 'Amount' => 'amount' */];

        $parameters = $this->parseRequestParameters($request, $map);
        if (!($parameters['transactionid'] ?? false)) {
            $this->_response($this->_getError(self::ER08));     // TRANSACTION_NOT_FOUND
        }
        $this->attachPrefix($parameters['transactionid']);

        $rows = [];
        $mg_id = [$parameters['transactionid'], "{$parameters['transactionid']}ref"];
        $tables = $this->isTournamentMode() ? ['bets_mp', 'wins_mp'] : ['bets', 'wins'];
        foreach ($tables as $table) {
            $rows = $this->getTransactionsByMgid($this->uid, $table, $mg_id);
            if (empty($rows)) {
                continue;
            }

            if (substr($rows[0]['mg_id'], -strlen('ref')) == 'ref') {
                $this->_response($this->_getError(self::ER28));     // TRANSACTION_ALREADY_CANCELLED
            }
            break;
        }
        if (empty($rows)) {
            $this->_response($this->_getError(self::ER08));     // TRANSACTION_NOT_FOUND
        }

        $wallet_parameters['state'] = 'single';
        $wallet_parameters['action'] = [
            'command' => $wallet_method,
            'parameters' => $parameters,
        ];

        return $wallet_parameters;
    }

    /**
     * @param stdClass $raw_request
     * @param array $field_name_map
     * @return array
     */
    private function parseRequestParameters(stdClass $raw_request, array $field_name_map): array
    {
        $response = [];

        foreach ($field_name_map as $raw_field_name => $parsed_field_name) {
            if (isset($raw_request->$raw_field_name)) {
                $response[$parsed_field_name] = $raw_request->$raw_field_name;

                if ($parsed_field_name == 'amount') {
                    $response[$parsed_field_name] = $this->convertFromToCoinage(
                        $response[$parsed_field_name],
                        self::COINAGE_UNITS,
                        self::COINAGE_CENTS
                    );
                }
                if ($parsed_field_name == 'transactionid') {
                    $response[$parsed_field_name] = $raw_request->RoundId .'.'. $raw_request->$raw_field_name;
                }
            }
        }

        return $response;
    }

    /**
     * @param stdClass $parameters The parameters for the wallet method.
     * @return array|bool Returns true if the operation was successful, else the error array.
     */
    protected function _bet(stdClass $parameters)
    {
        $response = parent::_bet($parameters);
        if ($response !== true) {
            return $response;
        }

        $bet_id = $this->_getTransaction('txn', self::TRANSACTION_TABLE_BETS);
        if (empty($bet_id)) {
            $this->addLogMessage("No bet was inserted.");
            return true;
        }

        // We set win_id = -1 if the Pariplay round is closed.
        $is_round_closed = (bool)($parameters->endgame ?? false);
        $win_id = $is_round_closed ? -1 : 0;

        return true;
    }

    /**
     * Inserts the win into db.wins then updates db.rounds.
     * Pariplay supports multiple bets and multiple wins for the same round.
     * If the win indicates EndGame then we update the round bet row with the win id and insert
     * a new row with bet_id = -1 to indicate that the round is closed.
     *
     * @param stdClass $parameters The parameters for the wallet method.
     * @return array|bool Returns true if the operation was successful, else the error array.
     */
    protected function _win(stdClass $parameters)
    {
        $response = parent::_win($parameters);
        if ($response !== true) {
            return $response;
        }

        $win_id = $this->_getTransaction('txn', self::TRANSACTION_TABLE_WINS);
        if (empty($win_id)) {
            $this->addLogMessage("No win was inserted.");
        }

        return true;
    }

    /**
     * @param stdClass $parameters
     * @return array|bool
     */
    protected function _cancel(stdClass $parameters)
    {
        if ($parameters->transactionid ?? false) {
            return parent::_cancel($parameters);
        }
        if ($parameters->endgame ?? false) {
            $this->closeRound($parameters);
        }
    }

    /**
     * @param stdClass $parameters
     * @return bool
     */
    protected function betwin(stdClass $parameters)
    {
        return true;
    }

    /**
     * We use -1 to implement Pariplay's concept of "closed rounds".
     * If a round has a bet and no win (the most common situation), we set win_id = -1.
     * Pariplay support multiple wins per round so if there is a round row with a win and no bet we set bet_id = -1.
     * If all else fails we insert a new row with bet_id = 0, round_id = [round Id], win_id = -1.
     *
     * @param stdClass $parameters
     * @return array|bool|mixed
     */
    private function closeRound(stdClass $parameters)
    {
        return true;
    }

    /**
     * @return array|bool|mixed
     */
    private function closeAllOpenRounds()
    {
        // Not allowed on Production. Only for Pariplay Automated Test Suite.
        if ($this->getSetting('test') !== true) {
            return $this->_getError(self::ER02);
        }

        return true;
    }

     /**
     * @return bool
     */
    public function doConfirmByRoundId()
    {
        return false;
    }
}
