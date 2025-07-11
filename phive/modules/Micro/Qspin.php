<?php
require_once __DIR__ . '/Casino.php';

class Qspin extends Casino {

    /**
     * This will hold the ext_game_name received from the provider request
     *
     * @var string
     */
    protected $gref = '';


    protected string $logger_name = 'quickspin';

  function __construct(){
      parent::__construct();
    }

  function activateFreeSpin(&$entry, $na, $bonus) {
      $entry['status'] = 'approved';
  }

  function awardFRBonus($uid, $gids, $rounds, $bonus_name, $entry)
  {
      $user = cu($uid);
      $bonus = phive('Bonuses')->getBonus($entry['bonus_id']);
      $cg = phive('MicroGames')->getByGameId($bonus['game_id'], '', $user);
      $cg = phive('MicroGames')->overrideGame($user, $cg);

      $gid = $this->stripQspin($cg['ext_game_name']);

      $params = [
          'txid'            => uniqid(),
          'remoteusername'  => $uid,
          'gameid'          => $gid,
          'amount'          => (int)$bonus['reward'],
          'freespinvalue'   => (int)$bonus['frb_denomination'],
          'expire'          => (int)strtotime($entry['end_time']),
          'promocode'       => (string)$entry['id'],
          'paylines'        => (int)$bonus['frb_lines']
      ];

      if ($this->getSetting('no-out') === true) {
          phive()->dumpTbl('qspin_test_frb_awarded', $params);
          return true;
      }

      $api_url = $this->getLicSetting('api_url', $user).'/casino/freespins/add';
      $api_user = $this->getLicSetting('api_login', $user);
      $api_pwd = $this->getLicSetting('api_pwd', $user);
      //Use Basic HTTP authentication. Quickspin provides credentials for each partnerid.
      $basic_auth_header = 'Authorization: Basic ' . base64_encode("{$api_user}:{$api_pwd}");
      $res = phive()->post($api_url, json_encode($params), 'application/json', $basic_auth_header, "qspin_api_out", 'POST');
      $res = json_decode($res, true);

      $this->logger->debug(__METHOD__, [
          'params' => $params,
          'gid' => $gid,
          'bonus' => $bonus,
          'user' => uid(),
          'api_url' => $api_url,
          'res' => $res,
          'res_status' => $res['status'],
      ]);

      if($this->isTournamentMode()) {
          $this->logger->debug('TOURNAMENT ' . __METHOD__, [
              'params' => $params,
              'gid' => $gid,
              'res' => $res,
              'tournament entry' => $this->t_entry
          ]);
      }
      return $res['status'] == 'ok';
  }

  function buildResponse($res){
        if (is_string($res)) {
            $res = ['errorcode' => $res, 'error_msg' => ''];
            header("HTTP/1.0 500 Internal Server Error");
        } else {
            $res['cashiertoken'] = $this->str_token;
            $res['bonusbalance'] = 0;
        }
        $this->setSessData();
        return json_encode($res);
    }

  function assignSessData(&$req, $key = 'cashiertoken'){
        $this->str_token = $req[$key];
        $this->token = $this->new_token = phMgetArr(urldecode($this->str_token));
    }

  function setSessData($arr = []){
        $arr = empty($arr) ? $this->token : $arr;
        phMset($this->str_token, json_encode($arr));
    }

  function createSessData(&$req)
  {
        $this->token = $this->new_token = phMgetArr($req['token']);
  }

  function exec(&$req, $action){
      if (!in_array($action, ['verifyToken'])) {
            $this->uid = $this->getUsrId($req['customerid']);
            $this->user = cu($this->uid);
            $this->ud = ud($this->user);
            if (empty($this->ud)) {
                $err = 'UNHANDLED';
            }
        }

        $tkey = $action == 'verifyToken' ? 'token' : 'cashiertoken';

        $this->assignSessData($req, $tkey);
        $no_token_action = in_array($action, ['getBalance', 'deposit', 'rollback']);


        if (empty($this->token) && !$no_token_action) {
            $err = 'INVALID_TOKEN';
        } else if (empty($this->token)) {
            $this->createSessData($req);
        }

        if ($this->isTournamentMode()) {
            $this->logger->debug(__METHOD__, [
              't_entry' => $this->t_entry,
              'action' => $action,
              'request' => $req,
              'error' => !empty($err) ? $err : ''
            ]);
        }

        return $this->buildResponse(!empty($err) ? $err : $this->$action($req));
    }

