<?php
require_once __DIR__ . '/QuickFire.php';
class Netent extends QuickFire {

    /** @var array $cache*/
    private $cache;

    private $cur_user;

    protected string $logger_name = 'netent';

  function __construct() {
      $this->settings = $this->allSettings();
      $this->multi_call = true;
      parent::__construct();
  }

    //We set the status to active, it will be set to approved when the frb win call arrives, and it will arrive when the bonus is finished
    //even if the amount is 0, if it is 0 we set status to failed, if it is not 0 we set to approved or active if the bonus has
    //wagering requirements (rake_percent is not 0)
  function activateFreeSpin(&$entry, $na, $bonus) {
      $entry['ext_id'] = $bonus['ext_ids'];
      $entry['status'] = 'active';
  }

    // TODO to be removed whenever we have the new game wizard /Ricardo
  function isBlocked($country = '') {
    $countries = array('DA', 'IT', 'BE', 'GI', 'IM', 'ES', 'FR', 'VI', 'US', 'CA', 'KP', 'IR', 'GG');
    if (empty($country)) {
        $country = cuCountry();
    }

    return in_array($country, $countries);
  }

  function dump($tag, $data) {
    if ($this->getSetting('test') === true) {
      phive()->dumpTbl($tag, $data);
    }
  }

  function setSess($sid, $arr) {
    phM('hmset', $sid, $arr, $this->exp_time);
  }

  function getSess($sid) {
    $this->sid = $sid;
    return phM('hgetall', $sid, $this->exp_time);
  }

  function fixXml($xml){
    return preg_replace('/(\w+?)(:)(\w+?)/', '$3', $xml);
  }

    /**
     * @param $xml
     * @return array
     */
    public function getReqData($xml)
    {
        $xmlData = $this->xmlToArray($xml);

        // TODO: use array_key_first when PHP >= 7.3.0
        if (!empty($xmlData)) {
            foreach ($xmlData as $action => $data) {
                return [$action, $data];
            }
        }
        return ['', []];
    }

    /**
     * Parses an XML or SOAP string into an array of data.
     * @param string $xml
     * @return array|null The array of data.
     *
     * @example 1
     * $xml = <<<EOS
     * <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
     *    <soap:Body>
     *        <ns2:withdrawAndDeposit xmlns:ns2="http://types.walletserver.casinomodule.com/3_0/">
     *            <description>testmerchant-éßřč,ž-的-</description>
     *            <jackpotContributions>
     *                <contribution>0.014</contribution>
     *                <contribution>0.077</contribution>
     *            </jackpotContributions>
     *        </ns2:withdrawAndDeposit>
     *    </soap:Body>
     * </soap:Envelope>
     * EOS;
     *
     * @example 2
     * $xml2 = <<<EOS
     * <withdrawAndDeposit>
     *    <description>testmerchant-éßřč,ž-的-</description>
     *    <jackpotContributions>
     *        <contribution>0.014</contribution>
     *        <contribution>0.077</contribution>
     *     </jackpotContributions>
     * </withdrawAndDeposit>
     * EOS;
     *
     * Both "xmlToArray($xml)" and "xmlToArray($xml2)" return:
     * [
     *      "withdrawAndDeposit" => [
     *          "description" => "testmerchant-éßřč,ž-的-",
     *          "jackpotContributions" => [
     *              "contribution" => [
     *                  [0] => "0.014",
     *                  [1] => "0.077",
     *              ]
     *          ]
     *      ]
     * ]
     *
     * Note that 1 "contribution" produces
     *      "jackpotContributions" => [
     *          "contribution" => "0.014"
     *      ]
     * but 2 "contributions" produce
     *      "jackpotContributions" => [
     *          "contribution" => [
     *              [0] => "0.014",
     *              [1] => "0.077",
     *          ]
     *      ]
     */
    public function xmlToArray(string $xml): array
    {
        $xml = $this->fixXml($xml);
        $xml_root = simplexml_load_string($xml);
        if ($xml_root === false) {
            return [];
        }

        $soap_body = $xml_root->xpath('//Body')[0];
        if ($soap_body) {
            $xml_body = $soap_body;
        } else {
            $xml_body = [$xml_root->getName() => $xml_root];
        }

        $json = json_encode((array)$xml_body);
        $array = json_decode($json, true);
        $this->arrayUnsetKeyRecursive($array, ['@attributes']);
        return $array;
    }

    /**
     * Recursively unsets the specified keys.
     *
     * @param array $array The array to process.
     * @param array $remove Array of keys to remove.
     */
    private function arrayUnsetKeyRecursive(array &$array, array $remove)
    {
        foreach ($array as $key => &$value) {
            if (is_string($key) && in_array($key, $remove)) {
                unset($array[$key]);
            } elseif (is_array($value)) {
                $this->arrayUnsetKeyRecursive($value, $remove);
            }
        }
    }

  function buildError($code) {
    if ($this->limit_reached === true) {
      $code = 6;
    }

    $map = array(
      1 => 'Not enough money',
      2 => 'Wrong currency',
      3 => 'Negative deposit',
      4 => 'Negative withdrawal',
      5 => 'Authentication failed',
      6 => 'Player limit exceeded',
    );
    $msg = $map[$code];
    if (empty($msg)) {
      $msg = 'Database error';
    }

    $str = '<?xml version="1.0" encoding="UTF-8"?>
      <S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
      <S:Header/>
      <S:Body>
          <S:Fault xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
          <faultcode>S:Client</faultcode>
          <faultstring xml:lang="en-GB">' . $msg . '</faultstring>
          <detail>
          <S2:' . $this->method . 'Fault xmlns:S2="http://types.walletserver.casinomodule.com/3_0/">
              <errorCode>' . $code . '</errorCode>
              <message>' . $msg . '</message>
              <balance>0</balance>
          </S2:' . $this->method . 'Fault>
      </detail>
      </S:Fault>
      </S:Body>
      </S:Envelope>';
      $this->logger->error(__METHOD__, ["request" => $this->cur_req, "response" => $str]);
    phive()->dumpTbl('netent_error_request', $this->cur_req, $this->udata);
    phive()->dumpTbl('netent_error_reply', $str, $this->udata);
      if($this->getSetting('test') === true){
          phive()->dumpTbl("netent_exec_error", $str);
      }

    return $str;

  }

