<?php

require_once __DIR__ . '/../../../phive/modules/Micro/TypeOne.php';

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.06.28.
 * Time: 14:33
 */
class Redtiger extends TypeOne
{
    protected $errorCodes = [
        100 => 'Invalid user token!',
        106 => 'User balance update error!',
        124 => 'User has insufficient funds!',
        107 => 'User not found!',
        108 => 'Invalid API KEY!',
        109 => 'Transaction not found!',
        130 => 'User banned!',
        140 => 'Invalid currency code!',
        143 => 'Invalid game round!',
        500 => 'Internal server error'
    ];

    function activateFreeSpin(&$entry, $na, $bonus) {
        $entry['status'] = 'approved';
    }

    /**
     * @param mixed $uid The user ID or user + tournament ID.
     * @param $gids. db.micro_games.ext_game_id. Eg. redtiger_JadeCharms or redtiger_JadeCharms-VS3. Jackpot games are suffixed with VS3.
     * @param $rounds
     * @param $bonus_name
     * @param $entry
     * @return bool|SimpleXMLElement
     */
    function awardFRBonus($uid, $gids, $rounds, $bonus_name, $entry) {
        if($this->getSetting('no-out') === true) {
            phive()->dumpTbl('rtg_test_frb_awarded', func_get_args());
            return true;
        }
        $bonus = phive('Bonuses')->getBonus($entry['bonus_id']);

        if ($this->getLicSetting('use_redtiger_bonus_api_v2', $uid)) {
            return $this->postCreateBonusV2($uid, $gids, $entry, $bonus);
        }

        $start_time = phive()->modTz('UTC');
        $end_time   = phive()->modTz('UTC', $entry['end_time']);

        $ud = ud($uid);

        $arr = [
            'title'     => $entry['id'],
            'currency'  => $ud['currency'],
            'stake'     => (mc($bonus['frb_denomination'], $ud['currency'], 'multi', false) / 100),
            'startTime' => $start_time,
            'endTime'   => $end_time,
            'active'    => true,
            'spins'     => $bonus['reward'],
            'games'     => [$this->stripRt($gids)],
            'users'     => [$uid . $prefix = $this->getSetting('brand_username_suffix', '')],
            'bonusCode' => $entry['id']
        ];

        phive()->post($this->getLicSetting('base_url').'campaigns/add', json_encode($arr), 'application/json', ["Authorization: Basic ".$this->getLicSetting('api_key', $uid)], 'redtiger-award-frb');
    }

    /**
     * Posts a CreateBonus request to the Red Tiger Bonus API v2.
     *
     * @param mixed $uid The user ID or user + tournament ID.
     * @param string $jackpot_game_id. eg. The game ID, e.g. 'redtiger_JadeCharms' or jackpot game ID, e.g. 'redtiger_JadeCharms-VS3'. Jackpot games are suffixed with VS3.
     * @param array $bonus_entry
     * @param array $bonus
     * @return false|mixed
     */
    private function postCreateBonusV2($uid, $jackpot_game_id, $bonus_entry, $bonus)
    {
        $ud = null;
        $user = cu($uid);
        if ($user) {
            $ud = $user->getData();
        }
        if (!is_array($ud) || empty($ud)) {
            phive()->dumpTbl('User not found (postCreateBonusV2).', func_get_args());
            return false;
        }

        $user_uid = $ud['id'];

        $jackpot_game_id = $this->stripRt($jackpot_game_id);
        list($site_id, $game_id) = $this->getEnvironment($user, $jackpot_game_id);
        $player_info = $this->getPlayerAttrBranded($user_uid, $site_id);

        $expires_at = $bonus_entry['end_time'] . ' 23:59:59';
        $seconds_to_expiration = max(0, strtotime($expires_at) - time());

        $stake = mc($bonus['frb_denomination'], $ud['currency'], 'multi', false);
        $stake = round($stake / 100, 2);

        $arr = [
            "userId" => $player_info,
            "casino" => $site_id,
            "currency" => $ud['currency'],
            "instanceCode" => $bonus_entry['id'],
            "campaignCode" => "0",
            'games' => [$game_id],
            "periods" => [
                "pending" => $seconds_to_expiration,
                "preactive" => $seconds_to_expiration,
                "active" => $seconds_to_expiration,
                "postactive" => $seconds_to_expiration,
            ],
            "type" => "spins",
            "subtype" => "finite",
            "preset" => "normal",
            "params" => [
                "stake" => number_format($stake, 2),
                "spins" => $bonus['reward'],
            ],
        ];

        $url = $this->getLicSetting('url_bonus_api_v2', $user) . '/bonuses/create';
        $request = json_encode($arr);
        $headers = $this->getBonusApiAuthenticationHeaders($arr, $user);
        $log_key = 'redtiger-award-frb-v2';

        $result = phive()->post($url, $request, 'application/json', $headers, $log_key);

        $result = json_decode($result);
        return $result->result->id ?? false;
    }

