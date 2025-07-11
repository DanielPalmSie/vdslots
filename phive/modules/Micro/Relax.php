<?php
require_once __DIR__ . '/Casino.php';

class Relax extends Casino
{

    /**
     * This will hold the ext_game_name received from the provider request
     *
     * @var string
     */
    protected $gref = '';

    /**
     * @var string
     */
    protected $prefix = 'qspin';

    /**
     * @var string
     */
    protected string $logger_name = 'relax';

    function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $entry
     * @param $na
     * @param $bonus
     */
    public function activateFreeSpin(&$entry, $na, $bonus)
    {
        $entry['status'] = 'approved';
    }

    /**
     * @param $uid
     * @param $gids
     * @param $rounds
     * @param $bonus_name
     * @param $entry
     * @return bool
     */
    public function awardFRBonus($uid, $gids, $rounds, $bonus_name, $entry)
    {
        $user = cu($uid);
        $bonus = phive('Bonuses')->getBonus($entry['bonus_id']);
        $cg = phive('MicroGames')->getByGameId($bonus['game_id'], '', $user);
        $cg = phive('MicroGames')->overrideGame($user, $cg);

        $gid = $this->stripQspin($cg['ext_game_name']);

        $params = [
            'txid'           => uniqid(),
            'remoteusername' => $uid,
            'gameid'         => $gid,
            'amount'         => (int)$bonus['reward'],
            'freespinvalue'  => (int)$bonus['frb_denomination'],
            'expire'         => (int)strtotime($entry['end_time']),
            'promocode'      => (string)$entry['id'],
            'paylines'       => (int)$bonus['frb_lines']
        ];

        if ($this->getSetting('no-out') === true) {
            phive()->dumpTbl('relax_test_frb_awarded', $params);
            return true;
        }

        $api_url = $this->getLicSetting('api_url', $user) . '/casino/freespins/add';

        $basic_auth_header = $this->getAuthHeader($user);
        $res = phive()->post($api_url, json_encode($params), 'application/json', $basic_auth_header, "relax_api_out", 'POST');
        $this->logger->debug(__METHOD__ . '/request', [$api_url, $params]);
        $res = json_decode($res, true);
        $this->logger->debug(__METHOD__.'/response', [$res]);

        return $res['status'] == 'ok';
    }

    /**
     * @param $res
     * @return false|string
     */
    public function buildResponse($res)
    {
        if (is_string($res)) {
            $res = ['errorcode' => $res, 'error_msg' => ''];
            $this->logger->debug(__METHOD__ . '/error', [$res]);
            header("HTTP/1.0 500 Internal Server Error");
        } else {
            $res['cashiertoken'] = $this->str_token;
            $res['bonusbalance'] = 0;
        }
        $this->setSessData();
        $this->logger->debug(__METHOD__, [$res]);

        return json_encode($res);
    }

    /**
     * @param $req
     * @param string $key
     */
    public function assignSessData(&$req, $key = 'cashiertoken')
    {
        $this->str_token = $req[$key];
        $this->token = $this->new_token = phMgetArr(urldecode($this->str_token));
    }

    /**
     * @param array $arr
     */
    public function setSessData($arr = [])
    {
        $arr = empty($arr) ? $this->token : $arr;
        $this->logger->debug(__METHOD__, [$arr]);
        phMset($this->str_token, json_encode($arr));
    }

    /**
     * @param $req
     */
    public function createSessData(&$req)
    {
        $this->token = $this->new_token = phMgetArr($req['token']);
    }

