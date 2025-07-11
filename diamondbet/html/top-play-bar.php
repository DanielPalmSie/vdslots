<?php
require_once __DIR__ . '/../../phive/phive.php';
phive()->sessionStart();
include_once('display.php');
$mg 	= phive('QuickFire');
$loc 	= phive('Localizer');
$pager 	= phive('Pager');
loadCss("/diamondbet/fonts/icons.css");

$top_logos = lic('topLogos', ['black']);
if(!empty($_REQUEST['lang']))
  $loc->setLanguage($_POST['lang'], true);

$qdep_link = depGo();
$user = cu();
if(!empty($user)){
  $pendings = phive('Cashier')->userPendingsByStatus($user->getId(), 'pending', array('flushed' => 0));
  $pending = empty($pendings) ? array() : $pendings[0];
}

if(!empty($_GET['eid'])){
  $t_entry = phive('Tournament')->entryById($_GET['eid']);
  $tournament = phive('Tournament')->getByEntry($t_entry);
}

$fast_psp_option_html = fastDepositIcon('fast-desktop', true);

?>
<script>
    var rewardsLong = 525;
    var rewardsShort = 350;
    <?php if(is_object($user)): ?>
    var rewardHeight = <?php echo phive('Trophy')->hasAnyReward($user) ? rewardsLong : rewardsShort ?>;

    function mboxRewards() {
        $.multibox({
            url: '<?php echo llink(phive()->getSiteUrl().'/rewards-popup/') ?>',
            id: "rewards-box",
            type: 'iframe',
            width: '702px',
            height: rewardHeight + 'px',
            globalStyle: {overflow: 'hidden'},
            overlayOpacity: 0.7,
            cls: 'mbox-rewards'
        });
    }

    $(document).ready(function () {

        <?php hasMp("mpStart('".phive('UserHandler')->wsUrl('mp-start')."');") ?>

        doWs('<?php echo phive('UserHandler')->wsUrl('rewardcount') ?>', function (e) {
            var res = JSON.parse(e.data);
            $("#reward-count").html(res.status0);
        });

        // get logout msg via websocket
        doWs('<?php echo phive('UserHandler')->wsUrl('logoutmsg'.substr(session_id(), 0, 5), false) ?>', function (e) {
            closeVsWS();
            mgSecureAjax({action: 'obsolete-session'});
            mboxMsg(e.data, true, function () {
                gotoLang("/?signout=true");
            }, 300, false, false);
        });

        $('.cancel-pending-top').click(function () {
            mgAjax({action: 'cancel-pending', id: $(this).attr('id').split('-')[1]}, function (ret) {
                $('.gpage-balance').hide();
                mboxMsg(ret, false, '', '365');
            });
        });

    doWs('<?php echo phive('UserHandler')->wsUrl('game-play-session') ?>', function (e) {
         var res = JSON.parse(e.data);
         $("#net-winnings").html(res.session.net_winnings);
     });

 });
 <?php endif ?>
