<?php
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/../../html/display_base_diamondbet.php';
include_once __DIR__ . '/../Former/FormerCommon.php';

use Snipe\BanBuilder\CensorWords;
use Videoslots\HistoryMessages\EndSessionHistoryMessage;
use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\TournamentCashTransactionHistoryMessage;
use Videoslots\HistoryMessages\TournamentHistoryMessage;

class Tournament extends PhModule {
      /** @var SQL $db */
      public $db;

  function __construct(){
      $this->db = phive('SQL');
      $this->mc = mCluster('mp');
  }

    function padLock(&$t){
        return empty($t['pwd']) ? '' : imgTag(fupUri('padlock_icon.png', true));
    }

    /**
     * Set bounty guy for a tournament
     *
     * @param tournamentId $
     * @param $bountyGuyId
     */
    // TODO this might not work if we work with "stale" tournaments in the nodes /Henrik
    public function setBountyGuysForTournament($tournamentId = null){
        if(empty($tournamentId))
            return;
        $tournamentId = intval($tournamentId);
        $sql = "SELECT te.user_id
                FROM tournament_entries te
                JOIN tournaments t ON te.t_id = t.id
                WHERE
                    te.result_place = 1
                    AND te.t_id != $tournamentId
                    AND t.prizes_calculated = 1
                    AND t.calc_prize_stamp BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()";
        $bountyGuys = $this->db->shs('merge', '', null, 'tournament_entries')->loadArray($sql);
        foreach($bountyGuys as $bountyGuy){
            $sql2 = "UPDATE tournament_entries SET bounty = true WHERE user_id = {$bountyGuy['user_id']} AND t_id = $tournamentId";
            phive('SQL')->sh($bountyGuy, 'user_id', 'tournament_entries')->query($sql2);
        }
    }

    // TODO test this
    function handleBountyPrize($t){
        if(empty($t['bounty_award_id']))
            return;
        $sql = "SELECT user_id, result_place FROM tournament_entries WHERE t_id = {$t['id']}";
        // TODO test this
        $tes = $this->db->shs('merge', '', null, 'tournament_entries')->loadArray($sql, 'ASSOC', 'result_place');
        foreach($tes as $place => $te) {
            if(!empty($te['bounty']) && $place > 1){
                $recipient = $tes[$place - 1];
                phive('Trophy')->giveAward($t['bounty_award_id'], ud($te['user_id']));
            }
        }
    }

  function insertBetWin($tbl, $ins){
    return phive('SQL')->sh($ins, 'user_id', $tbl)->insertArray($tbl, $ins);
  }

  function cSym(){
      return $this->getSetting('currency_sym');
  }

  function fmSym($amount, $div = 100){
    return $this->cSym().' '.nf2(round($amount / $div, 2), true, 1, ".", "");
  }

    /**
     * @param $cu_currency
     * @param $amount
     * @param int $div
     * @return string
     */
  public function fullFmSym($cu_currency, $amount, $div = 100){
      if (empty($cu_currency)) {
          return $this->fmSym($amount, $div);
      }
      return $this->fmSym($amount, $div).' '.($this->tCur() == $cu_currency ? '' : '('.efEuro(chg($this->tCur(), $cu_currency, $amount), true).')');
  }

  function prSpinInfo(&$t, $f){
    if(!empty($t['xspin_info']))
      $f();
  }

  function _getTplsWhere($where = array()){
      return $this->db->arrayWhere("tournament_tpls", $where);
  }

  function _getTplWhere($where = array()){
      return $this->db->loadAssoc('', 'tournament_tpls', $where);
  }

    function _getEntryWhere($where, $uid = ''){
        $uid = uid($uid);
        $db = empty($uid) ? $this->db->shs('merge', '', null, 'tournament_entries') : $this->db->sh($uid, '', 'tournament_entries');
        return $db->loadAssoc('', 'tournament_entries', $where);
    }

    function entryByTidUid($tid, $uid){
        if(is_array($tid))
            $tid = $tid['id'];
        if(is_array($uid))
            $uid = $uid['id'];
        $arr = ['t_id' => (int)$tid, 'user_id' => (int)$uid];
        return $this->_getEntryWhere($arr, $uid);
    }

  function isRegistered($tid, $uid = ''){
    $uid = empty($uid) ? cuPlId() : $uid;
    return !phive()->isEmpty($this->entryByTidUid($tid, $uid));
  }

  function placeUserHasInLeaderboard($tid, $uid = '')
  {
    $uid = empty($uid) ? cuPlId() : $uid;
    $entry = $this->entryByTidUid($tid, $uid);
    return $entry['result_place'] ?? 0;
  }
  /* Returns the number of spins a given user has left */
  function spinsUserHasLeft($tid, $uid = '')
  {
    $uid = empty($uid) ? cuPlId() : $uid;
    $entry = $this->entryByTidUid($tid, $uid);
    return $entry['spins_left'] ?? 0;
  }

  function entryById($eid, $uid = ''){
    return $this->_getEntryWhere(array('id' => (int)$eid), $uid);
  }

  function _getEntriesWhere($where = array()){
      return $this->db->shs('merge', '', null, 'tournament_entries')->arrayWhere("tournament_entries", $where);
  }

  //rc done
  function getEntriesByTplidUid($t, $ud, $entry_status = 'open', $recur_type = ''){
    $uid = is_numeric($ud) ? $ud : $ud['id'];
    if($recur_type !== ''){
      $tpl = $this->getParent($t);
      if((int)$tpl['recur'] !== $recur_type)
        return array();
    }
    $where_status = empty($entry_status) ? '' : " AND status = '$entry_status'";
    $str = "SELECT * FROM tournament_entries WHERE user_id = $uid $where_status AND t_id IN(SELECT id FROM tournaments WHERE tpl_id = {$t['tpl_id']})";
    return $this->db->sh($uid, '', 'tournament_entries')->loadArray($str);
  }

  /*
  function _runningEntries($uid){
    $uid = empty($uid) ? $_SESSION['mg_id'] : $uid;
    return $this->_getEntriesWhere(array('user_id' => $uid, 'status' => 'open'));
  }
  */

  function getEntriesByIds($eids){
      $eids = $this->db->makeIn($eids);
      //TODO test this
      return $this->db->shs('merge', '', null, 'tournament_entries')->loadArray("SELECT * FROM tournament_entries WHERE id IN($eids)", 'ASSOC', 'id');
  }

    function getDailyGameUserStats($date){
        // TODO can we remove? $tbl is not used
        if($tbl == 'bets'){
            $contrib = 'SUM(bw.jp_contrib) AS jp_contrib,';
        }

        // Should be good as only t.game_ref is retrieved, not prize_amount or registered_players
        $str = "SELECT
                bw.date,
                mg.device_type_num AS device_type,
                SUM(bw.bets) AS bets,
                SUM(bw.wins) AS wins,
                t.game_ref,
                bw.currency,
                bw.user_id,
                SUM(bw.op_fee) AS op_fee,
                $contrib
                bw.username,
                bw.firstname,
                bw.lastname,
                bw.country,
                mg.network
              FROM users_daily_stats_mp bw
              LEFT JOIN tournaments AS t ON bw.t_id = t.id
              LEFT JOIN micro_games AS mg ON t.game_ref = mg.ext_game_name AND mg.device_type_num = 0
              WHERE bw.date = '$date'
              GROUP BY bw.user_id, t.game_ref";

        return phive('SQL')->loadArray($str);
    }

    //Needs to be run after Cashier->calcUserCache and before the rest of the calculations
    //This one is doing the total stats, both mp and normal stats have to be in place before this one can be executed.
    function mpDailyStats($date){
        //$mp_rows = phive('UserHandler')->getDailyStats($date, $date, '', 'users_daily_stats_mp', 'user_id');
        $rows    = phive('UserHandler')->getDailyStats($date, $date, '', 'users_daily_stats', 'user_id');
        foreach($rows as $key => $row)
            unset($rows[$key]['id']);
        $base_map = ['user_id', 'currency', 'date', 'country', 'username', 'firstname', 'lastname'];
        $base_select = implode(',', $base_map);
        $map1 = array_merge($base_map, array(
            'affe_id',
            'deposits',
            'withdrawals',
            'bank_fee',
            'bank_deductions',
            'chargebacks',
            'transfer_fees',
            'ndeposits',
            'nwithdrawals',
            'nbusts',
            'frb_wins',
            'jp_fee',
            'frb_ded',
            'frb_cost'));

        $map2    = array('gross', 'op_fee', 'rewards', 'before_deal', 'tax', 'site_rev', 'gen_loyalty', 'bets', 'wins', 'jp_contrib', 'mp_adj');

        $sums    = $this->db->makeSums($map2);
        $str     = "SELECT $base_select, $sums FROM users_daily_stats_mp WHERE `date` = '$date' GROUP BY user_id";
        $mp_rows = $this->db->loadArray($str, 'ASSOC', 'user_id');

        $map3    = array_merge($map1, $map2);
        $inserts = $rows;

        foreach($mp_rows as $uid => $mp_r){
            $r = $inserts[$uid];
            if(empty($r)){
                $tmp = phive()->moveit($map3, $mp_r);
                $u = ud($uid);
                $tmp['affe_id'] = $ud['affe_id'];
                $inserts[$uid] = $tmp;
            }else{
                $r = phive()->addArrays($r, $mp_r, $map2);
                $inserts[$uid] = $r;
            }
        }

        $this->db->insert2DArr('users_daily_stats_total', $inserts);
    }

  function getUserResults($ud, $stime, $etime, $statuses = array('open')){
    $uid = uid($ud);
    if(!empty($statuses))
      $in = " AND te.status IN({$this->db->makeIn($statuses)}) ";
    $str = "SELECT te.*, t.tournament_name, t.start_format, t.start_time, t.end_time, t.mtt_start, t.status AS tstatus, mg.game_url, mg.game_name, t.tpl_id
            FROM tournament_entries te, micro_games mg, tournaments t
            WHERE te.user_id = $uid
            AND t.id = te.t_id
            AND mg.ext_game_name = t.game_ref
                AND t.start_time >= '$stime'
            AND t.start_time <= '$etime'
            $in";

    return $this->db->sh($uid, '', 'tournament_entries')->loadArray($str);
  }

  function cashBalance($t){
    return $this->sumEntries($t, 'cash_balance');
  }

    function sumEntries($t, $col){
        return array_sum(phive()->flatten($this->db->shs('merge', '', null, 'tournament_entries')->loadArray("SELECT SUM($col) FROM tournament_entries WHERE t_id = {$t['id']}")));
        //return $this->db->getValue("SELECT SUM($col) FROM tournament_entries WHERE t_id = {$t['id']}");
  }

  function getUserBalance($ud = null){
    $ud = empty($ud) ? cuPl()->data : $ud;
    return chg($ud['currency'], $this->tCur(), $ud['cash_balance'], 1);
  }

  function getMpCashInfo(&$t, &$ud, $action = 'registration'){
    $balance    = $this->getUserBalance($ud);
    $total_cost = $this->getBuyin($t, true);
    $rebuy_cost = $t['rebuy_cost'] + $t['rebuy_house_fee'];
    $can_afford = $action == 'registration' ? ($total_cost <= $balance) : ($rebuy_cost < $balance);
    return array($total_cost, $balance, $can_afford, $rebuy_cost);
  }

  //no rc - both
  function getAwardSpot($place){
    if($place['start_spot'] == $place['end_spot'])
      return $place['start_spot'].$this->spotSuffix($place['start_spot']);
    return $place['start_spot'].$this->spotSuffix($place['start_spot']).' - '.$place['end_spot'].$this->spotSuffix($place['end_spot']);
  }

  //no rc - both
  function isFreeRoll(&$t){
    return $t['category'] == 'freeroll';
  }

  function lgaLimConflict(&$t, $u = null, $is_rebuy = false, $is_ticket = false){
      $u = empty($u) ? cuPl() : $u;

      if(empty($u))
          return false;

      if ($is_rebuy === true) {
          $thold = $t['rebuy_cost'] + $t['rebuy_house_fee'];
      } else {
          $thold = $t['total_cost'];
      }

      if(empty($thold))
          return false;

      $rg     = rgLimits();

      // We get all limits grouped by type with the least left on each type.
      $limits = $rg->getMinLeftGrouped($u, ['wager', 'loss', 'betmax', 'timeout']);

      if(empty($limits))
          return false;

      // We exit immediately if a timeout limit exists.
      if(isset($limits['timeout']))
          return 'mp.lgatime.msg';

      // We convert the limits to pretty base currency
      foreach($limits as $type => &$left)
          $left = mc($left, $u, 'div');

      // If the bet max is less than the min bet of the tournament we fail.
      if(isset($limits['betmax']) && $limits['betmax'] < $t['min_bet']){
          return 'mp.lgamaxbet.msg';
      }

      // If the loss limit left is less than the cost of participating in the tournament we fail.
      if(isset($limits['loss']) && $t['category'] != 'freeroll' && $thold > $limits['loss'] && !$is_ticket){
          return 'mp.lgaloss.msg';
      }

      // If the wager limit left is less than the cost of participating in the tournament we fail.
      if(isset($limits['wager']) && $thold > $limits['wager']){
          return 'mp.lgawager.msg';
      }

      return false;
  }

  //no rc - both
  function playUrl($el, $game = array()){
      $c_user = cu() ? cu()->getId() : null;
      if ($c_user && empty($el['id'])) {
          phive('Logger')
              ->getLogger('bos_logs')
              ->error("eid is missing on play url", [
                  'request' => $_REQUEST,
                  'user_id' => $c_user,
                  'referrel' => phive('Http/HTTPReq')->getReferrerURL(),
                  'micro_game' => $game['game_url']
              ]);
      }
    $g_url = empty($game) ? $el['game_url'] : $game['game_url'];
    return llink("/play/$g_url/?eid={$el['id']}");
  }

  //rc done
  function runningEntriesWithName($uid){
    $uid = uid($uid);
    $str = "SELECT te.*, t.tournament_name, mg.game_url
            FROM tournament_entries te, tournaments t, micro_games mg
            WHERE te.user_id = $uid AND te.status = 'open' AND te.t_id = t.id AND t.game_ref = mg.ext_game_name";
    return $this->db->sh($uid, '', 'tournament_entries')->loadArray($str);
  }

  function displayResult($e, $status_col = 'status', $t = array())
  {
      $result = $this->getResultForDisplay($e, $status_col, $t);
      if(is_numeric($result)) {
          return $result;
      }

      return t($result);
  }

    /**
     *
     * @param array $e
     * @param string $status_col
     * @param array $t
     * @return string
     */
    function getResultForDisplay($e, $status_col = 'status', $t = array())
    {
        $t = empty($t) ? $this->getByEntry($e) : $t;
        if($e[$status_col] == 'finished'){
          if(!$this->isClosed($t))
            return 'mp.open';
          return empty($e['result_place']) ? 'mp.no.win' : $e['result_place'];
        }
        return "mp.{$e[$status_col]}";
    }

  function displayRegs(&$t){
    return $t['start_format'] == 'sng' ? "{$t['registered_players']} / {$t['max_players']}" : $t['registered_players'];
  }

  function displayPrize($e){
    if($e['status'] == 'finished')
      return empty($e['won_amount']) ? t('mp.no.prize') : $e['won_amount'];
    return t("mp.na");
  }

    function countEntries($t){
        return count($this->entries($t));
        //return phive("SQL")->getValue("SELECT COUNT(*) FROM tournament_entries WHERE t_id = '{$t['id']}'");
    }

  function getActiveMttTpls($his = ''){
    $his = empty($his) ? phive()->hisNow() : $his;
    list($date, $time) = explode(" ", $his);
    $str = "start_format = 'mtt' AND (recur_end_date >= '$his' OR CONCAT(mtt_start_date, ' ', mtt_start_time) >= '$his')";
    return $this->_getTplsWhere($str);
  }

  function getTplById($id){
    return $this->_getTplWhere(array('id' => $id));
  }

  function getParent($t){
    $where = array('id' => $t['tpl_id']);
    return $this->_getTplWhere($where);
  }

  function startDescrJsCall($tid){
    if(empty($_SESSION["start_descr_{$tid}_shown"]))
      echo "mpStartDescr($tid);";
    $_SESSION["start_descr_{$tid}_shown"] = true;
  }

    /**
     * @param $eid
     * @param null $uid - This is used to force a $uid on _getEntryWhere.
     *
     * We need the $uid param to enforce the selected player while logged in as admin, otherwise the current user is enforced.
     * The issue was found while trying to delete a chat message from chat_admin.php
     *
     * @return mixed
     */
  function getByEid($eid, $uid = ''){
    $entry = $this->_getEntryWhere(array('id' => $eid), $uid);
    return $this->getByEntry($entry);
  }

  function getByEntry($e){
    $where = array('id' => $e['t_id']);
    return $this->_getOneWhere($where);
  }

  function getByStatusTpl($tpl, $status){
      return $this->db->arrayWhere("tournaments", array('status' => $status, 'tpl_id' => $tpl['id']));
  }

  function _getByTpl($tpl_id, $mtt_start){
    return $this->_getOneWhere(array('tpl_id' => $tpl_id, 'mtt_start' => $mtt_start));
  }

    function getAllWhere($where = array()){
        //Note that $where can not contain a sub select involving a sharded or non-global table
        return $this->db->arrayWhere("tournaments", $where);
    }

    function _getOneWhere($where = array()){
        //Note that $where can not contain a sub select involving a sharded or non-global table
        $tid = $where['id'];

        //if we have tournament id and no other where conditions are used
        if ($tid && count($where) == 1){
            $tournamentData = $this->getTournamentCache($tid);

            if (!count($tournamentData)){
                $tournamentData = $this->db->loadAssoc('', 'tournaments', $where);
                $this->setTournamentCache($tid, $tournamentData);
            }
        } else {
            $tournamentData = $this->db->loadAssoc('', 'tournaments', $where);
        }

        $c_user = cu() ? cu()->getId() : null;
        if ($c_user && isset($_REQUEST['eid']) && empty($_REQUEST['eid'])) {
            phive('Logger')
                ->getLogger('bos_logs')
                ->error("tournament information when eid is missing", [
                    'request' => $_REQUEST,
                    'user_id' => $c_user,
                    'referrel' => phive('Http/HTTPReq')->getReferrerURL(),
                    'tournament_id' => $where,
                ]);
        }

        return $tournamentData;
    }

    /**
     * Generates a cache key for a tournament based on the brand and tournament ID.
     *
     * @param int $tid The tournament ID.
     * @return string The cache key.
     */
    public static function getTournamentCacheKey(int $tid): string
    {
        return "bos_tournament_" . phive('BrandedConfig')->getBrand() . ":" . $tid;
    }

    private function setTournamentCache($tid, $tournamentData){
        $memKey = self::getTournamentCacheKey($tid);
        $this->mc->setJson($memKey, $tournamentData, 60);
    }

    private function getTournamentCache($tid){
        $memKey = self::getTournamentCacheKey($tid);
        return $this->mc->getJson($memKey);
    }

    public function deleteTournamentCache($tid){
        $memKey = self::getTournamentCacheKey($tid);
        $this->mc->del($memKey);
    }


    function byId($tid, $with_game = false, $with_entry = false){
    $t = is_array($tid) ? $tid : $this->_getOneWhere(array('id' => (int)$tid));
    if($with_game)
      $t['game'] = phive('MicroGames')->getByGameRef($t['game_ref']);
    $u = cuPl();
    if($with_entry && !empty($u))
        $t['entry'] = $this->entryByTidUid($tid, $u->getId());
    return $t;
  }

 /**
  * Get tournament ID by tournament_entries ID.
  * @param $t_entry_id
  * @return false|mixed
  *
  */
  public function getTournamentIdByEntryId($t_entry_id = null)
  {
    $entries = $this->getEntriesByIds($t_entry_id);
    if (isset($entries[$t_entry_id])) {
      return $entries[$t_entry_id]['t_id'];
    }
    return false;
  }

  function displayDate(&$t){
    $zero_date = '0000-00-00 00:00:00';
    if($t['end_time'] != $zero_date)
      $res = $t['end_time'];
    else if($t['start_time'] != $zero_date)
      $res = $t['start_time'];
    else
      $res = $t['mtt_start'];
    return $res == $zero_date ? '' : $res;
  }

  function getByStatus($status){
    return phive("SQL")->arrayWhere("tournaments", array('status' => $status));
  }

  function getCost(&$t){
    return $t['category'] == 'freeroll' ? 0 : $t['cost'];
  }

  function getPotCost(&$t){
    return !empty($t['free_pot_cost']) ? 0 : $t['pot_cost'];
  }

  function curIso(){
    return $this->getSetting('currency');
  }

  function curSym(){
    return phive('Currencer')->getCurSym($this->curIso());
  }

    function getTicket(&$t, $u = '', $amount = ''){
        $amount = empty($amount) ? $this->getBuyin($t, true) : $amount;
        $u = empty($u) ? cuPl() : $u;
        if(empty($u))
            return [];
        $tickets = phive('Trophy')->getAwardOwnershipByType($u->getId(), 'mp-ticket');
        foreach($tickets as $ticket){
            if(!empty($ticket['bonus_id']) && $ticket['bonus_id'] != $t['tpl_id'])
                continue;
            if($ticket['amount'] == $amount)
                return $ticket;
        }
        return array();
    }

  // this function return the total cost of a single registration, do not mistake it with total_cost from the DB (sum of all the possible registrations a use can do with rebuy)
  function getBuyin(&$t, $only_amount = false, $cu_currency = false){
    if($t['category'] == 'freeroll')
      $amount = 0;
    else
      $amount = $t['cost'] + $this->getPotCost($t) + $t['house_fee'];

    if($only_amount)
      return $amount;

    //$cost = $chg ? chg(ciso(), 'EUR', $t['cost']) : $t['cost'];
    //$pot_cost = $chg ? chg(ciso(), 'EUR', $t['pot_cost']) : $t['pot_cost'];
    //return 'EUR '.nfCents($cost, true).' + '.(($pot_cost / $cost) * 100).'%';
    if (!empty($cu_currency)) {
        return $this->fullFmSym($cu_currency, $amount);
    } else {
        return $this->fmSym($amount);
    }
  }

  function getRowColor(&$t){
    $map   = array('freeroll' => 'freeroll-color', 'jackpot' => 'mp-jackpot-color', 'added' => 'added-color');
    $brandConfig = phive('BrandedConfig');
    $color = $map[$t['category']];
    if(empty($color)){
      if(!empty($t['guaranteed_prize_amount']))
        $color = 'guaranteed-color';
    }

    if(in_array($brandConfig->getBrand(), [$brandConfig::BRAND_VIDEOSLOTS, $brandConfig::BRAND_MRVEGAS]) &&
        $t['category'] == 'normal' &&
        $t['award_ladder_tag'] == 'payout' &&
        is_null($color)
    ) {
        $color = 'normal-payout-color';
    }

    return $color;
  }

  function getPotPercent($t, $field = 'pot_cost'){
    if($t['category'] == 'freeroll' || !empty($t['free_pot_cost']))
      $amount = 0;
    else
      $amount = $t['cost'] + $t[$field];
    return round(($t[$field] / $amount) * 100).'%';
  }

  function hasStarted($t){
    if($t['start_time'] == '0000-00-00 00:00:00')
      return false;
    return strtotime($t['start_time']) <= time();
  }

  function getStartStamp($t){
    return strtotime($this->hasStarted($t) ? $t['start_time'] : $t['mtt_start']);
  }

    function getInactiveStatuses($as_str = true){
        $arr = ['finished', 'cancelled'];
        if($as_str)
            return phive('SQL')->makeIn($arr);
        return $arr;
    }

