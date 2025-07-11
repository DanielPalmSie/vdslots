<?php
require_once __DIR__ . '/Casino.php';
class Yggdrasil extends Casino{

    function __construct(){
        parent::__construct();
    }

    function getSoap($action, $params, $ud){
        $u = cu($ud);
        $params['uid'] = $this->getLicSetting('api_login', $u);
        $params['pwd'] = $this->getLicSetting('api_pwd', $u);
        $params['org'] = $this->getLicSetting('org', $u);
        $param_str     = http_build_query($params);
        $url           = $this->getLicSetting('api_url', $u)."$action.json?$param_str";

        return json_decode(phive()->post($url, ' ', 'application/json', '', 'ygg_frb', 'GET'), true);
    }

    function awardFRBonus($uid, $gids, $rounds, $bonus_name, $entry) {

        $prepaid_id = $this->listPrepaidType($uid, $entry);
        $res = $this->addPrepaidToCampaign($uid, null, $entry, $prepaid_id); //EN

        return empty($res['msg']);
    }

    /**
     * createPrepaidCampaign in Yggdrasil BO, this should be made once manually and the number be stored in the
     * campaign_id for that jurisdiction in the config
     * @param int $uid
     * @param string $ref
     * @param string $lang
     * @param string $descr
     * @return bool true | false if error
     */
    function createPrepaidCampaign($uid, $ref, $lang, $description) {
        $ud = ud($uid);    // pass user according to jurisdiction you are going to create campaign for, example for UK pass a GB user
        $params = [
            'type'          => 'FREE_SPIN',
            'startDate'     => "2019-02-25 23:00:00",   //"{$entry['end_time']}+00:00:00",
            'endDate'       => "2100-05-25 23:00:00",   //"{$entry['end_time']}+00:00:00",
            'ref'           => $ref,
            'lang'          => $lang,
        ];
        $res = $this->getSoap('game/createPrepaidCampaign', $params, $ud);
        return empty($res['msg']);
    }

    /**
     * Lists the prepaid Types that exist for that particular game and jurisdiction
     * @param $uid
     * @param $entry
     * @return mixed
     */
    function listPrepaidType($uid, $entry) {
        $ud = ud($uid);
        $bonus = phive('Bonuses')->getBonus($entry['bonus_id']);
        $cg = phive('MicroGames')->getByGameId($bonus['game_id'], '');
        $gid = str_replace('yggdrasil_', '', $cg['ext_game_name']);

        $params = [
            'gameid'        => $gid,
            'aspect'        => 'BONUS',
            'currency'      => $ud['currency']];

        $res = $this->getSoap('game/listprepaidtypes', $params, $ud);
        // the first array contains the least amount to be paid for freespins.
        return $res['msg'] ?? $res['data'][0]['prepaidTypeId'];
    }

    /** Add Freespins to a campaign for user, using the prepaid_type_is from the above
     * @param $uid
     * @param $bonus_name
     * @param $entry
     * @param $prepaid_type_id
     * @return bool
     */
    function addPrepaidToCampaign($uid, $bonus_name, $entry, $prepaid_type_id) {
        $ud = ud($uid);
        $bonus = phive('Bonuses')->getBonus($entry['bonus_id']);

        $params = [
            'prepaidTypeId'  => $prepaid_type_id,
            'campaignId'     => $this->getLicSetting('campaign_id', $uid),
            'amount'         => phive()->twoDec(mc((int)$bonus['frb_denomination'], $ud['currency'])),
            'count'          => (int)$bonus['reward'],
            'nativeId'       => $uid,
            'ref'            => $entry['id'],
            'lang'           => $ud['preferred_lang'],
            'consumeBefore'  => "{$entry['end_time']} 00:00:00",
            'currency'       => $ud['currency'],
        ];
        $res = $this->getSoap('game/addPrepaidToCampaign', $params, $ud);
        return empty($res['msg']);
    }