</script>
<div class="top-play-bar">
  <div class="top-play-bar-content">

    <div class="topbar-logo topbar-txt">
        <img src="/diamondbet/images/<?= brandedCss() ?>logo_game_page.png" class="pointer" onclick="redirectHome()" />
    </div>
    <div class="topbar-logo over-age-play">
        <?= lic('rgOverAge', ['rg-top__item logged-in-time']); ?>
    </div>

    <?php

    $gameurl = $_GET['arg0'];
    $game = phive('MicroGames')->getByGameUrl($gameurl);
    $show_multiview = lic('hasMultiViewPlay');
    ?>
    <?php if (phive()->ieversion() != 10 && $show_multiview && empty($t_entry)) : ?>
      <div class="top-bar-pcontrols" data-current-grid="1" style="float:left;position:relative;height:20px;left:15px;top:-6px;">
          <img class="chgrd" data-target="1" onclick="useGridOfSize(1);" style="display:none;" src="/diamondbet/images/<?= brandedCss() ?>game_page2/1scr.png" alt="1 game win" />
          <img class="chgrd" data-target="2" onclick="useGridOfSize(2);" src="/diamondbet/images/<?= brandedCss() ?>game_page2/2scr.png" alt="2 columns grid" />
          <img class="chgrd" data-target="4" onclick="useGridOfSize(4);" src="/diamondbet/images/<?= brandedCss() ?>game_page2/4scr.png" alt="2x2 grid" />
      </div>
    <?php endif; ?>
    <?php if (!empty($top_logos) && !lic('getLicSetting', ['game_play_session'])): ?>
      <div class="top-bar-timer">
        <?= lic('rgLoginTime', ['rg-top__item logged-in-time']); ?>
      </div>
    <?php endif; ?>
    <?php if (lic('getLicSetting',['game_play_session'])): ?>
        <div class="game-play-session">
            <span class="game-play-session__icon icon icon-vs-clock-closed"></span>
            <div class="game-play-session__timer">
                <span class="timer-hour"></span>
                <span class="timer-min"></span>
                <span class="timer-sec"></span>
            </div>
            <?php if(empty($t_entry)): ?>
                <span class="game-play-session__icon icon icon-balance-alt1 margin-ten-left"></span>
                <span class="game-play-session__text">
                    <?php cs(true) ?><span id="net-winnings">0.00</span>
                </span>
            <?php endif ?>

            <script>licFuncs.Timer(licFuncs.AddTimerToDOM)</script>
        </div>
    <?php endif ?>

    <?php if(!isLogged()): ?>
        <?php if(!empty($top_logos)): ?>
            <div class="top-bar-menuitem no-border">
                <?php echo $top_logos ?>
            </div>
        <?php endif ?>
      <div style="float:right;">
        <?php drawLoginReg('top-play-login-btn') ?>
      </div>
    <?php else: ?>
      <div class="top-bar-menuitem top-bar-name">
        <?php if(!empty($t_entry)): ?>
          <div class="left mp-tname-top">
            <?php echo $tournament['tournament_name'] ?>
          </div>
        <?php endif ?>
        <div class="gpage-balance">
          <?php if(!empty($pending)): ?>
            <div class="margin-ten-left left"><?php et('gpage.pending') ?></div>
            <div class="margin-ten-left left"><?php efEuro($pending['amount']) ?></div>
            <div id="toppending-<?php echo $pending['id'] ?>" class="cancel-pending margin-ten-left pointer cancel-pending-top left"><?php et('cancel') ?></div>
          <?php endif ?>
        </div>
      </div>
      <?php if(!empty(lic('getLicSetting', ['show_panic_button']))): ?>
        <div class="topbar-logo panic-button-play">
          <?= lic('rgGameplayTopButton', []); ?>
        </div>
      <?php endif ?>
      <?php if(!empty($top_logos)): ?>
          <div class="top-bar-menuitem no-border">
              <?php echo $top_logos ?>
          </div>
      <?php endif ?>
      <?php licHtml('top_bar_participation_id') ?>
      <div class="topbar-cashier">
        <?php btnDefaultL(t('deposit'), '', $qdep_link) ?>
      </div>
      <?php licHtml('top_bar_add_funds') ?>
      <?php if(!empty($fast_psp_option_html)): ?>
          <div class="fast-deposit__container">
              <?php echo $fast_psp_option_html ?>
          </div>
      <?php endif ?>
      <?php if(is_object($user)):
      $award_count = phive('Trophy')->getUserAwardCount($user, array('status' => 0));
      if(!empty($award_count)):
      ?>
        <div class="no-border">
          <div class="topbar-rewards">
            <div class="icon icon-vs-gift" onclick="mboxRewards()"> </div>
          </div>
          <div id="reward-count" class="notifications-icon topbar-rewards-counter btn-cancel-default-l"><?php echo $award_count  ?></div>
        </div>
      <?php endif ?>
        <?php licHtml('game_topbar_balances', $user);?>
    <?php endif ?>
    <?php endif ?>
  </div>
</div>

<div class="game-bottom-wrapper" id="game-bottom-wrapper">
  <div class="game-bottom" id="game-bottom">
    <div class="game-bottom-button">
      <?php if(isLogged()): ?>
        <a id="signup-button" class="bigbutton" onclick="<?php echo $qdep_link ?>">
          <?php echo t('deposit') ?>
        </a>
      <?php else: ?>
        <a id="signup-button" class="bigbutton" href="<?php echo $loc->langLink('', '/?signup=true') ?>">
          <?php echo t('register') ?>
        </a>
      <?php endif ?>
    </div>
    <br clear="all"/>
    <br clear="all"/>
    <br clear="all"/>
    <div class="bottom-carousel">
      <ul id="mycarousel" class="jcarousel-skin-videoslots">
        <?php foreach(phive('MicroGames')->getPlayedOn() as $g): ?>
          <li>
            <img <?php jsOnClick(phive('MicroGames')->getUrl('', $g, false)) ?> src="<?php echo phive('MicroGames')->carouselPic($g) ?>" />
          </li>
        <?php endforeach ?>
      </ul>
    </div>
  </div>
</div>

<?php if(hasMp() && !phive('MicroGames')->blockMisc()): ?>
  <div class="right-fixed rot-90" onclick="showMpBox('/tournament')"><?php et('mps') ?></div>
<?php endif ?>

<?php $localstorage_key = phive('Localizer')->getSetting('ls_odds_format_key'); ?>

<?php $local_storage_key = phive('Localizer')->getSetting('ls_selected_outcome_key'); ?>

<script>
 setupLogin();

 <?php if(isLogged()): ?>
 // send notification via socket that the user is logged out
 doWs('<?php echo phive('UserHandler')->wsUrl('logoutmsg'.substr(session_id(), 0, 5), false) ?>', function (e) {
     closeVsWS();
     mgSecureAjax({action: 'obsolete-session'});
     mboxMsg(e.data, true, function () {
         gotoLang("/?signout=true");
     }, 300, false, false);
     <?php if($localstorage_key): ?>
     window.localStorage.removeItem('<?=$localstorage_key?>');
     <?php endif ?>
     <?php if($local_storage_key): ?>
        window.localStorage.removeItem('<?=$local_storage_key?>');
     <?php endif ?>
 });
 <?php endif ?>

 function redirectHome(){
     if((typeof extSessHandler !== 'undefined') && (typeof mpUserId === 'undefined') && (cur_country === 'ES')){
         gameCloseRedirection("goTo('<?php echo llink('/') ?>')");
     }
     else{
         goTo('<?php echo llink('/') ?>');
     }
 }
</script>