    /**
     * @param $req
     * @param $action
     * @return false|string
     */
    function exec(&$req, $action){
        $tkey = $action == 'verifyToken' ? 'token' : 'cashiertoken';

        $this->assignSessData($req, $tkey);

        if (!in_array($action, ['verifyToken'])) {
            // BoS data like $this->t_eid is set in the below getUsrId() call, that's why subsequent calls to useExternalSession()
            // work for excluding BoS from the ext game session logic.
            $this->uid = $this->getUsrId($req['customerid']);
            $this->user = cu($this->uid);
            $this->ud = ud($this->user);
            if (empty($this->ud)) {
                $err = 'UNHANDLED';
            }
        }

        if (empty($this->token) && !in_array($action, ['rollback', 'deposit'])) {
            $err = 'INVALID_TOKEN';
        }

        if(!in_array($action, ['rollback', 'verifyToken']) && $this->useExternalSession($this->user)){
            // We only override the game balance etc in case we are not looking at a rollback (because no token in that case anyway),
            // we're not looking at a verifyToken call (because then we handle it there instead) or we're not looking at a BoS context
            // which Casino::userExternalSession() is managing.
            $this->uid = $req['customerid'];
            $this->loadSession();
        }
        $this->logger->debug(__METHOD__, [!empty($err) ? $err : $this->$action($req)]);
        return $this->buildResponse(!empty($err) ? $err : $this->$action($req));
    }

    /**
     * @param $req
     * @return array|string
     */
    public function verifyToken(&$req)
    {
        $this->uid = $this->getUsrId($this->token['user_id']);
        $ud = $this->ud = ud($this->uid);
        if (empty($ud)) {
            $this->logger->debug(__METHOD__, [$req, 'INVALID_TOKEN']);
            return 'INVALID_TOKEN';
        }

        $locale = phive('Localizer')->getLocale($ud['preferred_lang']);
        $this->str_token = mKey($ud['id'], phive()->uuid());

        $eid = $this->t_entry['id'];

        $this->initSessionBalance($req['token']);

        $response = [
            'customerid'       => $this->mkUsrId($ud['id'], $eid),
            'username'         => $ud['firstname'],
            'locale'           => $locale,
            'countrycode'      => $ud['country'],
            'jurisdiction'     => $this->getJurisdiction($ud['id']),
            'gender'           => $ud['sex'],
            'brand'            => $this->getSetting('brand'),
            'customercurrency' => $this->getPlayCurrency($ud),
            'balance'          => $this->_getBalance($req),
            'birthdate'        => $ud['dob'],
            'lastlogin'        => strtotime($ud['last_login'])
        ];

        $maxBetLimit = phive('Gpr')->getMaxBetLimit(cu($this->uid));
        if (!empty($maxBetLimit)) {
            $response['betsettings']['maximumbet'] = bcdiv($maxBetLimit, 0.01, 0);
        }

        $this->logger->debug(__METHOD__, [$response]);
        return $response;
    }

    /**
     * @param $req
     * @return array|false|mixed|string
     */
    public function getGameByRef(&$req)
    {
        $gref = $this->getGameRef($req);
        $user = cu($this->uid);
        if (empty($this->game)) {
            $this->game = phive('MicroGames')->getByGameRef($gref, $req['channel'] == 'web' ? 'flash' : 'html5', $user);
        }
        return $this->game;
    }

    /**
     * @param $req
     * @return mixed|string
     */
    public function getGameRef(&$req)
    {
        $gref = $this->new_token['game_ref'];
        $user = cu($this->uid);
        if (empty($gref) && !empty($req['gameref'])) {
            $gref = $this->prefix . $req['gameref'];
        }
        if (empty($gref)) {
            $gref = $this->prefix.'default';
        }
        if (strpos($gref, $this->prefix) === false) {
            $gref = $this->prefix . $this->gref;
        }

        $this->game = phive('MicroGames')->getByGameRef($gref, $req['channel'] == 'web' ? 'flash' : 'html5', $user);
        $this->gref = $this->game['ext_game_name'];
        $this->logger->debug(__METHOD__, [$req, $this->gref]);
        return $this->gref;
    }

    /**
     * @param null $req
     * @return array|mixed
     */
    public function getUsr(&$req = null)
    {
        return $this->ud;
    }