    /**
     * Returns the authentication headers for the bonus API request.
     * The RT Bonus API Specification v.2.1.3 explains that we must concatenate the bonus_api_secret and md5 the result.
     * Note that unlike v.1.3 we no longer include "Authorization: Basic {$API_KEY}".
     *
     * @param array $request_body The array of request parameters.
     * @param mixed $user.
     * @return array The authentication headers.
     */
    private function getBonusApiAuthenticationHeaders(array $request_body, $user): array
    {
        $bonus_api_key = $this->getLicSetting('bonus_api_key', $user);
        $bonus_api_secret = $this->getLicSetting('bonus_api_secret', $user);
        $requestBodyAndSecret = json_encode($request_body) . $bonus_api_secret;
        $hash = md5($requestBodyAndSecret);
        return [
            "key: {$bonus_api_key}",
            "hash: {$hash}",
        ];
    }
    
    function execute($requestData){
        if(!in_array(remIp(), $this->getSetting('valid_ips'))) {
            return $this->errorMessage('Wrong IP', 108);
        }

        $this->game_action = $_GET['action'];
        if(!empty($requestData->transaction->rgsGameId)){
            $this->gref = $this->putRt($requestData->transaction->rgsGameId);
        }

        if($this->game_action == 'transactions'){
            $this->main_action = 'transactions';
            $this->game_action = $_GET['param'];
            if(!method_exists($this, $this->game_action)) {
                $this->game_action = 'actionGetTransaction';
            }
        }
        // We have to loop thorugh the transaction details in order to handle REFUND requests
        if($this->game_action == 'payin'){
            foreach($requestData->transaction->details as $detail) {
                if($detail->action == 'REFUND') {
                    $this->game_action = 'refund';
                    break;
                }
            }
        }

        //getDataFromToken is using $this->game_action
        $this->getDataFromToken(urldecode($requestData->userToken));
        if (!empty($this->gref) && $this->getSetting('environment')['JACKPOT'] == $this->session_data['environment']) {
            $this->gref .= '-' . $this->getSetting('environment')['JACKPOT'];
        }
        $this->new_token['game_ref'] = $this->gref;

        // We don't check token in case we're looking at a rollback
        if(empty($this->ud) && $this->game_action != 'refund') {
            return $this->errorMessage('wrong token', 100);
        }

        return call_user_func_array([$this, $this->game_action], [$requestData]);
    }
    
    function _getBalance($ud, $req = null, $divide = true){
        $balance = parent::_getBalance($ud, $req);
        return $divide ? $balance / 100 : $balance;
    }

    function getReturn($user, $req, $extra = []){
        $result = [
            'userToken' => $req->userToken,
            'wallets' => [
                'cash'  => [
                    'balance'   => $this->_getBalance($user, $req),
                    'currency'  => !empty($this->t_entry) ? phive('Tournament')->curIso() : $user['currency']
                ],
                'bonus' => [
                    'balance'   => 0.00,
                    'currency'  => $user['currency']
                ]
            ]
        ];

        $result = array_merge($result, $extra);

        $ret = [
            'success' => true,
            'result' => $result
        ];

        return $ret;
        
    }
    
    /**
     * @param array $requestData
     * @return array
     */
    public function token($requestData = null)
    {
        $user = $this->ud;
        $rtPlayerId = !empty($this->t_entry) ? $this->uid : $this->getPlayerAttrBranded($user['id'], $this->session_data['environment']);

        $extra = [
            'userId'        => $rtPlayerId,
            'userName'      => $this->getPlayerAttrBranded($user['username'], $this->session_data['environment']),
            'language'      => $user['preferred_lang'],
            'isTestUser'    => ((strpos($user['username'], 'devtest') !== false) ? true : false),
            'name'          => $user['firstname'] .' '. $user['lastname'],
        ];

        // TODO this needs to be optimized, we are doing 2 times the user query //Ricardo
        if (!empty($this->getLicSetting('set_country', $user['id']))) {
            $extra = array_merge($extra, ['country' => $user['country']]);
        }

        if ($this->getSetting('debug', false)) {
            phive()->dumpTbl('redtiger-token', $extra);
        }
        return $this->getReturn($this->ud, $requestData, $extra);
    }