  function verifyToken(&$req){
        $this->uid = $this->getUsrId($this->token['user_id']);
        $ud = $this->ud = ud($this->uid);
        if (empty($ud)) {
            if ($this->isTournamentMode()) {
                $this->logger->error(__METHOD__, ['Tournament error invalid token', $this->uid]);
            }
            return 'INVALID_TOKEN';
        }

        $locale = phive('Localizer')->getLocale($ud['preferred_lang']);
        $this->str_token = phive()->uuid() . '-' . $ud['id'];

        $eid = $this->t_entry['id'];

        $data = [
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

        $maxBet = phive('Gpr')->getMaxBetLimit(cu($ud['id']), true);

        if (!empty($maxBet)) {
            $data['classification'] = $maxBet == 5 ? '5gbp' : '2gbp';
        }

        return $data;
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
            $this->logger->debug(__METHOD__, [
                'user_id' => $user_id,
                't_entry' => $this->t_entry,
                'bos_country' => $bos_country
            ]);
            return $bos_country;
        }
        return $this->getLicSetting('environment', $user_id);
    }

    function getGameByRef(&$req){
        $gref = $this->getGameRef($req);
        if (empty($this->game)) {
            $this->game = phive('MicroGames')->getByGameRef($gref, $req['channel'] == 'web' ? 'flash' : 'html5');
        }
        return $this->game;
    }

    function getGameRef(&$req)
    {
        $gref = $this->new_token['game_ref'];
        if (empty($gref) && !empty($req['gameref'])) {
            $gref = 'qspin' . $req['gameref'];
        }
        if (empty($gref)) {
            $gref = 'qspindefault';
        }
        if(strpos($gref, 'qspin') === false) {
            $gref = 'qspin' . $this->gref;
        }

        $this->game = phive('MicroGames')->getByGameRef($gref, $req['channel'] == 'web' ? 'flash' : 'html5', $this->user);
        $this->gref = $this->game['ext_game_name'];

        return $this->gref;
    }