    function campaignpayout($req){
        $amount         = $this->getAmount($req, 'bonus');
        $entry_id       = $req['campaignref'];
        $uid            = $req['playerid'];
        $user           = $this->getUsr();
        $fspin          = phive('Bonuses')->getBonusEntry($entry_id, $uid);
        $bonus_bet      = 3; //its a freespin
        $cur_game       = $this->getGameByRef();
        $ext_id         = "ygg_". $req['reference'];
        $tr_id          = $req['prepaidref'];

        if (empty($cur_game)) {
            $this->dumpTst('yggdrasil-nogame', $req);
        }

        if (!empty($amount)) {
            $balance = $this->_getBalance($uid);
            $result = $this->insertWin($user, $cur_game, $balance, $tr_id, $amount, $bonus_bet, $ext_id, 2);
            if (!$result) {
                return $this->error(1);
            }
        }

        $this->handleFspinWin($fspin, $amount, $uid, 'Freespin win');

        $balance = $this->_getBalance();
        if($balance === false) {
            return $this->error(1);
        }

        $res = $this->playerInfo();
        $res['applicableBonus'] = phive()->twoDec($amount);

        return $res;
    }

    function error($code){
        $msgs = [
            1 => "No user, no game or db error, gref: {$this->gref}, uid: {$this->uid}",
            1000 => 'Session timed out.',
            1006 => 'Not enough money.',
            1007 => 'Limit reached.'
        ];

        $this->dumpTst('ygg-error-request', $_REQUEST);

        $res = ['code' => $code, 'msg' => $msgs[$code]];

        $this->dumpTst('ygg-error-reply', $_REQUEST);

        return json_encode($res);
    }

    function execute($action){
        $whitelist = $this->getSetting('whitelisted_ips');

        if ($this->getSetting('test') !== true && !in_array(remIp(), $whitelist)) {
            $this->dumpTst('ygg-error-ipblock', $_SERVER);
            die('ipblock');
        }

        $this->dumpTst('request', $_REQUEST);
        $this->loadToken($_REQUEST['sessiontoken']); // should also set tournament

        if(empty($this->sess) && in_array($action, ['playerinfo', 'wager'])) {
            return $this->error(1000);
        }

        $u = $this->getUsr(); // sets tournament as well

        if(is_numeric($u) && in_array($action, ['playerinfo', 'wager'])) {
            return $this->error($u);
        }

        if($action == 'playerinfo') {
            $this->initSessionBalance($_REQUEST['sessiontoken']);
            $res = $this->playerinfo();
        } else {
            $res = $this->$action($_REQUEST);
        }

        if(is_string($res)) {
            return $res;
        }

        return json_encode(['code' => 0, 'data' => $res]);
    }

    function insertToken($token, $user_id, $gref, $is_tournament = false){
        $arr = ['user_id' => $user_id, 'game_ref' => $gref, 'token' => $token];
        $u_obj = cu($user_id);
        phMsetArr($token, $arr, $this->exp_time);
        phMsetShard('yggdrasil', $gref, $user_id);
        //phM('hmset', $token, ['user_id' => $user_id, 'game_ref' => $gref], $this->exp_time);
    }

    function loadToken($token){
        $this->dumpTst('token received', $token);
        if(!empty($token)) {
            $this->sess = phMgetArr($token, $this->exp_time);
            $this->uid = $this->sess['user_id'];
            $this->gref = $this->token['game_ref'] = $this->sess['game_ref'];

        } else {
            $this->uid = $_REQUEST['playerid']; // this is vital in cases like win as the getUsr function requires $this->uid
        }

        if ($this->useExternalSession($this->uid) && empty($this->t_entry)) {
            $ext_session_id = phMgetArr($this->uid, $this->exp_time);
            if(!empty($ext_session_id)) {
                $this->setSessionById(cu($this->uid), $ext_session_id);
            }
        }
    }

    function getUsr($uid = ''){
        $uid = empty($uid) ? $this->getUsrId($this->uid): $this->getUsrId($uid); // handles setting the tournament

        $this->user = cu($uid);

        $this->u = (isset($this->u)) ? $this->u : ud($this->user);

        return $this->u;
    }

    function _getBalance($uid = null){
        if(empty($this->t_entry)) {
            $u_obj = cu($uid ?? $this->uid);
            if($this->useExternalSession($u_obj)){
                $balance = $this->getSessionBalance($u_obj);
            } else {
                $balance = phive('UserHandler')->getFreshAttr($this->uid, 'cash_balance');
            }
            $bonus_balances = phive('Bonuses')->getBalanceByRef($this->gref, $this->uid);

            $balance += $bonus_balances;
        } else {
            $balance = $this->tEntryBalance();
        }

        return $balance;
    }

