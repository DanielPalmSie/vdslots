<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class TournamentBoxBase extends DiamondBox{

  function init(){
      $this->th = phive('Tournament');
      if(!empty($_GET['award_id'])){
          $this->cur_ticket_t = $this->th->getTournamentFromAwardId(
              $_GET['award_id'],
              $this->th->getListing([])
          );
      }
  }

  function onAjax(){
    $this->init();
  }

  function getTimeInfo($t, $sstamp){
    $time_since_start 	= round((time() - $sstamp) / 60);
    $running_time 	= max($time_since_start, $t['duration_minutes']);
    if($running_time < 0)
      $running_time = 0;
    if($time_since_start > $t['duration_minutes']){
      $start_ing_ed = 'finished.on';
    }else
      $start_ing_ed = $time_since_start > 0 ? 'started.on' : 'starting.on';

    return array($time_since_start, $running_time, $start_ing_ed);
  }

  function fTime($sstamp){
    return strftime('%B %e, %G %H:%M GMT', $sstamp);
  }

  function getTimeFormats($sstamp){
    return array(date('l', $sstamp), $this->fTime($sstamp));
  }

  function getRunningTimeStr($t, $running_time){
    if($running_time > 0 && $running_time < $t['duration_minutes'])
      return t('mp.running').' '.$running_time.' '.t('time.min');
    return '';
  }

  function prTypesMpInfo(){
    $this->prMpInfo(array('jackpots', 'sng', 'freerolls', 'results'), 'mp.hiw.types.headline', 'mp-hiw-types-understood', 'mp-hiw-types');
  }

  function prGeneralMpInfo(){
    $this->prMpInfo(array('coin-value', 'time-left', 'spins-left', 'prize-pool'), 'mp.hiw.general.headline', 'mp-hiw-general-understood', 'mp-hiw-general', false);
  }

  function prPopupInfoSection($t, $lang = null){
?>
  <p>
    <?php if($t['play_format'] == 'xspin'): ?>
      <?php echo $this->th->getXspinInfo($t, 'tot_spins')." ".t('mp.spin.limit', $lang)." ".t("mp.{$t['start_format']}", $lang) ?>
      <br/>
    <?php endif ?>
    <?php et('mp.tournament.currency.is.currency', $lang) ?>
    <br/>
    <table>
      <tr>
        <td><?php et('mp.buyin', $lang) ?></td>
        <td><?php echo $this->fmSym($this->th->getCost($t)) ?></td>
      </tr>
      <tr>
        <td><?php et('mp.fee', $lang) ?></td>
        <td><?php echo $this->fmSym($t['house_fee']) ?></td>
      </tr>
      <tr>
        <td><?php et('mp.pot_cost', $lang) ?></td>
        <td><?php echo $this->fmSym($this->th->getPotCost($t)) ?></td>
      </tr>
    </table>
  </p>
  <?php
  }

  function prMpStartMsg($t, $e, $game, $lang){
  ?>
    <?php etDiv('mp.reminder.headline', "mp-popup-header gradient-default", $lang) ?>
    <div class="mp-popup-content-wrapper" id="mp-reg-start-wrapper">
      <?php et('mp.reminder.body.html', $lang)  ?>
      <center>
        <p class="a-big">
          <?php echo $t['tournament_name'] ?>
        </p>
        <?php
          $this->prPopupInfoSection($t, $lang);
          et('mp.open.new.window', $lang);
          dbCheck('new_window');
          btnDefaultL(t('mp.play', $lang), '', "mpStartGoTo('{$this->th->getPlayUrl($e)}')");
          btnCancelL(t('close', $lang), '', "mboxClose('mp-start')");
        ?>
      </center>
    </div>
  <?php
  }

  function prMpInfo($cols, $headline, $setting_alias, $box_id, $pr_btns = true){
    $config = array();
    foreach($cols as $col)
      $config["mp-hiw-{$col}"] = array("mp.hiw.$col", "mpGoto('$col')");
?>
  <div class="mp-popup-header gradient-default">
    <?php et($headline) ?>
  </div>
  <div class="how-it-works-wrapper">
    <?php foreach($config as $img => $info): ?>
      <div class="how-it-works-col">
        <img src="<?php fupUri("tournaments/$img.jpg") ?>"/>
        <center>
          <p class="a-big">
            <?php et($info[0].'.headline') ?>
          </p>
          <div class="mp-hiw-content">
            <?php et($info[0].'.html') ?>
          </div>
          <?php if($pr_btns) btnDefaultL(t("{$info[0]}.goto"), '', $info[1]) ?>
        </center>
      </div>
    <?php endforeach ?>
    <br clear="all" />
    <div class="mp-continue-btn">
      <?php btnCancelL(t('continue'), '', "mboxClose('$box_id')", 100) ?>
    </div>
    <div class="mp-hiw-check">
      <?php dbCheck("$box_id-check") ?>
      <?php et('mp.dont.show.again') ?>
    </div>
  </div>
  <?php
  }

  function prCashAndBalanceSection($enough_cash, $balance, $err_alias, $ud){
  ?>
    <p>
      <?php et('available.balance') ?>
      <div id="mp-cash-balance-section" class="margin-ten-left <?php echo $enough_cash ? '' : 'error' ?>">
        <div id="mp-cash-balance">
          <?php echo $this->th->prFullUserBalance($ud, true) ?>
        </div>
        <div id="mp-not-enough-money-msg" style="display: <?php echo $enough_cash ? 'none' : 'block' ?>;">
          <?php et($err_alias) ?>
        </div>
      </div>
    </p>
    <?php
  }

  function prAvailableBalanceAndCostSection($total_cost, $enough_cash, $balance, $skill_points, $ud, $err_alias = 'mp.not.enough.money.msg', $limit_err = false){
  ?>
    <p>
      <?php et('mp.account.charged') ?>
      <div class="margin-ten-left">
        <?php echo $this->th->fullFmSym($ud['currency'], $total_cost).' '.t('total') ?>
        <br/>
        <?php if($t['play_format'] == 'xspin'): ?>
          <?php echo $this->th->getXspinInfo($t, 'tot_spins').' '.t('mp.spins.to.qualify') ?>
        <?php endif ?>
      </div>
    </p>

    <?php $this->prCashAndBalanceSection($enough_cash, $balance, $err_alias, $ud) ?>

    <?php if(!$limit_err): ?>
      <div id="mp-reg-start-dep-btn" style="display: <?php echo $enough_cash ? 'none' : 'block' ?>;">
        <?php btnActionL(t('deposit'), '', depGO()) ?>
      </div>
    <?php endif ?>
    <?php
  }

    function passwordForm(&$t){
        echo t('password').':';
        dbInput('pwd', '', 'text', 'input-normal');
    }

    function prMpRegUnReg($t, $action, $box_id = '', $e = array(), $play_url = ''){
        if(empty($t)){
            //clear cache
            $this->th->deleteTournamentCache($_REQUEST['tid']);
            $t = $this->th->byId($_REQUEST['tid']);
        }

        /** @var DBUser $user */
        $user = cuPl();
        $ud = $user->data;
        if(empty($e)){
            $e = $this->th->entryById($_REQUEST['eid'], $ud['id']);
        }

        $skill_points = cuPlSetting('skill_points');
        list($total_cost, $balance, $enough_cash, $rebuy_cost) = $this->th->getMpCashInfo($t, $ud, $action);
        if($action == 'rebuy')
            $cancel_func = empty($play_url) ? "mpFinishedRedirect({$e['id']})" : "mboxClose('$box_id')";
        else
            $cancel_func = "mboxClose('$box_id')";
        $err_msg     = 'mp.not.enough.money.msg';
        $lga_msg     = $this->th->lgaLimConflict($t);
        $limit_err   = false;
        if(is_string($lga_msg)){
            $enough_cash = false;
            $err_msg = $lga_msg;
            $limit_err = true;
        }
        $is_success = $_REQUEST['is_success'] == "true";
    ?>
    <div class="mp-popup-header gradient-default">
        <?php et("mp.$action.headline") ?>
    </div>
    <div class="mp-popup-content-wrapper" id="mp-reg-start-wrapper">
        <?php if(!in_array($action, ['rebuy', 'unqueue']) && !$is_success): ?>
            <center>
                <p>
                    <?php echo $t['tournament_name'] ?>
                </p>
                <?php $this->prPopupInfoSection($t) ?>
            </center>
        <?php endif ?>

        <?php if($action == 'registration'): ?>

            <?php
            if(!$is_success) {
                $this->prAvailableBalanceAndCostSection($total_cost, $enough_cash, $balance, $skill_points, $ud, $err_msg, $limit_err);
                if(!empty($t['pwd']))
                    $this->passwordForm($t);
                if(!empty($user)){
                    if(!$limit_err && !$user->isPlayBlocked()){
                        $reg_ws_url = phive('UserHandler')->wsUrl('bosqreg');
                        btnDefaultL(t('mp.register'), '', "regTournament('no', '$reg_ws_url')");
                    }
                }
            }
            ?>
        <?php elseif($action == 'rebuy'): ?>
            <?php $this->prAvailableBalanceAndCostSection($rebuy_cost, $enough_cash, $balance, $skill_points, $ud) ?>
            <?php if(empty($play_url)): ?>
                <span class="rebuy-countdown"><?php et('mp.rebuy.countdown') ?></span>
                <span class="rebuy-countdown" id="rebuy-countdown"></span>
                <?php jsTag("rebuyCountDown(30, {$e['id']});") ?>
            <?php endif ?>
            <?php btnDefaultL(t('mp.rebuy'), '', "rebuyTournament('{$e['id']}', '$play_url')",'','rebuy-button') ?>

        <?php elseif($action == 'unregister'): ?>

            <div id="mp-unreg-info">
                <?php et('mp.unregister.body.html') ?>
            </div>
            <div id="mp-unreg-success-msg" style="display: none;">
                <?php et('mp.unregister.body.success.html') ?>
            </div>
            <div id="mp-unreg-fail-msg" style="display: none;">
                <?php et('mp.unregister.body.fail.html') ?>
            </div>
            <div id="mp-unreg-close" style="display: none;">
                <?php btnCancelL(t('close'), '', "mboxClose('$box_id')") ?>
            </div>
            <?php btnCancelDefaultL(t('mp.unregister'), '', "unregTournament()") ?>

        <?php elseif($action == 'unqueue'): ?>

            <div id="mp-unreg-info">
                <?php et('mp.unqueue.body.html') ?>
            </div>
            <div id="mp-unreg-success-msg" style="display: none;">
                <?php et('mp.unqueue.body.success.html') ?>
            </div>
            <div id="mp-unreg-close" style="display: none;">
                <?php btnCancelL(t('close'), '', "mboxClose('$box_id')") ?>
            </div>
            <?php btnCancelDefaultL(t('mp.unqueue'), '', "unregTournament('".t('mp.queue')."', 'tournament-unqueue')") ?>

        <?php endif ?>

        <?php
            if(!$is_success) {
                btnCancelL(t('cancel'), '', $cancel_func);
            }
        ?>
    </div>
  <?php
  }

  function prMpRebuy($t = [], $e = []){
    $this->prMpRegUnReg($t, 'rebuy', 'mp-rebuy-start', '', $_REQUEST['play_url']);
  }

  function prMpUnReg($t = []){
    $this->prMpRegUnReg($t, 'unregister', 'mp-unreg-start');
  }

  function prMpUnQueue($t = []){
      $this->prMpRegUnReg($t, 'unqueue', 'mp-unreg-start');
  }

  function prRegWithTicket(&$t, $ticket, &$user){
  ?>
    <div class="mp-popup-header gradient-default">
      <?php et("mp.ticket.buyin.headline") ?>
    </div>
    <div class="mp-popup-content-wrapper" id="mp-reg-start-wrapper">
      <center>
        <img class="reward-img" src="<?php echo phive('Trophy')->getAwardUri($ticket, $user) ?>" />
        <p>
          <h3>
            <?php echo rep(tAssoc("mp.ticket.buyin.content", $ticket)) ?>
          </h3>
        </p>
      <table>
        <tr>
          <td><?php btnDefaultL(t('yes'), '', "regTournament('yes')", 150) ?></td>
          <td><?php btnCancelL(t('no'), '', "showTournamentRegBox('no')", 150) ?></td>
        </tr>
      </table>
      </center>
    </div>
    <?php
  }

    function prPickAlias(){
    ?>
      <div class="mp-popup-header gradient-default">
        <?php et("mp.choose.alias.headline") ?>
      </div>
      <div class="mp-popup-content-wrapper" id="mp-reg-start-wrapper">
        <center>
          <img class="reward-img" src="<?php echo phive('Filer')->getFileUri('bos_logo.png') ?>" />
          <h3><?php et('mp.choose.alias.headline') ?></h3>
          <p>
            <?php et('mp.choose.alias.content') ?>
          </p>
          <?php dbInput('alias', '', 'text', 'input-normal') ?>
          <br/>
          <?php btnDefaultL(t('submit'), '', "submitAlias('{$_REQUEST['show_reg']}')", 100) ?>
          <p id="error" class="error"></p>
        </center>
      </div>
    <?php
    }

  function prMpReg($t = null){
    $u = cuPl();
    if(empty($t)){
        //clear cache
        $this->th->deleteTournamentCache((int)$_REQUEST['tid']);
        $t = $this->th->byId((int)$_REQUEST['tid']);
    }

    if($_REQUEST['check_for_ticket'] == 'yes') {
        $ticket = $this->th->getTicket($t, $u);
    }

    if(empty($ticket)) {
        $this->prMpRegUnReg($t, 'registration', 'mp-reg-start');
    } else {
        $this->prRegWithTicket($t, $ticket, $u);
    }
  }

  function prMpUnderLimit($eid){
    $this->prMpLimitMsgTbl('mp.under.limit.html', $eid);
  }

    function prMpWrongBetSize($eid){
        $this->prMpLimitMsgTbl('mp.wrong.betsize.html', $eid);
    }

  function prMpOverLimit($eid){
    $this->prMpLimitMsgTbl('mp.over.limit.html', $eid);
  }

  function prMpPausedCalc($eid){
    $this->prMpLimitMsgTbl('mp.games.down.html', $eid, '', 'error', 'mp.games.down.headline');
  }

  function prMpCancelled($eid){
    $this->prMpLimitMsgTbl('mp.cancelled.html', $eid, '', 'error', 'mp.cancelled.headline', goToLlink('/'));
  }

  function prMpLimitMsgTbl($alias, $t_eid, $content = '', $type = 'error', $headline = '', $close_action = 'closeLgaReality()'){
    if(empty($headline))
      $headline = $type == 'error' ? "mp.betlimit.$type.headline" : 'mp.start.headline';

    $t = is_array($t_eid) ? $t_eid : $this->th->getByEid($t_eid);
    if($headline == 'mp.start.headline') {
        phive('Tournament')->trackBattleInformation($t['id']);
    }
    $me = $this;
?>
    <div class="<?php echo $type == 'error' ? 'mp-info-header btn-cancel-default-l' : 'mp-popup-header gradient-default' ?>">
    <?php et($headline) ?>
  </div>
  <div class="mp-info-wrapper">
    <div class="mp-info-left">
      <?php echo empty($content) ? tAssoc($alias, ['bet_levels' => $this->getBetInterval($t)]) : $content ?>
      <p class="a-big">
        <?php echo $t['tournament_name'] ?>
      </p>
      <table>
        <tr>
          <td><?php et('mp.max.bet') ?></td>
          <td><?php echo $this->fmSym($t['max_bet']) ?></td>
        </tr>
        <tr>
          <td><?php et('mp.min.bet') ?></td>
          <td><?php echo $this->fmSym($t['min_bet']) ?></td>
        </tr>
        <?php $this->th->prSpinInfo($t, function() use ($t, $me){ ?>
          <tr>
            <td><?php et('mp.spins') ?></td>
            <td><?php echo $me->th->getXspinInfo($t, 'tot_spins') ?></td>
          </tr>
        <?php })
        ?>
      </table>
    </div>
    <div class="mp-info-right">
      <img src="<?php echo phive('Tournament')->img($t) ?>"/>
      <p>
        <span class="header-big"><?php et('mp.howto.change.betlevel.headline') ?></span>
      </p>
      <p>
        <?php et('mp.howto.change.betlevel.descr.html') ?>
      </p>
    </div>
    <br clear="all"/>
    <?php okCenterBtn($close_action) ?>
  </div>
<?php
  }

  function prMpFinished($eid = null){
    if(empty($eid))
      $eid = $_REQUEST['eid'];
    $e = $this->th->entryById($eid);
?>
  <center>
    <p class="headline-default-m">
      <?php et('mp.completed.headline') ?>
    </p>
    <p>
      <?php et('mp.completed.body') ?>
    </p>
    <table>
      <tr>
        <td><img src="<?php fupUri('tournaments/mp-finished-trophy.png') ?>"/></td>
        <td><?php et('mp.your.score') ?></td>
        <td>
          <span class="super-bold">
            <?php echo $e['win_amount'] ?></td>
          </span>
      </tr>
    </table>
    <br/>
    <br/>
    <br/>
    <br/>
    <br/>
    <button class="btn btn-l btn-cancel-l w-125 margin-ten-bottom" onclick="goTo('<?php echo llink(phive()->getSetting("bos_faq_page") . "?tournament_lobby=true&t_id={$e['t_id']}") ?>')">
      <?php et('mp.backtolobby.btn') ?>
    </button>
  </center>
  <?php
  }

  function infoArea($t, $status, $sstamp){
    if($this->th->isClosed($t))
      $sstamp = strtotime($t['end_time']);
    list($time_since_start, $running_time, $start_ing_ed) = $this->getTimeInfo($t, $sstamp);
    list($start_wday, $start_dtime) = $this->getTimeFormats($sstamp);
    ?>
    <?php echo t($start_ing_ed).' '.$start_wday ?>
    <br/>
    <?php echo $start_dtime ?>
    <?php echo $this->getRunningTimeStr($t, $running_time) ?>
  <?php }

  function getTournamentInfo(){
    $this->prTopRightInfo($this->th->byId($_REQUEST['tid']));
  }

  function tournamentInfo($t, $ajax = false){
    if($this->th->hasStarted($t)){
      $status 	  = 'mp.started';
      $start_time = $t['start_time'];
    }else{
      if($t['start_format'] != 'mtt'){
        $status	  = 'mp.registration.open';
      }else{
        $status   = 'mp.will.start';
        $start_time = $t['mtt_start'];
      }
    }
    $sstamp = strtotime($start_time);
    $reg_start_stamp = $this->th->getRegStartTime($t);
    $cu_currency = cuPlAttr('currency');
  ?>
  <script>
   <?php if(!empty($t)): ?>
     var curTid = <?php echo $t['id'] ?>;
     parent.curTid = curTid;
   <?php endif ?>
  </script>
      <?php if (!empty($t)) : ?>
          <img src="<?php echo $this->th->img($t); ?>" />
      <?php endif; ?>
  <br/>
  <table class="tournament-info-tbl">
    <?php if($reg_start_stamp !== false): ?>
    <tr>
      <td><?php et('mp.reg.start') ?>:</td>
      <td><?php echo $this->th->getRegStartTime($t, true) ?></td>
    </tr>
    <?php endif ?>
    <tr>
      <td><?php et('mp.start') ?>:</td>
      <td><?php echo $this->th->getStartOrStatus($t) ?></td>
    </tr>
    <tr>
      <td><?php et('mp.max.players') ?>:</td>
      <td><?php echo $t['max_players'] ?></td>
    </tr>
    <?php if(!empty($t['min_players'])): ?>
      <tr>
        <td><?php et('mp.min.players') ?>:</td>
        <td><?php echo $t['min_players'] ?></td>
      </tr>
    <?php endif ?>
    <tr>
      <td><?php et('mp.buyin') ?>:</td>
      <td><?php echo $this->th->getBuyIn($t, false, $cu_currency) ?></td>
    </tr>
    <?php if(!empty($t['pot_cost'])): ?>
      <tr>
        <td><?php echo t('mp.pot.cost').(empty($t['free_pot_cost']) ? '' : ' ('.t('free').')') ?>:</td>
        <td><?php echo $this->th->fullFmSym($cu_currency, $t['pot_cost']) ?></td>
      </tr>
    <?php endif ?>
    <?php if(!empty($t['guaranteed_prize_amount'])): ?>
      <tr>
        <td><?php et('mp.guaranteed.amount') ?>:</td>
        <td><?php echo $this->th->fullFmSym($cu_currency, $t['guaranteed_prize_amount']) ?></td>
      </tr>
    <?php endif ?>
    <tr>
      <td><?php et('mp.duration') ?>:</td>
      <td><?php echo $t['duration_minutes'].' '.t('minutes') ?></td>
    </tr>
    <tr>
      <td><?php et('mp.spins') ?>:</td>
      <td><?php echo empty($t['xspin_info']) ? et('na') : $this->th->getXspinInfo($t, 'tot_spins') ?></td>
    </tr>
    <tr>
      <td><?php et('mp.bet.interval') ?>:</td>
      <td><?php echo $this->getBetInterval($t) ?></td>
    </tr>
  </table>
  <?php
  }

  function redirectToPlayUrl($e, $game) {
    if (empty($e) || empty($e['id'])) {
        $game_id = empty($game['id']) ? 0 : $game['id'];
        return <<<JS
            ajaxGetBoxHtml(
                {func: 'showTournamentErrorDialog', game_id: $game_id},
                cur_lang,
                'TournamentBox',
                function (ret) {mpBox(ret, 'tournament-error-box', 'parent', '400px');}
            );
        JS;
    }

    return "top.goTo('{$this->th->playUrl($e, $game)}')";
  }

  function prRegBtn($t, $class = ''){

      $array     = $this->th->getActionButtonVariables($t);
      $minutes   = $array['minutes'];
      $loc_alias = $array['loc_alias'];
      $e         = $array['entry'];

      switch($loc_alias){
          case 'mp.rebuy':
              $game = $this->th->getGame($t);
              $conf = ['btn-action-l', "rebuyStart('prMpRebuy', 'mp-rebuy-start', {$t['id']}, {$e['id']}, '{$this->th->playUrl($e, $game)}')"];
              break;
          case 'mp.resume':
              $game = $this->th->getGame($t);
              $conf = ['btn-action-l', $this->redirectToPlayUrl($e, $game)];
              break;
          case 'mp.unregister':
              $conf = ['btn-cancel-default-l', "showTournamentUnRegBox('{$t['id']}')"];
              break;
          case 'mp.register':
              $conf = ['gradient-default', "showTournamentRegBox()"];
              break;
          case 'mp.upcoming.cdown':
              $conf = ['btn-cancel-l', '', $minutes];
              break;
          case 'mp.registration.closed':
              $conf = ['btn-cancel-l', "showTournamentRegBox()"];
              break;
          case 'register':
              $reg_url = llink('?signup=true');
              $_SESSION['show_signup'] = true;
              $conf = ['gradient-default', "top.goTo('$reg_url')"];
              break;
          case 'mp.unqueue':
              $conf = ['btn-cancel-default-l', "showTournamentUnqueueBox()"];
              break;
      }

  ?>
      <script>
          function bosResumeGameRedirect() {
              var redirectMethod = `<?php echo $conf[1] ?>`;
              if(redirectMethod.includes("top.goTo")){  // check the method that will be executed to avoid BOS registration button triggering game session closed popup
                  if((typeof top.extSessHandler !== 'undefined')  && (typeof top.mpUserId === 'undefined') && (parent.cur_country === 'ES')) {
                      top.gameCloseRedirection(`<?php echo $conf[1] ?>`);
                  }else{
                      <?php echo $conf[1] ?>
                  }
              }else{
                  <?php echo $conf[1] ?>
              }
          }
      </script>
      <div id="register-btn" class="<?php echo $conf[0] . ' ' . $class ?> yellow-right pointer" onclick="bosResumeGameRedirect()">
          <?php et2($loc_alias, $conf[2]) ?>
      </div>
    <?php
  }

    function prTopRightInfo($t = []){
    ?>
      <div id="tournament-name-headline" class="gradient-dark right-headline thin-border">
        <span>
          <?php echo $t['tournament_name'] ?>
        </span>
      </div>
      <div id="tournament-info" class="tournament-info">
        <?php $this->tournamentInfo($t); ?>
      </div>
      <div class="gradient-default yellow-right pointer" onclick="toLobby('<?php echo llink('/mp-lobby/') ?>')">
        <?php et('mp.lobby') ?>
      </div>
      <?php $this->prRegBtn($t) ?>
      <?php
    }

    function infoBox($t){ ?>
      <script>
       $(document).ready(function(){
         setupClock(<?php echo date('H') ?>, <?php echo date('i') ?>);
       });
      </script>
      <div id="top-right-info" style="position: relative">
        <?php $this->printPreLoader(); ?>
        <?php $this->prTopRightInfo() ?>
      </div>

      <table class="right-bottom">
        <tr>
          <td class="gradient-dark">
            <?php digitalClock(); echo ' GMT'; ?>
          </td>
          <td style="width: 8px;">

          </td>
          <td class="gradient-default login-deposit pointer">
            <?php if(isLogged()): ?>
              <div id="<?php echo phive('Casino')->generateDOMId('deposit', 'login-deposit' ) ?>" onclick="<?php echo depGo() ?>"><?php et('deposit') ?></div>
            <?php else: ?>
              <?php  $_SESSION['show_signup'] = true; ?>
              <div id="<?php echo phive('Casino')->generateDOMId('register', 'login-deposit') ?>" onclick="parent.goTo('<?php echo llink('/?signup=true') ?>')"><?php et('register') ?></div>
            <?php endif ?>
          </td>
        </tr>
      </table>
  <?php }

  function loadCssJs(){
    loadCss("/diamondbet/css/" . brandedCss() . "tournament.css");
    loadCss("/diamondbet/fonts/icons.css");
  }

  function getActiveEntries(){
    $es = phive('Tournament')->runningEntriesWithName();
    if(empty($es))
      echo 'no';
    else
      mpChooseBox($es, true);
  }

  function mpTopBar($str, $onclick){ ?>
    <div class="mp-popup-header gradient-default">
      <?php et($str) ?>
      <div class="cashier2-close" onclick="<?php echo $onclick ?>">X</div>
    </div>
  <?php }

    function showTournamentErrorDialog() {
        $close_action = ($_REQUEST['is_play_box'])
            ? "top.goTo('/?showTournamentBox=true')"
            : "mboxClose('tournament-error-box')";

        ?>
        <script>
            var doOk = function() {
                mgAjax({
                    action: 'get-fallback-play-url',
                    game_id: <?php echo $_REQUEST['game_id'] ?? 0; ?>
                }, function (ret) {
                    var response = typeof ret === 'object' ? ret : $.parseJSON(ret);

                    if (response.hasOwnProperty('success') &&
                        response.success === true &&
                        response.hasOwnProperty('url') &&
                        response.url.length > 0
                    ) {
                        mboxClose('tournament-error-box');
                        goTo(response.url);
                    } else {
                        <?php echo $close_action; ?>
                    }
                });
            };
        </script>
        <div class="mp-popup-header gradient-default">
            <?php et('tournament.error.modal.title') ?>
        </div>
        <div class="mp-popup-content-wrapper">
            <p>
                <?php et('tournament.error.modal.body') ?>
            </p>
            <table style="width: 100%;">
                <tr>
                    <td>
                        <?php btnActionL(t('tournament.error.modal.lobby.btn'), '', 'doOk()', 170) ?>
                    </td>
                    <td>
                        <?php btnDefaultL(t('tournament.error.modal.exit.btn'), '', $close_action, 170) ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }


  function prMyMpResults(){
    list($stime, $etime) = phive()->todaySpan();
    $rows = $this->th->getUserResults(cuPl(), $stime, $etime, array('open', 'finished', 'cancelled'));
  ?>
    <?php $this->mpTopBar('mp.my.tournaments', "$.multibox('close', 'mp-my-tournaments')") ?>
    <div class="tournament-list-wrapper">
      <table class="tournament-list">
        <tbody>
          <tr class="tournament-header">
            <th><?php et('mp.name') ?></th>
            <th><?php et('mp.sformat') ?></th>
            <th><?php et('mp.edate') ?></th>
            <th><?php et('mp.result') ?></th>
            <th><?php et('mp.entry.prize') ?></th>
            <th><?php et('mp.entry.status') ?></th>
            <th><?php et('mp.status') ?></th>
            <th><?php et('mp.action') ?></th>
            <th><?php echo '' ?></th>
          </tr>
          <?php $i = 0; foreach($rows as $te):
          $date = $this->th->displayDate($te);
          ?>
            <tr class="<?php echo oddEven($i) ?>">
              <td class="txt-align-left"><?php echo $te['tournament_name'] ?></td>
              <td><?php et("mp.{$te['start_format']}") ?></td>
              <td><?php lcDate($date) ?></td>
              <td><?php echo $this->th->displayResult($te) ?></td>
              <td><?php echo $this->th->displayPrize($te) ?></td>
              <td><?php et("mp.{$te['status']}") ?></td>
              <td><?php et($te['tstatus']) ?></td>
              <td>
                <?php if($this->th->canResume($te)) btnActionXs(t('mp.resume'), $this->th->playUrl($te)) ?>
              </td>
              <td>
                <?php btnActionXs(t('mp.lobby'), '', "toLobbyWin('{$te['t_id']}')") ?>
              </td>
            </tr>
          <?php $i++; endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
  }

  function printCSS(){
    $this->loadCssJs();
  }

  function startAliasPicking(){
    if(isLogged() && empty(cuAttr('alias')))
      echo "startAliasPicking();";
  }

  function printHTML(){
      if (phive('MicroGames')->blockMisc()) {
          die('deposit.country.ip.restriction');
      }

      $this->ts = [];
      $this->ct = end($this->ts);
    ?>
      <script>

     function listTs(params, func, keep_selected_tournament = false){
       params = typeof params == 'undefined' ? mpTsOptions() : params;
       if(typeof func == 'undefined'){
         params.func = mpGetSelected('tournament-format-btns') == 'mymps' ? 'myTournaments' : 'printList';
       }else
         params.func = func;
       ajaxGetBoxHtml(params, cur_lang, <?php echo $this->getId() ?>, function(ret){
           $("#tournament-list-wrapper").html(ret);
           reInitMain(keep_selected_tournament);
       });
     }

     function updateTournamentRow(tid){
       ajaxGetBoxHtml({func: 'updateTournamentRow', tid: tid}, cur_lang, <?php echo $this->getId() ?>, function(ret){
         $("#tr-"+tid).html(ret);
       });
     }

       function triggerFirstRowClick(keep_selected_tournament = false){
           <?php if(!empty($this->cur_ticket_t)): ?>
               tournamentInfo(<?php echo $this->cur_ticket_t['id'] ?>);
           <?php else: ?>
                if(keep_selected_tournament) {
                    var last_tournament_row = $("tr[id='tr-" + last_tournament_row_clicked +"']");
                    if(last_tournament_row.length) {
                        tournamentInfo(last_tournament_row_clicked);
                    }
                    last_tournament_row.trigger('click');
                } else {
                    $("tr[id^='tr-']").first().trigger('click');
                }
           <?php endif ?>
       }

     function addDoubleClicktoRows(tournament_id){
       const selector = tournament_id ? `tr[id^='tr-${tournament_id}']` : `tr[id^='tr-']`;

       $(selector).dblclick(function(){
         var tid = getSuffix($(this).attr('id'));
         toLobbyWin(tid);
       });
     }

     function mpTsOptions(){
       return {
         start_format: mpGetSelected('tournament-format-btns'),
         category: mpGetSelected('tournament-category-btns'),
         status: mpGetSelected('tournament-status-btns')
       };
     }

     function startMain(){
       sessionStorage.removeItem("last_tournament_row_clicked");
       triggerFirstRowClick();
       addDoubleClicktoRows();
       minuteCdown();

       // Direct links to the BoS will be picked up here
       var bos_category     = getParentParameterByName('bos_category');
       var bos_start_format = getParentParameterByName('bos_start_format');

       if(bos_category !== 'undefined') {
           selectCategory(bos_category);
       }
       if(bos_start_format !== 'undefined') {
           selectStartFormat(bos_start_format);
       }
       listTs();
     }


     function reInitMain(keep_selected_tournament = false){
       triggerFirstRowClick(keep_selected_tournament);
       addDoubleClicktoRows();
       minuteCdown();
     }

     function selectCategory(category) {
         unselectCss($("div[id^='category']"));
         selectCss($('#category-' + category));
     }

     function selectStartFormat(start_format) {
         unselectCss($("div[id^='format']"));
         selectCss($('#format-'+start_format));
     }

     $(document).ready(function(){

         <?php if(!empty($this->cur_ticket_t)): ?>
             toLobbyWin(<?php echo $this->cur_ticket_t['id'] ?>);
         <?php endif ?>

       <?php $this->startAliasPicking() ?>

       <?php
       if(cuPlSetting('mp-hiw-types-understood') != 'yes' && isLogged())
         echo "mpHiw('prTypesMpInfo', 'mp-hiw-types', 'this');";
       ?>
         var statuses_localized_strings = localStorage.getItem('tournament-statuses-localized-strings-' + cur_lang);
         if(!statuses_localized_strings) {
             mpAction({lang: cur_lang, action: 'tournament-statuses-localized-strings'}, function(res){
                 localStorage.setItem('tournament-statuses-localized-strings-' + cur_lang, JSON.stringify(res));
             });
         }
       <?php echo "mpLobbyWs('".phive('UserHandler')->wsUrl('mp-main-lobby', false)."');" ?>

       startMain();

       $('#format-mymps').click(function(){
         unselectCss($("div[id^='format']"));
         selectCss($(this));
         //var options = mpTsOptions();
         //options.start_format = 'all';
         listTs(undefined, 'myTournaments');
       });

       $.each(['all', 'sng', 'mtt'], function(i, v){
         $('#format-'+v).click(function(){
           unselectCss($("div[id^='format']"));
           selectCss($(this));
           listTs();
         });
       });

       $.each(['all', 'upcoming', 'in-progress', 'finished'], function(i, v){
         $('#status-'+v).click(function(){
           unselectCss($("div[id^='status']"));
           selectCss($(this));
           listTs();
         });
       });

       $.each(['all', 'normal', 'guaranteed', 'added', 'jackpot', 'xspin', 'freeroll'], function(i, v){
         $('#category-'+v).click(function(){
           unselectCss($("div[id^='category']"));
           selectCss($(this));
           listTs();
         });
       });

       $("#search").click(function(e){
           $(this).val('');
           listTs();
       });

         var onInputSearch = _.debounce(function() {
           var cur = $(this);

           if(cur.val().length > 2)
               listTs({str_search: cur.val()});

           if(cur.val().length == 0)
               listTs({str_search: ''});
       }, 300);

         $("#search").keyup(onInputSearch);
     });

    </script>

    <script>
        //This function is used to handle potential user redirects to BOS top menu FAQ and email from within a game
        //to show a game summary first if user is an ES player.
        function bosTopMenuRedirect(link){
            if ((typeof parent.extSessHandler !== 'undefined') && (typeof parent.mpUserId === 'undefined') && (cur_country === 'ES')){
                var redirectString = "parent.goTo(\'" + link + "\')";
                parent.gameCloseRedirection(redirectString);
            } else {
                parent.goTo(link);
            }
        }
    </script>

    <div class="tournament-wrapper">
      <?php depositTopBar('mps', "parent.$.multibox('close', 'mp-box')") ?>
      <div class="tournament-content">
        <div class="mp-top-info">
          <?php if(isLogged()): ?>
            <strong>
              <?php et('casino.balance') ?>
            </strong>
            <span id="mp-top-balance">
              <?php echo $this->th->prFullUserBalance(cu()->data) ?>
            </span>
            &nbsp;
            &nbsp;
            <?php //btnActionXs(t('mp.my.tournaments'), '', 'parent.showMyTournaments()', 200) ?>
          <?php endif ?>

          <div class="right">

            <ul class="topmost-menu">
              <li>
                <img class="mp-top-info-icon" src="<?php fupUri('mp-faq.png') ?>"/>
                  <span onclick="bosTopMenuRedirect('<?php echo llink(phive()->getSetting('bos_faq_page')) ?>')">FAQ</span>
              </li>
              <li>
                <img class="mp-top-info-icon" src="<?php fupUri('mp-chat.png') ?>"/>
                <span onclick="parent.<?php echo phive('Localizer')->getChatUrl()  ?>"><?php et('help.start.live.chat.headline') ?></span>
              </li>
              <li>
                <img class="mp-top-info-icon" src="<?php fupUri('mp-email.png') ?>"/>
                  <span onclick="bosTopMenuRedirect('<?php echo llink('/customer-service/') ?>')"><?php et('email') ?></span>
              </li>
            </ul>

          </div>
        </div>
      <table class="tournament-table">
        <tr>
          <td class="tournament-left">
            <div>
              <table class="tournament-format-btns">
                <tr>
                  <?php foreach(array('all', 'mtt', 'sng') as $str): ?>
                  <td class="tournament-format-btn gradient-dark thin-border">
                    <div id="<?php echo "format-$str" ?>">
                      <?php et("mp.$str") ?>
                    </div>
                  </td>
                  <?php endforeach ?>
                  <?php if(isLogged()): ?>
                    <td class="tournament-format-btn gradient-dark thin-border">
                      <div id="<?php echo "format-mymps" ?>">
                        <?php et("mp.mymps") ?>
                      </div>
                    </td>
                  <?php endif ?>
                </tr>
              </table>
            </div>
            <div>
              <table class="tournament-category-btns">
                <tr>
                  <?php foreach(array('all', 'normal', 'added', 'guaranteed', 'jackpot', 'freeroll') as $str): ?>
                  <td class="tournament-category-btn gradient-dark thin-border">
                    <div id="<?php echo "category-$str" ?>">
                      <?php et("mp.$str") ?>
                    </div>
                  </td>
                  <?php endforeach ?>
                </tr>
              </table>
            </div>
            <div>
              <table class="tournament-status-btns">
                <tr class="gradient-dark">
                  <td class="thin-border" style="width: 120px;">
                    <div>
                      <?php dbInput("search", '', null, 'search-tournament', '', true, false, false, t('search.tournament')) ?>
                    </div>
                  </td>
                  <?php foreach(array('all', 'upcoming', 'in-progress', 'finished') as $str): ?>
                    <td class="tournament-status-btn gradient-dark thin-border">
                      <div id="<?php echo "status-$str" ?>">
                        <?php et("mp.$str") ?>
                      </div>
                    </td>
                  <?php endforeach ?>
                </tr>
              </table>
            </div>
            <div id="tournament-list-wrapper" class="tournament-list-wrapper tournament-list-wrapper__preloader">
              <?php $this->printPreLoader(); ?>
            </div>
          </td>
          <td class="tournament-right">
            <div>
              <?php $this->infoBox($this->ct) ?>
            </div>
          </td>
        </tr>
      </table>
      </div>
    </div>
   <?php }

   function updateTournamentRow(){
     $t = phive('Tournament')->byId($_REQUEST['tid']);
     if(!empty($t)){
       $g = phive('MicroGames')->getByGameRef($t['game_ref']);
       $t['game_name'] = $g['game_name'];
       $this->tournamentRow($t);
     }else
       echo 'no';
   }

   function fmSym($amount){
     return $this->th->fmSym($amount, 100);
   }

   function getBetInterval($t){
       if(empty($t['bet_levels']))
           return $this->fmSym($t['min_bet']).' - '.$this->fmSym($t['max_bet']);
       return $this->th->cSym().' '.implode(', ', array_map(function($num){ return $num / 100;  }, explode(',', $t['bet_levels'])));
   }

   function prettyUserBalance(){
     return $this->fmSym($this->th->getUserBalance(), 1);
   }

   // function mpCancelledBox($t){
   //   return tAssoc('mp.cancelled.html', $t);
   // }

   function printTruncatedGameName(&$t){
     $gname = empty($t['game_name']) ? $t['game']['game_name'] : $t['game_name'];
     echo phive()->ellipsis($gname, 20);
   }

    function printList($ts = array()){
        addCacheHeaders("cache60");
        $ts = $this->th->getListingAdvanced($ts, $_REQUEST);
        $this->tournamentTable($ts);
    }

    function renderTournamentRow($t, $th, $echo = true, $nameLength = 32, $myps = false, $me) {
        $content = phive()->ob(function () use ($t, $th, $nameLength, $myps, $me) {
            ?>
            <td class="txt-align-left" id="td-start-status-<?php echo $t['id']; ?>">
                <?php echo $th->getStartOrStatus($t); ?>
            </td>
            <td class="txt-align-left" id="td-tournament-name-<?php echo $t['id']; ?>">
                <?php echo phive()->ellipsis($t['tournament_name'], $nameLength ); ?>
            </td>
            <td class="txt-align-left" id="td-game-name-<?php echo $t['id']; ?>">
                <?php $me->printTruncatedGameName($t); ?>
            </td>
            <td id="td-category-<?php echo $t['id']; ?>">
                <?php echo ucfirst($t['category']); ?>
            </td>
            <td id="td-get-buy-in-<?php echo $t['id']; ?>">
                <?php echo $th->getBuyIn($t); ?>
            </td>

            <?php if (!$myps): ?>
                <td id="td-reg-status-<?php echo $t['id']; ?>">
                    <?php et('mp.' . $t['status']); ?>
                </td>
            <?php endif; ?>

            <td id="td-enrolled-user-<?php echo $t['id']; ?>">
                <?php echo $th->displayRegs($t); ?>
            </td>

            <?php if ($myps): ?>
                <td id="td-end-status-<?php echo $t['id']; ?>">
                    <?php echo $th->displayResult($t, 'e_status', $t); ?>
                </td>
            <?php else: ?>
                <td id="td-icon-status-<?php echo $t['id']; ?>">
                    <?php echo $th->padLock($t); ?>
                </td>
            <?php endif; ?>
            <?php
        });

        if ($echo) {
            echo $content;
        }
        return $content;
    }


    // Function to handle user-specific tournaments
    function myTournaments() {
        $this->main_headlines = array('start', 'name', 'game', 'category', 'buyin', 'enrolled', 'result');
        $this->main_sorters = array('text', 'text', 'text', 'bigcurrency', 'text', 'bigcurrency');
        $ts = $this->th->allByUser(cuPlId());
        $me = $this;
        $this->main_row_render_func = function($t, $th) use ($me) {
            $this->renderTournamentRow($t, $th, true, 20, true, $me);
        };
        $this->printList($ts);
    }


    function tournamentRow($t, $echo = true){
        $me = $this;
        $def_func = function($t, $th) use ($me){

            $this->renderTournamentRow($t, $th, true, 32, false, $me);
        };
        $func = empty($this->main_row_render_func) ? $def_func : $this->main_row_render_func;
        $res = phive()->ob($func, [$t, $this->th]);
        if($echo)
            echo $res;
        return $res;
    }


   function tournamentTable($ts){
     tableSorter('tournament-list', empty($this->main_sorters) ? array('text', 'text', 'text', 'bigcurrency', 'text', 'bigcurrency') : $this->main_sorters);
     $t_cols = empty($this->main_headlines) ? array('start', 'name', 'game', 'category', 'buyin', 'state', 'enrolled') : $this->main_headlines;
   ?>
     <script>
         var last_tournament_row_clicked =  sessionStorage.getItem("last_tournament_row_clicked") || 0;
         function tournamentRowClickHandler(tournament_id) {
             if (tournament_id !== last_tournament_row_clicked) {
                 tournamentInfo(tournament_id);
             }
             last_tournament_row_clicked = tournament_id;
             sessionStorage.setItem("last_tournament_row_clicked", last_tournament_row_clicked);
         }
     </script>
     <table id="tournament-list" class="tournament-list">
       <thead>
         <tr class="tournament-header">
           <?php foreach($t_cols as $str): ?>
             <th>
               <?php et("mp.$str") ?>
             </th>
           <?php endforeach ?>
           <th>&nbsp;</th>
         </tr>
       </thead>
      <?php $i = 0; foreach($ts as $t): ?>
      <tr id="tr-<?php echo $t['id'] ?>" class="<?php echo ($i % 2 == 0 ? "even" : "odd").' '.$this->th->getRowColor($t) ?>" onclick="tournamentRowClickHandler('<?php echo $t['id']?>')" data-status="<?php echo $t['status']?>">
        <?php $this->tournamentRow($t) ?>
      </tr>
      <?php $i++; endforeach; ?>
    </table>
  <?php }

  function printPreLoader()
    { ?>
        <div class="preloader-main-lobby">
            <div class="preloader-main-lobby__overlay"></div>
            <div class="preloader-main-lobby__content">
                <span class="preloader-main-lobby__content-animated-part"></span>
            </div>
        </div>

   <?php }

}