    function getUsr(&$req = null){
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
     *
     * @param $req
     * @param bool $as_string
     * @return int
     */
    protected function _getBalance(&$req){
        $this->getGameRef($req); // sets the ext_game_name prop

        return parent::getBalance(false, false);
    }

    function getBalance(&$req){
        return [
            'customercurrency' => $this->getPlayCurrency($this->ud),
            'balance' => $this->_getBalance($req)
        ];
    }

  function _withdraw(&$req, $amount, $tid, $cur_game, $balance = 0){
        $result = $this->getBetByMgId($tid);
        // This means a standalone withdraw call, we don't have the balance in that case.
        if (empty($balance)) {
            $balance = $this->_getBalance($req);
        }
        if (!empty($result)) {
            return [
                'remotetxid' => $result['id'],
                'balance' => $balance,
                'txid' => $this->stripQspin($result['mg_id'], true)
            ];
        }

        if (!empty($amount)) {
            $jp_contrib = round($amount * $cur_game['jackpot_contrib']);

            $balance = $this->lgaMobileBalance($this->ud, $cur_game['ext_game_name'], $balance, $cur_game['device_type'], $amount);
            if ($balance < $amount) {
                return 'INSUFFICIENT_FUNDS';
            }

            $GLOBALS['mg_id'] = $tid;

            $balance = $this->playChgBalance($this->ud, "-$amount", $tid, 1);
            if ($balance === false) {
                return 'INSUFFICIENT_FUNDS';
            }

            $bonus_bet = empty($this->bonus_bet) ? 0 : 1;

            $extid = $this->insertBet($this->ud, $cur_game, $req['gamesessionid'], $tid, $amount, $jp_contrib, $bonus_bet, $balance);
            if (!$extid) {
                return 'UNHANDLED';
            }

            $balance = $this->betHandleBonuses($this->ud, $cur_game, $amount, $balance, $bonus_bet, 0, $tid);
            $ret = ['remotetxid' => $extid];
        } else {
            $ret = ['remotetxid' => uniqid()];
        }

        $ret['txid'] = $this->stripQspin($tid, true);
        $ret['balance'] = $balance;
        return $ret;
    }

    function getTid(&$req, $key = 'txid')
    {
        return $this->getNormalizedTxnId($req[$key]);
    }

    function stripQspin($str, $transaction = false)
    {
        if($transaction) {
            // here we need to check the format of the mg_id and get the raw id accordingly
            $raw_id = $this->getRawIdFromMgId($str);
        } else {
            // here we simply want to strip the qspin from the game ids
            $raw_id = str_replace(strtolower(get_class($this)), '', $str);
        }
        return $raw_id;
    }

  function withdraw(&$req) {
        $tid = $this->getTid($req);
        $amount = $req['amount'];
        $cur_game = $this->getGameByRef($req);
        $res = $this->_withdraw($req, $amount, $tid, $cur_game);
        return $res;
    }

  function deposit(&$req) {
        $tid = $this->getTid($req);
        $amount = $req['amount'];
        $cur_game = $this->getGameByRef($req);
        $res = $this->_deposit($req, $amount, $tid, $cur_game);
        return $res;
    }

  function _deposit(&$req, $amount, $tid, $cur_game, $award_type = 2, $balance = 0) {
        $result = $this->getBetByMgId($tid, 'wins');
        if (!empty($result)) {
            return ['balance' => $result['balance'], 'txid' => $this->stripQspin($tid, true), 'remotetxid' => $result['id']];
        }

        if ($req['txtype'] == 'freespinspayout') {

            $this->dumpTst('qspin_frbwin_call', $req);

            $fspin = phive('Bonuses')->getBonusEntry($req['promocode'], $this->ud['id']);

            $this->frb_win = true;
            $bonus = phive('Bonuses')->getBonus($fspin['bonus_id']);

            $this->logger->debug(__METHOD__, [
                '$fspin' => $fspin,
                '$amount' => $amount,
            ]);

            //The bonus can't be active atm
            if (!empty($fspin)) {
                if (empty($amount)) {
                    phive('Bonuses')->fail($fspin, 'Free spin bonus without winnings');
                } else {
                    $this->handleFspinWin($fspin, $amount, $this->ud, 'Freespin win');
                }
                $balance = $this->_getBalance($req);
                $winid = $this->insertWin($this->ud, $cur_game, $balance, $req['gamesessionid'], $amount, 3, $tid, 2);
                phive('Bonuses')->resetEntries();
                return ['balance' => $balance, 'txid' => $this->stripQspin($tid, true), 'remotetxid' => $winid];
            } else {
                phive()->dumpTbl('qspin_frbwin_failure', "Cant find the bonus entry with id: {$req['promocode']}", $this->ud);
            }
        }

        if (!empty($amount)) {
            if ($this->frb_win === true) {
                $bonus_bet = 3;
            } else {
                $bonus_bet = empty($this->bonus_bet) ? 0 : 1;
            }

            $extid = $this->insertWin($this->ud, $cur_game, $balance, $req['gamesessionid'], $amount, $bonus_bet, $tid, $award_type);

            if (!$extid) {
                return 'UNHANDLED';
            }

            $balance = $this->playChgBalance($this->ud, $amount, $req['gamesessionid'], $award_type);

            $balance = $this->handlePriorFail($this->ud, $tid, $balance, $amount);
            return ['balance' => $balance, 'txid' => $this->stripQspin($tid, true), 'remotetxid' => $extid];
        } else {
            return ['balance' => $this->_getBalance($req), 'txid' => $this->stripQspin($tid, true), 'remotetxid'
            => uniqid()];
        }
    }

  function populateSubReq(&$c_req, $req, $key){
        $c_req['txid'] = $req[$key]['txid'];
        $c_req['amount'] = $req[$key]['amount'];
        $c_req['txtype'] = $req[$key]['txtype'];
    }

  function withdrawAndDeposit($req){
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
            'balance' => $d_res['balance'],
            'withdraw' => ['txid' => $w_req['txid'], 'remotetxid' => $w_res['remotetxid']],
            'deposit' => ['txid' => $d_req['txid'], 'remotetxid' => $d_res['remotetxid']]
        ];
    }

    function _rollback($tbl, $req)
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