    function execute($func, $arr, $jurisdiction = null) {

        if (in_array($jurisdiction, $this->getSetting('licensed_environments')) && array_key_exists("transactionRef",$arr)) {
            $arr['transactionRef'] = $jurisdiction . $arr['transactionRef'];
        }

        $this->method = $func;
        $this->cur_req = $arr;

        if($this->getSetting('test') === true){
            phive()->dumpTbl("netent_exec_error", $this->method . ' ' . print_r($this->cur_req,true) );
        }
        if ($arr['callerId'] != $this->settings['username'] || $arr['callerPassword'] != $this->settings['password']) {
            return $this->buildError(5);
        }

        $this->sess = $this->getSess($arr['sessionId']);

        if (empty($arr['gameId'])) {
            $this->new_token['game_ref'] = $this->gref = 'netent_livegame_sw';
        } else {
            $this->new_token['game_ref'] = $this->gref = 'netent_' . $arr['gameId'];
        }

        //TODO rework next 10 lines as it does not make sense to do all this stuff to extract the user id /Ricardo
        list($prefix, $uid) = explode('_', $arr['playerName']);

        $userDetails = [];

        if (!empty($uid) && !in_array($jurisdiction, $this->getSetting('licensed_environments', []))) {
            //Don't do anything with $country here, it might not be correct
           $userDetails = explode('-', $uid);
        } else {
            // Used this for licensed environments to be checked important //TODO why is this important if it is not used?
            $userDetails = explode('-', $arr['playerName']);
        }

        list($uid, $country) = $userDetails;

        // Battle, this is where the battle info gets set.
        $this->uid = $this->getUsrId($uid);

        $user = $this->cur_user = cu($this->uid);

        if (!is_object($user)) {
            return $this->buildError(8);
        }
        $this->udata = $user->data;
        $GLOBALS['mg_username'] = $this->udata['username'];

        if ($this->useExternalSession($user)) {
            $this->setExternalSessionByToken($user, $arr['sessionId']);
        }

        // Added userid in the request to be used whenever it is needed, as in the request it has country appended to it
        $arr['userId'] = $this->uid;

        $res = $this->$func($arr);

        if (is_numeric($res)) {
            return $this->buildError($res);
        }

        if(isset($arr['reason']) && $arr['reason'] == 'GAME_PLAY_FINAL') {
            if (isset($arr['gameRoundRef'])) {
                $this->updateRound($this->udata['id'], $arr['gameRoundRef']);
            }
        }

        if (!empty($this->sess) && !empty($arr['sessionId'])) {
            if (!empty($this->gref)) {
	        $this->sess['gref'] = $this->gref;
            }

            $this->sess['uid'] = $this->uid;
            $this->setSess($arr['sessionId'], $this->sess);
        }


        $res = '<?xml version="1.0" encoding="UTF-8"?>
            <S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns2="http://types.walletserver.casinomodule.com/3_0/">
              <S:Header/>
              <S:Body>
                <ns2:' . $func . 'Response xmlns:S="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns2="http://types.walletserver.casinomodule.com/3_0/" xmlns:xml="http://www.w3.org/XML/1998/namespace">
                      ' . $res . '
                </ns2:' . $func . 'Response>
              </S:Body>
            </S:Envelope>';

        if($this->getSetting('test') === true){
            phive()->dumpTbl("netent_exec_error", $res);
        }
        return $res;
    }

  function getAmountTid($req, $key = 'amount') {
    return array(round($req[$key] * 100), "netent" . $req['transactionRef']);
  }

    public function getBonusId($req) {
        $bonus_programs = $req['bonusPrograms'] ?? null;
        if (empty($bonus_programs)) {
            return false;
        }

        $bonus_key = ($this->getSetting('cmv') == '9.5') ? 'externalReferenceId' : 'bonusProgramId';
        $bonus_value = '';
        array_walk_recursive( $bonus_programs, function ($value, $key) use ($bonus_key, &$bonus_value) {
            if ($key == $bonus_key) {
                $bonus_value = $value;
            }
        });

        return (string)$bonus_value;
    }