    /**
     * @param string $game_id
     * @return mixed
     * added an optional parameter to aid in testing
     */
    function getGameByRef($game_id = ''){

        if($_REQUEST['tag3'] === 'Channel.mobile') {
            $device = 'html5';
        } else {
            $device = 'flash';
        }

        if(!empty($game_id)) {
            if($_REQUEST['tag3'] === 'Channel.mobile' && !str_ends_with('_html', $this->gref)){
                $mobileGameId = $game_id.'_html';
                $this->game = phive('MicroGames')->getByGameRef('yggdrasil_'.$mobileGameId, $device, $this->user);

                // we could not find the mobile game with _html as part of the game id, we search again without it
                $this->game = empty($this->game) ? phive('MicroGames')->getByGameRef('yggdrasil_'.$game_id, $device, $this->user) : $this->game;
            }
            
            // default desktop search, we use is_null to not overwrite $this->game if we are on mobile
            if(is_null($this->game)) {
                $this->game = phive('MicroGames')->getByGameRef('yggdrasil_' . $game_id, $device, $this->user);
            }
        } else {
            if(empty($this->gref)) {
                $this->gref = "yggdrasil_".$_REQUEST['cat5']; // this is called in cases like the win where we don't have session token
            }

            // In DB for Yggdrasil, some mobile game_ref ends with '_html'
            if(empty($this->game) && $_REQUEST['tag3'] === 'Channel.mobile' && !str_ends_with('_html', $this->gref)) {
                $this->game = phive('MicroGames')->getByGameRef($this->gref . '_html', $device, $this->user);
            }
            if(empty($this->game)) {
                $this->game = phive('MicroGames')->getByGameRef($this->gref, $device, $this->user);
            }
            if(empty($this->game)) {
                $this->gref = 'yggdrasil_system';
                $this->game = phive('MicroGames')->getByGameRef($this->gref, $device, $this->user);
            }
        }

        return $this->game;
    }

    function getAmount($req, $key = 'amount'){
        return $req[$key] * 100;
    }

    function getMgId(){
        return "ygg{$_REQUEST['reference']}-{$_REQUEST['subreference']}";
    }

    function getStart($type = 'bets'){
        $mg_id = $this->getMgId();
        return [$this->u, $mg_id, $_REQUEST['subreference'], $this->getBetByMgId($mg_id, $type)];
    }

    function appendwagerresult($req){
        return $this->endwager($req);
    }

    function wager($req){
        list($user, $yggid, $rid, $result) = $this->getStart();

        if(!empty($result)){
            $balance 	= false;
        }else{
            $bet_amount  = $this->getAmount($req);
            $tmp_balance = $this->_getBalance();
            $cur_game    = $this->getGameByRef($req['cat5']);

            if(empty($cur_game)) {
                $this->dumpTst('yggdrasil-nogame', $req);
                return $this->error(1);
            }

            $balance = $this->lgaMobileBalance($user, $cur_game['ext_game_name'], $tmp_balance, $cur_game['device_type'], $bet_amount);

            if(!empty($bet_amount)){

                if($balance != $tmp_balance) {
                    return $this->error(1007);
                } else if($balance < $bet_amount) {
                    return $this->error(1006);
                }

                $GLOBALS['mg_id'] = $yggid;

                if(!empty($bet_amount)){
                    $balance = $this->playChgBalance($user, "-$bet_amount", $rid, 1);

                    if($balance === false) {
                        return $this->error(1);
                    }

                    $bonus_bet        = empty($this->bonus_bet) ? 0 : 1;

                    $result = $this->insertBet($user, $cur_game, $rid, $yggid, $bet_amount, 0, $bonus_bet, $balance);

                    if(!$result) {
                        return $this->error(1);
                    }
                }


                $balance = $this->betHandleBonuses($user, $cur_game, $bet_amount, $balance, $bonus_bet, $rid, $yggid);
            }
        }

        return $this->playerInfo($balance);
    }