        return [$result, $balance];
    }

  function rollback($req){
        list($bet, $w_balance) = $this->_rollback('bets', $req);
        list($win, $d_balance) = $this->_rollback('wins', $req);
        $balance = $d_balance === false ? $w_balance : $d_balance;
        if ($balance === false) {
            $balance = $this->_getBalance($req);
        }
        return ['balance' => $balance, 'txid' => $req['txid'], 'remotetxid' => uniqid()];
    }

    public function addCustomRcParams($regulator, $rcParams)
    {
        $regulator_params = [
            'rcinterval'    => true,
            'rcelapsed'     => true,
            'rchistoryurl'  => true,
        ];

        $regulator_params = array_merge($regulator_params, (array)$this->lic($regulator, 'addCustomRcParams'));

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

        if($rcParams['rcelapsed'] === 0 || $rcParams['rcelapsed'] === null) {
            $rcParams['rcelapsed'] = '0'; // this is so that it doesn't get filtered out
        }
    }

    protected function mapRcParameters($regulator, $rcParams)
    {
        $mapping = [
            'rcinterval'    => 'rcInterval',
            'rcelapsed'     => 'rcElapsedTime',
            'rchistoryurl'  => 'rcHistoryUrl'
        ];

        // filter out what we don't want to map
        $mapping = array_merge($mapping, (array) $this->lic($regulator, 'getmapRcParameters'));

        $rcParams = phive()->mapit($mapping, $rcParams, [], true);

        $this->manageRcParams($rcParams);

        $this->dumpTst('rc_params after mapit', $rcParams);

        return $rcParams;
    }

    public function getUrl($game, $lang, $device, $show_demo = false)
    {
        $this->initCommonSettingsForUrl();

        $this->dumpTst('initiated data', $this->iso . ' ' . $this->demo_or_real . ' ' . $this->platform . ' ' . $this->launch_url);

        $is_logged = isLogged();
        $channel = $device == 'desktop' ? 'web' : 'mobile';

        $user = cu();

        if (!empty($_SESSION['token_uid'])) {
            $game = phive('MicroGames')->overrideGameForTournaments($user, $game);
            $this->logger->debug(__METHOD__, ['tournament override game ' . $game]);
        } else {
            $game = phive('MicroGames')->overrideGame($user, $game);
        }

        $game_id = $this->stripQspin($game['ext_game_name']);

        if($is_logged) {
            $uid = empty($_SESSION['token_uid']) ? $user->getId() : $_SESSION['token_uid'];
            $ticket = mKey($uid, phive()->uuid());
            phMset($ticket, json_encode(['user_id' => $uid, 'game_ref' => 'qspin'.$game_id, 'device_type' =>
            $this->platform]));
        }

        $url_args = [
            'lang'          => phive('Localizer')->getLocale($lang),
            'ticket'        => $is_logged ? $ticket : null,
            'partnerid'     => $this->getLicSetting('partner_id', $uid),
            'partner'       => $this->getLicSetting('partner_name', $uid),
            'moneymode'     => $is_logged ? 'real' : 'fun',
            'gameid'        => $game_id,
            'channel'       => $channel,
            'homeurl'       => $this->getLobbyUrlForGameIframe(false, $lang, $this->platform),
            'jurisdiction'  => $this->getJurisdiction($uid)
        ];

        if($this->getRcPopup($this->platform, $user) == 'ingame') {
            $url_args = array_merge($url_args, (array)$this->getRealityCheckParameters($user, false, [
                'rcinterval', 'rcelapsed', 'rchistoryurl'
            ]));
        }

        /**
         * Small note about below
         * Reason we're no longer relying on the $this->getLaunchUrl or lic settings is because we have a single launch_url.
         * In order to avoid any rollback issues in the future we'll keep both this launch_url and the one in the lic setting.
         */
        $launch_url = $this->getLicSetting('launch_url') . '?' . http_build_query($url_args);
        $this->dumpTst('launch_url', $launch_url);

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
    function getMobilePlayUrl($gref, $lang, $lobby_url, $g = null, $args = [], $show_demo = false)
    {
        return $this->getUrl(phive('MicroGames')->getByGameRef($gref, 'html5'), $lang, 'mobile');
    }


}