    function _deposit($req, $amount, $tid, $cur_game, $balance, $award_type = 2, $recur = true) {
        $this->game_action = 'win';

        $ebid = $this->getBonusId($req);

        if(!empty($ebid)){

            $this->dumpLog('netent_frbwin_call', $this->xml, $this->udata['id']);
            $this->logger->debug(__METHOD__, ['frbwin_call' => $this->xml]);

            $fspin = phive('Bonuses')->getBonusEntry($ebid, $this->udata['id']);

            $this->frb_win = true;
            $bonus = phive('Bonuses')->getBonus($fspin['bonus_id']);

            $default_game = $cur_game;
            $cur_game = phive('MicroGames')->getByGameId($bonus['game_id'], $cur_game['device_type_num']);
            $this->logger->debug('Netent FRB', [
              'user' => $this->udata['id'],
              'default_game' => $default_game,
              'cur_game' => $cur_game,
              'fspin' => $fspin,
              'bonus' => $bonus
            ]);
            if(empty($cur_game)) {
	        phive()->dumpTbl('netent_frbwin_failure', $this->xml . " Type: game connection failure.", $this->udata);
                $this->logger->error(__METHOD__, ['frbwin_connection_failure' => $this->xml]);
	        $cur_game = $default_game;
            }

            if(!empty($fspin)){
                $this->handleFspinWin($fspin, $amount, $this->udata['id'], 'Freespin win');
                $balance = $this->_getBalance();
                if(!empty($amount)){
                    $wager_turnover = phive('Bonuses')->getTurnover($this->cur_user, $bonus);
                    $award_type = empty($wager_turnover) ? 3 : 5;

                    $winid = $this->insertWin($this->udata, $cur_game, $balance, $req['gameRoundRef'], $amount, 3, $tid, $award_type);
                    if($winid === false)
                        return 7;
                    $this->updateRound($this->udata['id'], $req['gameRoundRef'], $winid);
                }
                phive('Bonuses')->resetEntries();
                return array('extid' => $winid ?? uniqid(), 'balance' => $balance);
            }else{
                phive()->dumpTbl('netent_frbwin_failure', $this->xml . " Type: ext bonus id connection failure or already approved: $ebid", $this->udata);
                $this->logger->error(__METHOD__, ['frbwin_failure_already_approved' => $ebid]);
                return 7;
            }
        }

        if (!empty($amount)) {
            $jp_amount = $req['jackpotAmount'] * 100;
            if(!empty($jp_amount)){
                phive()->dumpTbl('netent_jp_win', $req);
                $award_type = 4;
            }

            if (!empty($amount)) {
                $bonus_bet = $this->bonusBetType();
	        $extid     = $this->insertWin($this->udata, $cur_game, $balance, $req['gameRoundRef'], $amount, $bonus_bet, $tid, $award_type);
                if($extid === false)
                    return 7;
	        $balance   = $this->playChgBalance($this->udata, $amount, $req['gameRoundRef'], $award_type);
                if($balance === false)
                    return 7;
            // TODO make a DDBB transaction with the whole process of inserting a win + round + change balance
            $this->updateRound($this->udata['id'], $req['gameRoundRef'], $extid);
	        $balance   = $this->handlePriorFail($this->udata, $tid, $balance, $amount);
	        return array('extid' => $extid, 'balance' => $balance);

            }
        } else {
            return array('extid' => uniqid(), 'balance' => $this->_getBalance($req));
        }

        return array();
    }

  function getCurGame($req){
    if ($this->isTournamentMode()) {
        $iso = $this->getLicSetting('bos-country', $this->cur_user);
        if (!empty($iso)) {
            $gco = phive('MicroGames')->getGameCountryOverrideByOverride($this->cur_user, $this->gref, $iso, true);
            if (!empty($gco)) {
                return phive('MicroGames')->getById($gco['game_id']);
            }
        }
    }

    $cur_game = phive('MicroGames')->getByGameRef($this->gref, null, $this->cur_user);
    if(empty($cur_game)){
      phive()->dumpTbl('netent_missing_game', ['gref' => $this->gref, 'request' => $req], $req['userId']);
      $cur_game = phive('MicroGames')->getByGameRef('netent_system');
    }
    return $cur_game;
  }

  function deposit($req) {
    list($amount, $tid) = $this->getAmountTid($req);
    $result = $this->getBetByMgId($tid, 'wins');
    $balance = $this->_getBalance($req);
    if (!empty($result)) {
      phive()->dumpTbl('netent_existing', $req);
        $this->logger->debug(__METHOD__, ['existing' => $req]);
      $res = array('extid' => $result['id'], 'balance' => $balance);
    } else {
      $cur_game = $this->getCurGame($req);
      //$cur_game = phive('MicroGames')->getByGameRef($this->gref);
      $res = $this->_deposit($req, $amount, $tid, $cur_game, $balance);
      if (!is_array($res)) {
          return $res;
      }
    }

    return "<balance>" . phive()->twoDec($res['balance']) . "</balance><transactionId>{$res['extid']}</transactionId>";
  }

    /**
     * @param $req
     * @return int|string
     */
    function getJpContrib($req) {
        $jackpot = $req['jackpotContributions'] ?? null;
        if (empty($jackpot)) {
            return 0;
        }

        // "array_walk_recursive" only iterates over leaves, which is fine in this case.
        $sum = 0;
        array_walk_recursive( $jackpot, function ($value, $key) use (&$sum) {
            if ($key == 'contribution') {
                $sum = bcadd($value, $sum, 4);
            }
        });

        return bcmul($sum, 100, 4);
    }

  function _withdraw($req, $amount, $tid, $cur_game, $balance) {
      if (!empty($amount)) {

        $jp_contrib = $this->getJpContrib($req);
        $balance = $this->lgaMobileBalance($this->udata, $cur_game['ext_game_name'], $balance, $cur_game['device_type'], $amount);

      if ($balance < $amount) {
	    return 1;
      }

      $balance = $this->playChgBalance($this->udata, "-$amount", $tid, 1);
      if ($balance === false) {
          return 1;
      }

      $bonus_bet = empty($this->bonus_bet) ? 0 : 1;

      //$GLOBALS['mg_id'] = $this->params['betreferencenum'];
      $extid = $this->insertBet($this->udata, $cur_game, $req['gameRoundRef'], $tid, $amount, $jp_contrib, $bonus_bet, $balance);
      if($extid === false)
          return 7;
      $this->insertRound($this->udata['id'], $extid, $req['gameRoundRef']);
      $balance = $this->betHandleBonuses($this->udata, $cur_game, $amount, $balance, $bonus_bet, $req['gameRoundRef'], $tid);
      return array('extid' => $extid, 'balance' => $balance);
    } else {
      return array('extid' => uniqid(), 'balance' => $this->_getBalance($req));
    }

    return array();
  }