    function endwager($req){
        list($user, $id, $rid, $result) = $this->getStart();
        $orig_result      = $this->getBetByMgId($id, 'wins');

        $GLOBALS['mg_id'] = $id;


        if(!empty($req['prepaidref'])){
            $amount         = $this->getAmount($req, 'bonusprize');
            $bonus_bet      = 3;
        } else {
            $amount         = $this->getAmount($req);
        }

        if(empty($amount)) {
            return $this->playerinfo($this->_getBalance());
        }

        if(empty($orig_result)){
            $cur_game   = $this->getGameByRef();

            if(empty($cur_game)) {
                $this->dumpTst('yggdrasil-nogame', $req);
                return $this->error(1);
            }

            if(empty($bonus_bet))
                $bonus_bet  = empty($this->bonus_bet) ? 0 : 1;

            if(!empty($amount)){
                $balance = $this->_getBalance($user['id']);
                $result = $this->insertWin($user, $cur_game, $balance, $rid, $amount, $bonus_bet, $id, 2);

                if(!$result)
                    return $this->error(1);

                $balance = $this->playChgBalance($user, $amount, $rid,  2);

                if($balance === false)
                    return $this->error(1);
            }

            $balance = $this->handlePriorFail($user, $rid, $balance, $amount);
        }else {
            $balance = $this->_getBalance();
        }

        $this->checkNextRound($user);
        return $this->playerInfo($balance);
    }

    function activateFreeSpin(&$entry, $na, $bonus) {
        $entry['status'] = 'approved';
    }

    function refund($tbl){
        list($user, $yggid, $rid, $result) = $this->getStart();
        $result = $this->getBetByMgId($yggid, $tbl);
        if(!empty($result)){
            $amount = $tbl == 'bets' ? $result['amount'] : -$result['amount'];
            $user = $this->getUsr($result['user_id']);
            //$extid = $result['id'];
            $this->changeBalance($user, $amount, $result['trans_id'], 7);
            $balance = $this->_getBalance($user['id']);
            if($balance === false)
                return $this->error(1);
            $this->doRollbackUpdate($yggid, $tbl, $balance, $amount);
        } else {
            // No bet found to refund, retrieve the current balance. And we don't store anything to bet table because we don't have game reference
            $balance = $this->_getBalance();
        }
        return $this->playerInfo($balance);
    }

    function cancelwager($req){
        return $this->refund('bets');
    }

    function playerInfo($balance = null){
        $u = $this->u;
        $balance = !empty($this->t_entry) ? $this->tEntryBalance() : ($balance ?? $this->_getBalance($u['id']));
        $user = cu($u['id']);
        $p_info = [
            'playerId'         => $this->uid, // we want to make sure the tournament entry is added here
            'nickName'         => $u['firstname'],
            'organization'     => $this->getLicSetting('org', $u),
            'balance'          => phive()->twoDec($balance),
            'applicableBonus'  => "0.00",
            'currency'         => $this->isTournament($this->uid) ? 'EUR' : $u['currency'],
            'country'          => $this->getCountry($u),
            'homeCurrency'     => $this->isTournament($this->uid) ? 'EUR' : $u['currency']
        ];

        $maxBetLimit = phive('Gpr')->getMaxBetLimit($user);
        if (!empty($maxBetLimit)) {
            $p_info['maxBetLimit'] = phive()->decimal($maxBetLimit);
        }

        $ext_session_id = phive('SQL')->getValue(null, 'ext_session_id', 'ext_game_sessions', ['id' => $this->session_entry['external_game_session_id']]);
        $p_info_additional = [
            'IT' => [
                'gameHistorySessionId' => $ext_session_id,
                'gameHistoryTicketId' => $this->session_entry['participation_id'],
            ]
        ];

        $country = $this->getCountry($u);
        $p_info = isset($p_info_additional[$country]) ? array_merge($p_info, $p_info_additional[$country]) : $p_info;

        return $p_info;
    }

    //yggdrasil_7302_html
    function remYgg($gref){
        $tmp = explode('_', $gref);
        return $tmp[1];
    }

    function getMobilePlayUrl($gref, $lang, $lobby_url, $g, $args = [], $show_demo = false)
    {
        $lobby_url = $this->wrapUrlInJsForRedirect($this->getLobbyUrl(true, $lang, 'mobile'));
        return $this->getDepUrl('', $lang, null,false, $gref, 'mobile') . "&home=$lobby_url";
    }

    function getSettingKey($base_key, $u = null){
        $u       = empty($u) ? cuPl() : cu($u);
        $country = empty($u) ? getCountry() : $u->getCountry();
        $key     = $base_key.strtolower($country);
        $value   = $this->getSetting($key);
        if(empty($value))
            return $this->getProxySetting($base_key, $u->data);
        return $value;
    }