    /**
     * Makes sure user is unique in redtiger system in case of jackpot games or different casino brands
     *
     * @param $attribute
     * @param $environment
     * @return string
     */
    private function getPlayerAttrBranded($attribute, $environment)
    {
        $rtPlayerInfo = $attribute;
        if (!empty($casino_brand = $this->getSetting('casino_brand'))) {
            $rtPlayerInfo .= $casino_brand;
        }
        if ($environment === $this->getSetting('environment')['JACKPOT']) {
            $rtPlayerInfo .= $environment;
        }

        return $rtPlayerInfo;
    }

    /**
     * /transactions/payout
     *
     * @param null $requestData
     * @return array
     */
    public function payout($requestData = null){
        $userBalance           = $this->_getBalance($this->ud, $requestData, false);
        $redTigerTransactionId = $requestData->transaction->rgsTxId;
        $round_id = $requestData->transaction->rgsRoundId;

        $bet = $this->getBetByMgId("redtiger".$redTigerTransactionId); // check if this balance update has already been handled by us
        $duplicated = false;
        if(!empty($bet)){
            $duplicated              = true;
            $videoSlotsTransactionId = $bet['id'];
        } else {
            $transactionAmount   = $requestData->transaction->amount * 100;
            $transactionCurrency = $requestData->transaction->currency; // can be ignored, should be exactly the same as the user's currency

            $videoSlotsTransactionId = null;

            if (!empty($transactionAmount)) {
                if (empty($userBalance) || $transactionAmount > $userBalance) {
                    return $this->errorMessage('User has insufficient funds!', 124);// if called with $balance = 0
                }

                // TODO: I have to deal with missing/wrong game ids
                $cur_game = phive('MicroGames')->getByGameRef($this->gref);
                if (empty($cur_game['active'])) {
                    if ($this->getSetting('dump_log')) {
                        phive()->dumpTbl(
                            'redtiger-bet-error',
                            ['error' => "Active game not found {$this->gref}.", 'request' => $requestData],
                            $this->ud['id'] ?? 0
                        );
                    }
                    return $this->errorMessage('User has insufficient funds!', 124);
                }

                $balance = $this->lgaMobileBalance($this->ud, $cur_game['ext_game_name'], $userBalance, $cur_game['device_type'], $transactionAmount);

                if ($balance < $transactionAmount){
                    return $this->errorMessage('User has insufficient funds!', 124);
                }
              
                $jp_contrib = $transactionAmount * $cur_game['jackpot_contrib'];

                $balance = $this->playChgBalance($this->ud, -$transactionAmount, $round_id, 1);
                if($balance === false) {
                    return $this->errorMessage('User balance update error!', 106);
                }
                $bonus_bet  = empty($this->bonus_bet) ? 0 : 1;

                $videoSlotsTransactionId     = $this->insertBet($this->ud, $cur_game, $round_id, 'redtiger'.$redTigerTransactionId, $transactionAmount, $jp_contrib, $bonus_bet, $balance);
                if($videoSlotsTransactionId === false) {
                    return $this->errorMessage('Insert bet error', 106);
                }


                $balance    = $this->betHandleBonuses($this->ud, $cur_game, $transactionAmount, $balance, $bonus_bet, $round_id, "redtiger".$redTigerTransactionId);
            }
        }

        return $this->getReturn($this->ud, $requestData, [
            'rgsTxId'       => $redTigerTransactionId,
            'rgiTxId'       => $videoSlotsTransactionId,
            'duplicated'    => $duplicated            
        ]);
    }