    /**
     * Overrides parent method where we're using this class's properties rather than relying on redis token data
     *
     * @return int
     */
    protected function getBonusBalance()
    {
        return (int)phive('Bonuses')->getBalanceByRef($this->gref, $this->uid);
    }

    /**
     * Overrides the parent method so that rather than calling the lgaMobileBalance we are simply adding the player
     * balance with the bonus balance. This is because calling the lgaMobileBalance during requests without an
     * ext_game_name (like rollback and getBalance) will cause the result to be 0 all the time which we want to avoid.
     *
     * @param $user array
     * @param $balance int
     * @param $bonus_balances int
     * @return int
     */
    protected function getTotalBalance($user, $balance, $bonus_balances)
    {
        return $balance + $bonus_balances;
    }


    /**
     * This is a simple two step procedure where we set the gref property which will be used to get an acurate
     * balance from the parent class.
     * @param $req
     * @return int|string
     */
    protected function _getBalance(&$req)
    {
        $this->getGameRef($req); // sets the ext_game_name prop

        return parent::getBalance(false, false);
    }

    /**
     * @param bool $req
     * @return array
     */
    public function getBalance(&$req)
    {
        return [
            'customercurrency' => $this->getPlayCurrency($this->ud),
            'balance'          => $this->_getBalance($req)
        ];
    }

    /**
     * @param $req
     * @param $amount
     * @param $tid
     * @param $cur_game
     * @param int $balance
     * @return array|string
     */
    public function _withdraw(&$req, $amount, $tid, $cur_game, $balance = 0)
    {
        $result = $this->getBetByMgId($tid);
        // This means a standalone withdraw call, we don't have the balance in that case.
        if (empty($balance)) {
            $balance = $this->_getBalance($req);
        }
        if (!empty($result)) {
            return [
                'remotetxid' => $result['id'],
                'balance'    => $balance,
                'txid'       => $this->stripQspin($result['mg_id'], true)
            ];
        }

        if (!empty($amount)) {
            $jp_contrib = round($amount * $cur_game['jackpot_contrib']);

            $balance = $this->lgaMobileBalance($this->ud, $cur_game['ext_game_name'], $balance,
                $cur_game['device_type'], $amount);
            if ($balance < $amount) {
                return 'INSUFFICIENT_FUNDS';
            }

            $GLOBALS['mg_id'] = $tid;

            $balance = $this->playChgBalance($this->ud, "-$amount", $tid, 1);
            if ($balance === false) {
                return 'INSUFFICIENT_FUNDS';
            }

            $bonus_bet = empty($this->bonus_bet) ? 0 : 1;

            $extid = $this->insertBet($this->ud, $cur_game, $req['gamesessionid'], $tid, $amount, $jp_contrib,
                $bonus_bet, $balance);
            if (!$extid) {
                return 'UNHANDLED';
            }

            $this->insertRound($this->ud['id'], $extid, $req['gamesessionid']);

            $balance = $this->betHandleBonuses($this->ud, $cur_game, $amount, $balance, $bonus_bet, 0, $tid);
            $ret = ['remotetxid' => $extid];
        } else {
            $ret = ['remotetxid' => uniqid()];
        }

        $ret['txid'] = $this->stripQspin($tid, true);
        $ret['balance'] = $balance;
        $this->logger->debug(__METHOD__, [$ret]);
        return $ret;
    }

    /**
     * @param $req
     * @param string $key
     * @return string
     */
    public function getTid(&$req, $key = 'txid')
    {
        return $this->getNormalizedTxnId($req[$key]);
    }

    /**
     * @param $str
     * @param false $transaction
     * @return array|string|string[]
     */
    public function stripQspin($str, $transaction = false)
    {
        if ($transaction) {
            // here we need to check the format of the mg_id and get the raw id accordingly
            $raw_id = $this->getRawIdFromMgId($str);
        } else {
            // here we simply want to strip the relax from the game ids
            $raw_id = str_replace($this->prefix, '', $str);
        }
        $this->logger->debug(__METHOD__, [$raw_id]);
        return $raw_id;
    }