  function withdraw($req) {
    list($amount, $tid) = $this->getAmountTid($req);
    $result = $this->getBetByMgId($tid);
    $balance = $this->_getBalance($req);
    if (!empty($result)) {
      $res = array('extid' => $result['id'], 'balance' => $balance);
    } else {
      $cur_game = $this->getCurGame($req);
      //$cur_game = phive('MicroGames')->getByGameRef($this->gref);
      $res = $this->_withdraw($req, $amount, $tid, $cur_game, $balance);
      if (!is_array($res)) {
	    return $res;
      }
    }

    return "<balance>" . phive()->twoDec($res['balance']) . "</balance><transactionId>{$res['extid']}</transactionId>";
  }

  function withdrawAndDeposit($req) {
    list($amount, $tid) = $this->getAmountTid($req, 'withdraw');
    $restype = 'bet';
    $balance = $this->_getBalance($req);
    $cur_game = $this->getCurGame($req);

    $balance_to_send = $balance;

    if (empty($cur_game)) {
      phive()->dumpTbl('netent_nogame', $this->xml);
        $this->logger->error(__METHOD__, ['nogame' => $this->xml]);
      return 1;
    }

    $result = $this->getBetByMgId($tid);
    if (!empty($result))
      $old = array('extid' => $result['id'], 'balance' => $balance);
    else{
      $betres = $this->_withdraw($req, $amount, $tid, $cur_game, $balance);
      $balance_to_send -= $amount;
      if (!is_array($betres))
	return $betres;
      $balance = $betres['balance'];
    }

    list($amount, $tid) = $this->getAmountTid($req, 'deposit');
    $result = $this->getBetByMgId($tid, 'wins');
    if (!empty($result)) {
      phive()->dumpTbl('netent_existing', $req);
        $this->logger->error(__METHOD__, ['existing' => $req]);
      $old = array('extid' => $result['id'], 'balance' => $balance);
    } else {
      $restype = 'win';
      $winres = $this->_deposit($req, $amount, $tid, $cur_game, $balance);
      $balance_to_send += $amount;
    }
    if (!empty($old))
      $res = $old;
    else
      $res = $restype == 'bet' ? $betres : $winres;

    return "<newBalance>" . phive()->twoDec($balance_to_send) . "</newBalance><transactionId>{$res['extid']}</transactionId>";
  }

  // Updated the function to be compatible with parent QuickFire's _getBalance function.
  function _getBalance() {
    if(empty($this->t_entry)){
      $balance = $this->useExternalSession($this->cur_user) ? $this->getSessionBalance($this->cur_user) : $this->cur_user->getBalance();
      $gref = $this->getOriginalRefFromOverridden();
      $gref = empty($gref) ? $this->gref : $gref;
      $bonus_balances = empty($this->gref) ? 0 : phive('Bonuses')->getBalanceByRef($gref, $this->udata['id']);
      return $this->lgaMobileBalance($this->udata, $this->gref, $balance + $bonus_balances);
    }else
      return $this->tEntryBalance();
  }