    /**
     * Unfortunately cancellation of a transaction will happen here
     *
     * /transactions/payin
     *
     * @param null $requestData
     * @return array
     */
    public function payin($requestData = null)
    {
        if ($this->getSetting('debug', false)) {
            phive()->dumpTbl('redtiger-payin', $requestData);
        }
        $duplicated = false;
        $redTigerTransactionId  = $requestData->transaction->rgsTxId;
        $bonus_ext_id           = $requestData->transaction->bonusId;
        $redtigerRoundId        = $requestData->transaction->rgsRoundId;
        $transactionAmount      = (float)$requestData->transaction->amount*100;
        $transactionCurrency    = $requestData->transaction->currency;
        $userBalance            = null;
        $videoSlotsTransactionId = null;

        // check for idempotency
        $result = $this->getBetByMgId("redtiger".$redTigerTransactionId, 'wins'); // check if we have this win
        if($result) {
            // duplicate transaction
            $duplicated = true;
            $videoSlotsTransactionId = $result['id'];
        } else {
            $cur_game = phive('MicroGames')->getByGameRef($this->gref);
            if (empty($cur_game)) {
                if ($this->getSetting('dump_log')) {
                    phive()->dumpTbl(
                        'redtiger-win-error',
                        ['error' => "Game not found {$this->gref}.", 'request' => $requestData],
                        $this->ud['id'] ?? 0
                    );
                }
                return $this->errorMessage('Insert win error.', 106);
            }
            if (!empty($bonus_ext_id) && !isset($requestData->transaction->bonusSpinIsDone)) {
                $bonus_bet = 3;
                $filter = 'ext_id';
                $this->frb_win = true;
                $bonus = phive('Bonuses')->getBonusEntryBy($this->ud['id'], $bonus_ext_id, $filter, $bonus_ext_id);
                if (!empty($bonus)) {
                    $arr = ['frb_denomination', 'frb_lines', 'rake_percent', 'frb_coins', 'game_id'];
                    $bonus_entries = array_diff_key($bonus, array_flip($arr));
                    $this->handleFspinWin($bonus_entries, $transactionAmount, $this->ud['id'], 'Freespin win');
                    $award_type = 2;
                    if (!empty($transactionAmount)) {
                        $redTigerTransactionId = $cur_game['network'] . $redTigerTransactionId;
                        $userBalance = $this->_getBalance($this->ud, $requestData, false);

                        $videoSlotsTransactionId = $this->insertWin(
                            $this->ud,
                            $cur_game,
                            $userBalance,
                            $redtigerRoundId,
                            $transactionAmount,
                            $bonus_bet,
                            $redTigerTransactionId,
                            $award_type
                        );
                        if ($videoSlotsTransactionId === false) {
                            return $this->errorMessage('Insert win error.', 106);
                        }
                    }
                    phive('Bonuses')->resetEntries();
                } else {
                    phive()->dumpTbl('redtiger_frbwin_failure', "Can not find the bonus entry with ext_id: {$bonus_ext_id}", $this->ud);
                }
            }
            if (!empty($transactionAmount) && empty($bonus_ext_id) ) {

                // Since when the jackpot is being won, it is sent in the same request as the normal win we need to iterate through
                // the request to insert a normal win and also the jackpot win
                // since everything is being sent in the same request with the same transaction id (mg_id) then we need to change the
                // mg_id for the jackpot win and appended jackpot to it, else the insert will not happen due to duplicate mg_id

                // So for security reasons we only iterate maximum of 2 times
                // To check for jackpot win we are using description, as till today there is no other way to differentiate the wins
                // as both actions are 'WIN'
                $win_transactions = $requestData->transaction->details;

                // Check if it is a jackot win
                if(count($win_transactions) > 1 && isset($win_transactions[1]) && $win_transactions[1]->description === "Jackpot win"){
                    // first insert normal win
                    // get each amount seperately
                    $transactionId = "redtiger". $redTigerTransactionId;
                    $transactionAmount      = (float)$win_transactions[0]->amount*100;
                    $userBalance = $this->_getBalance($this->ud, $requestData, false);

                    // insert normal win
                    list($balance, $videoSlotsTransactionId) = $this->insertWinAndCheckBalance(
                        $this->ud,
                        $cur_game,
                        $redtigerRoundId,
                        $transactionAmount,
                        $transactionId,
                        '2',
                        $userBalance
                    );

                    // insert second jackpot win
                    $transactionId = "redtiger_jackot_". $redTigerTransactionId;
                    $transactionAmount      = (float)$win_transactions[1]->amount*100;
                    $userBalance = $this->_getBalance($this->ud, $requestData, false);

                    list($balance, $videoSlotsTransactionId) = $this->insertWinAndCheckBalance(
                        $this->ud,
                        $cur_game,
                        $redtigerRoundId,
                        $transactionAmount,
                        $transactionId,
                        4,
                        $userBalance
                    );
                } else {
                    // insert win
                    $award_type = 2; // normal win
                    $userBalance = $this->_getBalance($this->ud, $requestData, false);

                    // in the case there is a jackpot win only, then we need to flag it accordingly
                    if(isset($win_transactions[0]) && $win_transactions[0]->description === "Jackpot win"){
                        $award_type = 4; // jackpot win
                    }

                    list($balance, $videoSlotsTransactionId) = $this->insertWinAndCheckBalance(
                        $this->ud,
                        $cur_game,
                        $redtigerRoundId,
                        $transactionAmount,
                        "redtiger". $redTigerTransactionId,
                        $award_type,
                        $userBalance
                    );
                }

                $userBalance = $balance;
            } else {
                $userBalance = $this->_getBalance($this->ud, $requestData);
            }
        }

        //we can give stale user, we return a fresh bonus balance anyway
        return $this->getReturn($this->ud, $requestData, [
            "rgsTxId"       => $redTigerTransactionId,
            "rgiTxId"       => $videoSlotsTransactionId,
            "duplicated"    => $duplicated            
        ]);
        
    }