    /**
     * Build the SQL query for the tournament list
     *
     * @param string $str_search
     * @param string $where_extra
     * @param int $limit
     * @param int $start
     * @return string
     */
    function _getSqlForListing($args, $where_extra = '', $limit = 500, $start = 0, $get_count = false)
    {
        if(empty($where_extra))
            $where_extra = "t.status NOT IN({$this->getInactiveStatuses(true)})";

        if(!empty($args['str_search'])){
            $str_search = phive('SQL')->escape("%{$args['str_search']}%");
            $str_search = " AND (g.game_name LIKE $str_search OR t.tournament_name LIKE $str_search)";
        }

        $select_province = licSetting('require_main_province') ? 'g.blocked_provinces,' : "";
		$select = "t.*, t.blocked_provinces as 'tournament_blocked_provinces',  g.game_name, g.blocked_countries, $select_province g.game_id";
        $sql_limit  = "LIMIT $start, $limit";
        if($get_count) {
            $sql_limit = '';
        }

        if($args['status'] == 'finished' && $get_count) {
            $sql_limit  = "LIMIT 1000";
        }

        $join = "JOIN micro_games AS g ON t.game_ref = g.ext_game_name";
        $uid = cuPlId();
        if(!empty($uid) && ($args['start_format'] == 'mymps' || !empty($args['with_entries']))) {
            $select    = 't.*,
                g.game_name,
                te.id as entry_id,
                te.result_place,
                te.status AS e_status,
                te.win_amount,
                te.joker,
                te.bounty,
                te.spins_left,
                te.rebuy_times as entry_rebuy_times,
                te.dname';

            if($args['start_format'] == 'mymps') {
                $join      = "INNER JOIN tournament_entries AS te ON te.t_id = t.id AND te.user_id = {$uid}
                              INNER JOIN micro_games AS g ON t.game_ref = g.ext_game_name";

            } elseif(!empty($args['with_entries'])) {
                $join      = "LEFT JOIN tournament_entries AS te ON te.t_id = t.id AND te.user_id = {$uid}
                          INNER JOIN micro_games AS g ON t.game_ref = g.ext_game_name";
            }
        }

        // replaced by $this->filterOutBasedOnCountry() after getting the list.
//        $where_extra .= $this->getWhereExtraForCountries();

        $sql = "SELECT $select FROM tournaments t
          $join
          WHERE $where_extra $str_search GROUP BY t.id ORDER BY t.id DESC $sql_limit";

        return $sql;
    }

    /**
     *
     * @param array $args
     * @return string
     */
    function _getWhereClauseForTournamentListing($args)
    {
        $where_extra = '';

        // Filter on status
        $req_status     = !empty($args['str_search']) ? 'all' : $args['status'];            // TODO: This will be confusing for players using the new filter on mobile.
        $minor_statuses = phive('SQL')->makeIn($this->getMinorStatuses($req_status));
        if(!empty($minor_statuses)) {
            $where_extra          = "t.status IN($minor_statuses)";
        }

        // Filter on category
        if(!empty($args['category']) && $args['category'] != 'all') {
            if($args['category'] == 'guaranteed') {  // temporary hack needed bc 'guaranteed' battles have category 'normal'
                $where_extra .= " AND t.guaranteed_prize_amount != 0";
            } else {
                $category = phive('SQL')->escape($args['category']);
                $where_extra .= " AND t.category = {$category}";
            }
        }

        // Filter on desktop_or_mobile
        if(!empty($args['desktop_or_mobile'])) {
            $desktop_or_mobile = phive('SQL')->escape($args['desktop_or_mobile']);
            $where_extra .= " AND t.desktop_or_mobile IN ({$desktop_or_mobile}, 'both')";
        }

        // Filter on supplied id's
        if(!empty($args['ids'])) {
            $ids = implode(',', $args['ids']);
            $where_extra .= " AND t.id IN ({$ids})";
        }

        // Filter on start_format
        if(!empty($args['start_format']) && !in_array($args['start_format'], ['all', 'mymps'])) {
            $start_format = phive('SQL')->escape($args['start_format']);
            $where_extra .= " AND t.start_format = {$start_format}";
        }

        // Trim AND from beginning
        $where_extra = ltrim($where_extra, ' AND ');

        return $where_extra;
    }

	// Not used, remove?
    function getWhereExtraForCountries()
    {
        $u       = cuPl();
        $country = empty($u) ? '' : $u->getCountry();
        $where_extra = '';
        if(empty($country)) {
            $where_extra .= " AND t.included_countries = '' ";
        } else {
            $where_extra .= "
                AND CASE WHEN t.included_countries != ''
                         THEN t.included_countries LIKE '%$country%'
		         ELSE t.excluded_countries NOT LIKE '%$country%'
		    END ";

//            $where_extra .= "
//                AND CASE WHEN g.included_countries != ''
//                         THEN g.included_countries LIKE '%$country%'
//		         ELSE g.blocked_countries NOT LIKE '%$country%'
//		    END ";
        }

        // old code was indeed checking the microgame table, but since that got changed it might have broken something else.
//        if(empty($country)) {
//            $where_extra .= " AND (t.included_countries = '' OR g.included_countries = '' ) ";
//        } else {
//            $where_extra .= " AND (t.excluded_countries NOT LIKE '%$country%' OR t.included_countries LIKE '%$country%') "
//                          . "AND (g.blocked_countries NOT LIKE '%$country%' OR g.included_countries LIKE '%$country%') ";
//        }

        return $where_extra;
    }

    /**
     * TODO this is not returning the correct position from tournament_entries, but it affects only newsite in some rare edge cases, so for now we keep it like this.
     * see CH16198 for more info about this.
     *
     * @param array $args
     * @param bool $for_display
     * @param int $limit
     * @param int $start
     * @return array
     */
    public function getListingByStatus($args, $for_display, $limit = 1000, $start = 0, $entry_fields = [])
    {
        if(!empty($args['start_format']) && $args['start_format'] == 'mymps') {
            $uid = cuPlId();
            if (empty($uid)) {
                return [];
            }
            $sql = $this->getAllByUserSQL($uid, $args, $entry_fields);

            $ts = $this->db->sh($uid)->loadArray($sql);

        } else {
            $where_extra = $this->_getWhereClauseForTournamentListing($args);

            $sql = $this->_getSqlForListing($args, $where_extra, $limit, $start);

            $ts = $this->_loadTournaments($sql);

            $ts = $this->_sortListing($ts, $args['status']);
        }

        if(!empty($args['sort_by'])) {
            $ts = phive()->sort2d($ts, $args['sort_by'], $args['sort_direction']);
        }

        // we need to filter before pagination.
        $ts = $this->filterOutBasedOnCountry($ts);
        $countBeforePagination = count($ts);

        $ts = $this->_paginateListing($ts, $args);

        if($for_display) {
            foreach ($ts as &$t) {
                $this->_addTournamentDisplayInfo($t);
            }
        }

        return ['tournaments' => $ts, 'number_of_tournaments' => $countBeforePagination];
    }


    function filterOutBasedOnCountry($tournaments)
    {
        $u       = cuPl();

        $iso_overwrite = phive('Localizer')->getDomainSetting('domain_iso_overwrite');

        if($iso_overwrite){
          [$countryIso] = explode("-", $iso_overwrite);
          $country = $countryIso;
        } else {
          $country = cuCountry('', false);
        }

        $province = empty($u) ? '' : $u->getProvince();

        $actions = array('blocked_countries', 'excluded_countries');
		if(licSetting('require_main_province')) array_push($actions, 'blocked_provinces', 'tournament_blocked_provinces');

        $res = array_filter($tournaments, function($el) use ($country, $province, $actions){
            foreach($actions as $key){
                if(empty($el[$key]))
                    continue;
                if(
					(
						($key === 'blocked_provinces' || $key === 'tournament_blocked_provinces') && in_array($province, explode(' ', $el[$key]))
					)
                    || in_array($country, explode(' ', $el[$key]))
                ) {
					return false;
				}
            }

            if(empty($el['included_countries']))
              return true;

            if(!in_array($country, explode(' ', $el['included_countries'])))
              return false;

            return true;
        });

        return $res;
    }

    /**
     * Sort the tournament list
     *
     * @param array $tournaments - Tournaments to be sorted
     * @param string|null $status - Current filter requested, needed to handle "finished" scenario with different sorting
     * @return array
     */
    private function _sortListing($tournaments, $status = null)
    {
        // In case of "finished" tournaments we want to display them in DESC order, no sorting based on type will be applied.
        if($status === 'finished') {
            return phive()->sort2d($tournaments, 'end_time', 'desc');
        }

        //1 SNG with registered_players, sort by amount of registered players
        //2 MTT sorted by mtt start
        //3 SNG with zero registered players
        //4 Full SNGs
        $arr1 = phive()->sort2d(array_filter($tournaments, function($el){
          return !empty($el['registered_players']) && strtolower($el['start_format']) == 'sng' && $el['registered_players'] < $el['max_players'];
        }), 'registered_players', 'desc');

        $arr2 = phive()->sort2d(array_filter($tournaments, function($el){
          return strtolower($el['start_format']) == 'mtt';
        }), 'mtt_start', 'asc');

        $arr3 = array_filter($tournaments, function($el){
          return strtolower($el['start_format']) == 'sng' && empty($el['registered_players']);
        });

        $arr4 = array_filter($tournaments, function($el){
            return !empty($el['registered_players']) && strtolower($el['start_format']) == 'sng' && $el['registered_players'] == $el['max_players'];
        });

        return array_merge($arr1, $arr2, $arr3, $arr4);
    }

    /**
     * Paginate the tournament list
     *
     * Since the tournaments are sorted by _sortListing(),
     * we cannot use SQL based pagination and keep the same order of the tournaments.
     *
     * @param array $tournaments
     * @param array $args
     * @return array
     */
    function _paginateListing($tournaments, $args)
    {
        return array_slice($tournaments, $args['page_start'], $args['page_limit']);
    }

    /**
     * Load tournaments from the database
     *
     * @param string $sql
     * @return array
     */
    function _loadTournaments($sql)
    {
        $tournaments = phive("SQL")->loadArray($sql);

        return $tournaments;
    }

    /**
     *
     * @param array $t A tournament
     * @return void
     */
    function _addTournamentDisplayInfo(&$t)
    {
        $t['pretty_buy_in']   = $this->getBuyin($t);
        $t['status_alias']    = 'mp.'.$t['status'];
        $t['reg_number']      = $this->displayRegs($t);

        $actionButtonvariables = $this->getActionButtonVariables($t);

        // This map holds the localized_string as the key,
        // and the value is an array where the first element is the action,
        // and the second optional element will be the replacers to use in the translation.
        $map = [
            'mp.rebuy'                 => ['rebuy_start'],
            'mp.resume'                => ['go_to_the_game'],
            'mp.unregister'            => ['unregister_for_tournament'],
            'mp.register'              => ['register_for_tournament'],
            'mp.upcoming.cdown'        => ['registration_opens_soon'],
            'mp.registration.closed'   => ['registration_closed'],
            'mp.finished'              => ['finished'],
            'mp.cancelled'             => ['cancelled'],
            'register'                 => ['login_or_register'],
            'mp.unqueue'               => ['unqueue']
        ];

        $t['action_button_action'] = $map[$actionButtonvariables['loc_alias']][0];
        $t['action_button_alias'] = $actionButtonvariables['loc_alias'];
    }


    /**
     * Return sorted and filtered listing
     *
     * @param array $args
     * @param string $where_extra
     * @return array
     */
    private function _getListing(array $args, $where_extra = '')
    {
        $sql = $this->_getSqlForListing($args, $where_extra);

        $res = $this->_loadTournaments($sql);

        $res = $this->filterOutBasedOnCountry($res);

        return $this->_sortListing($res, $args['status']);
    }

    /**
     * Return upcoming statuses
     * - upcoming: tournament is present in the list but registration still closed
     * - registration.open: tournament will start soon, but we allow player to register before.
     *
     * @return string[]
     */
    private function upcomingStatuses()
    {
        return array('upcoming', 'registration.open');
    }

    /**
     * Wrapper to simplify getting back all statuses associated to a status.
     * Ex. 'not-finished' will return all status except "finished,cancelled"
     *
     * @param string $major_status
     * @param bool $add_finished
     * @return array|string[]
     */
    public function getMinorStatuses($major_status, $add_finished = false)
    {
        if (empty($major_status)) {
            return [];
        }

        switch ($major_status) {
            case 'in.progress':
            case 'in-progress':
                $statuses = $this->begunStatuses(); // match on both in.progress' and 'in-progress'
                break;
            case 'upcoming':
                $statuses = $this->upcomingStatuses();
                break;
            case 'all':
                $begun = $this->begunStatuses();
                $upcoming = $this->upcomingStatuses();
                $finished = $add_finished ? ['finished'] : [];
                $statuses = array_merge($upcoming, $begun, $finished);
                break;
            case 'not-finished':
                $statuses = array_merge($this->begunStatuses(), $this->upcomingStatuses());
                break;
            default:
                $statuses = [$major_status];
                break;
        }

        return $statuses;
    }

    /**
     * Get tournaments to be displayed
     *
     * @param array $args
     * @param string $where_extra
     * @return array
     */
    public function getListing(array $args, $where_extra = "t.status NOT IN('finished', 'cancelled')")
    {
        return $this->_getListing($args, $where_extra);
    }

    /**
     * Return filtering value to apply on array_filter
     * No filter will be applied in these 3 cases:
     * - showing users battle "mymps"
     * - field value is "all"
     * - field value is empty
     *
     * @param string $field
     * @param array $args
     * @return false|mixed
     */
    private function doFilter($field, $args = []){
        if(in_array($args[$field], array('mymps')))
            return false;
        if($args[$field] == 'all' || empty($args[$field]))
            return false;
        return $args[$field];
    }

    function getPrizeListForRender($tid, $count = 0, $prize_list = [], $prize_pool = 0){
        $mp         = is_array($tid) ? $tid : $this->byId($tid);
        $prize_pool = empty($prize_pool) ? $this->totalPrizeAmount($mp, false) : $prize_pool;
        if(empty($prize_list)){
            $prize_list = $this->getPrizeList($mp, $count);
            $prize_list = empty($count) ? $prize_list : array_slice($prize_list, 0, $count);
        }

        $isUpcomingAwardPrize = $this->upcomingAwardPrize($mp);
        $prizeListCount = ($isUpcomingAwardPrize) ? max(array_column($prize_list, 'end_spot')) : count($prize_list);
        $lang = phive('Localizer')->getLanguage() ?? 'en';
        $positions = $this->translateOrdinalNumbers(range(1, $prizeListCount), $lang);

        $cu_currency = cuPlAttr('currency');
        $rarr = [];
        foreach($prize_list as $place){
            if(empty($place['percentage']) && empty($mp['award_ladder_tag']) && count($prize_list) > 5)
                continue;
            if($isUpcomingAwardPrize){
                $position = $positions[$place['start_spot']];
                if ($place['start_spot'] != $place['end_spot']){
                    $position .= ' - '.  $positions[$place['end_spot']];
                }

                $rarr[] = [
                    'place'  => $position,
                    'descr'  => $place['award']['description'] ?? $place['description'],
                    'status' => 'upcoming'
                ];
            }else{
                $tmp = ['place' => $positions[$place['spot']], 'status' => 'current'];
                if(empty($mp['award_ladder_tag'])){
                    $prize_amount = $prize_pool * $place['percentage'];
                    $tmp['award']           = "{$this->cSym()} ".nfCents($prize_amount, true);
                    if (!empty($cu_currency) && $cu_currency != $this->getSetting('currency')) {
                        $tmp['award'] .= ' (' . efEuro(chg($this->tCur(), $cu_currency, $prize_amount), true) . ')';
                    }
                    $tmp['pool_percentage'] = ($place['percentage'] * 100).'%';
                    $tmp['descr']           = '';
                }else{
                    $tmp['descr']           = $place['description'];
                }
                $rarr[] = $tmp;
            }
        }
        return $rarr;
    }

    /**
     * Returns the price list as an array.
     *
     * Two versions can be returned:
     * - short version, where the places are in ranges: "74th - 119th"
     * - long version
     *
     * By default, the short version will be returned for
     * upcoming tournaments where the prizes are trophy awards.
     *
     * @param int $tid
     * @param int $count
     * @param array $prize_list
     * @param int $prize_pool
     * @param boolean $return_short_version
     * @return array
     */
    function getPrizeListForRenderMobile($tid, $count = 0, $prize_list = [], $prize_pool = 0, $return_short_version = false){
        $tournament         = is_array($tid) ? $tid : $this->byId($tid);
        $prize_pool = empty($prize_pool) ? $this->totalPrizeAmount($tournament, false) : $prize_pool;

        if(empty($prize_list)){
            $prize_list = $this->getPrizeList($tournament, $count, $return_short_version);
            $prize_list = empty($count) ? $prize_list : array_slice($prize_list, 0, $count);
        }

        // TODO: return optimized long version.
        $isUpcomingAwardPrize = $this->upcomingAwardPrize($tournament);
        $prizeListCount = ($isUpcomingAwardPrize) ? max(array_column($prize_list, 'end_spot')) : count($prize_list);
        $lang = phive('Localizer')->getLanguage() ?? 'en';
        $positions = $this->translateOrdinalNumbers(range(1, $prizeListCount), $lang);

        $rarr = [];
        foreach($prize_list as $place){
            if(empty($place['percentage']) && !$this->isAwardPrize($tournament) && count($prize_list) > 5)
                continue;
            if($isUpcomingAwardPrize || ($this->isAwardPrize($tournament) && $return_short_version)){

                $position = $positions[$place['start_spot']];
                if ($place['start_spot'] != $place['end_spot']){
                    $position .= ' - '.  $positions[$place['end_spot']];
                }

                $rarr[] = [
                    'place'  => $position,
                    'descr'  => $place['award']['description'] ?? $place['description'],
                    'status' => 'upcoming'
                ];
            }else{
                $tmp = ['place' => $positions[$place['spot']], 'status' => 'current'];
                if(!$this->isAwardPrize($tournament)){
                    $tmp['award']           = "{$this->cSym()} ".nfCents($prize_pool * $place['percentage'], true);
                    $tmp['pool_percentage'] = ($place['percentage'] * 100).'%';
                    $tmp['descr']           = '';
                }else{
                    $description = !empty($place['award']['description']) ? $place['award']['description'] : $place['description'];
                    $tmp['descr']           = $description;
                }
                $rarr[] = $tmp;
            }
        }
        return $rarr;
    }

    /**
     * This is used by the desktop/legacy version of the Battle of Slots
     *
     * @param array $tournaments
     * @param array $args
     * @param bool $for_display
     * @return array
     */
    public function getListingAdvanced($tournaments = [], $args = [], $for_display = false){

        if(empty($tournaments)){
            $req_status = !empty($args['str_search']) ? 'all' : $args['status'];
            $minor_statuses = phive('SQL')->makeIn($this->getMinorStatuses($req_status));
            if(!empty($minor_statuses)) {
                $where = "t.status IN($minor_statuses)";
            }
            $tournaments = $this->getListing($args, $where);
        }

        if($format = $this->doFilter('start_format', $args)){
            $tournaments = array_filter($tournaments, function($t) use ($format){
                return $t['start_format'] == $format;
            });
        }

        if($cat = $this->doFilter('category', $args)){
            $tournaments = array_filter($tournaments, function($t) use ($cat){
                if($cat == 'guaranteed' && !empty($t['guaranteed_prize_amount']))
                    return true;
                if($cat == 'xspin' && $t['play_format'] == 'xspin')
                    return true;
                return $t['category'] == $cat;
            });
        }

        $tournaments = $this->filterOutBasedOnCountry($tournaments);

        if(!empty($args['sort_by']))
            $tournaments = phive()->sort2d($tournaments, $args['sort_by']);

        if($for_display){
            foreach ($tournaments as &$t) {
                $this->_addTournamentDisplayInfo($t);
            }
        }

        return $tournaments;
    }