    /**
     * @param $req
     * @return array|string
     */
    public function withdraw(&$req)
    {
        $tid = $this->getTid($req);
        $amount = $req['amount'];
        $cur_game = $this->getGameByRef($req);
        $this->logger->debug(__METHOD__, [$req, $amount, $tid]);
        return $this->_withdraw($req, $amount, $tid, $cur_game);
    }

    /**
     * @param $req
     * @return array|string
     */
    public function deposit(&$req)
    {
        $tid = $this->getTid($req);
        $amount = $req['amount'];
        $cur_game = $this->getGameByRef($req);
        $this->logger->debug(__METHOD__, [$req, $amount, $tid]);
        return $this->_deposit($req, $amount, $tid, $cur_game);
    }

    /**
     * @param $req
     * @param $amount
     * @param $tid
     * @param $cur_game
     * @param int $award_type
     * @param int $balance
     * @return array|string
     */
    public function _deposit(&$req, $amount, $tid, $cur_game, $award_type = 2, $balance = 0)
    {
        $result = $this->getBetByMgId($tid, 'wins');
        if (!empty($result)) {
            return [
                'balance'    => $this->_getBalance($req),
                'txid'       => $this->stripQspin($tid, true),
                'remotetxid' => $result['id']
            ];
        }

        if ($req['txtype'] == 'freespinspayout') {

            $this->dumpTst('relax_frbwin_call', $req);
            $this->logger->debug(__METHOD__.'/frbwin_call', [$req]);
            $fspin = phive('Bonuses')->getBonusEntry($req['promocode'], $this->ud['id']);

            $this->frb_win = true;
            $bonus = phive('Bonuses')->getBonus($fspin['bonus_id']);

            //The bonus can't be active atm
            if (!empty($fspin)) {
                if (empty($amount)) {
                    $this->logger->debug(__METHOD__.'/free_spin_without_winnings', [$fspin]);
                    phive('Bonuses')->fail($fspin, 'Free spin bonus without winnings');
                } else {
                    $this->logger->debug(__METHOD__ . '/free_spin_win', [$fspin, $amount]);
                    $this->handleFspinWin($fspin, $amount, $this->ud, 'Freespin win');
                }
                $balance = $this->_getBalance($req);
                $winid = $this->insertWin($this->ud, $cur_game, $balance, $req['gamesessionid'], $amount, 3, $tid, 2);
                phive('Bonuses')->resetEntries();
                return ['balance' => $balance, 'txid' => $this->stripQspin($tid, true), 'remotetxid' => $winid];
            } else {
                $this->logger->error(__METHOD__ . '/frbwin_failure', [$req['promocode']]);
                phive()->dumpTbl('relax_frbwin_failure', "Cant find the bonus entry with id: {$req['promocode']}", $this->ud);
            }
        }

        if (!empty($amount)) {
            if ($this->frb_win === true) {
                $bonus_bet = 3;
            } else {
                $bonus_bet = empty($this->bonus_bet) ? 0 : 1;
            }

            // if it was a jackpot win, set the award_type = 4 (JACKPOT_WIN_AWARD_TYPE)
            $award_type = ! empty($req['jackpotpayout']) ? 4 : 2;

            $extid = $this->insertWin($this->ud, $cur_game, $balance, $req['gamesessionid'], $amount, $bonus_bet, $tid,
                $award_type);

            if (!$extid) {
                return 'UNHANDLED';
            }

            if (isset($req['ended']) && $req['ended'] == 'true') {
                $this->updateRound($this->ud['id'], $req['gamesessionid'], $extid);
            }

            $balance = $this->playChgBalance($this->ud, $amount, $req['gamesessionid'], $award_type);

            $balance = $this->handlePriorFail($this->ud, $tid, $balance, $amount);
            return ['balance' => $balance, 'txid' => $this->stripQspin($tid, true), 'remotetxid' => $extid];
        } else {
            if (isset($req['ended']) && $req['ended'] == 'true') {
                $this->updateRound($this->ud['id'], $req['gamesessionid'], null);
            }

            return [
                'balance'    => $this->_getBalance($req),
                'txid'       => $this->stripQspin($tid, true),
                'remotetxid' => uniqid()
            ];
        }
    }