    /**
     *Inserts the win and checks that it was inserted without any errors
     *
     * @param int $user_id
     * @param object $cur_game
     * @param int $redtigerRoundId
     * @param int $transactionAmount
     * @param int $transactionId
     * @param int $award_type
     *
     * @return array $balance,$visdeoslotsTransactionId
     */
    public function insertWinAndCheckBalance($user_id, $cur_game, $redtigerRoundId, $transactionAmount, $transactionId, $award_type, $user_balance){

        $videoSlotsTransactionId = $this->insertWin(
            $user_id,
            $cur_game,
            $user_balance,
            $redtigerRoundId,
            $transactionAmount,
            $this->bonusBetType(),
            $transactionId,
            $award_type
        );

        if($videoSlotsTransactionId === false) {
            return $this->errorMessage('Insert win error.', 106);
        }

        $balance = $this->playChgBalance($this->ud, $transactionAmount, $redtigerRoundId, 2);

        if($balance === false) {
            return $this->errorMessage('User balance update error!', 106);
        }

        return [$balance, $videoSlotsTransactionId];
    }


    /**
     * Return transaction by RedTiger transaction id
     *
     * @param null $requestData
     * @return array
     */
    public function actionGetTransaction($requestData = null){
        $redTigerTransactionId = $_GET['param'];
        $transaction = phive('Casino')->getBetByMgId('redtiger'.$redTigerTransactionId, 'bets');
        if(empty($transaction)) {
            $transaction = phive('Casino')->getBetByMgId('redtiger'.$redTigerTransactionId, 'wins');
        }

        // transaction is not found
        if(empty($transaction)){
            $ret = [
                'success' => false,
                'error' => [
                    'msg' => "Error, transaction is not found",
                    'details' => "Error, this transaction is not found: {$redTigerTransactionId}",
                    'code'  => 104
                ]
            ];
            return $ret;
        }

        $ret = [
            "success" => true,
            "result" => [
                'userToken'     => $requestData->userToken,
                "status"        => "COMPLETED", // FAILED / PENDING
                "rgsRoundId"    => $requestData->transaction->rgsRoundId,
                "rgiTxId"       => $transaction['id'],
                "rgsTxId"       => $redTigerTransactionId,
                "rgsGameId"     => $this->stripRt($transaction['game_ref']),
                "amount"        => $transaction['amount'] / 100,
                "currency"      => $transaction['currency']
            ]
        ];

        return $ret;
    }

    function refundTransaction($tx_id, $tbl){
        $type = $tbl == 'bets' ? 7 : 1;
        $trans = $this->getBetByMgId("{$tx_id}ref", $tbl);
        
        if(!empty($trans)){ // it is already cancelled
            //Do we do something here?
        } else {
            $trans  = $this->getBetByMgId($tx_id, $tbl);
            $amount = $trans['amount'];
            if(!empty($trans)){
                //We can not rely on the token session being available so we get the user data like this instead.
                $this->ud = ud($trans['user_id']);
                $amount = $tbl == 'bets' ? $amount : -$amount;
                $updatedBalance = $this->playChgBalance($this->ud, $amount, $trans['trans_id'], $type); // refund
                $this->doRollbackUpdate($tx_id, $tbl, $updatedBalance, $amount);
            }            
        }
      
        return $trans;
    }
    