    function getDepUrl($gid, $lang, $game = null, $show_demo = false, $gref = '', $channel = 'pc')
    {
        $this->initCommonSettingsForUrl();
        $base_url = $this->launch_url;
        $is_logged = isLogged();

        $current_user = cu($this->getUsrId($_SESSION['token_uid'])); // will grab the tournament id if it exist#

        $this->dumpTst('base_url', $base_url);

        if (empty($gref)) {
            $game = phive('MicroGames')->getByGameId($gid);
        } else {
            $game = phive('MicroGames')->getByGameRef($gref);
        }
        $game = phive('MicroGames')->overrideGame($current_user, $game);

        $gref = $game['ext_game_name'];
        $gid = $this->remYgg($gref);

        if($is_logged) {
            $user_id       = empty($_SESSION['token_uid']) ? $current_user->getId() : $_SESSION['token_uid'];
            $is_tournament = $this->isTournament($user_id);
            $currency      = $is_tournament ? phive('Tournament')->curIso : $current_user->getData()['currency'];
            $token         = mKey($user_id, phive()->uuid());
            $this->insertToken($token, $user_id, $gref, $is_tournament);
        }

        $params = [
            'gameid'    => $gid,
            'lang'      => $lang,
            'channel'   => $channel,
            'org'       => $this->getLicSetting('org', $current_user), // no need to check if player is logged in or not
            'currency'  => $is_logged ? $currency : ciso(),
            'key'       => $is_logged ? $token : null
        ];

        $license = $this->getLicSetting('license', $current_user);
        if (!empty($license)) {
            $params['license'] = $license;
        }

        if ($this->getCountry($current_user) === 'ES') {
            $params['disableCurrencyCoins'] = 'yes';
        }

        if($this->getRcPopup($this->platform, $current_user) == 'ingame') {
            $params = array_merge(
                $params,
                (array)$this->getRealityCheckParameters($current_user, false, ['reminderElapsed', 'reminderInterval', 'clientHistoryUrl', 'realityCheckBackURL'])
            );
        }

        $url =  $base_url . '?' . http_build_query($params);
        $this->dumpTst('launch_url', $url);
        return $url;
    }

    public function addCustomRcParams($regulator, $rcParams)
    {
        $custom_rc_params = [
            'reminderElapsed'       => true,
            'reminderInterval'      => true,
            'clientHistoryUrl'      => true,
            'realityCheckBackURL'   => true
        ];

        return array_merge($rcParams, $custom_rc_params);
    }

    public function manageRcParams(&$rcParams)
    {
        $rcParams['remainderElasped']       = round($rcParams['remainderElasped'] / 60); // round to nearest minute
        $rcParams['clientHistoryUrl']       = $this->wrapUrlInJsForRedirect($rcParams['clientHistoryUrl']);
        $rcParams['realityCheckBackURL']    = $this->wrapUrlInJsForRedirect($rcParams['realityCheckBackURL']);
    }

    public function mapRcParameters($regulator, $rcParams)
    {
        $mappers = [
            'reminderElapsed'       => 'rcElapsedTime',
            'reminderInterval'      => 'rcInterval',
            'clientHistoryUrl'      => 'rcHistoryUrl',
            'realityCheckBackURL'   => 'rcLobbyUrl'
        ];

        $mappers = array_merge((array)$mappers, (array)$this->lic($regulator, 'getMapForCustomParams'));

        $rcParams = phive()->mapit($mappers, $rcParams, [], false);

        $this->manageRcParams($rcParams);

        $this->dumpTst('params-after-mapit', $rcParams);

        return $rcParams;
    }

    function getbalance($req){
        return $this->playerInfo();
    }

    /**
     * @param $token
     *
     * Logic for game session balance, store ext_session_id with separate token
     * since the sessiontoken is not sent with every request, see loadtoken
     */
    private function initSessionBalance($token):void
    {
        $user_id = getMuid($token);
        $user = cu($user_id);
        $arr = phMgetArr($token, $this->exp_time);
        if($this->useExternalSession($user) && empty($this->t_entry)) {
            $game = phive('MicroGames')->getByGameRef($arr['game_ref']);
            $ext_session_id = lic('initGameSessionWithBalance', [$user, $user_id, $game], $user);
            phMsetArr($user_id, $ext_session_id, $this->exp_time);
            $this->loadToken($token);
        }
    }
}