    /**
     * @param $c_req
     * @param $req
     * @param $key
     */
    public function populateSubReq(&$c_req, $req, $key)
    {
        $c_req['txid'] = $req[$key]['txid'];
        $c_req['amount'] = $req[$key]['amount'];
        $c_req['txtype'] = $req[$key]['txtype'];
    }

    /**
     * @param $req
     * @return array|string
     */
    public function withdrawAndDeposit($req)
    {
        $cur_game = $this->getGameByRef($req);

        $w_req = $req;
        $this->populateSubReq($w_req, $req, 'withdraw');
        $balance = $this->_getBalance($req);
        $w_res = $this->_withdraw($w_req, $w_req['amount'], $this->getTid($w_req), $cur_game, $balance);
        if (is_string($w_res)) {
            return $w_res;
        }
        $d_req = $req;
        $this->populateSubReq($d_req, $req, 'deposit');
        $d_res = $this->_deposit($d_req, $d_req['amount'], $this->getTid($d_req), $cur_game, 2, $balance);

        return [
            'balance'  => $d_res['balance'],
            'withdraw' => ['txid' => $w_req['txid'], 'remotetxid' => $w_res['remotetxid']],
            'deposit'  => ['txid' => $d_req['txid'], 'remotetxid' => $d_res['remotetxid']]
        ];
    }

    /**
     * @param $tbl
     * @param $req
     * @return array
     */
    public function _rollback($tbl, $req)
    {
        //No need to pass in user id as it is set already
        $result = $this->getBetByNormalizedMgId($req['originaltxid'], $tbl);
        $balance = false;
        $already = false;
        if (empty($result)) {
            $already_id = $req['originaltxid'] . 'ref';
            $result = $this->getBetByNormalizedMgId($already_id, $tbl);
            if (!empty($result)) {
                $already = true;
            }
        }

        if ($already == false && !empty($result)) {
            $id = $result['mg_id'];
            if ($tbl == 'bets') {
                $type = 7;
                $amount = $result['amount'];
            } else {
                $type = 1;
                $amount = -$result['amount'];
            }
            $type = $tbl == 'bets' ? 7 : 1;
            $balance = $this->playChgBalance($this->ud, $amount, $result['trans_id'], $type);
            $this->doRollbackUpdate($id, $tbl, $balance, $amount);
        }
        $this->logger->debug(__METHOD__, [$result, $balance]);
        return [$result, $balance];
    }

    /**
     * @param $req
     * @return array
     */
    public function rollback($req)
    {
        list($bet, $w_balance) = $this->_rollback('bets', $req);
        list($win, $d_balance) = $this->_rollback('wins', $req);
        $balance = $d_balance === false ? $w_balance : $d_balance;
        if ($balance === false) {
            $balance = $this->_getBalance($req);
        }
        return ['balance' => $balance, 'txid' => $req['txid'], 'remotetxid' => uniqid()];
    }

    /**
     * @param $regulator
     * @param $rcParams
     * @return array
     */
    public function addCustomRcParams($regulator, $rcParams)
    {
        $regulator_params = [
            'rcinterval'   => true,
            'rcelapsed'    => true,
            'rchistoryurl' => true,
        ];

        $regulator_params = array_merge($regulator_params, (array)$this->lic($regulator, 'addCustomRcParams'));
        $this->logger->debug(__METHOD__, [$rcParams, $regulator_params]);
        return array_merge($rcParams, $regulator_params);
    }