    /**
     * Refund/cancel transaction
     *
     * @param array $requestData
     * @return array
     */
    public function refund($requestData = null){
        $redTigerTransactionId      = $requestData->transaction->rgsTxId;
        $redTigerTransactionRefInDb = 'redtiger'.$redTigerTransactionId;
        $bet                        = $this->refundTransaction($redTigerTransactionRefInDb, 'bets');
        //$win                        = $this->refundTransaction($redTigerTransactionRefInDb, 'wins');
        //$loc_id                     = empty($bet) ? $win['id'] : $bet['id'];
        $loc_id                     = $bet['id'];
        if(empty($loc_id)){
            return $this->errorMessage('Transaction not found!', 109);
            //$loc_id = uniqid();
        }
        return $this->getReturn($this->ud, $requestData, [
            'rgsTxId'       => $redTigerTransactionId,
            'rgiTxId'       => $loc_id,
            'duplicated'    => false            
        ]);
    }

    public function getGameRef($requestData = null)
    {
        if(!empty($this->gref)) {
            return $this->gref;
        }
        $this->gref = $this->session_data['gameid'];
        if(empty($this->gref)) {
            $this->gref = $this->putRt($requestData->transaction->rgsGameId);
        }
        return $this->gref;
    }

    /**
     * "Universal" error message "sender"
     *
     * @param $message
     * @param $details
     * @param $code
     * @return array
     */
    public function errorMessage($message, $code)
    {
        $ret = [
            'success' => false,
            'error' => [
                'msg'   => $message,
                'details'   => $this->errorCodes[$code],
                'code'      => $code,
            ]
        ];

        return $ret;
    }

    function stripRt($game_id){
        return str_replace('redtiger_', '', $game_id);
    }

    function putRt($game_id){
        return "redtiger_".$game_id;
    }

    /**
     * https://gserver.dopamine-gaming.com/videoslots/launcher/DragonsLuck?playMode=demo&lang=en-GB
     *
     * @param $gameId db.micro_games.ext_game_name. Eg. redtiger_JadeCharms or redtiger_JadeCharms-VS3. Jackpot games are suffixed with VS3.
     * @param $language
     * @param $device
     * @param bool $show_demo
     * @return string
     */
    protected function getUrl($gameId, $language, $device, $show_demo = false)
    {
        $this->initCommonSettingsForUrl();
        $rawGameId = $gameId;
        $gameId = $this->stripRt($gameId);

        $is_logged = isLogged();
        $user = cu();
        $rc_popup = $this->getRcPopup($device, $user);
        $ud = $is_logged ? $user->getData() : null;
        $uid = $_SESSION['token_uid'] ?? $ud['id'];
        list($environment, $gameId) = $this->getEnvironment($user, $gameId);

        $launch_params = [
            'playMode'  => $is_logged ? 'real' : 'demo',
            'lang'      => preg_replace("/_/", "-", phive('Localizer')->getLocale($language)),
            'token'     => $is_logged ? $this->putTokenToRedis($uid, $gameId, $device, $environment) : null,
            'siteId'    => $environment
        ];

        if($rc_popup == 'ingame') {
            $launch_params = array_merge($launch_params, (array)$this->getRealityCheckParameters($user, false, [
                'realityCheckMinutes', 'realityCheckElapsedMinutes', 'realityCheckLobbyUrl', 'realityCheckHistoryUrl'
            ]));
        }

        $base_launch_url = $this->getLicSetting('launch_url') . $gameId;
        $launch_url = $base_launch_url . '?' . http_build_query($launch_params);

        if ($this->getSetting('debug')) {
            $log = [
                'raw_game_id' => $rawGameId,
                'environment' => $environment,
                'launch_url' => $launch_url,
            ];
            phive()->dumpTbl('rtg_launch_url', array_merge(func_get_args(), $log), $uid);
        }

        return $launch_url;
    }

    public function addCustomRcParams($regulator, $rcParams)
    {
        $regulator_params = [
            'realityCheckMinutes'           => true,
            'realityCheckElapsedMinutes'    => true,
            'realityCheckLobbyUrl'          => true,
            'realityCheckHistoryUrl'        => true
        ];

        return array_merge($rcParams, $regulator_params);
    }