    /**
     * TODO move this out of here this is just a panic fix
     * @return mixed
     */
  public function getOriginalRefFromOverridden()
  {
      $jur = licJur($this->cur_user);
      return phive('SQL')->getValue("SELECT mg.game_id FROM game_country_overrides gco
                                        LEFT JOIN micro_games mg on gco.game_id = mg.id
                                        WHERE gco.ext_game_id = '{$this->gref}' AND gco.country = '{$jur}'");
  }

  // Updated the function to be compatible with parent QuickFire's getBalance function.
  function getBalance($as_string = true, $from_cache = true) {
    return '<balance>' . phive()->twoDec($this->_getBalance()) . '</balance>';
  }

  function getPlayerCurrency($req) {
    $currency = $this->getPlayCurrency($this->udata);
    //$currency = empty($this->t_entry) ? $this->udata['currency'] : 'EUR';
    return '<currencyIsoCode>' . $currency . '</currencyIsoCode>';
  }

  function _rollback($tbl, $req) {
    $id = 'netent' . $req['transactionRef'];
    $result = $this->getBetByMgId($id, $tbl);
    $already = false;
    if (empty($result)) {
      $already_id = $id . 'ref';
      $result = $this->getBetByMgId($already_id, $tbl);
      if (!empty($result)) {
	$already = true;
      }
    }

    if ($already == false && !empty($result)) {
        if ($tbl == 'bets'){
	    $amount = $result['amount'];
            $type = 7;
        }else{
	    $amount = -$result['amount'];
            $type = 1;
        }
        $balance = $this->playChgBalance($this->udata, $amount, $result['trans_id'], $type);
        $this->doRollbackUpdate($id, $tbl, $balance, $amount);
        return true;
    }

    return false;
  }

    public function getSettingOrDefault($setting = null, $user = null)
    {
        if (!empty($setting)) {
            return $this->getLicSettingOrOverride($setting, $user);
        }
        return false;
    }


    public function getSettingOrProxy($setting = null, $user = null)
    {
        if (!empty($setting)) {
            return !empty($this->getLicSettingOrOverride($setting, $user))
                ? $this->getLicSettingOrOverride($setting, $user)
                : $this->getProxySetting($setting, $user);
        }
    }


    function getProxySetting($normal, $ud = '', $proxy = ''){
        $ud = ud($ud);

        // If we don't want to proxy and if player is not Australian
        if(!$this->doProxy($ud)) {
            return $this->getSettingOrDefault($normal,$ud);
        }

        // If we want to proxy or if the player is Australian
        // Proxying so we use the proxy version and if it doesn't exist we just prepend proxy_ to the normal version.
        if(empty($proxy))
            $proxy = "proxy_{$normal}";

        phive()->dumpTbl("proxy", [
                'action' => "Proxyed url",
                'url' => $proxy,
                'user id' => $ud['id'],
                'username' => $ud['username'],
               'country' => $ud['country']
           ]
       );
        $this->logger->debug(__METHOD__, ['proxy' => [
            'action' => "Proxyed url",
            'url' => $proxy,
            'user id' => $ud['id'],
            'username' => $ud['username'],
            'country' => $ud['country']
        ]]);
        $res = $this->getSetting($proxy);
        if(empty($res))
            $res = $this->getSettingOrDefault($normal,$ud);

        return $res;
    }

  function fixGid($gid, $sw_too = true) {
    $replace = array('netent_');
    if($sw_too === true)
      $replace[] = '_sw';
    return str_replace($replace, '', $gid);
  }

  function rollbackTransaction($req) {
    phive()->dumpTbl('netent_rollback', $req);
      $this->logger->debug(__METHOD__, ['rollback' => $req]);
    $this->_rollback('bets', $req);
    $this->_rollback('wins', $req);
    return '';
  }

    /*
  function getFreeSpinForPromo($uid, $status = 'active', $extra = '') {
    $where_status = empty($status) ? "AND status != 'failed'" : "AND status = '$status'";
    $str = "SELECT * FROM bonus_entries WHERE user_id = $uid AND bonus_tag = 'netent' AND balance = 0 AND cost = 0 $where_status $extra";
    return phive('SQL')->loadAssoc($str);
  }
    */


  function getDepUrl($gid, $lang, $game = null, $show_demo = false) {

      $this->loadUserForGameLaunch();

      if ($_SESSION['token_uid'] ?? false) {
          $g = phive('MicroGames')->getByGameId($gid, 0, null);
          $r = phive('MicroGames')->overrideGameForTournaments($this->cur_user, $g);
      } else {
          $r = phive("MicroGames")->getGameOrOverrideByGid($gid, $this->cur_user);
      }
      if (!empty($r)) {
          $gid = $r['ext_game_name'];
      }

      $token_uid = $_SESSION['token_uid'] ?? '';
      $url = phive()->getSiteUrl() . $this->getSetting('flash_play') . "?gid=$gid&lang=$lang&mp_id={$token_uid}" . ($show_demo ? "&show_demo=true" : "");
      $this->dumpTst('netent_launch_desktop', ['url' => $url]);
      $this->logger->debug(__METHOD__, [$url]);
      return $url;
  }

  function getMobilePlayUrl($gref, $lang, $lobby_url, $g, $args = [], $show_demo = false) {
    $this->loadUserForGameLaunch();

    if ($_SESSION['token_uid'] ?? false) {
      $g = phive('MicroGames')->getByGameRef($gref, 1, null);
      $r = phive('MicroGames')->overrideGameForTournaments($this->cur_user, $g);
    } else {
      $r = phive("MicroGames")->getGameOrOverrideByGref($gref, $this->cur_user, 1);
    }
    if (!empty($r)) {
        $gref = $r['ext_game_name'];
    }

    $gref = str_replace(array('netent_', '_sw'), '', $gref);
    $url = phive()->getSiteUrl() . "/diamondbet/netent_new.php?gid=$gref&lang=$lang&channel=mobg";

    if(!empty($_SESSION['token_uid'])) {
      $url .= "&mp_id={$_SESSION['token_uid']}";
    }
    $this->dumpTst('netent_launch_mobile', ['url' => $url]);
      $this->logger->debug(__METHOD__, [$url]);
    return $url;
  }

    function postSoap($xml, $action, $arr = null, $auth_prefix = 'sub', $timeout = null) {
      if($timeout === null) {
        $timeout = $this->getSetting('soap_timeout');
      }
        if($this->getSetting('out-false'))
            return false;

        if (!empty($arr)) {
            $str = '';
            foreach ($arr as $k => $v)
	        $str .= "<$k>$v</$k>";
            $xml .= $str;
        }

        if (!empty($this->cache['soap_username'])) {
            $username = $this->cache['soap_username'];
        } else {
            $username = $this->getLicSettingOrOverride("{$auth_prefix}username");
        }

        if (!empty($this->cache['soap_password'])) {
            $password = $this->cache['soap_password'];
        } else {
            $password = $this->getLicSettingOrOverride("{$auth_prefix}password");
        }

        $url = $this->getLicSettingOrOverride('url');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:api="http://casinomodule.com/api" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/">
             <soapenv:Header/>
             <soapenv:Body>
                <api:' . $action . ' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                  ' . $xml . '
                   <merchantId xsi:type="xsd:string">' . $username . '</merchantId>
                   <merchantPassword xsi:type="xsd:string">' . $password . '</merchantPassword>
                </api:' . $action . '>
             </soapenv:Body>
          </soapenv:Envelope>';
        $debug_key = '';
        if($action != 'getCurrentJackpot'){
            $debug_key = $this->extCallDebug("netent_{$action}");
        }
        $this->logger->debug(__METHOD__ . "{$action}_request", [$url, $xml]);
        $res = phive()->post($url, $xml, 'text/xml', ["SOAPAction: \"{$url}/$action\""], $debug_key, 'POST', $timeout);

        $this->dump("netent_{$action}_res", $res);
        $this->dump("netent_{$action}_xml", $xml);

        if (strpos($res, '<soapenv:Fault>') !== false) {
            $this->dump("netent_{$action}_fault", $res);
            phive()->dumpTbl("netent_{$action}_fault", [$res, $url, $xml]);
            $this->logger->error(__METHOD__."netent_{$action}_fault" , [$res, $url, $xml]);
            return 'fail';
        }
        $this->logger->debug(__METHOD__ . "{$action}_response", [$res]);
        return $res;

    }

    function getExtUname($uid, $country = '') {
        $ud = ud($uid);
        $uid = $this->getUid($ud, $uid);
        if (in_array($country, $this->getSetting('licensed_environments', []))) {
            return $uid;
        } else {
            return $this->getSetting('subprefix') . $uid;
        }
    }

  function getBonuses($uid) {
    return $this->postSoap("<userName>$uid</userName><password>$uid</password>", 'getUserBonusInfoDetailV4');
  }

  function getUserFRB($uid) {
    $country = cu($uid)->getCountry();
    $res = $this->postSoap('', 'getUserFreeRoundGames', array('userName' => $this->getExtUname($uid, $country), 'password' => $uid), 'call_');
    $res = $this->fixXml($res);
    try{
      $res = new SimpleXMLElement($res);
      $res = (array)$res->xpath('//getUserFreeRoundGamesReturn');
      $res = (array)$res[0];
      $res = $res['getUserFreeRoundGamesReturn'];
      return $res;
    }catch (Exception $e){
        $this->logger->error(__METHOD__, [$e->getMessage()]);
    }

  }

  function frbStatus($e) {
    if($e['status'] != 'active')
      return false;
    $gids = $this->getUserFRB($e['user_id']);
    if(empty($gids))
      return 'activate';
    $egids = array_map(function($gid){ return phive('Netent')->fixGid($gid, false); }, explode('|', $e['ext_id']));
    $intersected = array_intersect($gids, $egids);
    if(count($intersected) === count($egids))
      return false;
    return 'activate';
  }

  function awardFRBonus($uid, $gids, $rounds, $bonus_name, $entry) {
    if($this->getSetting('no-out') === true) {
        phive()->dumpTbl('netent_test_frb_awarded', func_get_args());
        return true;
    }

      //TODO, better to use the new registerUser here instead?
    $this->getSid('', false, $uid);
    $user = cu($uid);
    $country = $user->getCountry();

    $exp_date = $entry['end_time'] . 'T23:59:58';
    $money_exp_date = $entry['end_time'] . 'T23:59:59';
    $arr = array(
      'numberOfFreeRounds' => $rounds,
      'externalReferenceId' => $entry['id'],
      'freeRoundValidity' => $exp_date,
      'bonusMoneyValidity' => $money_exp_date,
      'wagerRequirement' => 0,
      'bonusWallet' => 'SEAMLESS_WALLET',
    );
    $gids = array_map(function ($gid) {return str_replace(array('netent_'), '', $gid);}, explode('|', $gids));
    $gids_str = '';
    foreach ($gids as $gid) {
        $go = phive('SQL')->getValue("SELECT gco.ext_game_id FROM micro_games mg
                        LEFT JOIN game_country_overrides gco ON gco.game_id = mg.id
                        WHERE mg.ext_game_name = 'netent_{$gid}' AND gco.country = '". licJur($user) ."'");
        if (!empty($go))
        $gid = str_replace(array('netent_'), '', $go);

      $gids_str .= '<gameIds>' . $gid . '</gameIds>';
    }

    $arr['gameIds'] = $gids_str;
    $arr['userNames'] = '<userNames>' . $this->getExtUname($uid, $country) . '</userNames>';

    return $this->postSoap('', 'giveFreeRounds', $arr, 'call_');
  }

  function cancelFRBonus($eid) {
    $arr = array(
      'externalReferenceId' => $eid,
      'bonusWallet' => 'SEAMLESS_WALLET',
    );
    return $this->postSoap('', 'forfeitFreeRounds', $arr, 'call_');
  }

    function getCurrentJackpot($jp_id, $currency)
    {
        $current_jackpot_response =$this->postSoap('', 'getCurrentJackpot', array('jackpotId' => $jp_id, 'currencyISOCode' => $currency), null, 10);
        return $this->convertSoapResponse($current_jackpot_response);
    }

    public function getIndividualJackpotInfo()
    {
        $get_individual_jackpot_info =$this->postSoap('', 'getIndividualJackpotInfo', [], null, 10);
        return $this->convertSoapResponse($get_individual_jackpot_info);
    }

    public function getJackpotsForGames()
    {
        $get_jackpot_game_response = $this->postSoap('<queryName>Get Jackpots For Games</queryName>', 'executeNamedQuery', [], null, 10);
        return $this->convertSoapResponse($get_jackpot_game_response);
    }

    function convertSoapResponse($soap)
    {
        $clean_xml = str_ireplace(['soapenv:', 'ns1:'], '', $soap);
        $xml = simplexml_load_string($clean_xml);
        $xml = json_encode($xml);
        $json_xml = json_decode($xml, true);

        return $json_xml;
    }

    /**
     * Information about the provider's jackpots these are jackpots that are
     * Available in multiple currencies where they take care of exchange
     * Jackpots are the same throughout currencies
     * Different Jackpots per device
     *
     * @return array
     */
    function parseJackpots()
    {
        /**
         * Small note about the following
         * We are calling this function to keep consistent with other provider jps
         * as of 09/12/2019 there are currently no other jurisdiction urls to get the jackpots from however,
         * With the current implementation as is, because we have no other jp_urls we're using Netent::postSoap to request the jackpot data
         * there we're using a lic setting to call the url we need to post to and we would need to implement some more
         * changes for other jurisdictions there when needed for now we simply loop through once and place the default key
         * the jur
         */
        $jur_jp_urls = $this->getAllJurSettingsByKey('jp_url');
        $jur_username = $this->getAllJurSettingsByKey('username');
        $jur_password = $this->getAllJurSettingsByKey('password');

        $games_by_game_id = phive('SQL')->load1DArr("SELECT game_name, game_id FROM micro_games WHERE network = 'netent' AND active = 1", 'game_name', 'game_id');

        foreach ($jur_jp_urls as $jur => $jp_url) {
            if (!empty($username = $jur_username[$jur])) {
                $this->cache['soap_username'] = $username;
            }
            if (!empty($password = $jur_password[$jur])) {
                $this->cache['soap_password'] = $password;
            }

            $jackpots_response = $this->getJackpotsForGames();
            $this->dumpTst('Jackpots received', $jackpots_response);

            $jackpots = $jackpots_response['Body']['executeNamedQueryResponse']['executeNamedQueryReturn']['executeNamedQueryReturn'];
            array_shift($jackpots); // this is because the first element is always GameId;JackpotId

            $individual_jackpot_response = $this->getIndividualJackpotInfo(); // returns list of individual jackpot data
            $this->dumpTst('Individual Jackpot Data', $individual_jackpot_response);

            if(empty($jackpots_response) || empty($individual_jackpot_response)) {
                return []; // we have an error receiving or parsing the soap responses
            }

            $list_of_individual_jackpots = $individual_jackpot_response['Body']['getIndividualJackpotInfoResponse']['getIndividualJackpotInfoReturn']['getIndividualJackpotInfoReturn'];

            $jackpot_map = [];

            foreach ($list_of_individual_jackpots as $individual_jackpot) {
                $jackpot_map[$individual_jackpot['jackpotName']] = $individual_jackpot;
            }

            $insert = [];

            foreach ($jackpots as $jackpot) {
                $game_jackpot = explode(';', $jackpot);

                $game_id = 'netent_'.$game_jackpot[0];

                $game_name = $games_by_game_id[$game_id];

                if(empty($game_name)) {
                    continue;
                }

                $jackpot_id = $game_jackpot[1];

                $jackpot_data = $jackpot_map[$jackpot_id];
                $pot = [
                    'jp_value'      => $jackpot_data['currentJackpotValue']['amount'] * 100,
                    'currency'      => 'EUR',
                    'jp_name'       => $game_name,
                    'network'       => 'netent',
                    'jp_id'         => $jackpot_id,
                    'module_id'     => $game_id,
                    'local'         => strtolower($jackpot_data['jackpotType']) == 'local' ? 1 : 0,
                    'game_id'       => $game_id,
                    'jurisdiction'  => $jur
                ];

                $insert[] = $pot;
            }
        }

        return $insert;
    }

    function logoutUser($uid, $netent_username){
        $uid  = uid($uid);
        $sid  = phMgetShard($netent_username, $uid);
        if(empty($sid))
            return false;
        $body = '<sessionId xsi:type="xsd:string" xs:type="type:string" xmlns:xs="http://www.w3.org/2000/XMLSchema-instance">'.$sid.'</sessionId>';
        $this->postSoap($body, 'logoutUser');
        phMdelShard($netent_username, $uid);
        return true;
    }

    function regLoginCommon($u, $ext_uid, $action, $qtag, $extra_xml = ''){
        $dob    = str_replace('-', '', $u['dob']);
        $sex    = $u['sex'] == 'Male' ? 'M' : 'F';
        $action = $action;
        $qtag   = $qtag;

        foreach ($u as $key => &$val)
	    $val = phive()->chop($val, 19, '');

        $currency = $this->getPlayCurrency($u, $this->t_eid);
        $country  = $this->getCountry($u);
        $city     = $this->getCity($u);
        if ($this->isTournamentMode()) {
            $tournament_country = $this->getLicSetting('bos-country', $u);
            if (!empty($tournament_country)) {
                $country = $city = $tournament_country;
            }
        }

        $data = "$extra_xml
              <data>FName</data>
              <data>{$u['firstname']}</data>
              <data>LName</data>
              <data>{$u['lastname']}</data>
              <data>Birthdate</data>
              <data>$dob</data>
              <data>City</data>
              <data>$city</data>
              <data>Country</data>
              <data>$country</data>
              <data>Sex</data>
              <data>$sex</data>
              <data>DisplayName</data>
              <data>{$u['firstname']}</data>";

        $xml = '
               <userName xsi:type="xsd:string">' . $ext_uid . '</userName>
               <password xsi:type="xsd:string">' . $ext_uid . '</password>
               ' . $promo . '
               <extra xsi:type="api:ArrayOf_xsd_string" soapenc:arrayType="xsd:string[]">
                 ' . $data . '
               </extra>
               <currencyISOCode xsi:type="xsd:string">' . $currency . '</currencyISOCode>';

        $response = $this->postSoap($xml, $action);
        $xml_response = simplexml_load_string($response);
        if ($xml_response === false) {
            return false;
        }
        $xml_element = $xml_response->xpath("//{$qtag}")[0] ?? null;
        return (string)$xml_element;
    }

    function registerUser($uid = '', $t_eid = '', $test = false){
        $u = ud($uid);
        if(!empty($t_eid))
            $ext_uid = $u['id'].'e'.$t_eid;
        $ext_uid = $this->getUid($u, $ext_uid);
        $this->t_eid = $t_eid;
        if (!empty($u))
            return $this->regLoginCommon($u, $ext_uid, 'registerUser', 'registerUserReturn');
    }

    function getSid($channel = '', $test = false, $uid = '', $mp_id = ''){
        $u = !empty($uid) ? ud($uid) : $_SESSION['local_usr'];
        //phive()->dumpTbl('netent_tournament', $_SESSION);
        if(!empty($mp_id)){
            $uid = $mp_id;
            $this->getUsrId($mp_id);
        }else
            $uid = $u['id'];
        $uid = $this->getUid($u);
        if (!empty($u)) {
            if (!empty($channel))
	        $ch_xml = "<data>Channel</data><data>$channel</data>";
            if ($this->useExternalSession($u)) {
                $this->logoutUser($u, $uid);
            }
            $sid = $this->regLoginCommon($u, $uid, 'loginUserDetailed', 'loginUserDetailedReturn', $ch_xml);
            if(!empty($mp_id)){
                // TODO implement logout for all forms of play
                $this->logger->debug(__METHOD__, ['mp_id' => $mp_id]);
                phMsetShard($mp_id, $sid, $u['id']);
            } else if ($this->useExternalSession($u)) {
                $this->logger->debug(__METHOD__, ['mp_id' => 'useExternalSession' . $mp_id]);
                phMsetShard($uid, $sid, $u['id']);
            }

            $this->logger->getLogger('game_providers')->debug(__METHOD__, [
                'user' => $uid,
                'sid' => $sid,
                'mp_id' => $mp_id,
            ]);

            if (!$sid) {
                $this->logger->error(__METHOD__, ['No session_id']);
                return $this->buildError(5);
            }
            $this->logger->debug(__METHOD__, ['session_id' => $sid]);
            return $sid;
        }
    }

  function calcBalances($date) {
    $res = array();
    $wins = phive()->group2d(phive('SQL')->shs('merge', '', null, 'bets_tmp')->loadArray("SELECT * FROM bets_tmp WHERE mg_id LIKE 'netent%' AND created_at >= '$date 23:59:30' AND created_at <= '$date 23:59:59'"), 'currency');
    foreach ($wins as $cur => $ws) {
      $uwsg = phive()->group2d($ws, 'user_id');
      foreach ($uwsg as $uws) {
	$average = phive()->sum2d($uws, 'balance') / count($uws);
	$res[$cur] += $average;
      }
    }

    foreach ($res as $cur => $balance)
      phive()->miscCache("$date-netent-balance-$cur", (int)$balance);
  }

  public function processRequest($request, $jurisdiction = null) {

      $smicro = microtime(true);

      /*
      if(phive("Config")->getValue('network-status', 'netent') == 'off')
          die("turned off");
      */
      $sql = phive('SQL');

      $this->dump('netent_request', $request);
      $this->logger->debug(__METHOD__, ['request' => $request]);

      list($func, $req) = $this->getReqData($request);

      $this->dump('netent_parsed_request', compact('func', 'req'));
      list($casino, $uid) = explode('_', $req['playerName']);

      $result = $this->execute($func, $req, $jurisdiction);

      $duration 	= microtime(true) - $smicro;

      $insert = array(
          'duration' 	=> $duration,
          'method' 	=> $func,
          'mg_id'		=> $GLOBALS['mg_id'],
          'token' => 'netent',
          'username' 	=> $this->uid,
          'host' => gethostname());

      if($this->isTest()){
          $sql->insertArray('game_replies', $insert);
      }

      phive('MicroGames')->logSlowGameReply($duration, $insert);

      $output = ob_get_clean();

      $this->dump('netent_answer', $result);
      $this->logger->debug(__METHOD__, ['answer' => $result]);
      $this->dump('netent_output', $output);
      $this->logger->debug(__METHOD__, ['output' => $output]);

      return $result;
  }
    /**
     * Starts the External Game Session for Italian players
     * If the startup fails it will show an error popup and redirect the player to the lobby
     *
     * @param $user
     * @param $session_id
     * @param $game
     * @return false|int|mixed
     */
    public function initNetentExternalGameSession($user, $session_id, $game)
    {
        $this->logger->getLogger('game_providers')->debug(__METHOD__, [
            'user' => $user->getId(),
            'session_id' => $session_id,
            'ext_game_name' =>  $game['ext_game_name'],
        ]);

        if (!empty($external_session_id = lic('initGameSessionWithBalance', [$user, $session_id, $game], $user))) {
            return $external_session_id;
        }
        return false;
    }

    /**
     * Loads the current user and sets $this->t_entry if this is a tournament.
     */
    private function loadUserForGameLaunch()
    {
        if ($_SESSION['token_uid'] ?? false) {
            $this->uid = $this->getUsrId($_SESSION['token_uid']);
            $this->cur_user = cu($this->uid);
        } else {
            $this->cur_user = cu();
            if (!empty($this->cur_user)) {
                $this->uid = $this->cur_user->getId();
            }
        }
    }

    /**
     * @param $key
     * @param null $user
     * @return mixed
     */
    private function getLicSettingOrOverride($key, $user = null)
    {
        if ($this->isTournamentMode()) {
            $country = $this->getLicSetting('bos-country', $user);
            if (!empty($country)) {
                $def_ss = (array)$this->getSetting('licensing')['DEFAULT'];
                $jur_ss = (array)$this->getSetting('licensing')[$country];
                return $jur_ss[$key] ?? $def_ss[$key];
            }
        }
        return $this->getLicSetting($key, $user);
    }
}