    /**
     * This function is used to simply edit the values of the params we need
     *
     * @param $rcParams
     */
    public function manageRcParams(&$rcParams)
    {
        $rcParams['rcinterval'] = $rcParams['rcinterval'] * 60; // we want the interval in seconds

        if ($rcParams['rcelapsed'] === 0 || $rcParams['rcelapsed'] === null) {
            $rcParams['rcelapsed'] = '0'; // this is so that it doesn't get filtered out
        }
    }

    /**
     * @param $regulator
     * @param $rcParams
     * @return mixed
     */
    protected function mapRcParameters($regulator, $rcParams)
    {
        $mapping = [
            'rcinterval'   => 'rcInterval',
            'rcelapsed'    => 'rcElapsedTime',
            'rchistoryurl' => 'rcHistoryUrl'
        ];

        // filter out what we don't want to map
        $mapping = array_merge($mapping, (array)$this->lic($regulator, 'getmapRcParameters'));

        $rcParams = phive()->mapit($mapping, $rcParams, [], true);

        $this->manageRcParams($rcParams);

        $this->dumpTst('rc_params after mapit', $rcParams);
        $this->logger->debug(__METHOD__, [$rcParams]);
        return $rcParams;
    }

    /**
     * @param $game_id
     * @param $lang
     * @param $device
     * @param false $show_demo
     * @return string
     */
    public function getUrl($game, $lang, $device, $show_demo = false)
    {
        $this->initCommonSettingsForUrl();

        $this->dumpTst('initiated data',
            $this->iso . ' ' . $this->demo_or_real . ' ' . $this->platform . ' ' . $this->launch_url);
        $this->logger->debug(__METHOD__.'/init', [$this->iso, $this->demo_or_real, $this->platform, $this->launch_url]);
        $is_logged = isLogged();
        $channel = $device == 'desktop' ? 'web' : 'mobile';

        $user = cu();

        if (!empty($_SESSION['token_uid'])) {
            $game = phive('MicroGames')->overrideGameForTournaments($user, $game);
        } else {
            $game = phive('MicroGames')->overrideGame($user, $game);
        }
        $this->logger->debug(__METHOD__, [$game['ext_game_name']]);
        $game_id = $this->stripQspin($game['ext_game_name']);

        if ($is_logged) {
            $uid = empty($_SESSION['token_uid']) ? $user->getId() : $_SESSION['token_uid'];
            $ticket = mKey($uid, phive()->uuid());
            phMset($ticket, json_encode([
                'user_id'     => $uid,
                'game_ref'    => $this->prefix . $game_id,
                'device_type' => $this->platform
            ]));
        }

        $url_args = [
            'lang'         => phive('Localizer')->getLocale($lang),
            'ticket'       => $is_logged ? $ticket : null,
            'partnerid'    => $this->getLicSetting('partner_id', $uid),
            'partner'      => $this->getLicSetting('partner_name', $uid),
            'moneymode'    => $is_logged ? 'real' : 'fun',
            'gameid'       => $game_id,
            'channel'      => $channel,
            'homeurl'      => $this->getLobbyUrlForGameIframe(false, $lang, $this->platform),
            'jurisdiction' => $this->getJurisdiction($uid)
            // we want this shown even when player is not logged in
        ];

        if ($this->getRcPopup($this->platform, $user) == 'ingame') {
            $url_args = array_merge($url_args, (array)$this->getRealityCheckParameters($user, false, [
                'rcinterval',
                'rcelapsed',
                'rchistoryurl'
            ]));
        }

        if(lic('getLicSetting', ['game_play_session'])) {
          $url_args['disabledrgoverlay'] = 'true';
        }

        $launch_url = $this->getLicSetting('launch_url', $uid) . '?' . http_build_query($url_args);
        $this->dumpTst('launch_url', $launch_url);
        $this->logger->debug(__METHOD__, [$launch_url]);
        return $launch_url;
    }