    public function mapRcParameters($regulator, $rcParams)
    {
        $mapping_params = [
            'realityCheckMinutes'           => 'rcInterval',
            'realityCheckElapsedMinutes'    => 'rcElapsedTime',
            'realityCheckLobbyUrl'          => 'rcLobbyUrl',
            'realityCheckHistoryUrl'        => 'rcHistoryUrl'
        ];

        $rcParams = phive()->mapit($mapping_params, $rcParams, [], false);

        $rcParams['realityCheckElapsedMinutes'] = (int)floor($rcParams['realityCheckElapsedMinutes']/60); // need this in minutes

        return $rcParams;
    }

    /**
     * https://gserver.dopamine-gaming.com/videoslots/launcher/DragonsLuck?playMode=real&lang=en-GB&token=m9YjvT6wry3Uhy3PxVrGKbYTpUGr4jqU
     *
     * @param string $gameId
     * @param string $language
     * @param $game
     * @param bool $show_demo
     * @return string
     */
    public function getDepUrl($gameId, $language, $game = null, $show_demo = false)
    {
        return $this->getUrl($gameId, $language, 'desktop', $show_demo);
        //return 'https://gserver.dopamine-gaming.com/videoslots/launcher/DragonsLuck?playMode=demo&lang=en-GB';
    }

    /**
     * @param $gameId
     * @param $language
     * @param $lobbyUrl
     * @param $game
     * @return string
     */
    public function getMobilePlayUrl($gameId, $language, $lobbyUrl, $game, $args = [], $show_demo = false)
    {
        return $this->getUrl($gameId, $language, 'mobile');
    }

    public function putTokenToRedis($userId, $gameId = '', $device = 'desktop', $environment)
    {
        $gameId = $this->putRt($gameId);
        $token = mKey($userId, phive()->uuid());
        phMset($token, json_encode(array('token' => $token, 'userId' => $userId, 'gameid' => $gameId, 'device' => $device, 'environment' => $environment)));
        return $token;
    }

    /**
     * Get the user data and refresh the token in Redis
     *
     * @param null $userToken
     * @return bool
     */
    public function getDataFromToken($userToken = null){
      $userId = null;
      $tokenData = phMget($userToken);
      if(!empty($tokenData)){
        $tokenArray = json_decode($tokenData, true);
        phM('expire', $userToken, 7200); // +2 hours
        $userId = $tokenArray['userId'];
        $this->session_data = $tokenArray;
      } elseif(strpos($userToken, '[') !== false) {
          $userId = getMuid($userToken);
          if($this->game_action != 'refund') {
              return false;
          }
          $a = explode('_', $userToken);
          if(!empty($a[0])){
              $userId = $a[0];
          }
      }

      $this->uid = $userId;
      $userId = $this->getUsrId($userId);
      $this->ud = ud($userId);

      return $tokenArray;
    }

    /**
     * environment countries are set on Redtiger config
     *
     * @param $user
     * @param $game_id
     * @return array
     */
    public function getEnvironment($user, $game_id)
    {
        $envs = $this->getSetting('environment');
        $jp_env = $envs['JACKPOT'];
        $count = 0;
        $new_game_id = str_replace("-{$jp_env}", '', $game_id, $count);
        if (!empty($count)) {
            return [$jp_env, $new_game_id];
        }

        $country = phive('Licensed')->getLicCountry($user);
        $environment = $envs[$country] ?? $envs["DEFAULT"];
        return [$environment, $game_id];
    }

    /**
     * We get all the jackpots from tehe provider and put them in the micro_jp table
     * The module_id should match with the module_id in the micro_games table
     *
     * @return array to be inserted into the micro_jp table
     */
    function parseJackpots(){
        $data = json_decode(phive()->get($this->getSetting('jp-feed')), true);
        $games = $data['result']['jackpots']['0']['games'];
        $pots = $data['result']['jackpots']['0']['pots'];

        $res = array();
        foreach($games as $game){
            foreach($pots as $pot) {
                $jp_id = 'redtiger_' . $pot['id'];
                    $res[] = array(
                        'jp_id' => $jp_id,
                        'jp_name' => $game,
                        'module_id' => $game . "_" . $pot['id'] ,
                        'network' => 'redtiger',
                        'jp_value' => $pot['amount']);

            }
        }
        return $res;
    }


}