    // TODO is this used?
    function getByUser($uid, $tid = '', $gref = '', $where = "t.status IN('in.progress', 'late.registration')", $where_extra = ""){
        if(!empty($where))
            $where = " AND $where ";
        if(!empty($gref))
            $where .= " AND t.game_ref = '$gref' ";
        if(!empty($tid))
            $where .= " AND te.t_id = $tid ";
        $where .= $where_extra;
        return phive("SQL")->sh($uid, '', 'tournament_entries')->loadArray("
            SELECT te.*, t.status, t.pot_cost, t.house_fee, t.play_format FROM tournament_entries te
            LEFT JOIN tournaments AS t ON t.id = te.t_id
            WHERE te.user_id = $uid
            $where");
    }

    function allByUser($uid){
        if (empty($uid)) {
            return [];
        }
        $sql = $this->getAllByUserSQL($uid);
        return $this->db->sh($uid, '', 'tournament_entries')->loadArray($sql);
    }

  function getAllByUserSQL($uid, $args = [], $entry_fields = [])
  {
      if(empty($entry_fields)) {
          $entry_fields_as_string = ',
                te.id as entry_id,
                te.result_place,
                te.status AS e_status,
                te.win_amount,
                te.joker,
                te.bounty,
                te.spins_left,
                te.rebuy_times as entry_rebuy_times,
                te.dname';
      } else {
          // Always return the id, bc we use that in the GraphQL to determine if the entry was returned or not
          $entry_fields_as_string = ', te.id as entry_id';
          $columns = phive('SQL')->getColumns('tournament_entries');

          foreach ($entry_fields as $entry_field => $value) {
              if(array_key_exists($entry_field, $columns) && $entry_field !== 'id') {
                  $entry_fields_as_string .= ", te.{$entry_field} AS entry_{$entry_field}";
              }

          }
          $entry_fields_as_string = rtrim($entry_fields_as_string, ',');
      }

      $sql = "SELECT t.*,
                g.game_name,
                g.game_id
                {$entry_fields_as_string}
            FROM tournaments t
            INNER JOIN tournament_entries AS te ON te.t_id = t.id AND te.user_id = $uid
            INNER JOIN micro_games AS g ON t.game_ref = g.ext_game_name ";

      $where = '';

      if(!empty($args['category']) && $args['category'] != 'all') {
          $category = phive('SQL')->escape("{$args['category']}");
          $where .= "t.category = {$category} ";
      }

      if(!empty($args['status']) && $args['status'] != 'all') {
          $minor_statuses = phive('SQL')->makeIn($this->getMinorStatuses($args['status']));
          if(!empty($minor_statuses)) {
              if(!empty($where)) {
                  $where .= ' AND ';
              }
              $where .= "t.status IN($minor_statuses)";
          }
      }

      if(!empty($args['str_search'])) {
          $str_search = phive('SQL')->escape("%{$args['str_search']}%");
          if(!empty($where)) {
              $where .= ' AND ';
          }
          $where .= "(g.game_name LIKE $str_search OR t.tournament_name LIKE $str_search)";
      }

      if(!empty($where)) {
          $sql .= "WHERE {$where}";
      }

      $sql .= " GROUP BY t.id ORDER BY t.id DESC
            LIMIT 0,30";

      return $sql;
  }

  //no rc - both
  function getTimeLeft(&$t){
    if(!phive()->isEmpty($t['start_time']) && !$this->isClosed($t)){
      $end_time = phive()->hisMod("+{$t['duration_minutes']} min", $t['start_time']);
      $arr      = phive()->timeIntervalArr('', phive()->hisNow(), $end_time);
      return tAssoc('mp.time.left', $arr);
    }
    return false;
  }

    /**
     * Returns an array of dates for when the tournament need to be created based on template
     * - recur tournament: 1/N dates (day,week,month)
     * - non recur: 1 date
     *
     * @param $tpl
     * @param string $start_stamp
     * @param string $end_stamp
     * @return array
     */
    function getMttSchedule($tpl, $start_stamp = '', $end_stamp = '')
    {
        $fc = new FormerCommon();
        if ($tpl['start_format'] != 'mtt')
            return array();

        $start_stamp = empty($start_stamp) ? strtotime($tpl['created_at']) : (is_numeric($start_stamp) ? $start_stamp : strtotime($start_stamp));
        $end_stamp = empty($end_stamp) ? strtotime($tpl['recur_end_date']) : (is_numeric($end_stamp) ? $end_stamp : strtotime($end_stamp));

        if (!empty($tpl['recur'])) {
            $res = array();

			$dt = new DateTime('December 29th');
			$syear = date('Y', $end_stamp);
			if (date('W') == $dt->format('W')) {
				$syear--;
			}

            $eyear = date('Y', $end_stamp);
            $days = explode(',', $tpl['mtt_recur_days']);

            foreach (range($syear, $eyear) as $year) {
                switch ($tpl['mtt_recur_type']) {
                    case 'week':
                        foreach ($fc->getWeeks() as $week) {
                            foreach ($days as $day) {
                                $cur_date = $year . 'W' . $week . $day . ' ' . $tpl['mtt_start_time'];
                                $stamp = strtotime($cur_date);
                                if ($stamp >= $start_stamp && $stamp <= $end_stamp) {
                                    $res[] = date('Y-m-d', $stamp) . " " . $tpl['mtt_start_time'];
                                }
                            }
                        }
                        break;
                    case 'day':
                        foreach ($fc->getDaysInYear($year) as $day) {
                            $day = str_pad($day, 3, '0', STR_PAD_LEFT);
                            $hours = explode(',', $tpl['mtt_start_time']);
                            foreach ($hours as $hour) {
                                $cur_date = date("Y-m-d", strtotime("$year.$day")) . " $hour:00:00";
                                $stamp = strtotime($cur_date);
                                if ($stamp >= $start_stamp && $stamp <= $end_stamp) {
                                    $res[] = $cur_date;
                                }
                            }
                        }
                        break;
                    case 'month':
                        foreach ($fc->getMonths() as $month) {
                            foreach ($days as $day) {
                                $day = $fc->pad($day);
                                $cur_date = "$year-$month-$day " . date('H:i:s');
                                $stamp = strtotime($cur_date);
                                if ($stamp >= $start_stamp && $stamp <= $end_stamp) {
                                    $res[] = date('Y-m-d', $stamp) . " " . $tpl['mtt_start_time'];
                                }
                            }
                        }
                        break;
                }
            }

            return $res;
        } else {
            $stamp = strtotime($tpl['mtt_start_date'] . " " . $tpl['mtt_start_time']);
            if ($stamp >= $start_stamp && $stamp <= $end_stamp) {
                return array($tpl['mtt_start_date'] . " " . $tpl['mtt_start_time']);
            }
            return array();
        }
    }

  function save($t){
      //return phive("SQL")->insertOrUpdate('tournaments', $t, $t);
      return phive("SQL")->save('tournaments', $t);
  }

  function getXspinInfo($t, $key = ''){
    if(is_numeric($t))
      $t = $this->byId($t);
    //list($gids, $info, $tot_spins) = explode(':', $t['xspin_info']);
    //$res = array('gids' => $gids, 'info' => $info, 'tot_spins' => $tot_spins);
    $res = array('tot_spins' => $t['xspin_info'] * $t['spin_m']);
    return empty($key) ? $res : $res[$key];
  }

  //no rc - both
  function trDescr(&$t){
    return is_array($t) ? "tid-".$t['id'] : "tid-".$t;
  }

  //no rc - both
  function log($tag, $data = ''){
    if($this->getSetting('logging') === true)
      phive()->dumpTbl("mp-$tag", $data);
  }

  function wsTopBalance(&$u, $mp_balance = ''){

      $userData = $u->getData();
      $userData['cash_balance'] = $u->getAttr('cash_balance', true);

    $mp_balance = empty($mp_balance) ? $this->prFullUserBalance($u->data) : $mp_balance;
    // mp_balance used on old desktop site (already formatted value), bos_balance used on the new site returning only the amount
    toWs([
        'mp_balance' => $mp_balance,
        'bos_balance'=> $this->getUserBalance($userData),
        'cash' => $u->getAttr('cash_balance', true),
        //'vault_balance' => phive('DBUserHandler/Booster')->getVaultBalance()
    ], 'balance', $u->getId());
  }


    public function regQueueConsumer($tpl_id, $str, $uid = '', $args = []){
        $tpl_id = (int)$tpl_id;

        $tpl = $this->getTplById($tpl_id);
        if(!$this->isSngTemplateOn($tpl)){
            return false;
        }

        // If the user got bored and logged out before getting to the front of the queue we abort.
        if(!empty($uid) && !isLogged($uid)){
            return false;
        }

        // If the user has unqueued we abort.
        if(empty(phMgetShard('tplqueue'.$tpl_id, $uid))){
            return false;
        }

        // We try for 100 seconds.
        $sql_str = "SELECT * FROM tournaments WHERE status = 'registration.open' AND tpl_id = $tpl_id AND registered_players < max_players ORDER BY id DESC LIMIT 1";

        foreach(range(0, 600) as $try_num){
            $t = $this->db->loadAssoc($sql_str);
            if(empty($t)){
                // Whe check if the template is off, if it is we exit.
                $tpl = $this->getTplById($tpl_id);
                if(!$this->isSngTemplateOn($tpl)){
                    return false;
                }
                // If nothing available yet we sleep for a second before the next try.
                sleep(1);
            } else {
                // We remove the Redis "flag" and decrease the queue count.
                phMdelShard('tplqueue'.$tpl_id, $uid);
                $this->mc->decr('tplqueue'.$tpl_id, $this->getSetting('q-timeout'));

                // We tell a WS channel to decrease the queue number, all players in this template channel get the message.
                toWs(['msg' => 'dec_q_cnt'], 'bosqcnt'.$tpl_id, 'na');
                $res = $this->regRebuyCommon('register', $t['id'], $str, $uid, $args);
                /*
                $to_print = [
                    'uid' => $uid,
                    'ws' => [['msg' => 'dec', 'bos_tpl_id' => $tpl_id], 'bosqcnt'.$tpl_id],
                    'reg' => ['register', $t['id'], $str, $uid, $args],
                    'res' => $res
                ];
                phive()->dumpTbl('reg-queue', $res, $uid);
                //*/

                return true;
            }
            pcntl_signal_dispatch();
        }

        return false;
    }

    function queueReg($t_id, $u_obj, $args){
        $u_obj = cu($u_obj);
        $t   = is_array($t_id) ? $t_id : $this->byId($t_id);
        $tpl = $this->getParent($t);

        $can_queue = $this->canQueue($t, $u_obj, null, $tpl, $args);
        if (is_string($can_queue)) {
            return dclickEnd('mp_rebuy_ok', [
                'status' => $can_queue,
                'msg' => 'mp.registration.closed'
            ]);
        }

        // We make a note in Redis that this user is in the queue for the BoS template in question.
        phMsetShard('tplqueue'.$tpl['id'], $t_id, $u_obj, $this->getSetting('q-timeout'));

        $q_cnt = phive('Site/Publisher')->singleNoLB($tpl['queue'], 'Tournament', 'regQueueConsumer', [$tpl['id'], 'reg', $u_obj->getId(), $args]);
        // list($q_instance, $q_cnt) = phive('Site/Publisher')->singleLoadBalanced($tpl['id'], 'bos', 'Tournament', 'regQueueConsumer', [$tpl['id'], 'reg', $u_obj->getId(), $args], true, false, true);
        //$q_cnt = 10;

        $this->mc->set('tplqueue'.$tpl['id'], $q_cnt, $this->getSetting('q-timeout'));

        return dclickEnd('mp_rebuy_ok', [
            'status'     => 'queued',
            'q_cnt'      => max($q_cnt, 2),
            'ws_cnt_url' => phive('UserHandler')->wsUrl('bosqcnt'.$tpl['id'], false)
        ]);
    }

    public function isSngTemplateOn($tpl){
        if($tpl['start_format'] != 'sng'){
            return false;
        }

        // If the template is off due to recur 0 or recur end date having passed we stop.
        if(empty($tpl['recur']) || $this->hasExpired($tpl)){
            return false;
        }

        return true;
    }

    public function purgeTemplateQueue($tpl){
        if(empty($tpl['queue'])){
            return false;
        }

        return phive('Site/Publisher')->purge($tpl['queue']);
    }

    function regRebuyCommon($func, $t_e_id, $str, $uid = '', $args = []){
        dclickStart('mp_rebuy_ok');
        $t   = [];
        $res = null;
        $u   = empty($uid) ? cuPl() : cu($uid);

        // We only queue registrations and only in a context where the logic is not executed in a queue.
        if($func == 'register' && !phive()->isQueued()){
            $t   = $this->byId($t_e_id);
            $tpl = $this->getParent($t);

            // We first check if the SNG template is on and if it is we check if queued and return the result.
            if($this->isSngTemplateOn($tpl) && $this->isQueued($tpl, $t)){
                // Queueing is on  so we check if we can queue, this is the same like can canRegister only we
                // don't check if it is full or open.
                $can_queue = $this->canQueue($t, $u, null, $tpl, $args);
                if(is_string($can_queue)) {
                    $res = $can_queue;
                } else {
                    //return $this->queueReg($t, $u, $args);

                    return dclickEnd('mp_rebuy_ok', [
                        't_id'   => $t['id'],
                        'status' => 'queue_yes_no'
                    ]);
                }
            }

            if($res === null){
                $can_reg = $this->canRegister($t, $u, null, $tpl, $args);
                if(is_string($can_reg)){
                    $res = $can_reg;
                }
            }
        }
        if(empty($u)){
            return dclickEnd('mp_rebuy_ok', ['status' => 'nok', 'msg' => 'no session']);
        }

        if (!empty($u->getSetting('privacy-pinfo-hidealias'))) {
            $u->data['alias'] = 'Anonymous' . base_convert($u->data['id'], 10, 9);
        }

        // If we have already determined that the user can not register we don't try again.
        $res = $res ?? $this->$func($u, $t_e_id, $args);

        if(empty($t)){
            $t = $func == 'register' ? $this->byId($t_e_id) : $this->getByEid($t_e_id);
        }

        $e   = $this->entryByTidUid($t['id'], $u->getId());
        if($func == 'register'){
            if(!is_string($res) && $e !== null && $t['start_format'] !== 'sng') {
                uEvent('mpbuyin', '', $t['tournament_name'], $t['id'], $u->data);
                if($this->isRegLate($t)) {
                    $this->startWs($t, $e);
                }
            }

        }
        $msg    = is_string($res) ? "mp{$str}.error.$res" : "mp{$str}.ok";
        $status = is_string($res) ? $res : 'ok';

        if($msg == 'mpreg.error.closed') {
            phive('Logger')->getLogger('bos_logs')->info('tournament_closed', $t);
        }

        if(phive()->isQueued()){
            // If we're queued we need to translate before we send out via the websocket.
            $msg = t2($msg, $t, $u);
            // In a queued context we need to explicitly set the user currency.
            setCur($u->getCurrency());
        }

        $mp_balance = $this->prFullUserBalance($u->data);
        if($status == 'no-cash')
            $balance = $mp_balance;

        phive('UserHandler')->logAction($u, "Action: $func, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'battle');
        $this->log('regrebuy');
        $this->mc->del('tournament'.$t['id']);
        $this->wsTopBalance($u, $mp_balance);
        $this->initMem($t);
        $content_arr = ['msg' => $msg, 'status' => $status, 'balance' => nfCents($balance, true), 'entry_balance' => $e['cash_balance']];
        phive()->isQueued() ? toWs($content_arr, 'bosqreg', $u->getId()) : dclickEnd('mp_rebuy_ok');
        return $content_arr;
    }

    function prFullUserBalance($u, $cu_currency_first = false)
    {
        if ($cu_currency_first === true) {
            return $this->fmSym($this->getUserBalance($u)) . ' ' . ($this->getSetting('currency') == $u['currency'] ? '' : '(' . efEuro($u['cash_balance'], true) . ')');
        } else {
            return efEuro($u['cash_balance'], true) . ' ' . ($this->getSetting('currency') == $u['currency'] ? '' : '(' . $this->fmSym($this->getUserBalance($u)) . ')');
        }
    }

  function isRegLate(&$t){
    return $t['status'] === 'late.registration';
  }

  function isRegOpen(&$t){
    return $t['status'] === 'registration.open';
  }

  // TODO this is a duplicate of inProgress.
  function isPlaying(&$t){
    return ($this->isRegLate($t) || $t['status'] == 'in.progress');
  }

    function wsSystemMsg($u, &$t, $alias){
        if(!is_object($u))
            return false;
        $msg = array('firstname' => tAll('mp.system'), 'msg' => tAll($alias, array($u->getAlias(true))), 'hi' => date('H:i'), 'tid' => $t['id'], 'wstag' => 'smsg');
        // commented as we dont want system message in the chatbox
        //$this->addToChatContents($t, $msg);
        $this->log('systemmsg');
        toWs($msg, 'mp'.$t['id'], 'na');
        toWs($msg, 'lobbychat'.$t['id'], 'na');
    }

    function isPrizeTicket($ticket){
        return in_array($ticket['id'], phive('Config')->valAsArray('mp', 'prize-ticket-ids'));
    }

    function useTicket(&$t, &$u, $cost, $ticket, $fx = true){
        $tr_type = $this->isPrizeTicket($ticket) ? 97 : 74;
        $this->transactUser($t, $u, $cost, $tr_type, 'cash', false, "-aid-{$ticket['id']}", $fx);
        $use_award_res = phive('Trophy')->useAward($ticket);
        // It is an error message or false
        if(!is_array($use_award_res)){
            return false;
        }
        return $u->getBalance();
    }

  function incrEntry($eid, $incr, $extra, $uid){
    return $this->db->incrValue('tournament_entries', '', array('id' => $eid), $incr, $extra, $uid);
  }

  function rebuy($u, $eid){
      $u    = cuPl($u);
      if(empty($u))
          return 'loggedout';
      $e    = $this->entryById($eid, $u->getId());
      if($e['user_id'] != $u->getId())
          return false;
      $t    = $this->getByEntry($e);
      if(!$this->canRebuy($t, $e))
          return 'fail';
      $cost       = round($this->chgToUsr($u, $t['rebuy_cost'], 1));
      $house_fee  = round($this->chgToUsr($u, $t['rebuy_house_fee'], 1));
      $total_cost = $cost + $house_fee;

      $limits_check = $this->lgaLimConflict($t, cu(), true);
      if ($limits_check !== false) {
          return $limits_check;
      }
      //mp-ticket
      //$ticket = $this->getTicket($u, $t, $t['rebuy_cost']);
      //if(!empty($ticket))
      //$new_balance = $this->useTicket($t, $u, $cost, $ticket, false);
      //else{
      $t_id = is_array($t) ? $t['id'] : $t;
      if ($u->getAttr('cash_balance', true) < $total_cost) {
          return 'no-cash';
      }
      if (!empty($house_fee)) {
          $new_balance = phive('Casino')->changeBalance($u, -$house_fee, $this->trDescr($t), 52, '', 0, 0, false, 0, '', (int)$t_id);
        }
        $new_balance = phive('Casino')->changeBalance($u, -$cost, $this->trDescr($t), 54, '', 0, 0, false, 0, '', (int)$t_id);
        //}

        if ($new_balance !== false) {
            if (in_array($t['prize_type'], array('win-fixed'))) {
                $this->incr($t, '', array('prize_amount' => $t['rebuy_cost']));
            }
            $this->incrEntry(
                $eid,
                array(
                    'rebuy_times' => -1,
                    'cash_balance' => $t['rebuy_cost'] * $t['spin_m'],
                    'spins_left' => $this->getXspinInfo($t, 'tot_spins')
                ),
                array('status' => 'open'),
                $u->getId()
            );
            $this->wsOnChange($e['id'], $t, 1, 'main', $u->getId(), true);
            $this->wsSystemMsg($u, $t, 'mp.rebuy.system.msg');
            return $new_balance;
        } else {
            return 'no-cash';
        }
    }

  //rc done
  /** @var $u DBUser */
  function insertEntry($u, $t){
    $info = $this->getXspinInfo($t);
    $move = array('get_race', 'get_loyalty', 'get_trophy', 'win_format', 'play_format', 'rebuy_times');
    $entry = phive()->moveit($move, $t);

    $entry['user_id'] 	        = $u->getId();
    $entry['dname']   	        = $u->getAlias();
    $entry['t_id'] 		= $t['id'];
    $entry['cash_balance'] 	= $t['cost'] * $t['spin_m'];
    $entry['status']            = $t['status'] == 'registration.open' ? 'upcoming' : 'open';
    $entry['spins_left'] 	= $info['tot_spins'];
    $entry['updated_at'] 	= phive()->hisNow();

    return $this->db->sh($u, 'id', 'tournament_entries')->insertArray('tournament_entries', $entry);
  }

  //no rc - both
  function entryIsZero(&$e, &$t){
    if($e['play_format'] == 'xspin' && (int)$e['spins_left'] === 0)
      return true;
    return $e['cash_balance'] < $t['min_bet'];
  }

  //no rc - both
  function wsTournamentCreation($tid, $lobby_tag = 'main'){
    $this->wsTlobby($tid, $lobby_tag);

    $tournament = $this->byId($tid,true);
    $this->_addTournamentDisplayInfo($tournament);

    $tournament['game'] = phive('MicroGames')->getByGameRef($tournament['game_ref']);
    if (empty($tournament['game_name']) && empty($tournament['game']['game_name'])) {
      $tournament['game_name'] = $tournament['game'];
    }

    $tournament_data = [
      'start_format' => $tournament['start_format'], // needed to determine if we need to refetch the BattleReminderStrip (only on sng, where we display the number of players)
      'status' => $tournament['status'],
      'start_time' => $tournament['start_time'],
      'mtt_start' => $tournament['mtt_start'],
      'start_status' => $this->getStartOrStatus($tournament, true, false, true),
      'tournament_name' => phive()->ellipsis($tournament['tournament_name'], 32),
      'game_name' => phive()->ellipsis(empty($tournament['game_name']) ? $tournament['game']['game_name'] : $tournament['game_name'], 20),
      'category' => ucfirst($tournament['category']),
      'get_buy_in' => $this->getBuyIn($tournament),
      'enrolled_user' => $this->displayRegs($tournament),
      'pad_lock' => $this->padLock($tournament),
      'excluded_countries' => $tournament['excluded_countries'] ?? '',
      'game_blocked_countries' => $tournament['game']['blocked_countries'] ?? '',
      'blocked_provinces' => $tournament['blocked_provinces'] ?? '',
      'game_blocked_provinces' => $tournament['game']['blocked_provinces'] ?? '',
      'included_countries' => $tournament['included_countries'] ?? '',
      'guaranteed_prize_amount' => $tournament['guaranteed_prize_amount'],
      'award_ladder_tag' => $tournament['award_ladder_tag'] ?? ''
    ];
    $this->wsTmainLobby($tid, $tournament_data);
  }

  //no rc - both
  function wsTlobby($tid, $sub_type = 'main'){
    $this->log('lobby');
    $extra = [];
    if(is_array($tid)){
        $t               = $tid;
        $tid             = $t['id'];
        $extra['status'] = $t['status'];
    }

    $to_ws = [
        'action'  => 'update',
        'tid'     => $tid,
        'type'    => 'tournament-lobby',
        'subtype' => $sub_type
    ];

    toWs(array_merge($to_ws, $extra),
        'mp-tournament-lobby'.$tid,
        'na'
    );
  }

  //no rc - both

  function wsTmainLobby($tid, $tournament_data) {
      toWs(['action' => 'update', 'tid' => $tid, 'type' => 'tournament-row', 'tournament' => $tournament_data], 'mp-main-lobby', 'na');
  }

  //no rc - both
  function getRebuyEndTime(&$t){
    if($t['start_format'] == 'sng')
      return $t['duration_rebuy_minutes'].' '.t('minutes').' '.t('after.start');
    $start_time = phive()->isEmpty($t['start_time']) ? $t['mtt_start'] : $t['start_time'];
    return phive()->hisMod("+{$t['duration_rebuy_minutes']} minute", $start_time);
  }

    /**
     *  We check if the tournament is ongoing and possible issues if they open in different devices, browser tabs with spins left
     *
     * no rc - both
     *
     * @param array $t
     * @param array $e
     * @param bool $check_time
     * @return bool
     */
    public function canRebuy($t, &$e, $check_time = true)
    {
        if (!$this->isPlaying($t)) {
            return false;
        }
        if ((int)$e['rebuy_times'] <= 0 || !$this->entryIsZero($e, $t)) {
            return false;
        }
        if ($check_time) {
            if (empty($t['duration_rebuy_minutes'])) {
                return true;
            }
            return $this->getRebuyEndTime($t) > phive()->hisNow();
        }
        return true;
    }

    function getMpInfoKey($tid, $uid = ''){
        $uid = empty($uid) ? cuPlAttr('id') : $uid;
        return 'mpinfo'.$uid.'-'.$tid;
    }

    /**
     * Return a list of columns that will be stored in Redis for the Tournament leaderboard
     *
     * @return array
     */
    function getMemKeys(){
        $keys = [
            'id',
            'win_amount',
            'cash_balance',
            'highest_score_at',
            'win_amount',
            'biggest_win',
            'updated_at',
            'user_id',
            'spins_left',
            'dname',
            'joker',
            'bounty'
        ];
        // Return an array with the same keys as values
        return array_combine($keys, $keys);
    }

    /*
    function addToMem(&$t, &$e){
        list($mem_key, $stamps_mem_key) = $this->getLboardKeys($t);
        $lboard = phMgetArr($mem_key);
        $this->addToMemArr($e, $lboard);
        phMsetArr($mem_key, $lboard);
    }
    */

    function addToMemArr(&$e, &$lboard){
        $lboard[] = array_intersect_key($e, $this->getMemKeys());
    }

    /**
     * Initialize the Redis leaderboard.
     * Only the fields defined in getMemKeys() are used from tournament_entries.
     *
     * @param $t - tournament object
     * @param bool $set_expire - Not used anymore.. can be removed?
     */
    function initMem($t, $set_expire = true){
        list($mem_key, $stamps_mem_key) = $this->getLboardKeys($t);
        // In case we're testing we want to reset the leaderboard.
        $this->mc->del($mem_key);
        $lboard = $this->getLeaderBoard($t, false);
        $insert = [];
        $keys = $this->getMemKeys();
        $i = 0;
        foreach($lboard as $e){
            $tmp = array_intersect_key($e, $keys);
            // We initialize biggest win to avoid undefined behaviour in array multisort
            $tmp['biggest_win'] = empty($tmp['biggest_win']) ? $i : $tmp['biggest_win'];
            $insert[] = $tmp;
            $i++;
        }
        $insert = $this->sortLeaderboard($t, $insert);
        $this->mc->setJson($mem_key, $insert, $this->getMaxDur());
    }

    function getMaxDur(){
        return empty($this->getSetting('max_duration')) ? 36000 : $this->getSetting('max_duration');
    }

    function getLboardKeys($t){
        return ["mpleaderboard".$t['id'], "stamps-mpleaderboard".$t['id']];
    }

    /**
     * Updates the memory representation of the tournament leaderboard and send the update to the frontend
     *
     * @param $eid
     * @param string $t
     * @param int $made_rebuy
     * @param string $lobby_tag
     * @param string $uid
     * @param false $force_update
     * @return false|void
     */
    function wsOnChange($eid, $t = '', $made_rebuy = 0, $lobby_tag = 'main', $uid = '', $force_update = false, $entry = null, $tourney = null){
        if (!empty($entry) && !empty($tourney)) {
            $e = json_decode(json_encode($entry), true);
            $t = json_decode(json_encode($tourney), true);
        }
        else
        {
            $e = is_array($eid) ? $eid : $this->entryById($eid, $uid);
            if (empty($t))
                $t = $this->getByEntry($e);
        }
        list($mem_key, $stamps_mem_key) = $this->getLboardKeys($t);

        $lboard_before = $this->mc->getJson($mem_key);
        //In case we've restarted Redis we need to initialize
        if(empty($lboard_before)){
            $this->initMem($t);
            $lboard_before = $this->mc->getJson($mem_key);
        }
        $lboard_after = $lboard_before;
        $found_user = false;
        // Maintentance of the in-memory leaderboard representation.
        foreach($lboard_after as &$row){
            if($row['user_id'] == $e['user_id']){
                $found_user = true;
//                $row['id']         = $e['id'];   // need this here ?
                $row['win_amount'] = $e['win_amount'];
                $row['spins_left'] = $e['spins_left'];
                $row['updated_at'] = phive()->hisNow();
                if($e['win_amount'] > $row['biggest_win']){
                    $row['biggest_win'] = $e['win_amount'];
                    $row['highest_score_at'] = phive()->hisNow();
                }
                break;
            }
        }

        // We got a late reg so we have to add it to the in-memory representation of the leaderboard
        // wsOnChange is called on registration so this is the only place
        if(!$found_user)
            $this->addToMemArr($e, $lboard_after);

        // PHP multisort results in undefined behaviour in some instances
        $lboard_after  = $this->sortLeaderboard($t, $lboard_after);
        $this->mc->setJson($mem_key, $lboard_after, $this->getMaxDur());

        $pos_arr = phive()->arrCol($lboard_after, 'user_id');
        //We get the people with a new position on the leaderboard
        $diff = array_diff_assoc($pos_arr, phive()->arrCol($lboard_before, 'user_id'));

        //phive()->dumpTbl('mpws', [$lboard_before, $lboard_after, $diff]);

	    // We need to update everyone's own position (displayed in red) that got their position changed.
	    // Example: player A goes above player B who was in position 50, now he is on 51, and this is sent
	    // to him.
        foreach($diff as $uid){
          $pos = array_search($uid, $pos_arr);
          $cur_usr = $lboard_after[$pos];
          toWs([
            'alias'           => $this->formatBattleAliasForDisplay($cur_usr['dname']),
            'win_amount'      => $cur_usr['win_amount'],
            'spins_left'      => $cur_usr['spins_left'],
            'user_id'         => $uid,
            'pos'             => $pos + 1,
          ], $this->getMpInfoKey($t['id'], $uid), 'na');
        }

        // We need current position to prevent sending updates if the player is outside of the displayed leaderboard.
        $current_pos = array_search($e['user_id'], $pos_arr) + 1;

        //We update current player with his new score, and spins left, when is the the one making the bet.
        toWs([
//            'id'         => $e['id'],  // need this here?
            'user_id'    => $e['user_id'],
            'win_amount' => $e['win_amount'],
            'spins_left' => $e['spins_left'],
            'pos'        => $current_pos,
            'alias'      => $this->formatBattleAliasForDisplay($e['dname'])
        ], $this->getMpInfoKey($t['id'], $e['user_id']), 'na');

        /**
         * With this limitation to the top 30 player we are not sending the Rebuy event from 31th afterwards.
         * so we need to force the update to be able to trigger the popup for players with 0 spins left that can rebuy
         * The same applies for finished tournament
         */
        //prevent rebuys after duration_rebuy_minutes
        $rebuy_times = $this->canRebuy($t, $e) ? $e['rebuy_times'] : 0;
        if($e['spins_left'] == 0 && ($rebuy_times > 0 || $e['status'] == 'finished')) {
            $force_update = true;
        }
        //$top_count     = phive('Config')->getValue('websockets', 'show_top_battle_count');
        $top_count = 30;
        if(!empty($top_count) && !$force_update){
            //current position is larger than the top range of positions we want to communicate to the clients
            //server side code doesn't send any updates for people who are not on the client side displayed leaderboard
            if($current_pos > $top_count)
                return false;
        }

        $ud = ud($e['user_id']);

        $change_pos = array_search($e['user_id'], $diff) === false ? false : true;

        // Leaderboard updates: player A is on position 3, player B goes from 4 to 3, everyone needs to see that
        // A goes to 4 and that B goes to 3 (switching places), and B's new score etc.
        // If player is on leaderboard we update him, if new position has been reached we set change_pos to true.
        toWs([
            'change_pos'    => $change_pos,
            'pos'           => $current_pos, //Only needed if we need to change the position, at which point it will always exist
            'tid'           => $t['id'],
            'eid'           => $e['id'],
            'status'        => $e['status'],
            'is_zero'       => $this->entryIsZero($e, $t),
            'rebuy_times'   => $rebuy_times,
            'spins_left'    => $e['spins_left'],
            'win_amount'    => $e['win_amount'],
            'total'         => $this->fAmount($this->totalPrizeAmount($e['t_id'], false)),
            'user_id'       => $ud['id'],
            'made_rebuy'    => $made_rebuy,
            'wstag'         => 'leaderboard',
            'alias'         => $this->formatBattleAliasForDisplay($e['dname']),
            'dname'         => $this->formatBattleAliasForDisplay(ucfirst(strtolower($ud['firstname']))) // used if no battle alias is present
        ], 'mp'.$t['id'], 'na');
        $this->wsTlobby($t['id'], $lobby_tag);
    }

  //no rc - both
  function fAmount($amount){
    return $this->cSym().' '.number_format($amount / 100, 2);
  }

  //no rc - both
  function tCur(){
    $res = $this->getSetting('currency');
    return empty($res) ? 'EUR' : $res;
  }

  /**
   * @param $e
   * @param $amount negative => bet, positive => win
   * @param $ud
   * @param $t Tournament as array
   * @param $multi_call is true if we're passing from a GP that supports deposit and withdraw in a single call, if we do we limit socket updates to only happen on bets
   * @return int
   */
    function playChgEntry($e, $amount, $ud, $t, $multi_call = false, $gp_obj = null){

        // handling variable bet levels
        $spinCount = 1;
        if($t['min_bet'] != $t['max_bet']){
            if($amount < 0){ // it is a bet
              $spinCount = abs($amount/$t['min_bet']);
              //  file_put_contents('/tmp/playchgentry.log', "playChgentry BETCOUNT is int: { $betCount}\n",FILE_APPEND);
            }
        }

        //Empty amounts are sometimes sent in "finished game round" calls etc, in that case, do nothing.
        if(empty($amount))
            return $e;

        if($e['status'] != 'open' && $amount < 0){
            $this->wsEntryFinished($e, $ud, $t, 'mplimit', 'yes', $gp_obj);
            //phive('Casino')->pexecLimit($ud, 'mp.finished', $t['game_ref'], 'no', $e['id']);
            return 0;
        }

        $incs = array();
        $upd_extra = array('updated_at' => phive()->hisNow());

        if($amount > 0){
            if($this->isCalculated($t)){
                phive('Cashier')->insertTransaction($ud['id'], $amount, 83, 'Tournament win after prize calculation.');
                phive()->dumpTbl('win_after_prize_calc_entry', $e, $ud);
                return 0;
            }
            //win
            if(!in_array($t['prize_type'], ['win-prog', 'win-fixed', 'win-static'])){
                $incs['cash_balance'] = $amount;
            }

            if($e['win_format'] == 'tht')
                $incs['win_amount'] = $amount;
            else if($e['win_format'] == 'thw'){
                if($e['win_amount'] < $amount)
                    $upd_extra['win_amount'] = $amount;
            }
        }else{
            //bet
            $incs['cash_balance'] = $amount;
            $incs['turnover'] = abs($amount);
            if($e['play_format'] == 'xspin'){
                // if they bet $betCount times as a minimum bet
                // we have to deduct $betCount times from spins_left
                $times = is_int($spinCount) ? $spinCount : 1;
                $incs['spins_left'] = -1 * $times;
            }
        }

        if($e['biggest_win'] < $amount){
            $upd_extra['biggest_win'] = $amount;
            $upd_extra['highest_score_at'] = phive()->hisNow();
        }

        $new_e = phive()->addArrays($e, $incs);

        $has_ended = $this->hasEnded($t);
        $e_is_zero = $this->entryIsZero($new_e, $t);

        if($e_is_zero || $has_ended){
            $new_e['status'] = 'finished';
            $upd_extra['status'] = 'finished';

            phive()->pexec('Tournament', 'propagateEndSessionMessage', [$e['id']]);
        }

        $this->incrEntry($e['id'], $incs, $upd_extra, $ud['id']);

        if($has_ended)
            $this->wsEntryFinished($new_e, $ud, $t, 'mplimit', 'no', $gp_obj);
        else{
            // We perform a websocket update if it's a bet or we're dealing with a non-multi GP
            // We set the sleep to 100k microseconds to let the win register in case of a multi GP
            if($amount < 0 || $multi_call !== true){
                $args = [$e['id'], 'na', 0, 'leaderboard', $e['user_id'], false, $new_e, $t];
                phive()->fire('tournament','tournamentEntryUpdatedEvent', $args, 100, function() use ($args){
                    phive()->pexec('Tournament', 'wsOnChange', $args, 100000);
                },  $e['user_id']);
            }
        }

        //phive()->dumpTbl('pl-chg-entry', array($amount, $new_e));

        //bet
        if($amount < 0 && $e_is_zero && !$this->canRebuy($t, $new_e)){
            //phive()->dumpTbl('pl-chg-entry-finished', 'yes');
            $this->wsEntryFinished($new_e, $ud, $t, 'mplimit', 'no', $gp_obj);
        }

        //TODO if bet and type is not xspin do we need to do the websocket udpate?
        return $new_e;
    }

    function wsEntryFinished($eid, $ud = [], $t = [], $wstag = 'mplimit', $go_home = 'no', $gp_obj = null){
        if(is_array($eid))
            $e = $eid;
        else if(empty($ud) || empty($t))
            $e = $this->entryById($eid, $ud['id']);
        if(empty($ud))
            $ud = ud($e['user_id']);
        if(empty($t))
            $t = $this->getByEntry($e);
        list($mem_key, $stamps_mem_key) = $this->getLboardKeys($t);
        // We might not run the usual wsOnChange in case we're finished, in that case we need to update
        // the leaderboard with the zeroed out spins left to make sure the in-memory representation
        // is consistent with the actual leaderboard
        $lboard = $this->mc->getJson($mem_key);
        foreach($lboard as &$row){
            if($row['user_id'] == $ud['id']){
                $row['spins_left'] = 0;
                break;
            }
        }
        $this->mc->setJson($mem_key, $lboard, $this->getMaxDur());

        //if(empty($gp_obj)){
        //    $gp_obj = phive('Casino')->getGpFromGref($t['game_ref']);
        //}

        //if(method_exists($gp_obj, 'logoutUser')){
            //phive()->pexec(get_class($gp_obj), 'logoutUser', [$ud['id'], phive('Casino')->mkUsrId($ud['id'], $e['id'])]);
        //}

        // We sleep for 2 seconds before we send the finished popup over the websocket, the reason for this wait is that
        // we want to make sure that the GP has enough time to send the fs / bonus start fi event from the game to our
        // FE JS. In case we have that event we will pause display of the completed popup until the bonus / fs have been played out.
        phive('Casino')->pexecLimit($ud, 'mp.finished', $t['game_ref'], $go_home, $e['id'], $wstag, 2000000);
    }

  //no rc
  /**
   * set tournament entry as finish by id
   * when entry has no re-buys or spins left
   * call from re-buy popup countdown
   * @params int $eid
   */
  function finishEntry($eid){
    $e = $this->entryById($eid);
    if ($e['user_id'] != cuPlAttr('id', '', true)) {
        return false;
    }
    if ((int)$e['rebuy_times'] <= 0 && $e['spins_left'] <= 0) {
        $e['status'] = 'finished';
    }

    $this->wsEntryFinished($eid);
    $this->saveEntry($e);
  }

  //no rc
  function globalStatus(){
    return phive('Config')->getValue('mp', 'global-status');
  }

  //no rc
  function globalStatusMsg(){
    $status = $this->globalStatus();
    if(empty($status))
      $status = 'on';
    et("mp.status.$status");
  }

    //no rc
    function canRegister($t, $u, $e = null, $tpl = null, $args = []){

        $can_queue = $this->canQueue($t, $u, $e, $tpl, $args);
        if(is_string($can_queue)){
            return $can_queue;
        }

        if($this->isFull($t)){
            return 'full';
        }

        if(!$this->openForRegStatus($t)){
            return 'closed';
        }

        return true;
    }

    public function isQueued($tpl, $t){
        return $t['start_format'] == 'sng' && !empty($tpl['queue']);
    }

    public function userIsQueued($tpl, $u_obj){
        return !empty(phMgetShard('tplqueue'.$tpl['id'], $u_obj->getId()));
    }

    public function canQueue($t, $u, $e = null, $tpl = null, $args = []){
        $u = empty($u) ? cuPl() : $u;
        if(empty($u)) {
            return 'loggedout';
        }

        $game = $this->getGame($t);

        if(phive('MicroGames')->checkIsGameBlocked($u->getCountry(), $u->getProvince(), $game)){
            return 'restricted';
        }

        if(in_array($u->getCountry(), explode(' ', $t['excluded_countries']))) {
            return 'restricted';
        }

        if(!empty($t['included_countries']) && !in_array($u->getCountry(), explode(' ', $t['included_countries']))){
            return 'restricted';
        }

		if (licSetting('require_main_province')) {
			if (!empty($province = $u->getProvince()) && !empty($t['blocked_provinces'])) {
				if(in_array($province, explode(' ', $t['blocked_provinces']))) {
					return 'closed';
				}
			}
		}

        $is_on = $this->globalStatus();

        if($is_on !== 'on') {
            return 'closed';
        }

        if(!empty($t['pause_calc'])){
            return 'closed';
        }

        if(!empty($t['pwd']) && $args['pwd'] != $t['pwd']){
            return 'pwd';
        }

        $tpl = $tpl ?? $this->getParent($t);

        if($this->userIsQueued($tpl, $u)){
            // We have a queued SNG but the player is already queued.
            return 'queued.already';
        }

        if(empty($e)) {
            $e = $this->entryByTidUid($t['id'], $u->getId());
        }
        if(!empty($e)) {
            return 'already';
        }

        $es = $this->getEntriesByTplidUid($t, $u->data, 'open', 3);
        if(!empty($es)) {
            return 'closed';
        }

        if($this->isRestrictedRecurring($u, $tpl)){
            return 'closed';
        }

        $total_cost_def_cur = $this->getBuyin($t, true);

        $ticket = $args['use_ticket'] ? $this->getTicket($t, $u, $total_cost_def_cur) : null;

        if(!$this->depWagerLimCheck($t, $u, null, null, $ticket)){
            return 'limit';
        }

        // We disallow registering in freerolls when you have an active bonus
        if($t['category'] != 'freeroll' && phive('Bonuses')->hasActive($u->getId()) && empty($t['allow_bonus'])){
            return 'bonus';
        }

        return true;
    }

    function isRestrictedRecurring($u, $tpl){
        //1. Get all not closed tournaments with the same tpl_id with recur 4
        //2. Check if the player is registered in any of them, disregard entry status
        //3. Return false if 2 is positive
      if((int)$tpl['recur'] === 4){
            $opened_tournaments_id = $this->db->loadCol("SELECT t.id FROM tournaments t WHERE t.tpl_id = {$tpl['id']} AND t.status NOT IN('finished', 'cancelled')", "id");
            if(!empty($opened_tournaments_id)) {
              $opened_tournaments_id_str = $this->db->makeIn($opened_tournaments_id);
              $str = "SELECT count(1) FROM tournament_entries te WHERE te.t_id IN ({$opened_tournaments_id_str}) AND te.user_id = {$u->getId()}";
              $entries_count  = $this->db->sh($u)->getValue($str);
              if($entries_count > 0) {
                return true;
              }
            }
        }
        return false;
    }


  //no rc
  function isClosed(&$t){
    return in_array($t['status'], array('finished', 'cancelled'));
  }

  //no rc
  function isUpcoming(&$t){
    return in_array($t['status'], array('upcoming', 'registration.open'));
  }

  //no rc
  function canPlay(&$t, $e){
    if(empty($e))
      return false;
    if($e['status'] != 'open')
      return false;
    return in_array($t['status'], array('in.progress', 'late.registration'));
  }

    public function transactUser(
        &$t,
        &$u,
        $amount,
        $ttype,
        $pw = 'cash',
        $change_balance = true,
        $extra_descr = '',
        $fx = true
    ) {
        if (empty($amount)) {
            return $u->getBalance();
        }
        if (in_array($ttype, array(55, 57, 58, 59))) {
            $change_balance = false;
        }
        $descr = $this->trDescr($t) . $extra_descr;
        $def_balance = $pw == 'cash' ? $u->getBalance() : $u->getSp();

        if (empty($amount)) {
            return $def_balance;
        }

        if ($fx) {
            $amount = round($this->chgToUsr($u, $amount, 1));
        }
        //Currently reward prizes only work for freerolls.
        //if($t['category'] == 'freeroll'){
        //  phive("Cashier")->insertTransaction($u, $amount, 57, $descr);
        //  return $def_balance;
        //}
        $t_id = is_array($t) ? $t['id'] : $t;
        if ($change_balance) {
            if ($pw == 'cash') {
                return phive("QuickFire")->changeBalance($u, $amount, $descr, $ttype, '', 0, 0, false, 0, '', (int)$t_id);
            } else {
                $u->incSp($amount);
                $new_trans_id = phive("Cashier")->insertTransaction($u, $amount, $ttype, $descr);
            }
        } else {
            $new_trans_id = phive("Cashier")->insertTransaction($u, $amount, $ttype, $descr);
        }

        $history_message = [
            'user_id' => (int)$u->getId(),
            'transaction_id' => (int)$new_trans_id,
            'amount' => (int)$amount,
            'currency' => $u->getCurrency(),
            'transaction_type' => (int)$ttype,
            'parent_id' => 0,
            'description' => $descr,
            'tournament_id' => (int)$t_id,
            'device_type' => phive()->isMobile() ? 1 : 0,
            'event_timestamp' => time(),
        ];

        try {
            $history_message = new TournamentCashTransactionHistoryMessage($history_message);

            /** @uses Licensed::addRecordToHistory() */
            lic(
                'addRecordToHistory',
                [
                    'tournament_cash_transaction',
                    $history_message,
                ],
                $u
            );
        } catch (InvalidMessageDataException $e) {
            phive('Logger')->getLogger('history_message')
                ->error(
                    $e->getMessage(),
                    [
                        'topic'             => 'tournament_cash_transaction',
                        'validation_errors' => $e->getErrors(),
                        'trace'             => $e->getTrace(),
                        'data'              => $history_message
                    ]
                );

        }

    return $def_balance;
  }


  function getDepWagerLimSums($u, $stime, $etime){
      $depsum        = phive('Cashier')->sumTransactionsByType($u->getId(), 3, $stime, $etime);
      $sums          = phive('UserHandler')->sumGameSessions($u->getId(), $stime, $etime, 'game_ref');
      $game_refs_str = $this->db->makeIn(array_column($sums, 'game_ref'));
      $games         = $this->db->loadArray("SELECT * FROM micro_games WHERE ext_game_name IN($game_refs_str)", 'ASSOC', 'ext_game_name');

      $bet_amount = 0;
      foreach($sums as $sum){
          // TODO this line will incur a DB call per iteration of the loop, a more optimized solution would
          // be to redo the above SQL and join on the game_country_overrides table. /Henrik
          $bet_amount += phive('Casino')->getRtpProgress($sum['bet_amount'], $games[$sum['game_ref']], $u);
      }

      $dep_sum_changed   = $this->chgFromUsr($u, $depsum);
      //$wager_sum_changed = $this->chgFromUsr($u, $bet_amount);
      return ['dep_sum' => $dep_sum_changed, "wager_sum" => $bet_amount];
  }

    /**
     * Checks for requirements when registering on tournaments.
     *
     * We allow if:
     *   - Reg Lim Period is empty, means that there no requirement to register
     *   - Has permission or setting to test
     *   - Has a freeroll ticket
     *   - Is from a country where the requirements are not allowed
     *
     * We don't allow:
     *   - Checking for the configured period if there have deposited enough @see getDepWagerLimSums
     *   - The wager requirements (for the customer their XP points) as enough in the last X days @see getDepWagerLimSums
     *
     * @param $t
     * @param DBUser $u
     * @param string $stime
     * @param string $etime
     * @param array $ticket
     * @return bool
     */
    public function depWagerLimCheck(&$t, &$u, $stime = '', $etime = '', $ticket = null){

        if(empty($t['reg_lim_period']))
            return true;

        if(p('tournament.view') || $u->hasSetting('freeroll-tester'))
            return true;

        $active_current_ticket = phive('Trophy')->getActiveCurrent($u, 'normal');
        if($active_current_ticket['type'] == 'mp-freeroll-ticket') {
            return true;
        }

        if (!empty($ticket) && $ticket['type'] == 'mp-freeroll-ticket') {
            return true;
        }

        if(in_array($u->getCountry(), explode(' ', $t['reg_lim_excluded_countries']))) {
            return true;
        }

        if(empty($stime))
            $stime  = phive()->hisMod("-{$t['reg_lim_period']} day", '', 'Y-m-d');
        if(empty($etime))
            $etime  = phive()->hisNow();

        $this->reg_sums = $sums = $this->getDepWagerLimSums($u, $stime, $etime);

        if($sums['dep_sum'] < $t['reg_dep_lim']){
            return false;
        }

        if($sums['wager_sum'] < $t['reg_wager_lim']){
            return false;
        }

        return true;
    }

  //no rc
  function decNregs(&$t){
    $this->incr($t, '', array('registered_players' => -1));
  }


    function sngCron(){
        if(phive('Config')->getValue('mp', 'sng-cron-start') === 'off')
            return;

        $str = "SELECT * FROM tournaments WHERE status = 'registration.open' AND start_format = 'sng' AND registered_players >= max_players";
        $ts = $this->db->loadArray($str);
        foreach($ts as $t){
            $now             = phive()->hisNow();
            $t['status']     = 'in.progress';
            $t['start_time'] = $now;
            // We save only on the master for now
            $this->db->save('tournaments', $t);
            $this->setEntriesStatus($t, 'open');
            $this->initMem($t);
	    $sng_tpl = $this->getParent($t);
	    if(!$this->hasExpired($sng_tpl) && in_array((int)$sng_tpl['recur'], array(1, 3, 4))){
                $new_tid = $this->insertSng($t, $sng_tpl);
            }
        }
    }

    function insertSng(&$t, &$sng_tpl){
        $currently_registering = $this->getByStatusTpl($sng_tpl, 'registration.open');
        //if more than the one we're about to set to in.progress are registering we don't create a new one
        if(count($currently_registering) <= 1) {
            if (!$this->checkSngLimit($sng_tpl)) {
                phive()->dumpTbl('limiting-sng', $t);
                return false;
            }

            return $this->insertTournament($sng_tpl);
        }
        return false;
    }

    public function isFull(&$t){
        return $t['registered_players'] >= $t['max_players'];
    }

    //no rc
    function register($u, $tid, $args = []){
        $pw = 'cash';

        $u   = cu($u);
        if(empty($u)){
            return 'loggedout';
        }

        $uid = $u->getId();
        $t = is_array($tid) ? $tid : $this->byId($tid);
        $e = $this->entryByTidUid($t['id'], $u->getId());
        if(!empty($e)) {
            return 'already';
        }

        if($t['registered_players'] < 0) {
            phive('Logger')
                ->getLogger('bos_logs')
                ->error(
                    "Doing register for tournament from register() method - check number of registered players before updating registered_players column",
                    [
                        'tournament_id'      => $t['id'],
                        'registered_players' => $t['registered_players'],
                        'status'             => $t['status'],
                    ]
                );
        }

        $total_cost_def_cur = $this->getBuyin($t, true);

        $ticket = $args['use_ticket'] ? $this->getTicket($t, $u, $total_cost_def_cur) : null;

        $descr 	          = $this->trDescr($t);
        $new_balance      = false;
        $total_cost       = round($this->chgToUsr($u, $total_cost_def_cur, 1));
        $inc_pot_with     = 0;
        $inc_players_with = 0;


        //mp-ticket
        if(!empty($ticket) && $t['category'] != 'freeroll'){
            $new_balance = $this->useTicket($t, $u, $total_cost, $ticket, false);
            // Error message.
            if($new_balance === false){
                return 'fail';
            }
        } else {
            if($pw == 'cash' && $u->getAttr('cash_balance') >= $total_cost){
                $map              = array(34 => 'cost', 52 => 'house_fee');
                $pot_trtype       = empty($t['free_pot_cost']) ? 35 : 58;
                $map[$pot_trtype] = 'pot_cost';
            }//else if($pw == 'points' && $u->getSp() >= $total_cost){
            //nTODO skill point tournaments are currently not working with cancel tournament or free pot cost
            //$map = array(37 => 'cost', 49 => 'pot_cost', 52 => 'house_fee');
            //}

            foreach($map as $ttype => $col){
                if($t['category'] == 'freeroll'){
                    $ttype = 55;
                    $chg_bal = false;
                }else{
                    $chg_bal = true;
                }
                $new_balance = $this->transactUser($t, $u, -$t[$col], $ttype, $pw, $chg_bal);
            }
        }

        if($new_balance !== false)
            $inc_pot_with = $t['prize_type'] == 'win-fixed' ? $t['cost'] : $t['pot_cost'];

        if ($new_balance !== false || $t['category'] == 'freeroll') {
            $this->incr($t, '', array('registered_players' => 1));
            $inc_players_with = 1;
            //Insert tournament_entries
            $new_eid = $this->insertEntry($u, $t);

            // We get a fresh copy of the tournment to check the amount of registered players to avoid the SNG
            // getting stuck.
            $upd_arr = [];
            //clear cache
            $this->deleteTournamentCache($t['id']);
            $fresh_t = $this->byId($t['id']);
            if (phive('Config')->getValue('mp', 'sng-cron-start') === 'off' && $fresh_t['registered_players'] >= $t['max_players'] && $t['start_format'] == 'sng') {
                $upd_arr['status'] = 'in.progress';
                $now = phive()->hisNow();
                //$t['mtt_start'] = $now;
                $upd_arr['start_time'] = $now;
                $this->setEntriesStatus($t, 'open');
                $this->initMem($t);
                $sng_tpl = $this->getParent($t);
                if (!$this->hasExpired($sng_tpl) && in_array((int)$sng_tpl['recur'], array(1, 3, 4))) {
                    // TODO, remove this and have a cron job that checks for in.progress and if in.progress has registration.open of same tpl id
                    // if no, create a new one
                    $new_tid = $this->insertSng($t, $sng_tpl);
                }
                // For SNG we need to keep in sync the changes on "status" on the SHARDS too, otherwise some queries needed by mobile BoS will display wrong data (Ex. battle reminder strip)
                // The SHARDS are updated properly only when the CRON is running, otherwise we only update MASTER
                $this->db->updateArray('tournaments', $upd_arr, ['id' => $t['id']]);
            }

            phive()->fire('tournament','tournamentRegisterUserEvent', [$t, $uid, $new_eid, $inc_pot_with], 0, function () use ($t, $uid, $new_eid, $inc_pot_with) {
                $module = phive('Casino')->getGpFromGref($t['game_ref'], false);
                phive()->pexec($module, 'registerUser', [$uid, $new_eid]); // same as CasinoEventHandler->onTournamentRegisterUserEvent
                phive('Events/NotificationEventHandler')->onTournamentRegisterUserEvent($t, $uid, $new_eid, $inc_pot_with);
            }, $uid);

            $this->incr($t, '', array('prize_amount' => $inc_pot_with), $upd_arr);
        }

        if ($t['registered_players'] < 0) {
            $updatedT = $this->byId($t['id']);
            phive('Logger')
                ->getLogger('bos_logs')
                ->error("Doing register for tournament from register() method - new number of players after updating registered_players column",
                        [
                            'tournament_id'         => $updatedT['id'],
                            'registered_players'    => $updatedT['registered_players'],
                            'status'                => $updatedT['status'],
                            'balance'               => $new_balance,
                            'category'              => $t['category']
                        ]
                    );
        }

        return $new_balance === false ? 'no-cash' : $t;
    }

  //no rc
  function hasExpired($tpl){
    return (strtotime($tpl['recur_end_date']) < time());
  }


  //no rc
    function calcPrizesCron()
    {
        if (phMget('tournament-cron-lock')) {
            phive('Logger')->getLogger('bos_logs')->info("tournament_cron", "Skipped due to tournament-cron-lock");
            return false;
        }
        phMset('tournament-cron-lock', true, phive()->getSetting("bos_tournament_cron_lock_time"));

        $cron_start = microtime(true); // TODO: Remove me after debug

        $now = phive()->hisNow();
        $where = "WHERE prizes_calculated = 0
                      AND pause_calc = 0
                      AND calc_prize_stamp <= '$now'
                      AND calc_prize_stamp IS NOT NULL
                      AND calc_prize_stamp != '0000-00-00 00:00:00'";
        $ts = $this->db->loadArray("SELECT * FROM tournaments $where");

        if (is_array($ts) && count($ts) > 0) {
            try {
                foreach ($ts as $t) {
                    $this->calcPrizes($t);
                }
            } catch (Exception $e) {
                phMdel('tournament-cron-lock');
                phive('Logger')->getLogger('bos_logs')->error("tournament_cron", $e->getMessage());
            }
        }

        // Calculate SNG status, and add new ones if current SNGs are full
        $sng_start = microtime(true);
        $this->initSng();
        $this->sngCron();
        $sng_duration = microtime(true) - $sng_start;

        // Change status of Scheduled battles
        $schedule_micro = microtime(true);

        try {
            $this->mttScheduleCron();
        } catch (Exception $e) {
            phMdel('tournament-cron-lock');
            phive('Logger')->getLogger('bos_logs')->error("tournament_cron", $e->getMessage());
        }

        $schedule_duration = microtime(true) - $schedule_micro;

        $smicro = microtime(true);
        $this->statusesCron();
        $duration_status = microtime(true) - $smicro;

        $tourn_closed_time = microtime(true);
        $current_min = date('Y-m-d H:i:00', strtotime('-1 minutes'));
        $tournaments_closed = $this->db->loadArray("SELECT id FROM tournaments WHERE end_time > '$current_min'"); // TODO: Remove me after debug
        $tournaments_closed = !empty($tournaments_closed) ? implode(', ', array_column($tournaments_closed, 'id')) : 'none';
        $closed_duration = microtime(true) - $tourn_closed_time;

        $chat_block_time = microtime(true);
        $this->chatBlockCron();
        $chat_block_duration = microtime(true) - $chat_block_time;

        phive('Logger')->getLogger('bos_logs')->info("tournament_cron", [
            'total_duration' => (microtime(true) - $cron_start) . " seconds",
            'sng-cron' => $sng_duration . " seconds",
            'schedule-cron' => $schedule_duration . " seconds",
            'statuses-cron' => $duration_status . " seconds",
            'closed-tourn-duration' => $closed_duration . " seconds",
            'chat-block-duration' => $chat_block_duration . " seconds",
            'tournaments_ended' => $tournaments_closed,
        ]);

        phMdel('tournament-cron-lock');
    }

    //no rc
    /**
     * Set tournament status to finished and calculate the prizes
     * (if sng and recur = 2) spawn a new tournament from the same template
     *
     * Extra notes:
     * we update and notify all the entries before saving the tournament with the new status, to avoid having user in open status on a finished tournament.
     * Beware we are passing $t to setEntriesStatus with "in memory" changes for the status so it will trigger the proper action on the WS call.
     *
     * @param $t
     * @param $estamp
     * @return array
     */
    function endTournament($t, $estamp = null)
    {
        $t = is_numeric($t) ? $this->byId($t) : $t;
        //If tournament is already finished we assume a forced prize calculation is wanted
        if ($t['status'] == 'finished')
            return $this->calcPrizes($t);
        if (phMget("tournament-ended".$t['id'])) {
            return;
        }
        phMset("tournament-ended".$t['id'], true, 36000);
        $t['end_time'] = phive()->hisNow($estamp);
        $t['status'] = 'finished';
        $this->save($t);

        phive()->fire('tournament', 'tournamentFinishedEvent', [$t], 0, function() use ($t){
            phive('Events/TournamentEventHandler')->onTournamentFinishedEvent($t);
            phive('Events/TrophyEventHandler')->onTournamentFinishedEvent($t);
        });
    }

  //no rc
  function totalSpinsLeft(&$t){
    if(empty($t['xspin_info']))
      return false;
    $entries = $this->entries($t);
    if(empty($entries))
      return false;
    return phive()->sum2d($entries, 'spins_left');
    //return (int)$this->db->getValue("SELECT SUM(spins_left) FROM tournament_entries WHERE t_id = {$t['id']}");
  }

  //rc test
  function getByStatuses($statuses){
    return $this->db->loadArray("SELECT * FROM tournaments WHERE status IN({$this->db->makeIn($statuses)}");
  }

  function getEntriesByStatus($status, $group_col = ''){
      $group_by = '';
      if(!empty($group_col)){
          $group_by = "GROUP BY $group_col";
      }
      return $this->db->shs()->loadArray("SELECT * FROM tournament_entries WHERE status = '$status' $group_by");
  }

  //no rc
  function getActive(){
    return $this->getByStatuses($this->begunStatuses());
  }

  //rc test
  function pausePrizeCalc(){
    foreach($this->getActive() as $t){
      $t['pause_calc'] = 1;
      $this->save($t);
    }
  }

    /**
     * Statuses in which the tournament is active
     * - late.registration: until we allow player to register
     * - in.progress: while the tournament is ongoing but registration are closed
     *
     * @return string[]
     */
    private function begunStatuses()
    {
        return array('late.registration', 'in.progress');
    }

    /**
     * Check if the provided tournament is in progress.
     *
     * @param array $tournament - tournament data
     * @return bool
     */
    public function inProgress($tournament)
    {
        return in_array($tournament['status'], $this->begunStatuses());
    }

    public function openForRegStatus($t){
        return in_array($t['status'], ['late.registration', 'registration.open']);
    }

    //no rc
    /**
     * Called every minute by tournament_cron.php
     * - Update the tournament status based on the tournament time parameters;
     * Ex. if ($now >= $reg_open && $now < mtt_start) { $status = 'registration.open'; }
     *
     * - Check if some tournament doesn't meet the minimum requirement and cancel them (we ignore less than 0 as that is caused by concurrency issues with registered players)
     * Ex. if ($t['registered_players'] < $t['min_players']) { $this->cancel($t);}
     *
     * - (if tournament begin) Update tournament_entries (enable the player to play) and set jokers & bounty
     * - Check if some begun tournament is finished and end the tournament
     *
     * check the wiki https://wiki.videoslots.com/index.php?title=Battle_of_Slots_counters
     * for a deeper explanation of all the status based on time/dates
     *
     * @param string $now
     */
    function statusesCron($now = '')
    {
        $now = empty($now) ? time() : $now;
        $in_statuses = phive("SQL")->makeIn(array('finished', 'cancelled'));
        $where = " status NOT IN($in_statuses) AND start_format = 'mtt'";
        $begun_statuses = $this->begunStatuses();

        foreach ($this->getAllWhere($where) as $t) {

            $new_status = $t['status'];
            $old_status = $t['status'];
            $reg_open_str = phive()->hisMod("-{$t['mtt_reg_duration_minutes']} minute", $t['mtt_start']);
            $late_reg_str = phive()->hisMod("+{$t['mtt_late_reg_duration_minutes']} minute", $t['mtt_start']);
            $sstamp = strtotime($t['mtt_start']);
            $reg_open = strtotime($reg_open_str);
            $late_reg = strtotime($late_reg_str);

            if ($now >= $reg_open && $now < $sstamp) {
                $new_status = 'registration.open';
            } else if ($now >= $sstamp && $now < $late_reg) {
                $new_status = 'late.registration';
                /*
                */
            } else if ($now >= $sstamp) {
                $new_status = 'in.progress';
            }

            if (empty($new_status)) {
                continue;
            }

            $t['status'] = empty($new_status) ? $t['status'] : $new_status;

            if (in_array($new_status, $begun_statuses) && !in_array($old_status, $begun_statuses)) {
                if (phMget("tournament-started".$t['id'])) {
                    continue;
                }
                phMset("tournament-started".$t['id'], true, 36000);
                if ($t['registered_players'] >= 0 && $t['registered_players'] < $t['min_players']) {
                    phive('Logger')
                        ->getLogger('bos_logs')
                        ->error("Doing cancel for tournament #{$t['id']} from statusesCron() method", [
                            'tournament_id' => $t['id'],
                            'registered_players' => $t['registered_players'],
                            'status'             => $t['status'],
                        ]
                    );
                    $this->cancel($t);
                    continue;
                }
                $t['start_time'] = $t['mtt_start'];
                //We have to save tournament status before we open up all entries to avoid them seeing a non-open tournament on game page load
                $this->save($t);
                $this->setEntriesStatus($t, 'open');

                // make lottery if applicable for battle of jokers
                if (in_array($old_status, ['upcoming', 'registration.open']) && $new_status == 'late.registration') {
                    if ($t['number_of_jokers'] > 0 && $t['registered_players'] >= $t['min_players']) {
                        $entries = $this->entries($t);
                        shuffle($entries);
                        $jokers = array_slice($entries, 0, $t['number_of_jokers']);
                        foreach ($jokers as $jokerEntry) {
                            $jokerEntry['joker'] = true;
                            $this->saveEntry($jokerEntry);
                        }
                    }
                }

                // if tournament just has started we'll handle the bounty stuff
                if (!empty($t['bounty_award_id']) && in_array($old_status, ['upcoming', 'registration.open']) && in_array($new_status, $begun_statuses)) {
                    $this->setBountyGuysForTournament($t['id']);
                }

                $this->initMem($t);
            } else {
                $this->save($t);
            }
            if($new_status != $old_status) {
                $tournament_data = [
                    'status' => $t['status']
                ];

                if($new_status !== 'late.registration') {
                    $tournament_data['start_time'] = $t['start_time'];
                    $tournament_data['start_format'] = $t['start_format'];
                    $tournament_data['mtt_start'] = $t['mtt_start'];
                }

                $this->wsTmainLobby($t['id'], $tournament_data);
            }
        }

        $in_statuses = phive("SQL")->makeIn($begun_statuses);
        $where = " status IN($in_statuses) ";
        foreach ($this->getAllWhere($where) as $t) {
            $estamp = $this->getEndTime($t);
            // TODO change the below check to only exclude tournaments in the late reg period or the rebuy period
            // TODO the || part with != 'mtt' will never execute as we're not getting SNGs anyway? /Henrik
            if ($now >= $estamp || ($this->totalSpinsLeft($t) === 0 && $t['start_format'] != 'mtt')) {
                $this->endTournament($t, $estamp);
            }
        }
    }

  //no rc
  function spotSuffix($n, $ends = array('th','st','nd','rd','th','th','th','th','th','th')){
      return ((($n % 100) >= 11) && (($n%100) <= 13)) ? 'th' : $ends[$n % 10];
  }

    function spotSuffix2($n)
    {
        $prefixes = array('first', 'second', 'third', 'other');
        $prefix_alias = array('mp.first.prize', 'mp.second.prize', 'mp.third.prize', 'mp.other.prize');
        $results = phive('Localizer')->getRawStringFromPrefixes(phive('Localizer')->getLanguage(), $prefixes);
        $resultsMap = [];

        //map alias to value for constant time lookup
        foreach ($results as $result) {
            $resultsMap[$result["alias"]] = $result["value"];
        }

        foreach ($prefix_alias as $key => $alias) {
            if (isset($resultsMap[$alias])) {
                $prefixes[$key] = t($alias);
            } else {
                $prefixes[$key] = '';
            }
        }

        $map = array($prefixes[3],$prefixes[0],$prefixes[1],$prefixes[2],$prefixes[3],$prefixes[3],$prefixes[3],$prefixes[3],$prefixes[3],$prefixes[3]);
        return $this->spotSuffix($n, $map);
    }

  function updateEntries($t, $updates, $where_extra = ''){
      $where = "t_id = {$t['id']} $where_extra";
      return $this->db->shs('', '', null, 'tournament_entries')->updateArray('tournament_entries', $updates, $where);
  }

  function isCalculating(&$t){
    return ($t['status'] == 'finished' && empty($t['prizes_calculated']));
  }

  function winEvent(&$t, $spot, $ud){
    $tag = "mpwin";
    $spot = $spot.$this->spotSuffix($spot);
    uEvent($tag, $spot, $t['tournament_name'], $t['id'], $ud);
  }

  function startWs($t, $e){
    $game = $this->getGame($t);
    $ud = ud($e['user_id']);
    $html = phive('BoxHandler')->getRawBoxHtml('TournamentBox', 'prMpStartMsg', $t, $e, $game, $ud['preferred_lang']);
    $this->log('start');
    toWs(array('html' => $html,'tournament_id' => $t['id']), 'mp-start', $e['user_id']);
  }

    function startEvents($t, $e){
        $battle_start_deply = phive('Tournament')->getSetting('delay_in_battle_start_popup');

        phive()->fire('tournament', 'tournamentStartEvent', [$t, $e],$battle_start_deply, function() use ($t, $e){
            phive('Events/NotificationEventHandler')->onTournamentStartEvent($t, $e);
        }, $e['user_id']);
    }

  function limitEvent($t, $e, $limit){
    $ud = ud($e['user_id']);
    phive('Casino')->pexecLimit($ud, $limit, $t['game_ref'], 'no', $e['id']);
  }

    /**
     * This function update the status of all the entries for a tournament and triggers the proper notification via WS
     * - tournament has started
     * - tournament is finished
     * - tournament is cancelled
     * @param $t
     * @param $status
     * @return mixed
     */
    function setEntriesStatus($t, $status)
    {
        $t = is_array($t) ? $t : $this->byId($t); // TODO the $t is always a tournament array, can we remove this extra check?
        $res = $this->updateEntries($t, array('status' => $status), " AND status != 'cancelled'");
        $entries = $this->entries($t);
        switch ($status) {
            case 'open':
                foreach ($entries as $e) {
                    $this->startEvents($t, $e);
                }
                break;
            case 'finished':
                $this->_addTournamentDisplayInfo($t);
                $tournament_data = [
                    'status' => $t['status'],
                    'enrolled_user' => $this->displayRegs($t),
                    'action_button_action' => $t['action_button_action'],
                    'action_button_alias' => $t['action_button_alias'],
                ];
                $this->wsTmainLobby($t['id'], $tournament_data);
                foreach ($entries as $e) {
                    $this->limitEvent($t, $e, 'mp.finished');
                }
                break;
            case 'cancelled':
                foreach ($entries as $e) {
                    $this->limitEvent($t, $e, 'mp.cancelled');
                }
                break;
        }
        return $res;
    }

  function getPrizeList($t, $cnt = 15, $return_short_version = false){
    if($this->upcomingAwardPrize($t) || ($this->isAwardPrize($t) && $return_short_version)) {
      return $this->getPrizeAwards($t);
    }else{
      if(empty($t['award_ladder_tag'])){
        if(empty($cnt))
          $cnt = $t['max_players'];
        else if(!empty($t['registered_players']))
          $cnt = $t['registered_players'];
      }else{
        $this->getPrizeAwards($t);
        $tmp = $this->getCachedAwardLadder($t);
        $last = end($tmp);
        $cnt = $last['end_spot'];
      }
      $lst = array();
      for($i = 1; $i <= $cnt; $i++){
        $tmp         = empty($t['award_ladder_tag']) ? $this->getPrizePercent($t, $i, true) : $this->getPrizeAward($t, $i);
        $tmp['spot'] = $i;
        $lst[]       = $tmp;
      }
      return $lst;
    }
  }

    function sortLeaderboard(&$t, $entries){
        $sort_by                = $t['win_format'] == 'thw' ? array('win_amount', 'cash_balance', 'highest_score_at') : array('win_amount', 'biggest_win', 'updated_at');
        return phive()->sort2d($entries, $sort_by, array('desc', 'desc', 'asc'));
    }

    /**
     * Will return all the tournament_entries for a Tournament.
     * The data can comes from the DB or Redis.
     * IMPORTANT: the tournament_entries data from DB and Redis is slightly different.
     *
     * @param $t - tournament object
     * @param bool $apply_limit - to limit the results from the leaderboard to "show_top_battle_count" (NOT USED ATM...)
     * @param bool $get_from_mem - TRUE = fetch the leaderboard from Redis (if available), FALSE = Always hit the DB.
     * @return mixed - An array of tournament_entries;
     */
    function getLeaderBoard($t, $apply_limit = true, $get_from_mem = false){

        // config ('websockets', 'show_top_battle_count') IS NOT ON LIVE AT ALL
        // This means $top_count will always be null
        $top_count = $apply_limit ? phive('Config')->getValue('websockets', 'show_top_battle_count') : null;

        list($mem_key, $stamps_mem_key) = $this->getLboardKeys($t);

        if($get_from_mem){
            $res = $this->mc->getJson($mem_key);
            if(!empty($top_count))
                $res = array_slice($res, 0, $top_count);
        }

        $sql_limit = '';
        if(!empty($top_count)) {
            $sql_limit  = "LIMIT 0, $top_count";
        }

        if(empty($res)){
            $limit = empty($top_count) ? '' : "LIMIT 0, $top_count";
            $str = "SELECT * FROM tournament_entries WHERE t_id = {$t['id']} ORDER BY win_amount DESC $sql_limit";
            $res = $this->db->shs('merge', 'win_amount', 'desc', 'tournament_entries')->loadArray($str);
        }

        $leaderboard = $this->sortLeaderboard($t, $res);

        return $leaderboard;
    }

    function getPrizePercent($t, $place, $ret_row = false){
        //this has to be max players if we want to show something before anyone is registered
        $rp = $t['registered_players'];
        if(empty($this->ladder))
            $this->ladder = $this->db->loadArray("SELECT * FROM tournament_ladder WHERE tag = '{$t['ladder_tag']}'");

        $ladder_max_rp = max(array_column($this->ladder, 'players_max'));

        // Do we have more registered players than the ladder can handle?
        // If so we default to the ladder max.
        $rp = $rp > $ladder_max_rp ? $ladder_max_rp : $rp;

        // Player position is outside of the ladder bounds, if that happens he automaticlly gets nothing.
        if($place > $rp)
            return $ret_row ? array() : 0;

        foreach($this->ladder as $l){
            if($l['start_spot'] <= $place && $l['end_spot'] >= $place && $l['players_min'] <= $rp && $l['players_max'] >= $rp)
	        return $ret_row ? $l : $l['percentage'];
        }
        return $ret_row ? array() : 0;
    }

  /*
     Not used but don't remove yet
  function transactSp($u, $t, $amount, $type, $spamount){
    $u = is_numeric($u) ? phive("UserHandler")->getUser($u) : $u;
    $u->incSp($spamount);
    phive("Cashier")->insertTransaction($u, $amount, $type, $this->trDescr($t));
  }
  */

  function chgFromUsr($u, $amount, $m = 1){
      $cur = empty($u) ? ciso() : $u->getCurrency();
      return chg($cur, $this->tCur(), $amount, $m);
  }

  function chgToUsr($u, $amount, $m = 0.99){
    $cur = empty($u) ? ciso() : $u->getCurrency();
    return chg($this->tCur(), $cur, $amount, $m);
  }

  //rc done
  function getTotalWon(&$t){
    return $this->sumEntries($t, 'win_amount');
  }

  function findPosition(&$lboard, &$ud){
    foreach($lboard as $i => $r){
      if($r['user_id'] == $ud['id'])
        return array($i + 1, $r);
    }
    return array();
  }

  function totalPrizeAmount($t_or_id, $add_cash_balance = true){
      $t = is_array($t_or_id) ? $t_or_id : $this->byId($t_or_id);
      if(!empty($t['award_ladder_tag']))
          return -1;
      $g_amount = $t['guaranteed_prize_amount'];
      $p_amount = $t['prize_amount'];
      switch($t['prize_type']){
      case 'cash-balance':
          $tot = $p_amount + $this->cashBalance($t);
          break;
      case 'cash-fixed':
          $tot = $p_amount;
          break;
      case 'win-static':
          $tot = $g_amount;
          break;
      case 'win-fixed':
          $tot = $p_amount;
          break;
      case 'win-prog':
          if($t['status'] == 'finished')
              $tot = $p_amount;
          else
              $tot = $p_amount + $this->getTotalWon($t) + ($add_cash_balance ? $this->cashBalance($t) : 0);
          break;
      }

      // Added with guaranteed is guaranteed amount + buyins
      if($t['category'] == 'added'){
          return $tot + $g_amount;
      }

      return $tot < $g_amount ? $g_amount : $tot;
  }

  function getGame(&$t){
    return phive('MicroGames')->getByGameRef($t['game_ref']);
  }

  function getGameWithEntry($e){
      if(empty($e)){
          error_log('Caught error: entry missing for Tournament::getGameWithEntry()');
          // We need an entry in order to get the game, if we don't have one we return immediately with an empty array.
          return [];
      }
    $str = "SELECT * FROM micro_games WHERE device_type = 'flash' AND ext_game_name IN(SELECT game_ref FROM tournaments WHERE id = {$e['t_id']})";
    return $this->db->loadAssoc($str);
  }

  function img(&$t){
    fupUri("tournaments/{$t['game_ref']}.jpg");
  }

  function getTournamentUri($t){
    return fupUri("tournaments/{$t['game_ref']}.jpg", true);
  }

  function canGetPrize(&$e, &$t){
    //TODO check if cash balance should be allowed to be non-zero in case of win-prog
    if($e['turnover'] < $t['turnover_threshold'])
      return false;
    return true;
  }

  function getRegTimeCommon(&$t, $col_name, $status, $op = '-', $as_str = false){
    if($t['start_format'] == 'mtt' && $t['status'] == $status){
      $time = strtotime(phive()->hisMod("$op{$t[$col_name]} minutes", $t['mtt_start']));
      return $as_str ? date('M j H:i', $time) : $time;
    }
    return false;
  }

  function getRegStartTime(&$t, $as_str = false){
    return $this->getRegTimeCommon($t, 'mtt_reg_duration_minutes', 'upcoming', '-', $as_str);
  }

  function getLateRegEndTime(&$t, $as_str = false){
    return $this->getRegTimeCommon($t, 'mtt_late_reg_duration_minutes', 'late.registration', '+', $as_str);
  }

  function getEndTime(&$t){
    return strtotime(phive()->hisMod("+{$t['duration_minutes']} minutes", $t['start_time']));
  }

  function hasEnded(&$t){
    return time() >= $this->getEndTime($t);
  }

    function returnCashBalance(&$u, &$t, &$e, $msg = ''){
        if(!empty($e['cash_balance'])){
            $ttype = $t['category'] != 'freeroll' ? 48 : 59;
            //We've got a non cash tournament that is not a freeroll, should not currently happen
            if($ttype == 48 && $t['prize_type'] != 'cash-fixed')
                return;
            $this->transactUser($t, $u, $e['cash_balance'] / $t['spin_m'], $ttype, 'cash', true, $msg);
        }
    }

  function isCalculated(&$t){
    return !empty($t['prizes_calculated']);
  }

  function wsCalcPrizeFinished(&$ud, &$t, &$e){
    phive('Casino')->pexecLimit($ud, 'mp.finished', $t['game_ref'], 'no', $e['id'], 'mpcalculated');
  }

  function setPrizeAmount(&$entries, &$t){
    if(in_array($t['prize_type'], array('cash-balance', 'win-prog')))
      $t['prize_amount'] += phive()->sum2d($entries, 'cash_balance') / $t['spin_m'];

    if($t['prize_type'] == 'win-prog')
      $t['prize_amount'] += phive()->sum2d($entries, 'win_amount') / $t['spin_m'];
  }

    //Only the cron is respecting pause_calc, if this function is called somewhere else we assume a forced calculation is wanted

    /**
     * @param $t
     * @param $skip_check
     * @return array|false|string|void
     * @throws Exception
     */
    function calcPrizes($t, $skip_check = false){
        $this->ladder = [];
        //TODO the below becomes redundant when the dedicated tournament machine is up and running
        $t = is_numeric($t) ? $this->byId($t) : $t;
        if (phMget('prizes-calculated'.$t['id'])) {
            return false;
        }
        phMset('prizes-calculated'.$t['id'], true, 36000);

        dumpTbl("calc-prizes-1", "start", $t['id']);
        phive('Logger')->getLogger('bos_logs')->info("tournament", $t);

        if(!$skip_check && !empty($t['prizes_calculated'])) {
            phive('Logger')->getLogger('bos_logs')->info("skipping", $t);
            return;
        }
        $t['prizes_calculated'] = 1;
        $this->save($t);
        dumpTbl("calc-prizes-2","flag-set-prizes_calculated", $t['id']);
        $tpl                    = $this->getParent($t);
        $users                  = array();
        $entries                = $this->sortLeaderboard($t, $this->entries($t));

        phive('Logger')->getLogger('bos_logs')->info("tournament_entries", $entries);

        foreach($entries as $e)
            $users[$e['id']] = cu($e['user_id']);

        if(!empty($t['award_ladder_tag'])){
            $res = array();
            foreach($entries as $i => $e) {
                if(!$this->canGetPrize($e, $t))
                    continue;
                $this->wsCalcPrizeFinished($ud, $t, $e);
                $u      = $users[$e['id']];
                $ud     = $u->data;
                $place 	= $i + 1;
                $this->winEvent($t, $place, $ud);
                $a      = $this->getPrizeAward($t, $place, $e['user_id']);
                $res[]  = $a;
                $won_str = empty($a) ? 'nothing' : $a['alias'];

                phive('UserHandler')->logAction($u, "Won: $won_str, Position: $place, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'battle');

                phive('Trophy')->giveAward($a, $ud);
                $e['result_place'] = $place;

                $this->saveEntry($e);
                $this->returnCashBalance($u, $t, $e);
            }
            $this->save($t);
            $this->handleBountyPrize($t);
            dumpTbl("calc-prizes-3-end","awards-given", $t['id']);
            return $res;
        }

        $this->setPrizeAmount($entries, $t);

        if($t['category'] == 'added' && !empty($t['guaranteed_prize_amount'])){
            // Prize diff is the whole guaranteed as that is now regarded as the added amount.
            $prize_diff 	= $t['guaranteed_prize_amount'];
            // The prize amount is now the whole total of guaranteed and buy ins.
            $prize_amount 	= $t['guaranteed_prize_amount'] + $t['prize_amount'];
        }else if(!empty($t['guaranteed_prize_amount']) && $t['guaranteed_prize_amount'] > $t['prize_amount']){
            $prize_diff 	= $t['guaranteed_prize_amount'] - $t['prize_amount'];
            $prize_amount 	= $t['guaranteed_prize_amount'];
        }else
            $prize_amount 	= $t['prize_amount'];

        dumpTbl("calc-prizes-3","calc-prize-amount-init", $t['id']);
        foreach($entries as $i => $e){
            if(!$this->canGetPrize($e, $t))
                continue;
            $this->wsCalcPrizeFinished($ud, $t, $e);
            $u = $users[$e['id']];
            $place 		= $i + 1;
            $this->winEvent($t, $place, $u->data);
            $percent 	       = $this->getPrizePercent($t, $place);
            $prize 	       = floor($percent * $prize_amount);
            $prize_changed     = floor($this->chgToUsr($u, $prize));
            $e['won_amount']   = $prize;

            phive('UserHandler')->logAction($u, "Won: $prize, Position: $place, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'battle');

            if($t['prize_type'] == 'cash-fixed')
                $this->returnCashBalance($u, $t, $e);
            $e['result_place'] = $place;
            if(!empty($prize))
                $this->handleCashPrize($u, $prize_changed, $t, $prize_diff, $percent, $prize, $e);
            $this->saveEntry($e);
        }
        dumpTbl("calc-prizes-4","calc-prize-amount-end", $t['id']);
        $this->save($t);
        $this->handleBountyPrize($t);
        // TODO this should not be necessary, winnings and bets should always update the in memory representation
		// I got a big win on the last spin and it didn't register in the in-memory leaderboard /Henrik
        $this->initMem($t, false);
        dumpTbl("calc-prizes-5-end","tournament-updated", $t['id']);
        return $t;
    }

  function upcomingAwardPrize(&$t){
    return !empty($t['award_ladder_tag']) && $this->isUpcoming($t);
  }

  function isAwardPrize($t) {
      return !empty($t['award_ladder_tag']);
  }

  function getCachedAwardLadder($t){
    return $this->award_ladder[$t['id']];
  }

  function getPrizeAwards($t, $user_id = null){
      $current_user_country = phive('Licensed')->getLicCountry(cu($user_id));

      $tournament_award_ladder = $this->db->loadArray("SELECT * FROM tournament_award_ladder WHERE tag = '{$t['award_ladder_tag']}'");
      $in_str = $this->db->makeIn(phive()->arrCol($tournament_award_ladder, 'award_id', 'award_id'));
      $awards = $this->db->loadArray("SELECT * FROM trophy_awards WHERE id IN($in_str)", 'ASSOC', 'id');

      $awards = $this->checkAwards($awards, $tournament_award_ladder, $current_user_country);

      phive()->addTo2d($awards, $tournament_award_ladder, 'award_id', 'award');
      $this->award_ladder[$t['id']] = $tournament_award_ladder;

    return $this->award_ladder[$t['id']];
  }

    /**
     * Returning awards after checking foreach if it is allowed in a country, if not returns the alternative award.
     *
     * @param $current_awards
     * @param $tournament_award_ladder
     * @param $user_country
     *
     * @return array
     */
    public function checkAwards($current_awards, $tournament_award_ladder, $user_country)
    {
        $alternative_award_ids = $this->db->makeIn(phive()->arrCol($tournament_award_ladder, 'alternative_award_id', 'alternative_award_id'));
        $alternative_awards = $this->db->loadArray("SELECT * FROM trophy_awards WHERE id IN($alternative_award_ids)", 'ASSOC', 'id');

        $awards = [];

        foreach ($current_awards as $award_key => $award) {
            $is_country_excluded = $this->isCountryExcluded($award['excluded_countries'], $user_country);

            if ($is_country_excluded) {
                $key = array_search($award['id'], array_column($tournament_award_ladder, 'award_id'));
                $current_tournament_award_ladder = $tournament_award_ladder[$key];

                $alternative_award = $alternative_awards[$current_tournament_award_ladder['alternative_award_id']];

                if ($alternative_award) {
                    $awards[$award_key] = $alternative_award;
                } else {
                    $awards[$award_key] = $award;
                }
            } else {
                $awards[$award_key] = $award;
            }
        }

        return $awards;
    }

    /**
     * @param string $excluded_countries
     * @param $country
     *
     * @return bool
     */
    public function isCountryExcluded(string $excluded_countries, $country)
    {
        if (strpos($excluded_countries, $country) !== false) {
            return true;
        }

        return false;
    }

  //no rc
  function getPrizeAward(&$t, $place, $user_id = null){
    $awards = $this->getPrizeAwards($t, $user_id);
    foreach($awards as $l){
      if($l['start_spot'] <= $place && $l['end_spot'] >= $place)
	return $l['award'];
    }
    return false;
  }

    /**
     * @param array $u User
     * @param integer $prize_changed The amount in user's currency
     * @param array $t Tournament
     * @param integer $prize_diff
     * @param float $percent
     * @param integer $prize The amount in Tournament base currency
     */
  function handleCashPrize($u, $prize_changed, $t, $prize_diff, $percent, $prize, $entry = null){
    if(!empty($t['award_sub_type'])){
      /*
      Not used at the moment but don't remove yet
      $ud = $u->data;
      foreach($this->getPrizeAwards($t, $prize) as $a){
        phive('Trophy')->giveAward($a, $ud);
        phive('UserHandler')->logAction($u, "Won {$a['description']} in tournament: {$t['tournament_name']}", 'tournament-prize');
      }
      */
        } else {
            $t_id = is_array($t) ? $t['id'] : $t;
            if ($entry['joker'] && $prize_changed > 0 && (int)$entry['result_place'] === 1) {
                // additional joker prize. The rest of the code will add one winnings so this is how we double up joker prize
                phive("QuickFire")->changeBalance($u, $prize_changed, $this->trDescr($t), 85, '', 0, 0, false, 0, '', (int)$t_id); // Tournament prize double up - Joker
            }
            phive("QuickFire")->changeBalance($u, $prize_changed, $this->trDescr($t), 38, '', 0, 0, false, 0, '', (int)$t_id);
            $diff_amount = ceil($this->chgToUsr($u, $prize_diff * $percent));
            if (!empty($diff_amount)) {
                phive("Cashier")->insertTransaction($u, $diff_amount, 41, $this->trDescr($t));
            }
        }
    }

  function saveEntry($e){
    return phive("SQL")->sh($e)->save('tournament_entries', $e);
  }

    public function countTournamentWithTemplateId($tpl_id){
        phive("SQL")->query("SELECT COUNT(id) FROM tournaments WHERE tpl_id = ".phive("SQL")->escape($tpl_id));
        return phive("SQL")->result();
    }

    function initSng($now = ''){
        if(empty($now))
            $now = time();
        // TODO Fold has expired filtering into the query
        foreach($this->_getTplsWhere(array('start_format' => 'sng')) as $tpl){
            $count = $this->countTournamentWithTemplateId($tpl['id']);
            if($tpl['recur'] == 0 && $count> 0) {
                continue;
            }

            if($this->hasExpired($tpl))
                continue;
            $sng_start = $tpl['mtt_start_date'].' '.$tpl['mtt_start_time'];
            // If the start is 0000-00-00 00:00:00 we default to the old behaviour, ie don't create if something already exists.
            if(phive()->isEmpty($sng_start) || $this->getSetting('old_init') === true){
                $active    = $this->getAllWhere("tpl_id = {$tpl['id']}");
                if(empty($active)) {
					if (!$this->checkSngLimit($tpl)) {
						continue;
					}

					$this->insertTournament($tpl);
				}
            }else{
                $statuses  = $this->getInactiveStatuses(true);
                $active    = $this->getAllWhere("tpl_id = {$tpl['id']} AND status NOT IN($statuses)");
                // We create a new SNG tournament if:
                // 1.) We do not already have tournaments which are not cancelled or finished.
                // 2.) The start stamp is less than now.
                if(empty($active) && strtotime($sng_start) <= $now) {
					if (!$this->checkSngLimit($tpl)) {
						continue;
					}

					$this->insertTournament($tpl);
				}
            }
        }
    }

  function getPlayUrl(&$e){
    if(empty($e['game_url'])){
      $game = $this->getGameWithEntry($e);
      $game_url = $game['game_url'];
    }else
    $game_url = $e['game_url'];
    $ud = cu($e['user_id'])->data;
    return "/{$ud['preferred_lang']}/play/$game_url/?eid={$e['id']}";
  }

  //no rc - both
    function canResume($e){
      return $e['status'] == 'open';
    }

    function entryIsOpen($e){
        return $e['status'] == 'open';
    }

    function canCancel(&$t, $u, $e = array()){
       // if(empty($e))
       //     return false;
        if($this->hasStarted($t)) {
            return false;
        }

        $u = empty($u) ? cuPl() : $u;
        if(empty($e)) {
            $e = $this->entryByTidUid($t['id'], $u->getId());
        }
        // Even if the entry status is finished we will not allow the player to unregister
        if(empty($e) || $e['status'] == 'cancelled' || $e['status'] == 'finished') {
            return false;
        }
        return true;
  }

    //mp-ticket responsible for the change from where array to string, ie t-X can now also be t-X-aid-Y
    //sharding current position
    function getTransactions($tid, $uid){
        $tr_descr = $this->trDescr($tid);
        $where = "(description LIKE '$tr_descr' OR description LIKE '{$tr_descr}-%') AND description NOT LIKE '%-cancelled' AND user_id = $uid";
        return phive("SQL")->sh($uid)->arrayWhere("cash_transactions", $where);
    }

    function incr($t, $col, $amount, $extra = array()){
        //return $this->db->shs('', '', null, 'tournaments')->incrValue('tournaments', $col, array('id' => $t['id']), $amount, $extra);
        return $this->db->incrValue('tournaments', $col, array('id' => $t['id']), $amount, $extra);
    }

    //no rc
    public function undoTransactions($t, $uid = '', $do_only = array(), $undo_non_credits = true)
    {
        $cts = $this->getTransactions($t['id'], $uid);
        $t_id = is_array($t) ? $t['id'] : $t;
        $real_types = array(34 => 61, 35 => 62, 52 => 63, 54 => 64);
        $do_only = empty($do_only) ? array_keys($real_types) : $do_only;
        foreach ($cts as $ct) {
            /*
            if (in_array($ct['transactiontype'], array(37))) {
                //$this->transactSp($ct['user_id'], $t, abs($ct['amount']), $ct['transactiontype'], $t['cost']);
            }*/

            if (in_array($ct['transactiontype'], $do_only)) {
                phive("Cashier")->undoTransaction($ct, true, $real_types[$ct['transactiontype']], $t_id);
            }

            if ($undo_non_credits) {
                if (in_array($ct['transactiontype'], array(55, 57, 58))) {
                    phive("Cashier")->undoTransaction($ct, false, '', $t_id);
                }
            }
        }
        return $cts;
    }

  function deleteEntry($e){
    return $this->db->delete('tournament_entries', array('id' => $e['id']), $e['user_id']);
  }

  //no rc - both
  //mp-ticket
  function getAwardIdFromTr($tr){
    $arr = explode('-', $tr['description']);
    $current = true;
    while($current){
      $current = next($arr);
      if($current == 'aid')
        return next($arr);
    }
    return false;
  }

  function getDailyEntryIds($stamp){
      $str = "SELECT e.id FROM tournaments t, tournament_entries e WHERE DATE(t.calc_prize_stamp) = DATE('$stamp') AND e.t_id = t.id";
      //TODO test this
      return $this->db->shs('merge', '', null, 'tournament_entries')->loadCol($str, 'id');
  }

  /*
     Income:
     bets (bets_mp)
     freeroll win (te)
     freeroll cash balance (te)
     fixed cash balance (te)
     fixed wins (te)
     house fe (ct)

     Costs:
     wins (wins_mp)
     op fee (bets_mp)
     freeroll buy in (t cost)
     freeroll re-buy (t.rebuy_times - te.rebuy_times) * t.rebuy_cost
     fixed re-buy (t.rebuy_times - te.rebuy_times) * t.rebuy_cost
     guaranteed diff (ct)
     fixed buy in (t cost)
     free pot cost (ct)

     ------------------------

     Win fixed heads up x2 10 eur buy in loss example:
     Bets: (10 * 2) + (5 * 2) = 30
     Wins: 0
     Cash balance: 10
     Op fee: (30 - 0) * 0.14 = 4.2
     Buy in: 20
     Wins: 0
     House fee: 2
     Hyp profit: 30 - 0 - 4.2 + 10 - (20 * 2) + 0 + 2 = -2.2
     Actual profit: 2 - 4.2 = -2.2
     NOTE: We need to multiply buyin with tournament spin_m


     Win fixed heads up x2 10 eur buy in normal example:
     Bets: (10 * 2) + (5 * 2) = 30
     Wins: 30 * 0.96 = 28.8
     Cash balance: 10
     Op fee: (30 - 28.8) * 0.14 = 0.168
     Buy in: 20
     Wins: 28.8
     House fee: 2
     Hyp profit: 30 - 28.8 - 0.168 + 10 - (20 * 2) + 28.8 + 2 = 1.832
     Actual profit: 2 - 0.168 = 1.832
     NOTE: We need to multiply buyin with tournament spin_m

     Win prog heads up x1 10 eur buy in example:
     Bets: 2 * 10 = 20
     Wins: 20 * 0.96 = 19.2
     Op Fee: (20 - 19.2) * 0.14 = 0.112
     House fee: 1
     Hyp profit: 20 - 19.2 - 0.112 + 1 = 1.688
     Actual profit: 1 - 0.112 + (20 - 19.2) = 1.688

     Win prog freeroll heads up x1 10 eur buy in example:
     Bets: 2 * 10 * 2 = 20
     Wins: 20 * 0.96 = 19.2
     Op fee: (20 - 19.2.) * 0.14 = 0.112
     Buy in: 20
     Wins: 19.2
     Hyp profit: 20 - 19.2 - 0.112 - 20 + 19.2 = -0.112
     Actual profit: -0.112
   */
    function calcDailyStats($date, $db = null){
        if(empty($db))
            $db = phive('SQL');

        $ts = $db->loadArray("SELECT * FROM tournaments WHERE DATE(calc_prize_stamp) = '$date'");
        //phive('SQL')->truncate('bets_mp_tmp', 'wins_mp_tmp');
        foreach($ts as $t)
            $this->calcTournamentStats($t, $date, $db);
    }

    function calcTournamentStats($t, $date, $db = null){

        $date   = empty($date) ? phive()->yesterday() : $date;
        $tid    = $t['id'];

        $sql    = phive('SQL');
        $taxmap = phive('Cashier')->getTaxMap();
        $trs    = $db->loadArray("SELECT * FROM cash_transactions WHERE description LIKE 'tid-$tid' AND description NOT LIKE '%cancelled%'");

        $trss   = phive()->group2d($trs, 'user_id');

        $bets   = $db->loadArray("SELECT * FROM bets_mp WHERE t_id = $tid");
        $bets   = phive()->group2d($bets, 'user_id');
        $wins   = $db->loadArray("SELECT * FROM wins_mp WHERE t_id = $tid");
        $wins   = phive()->group2d($wins, 'user_id');

        $map    = array(52 => 'house_fee', 59 => 'freeroll_recovered', 55 => 'freeroll_cost', 58 => 'free_pot_cost', 41 => 'guaranteed_cost');

        $convert_fields = array('bets', 'op_fee', 'wins', 'gen_loyalty', 'win_sum', 'cb', 'rebuy_sum', 'buyin_sum', 'mp_adj');
        $mp_cur         = $this->getSetting('currency');
        foreach($trss as $uid => $trs){
            $e        = $this->entryByTidUid($t['id'], $uid);
            $u        = cu($uid);
            $ud       = $u->data;
            $currency = $ud['currency'];
            $res = array(
                't_id'      => $t['id'],
                'e_id'      => $e['id'],
                'user_id'   => $ud['id'],
                'username'  => $ud['username'],
                'firstname' => $ud['firstname'],
                'lastname'  => $ud['lastname'],
                'date'      => $date,
                'currency'  => $currency,
                'mp_adj'    => 0,
                'country'   => $ud['country']
            );
            foreach($trs as $tr){
                $field = $map[$tr['transactiontype']];
                if(!empty($field))
                    $res[$field] += abs($tr['amount']);
            }

            foreach($bets[$uid] as $b){
                $res['bets']        += $b['amount'];
                $res['op_fee']      += $b['op_fee'];
                $res['gen_loyalty'] += $b['loyalty'];
                $res['jp_contrib']  += $b['jp_contrib'];
            }

            foreach($wins[$uid] as $w){
                $res['wins']   += $w['amount'];
                $res['op_fee'] -= $w['op_fee'];
            }

            if($t['category'] == 'freeroll' || in_array($t['prize_type'], ['win-fixed', 'win-static'])){
                $res['cb']        = $e['cash_balance'];
                $res['win_sum']   = $e['win_amount'];
                $res['rebuy_sum'] = ($t['rebuy_times'] - $e['rebuy_times']) * $t['rebuy_cost'] * $t['spin_m'];
                $res['buyin_sum'] = $t['cost'] * $t['spin_m'];
                $res['mp_adj']    = $res['cb'] + $res['win_sum'] - $res['rebuy_sum'] - $res['buyin_sum'];
            }

            foreach($convert_fields as $key)
                $res[$key] = chg($mp_cur, $currency, $res[$key], 1, $date);
            //house fee - op fee
            $res['rewards']     = abs($res['guaranteed_cost']) + abs($res['free_pot_cost']);
            $res['gross']       = $res['bets'] - $res['wins'] - $res['jp_contrib'];
            $res['tax']         = ($res['gross'] - $res['rewards'] + $res['mp_adj'] + $res['house_fee']) * $taxmap[$ud['country']];
            $res['before_deal'] = $res['gross'] + $res['house_fee'] - $res['op_fee'] + $res['mp_adj'] - $res['rewards'] - $res['tax'];
            $res['site_rev']    = $res['before_deal'];

            $actual        = $res['house_fee'] - $res['op_fee'] - $res['rewards'] - $res['tax'];
            $db->insertArray('users_daily_stats_mp', $res);
        }
    }

  /*
  function getStats($date, $where){
    //$where -> t.category = 'freeroll' | t.category != 'freeroll' AND t.prize_type = 'win-fixed'
    if($this->isSlave())
      return $this->masterDo('getStats');
    $site_id = $this->getSiteId();
    $where_site = empty($site_id) ? '' : "AND te.site_id = $site_id";
    $sql = "SELECT
              SUM(te.cash_balance) AS cb_sum,
              SUM(te.win_amount) AS win_sum,
              SUM((t.rebuy_times - te.rebuy_times) * t.rebuy_cost * t.spin_m) AS rebuy_sum,
              SUM(t.cost) * t.spin_m AS buyin_sum,
              t.spin_m,
              te.user_id AS user_id
            FROM tournament_entries te, tournaments t
            WHERE te.t_id = t.id
            AND DATE(t.calc_prize_stamp) = '$date'
            AND $where
            $where_site
            GROUP BY te.user_id";
    return $this->db->loadArray($sql, 'ASSOC', 'user_id');
  }
  */

    function cancelEntry($e, $u, $t, $cancel_popup = true, $in_progr = false){
        $u = empty($u) ? cuPl() : $u;
        if(empty($u))
            return false;
        if(empty($t))
            $t = $this->getByEntry($e);
        if(empty($e))
            $e = $this->entryByTidUid($t['id'], $u->getId());
        if(empty($e))
            return false;

        $himself = empty($u) ? 'By cron' : 'Himself';
        if ($t['registered_players'] <= 0) {
            phive('Logger')
                ->getLogger('bos_logs')
                ->error("User #{$u->getId()} is trying to cancel tournament #{$t['id']} {$himself}",
                    [
                        'registered_players' => $t['registered_players'],
                        'status'             => $t['status'],
                        'tournament_id'      => $t['id'],
                        'user_id'            => $u->getId(),
                    ]
                );
        }

        if($in_progr && !$this->isFreeRoll($t)){
            /*
               tht + cash-balance -> balance + house fee + pot cost
               tht + cash-fixed -> balance + house fee + pot cost
               tht + win-prog -> balance + win amount + pot cost + house fee
               tht + win-fixed -> cost + house fee

               thw + cash-balance -> balance + house fee + pot cost
               thw + cash-fixed -> balance + house fee + pot cost
               thw + win-prog -> den här kombon stöds inte med riktiga pengar fn
               thw + win-fixed -> cost + house fee

               undo:
               35 -> pot cost
               58 -> free pot cost (do not credit player)
               52 -> house fee
               create:
               48 with the balance and credit
               65 with the win amount, credit player, disregard in daily stats as the number is automatically accounted for by way of bets / wins
             */

            //Also reverts the free pot cost transaction, no need to pass that type in explicitly
            $this->undoTransactions($t, $u->getId(), array(35, 52, 54));
            if(in_array($t['prize_type'], ['win-fixed', 'win-static']))
                $this->undoTransactions($t, $u->getId(), array(34), false);
            else
                $this->returnCashBalance($u, $t, $e, ", was cancelled so cash balance was returned.");

            if($t['prize_type'] == 'win-prog' && !empty($e['win_amount']))
                $this->transactUser($t, $u, $e['win_amount'], 65, 'cash', true, ", was cancelled so progressive wins were returned.");

            $e['status'] = 'cancelled';
            $this->saveEntry($e);
        }else{
            if(!$this->canCancel($t, $u, $e))
                return false;
            if($t['registered_players'] < 0){
                phive('Logger')->getLogger('bos_logs')->error("Negative Amount of Registered User on Tournament",
                    [
                        'registered_players' => $t['registered_players'],
                        'start_format'       => $t['start_format'],
                        'status'             => $t['status'],
                        'start_time'         => $t['start_time'],
                        'tournament_id'      => $t['id'],
                        'user_id'            => $u->getId(),
                    ]);
            }
            $this->incr($t, 'registered_players', -1);
            $trs = $this->undoTransactions($t, $u->getId());
            //mp-ticket
            if(in_array($trs[0]['transactiontype'], [74, 97])){
                //loop in case tickets have been used for rebuys -> turned off doesn't work
                //foreach($trs as $tr){
                    //TODO uncomment this when type 73 actually works
                    //phive('Cashier')->insertTransaction($u->getId(), $tr['amount'], 75, "Unreg from tournament: {$t['id']}");
                    $aid = $this->getAwardIdFromTr($trs[0]);
                    phive('Trophy')->giveAward($aid, $u->data);
                //}
            }

            $this->deleteEntry($e);
            if(!empty($t['pot_cost']))
                $this->incr($t, 'prize_amount', -$t['pot_cost']);
            if($t['prize_type'] == 'win-fixed')
                $this->incr($t, 'prize_amount', -$t['cost']);


            $this->wsTopBalance($u);
            if($t['status'] !== 'cancelled') {
                $this->deleteTournamentCache($t['id']);
                $tournament = $this->byId($t['id']);
                $this->wsTmainLobby($t['id'], ['enrolled_user' => $this->displayRegs($tournament)]);
            }
        }

        phive('UserHandler')->logAction($u, "Action: unreg, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'battle');
        $this->mc->del('tournament'.$t['id']);

        if($cancel_popup){
            $this->log('cancelentry');
            toWs(array('action' => 'update', 'tid' => $t['id'], 'type' => 'tournament-cancelled-popup'), 'mp-main-lobby', $u->getId());
            phive('Casino')->pexecLimit($u->data, 'TournamentBox.prMpCancelled.func', $t['game_ref'], 'no', $e['id'], 'mplimit');
        }
        return $e;
    }

  function cancelEntries($entries, $t, $in_progr){
    foreach($entries as $e){
      $u = cu($e['user_id']);
      $this->cancelEntry($e, $u, $t, true, $in_progr);
    }
  }

  function pauseResume($tid, $action){
    $this->db->updateArray('tournaments', array('pause_calc' => $action), array('id' => $tid));
  }

  function resume($tid){
    $this->pauseResume($tid, 0);
  }

  function pause($tid){
    $this->pauseResume($tid, 1);
  }

  //rc done
  function cancel($t){
    //TODO test this with WS, undone transactions etc
    $t = is_array($t) ? $t : $this->byId($t);
    if($t['status'] == 'finished')
      return false;
    $in_progr    = $this->inProgress($t);
    $t['status'] = 'cancelled';
    $this->save($t);
    $entries = $this->entries($t);
    $this->cancelEntries($entries, $t, $in_progr);


    $this->wsTlobby($t['id']);

    $this->deleteTournamentCache($t['id']);
    $t = $this->byId($t['id']);
    $this->_addTournamentDisplayInfo($t);
    $tournament_data = [
      'status' => $t['status'],
      'start_status' => $this->getStartOrStatus($t, true, false, true),
      'enrolled_user' => $this->displayRegs($t),
      'action_button_action' => $t['action_button_action'],
      'action_button_alias' => $t['action_button_alias']
    ];
    $this->wsTmainLobby($t['id'], $tournament_data);
  }

  //rc done
  function entries($t){
    $where = array('t_id' => $t['id']);
    return $this->_getEntriesWhere($where);
  }

  //rc done
  function insertTournament($tpl, $shis = ''){
    $move = array('tournament_name', 'category', 'start_format', 'win_format', 'play_format', 'cost', 'pot_cost', 'house_fee',
		  'xspin_info', 'min_players', 'max_players', 'duration_minutes', 'prize_type', 'mtt_late_reg_duration_minutes',
		  'mtt_reg_duration_minutes', 'guaranteed_prize_amount', 'game_ref', 'min_bet', 'max_bet', 'get_race', 'get_loyalty',
                  'get_trophy', 'rebuy_times', 'rebuy_cost', 'award_ladder_tag', 'duration_rebuy_minutes', 'ladder_tag',
                  'included_countries', 'excluded_countries', 'reg_wager_lim', 'reg_dep_lim', 'reg_lim_period', 'free_pot_cost',
                  'turnover_threshold', 'allow_bonus', 'total_cost', 'rebuy_house_fee', 'spin_m', 'pwd', 'number_of_jokers', 'bounty_award_id',
                  'bet_levels', 'desktop_or_mobile', 'reg_lim_excluded_countries', 'blocked_provinces'
    );

    $t = phive()->moveit($move, $tpl);

    if($tpl['start_format'] == 'mtt'){
      $t['mtt_start']   = $shis;
      $t['status'] 	= 'upcoming';
    }else
      $t['status'] 	= 'registration.open';

      $t['tpl_id'] 	= $tpl['id'];

    $tid = phive("SQL")->insertArray('tournaments', $t);
    phive()->fire('tournament', 'tournamentCreatedEvent', [$tid], 0, function() use ($tid) {
        phive('Events/NotificationEventHandler')->onTournamentCreatedEvent($tid);
    });
    return $tid;
  }

    /**
     * Populate the tournaments table based on tournament_tpls
     * - check for template that are still active
     * - get the $start time/s for each template (1 date for simple tournament or N dates for recur, Ex. day, week, ...)
     * - insert the tournament only if one with the same $tpl['id'] and $start doesn't exist already
     * @param string $shis
     */
    function mttScheduleCron($shis = '')
    {
        $shis = empty($shis) ? phive()->hisNow() : $shis;
        $active = $this->getActiveMttTpls($shis);
        foreach ($active as $tpl) {
            $ehis = phive()->hisMod("+{$tpl['mtt_show_hours_before']} hour", $shis);
            //echo "{$tpl['id']} - {$shis} {$ehis}\n";
            $schedule = $this->getMttSchedule($tpl, $shis, $ehis);
            foreach ($schedule as $start) {
                $t = $this->_getByTpl($tpl['id'], $start);
                if (empty($t)) {
                    $this->insertTournament($tpl, $start);
                }
            }
        }
    }

  function finBkg(){
    fupUri('tournaments/mp-finished-bkg.png');
  }

  function chatId(&$t){
      return mKey($t['id'], 'mp-chats', 'mpchats');
  }

  function getAllChatMsgs(){
    $ret = array();
    foreach($this->mc->asArr('mpchats*') as $sub)
      $ret = array_merge($ret, (array)json_decode($sub, true));
    return phive()->sort2d($ret, 'created_at');
  }

  function getChatContents($t){
      return $this->mc->getJson($this->chatId($t));
  }

    function _setChatContents(&$t, $arr){
        $content = $this->getChatContents($t);
        $this->mc->setJson($this->chatId($t), $arr, $this->getMaxDur());
    }

  function addToChatContents($t, $add){
    $add               = is_string($add) ? json_decode($add, true) : $add;
    $arr               = $this->getChatContents($t);
    $add['created_at'] = time();
    $arr[]             = $add;
    $this->_setChatContents($t, $arr);
  }

    function deleteFromChatContents($t, $message_id) {
        $chat_messages = $this->getChatContents($t);
        $message_to_delete = [
            'id' => $message_id,
            'wstag' => 'update_msg'
        ];
        $message_found = $res = false;
        foreach($chat_messages as $index=>$message) {
            if($message['id'] == $message_id) {
                // found message to delete, we can skip the rest.
                unset($chat_messages[$index]);
                $message_found = true;
                $res = $message['msg'];
                continue;
            }
        }

        if($message_found) {
            // delete the message from redis
            $this->_setChatContents($t, $chat_messages);

            // send a message to WS to delete the messages in realtime
            toWs($message_to_delete, 'mp'.$t['id'], 'na');
            toWs($message_to_delete, 'lobbychat'.$t['id'], 'na');
        }
        return $res;
    }

    function isChatBlocked(&$u){
        if(empty($u))
            return;
        return $u->hasSetting('mp-chat-block');
    }

  //TODO, the interface is on the tournament machine so we need to somehow push to the correct slave
  //the whole admin interface is a PITA, how do we link to the corrent slave etc in it?
  function doChatBlock($uid, $eid, $days = 0)
  {
      $u = cu($uid);
      $u->setSetting('mp-chat-block', 1, false);
      if (!empty($days)) {
          $u->setSetting('mp-chat-block-unlock-date', phive()->hisMod("+$days day"), false);
      } else {
          $u->deleteSetting('mp-chat-block-unlock-date');
      }

      $descr_span = empty($days) ? "permanently" : "$days days";

      phive('UserHandler')->logAction($u, "Chat blocked $descr_span.", 'mp-chat');
  }

    /**
     * Perform a remote chat block action for a user.
     *
     * @param int $uid The user ID.
     * @param int $tournament_id The tournament ID.
     * @param int $days (Optional) Number of days for the chat block. Default is 0.
     *
     * @return bool Whether the chat block action was successful.
     */
    public function doChatBlockRemote($uid, $tournament_id, $days = 0)
    {
        $user = cu($uid);
        $remote_brand = getRemote();
        $remote_user_id = $user->getRemoteId();

        if (in_array(__FUNCTION__, lic('getLicSetting', ['cross_brand'], $this)['methods_to_sync'], true)) {
            try {
                $response = toRemote(
                    $remote_brand,
                    'doChatBlock',
                    [$remote_user_id, $tournament_id, $days]
                );

                $success = $response['success'] ?? false;
                $result_message = $success ? 'true' : 'false';

                phive('UserHandler')->logAction(
                    $user,
                    "Added Chat block to {$remote_brand} for user {$uid} resulted in {$result_message}",
                    'mp-chat'
                );

                return $success;
            } catch (Exception $e) {
                error_log("Error in doChatBlockRemote: " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

  //TODO chat admin is broken in a distributed scenario
  function removeChatBlock(&$u){
    $u->deleteSettings('mp-chat-block', 'mp-chat-block-unlock-date');
  }

  function chatBlockCron(){
    $cstamp = phive()->hisNow();
    foreach(phive('UserHandler')->rawSettingsWhere("setting ='mp-chat-block-unlock-date' AND TIMESTAMPDIFF(MINUTE, value, '$cstamp') > 1") as $s){
      $user = cu($s['user_id']);
      $this->removeChatBlock($user);
    }
  }

    function doChatDelete($uid, $eid, $message_id){
        $u = cu($uid);
        $t = $this->getByEid($eid, $uid);
        $res = $this->deleteFromChatContents($t, $message_id);
        if (!empty($res)) {
            phive('UserHandler')->logAction($u, "Chat message deleted: $res.", 'mp-chat');
        }
    }

    function addChatMessage($tid, $message)
    {
        $tid = (int)$tid;
        if ($this->getSetting('disable_chat') === true) {
            return ['ok'];
        }
        $u = cuPl();

        $ud = $u->data;
        if(!$this->isRegistered($tid, $ud))
            $res = array('nok');
        else{
            $hi       = date('H:i');
            $str_msg  = html_entity_decode($message, ENT_QUOTES);
            $censor   = new CensorWords();
            $langs    = $this->getSetting('enabled_censor_languages',
                ['en-base', 'en-uk', 'en-us', 'es', 'de', 'cs', 'fi', 'it', 'jp', 'fr', 'kr', 'no', 'nl']);
            $censor->setDictionary($langs);
            $clean    = $this->cleanMsg($str_msg);
            $str_msg  = $censor->censorString($clean);
            $e        = $this->entryByTidUid($tid, $ud['id']);
            $msg      = [
                'id' => uniqid(),
                'user_id' => $ud['id'],
                'firstname' => $u->getAlias(true),
                'msg' => $str_msg['clean'],
                'hi' => $hi,
                'tid' => $tid,
                'wstag' => 'umsg',
                'entry_id' => $e['id'],
                // this field is used to determine if the message comes from a banned user, if not empty we will display the message only for the specified user.
                'only' => $this->isChatBlocked($u) || $this->isBannedMessage($str_msg['clean']) ? $ud['id'] : ''
            ];
            $t        = $this->byId($tid);
            $this->addToChatContents($t, $msg);
            toWs($msg, 'mp'.$tid, 'na');
            toWs($msg, 'lobbychat'.$tid, 'na');
            toWs($msg, 'mp-chat-admin', 'na');
            $res = array('ok');
        }

        return $res;
    }

    /**
     * This will check if the message provided contains any of the banned words defined in "banned-chat-words" from admin2
     * The match is done case insensitive, and will match the word even if is contained inside other words
     *
     * Ex.
     * $msg = 'King BaNaNa loves his bananas';
     * $bannedWords = ['banana', 'test'];
     * $matches = ['BaNaNa', 'bananas'];
     * $found = 2;
     * $containsBannedWord = true;
     *
     * @param $msg
     * @return bool
     */
    function isBannedMessage($msg) {
        $containsBannedWord = false;
        $bannedWords = phive('Config')->valAsArray('mp', 'excluded-chat-words', ',');
        $matches = [];
        foreach($bannedWords as $bannedWord) {
            // check for any instance of the word case insensitive
            // Ex.
            $found = preg_match_all('/'.$bannedWord.'/i', $msg, $matches);
            if($found > 0) {
                $containsBannedWord = true;
                break;
            }
        }

        return $containsBannedWord;
    }

  //no rc - both
  function wrapCdown($str, $updown = 'cdown'){
    return ' <span class="minute-'.$updown.'">'.$str.'</span> ';
  }

  //no rc - both
  function prettyTime(&$t, $st = '', $show_in = true){
    if($t['status'] == 'late.registration'){
      $late_reg_end_time = $this->getLateRegEndTime($t);
      $mins              = phive()->subtractTimes($late_reg_end_time, time(), 'm');
      return t('in').$this->wrapCdown($mins).' '.t('min.minute');
    }else if($this->hasStarted($t)){
      $mins = phive()->subtractTimes(time(), strtotime($t['start_time']), 'm');
      return $this->wrapCdown($mins, 'cup').' '.t('minutes.ago');
    }else{
      if($t['start_format'] == 'mtt'){
        $st   = empty($st) ? strtotime($t['mtt_start']) : $st;
        $info = phive()->timeIntervalArr('', time(), $st);
        //$info = phive()->timeIntervalArr('', time(), $this->getRegStartTime($t));
        if($info['hours'] > 0)
          return date('M j H:i', $st);
        return ($show_in ? t('in') : '').$this->wrapCdown($info['mins']).' '.t('min.minute');
      }else
        return 'SNG';
    }
  }

  //no rc
  public function getStartOrStatus($t, $show_in = true, $force_pr_time = false, $return_aliases = false){
    if($this->isRegLate($t)) {
      $late_reg = 'mp.late.registration';
      return $return_aliases ? $late_reg : t($late_reg);
    }

    if($force_pr_time && $this->isClosed($t)) {
      return date('M j H:i', strtotime($t['start_time']));
    }

    $status_alias = 'mp.'.$t['status'];

    if($this->isClosed($t)) {
      return $return_aliases ? $status_alias : t($status_alias);
    } else {
      return $this->prettyTime($t, '', $show_in);
    }

  }

  //no rc
  function containsTLD($string) {
    preg_match(
      "/(AC($|\/)|\.AD($|\/)|\.AE($|\/)|\.AERO($|\/)|\.AF($|\/)|\.AG($|\/)|\.AI($|\/)|\.AL($|\/)|\.AM($|\/)|\.AN($|\/)|\.AO($|\/)|\.AQ($|\/)|\.AR($|\/)|\.ARPA($|\/)|\.AS($|\/)|\.ASIA($|\/)|\.AT($|\/)|\.AU($|\/)|\.AW($|\/)|\.AX($|\/)|\.AZ($|\/)|\.BA($|\/)|\.BB($|\/)|\.BD($|\/)|\.BE($|\/)|\.BF($|\/)|\.BG($|\/)|\.BH($|\/)|\.BI($|\/)|\.BIZ($|\/)|\.BJ($|\/)|\.BM($|\/)|\.BN($|\/)|\.BO($|\/)|\.BR($|\/)|\.BS($|\/)|\.BT($|\/)|\.BV($|\/)|\.BW($|\/)|\.BY($|\/)|\.BZ($|\/)|\.CA($|\/)|\.CAT($|\/)|\.CC($|\/)|\.CD($|\/)|\.CF($|\/)|\.CG($|\/)|\.CH($|\/)|\.CI($|\/)|\.CK($|\/)|\.CL($|\/)|\.CM($|\/)|\.CN($|\/)|\.CO($|\/)|\.COM($|\/)|\.COOP($|\/)|\.CR($|\/)|\.CU($|\/)|\.CV($|\/)|\.CX($|\/)|\.CY($|\/)|\.CZ($|\/)|\.DE($|\/)|\.DJ($|\/)|\.DK($|\/)|\.DM($|\/)|\.DO($|\/)|\.DZ($|\/)|\.EC($|\/)|\.EDU($|\/)|\.EE($|\/)|\.EG($|\/)|\.ER($|\/)|\.ES($|\/)|\.ET($|\/)|\.EU($|\/)|\.FI($|\/)|\.FJ($|\/)|\.FK($|\/)|\.FM($|\/)|\.FO($|\/)|\.FR($|\/)|\.GA($|\/)|\.GB($|\/)|\.GD($|\/)|\.GE($|\/)|\.GF($|\/)|\.GG($|\/)|\.GH($|\/)|\.GI($|\/)|\.GL($|\/)|\.GM($|\/)|\.GN($|\/)|\.GOV($|\/)|\.GP($|\/)|\.GQ($|\/)|\.GR($|\/)|\.GS($|\/)|\.GT($|\/)|\.GU($|\/)|\.GW($|\/)|\.GY($|\/)|\.HK($|\/)|\.HM($|\/)|\.HN($|\/)|\.HR($|\/)|\.HT($|\/)|\.HU($|\/)|\.ID($|\/)|\.IE($|\/)|\.IL($|\/)|\.IM($|\/)|\.IN($|\/)|\.INFO($|\/)|\.INT($|\/)|\.IO($|\/)|\.IQ($|\/)|\.IR($|\/)|\.IS($|\/)|\.IT($|\/)|\.JE($|\/)|\.JM($|\/)|\.JO($|\/)|\.JOBS($|\/)|\.JP($|\/)|\.KE($|\/)|\.KG($|\/)|\.KH($|\/)|\.KI($|\/)|\.KM($|\/)|\.KN($|\/)|\.KP($|\/)|\.KR($|\/)|\.KW($|\/)|\.KY($|\/)|\.KZ($|\/)|\.LA($|\/)|\.LB($|\/)|\.LC($|\/)|\.LI($|\/)|\.LK($|\/)|\.LR($|\/)|\.LS($|\/)|\.LT($|\/)|\.LU($|\/)|\.LV($|\/)|\.LY($|\/)|\.MA($|\/)|\.MC($|\/)|\.MD($|\/)|\.ME($|\/)|\.MG($|\/)|\.MH($|\/)|\.MIL($|\/)|\.MK($|\/)|\.ML($|\/)|\.MM($|\/)|\.MN($|\/)|\.MO($|\/)|\.MOBI($|\/)|\.MP($|\/)|\.MQ($|\/)|\.MR($|\/)|\.MS($|\/)|\.MT($|\/)|\.MU($|\/)|\.MUSEUM($|\/)|\.MV($|\/)|\.MW($|\/)|\.MX($|\/)|\.MY($|\/)|\.MZ($|\/)|\.NA($|\/)|\.NAME($|\/)|\.NC($|\/)|\.NE($|\/)|\.NET($|\/)|\.NF($|\/)|\.NG($|\/)|\.NI($|\/)|\.NL($|\/)|\.NO($|\/)|\.NP($|\/)|\.NR($|\/)|\.NU($|\/)|\.NZ($|\/)|\.OM($|\/)|\.ORG($|\/)|\.PA($|\/)|\.PE($|\/)|\.PF($|\/)|\.PG($|\/)|\.PH($|\/)|\.PK($|\/)|\.PL($|\/)|\.PM($|\/)|\.PN($|\/)|\.PR($|\/)|\.PRO($|\/)|\.PS($|\/)|\.PT($|\/)|\.PW($|\/)|\.PY($|\/)|\.QA($|\/)|\.RE($|\/)|\.RO($|\/)|\.RS($|\/)|\.RU($|\/)|\.RW($|\/)|\.SA($|\/)|\.SB($|\/)|\.SC($|\/)|\.SD($|\/)|\.SE($|\/)|\.SG($|\/)|\.SH($|\/)|\.SI($|\/)|\.SJ($|\/)|\.SK($|\/)|\.SL($|\/)|\.SM($|\/)|\.SN($|\/)|\.SO($|\/)|\.SR($|\/)|\.ST($|\/)|\.SU($|\/)|\.SV($|\/)|\.SY($|\/)|\.SZ($|\/)|\.TC($|\/)|\.TD($|\/)|\.TEL($|\/)|\.TF($|\/)|\.TG($|\/)|\.TH($|\/)|\.TJ($|\/)|\.TK($|\/)|\.TL($|\/)|\.TM($|\/)|\.TN($|\/)|\.TO($|\/)|\.TP($|\/)|\.TR($|\/)|\.TRAVEL($|\/)|\.TT($|\/)|\.TV($|\/)|\.TW($|\/)|\.TZ($|\/)|\.UA($|\/)|\.UG($|\/)|\.UK($|\/)|\.US($|\/)|\.UY($|\/)|\.UZ($|\/)|\.VA($|\/)|\.VC($|\/)|\.VE($|\/)|\.VG($|\/)|\.VI($|\/)|\.VN($|\/)|\.VU($|\/)|\.WF($|\/)|\.WS($|\/)|\.XN--0ZWM56D($|\/)|\.XN--11B5BS3A9AJ6G($|\/)|\.XN--80AKHBYKNJ4F($|\/)|\.XN--9T4B11YI5A($|\/)|\.XN--DEBA0AD($|\/)|\.XN--G6W251D($|\/)|\.XN--HGBK6AJ7F53BBA($|\/)|\.XN--HLCJ6AYA9ESC7A($|\/)|\.XN--JXALPDLP($|\/)|\.XN--KGBECHTV($|\/)|\.XN--ZCKZAH($|\/)|\.YE($|\/)|\.YT($|\/)|\.YU($|\/)|\.ZA($|\/)|\.ZM($|\/)|\.ZW)/i",
      $string,
      $M);
    $has_tld = (count($M) > 0) ? true : false;
    return $has_tld;
  }


  //no rc
  function cleanMsg($url){
    $url = strip_tags($url);
    $us = preg_split('|[\s,:/<>"=]+|', $url);
    foreach ($us as $k => $u) {
      if (stristr($u,".")) { //only preg_match if there is a dot
        if ($this->containsTLD($u) === true) {
          unset($us[$k]);
          return $this->cleanMsg( implode(' ',$us));
        }
      }
    }
    return implode(' ', $us);
  }

    function getBetLvlArr($e, $t = ''){
        if(empty($t))
            $t = $this->getByEntry($e);
        if(empty($t))
            return [];
        $num_lines = phive('MicroGames')->getByGameRef($t['game_ref'])['num_lines'];
        if(empty($num_lines))
            return [];
        if(empty($t['bet_levels']))
            return [$t['min_bet'] / $num_lines];
        $arr = explode(',', $t['bet_levels']);
        return array_map(function($num) use($num_lines){ return $num / $num_lines; }, $arr);
    }

    /**
     * Get the variables we need to create the action button, and return them as an array
     *
     * @param array $tournament
     * @return array
     */
    function getActionButtonVariables($tournament, $user = null)
    {
        $minutes = '';
        $entry   = [];

        if ($tournament['status'] == 'upcoming') {
            $loc_alias = 'mp.upcoming.cdown';
            $minutes   = $this->prettyTime($tournament, $this->getRegStartTime($tournament), true);
        } else if ($tournament['status'] == 'finished') {
            $loc_alias = 'mp.finished';
        } else if ($tournament['status'] == 'cancelled') {
            $loc_alias = 'mp.cancelled';
        } else {
            $user = $user ?? cuPl();
            if (!empty($user)) {
                if ($user->isPlayBlocked()) {
                    $loc_alias = 'mp.registration.closed';
                } else {

                    $entry = $this->entryByTidUid($tournament['id'], $user->getId());
                    $tpl   = $this->getParent($tournament);

                    if(empty($entry)){
                        // User is not registered in the tournament already.
                        if(!$this->isQueued($tpl, $tournament) && !$this->openForRegStatus($tournament)){
                            // The battle is not open for registrations anymore and it is not queued, there is no complicated error message we want to
                            // subsequently display so we can safely show Closed. If it is queued we have to show the reg button on a started tournament
                            // so the player does not have to find a registering one just to get in line in the queue.
                            $loc_alias = 'mp.registration.closed';
                        } else  if ($this->userIsQueued($tpl, $user)) {
                            // The player is queued already so we stop here.
                            $loc_alias = 'mp.unqueue';
                        } else if($this->isRestrictedRecurring($user, $tpl)) {
                            // The Battle is restricted (type 4) and there are currently active ones that the user is registered to so no go.
                            $loc_alias = 'mp.registration.closed';
                        } else if(!empty($tournament['reg_lim_period'])) {
                            // We want to show a Register button here as we aim for an explanatory error message displayed via Ajax.
                            // The button is too small to show all the info.
                            $loc_alias = 'mp.register';
                        } else if ($this->isQueued($tpl, $tournament) && $this->canQueue($tournament, $user, $entry, $tpl) === true) {
                            // We have a queued BoS and the player can queue so we show the Register button.
                            $loc_alias = 'mp.register';
                        } else if ($this->canRegister($tournament, $user, $entry, $tpl) === true) {
                            // We have an unqueued BoS and the player can queue so we show the Register button.
                            $loc_alias = 'mp.register';
                        } else {
                            $loc_alias = 'mp.registration.closed';
                        }
                    } else {
                        // User is registered to the tournament already.
                        if ($this->canCancel($tournament, $user, $entry)) {
                            // The player is already registered and can unregister.
                            $loc_alias = 'mp.unregister';
                        } else if($this->isFreeRoll($tournament) && (!$this->canResume($entry) || empty($entry['spins_left']))) {
                            // We have a freroll and the player is done with it, no rebuys possible.
                            $loc_alias = 'mp.finished';
                        } else {
                            if ($this->canResume($entry) && $entry['spins_left'] > 0) {
                                // user can resume playing only if he has spins left, otherwise if user has 0 spins but the game is still ongoing he will not see the rebuy button
                                $loc_alias = 'mp.resume';
                            } else if ($this->canRebuy($tournament, $entry)) {
                                // We have a finished entry in a non-freeroll BoS with rebuy capabilities.
                                $loc_alias = 'mp.rebuy';
                            } else {
                                // Nothing matched so we show finished.
                                $loc_alias = 'mp.registration.closed';
                            }
                        }
                    }
                }
            } else {
                $loc_alias = 'register';
            }
        }

        return [
            'minutes'   => $minutes,
            'loc_alias' => $loc_alias,
            'entry'     => $entry,
        ];
    }

    /**
     * Tracks whether the Battle Information for a tournament has been show to the user.
     *
     * @param array $tournament
     * @return void
     */
    function trackBattleInformation($tournament_id)
    {
        $_SESSION['played_tournaments'][$tournament_id] = true;
    }

    /**
     * Checks if the Battle Information for a tournament has been shown
     * to the user during the current session.
     *
     * @param int $tournament_id
     * @return void
     */
    function hasShownBattleInformation($tournament_id)
    {
        if(!empty($_SESSION['played_tournaments'][$tournament_id])) {
            return true;
        }

        return false;
    }

    /**
     * Sets the battle alias for a logged in user.
     *
     * @param string $alias
     * @return array
     */
    function setBattleAlias($alias)
    {
        $u = cuPl();
        if(!empty($u)){
            $alias = phive()->cleanUpString($alias);
            if(strlen($alias) > 8 || empty($alias)){
                $msg = 'registration.alias.length';
                $status = 'nok';
            }else{
                $alias = phive('SQL')->escape($alias, false);
                $cnt = phive('UserHandler')->countUsersWhere('alias', $alias);
                if(strtolower($alias) == strtolower($u->getUsername())){
                    $msg = 'registration.alias.username.same';
                    $status = 'nok';
                }else{
                    if(empty($cnt)){
                        $u->setAttr('alias', $alias);
                        $msg = 'alias.successfully.updated';
                        $status = 'ok';
                    }else{
                        $msg = 'registration.alias.taken';
                        $status = 'nok';
                    }
                }
            }
        }else
            $status = 'nok';

        return ['status' => $status, 'msg' => $msg];
    }

    /**
     * Return a shortened version of the alias based on "battle_alias_length" setting (Ex. mylongalias => mylongal...)
     * If the config doesn't exist it will default to 8 the value currently used when creating a battle alias.
     *
     * @param $entry
     * @return string
     */
    public function formatBattleAliasForDisplay($alias) {
        $limit = $this->getSetting('battle_alias_length') ?? 8;
        return strlen($alias) > $limit ? phive()->ellipsis($alias, $limit) : $alias;
    }

    /**
     * Get the tournaments for the activity feed box on the home page, these are upcoming battles.
     *
     * //TODO rework this function as logic is too complex, plus we need to stop using link comparision to get mobile version
     *
     * @return array
     */
    public function getTournamentsForActivityFeedBox($mp_rows, $args = [])
    {
        // Get tournaments only for mobile or desktop, depending on the sitetype
        $tmp = $this->getListing([], "t.status NOT IN('finished', 'cancelled', 'in.progress')");
        if (empty($tmp)) {
          return [];
        }

        $res               = array();
        $res[]             = $tmp[0];
        unset($tmp[0]);
        $count = count($tmp);
        for($i = 0; $i < $count; $i++){
            if($tmp[$i]['start_format'] == 'mtt'){
                $res[] = $tmp[$i];
                unset($tmp[$i]);
                break;
            }
        }
        $res = array_merge($res, $tmp);
        $tournaments = array_slice($res, 0, $mp_rows);

        usort($tournaments, function ($a, $b) {
            return strcmp($a['start_format'], $b['start_format']);
        });

        return $tournaments;
    }

    /**
     * This applies to the ticket of the new SNG tournament ("jackpot chase")
     * If the award being used contains in the "bonus_id" field  a value, it means that it's the tournament tpl_id associated to the reward.
     * In that scenario we return the currently "registration.open" tournament for that SNG template.
     *
     * @param $award_id
     * @param array $tournaments
     * @return array|mixed
     */
    public function getTournamentFromAwardId($award_id, $tournaments = []) {
        if(empty($tournaments)) {
            $tournaments = $this->getListing([]);
        }

        $tpl_id = phive('Trophy')->getAward($award_id)['bonus_id'];

        if(!empty($tpl_id)){
            foreach($tournaments as $t){
                if($t['tpl_id'] == $tpl_id && $this->isRegOpen($t)){
                    return $t;
                }
            }
        }

        return [];
    }

    public function checkForCorrectGame($game, $t){
        $game_refs = array_column(phive('MicroGames')->getGames($game), 'ext_game_name');

        if(!in_array($t['game_ref'], $game_refs)){
            return false;
        }

        return true;
    }

    public function hasBosSearchLink()
    {
        if(!empty($_GET['bos_category']) || !empty($_GET['bos_start_format'])) {
            return true;
        }

        return false;
    }

    public function getMobileBosSearchType($category = '', $start_format = '')
    {
        switch ([$category, $start_format]) {
            case ['freeroll', '']:

                $search_type = 'freerolls';
                break;

            case ['jackpot', '']:

                $search_type = 'jackpot';
                break;

            case ['guaranteed', '']:

                $search_type = 'guaranteed';
                break;

            case ['', 'sng']:

                $search_type = 'sng';
                break;

            case ['freeroll', 'sng']:

                $search_type = 'sng_freerolls';
                break;

            case ['jackpot', 'sng']:

                $search_type = 'sng_jackpot';
                break;

            case ['guaranteed', 'sng']:

                $search_type = 'sng_guaranteed';
                break;

            case ['freeroll', 'mtt']:

                $search_type = 'mtt_freerolls';
                break;

            case ['jackpot', 'mtt']:

                $search_type = 'mtt_jackpot';
                break;

            default:
                $search_type = 'default';
                break;
        }

        return $search_type;
    }

    function checkSngLimit($sng_tpl){
        //Temporal limiting by in progress via config //TODO make this properly
        //$limit_sng = $this->getSetting('limit_in_progress_sng');
        $limit_sng = phive('Config')->getValue('mp', 'limit-in-progress-sng');
        // If more than the limit is active we don't create a new one.
        if (!empty($limit_sng) && (count($this->getByStatusTpl($sng_tpl, 'in.progress')) + 1) >= $limit_sng) {
            return false;
        }

        return true;
    }

    /**
     * Return tournament status localized strings necessary for bos front-end update
     * @param $lang
     * @return array
     */
    public function getStatusesLocalizedStrings($lang) {
        $lang = $lang ?? phive('Localizer')->getCurNonSubLang();

        $cache_key = $lang . '-bos-statuses-localized-strings';
        $localized_strings = phMgetArr($cache_key);

        if(!empty($localized_strings)) {
        return $localized_strings;
        }

        $status_aliases = [
        'mp.upcoming', 'mp.registration.open', 'mp.late.registration', 'mp.in.progress', 'mp.finished', 'mp.cancelled',
        'minutes.ago', 'in', 'min.minute'
        ];

        $localized_strings = [];
        foreach ($status_aliases as $alias) {
        $localized_strings[$alias] = t($alias, $lang);
        }

        phMsetArr($cache_key, $localized_strings, 86000);
        return $localized_strings;
    }

    /**
     * Publishes the tournament message to the event bus.
     *
     * @param int $tournamentId
     */
    public function propagateTournamentCreationMessage(int $tournamentId): void
    {
        $tournament = $this->_getOneWhere(['id' => $tournamentId]);

        $int_keys = [
            'cost',
            'pot_cost',
            'house_fee',
            'min_players',
            'max_players',
            'duration_minutes',
            'duration_rebuy_minutes',
            'mtt_late_reg_duration_minutes',
            'mtt_reg_duration_minutes',
            'prize_amount',
            'guaranteed_prize_amount',
            'min_bet',
            'max_bet',
            'get_race',
            'get_loyalty',
            'get_trophy',
            'rebuy_times',
            'rebuy_cost',
            'reg_wager_lim',
            'reg_dep_lim',
            'reg_lim_period',
            'free_pot_cost',
            'turnover_threshold',
            'allow_bonus',
            'total_cost',
            'rebuy_house_fee',
            'spin_m',
            'number_of_jokers',
            'bounty_award_id',
        ];

        foreach ($int_keys as $key) {
            $tournament[$key] = (int)$tournament[$key];
        }

        $tournament['tournament_id'] = (int)$tournament['id'];
        $tournament['event_timestamp'] = time();
        unset($tournament['id']);

        try {
            $messageData = new TournamentHistoryMessage($tournament);

            phive('Licensed')->addRecordToHistory(
                'tournament',
                $messageData,
            );
        } catch (InvalidMessageDataException $e) {
            phive('Logger')->getLogger('history_message')
                ->error(
                    $e->getMessage(),
                    [
                        'topic'             => 'tournament',
                        'validation_errors' => $e->getErrors(),
                        'trace'             => $e->getTrace(),
                        'data'              => $tournament
                    ]
                );
        }
    }

    /**
     * Returns start time of the current game session of the provided tournament entry.
     * In case of a re-buy it takes the datetime of a re-buy transaction related to this tournament entry.
     *
     * @param int $tournamentEntryId
     * @return DateTime
     * @throws Exception
     */
    protected function getSessionStartTime(int $tournamentEntryId): DateTime
    {
        $tournamentEntry = $this->_getEntryWhere(
            [
                'id' => $tournamentEntryId
            ]
        );
        $tournament = $this->_getOneWhere(
            [
                'id' => $tournamentEntry['t_id']
            ]
        );

        if ($tournamentEntry['rebuy_times'] < $tournament['rebuy_times']) {
            $select = "
                SELECT timestamp
                FROM cash_transactions
                WHERE
                    transactiontype = 54
                AND
                    description LIKE 'tid-{$tournamentEntry['t_id']}'
                AND
                    user_id = {$tournamentEntry['user_id']}
                ORDER BY timestamp DESC
                LIMIT 1
            ";
            $cashTransactionTimestamp = $this->db
                ->sh($tournamentEntry['user_id'], 'user_id', 'cash_transactions')
                ->getValue($select);

            return new \DateTime($cashTransactionTimestamp);
        }

        return new \DateTime($tournament['start_time']);
    }

    /**
     * Sends EndSessionHistoryMessage for a user that took part in the tournament.
     * This is needed for reporting purposes.
     *
     * @param int $tournamentEntryId
     * @throws Exception
     */
    public function propagateEndSessionMessage(int $tournamentEntryId): void
    {
        $tournamentEntry = $this->_getEntryWhere(
            [
                'id' => $tournamentEntryId,
            ]
        );
        $tournament = $this->_getOneWhere(['id' => $tournamentEntry['t_id']]);
        $isMobile = phive()->isMobile() ? 1 : 0;
        $startTime = $this->getSessionStartTime($tournamentEntryId)->format('Y-m-d H:i:s');
        $historyMessage =
            [
                'game_session_id' => 0,
                'user_id' => (int)$tournamentEntry['user_id'],
                'game_ref' => $tournament['game_ref'],
                'device_type' => $isMobile,
                'start_time' => $startTime,
                'end_time' => (new \DateTime())->format('Y-m-d H:i:s'),
                'is_tournament' => true,
                'event_timestamp' => time(),
            ];

        try {
            /** @uses Licensed::addRecordToHistory() */
            lic(
                'addRecordToHistory',
                [
                    'end_session',
                    new EndSessionHistoryMessage($historyMessage)
                ],
                $tournamentEntry['user_id']
            );
        } catch (InvalidMessageDataException $e) {
            phive('Logger')->getLogger('history_message')
                ->error(
                    $e->getMessage(),
                    [
                        'topic'             => 'end_session',
                        'validation_errors' => $e->getErrors(),
                        'trace'             => $e->getTrace(),
                        'data'              => $historyMessage
                    ]
                );
        }
    }

	/**
	 * Get numbers formatted in style of choice, ordinal formatter is used as default
	 *
	 * @param $numbers
	 * @param $targetLanguage
	 * @param int $style
	 * @return array
	 */
	function translateOrdinalNumbers($numbers, $targetLanguage, int $style = NumberFormatter::ORDINAL):array {
		$translatedNumbers = [];

        // Use the "Intl" extension to format the ordinal number in the target language
		$formatter = new NumberFormatter($targetLanguage, $style);

		foreach ($numbers as $number) {
			$translatedNumber = $number;
			$formattedNumber = $formatter->format($number);

			if ($formattedNumber !== false) {
				$translatedNumber = $formattedNumber;
			}

			$translatedNumbers[$number] = $translatedNumber;
		}

		return $translatedNumbers;
	}

    /**
     * Update the wager limit for tournament related transactions
     *
     * @param $user
     * @param $type
     * @param $amount
     */
    public function updateWagerLimit($user, $type, $amount) {
      $rg = rgLimits();

      try {
        $user = cu($user);
        $abs_amount = abs($amount);
        if (in_array($type, [34, 35, 52, 54])) {
          $rg->incType($user, 'wager', $abs_amount);
        }
      } catch (Exception $e) {
        error_log("Wager limit increase failed: {$e->getMessage()}");
      }
    }

    /**
     * @param int $game_id
     * @param DBUser|int|null $user
     * @return array
     */
    public function getPossibleEntry($game_id, $user = null): array
    {
        $user = cu($user);
        if (empty($user)) return [];

        $sql = <<<SQL
            SELECT te.*
            FROM tournaments t
                INNER JOIN tournament_entries AS te ON te.t_id = t.id AND te.user_id = {$user->getId()}
                INNER JOIN micro_games AS g ON t.game_ref = g.ext_game_name
            WHERE
                g.id = {$game_id}
                AND (t.status = 'in.progress' OR t.status = 'late.registration')
            ORDER BY t.end_time
            LIMIT 1
        SQL;

        $data = phive('SQL')->sh($user->getId())->loadArray($sql);
        return (!empty($data)) ? $data[0] : [];
    }
}