    /**
     * @param $gid -> this should be the ext_game_name of the row
     * @param $lang
     * @param $game
     * @param bool $show_demo
     * @return string
     */
    function getDepUrl($gid, $lang, $game = null, $show_demo = false)
    {
        return $this->getUrl(phive('MicroGames')->getByGameId($gid), $lang, 'desktop', $show_demo);
    }

    /**
     * @param $gref -> this should be the ext_game_name of the row
     * @param $lang
     * @param $lobby_url
     * @return string|void
     */
    function getMobilePlayUrl($gref, $lang, $lobby_url)
    {
        return $this->getUrl(phive('MicroGames')->getByGameRef($gref, 'html5'), $lang, 'mobile');
    }

    /**
     * Get the Jurisdiction of the player or the tournament jurisdiction
     *
     * @param $user_id
     * @return mixed
     */
    public function getJurisdiction($user_id)
    {
        $bos_country = $this->getLicSetting('bos-country', $user_id);
        if ($this->isTournamentMode() && !empty($bos_country)) {
            return $bos_country;
        }
        return $this->getLicSetting('environment', $user_id);
    }


    /**
     * @param $token
     *
     * Logic for game session balance, store ext_session_id with separate token
     * since the session token is not sent with every request
     *
     * @see see Relax::loadSession();
     */
    private function initSessionBalance($token):void
    {
        $user_id = getMuid($token);
        $user = cu($user_id);
        $data = phMgetArr($token, $this->exp_time);

        if (!$this->useExternalSession($user) || $this->isTournamentMode()) {
            return;
        }

        $game = phive('MicroGames')->getByGameRef($data['game_ref'], null, $user);
        $ext_session_id = lic('initGameSessionWithBalance', [$user, $user_id, $game], $user);

        if (!empty($ext_session_id)) {
            phMsetArr($user_id, $ext_session_id, $this->exp_time);
        }

        $this->loadSession($token);
    }

    /**
     * @param $token
     *
     * @return void
     */
    function loadSession($token = null): void
    {
        if (!empty($token)) {
            $this->sess = phMgetArr($token, $this->exp_time);
            $this->uid = $this->sess['user_id'];
            $this->gref = $this->token['game_ref'] = $this->sess['game_ref'];
        }

        if (!$this->useExternalSession($this->uid) || $this->isTournamentMode()) {
            return;
        }

        $ext_session_id = phMgetArr($this->uid, $this->exp_time);
        if (!empty($ext_session_id)) {
            $this->setSessionById(cu($this->uid), $ext_session_id);
        }
    }

    function parseJackpots(): array
    {
        $basic_auth_header = $this->getAuthHeader();
        $jur_jp_urls = $this->getSpecificJurSettingsByKey('jp_url', ['IT', 'DE', 'DK']);
        $partner_ids = $this->getAllJurSettingsByKey('partner_id');
        $inserts = [];

        foreach ($jur_jp_urls as $jur => $jp_url) {
            $get_jackpot_values_params = [
                'jurisdiction' => $jur,
            ];

            $jackpots = $this->getJackpotValues($get_jackpot_values_params, [$basic_auth_header]);
            $jackpotIdNameMapping = [];
            $games = [];

            foreach ($jackpots as $jackpot) {
                $jackpotIdNameMapping[$jackpot['id']] = $jackpot['name'];
                $games = array_unique(array_merge($games, $jackpot['games']));
            }

            foreach ($games as $gameName) {
                $game_ref = $this->prefix . $gameName;
                $game = phive("MicroGames")->getByGameRef($game_ref);
                if (empty($game)) {
                    continue;
                }
                $get_jackpot_info_params = [
                    'partnerId' => $partner_ids[$jur] ?? $partner_ids['DEFAULT'],
                    'g' => $gameName
                ];

                $jackpotInfoData = $this->getJackpotInfo($jp_url, $get_jackpot_info_params);

                foreach ($jackpotInfoData as $jackpotInfoDatum) {

                    $inserts[] = [
                        'module_id'     => $game_ref,
                        'currency'      => $jackpotInfoDatum['currency'] ?? 'EUR',
                        'jp_name'       => $jackpotIdNameMapping[$jackpotInfoDatum['jackpotId']],
                        'jp_id'         => $jackpotInfoDatum['jackpotId'],
                        'jp_value'      => $jackpotInfoDatum['size'],
                        'game_id'       => $game_ref,
                        'jurisdiction'  => $jur,
                        'network'       => 'relax',
                    ];
                }
            }
        }

        return $inserts;
    }

