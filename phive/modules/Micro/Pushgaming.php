<?php
require_once __DIR__ . '/Gp.php';

class Pushgaming extends Gp
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
    protected $_m_bFrwSendPerBet = false;

    /**
     * Update the status in bonus_entries table by the last FRW request from the GP in the _win() method. Default: true.
     * If set to false make sure that $this->_handleFspinWin() is called from the extended class.
     * @var bool
     */
    protected $_m_bUpdateBonusEntriesStatusByWinRequest = false;

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
    protected $_m_bForceHttpOkResponse = false;

    private $_m_aMapGpMethods = [
        'auth' => '_init',
        'wallet' => 'wallet',
        'txn' => 'txn',
        'cancel' => 'cancel'
    ];

    private $overrule_errors = [
        'ER03' => [
            'responsecode' => 401,
            'status' => 'AuthorizationException',
            'return' => 'default',
            'code' => 'ER03',
            'message' => 'The authentication credentials are incorrect.'
        ],
        'ER04' => [
            'responsecode' => 412,
            'status' => 'TxnTombstoneException',
            'return' => 'default',
            'code' => 'ER04',
            'message' => 'Bet transaction ID has been cancelled previously.'
        ],
        'ER06' => [
            'responsecode' => 412,
            'status' => 'InsufficientFundsException',
            'return' => 'default',
            'code' => 'ER06',
            'message' => 'Insufficient money in player account to fulfill operation.'
        ],
        'ER07' => [
            'responsecode' => 404,
            'status' => 'TxnNotFoundException',
            'return' => 'default',
            'code' => 'ER07',
            'message' => 'Transaction was not found.'
        ],
        'ER08' => [
            'responsecode' => 404,
            'status' => 'TxnNotFoundException',
            'return' => 'default',
            'code' => 'ER08',
            'message' => 'Transaction was not found.'
        ],
        'ER09' => [
            'responsecode' => 404,
            'status' => 'PlayerNotFoundException',
            'return' => 'default',
            'code' => 'ER09',
            'message' => 'Player not found.'
        ],
        'ER16' => [
            'responsecode' => 400,
            'status' => 'BadRequestException',
            'return' => 'default',
            'code' => 'ER16',
            'message' => 'Invalid request.'
        ],
        'ER25' => [
            'responsecode' => 403,
            'status' => 'PlayerLimitException',
            'return' => 'default',
            'code' => 'ER25',
            'message' => 'Player is blocked.'
        ],
        'ER26' => [
            'responsecode' => 403,
            'status' => 'PlayerStatusException',
            'return' => 'default',
            'code' => 'ER26',
            'message' => 'Player is banned.'
        ],
    ];

    protected string $logger_name = 'pushgaming';

    public function toWholeUnits($amount)
    {
        // We can't just divide by 100, because they want 1000 cents to become 10.00 units.
        return rnfCents($amount, '.', '');
    }

    public function toCents($amount)
    {
        return $amount * 100;
    }

    /**
     * This is the first call of every session. Requires token from the Game Start Communication Process.
     * Required parameters are mandatory to start Game Client.
     *
     * @return bool true on success
     */
    protected function _init()
    {
        parent::_init();
        $ud = $this->_getUserData();
        $init_data  = [
            'player' => [
                'playerId' => $ud['id'],
                'country' => $ud['country'],
                'lang' => $ud['preferred_lang'],
                'wallet' => $this->getAccountReturnSection($ud),
                'jurisdiction' => $this->getLicSetting('license', cu($ud)),
            ],
            'token' => $this->getTokenReturnData()
        ];

        if(!empty($ud['alias']))
            $init_data['alias']['player'] = $ud['alias'];

        $max_bet = phive('Gpr')->getMaxBetLimit(cu($ud));
        if (!empty($max_bet)) {
            $init_data['player']['maxStake'] = bcdiv($max_bet, 1, 2);
        }

        return $init_data;
    }

    public function getTokenReturnData()
    {
        return [
            'type' => 'Bearer',
            'token' => $this->_m_sSessionKey,
            'expires' => $this->getSetting('token_lifetime')
        ];
    }

    public function getAccountReturnSection($ud)
    {
        $balance = $this->_getBalance($ud);
        $ret_balance = $this->toWholeUnits($balance);
        return [
            'type' => 'ACCOUNT',
            'currency' => $ud['currency'],
            'balance' => $ret_balance,
            'funds' => [
                [
                    'type' => 'CASH',
                    'balance' => $ret_balance
                ]
            ]
        ];
    }

    public function wallet()
    {
        $ud = $this->_getUserData();
        return $this->getAccountReturnSection($ud);
    }

    public function txn()
    {
        $ud = $this->_getUserData();
        $ret = [
            'txnTs' => phive()->hisNow(),
            'status' => 'OK'
        ];

        $params = $this->getGpParams();

        // RGS_FREEROUND_WIN is currently not supported.
        $method_map = [
            'WIN' => '_win',
            'STAKE' => '_bet',
            'RGS_FREEROUND_CLEARDOWN' => '_win'
        ];

        $results = [];

        // They will always send only one action at a time, the actions array is there just for backwards compatibility.
        $a = $params['actions'][0];

        // TODO handle jackpot win?
        $param_obj = (object)[
            'amount' => $a['amount'] * 100,
            'roundid' => $params['rgsRoundId'],
            'transactionid' => $params['rgsTxnId']
        ];

        $method = $method_map[$a['type']];

        if (empty($method) || !method_exists($this, $method)) {
            // TODO test this.
            return $this->response(self::ER16);
        }

        if ($method == '_bet') {
            if (empty($params['txnDeadline'])) {
                // txnDeadline MUST exist for a bet, otherwise we return bad request.
                return $this->response(self::ER16);
            }

            $txn_stamp = strtotime($params['txnDeadline']);

            if ($txn_stamp < time()) {
                // The deadline has expired, we can't process this bet, we must return tombstone exception.
                $res = $this->response(self::ER04);
                return $res;
            }
        }

        $res = true;
        if ($a['type'] == 'RGS_FREEROUND_CLEARDOWN') {
            // We're looking at an FRB win.

            // RGS_FREEROUND_WIN will never be sent unless we tell them to turn it on.

            /*
               Short Explanation:

               Doing things in a custom fashion is necessary because of how the Push Gaming FRBs work, the
               default Gp logic doesn't work, it's impossible to achieve insert of win and at the same time AVOID
               crediting the player TWO times.


               Full explanation:

               - Push is sending the FRB win as a lump sum when the spins have all been played out, therefore we have
               protected $_m_bFrwSendPerBet = false and we WANT to have $_m_bUpdateBonusEntriesStatusByWinRequest = true but
               for some reason the current FRBs via Relax start out with status approved and frb_remaining for instance 10
               if we're looking at a 10 spin bonus. We're also looking at a bonus that does not need to be turned over, I didn't
               check how such a bonus is handled by the default logic.

               - We then want to just be able to do:
               1.) $this->_setFreespin($ud['id'], $a['rgsActionId']); to turn on FRB handling in Gp::_win(), this will however
               NOT insert a win which is what we want in this case, so not calling Gp::_setFreespin() will give us the
               correct behaviour at this stage, in Gp::_win().

               2.) We now want to run Gp::_handleFspinWin() in Gp::_win() if we do $this->_m_oRequest->freespin->id = $a['rgsActionId']
               before we call Gp::_win(). Gp::win() will call Gp::_handleFspinWin() which in turn calls Casino::handleWin() which
               in turn credits the player AGAIN.
             */


            $frb_entry = phive('Bonuses')->getBonusEntry($a['rgsActionId'], $ud['id']);
            if (empty($frb_entry)) {
                return $this->response(self::ER16);
            }

            if (empty($param_obj->amount)) {
                $ret['igpTxnId'] = uniqid();
                $ret['wallet'] = $this->getAccountReturnSection($ud);
                $ret['token'] = $this->getTokenReturnData();
                $this->handleFspinWin($frb_entry, $param_obj->amount, $ud['id'], '');

                return $ret;
            }

            $b = phive('Bonuses')->getBonus($frb_entry['bonus_id']);
            $turnover = phive('Bonuses')->getTurnover($ud, $b);

            // Inserting the win happens here.
            $win_id = $this->insertWin(
                $ud,
                $this->_getGameData(),
                // We don't add win amount to balance column if we have turnover requirements that will instead cause
                // the win to become a bonus balance that needs to be turned over.
                $ud['cash_balance'] + (empty($turnover) ? $param_obj->amount : 0),
                0,
                $param_obj->amount,
                self::FREESPIN_REWARD,
                $param_obj->transactionid,
                $this->_getAwardTypeCode($param_obj),
                null);

            if ($win_id) {
                // The credit happens here.
                $this->handleFspinWin($frb_entry, $param_obj->amount, $ud['id'], 'Freespin win');
            }

        } else {
            $res = $this->$method($param_obj);
        }

        if ($res !== true) {
            // A real error so we return immediately.
            return $res;
        }

        $ret['igpTxnId'] = $this->getInsertedTxnId();
        $ret['wallet'] = $this->getAccountReturnSection($ud);
        $ret['token'] = $this->getTokenReturnData();

        return $ret;
    }

    public function cancel()
    {
        $params = $this->getGpParams();

        $ud = $this->_getUserData();
        $ret = [
            'txnTs' => phive()->hisNow(),
            'status' => 'CANCELLED',
            'igpTxnId' => phive()->uuid()
        ];

        $txn_id = $params['rgsTxnId'];

        $param_obj = (object)[
            'transactionid' => $txn_id
        ];

        $res = $this->_cancel($param_obj);

        if ($res !== true) {
            // A real error so we return immediately.
            return $res;
        }

        $ret['wallet'] = $this->getAccountReturnSection($ud);

        return $ret;
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
            ->_mapGpMethods($this->_m_aMapGpMethods)
            ->_whiteListGpIps($this->getSetting('whitelisted_ips'))
            ->_overruleErrors($this->overrule_errors)
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
        $headers = getallheaders();
        $req_arr = json_decode($this->_m_sInputStream, true);

        // Lets do some errors checks first

        if ($headers['Operator-Api-Key'] != $this->getSetting('api_key')) {
            // 401 Unauthorized

            phive('Logger')->getLogger('pushgaming')->error('mismatching-api-ky', [
                'Operator-Api-Key' => $headers['Operator-Api-Key'],
                'our-api_key' => $this->getSetting('api_key'),
            ]);

            return $this->response(self::ER03, true);
        }


        $request_uri = explode('?',$_SERVER['REQUEST_URI']);
        $path_params = explode('/', $request_uri[0]);
        $gp_method = array_pop($path_params);
        $our_method = $this->getWalletMethodByGpMethod($gp_method);

        //lets check that method sent by gp is valid method that we have implemented
        if (!in_array($gp_method, array_keys($this->_m_aMapGpMethods))) {
            phive('Logger')->getLogger('pushgaming')->error('unsupported-gp_method', $gp_method);
            return $this->response(self::ER16, true);
        }

        $this->_setGpMethod($our_method);

        // if Authorization token is given in headers, read related session from redis
        $has_auth_token = !empty($headers['Authorization']);

        if ($has_auth_token) {
            list($bearer, $token) = explode(' ', $headers['Authorization']);
            if (!empty($token)) {
                $this->_m_sSessionKey = $token;
                $sess_data = $this->fromSession($token);
            }
        }

        // setting this variable separately to run following tests on playerId sent by GP.
        // 1. This should be a correct user_id of a player that exists in our system.
        // 2. When we launch game we create session based on token and user_id,
        //      Gp sends us token in headers. playerId is sent in two requests, in Wallet requests and in txn request.
        //      SO we need to cross check this id is the same that we have save in redis against a given token
        $gp_playerId = '';

        // for all wallet requests second-last parameter is always playerId, (we popped gp_method already from $path_params)
         if($gp_method == 'wallet')
             $gp_playerId = array_pop($path_params);

        if(!empty($req_arr['playerId']))
            $gp_playerId = $req_arr['playerId'];

        if(!empty($gp_playerId)) {
            // check if we have redis session based on token and user_id in that session is same as $gp_playerId
            // also check if its a valid user in our system
            if((!empty($sess_data) && $gp_playerId != $sess_data->userid) || !phive('UserHandler')->getUserByAttr('id', $gp_playerId))
                return $this->response(self::ER09, true);

            $req_arr['playerId'] = $gp_playerId;

        }


        // If we're looking at a request that is NOT a win OR a cancel we need to check that token is provided in request.
        if ($our_method != 'cancel' && $req_arr['actions'][0]['type'] != 'WIN') {
            if (!$has_auth_token || empty($this->_m_sSessionKey)) {
                // Auth token is missing, we return 401
                return $this->response(self::ER03, true);
            }

            // for 'RGS_FREEROUND_CLEARDOWN' we accept expired token aswell
            if (empty($sess_data) && $req_arr['actions'][0]['type'] != 'RGS_FREEROUND_CLEARDOWN') {
                // Token is either fradulent or has expired.
                return $this->response(self::ER03, true);
            }
        }

        $device_num = 0;
        if (!empty($req_arr['channel'])) {
            $device_num = $req_arr['channel'] == 'PC' ? 0 : 1;
        } else {
            if (!empty($sess_data)) {
                $device_num = $sess_data->device;
            }
        }

        $req = [
            'device' => $device_num,
            'action' => [
                'command' => $our_method,
                'parameters' => [

                ]
            ]
        ];

        if (!empty($req_arr['playerId'])) {
            $req['playerid'] = $req_arr['playerId'];
        }

        if (!empty($req_arr['rgsGameId'])) {
            $req['skinid'] = $req_arr['rgsGameId'];
        } else {
            $game_id_params = explode('=', $request_uri[1]);
            if (!empty($game_id_params[0]) && $game_id_params[0] == 'rgsGameId') {
                $req['skinid'] = $game_id_params[1];
            }
        }


        if ($our_method == 'cancel') {
            // They have the rgsTxnId in the path, NOT the request body, so we put it back for ease of use in self::cancel().
            // path looks like this rgs/hive/txn/11003-0302-19924-19924/cancel
            // 'cancel' was popped out already when extracting $gp_method
            $req_arr['rgsTxnId'] = array_pop($path_params);
        }

        $this->_setRequestObj($req, $sess_data ?? []);

        $this->_setGpParams($req_arr);
        $this->_setUserData();

        if (!empty($this->_m_oRequest->skinid)) {
            $this->_setGameData(true);
        }

        return $this;
    }

    /**
     * Execute the requested command from gaming provider
     * @return void
     */
    public function exec()
    {
        $this->_setActions();
        $action = $this->_getActions()[0];
        $method = $action->command;
        $this->_setWalletMethod($method);

        //Following doesnt log debug level entries
        /*
        $this->logger->debug('gp_request_999', [
            'action' => $action,
            'method' => $method,
        ]);
        */

        $response = empty($action->parameters) ? $this->$method() : $this->$method($action->parameters);

        phive('Logger')->getLogger('pushgaming')->debug('gp_response', $response);

        if (!is_array($response)) {

            // A string, we convert to an error array.
            $response = $this->_getError($response);
        }

        return json_encode($response);
    }

    /**
     * The logic responsible for outputting the reply to the GP.
     *
     * @param array|string $resp_arr The response data as passed in by Pushgaming::exec(), or an error key.
     *
     * @return null It will echo the result as an HTTP body.
     */
    protected function _response($resp)
    {
        $response = $resp;

        if (is_string($resp)) {
            // We're looking at an error so we get it.
            $response = $this->_getError($resp);
        }

        die(json_encode($response));
    }

    public function response($resp, $json_encode = false)
    {
        $response = $resp;

        if (is_string($resp)) {
            // We're looking at an error so we get it.
            $response = $this->_getError($resp);
        }

        return $json_encode ? json_encode($response) : $response;
    }

    /**
     * Get an error by it's key
     *
     * @param string $p_sKey The constant key eg. ER{XX}
     * @return mixed false if error was not found
     */
    protected function _getError($p_sKey)
    {
        $error_arr = parent::getErrorArr();
        $resp_arr = $error_arr[$p_sKey] ?? $error_arr[self::ER03];

        // Various idempotency errors we want to ignore.
        $non_errors = [self::ER18, self::ER05, self::ER28];
        if (in_array($resp_arr['code'], $non_errors)) {
            return true;
        }

        $this->_setResponseHeaders($resp_arr);
        return [
            'code' => $resp_arr['status'],
            'msg' => $resp_arr['message'],
            'timestamp' => phive()->hisNow(),
            'uiEvent' => $resp_arr['uiEvent'] ?? 'NONE',
            'reqId' => $resp_arr['reqId'] ?? phive()->uuid()
        ];
    }

    public function getError($key)
    {
        // parent::printErrors();
        return $this->_getError($key);
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
        $u_obj = cu();
        $lobby_url = $this->getLobbyUrl(false, $p_sLang, $p_sTarget);

        $params = [
            'rgsCode' => 'hive',
            'rgsGameId' => $p_mGameId,
            'lang' => cLang(),
            'jurisdiction' => $this->getLicSetting('license')
        ];

        if (!empty($u_obj)) {
            // Logged in.
            $uid = uid($u_obj);
            $ud  = ud($uid);
            $this->_m_sToken = $this->getGuidv4($uid);
            $this->toSession($this->_m_sToken, $uid, $p_mGameId, $p_sTarget);

            $locale = explode('_', phive('Localizer')->getLocale(cLang()));
            //for pushgaming
            $params['country'] = $locale[1] ?? getCountry();
            $params['playerId'] = $uid;
            $params['mode'] = 'REAL';
            $params['token'] = $this->_m_sToken;
            $params['lobbyUrl'] = $lobby_url;
        } else {
            // Not logged in.
            if (!$show_demo) {
                return false;
            }

            // NOTE, this type of DEMO play without token (ie unlogged) is per default disabled on the Push Gaming side, if we want this
            // we need to tell them to turn it on. They prefer logged in demo play.

            $params['mode'] = 'DEMO';
            $params['ccyCode'] = ciso();
        }

        $launch_url = str_replace('{igp_code}', $this->getLicSetting('igp_code'), $this->getLicSetting('launch_url')) . http_build_query($params);
        phive('Logger')->getLogger('pushgaming')->debug('launch_url', $launch_url);
        return $launch_url;
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
        $gids = [];
        if (empty($p_sGameIds)) {
            $bonus = phive('Bonuses')->getBonus($p_aBonusEntry['bonus_id']);
            $gids = [$bonus['game_id']];
        } else {
            $gids = explode('|', $p_sGameIds);
        }

        foreach ($gids as &$gid) {
            $gid = $this->stripPrefix($gid);
        }

        $u_obj = cu($p_iUserId);

        $endTimeUTC = new DateTime('now', new DateTimeZone('UTC'));
        $endTimeUTC->modify('+24 hours');
        $dateTimeFormatted = $endTimeUTC->format('Y-m-d\TH:i:s\Z');
        $params = [
            'bonus' => [
                'type' => 'multiCcyFreeRounds',
                'bonusId' => $p_aBonusEntry['id'],
                'rgsCode' => 'hive',
                // We just want two decimals, no thousands separator.
                'betAmount' => rnfCents($this->_getFreespinValue($p_iUserId, $p_aBonusEntry['id']), '.', ','),
                'ccyCode' => $u_obj->getCurrency(),
                'numFreeRounds' => (int)$p_iFrbGranted,
                'validUntilUTC' => $dateTimeFormatted,
                'rgsGameIds' => $gids,
                'igpCode' => $this->getLicSetting('igp_code'),
            ],
            'playerIds' => [$p_iUserId]
        ];

        $url = str_replace('{igp_code}', $this->getLicSetting('igp_code'),$this->getLicSetting('api_url')) . 'multiCcyBonusAward';
        $headers = ["Mesh-API-Key: " . $this->getLicSetting('mesh_api_key')];

        phive('Logger')->getLogger('pushgaming')->debug('awardFRBonus_to_gp',
            [
                'url'    => $url,
                'header' => $headers,
                'params' => $params,
            ]
        );
        $res = phive()->post($url, $params, 'application/json', $headers, 'pushgaming-award-frb-bonus');

        phive('Logger')->getLogger('pushgaming')->debug('awardFRBonus_gp_response', $res);

        if (empty($res)) {
            return false;
        }

        $res = json_decode($res, true);
        return $res['bonusPlayerAwardStatuses'][0]['status'] === 'active';
    }

    function cancelFRBonus($eid)
    {
        $url = $this->getLicSetting('api_url') . "bonusAward/$eid/forfeit";
        $headers = ["Mesh-API-Key: " . $this->getLicSetting('mesh_api_key')];
        $res = phive()->post($url, [], 'application/json', $headers, 'pushgaming-forfeit-frb-bonus');

        if (empty($res)) {
            return false;
        }

        return json_decode($res, true);
    }


}