    private function getAuthHeader($user = null): string
    {
        $api_user = $this->getLicSetting('api_login', $user ?: 'videoslots');
        $api_pwd = $this->getLicSetting('api_pwd', $user ?: 'dev');
        //Use Basic HTTP authentication. Relax provides credentials for each partner id
        return 'Authorization: Basic ' . base64_encode(sprintf("%s:%s", $api_user, $api_pwd));
    }

    /**
     * Get the jackpot values.
     * DOC. https://docs.relax-gaming.com/operators/partner-api/ref/#tag/Jackpots/paths/~1casino~1jackpots~1values/get
     *
     * @return array[] An array of jackpot values where each jackpot contains:
     *                 - 'amount' => float The amount of the jackpot.
     *                 - 'games' => string[] An array of game names.
     *                 - 'id' => int The ID of the jackpot.
     *                 - 'name' => string The name of the jackpot.
     */
    private function getJackpotValues(array $params, array $headers = []): array
    {
        $url = $this->getLicSetting('api_url') . '/casino/jackpots/values?' . http_build_query($params);
        $this->logger->debug(__METHOD__ . '/request', [$url, $headers]);

        $response = phive()->get($url, '', $headers, 'relax_jackpot_values');
        $jackpot_values = json_decode($response, true);

        $this->logger->debug(__METHOD__.'/response', [$jackpot_values]);

        if (!empty($jackpot_values['status']) && $jackpot_values['status'] === 'ok') {
            return $jackpot_values['jackpotvalues'];
        }
        return [];
    }

    /**
     * Get the jackpot info.
     * DOC. https://docs.relax-gaming.com/operators/jackpot-api/ref/#/paths/~1getjackpotinfo/get
     *
     * @return array[] An array of jackpot info where each jackpot contains:
     *     - 'mode' (optional) => string Mode of the jackpot:
     *          HOT - when the guaranteed fallout value is at least 80%.
     *          SUPERHOT -  when the guaranteed fallout value is at least 90%.
     *     - 'size' => int Current size of the jackpot, in Euro cents.
     *     - 'jackpotId' => int The ID of the jackpot.
     *     - 'lastWin' => array An array containing:
     *         - 'winCount' => int Number of times the jackpot has been won.
     *         - 'amount' => int Amount won for the previous jackpot win, in Euro cents.
     *         - 'timestamp' => int Timestamp of the previous jackpot win in Unix time, in milliseconds.
     *     - 'guaranteed' (optional) => int Guaranteed fallout occurs before this value is reached, in Euro cents.
     *          Only for the Dream Drop Mega and Major jackpots.
     *     - 'currency' => string Jackpot currency, ISO 4217.
     */
    private function getJackpotInfo(string $jp_url, array $params): array
    {
        $url = $jp_url . '/getjackpotinfo?' . http_build_query($params);
        $this->logger->debug(__METHOD__ . '/request', [$url]);

        $response = phive()->get($url, '', '', 'relax_jackpot_info');
        $jackpot_info = json_decode($response, true);

        $this->logger->debug(__METHOD__.'/response', [$jackpot_info]);

        if (!empty($jackpot_info['jackpots'])) {
            return $jackpot_info['jackpots'];
        }

        return [];
    }
}
