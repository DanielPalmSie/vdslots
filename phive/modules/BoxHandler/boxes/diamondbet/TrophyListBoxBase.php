<?php

use Laraphive\Domain\User\DataTransferObjects\Responses\GetXpProfileResponseData;
use Laraphive\Domain\User\Factories\GetXpProfileFactory;

require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

class TrophyListBoxBase extends DiamondBox{

    protected $device_type = 0;

    function init(&$user, bool $is_api = false){
        $this->th = phive('Trophy');
        $this->pagination = phive("Paginator");
        if(!$is_api) {
            loadCSS("/diamondbet/fonts/icons.css");
        }

        if(empty($this->categories)) {
            $this->categories = $this->th->getCategories($user, 'category', '', 'trophy');
        }
        $this->cur_user = $this->user = $user;
        if(empty($user)) {
            $user = cuPl();
        }
        if(empty($user)) {
            die(jsRedirect("/", isset($_GET['load_box']))); // "true" when the page is loaded from AjaxBoxesForMobile (new game mode)
        }
        $this->username = $user->getUsername();
        $this->setTrTypes();
    }

  function onAjax(){
    $this->th = phive('Trophy');
    $this->user = cu($_REQUEST['uid']);
    if(empty($this->user))
      $this->user = cu();
  }

  function forfeitAwardNotInUse(){
    phive('Trophy')->deleteNonActiveAward($_SESSION['mg_id'], (int)$_REQUEST['id'], 2);
    echo 'ok';
  }

  function resetTrophies(){
    if($_SESSION['mg_id'] == $_REQUEST['uid'] && $this->th->hasCompleted($_SESSION['mg_id'], trim($_REQUEST['gref'])))
      $this->th->resetTrophies($_SESSION['mg_id'], trim($_REQUEST['gref']));
    echo 'ok';
  }


  function drawXpHeadlines(&$user){ ?>
      <div class="trophy-fullname-headline trophy-category-headline text-medium-bold"><?php echo html_entity_decode($user->getFullName(), ENT_QUOTES | ENT_XHTML) ?></div>
      <div class="trophy-username-headline trophy-category-headline header-big"><?php echo html_entity_decode($user->getUsername(), ENT_QUOTES | ENT_XHTML) ?></div>
  <?php }


  function drawBigAwardStar(){ ?>
      <div class="big-award-star"><img src="<?php fupUri('trophies/trophystar_accountpage.png') ?>" /></div>
  <?php }

  function drawSmallAwardStar(){ ?>
      <div class="small-award-star"><img src="<?php fupUri('trophies/trophystar_accountpage.png') ?>" /></div>
  <?php }

  function drawXpProgressBar($user, $width = 330){
    list($xp_points, $xp_next, $progress) = $this->th->getXpData($user);
  ?>
    <div class="trophy-level-txt text-medium-bold">
      <?php echo t('trophy.level').' '.$this->th->getUserXpInfo($user) ?>
    </div>
    <div class="trophy-status-txt text-medium-bold">
      <?php echo round($xp_points,2).' / '.$xp_next.' '.t('trophy.xp') ?>
    </div>
    <div class="xp-progressbar-bkg"></div>
    <div id="xp-progressbar-bar" class="xp-progressbar-bar gradient-trophy-bar" style="width: <?php echo $progress * $width  ?>px;"></div>
  <?php }

    /**
     * @return \Laraphive\Domain\User\DataTransferObjects\Responses\GetXpProfileResponseData
     *
     * @api
     */
    function getXpProfileData(): GetXpProfileResponseData
    {
        $user = cu();
        $this->init($user, true);

        list($xpPoints, $xpNext, $progress) = $this->th->getXpData($user);

        $userLevel = $this->th->getUserXpInfo($user);
        $progressDetails = round($xpPoints, 2) . ' / ' . $xpNext . ' ' . t('trophy.xp');
        $avatar = sprintf(
            "%s%s%s%s",
            phive()->getSetting('domain') . '/diamondbet/images/',
            brandedCss(),
            ucfirst($user->data['sex']),
            '_Profile.jpg'
        );

        $data = [
            'xp_points' => $xpPoints,
            'xp_next' => $xpNext,
            'user_level' => $userLevel,
            'progress' => $progress,
            'progress_details' => $progressDetails,
            'fullName' => $user->getFullName(),
            'username' => $user->getUsername(),
            'email' => $user->getAttribute('email'),
            'avatar' => $avatar,
            'current_bonus' => "NA"
        ];

        $factory = new GetXpProfileFactory();

        return $factory->createResponse($data);
    }

  function xpSection(&$user){
    $this->drawXpHeadlines($user);
    $this->drawBigAwardStar();
    $this->drawXpProgressBar($user);
  }

  function achievementsTopSection(&$user){
    $arr = $this->th->getAchievementStatuses($user, $this->categories);
    $chunks = array_chunk($arr, ceil(count($arr) / 2), true);
  ?>
     <div class="trophies-top simple-box">
       <table>
         <tr>
           <td>
             <table class="big-xp">
               <tr>
                 <td>
                   <div class="simple-box">
                     <?php $this->drawBigAwardStar() ?>
                   </div>
                 </td>
                 <td style="vertical-align: top;">
                   <div class="big-xp-progress">
                     <?php
                     $this->drawXpHeadlines($user);
                     $this->drawXpProgressBar($user, 470);
                     ?>
                   </div>
                 </td>
               </tr>
             </table>
           </td>
         </tr>
       </table>
     </div>
     <br/>
   <?php $this->profileAwardSections($user, $chunks);
  }

  function profileAwardSections($user, $chunks) { ?>
     <div class="trophies-top simple-box left">
       <div class="left" style="margin-left: 10px;">
         <h3><?php et('achievements.headline') ?></h3>
         <div class="left">
           <?php $this->awardSection($user, $chunks[0], false) ?>
         </div>
         <div class="left" style="margin-left: 25px;">
           <?php $this->awardSection($user, $chunks[1], false) ?>
         </div>
       </div>
     </div>
     <br clear="all"/>
   <?php
  }

  function profileTopSection(&$user){ ?>
      <div class="trophies-top simple-box">
        <table>
          <tr>
            <td class="trophy-top-left">
              <?php $this->xpSection($user) ?>
            </td>
            <td width="20px;"></td>
            <td class="trophy-top-right" width="310px;" style="vertical-align: top;">
              <?php $this->curBalances()  ?>
              <?php $this->printBankMenu() ?>
            </td>
          </tr>
         </table>
      </div>
  <?php
  }

  function curBalances(){
    $this->drawCurrentBalances(array(10, 200, 90, 10));
  }

  function printBankMenu(){
    $btn_width = 96;
  ?>
      <div class="left">
        <?php btnDefaultS(t('deposit'), '', depGo(), $btn_width, 'deposit-btn') ?>
      </div>
      <div class="left margin-ten-left">
        <?php btnDefaultS(t('withdraw'), '', withdrawalGo(), $btn_width, 'withdraw-btn') ?>
      </div>
      <div class="left margin-ten-left">
        <?php btnDefaultS(t('documents'), phive('UserHandler')->getUserAccountUrl('documents'), '', $btn_width, 'documents-btn') ?>
      </div>
      <?php
  }

  function printCSS(){
    loadCss("/diamondbet/css/" . brandedCss() . "game-chooser.css");
  }

  function printRewardsPage($user, &$pendings){
    $this->handleCancelPending();

    $this->profileTopSection($user);
      ?>
      <?php if(!empty($pendings)): ?>
        <br clear="all" />
        <div class="simple-box" style="padding: 10px;">
          <?php $this->printTrTable($pendings, 'pending.withdrawals') ?>
        </div>
      <?php endif ?>

      <?php $this->inUseRewardsSection($user) ?>

      <div class="simple-box left rewards-middle-box">
        <h3><?php et('active.rewards') ?></h3>
        <?php $this->activeRewardsSection($user) ?>
      </div>
      <div class="simple-box left rewards-middle-box margin-ten-left">
        <h3><?php et('latest.won.trophies') ?></h3>
        <?php $this->printLatestTrophies($user) ?>
      </div>
      <div class="left">
        <?php $this->drawRecentAccHistory(array(25, 250, 200, 155, 25), 'pad10 margin-ten-top') ?>
      </div>
      <br clear="all" />
      <br clear="all" />
  <?php
  }

  function getAwardUri(&$a, &$user){
    echo $this->th->getAwardUri($a, $user);
  }


  function getTrophyTitle($trophy):string {
        $trophy_title_text = "";

        //displaying titles only for trophies. no titles for freespins or rewards
      if (strpos($trophy['alias'], 'trophie') !== false) {
          $trophy_title = 'trophyname.'.$trophy['alias'];
          $trophy_title_text = t($trophy_title);
      }

      return $trophy_title_text;
  }

    /**
     * Print reward history HTML, on desktop it loads everything, on mobile X items on each "view more" click
     *
     * @param $user
     */
    public function rewardHistory(&$user)
    {
            $userId = is_object($user) ? $user->getId() : $user['id'];
            $where = 'trophy_award_ownership.status != 0 AND trophy_award_ownership.user_id = '.$userId;
            $this->moreinfoJs();
            $limit = $this->getLimit('rewardHistory');
            $totalAwards = $this->th->getUserAwardCount($user, $where);
            $this->pagination->setPages($totalAwards, '', $limit);
            $offset = phive("Paginator")->getOffset($limit);
            $paginatedAwards = $this->th->getUserAwardsHistory($user, $limit, $offset);
        ?>
        <div class="reward-history-container">
            <?php if ($totalAwards === 0): ?>
                <h3 class="reward-history-empty"><?= et('no.rewards.found'); ?></h3>
                <?php return; ?>
            <?php endif; ?>

            <?php foreach ($paginatedAwards as $a): ?>
                <?php $this->rewardDetailed($user, $a, false, false, true); ?>
            <?php endforeach; ?>

            <br clear="all"/>
            <br clear="all"/>

            <?php if (phive()->isMobile()): ?>
                <?php $this->printViewMoreButton('.reward-history-container .reward-detailed:last', 'TrophyListBox', 'getMoreRewards', $limit); ?>
            <?php else: ?>
                <?php $this->pagination->render(); ?>
            <?php endif; ?>
        </div>
        <br clear="all"/>
        <br clear="all"/>
        <?php
    }

      function activateJs(){ ?>
          <script>
           var rewardClicked = false;
           function activateAwardOkCancel(id, onParent){
               if(rewardClicked) {
                   return;
               }
               rewardClicked = true;
               var me = onParent == true ? parent : window;
               showLoader(function(){
                   mgJson({aid: id, action: 'use-trophy-award'}, function(award){
                       if(empty(award.error)){
                           if(award.type == 'deposit' || award.type == 'top-up'){
                               me.mboxDeposit('/cashier/deposit/', jsReloadBase);
                           }else if(!empty(award.bonus_id)){
                               <?php if(siteType() === 'normal'): ?>
                               me.playBonus(award.bonus_id);
                               <?php else: ?>
                               me.playMobileGame(award.mobile_game_ref);
                               <?php endif ?>
                           }else if(award.type == 'wheel-of-jackpots'){
                               if((typeof top.extSessHandler !== 'undefined') && (typeof top.mpUserId === 'undefined') && (top.cur_country === 'ES')){
                                   top.extSessHandler.showGameSummary("goTo(" + '\'/' + cur_lang + '/the-wheel-of-jackpots/\'' + ",\'_top\'" + ")");
                               }
                               else{
                                   window.top.location.href = '/' + cur_lang + '/the-wheel-of-jackpots/';
                               }
                           }else {
                               mboxMsg("<?php et('reward.activated.msg') ?>", true, jsReloadWithParams, undefined, undefined, undefined, "<?php et('msg.title') ?>");
                           }

                       }else{
                           mboxMsg(award.error, undefined, undefined, undefined, undefined, undefined, "<?php et('msg.title') ?>");
                       }
                   }).always(function() {
                       rewardClicked = false;
                   });
               });
           }
          </script>
   <?php
  }

    /**
     * Print the detailed reward HTML
     *
     * @param $user
     * @param $a - award
     * @param bool $show_progress
     * @param bool $show_buttons
     * @param bool $hide_moreinfo
     * @param bool $list
     */
    public function rewardDetailed(&$user, &$a, $show_progress = true, $show_buttons = true, $list = false, $hide_moreinfo = false){
      if(empty($a))
          return;

       $show_forfeit_btn = true;
       if(!isLogged())
           $show_buttons = true;

       $a = is_numeric($a) ? array_pop($this->th->getUserAwards($user, ' != 0', '', $a)) : $a;

       if($show_progress)
          $details = $this->th->getRewardInUseDetails($user, $a);
      else
          $details['exp_date'] = $a['expire_at'];

      $bar_width = siteType() === 'normal' ? 350 : 150;
      if($a['type'] == 'mp-freeroll-ticket'){
          $show_progress = false;
          $hide_finished_date = $list ? false : true;
          $show_forfeit_btn = $list ? false : true;
      }

      if($a['type'] == 'freespin-bonus' && $a['be_id'] != null && $a['be_status'] == 'active'){
        $be_flag = true;
        $show_forfeit_btn = false;
        $show_progress = true;
        $hide_finished_date = true;
        $show_buttons = true;
        $details['progress'] = phive("Bonuses")->progressPercent(phive('Bonuses')->getBonusEntry($a['be_id'], $user->getId()));
        $details['status'] = $a['be_status'];
        $forfeit_bonus_button = phive("Bonuses")->getForfeitBonusFlag($a['bonus_id']);
        $has_ongoing_session = phive("Casino")->checkPlayerIsPlayingAGame($user->getId());

        if($forfeit_bonus_button){
          $show_forfeit_btn = true;
        }
    }
  ?>
      <div class="simple-box pad10 margin-ten-top left reward-detailed">
      <h3>
          <?php echo ucfirst(rep(tAssoc("rewardheadline.{$a['type']}", $a))) ?>
          <?php if($show_progress): ?>
              <span class="active-txt"><?php et('is.active') ?></span>
          <?php endif ?>
      </h3>
      <?php if(empty($hide_moreinfo)): ?>
          <img class="moreinfo" moreinfopicid="<?php echo $a['tao_id'] ?>" src="/diamondbet/images/<?= brandedCss() ?>moreinfo.png"/>
          <div id="<?php echo $a['tao_id'] ?>-more-info-box" class="trophy-infobox moreinfo-info">
              <?php echo rep(tAssoc("rewardtype.{$a['type']}", $a)) ?>
          </div>
      <?php endif; ?>
      <div class="simple-frame left pad10">
          <img src="<?php $this->getAwardUri($a, $user) ?>" />
      </div>
      <?php  ?>
      <div class="left margin-ten-left active-reward-area">
          <table class="zebra-tbl">
              <tr class="odd">
                  <td><?php et('expire.date')  ?></td>
                  <td><?php echo lcDate($details['exp_date']) ?></td>
              </tr>
              <?php if($show_progress): ?>
                  <tr class="even">
                      <td><?php et('days.left')  ?></td>
                      <td><?php echo phive()->subtractTimes($details['exp_date'], phive()->hisNow(), 'd') ?></td>
                  </tr>

                  <tr class="odd">
                  <td><?php et('bonus.status')?></td>
                  <td>
                    <?php if ($details['status'] == 'active'): ?>
                      <span class="active-txt"><?php et('is.active') ?></span>
                    <?php endif ?>
                  </td>
                  </tr>

              <?php endif ?>

                  <tr class="even">
                      <td><?php et('activated.date')  ?></td>
                      <td><?php lcDate($a['activated_at']) ?></td>
                  </tr>
                  <?php if($hide_finished_date !== true): ?>
                      <tr class="odd">
                          <td><?php et('finished.date') ?></td>
                          <td><?php lcDate($a['finished_at']) ?></td>
                      </tr>
                  <?php endif ?>
                  <?php if(!$show_progress): ?>
                  <?php if((int)$a['status'] !== 0): ?>
                      <tr class="even">
                          <td><?php et('reward.status')  ?></td>
                          <td><?php echo et("reward.status.{$a['status']}") ?></td>
                      </tr>
                  <?php endif ?>
              <?php endif ?>
          </table>
          <?php if(isset($a['status']) && (int)$a['status'] === 0): ?>
            <?php if ($a['type'] == 'mp-ticket'): ?>
              <div class="reward-action-area">
                  <?php btnDefaultS(t('mps'), '', "goToMobileBattleOfSlots('". phive('Tournament')->getSetting('mobile_bos_url')."')", 234) ?>
              </div>
            <?php else: ?>
              <div class="reward-action-area">
                  <?php btnDefaultS(t('activate'), '', "activateAwardOkCancel('{$a['id']}', false)", 117) ?>
                  <?php btnCancelDefaultS(t('forfeit'), '', "forfeitAwardOkCancel('{$a['id']}')", 117) ?>
              </div>
            <?php endif ?>
          <?php endif ?>

          <?php if($show_progress): ?>
            <div class="bonus-progress-holder">
            <div class="award-progressbar-bkg bonus-progressbar-bkg"></div>
              <div class="award-progressbar-bar gradient-trophy-bar bonus-progressbar-bar" style="width: <?php echo ($details['progress']/100) * $bar_width ?>px;"></div>
              <div class="bonus-entry-progress-txt"><?php echo $details['progress']."%" ?></div>
               <?php if($show_forfeit_btn): ?>
                  <div class="bonus-entry-btn-holder">
                  <?php if($be_flag):?>
                      <?php if($show_buttons):?>
                          <?php btnCancelDefaultXs(t('forfeit'), '', "failBonusConfirm({$a['be_id']});", 100);?>
                      <?php endif?>
                  <?php else: ?>
                      <?php if($show_buttons) btnCancelDefaultXs(t('forfeit'), '', "forfeitAwardOkCancel('{$a['id']}')", 117) ?>
                  <?php endif ?>
                  </div>
            <?php endif ?>
            </div>
          <?php endif ?>
      </div>
              </div>
   <?php
   ?>
   <script>
    function failBonusConfirm(entry_id){
      var onClick = "$.ajax({ \
                        url: '/account?action=deletebonusentry&id=" + entry_id + "', \
                        type: 'GET', \
                    }) \
                    .done(function(response) { \
                      window.location.reload(); \
                    }) \
                    .fail(function(xhr, status, error) { \
                      console.error('Error deleting bonus entry:', error); \
                    }) \";
      $("button[onclick='deletebonus']").attr('onclick', onClick);

      <?php if($has_ongoing_session):?>
        mboxMsg($("#on-going-game-session").html(), false, undefined, undefined, undefined, undefined, "<?php et('msg.title') ?>");
      <?php else: ?>
        mboxMsg($("#fail-confirm").html(), false, undefined, undefined, undefined, undefined, "<?php et('msg.title') ?>");
      <?php endif?>
    }

   </script>
   <?php
   failBonusConfirm();
   cannotForfit();
  }

    /**
     * Print the HTML for the rewards that is returned by the click on "load more" button
     */
    public function getMoreRewards()
    {
        $user = cu();

        $offset = (int) $_GET['offset'];
        $limit = $this->getLimit('rewardHistory');
        $rewards 	= $this->th->getUserAwards($user, " != 0", "tao.created_at DESC", '', '', '', false, $limit, $offset);

        foreach($rewards as $a) {
            $this->rewardDetailed($user, $a, false, false, true);
        }
    }

	/**
     * Show game bottom section with the two active bonuses
     *
	 * @return void
	 */
	function playBottom()
    {
		  $progress = phive('Bonuses')->getActiveBonusesForProgress(cu());
		?>

        <div class="current-reward-holder">
			<?php if (!empty($progress['freespin']['bonus_entry'])): ?>
                <div>
                    <img class="reward-img" src="<?php !empty($a) ? $this->getAwardUri($a, cu()) : phive('Bonuses')->doPic($progress['freespin']['bonus_entry']) ?>" />
                    <div class="reward-element progress-bar-top-parent">
                        <div class="progress-bar-holder">
                            <div class="award-progressbar-bkg"></div>
                            <div id="freespin-reward-progress-bar" class="award-progressbar-bar gradient-trophy-bar" style="width: <?php echo $progress['freespin']['progress_width'] ?>px;"></div>
                        </div>
                    </div>
                    <div id="freespin-reward-progress" class="reward-element">
						          <?php echo $progress['freespin']['progress'] ?>
                    </div>
                </div>

                <div class="divider"></div>
			<?php endif; ?>

			<?php if (!empty($progress['welcome-bonus']['bonus_entry'])): ?>
                <div>
                    <img class="reward-img" src="<?php phive('Bonuses')->doPic($progress['welcome-bonus']['bonus_entry']) ?>" />

                    <div class="reward-element progress-bar-top-parent">
                        <div class="progress-bar-holder">
                            <div class="award-progressbar-bkg"></div>
                            <div id="welcome-reward-progress-bar" class="award-progressbar-bar gradient-trophy-bar" style="width: <?php echo $progress['welcome-bonus']['progress_width'] ?>px;"></div>
                        </div>
                    </div>
                    <div id="welcome-reward-progress" class="reward-element">
						<?= $progress['welcome-bonus']['progress'] ?>
                    </div>
                </div>
			<?php endif; ?>
        </div>
		<?php
	}

      function inUseRewardsSection(&$user, $more_info_js = true){
          if($more_info_js)
              $this->moreinfoJs();
          //$aid = $user->getSetting('current-multiply-award-id');
          $settings = $this->th->getAllCurrent($user);
          foreach($settings as $setting)
              $this->rewardDetailed($user, $setting['value']);
          $this->printBonusJs();
          $this->handleDeleteBonusEntry();
          $cb = phive('Bonuses')->getCurrentActive($user);
          if(!empty($cb))
              $this->printBonus($cb, true);
      }

  function moreinfoJs(){ ?>
  <script>
    $(document).ready(function(){
        var showHide = function(curEl, action, event){
            var element = $(curEl);
            var id = element.attr('moreinfopicid');
            var infoBox = $("#"+id+"-more-info-box");
            // desktop
            if(event != 'click') {
                if(action == 'show') {
                    infoBox.show();
                    moveTo(infoBox, element, {top: -70, left: -43});
                    return;
                }
                if(action == 'hide') {
                    infoBox.hide();
                }
            } else { // mobile
                // First close other info popups
                $(".moreinfo-info").hide();
                $(".reward-detailed").removeClass('infobox-spacing');

                // Then show the info box ABOVE the box it belongs to
                infoBox.show();

                $(element).parent().addClass('infobox-spacing');
            }
        }
        var eventTypeOn = 'click';
        var eventTypeOff = 'click';
        if(!isMobile()) {
            eventTypeOn = 'mouseover';
            eventTypeOff = 'mouseout';
        }
        $('.reward-history-container').on(eventTypeOn, '.moreinfo', function() {
            showHide(this, 'show', eventTypeOn);
        }).on(eventTypeOff, '.moreinfo', function() {
            showHide(this, 'hide', eventTypeOff);
        });
    });
  </script>
  <?php
  }

  function selectedReward($aid = ''){
    if(empty($this->cur_user))
      $this->cur_user = cuPl();
    $aid = empty($aid) ? $_REQUEST['aid'] : $aid;
    $status = isset($_REQUEST['status']) ? " = {$_REQUEST['status']}" : '';
    $a = array_shift($this->th->getUserAwards($this->cur_user, $status, '', $aid, siteType() === 'normal' ? '' : 1));
    $this->activateJs();
    $this->rewardDetailed($this->cur_user, $a, false, true, false, true);
  }

  function myRewards(&$user){
  ?>
  <script>
   $(document).ready(function(){
     $('.reward-img').each(function(){
       var me = $(this);

         if(!isNumber(me.attr('aid'))){
             // We don't apply the click event on something that is already active or can not be used.
             return;
         }

       me.click(function(){
         ajaxGetBoxHtml({aid: me.attr('aid'), func: 'selectedReward', status: 0}, cur_lang, <?php echo (($this->device_type === 1) ? "'MobileTrophyListBox'" : "'TrophyListBox'"); ?>, function(ret){
           $('#reward-holder').html(ret);
         });
       });
     });
   });
  </script>
  <div class="general-account-holder">
    <?php $this->inUseRewardsSection($user) ?>
    <div id="reward-holder">
    </div>
    <div id="msgBox" class="simple-box left"></div>
    <div class="simple-box left rewards-middle-box">
      <h3><?php et('active.rewards') ?></h3>
      <?php $this->activeRewardsSection($user) ?>
    </div>
  </div>
  <?php
  }

  function activeRewardsSection(&$user, $parent = 'false', $awards = array()){
    if(empty($awards))
      $awards = $this->th->getUserAwards($user, " = 0", "rewarded_at DESC", '', siteType() === 'normal' ? '' : 1);
    $this->activateJs();
  ?>
   <script>

    function forfeitAwardOkCancel(aid){
      mboxDialog("<?php et('reward.forfeit.confirm.msg') ?>", 'mboxClose()', "<?php et('no') ?>", "forfeitAwardNotInUse('"+aid+"')", "<?php et('yes') ?>", false, 280, undefined, undefined, "<?php et('confirm.title') ?>");
    }

    function forfeitAwardNotInUse(aid){
      ajaxGetBoxHtml({id: aid, func: 'forfeitAwardNotInUse'}, cur_lang, <?php echo (($this->device_type === 1) ? "'MobileTrophyListBox'" : "'TrophyListBox'"); ?>, function(ret){
        jsReloadBase();
      });
    }

    function activateReward(self) {

        if(self.attr('placeholder') == 'true')
            return;

        var id = self.attr('aid');
        var atype = self.attr('atype');

        if(atype == 'mp-ticket'){
          <?php if(siteType() === 'normal'): ?>
            window.parent.showMpBox('/tournament/?award_id=' + id);
          <?php else: ?>
            goToMobileBattleOfSlots('<?php echo phive('Tournament')->getSetting('mobile_bos_url'); ?>', {type: 'award', id: id});
          <?php endif; ?>
            return;
        }

        if(id == 'bonus'){
            mboxMsg("<?php et('reward.noactivate.bonus.msg') ?>", true, false, 200, undefined, undefined, "<?php et('msg.title') ?>");
            return;
        }

        if(id == 'multiply'){
            mboxMsg("<?php et('reward.noactivate.multiply.msg') ?>", true, false, 200, undefined, undefined, "<?php et('msg.title') ?>");
            return;
        }
        activateAwardOkCancel(id, <?php echo $parent ?>);
    }

    $(document).ready(function(){
        $(document).on('click', '.reward-img', function(){

            var self = $(this);

            <?php if(siteType() === 'normal'): ?>
                activateReward(self);
            <?php endif; ?>
        });

        window.parent.$("#multibox-overlay-rewards-box").mousedown(function(e) { // clossing the modal when user clicks outside
          var container = $("#rewards-box");
           // if the target of the click isn't the container nor a descendant of the container
          if (!container.is(e.target) && container.has(e.target).length === 0) window.parent.$.multibox('close', 'rewards-box');
        });
    });
   </script>
   <?php foreach($this->placeholderFill($awards) as $a):
   $can_use = $this->th->canUseAward($user, $a);
   ?>
     <div class="trophy-container">
       <?php if(empty($a)): ?>
         <img class="trophy-img reward-img" placeholder="true"  src="<?php $this->getAwardUri($a, $user) ?>" title="<?= $this->getTrophyTitle($a) ?>" />
       <?php else: ?>
         <img atype="<?php echo $a['type'] ?>" aid="<?php echo $can_use !== true ? $can_use : $a['id'] ?>"
              id="<?php echo $a['alias'].$a['tao_id'].'-img' ?>" class="trophy-img reward-img" src="<?php $this->getAwardUri($a, $user) ?>" title="<?= $this->getTrophyTitle($a) ?>" />
       <?php endif ?>
        </div>
        <?php if(!empty($a)): // Also show this on mobile ?>
        <div id="<?php echo $a['alias'].$a['tao_id'].'-info' ?>" class="trophy-infobox reward-infobox" style="display: none;">
          <div>
            <?php if($a['type'] === 'mp-ticket'): ?>
              <?php echo t('get').' '.rep(tAssoc("rewardtype.{$a['type']}-tooltip", $a)) ?>
            <?php else: ?>
              <?php echo t('get').' '.rep(tAssoc("rewardtype.{$a['type']}", $a)) ?>
            <?php endif ?>
            <br/>
            <hr class="thin-line"/>
            <span class="bonus-expire-date"><?php echo t('expire.date').': '.lcDate($a['expire_at'], false) ?></span>
          </div>
        </div>
        <?php endif ?>
      <?php endforeach ?>
   <?php
   }

   function awardSection(&$user, $a_statuses = array(), $show_headline = true){
     if(empty($a_statuses))
       $a_statuses = $this->th->getAchievementStatuses($user, $this->categories);
   ?>
     <?php if($show_headline): ?>
       <h3><?php et('achievements.headline') ?></h3>
     <?php endif ?>
     <?php foreach($a_statuses as $cat => $info): ?>
       <div class="award-section-line">
         <div class="activity-category-description">
           <?php et("trophy.$cat.category") ?>
         </div>
         <img class="activity-progress-image" src="<?php fupUri("trophies/achivementbar_{$info['user_prog']}.png") ?>" />
         <div class="activity-progress-txt"><?php echo $info['user_count'].'/'.$info['tot_count'] ?></div>
       </div>
      <?php endforeach ?>
  <?php
  }

  function printSubWrap(&$sub, $sub_cat_attr=null){
    if(is_array($sub)){
      $sub_cat = $sub[0]['sub_category'];
      $show = true;
    }else{
      $sub_cat = $sub;
      $sub = array();
      $show = false;
    }
    if (!empty($sub_cat_attr)) {
      $sub_cat = $sub_cat_attr;
    }
  ?>
      <div id="trophy-subcont-<?php echo trim($sub_cat) ?>" style="<?php if(!$show) echo "display: none;" ?>">
        <?php $this->printSub($sub) ?>
      </div>
  <?php }

  function printSection($str, $sub_cat = '', $click_func = 'getSubTrophies', $print_wrap = true, $hl = array()){
      $resetTrophiesClass = $hl['completed'] ? 'pointer reset-trophies' : '';
      $resetTrophiesOnclick = $hl['completed'] ? 'resetTrophies(\''.$hl['sub_category'].'\','.$hl['user_id'].', this)' : '';
      $resetTrophiesColorClass = $hl['reset_col'] === 'green' ? "zero-opacity" : '';
  ?>
  <table class="trophy-category-section w-100-pc" id="<?php echo empty($sub_cat) ? '' : "trophy-headline-".trim($sub_cat) ?>">
    <tr>
      <td style="width: 10%;">
        <?php if($hl['can_reset']): ?>
          <?php if(useOldDesign()): ?>
                <img class="<?= $resetTrophiesClass ?>" src="/diamondbet/images/<?= brandedCss() ?>reload_<?php echo $hl['reset_col'] ?>.png" <?php if($resetTrophiesOnclick): ?>onclick="<?= $resetTrophiesOnclick ?>"<?php endif; ?> />
          <?php else: ?>
                <button class="new-reset-icon <?= $resetTrophiesClass ?> <?= $resetTrophiesColorClass ?>"
                    <?php if($resetTrophiesOnclick): ?>onclick="<?= $resetTrophiesOnclick ?>"<?php endif; ?>
                >
                    <span class="icon icon-refresh"></span>
                </button>
          <?php endif ?>
        <?php endif ?>
      </td>
      <td style="width: 40%;">
        <div class="trophy-category-headline text-medium-bold <?php if(!$print_wrap) echo "selected-color" ?>">
          <?php echo "$str" ?>
        </div>
      </td>
      <td style="width: 45%;"><hr class="thin-line"/></td>
      <td style="width: 5%;"><img class="expand-toggle" src="/diamondbet/images/<?= brandedCss() ?><?php echo $print_wrap ? 'plus' : 'minus' ?>.png" onclick="<?php echo "$click_func('".trim($sub_cat)."', '{$this->user->getId()}')" ?>"/></td>
    </tr>
  </table>
  <?php
  if($print_wrap)
    $this->printSubWrap($sub_cat);
  }

  function trophyStrSearch(){
    $this->grouped = phive('Trophy')->getUserTrophies($_SESSION['mg_id'], $_REQUEST['substr'], '', 'sub_category', true, $_REQUEST['type']);
    if(empty($this->grouped))
      die(t('trophy.empty.search.result'));
    $this->printTrophies();
  }

  function printTrophySrc(&$t){
    if(empty($t))
      fupUri("events/trophy_placeholder.png");
    else
      fupUri("events".(empty($t['finished']) ? '/grey' : '')."/{$t['alias']}_event.png");
  }

  function placeholderFill($arr){
    return array_slice(array_merge($arr, array_fill(0, 16, null)), 0, 16);
  }

  function printLatestTrophies(&$user){
      $this->printTrophyJs($user);
      foreach($this->placeholderFill($this->th->getLatestTrophies($user)) as $t)
          $this->printTrophy($t);
  }

  function printTrophy(&$t, $prog_bar = true, $str_prefix = 'trophyname'){
    if(!empty($t['hidden']) && empty($t['finished']))
      return;
    $this->sub_empty = false;
  ?>
  <div class="trophy-container">
    <img id="<?php echo empty($t) ? '' : $t['alias'].'-img' ?>" class="trophy-img" src="<?php echo $this->th->getTrophyUri($t) ?>" title="<?= $this->getTrophyTitle($t) ?>" />
    <?php if(!empty($t['repeatable']) && !empty($t['cnt'])): ?>
      <div class="cnt-icon btn-cancel-default-l">
        <?php echo $t['cnt'] ?>
      </div>
    <?php endif ?>
  </div>

  <div id="<?php echo $t['alias'].'-info' ?>" class="trophy-infobox" style="display: none;">
    <div class="text-medium-bold">
      <?php et($str_prefix.'.'.$t['alias']) ?>
    </div>
    <?php echo rep(tAssoc('trophy.'.$this->th->getDescrStr($t).'.descr', $t), $this->user, true) ?>
    <?php if(empty($t['repeatable']) && $prog_bar): ?>
      <div class="trophy-progressbar-bkg"></div>
      <div id="trophy-progressbar-bar" class="trophy-progressbar-bar gradient-trophy-bar" style="width: <?php echo $this->th->getTrophyProgress($t) * 85 ?>%;"></div>
    <?php endif ?>
  </div>
  <?php
  }

  function printTrophyHeadlines($sub_cat = '', $show_first = true){
      $this->th = phive('Trophy');
      $cat      = $_REQUEST['category'] == 'all' ? '' : $_REQUEST['category'];
      $sub_cat  = $_REQUEST['sub_category'] == 'all' ? '' : $_REQUEST['sub_category'];
      $uid = $this->user->getId();

      if(empty($uid))
          $uid      = empty($_REQUEST['uid']) ? $_SESSION['mg_id'] : $_REQUEST['uid'];
      if(empty($this->grouped))
          $this->grouped = $this->th->getUserTrophiesHeadlines($uid, false, $_REQUEST['type'], $cat, $sub_cat);
      $this->grouped = $this->searchSubstringInTrophies($_REQUEST['substr'], $this->grouped);
      if(empty($this->grouped))
          die(t('trophy.empty.search.result'));
      foreach($this->grouped as $head){
          $this->printSection(
              $head['headline'],
              $head['sub_category'],
              $show_first ? 'hideSubTrophies' : 'getSubTrophies',
              !$show_first,
              $head
          );

          if($show_first){
              $sub = phive('Trophy')->getSub($uid, $head['sub_category'], $_REQUEST['type']);
              $this->printSubWrap($sub);
          }

          $show_first = false;
      }
  ?>
  <div id="reset-info" class="trophy-infobox" style="display: none;">
      <div class="text-medium-bold"> <?php et('restart.your.trophies') ?> </div>
      <?php et('restart.your.trophies.descr') ?>
  </div>
  <?php
  }

  function searchSubstringInTrophies($sub_string='', $trophies_array=[]) {
    $result =[];

    if (empty($sub_string)) {
      return $trophies_array;
    }

    $substring_is_not_in_the_text = function ($substr, $text) {
      $substring_is_empty =  empty($substr);
      $is_searched_substring_in_game_name = is_numeric(
        strpos(
          strtolower($text), strtolower($substr)
        )
      );
      return (!$substring_is_empty && !$is_searched_substring_in_game_name);
    };

    foreach($trophies_array as $key => $head){

      $trophy_names_string = '';
      foreach (explode(',', $head['trophy_names']) as $trophy_name) {
        $trophy_names_string .= t('trophyname.'.$trophy_name);
      }
      if($substring_is_not_in_the_text($sub_string, $head['game_name'].$head['network'].$head['operator'].$head['sub_category'].$head['category'].$trophy_names_string))
        continue;

      $result[$key] = $head;
    }

    return $result;
  }

  function printSub(&$sub){
    if(empty($sub))
      return false;
    $this->sub_empty = true;
    foreach($sub as &$t)
      $this->printTrophy($t);
    if($this->sub_empty)
      et('trophy.no.completed.yet');
  ?>
  <?php if(phive('Trophy')->subHasTm($sub)): ?>
    <div class="trademark-txt">
    <?php echo et("trophy.{$sub[0]['game_ref']}.trademark") ?>
    </div>
  <?php endif;
  }

  function printBySub(){
    $sub = phive('Trophy')->getSub($_REQUEST['uid'], $_REQUEST['sub_category'], $_REQUEST['type']);
    $this->printSub($sub);
  }

  function printTrophies(){
      $this->th = phive('Trophy');
      $cat      = $_REQUEST['category'] == 'all' ? '' : $_REQUEST['category'];
      $sub_cat  = $_REQUEST['sub_category'] == 'all' ? '' : $_REQUEST['sub_category'];
      $uid      = empty($_REQUEST['uid']) ? $_SESSION['mg_id'] : $_REQUEST['uid'];
      if(empty($this->grouped))
          $this->grouped = $this->th->getUserTrophies($uid, $cat, $sub_cat, 'sub_category', false, $_REQUEST['type'], '', "ORDER BY t.category, t.sub_category, t.type DESC, t.threshold");
      if(empty($this->grouped))
          die(t('trophy.empty.search.result'));
      foreach($this->grouped as $gref => $sub){
          $this->printSection($this->th->getTrophySectionHeadline($sub[0]),$sub[0]['sub_category_column'], 'hideSubTrophies', false);
          $this->printSubWrap($sub, $sub[0]['sub_category_column']);
      }
  }

    function printSubSel(){
      if(empty($this->sub_categories))
        $this->sub_categories = phive('Trophy')->getCategories($this->user, 'sub_category', $_REQUEST['category'] == 'all' ? '' : $_REQUEST['category'], 'trophy');
      dbSelect('sub_category', $this->sub_categories, $this->sub_categories, array('all', t('all.trophy.subcategories')));
    }

    function printGinfoId(){
    ?>
    <script>
     function getInfoId(imgEl){
       var tmp = imgEl.attr('id').split('-');
       tmp.pop();
       var alias = tmp.join('-');
       return "#"+alias+"-info";
     }

     function getImgId(infoElement) {
       var tmp = infoElement.attr('id').split('-');
       tmp.pop();
       var alias = tmp.join('-');
       return "#"+alias+"-img";
     }
    </script>
    <?php
    }

    function printBubbleJs(&$user){
      $this->printGinfoId();
    ?>
    <script>
     function setupBubbles(){
       $(".reset-trophies").mouseover(function(){
         pos = $(this).offset();
         $("#reset-info").css({left: pos.left - 42, top: pos.top - 80}).show();
       });

       $(".reset-trophies").mouseout(function(){
         $("#reset-info").hide();
       });

       $(".trophy-img").each(function(i){

         if(empty($(this).attr('id')))
           return;

         $(this).mouseover(function(){
           //$(".trophy-infobox").hide();
           pos = $(this).offset();
           $(getInfoId($(this))).show().offset({left: pos.left-22, top: pos.top-70});
         });

         $(this).mouseout(function(){
           $(getInfoId($(this))).hide();
         });

       });
     }
    </script>
    <?php
    }

    function printTrophyJs(&$user){
      if ($GLOBALS['site_type'] != 'mobile')
        $this->printBubbleJs($user);
    ?>
    <script>
	 var sTropyBox = <?php echo (($this->device_type === 1) ? "'MobileTrophyListBox'" : "'TrophyListBox'"); ?>;

     function hideSubTrophies(sub, uid, skip_escape_selector = false){
        if(isNaN(sub)) { // Check required when subcategory has string number.
            sub = !skip_escape_selector ? $.escapeSelector(sub) : sub;
        }
        var headline = $("#trophy-headline-"+sub).find('.trophy-category-headline');
        headline.removeClass('selected-color');
        var clicker = $("#trophy-headline-"+sub).find('.expand-toggle');
        clicker.attr('src', '/diamondbet/images/<?= brandedCss() ?>plus.png');
        $("#trophy-subcont-"+sub).hide(200, function(){
            clicker.one('click', function(){
               getSubTrophies(sub, uid, {calledFromHideClick: true});
            });
        });
     }

     function getSubTrophies(sub, uid, options){
         var type = getSelectedType();
         ajaxGetBoxHtml({sub_category: sub, uid: uid, func: 'printBySub', type: type}, cur_lang, sTropyBox, function(ret){
             /**
              * If calledFromHideClick is true then using the sub because if already string has backslash
              * to avoid to add more backslash else using the escapeSelector.
              * Here escapeSelector jquery function is used to add backslash(\) in front of any special character
              * found in the passed string
              */
             var subCategory = sub;
             if(isNaN(sub)) {
                 subCategory = (options !== undefined && options.calledFromHideClick) ? sub : $.escapeSelector(sub);
             }
             var sel = "#trophy-subcont-"+subCategory;
             $(sel).html(ret).show(200);
             var headline = $("#trophy-headline-"+subCategory).find('.trophy-category-headline');
             var clicker = $("#trophy-headline-"+subCategory).find('.expand-toggle');
             headline.attr('onclick', '').addClass('selected-color');
             clicker.off('click').attr('onclick', '').attr('src', '/diamondbet/images/<?= brandedCss() ?>minus.png');
             clicker.click(function(){
                 var skip_escape_selector = true;
                 hideSubTrophies(subCategory, uid, skip_escape_selector);
             });
             if (siteType != 'mobile')
                 setupBubbles();
         });
     }

     function updateSubSel(cat, type){
       type = empty(type) ? getSelectedType() : type;
       ajaxGetBoxHtml({category: cat, type: type, func: 'printSubSel', uid: <?php echo $user->getId() ?>}, cur_lang, sTropyBox, function(ret){
         $("#sub-sel-holder").html(ret);
         initSubSel();
       });
     }

     function getSelCat(){
       return $("#category").val();
     }

     function getSelSubCat(){
       return $("#sub_category").val();
     }

     function listTrophies(cat, sub, type){
       type = empty(type) ? getSelectedType() : type;
       cat = empty(cat) ? getSelCat() : cat;
       sub = empty(sub) ? getSelSubCat() : sub;

       var str = $("#search_str").val();
       if (str.length < 3) {
         str = "";
       }

       showLoader(function() {
        var params = {category: cat, sub_category: sub, substr: str, func: 'printTrophyHeadlines', type: type, uid: <?php echo $user->getId() ?>};
        ajaxGetBoxHtml(params, cur_lang, sTropyBox, function(ret){
          $("#trophy-area").html(ret);
          if (siteType != 'mobile') {
            setupBubbles();
          }
        });
       }, false, "<?php et('filtering.trophies'); ?>");

     }

     /**
      * Calculates and sets the maxHeight of selectbox dropdown from
      * jquery plugin '_openSelectbox' function inside 'jquery.selectbox-0.1.3.js'.
      */
     function setMaxHeightDropdown(inst, elementClass) {
         var el = $("#sbOptions_" + inst.uid),
             viewportHeight = parseInt($(window).height(), 10),
             offset = $("#sbHolder_" + inst.uid).offset(),
             scrollTop = $(window).scrollTop(),
             height = el.prev().height(),
             diff = viewportHeight - (offset.top - scrollTop) - height / 2;

         var elementHeight = (parent.$(elementClass).height() || 0 ) + parseInt(parent.$(elementClass).css('bottom') || 0);
         var trophyAreaHeight = $("#trophy-area").height();
         var trophyHeadlineLoginHeight = $('#trophy-headline-login').height();
         var maxHeight = diff - height - elementHeight;
         if(maxHeight > trophyAreaHeight) {
             if(trophyHeadlineLoginHeight) {
                 el.css({
                     "maxHeight": trophyAreaHeight + "px"
                 });
             } else {
                 const defaultHeight = 200;
                 let heightVar = trophyAreaHeight > 35 ? trophyAreaHeight : 35;
                 let availableMaxHeight =  maxHeight >= defaultHeight ? defaultHeight : maxHeight;

                 if(heightVar < availableMaxHeight) {
                     heightVar = availableMaxHeight;
                 }
                 el.css({
                     "maxHeight": heightVar +'px' //Displaying default height when trophy area is empty.
                 });
             }
         } else {
             el.css({
                 "maxHeight": maxHeight + "px"
             });
         }
     }

     function initSubSel(){
       if (siteType != 'mobile') {
        $('#sub_category').selectbox({
          onChange: function(val, inst){
            listTrophies('', val);
          },
          onOpen:function (inst) {
              var elementClass = '.games-footer';
              setMaxHeightDropdown(inst, elementClass);
          }
        });
       } else {
         $('#sub_category').change(function(){
           listTrophies('', getSelSubCat());
         });
       }
     }

     function trophyStrSearch(){
       var str = $("#search_str").val();
       if (str.length < 3) {
         if (str.length == 0) {
           listTrophies();
         }

         return;
       }

       var type = getSelectedType();
       ajaxGetBoxHtml({func: 'trophyStrSearch', substr: str, type: type}, cur_lang, sTropyBox, function(ret){
         $("#trophy-area").html(ret);
         if (siteType != 'mobile') {
           setupBubbles();
         }
       });
     }

     function getSelectedType(){
       return $("#trophy-top input[name='trophy-type']:checked").attr('id').split('-').shift();
     }

     function resetTrophies(gref, uid, me){
       hideSubTrophies(gref, uid);
       ajaxGetBoxHtml({uid: uid, func: 'resetTrophies', gref: gref}, cur_lang, sTropyBox, function(ret){
         $(me).attr('src', '/diamondbet/images/<?= brandedCss() ?>reload_grey.png');
       });
     }

     $(document).ready(function(){
       if (siteType != 'mobile') {
        $('#category').selectbox({
            onChange: function(val, inst){
                updateSubSel(val);
                listTrophies(val, 'all');
            },
            onOpen:function (inst) {
                var elementClass = '.games-footer';
                setMaxHeightDropdown(inst, elementClass);
            }
        });
       } else {
		 $('#category').change(function(){
           updateSubSel(getSelCat());
           listTrophies(getSelCat(), 'all');
		 });
       }

       $("input[id$='-check']").change(function(){
         var type = $(this).attr('id').split('-').shift();
         listTrophies('', '', type);
       });

       if (siteType != 'mobile') {
        initSubSel();
       }

       $("#search_str").keyup(function(){
         trophyStrSearch();
       });

       if (siteType != 'mobile') {
        setupBubbles();
       }
     });
    </script>
    <?php
    }

    function printHTML($user = null){
        if (empty($user)) {
            jsRedirect('/');
            return false;
        }
        $this->user = $user;
        $this->grouped = phive('Trophy')->getUserTrophiesHeadlines($user->getId(), false, 'progressed');
        //$this->grouped = phive('Trophy')->getUserTrophies($user->getId(), '', '', 'sub_category', false, 'progressed', '', "ORDER BY t.category, t.sub_category, t.type DESC, t.threshold");

        $this->achievementsTopSection($user);
        $this->printTrophyJs($user);
    ?>
    <br clear="all" />
    <?php $this->printThrophyHtml() ?>
    <br/>
  <?php }

  function printThrophyHtml() {
  ?>
      <div id="trophy-top" class="trophies-top-section simple-box pad10">
        <div class="left margin-five-left">
          <h3><?php et('trophies.headline') ?></h3>
        </div>
        <div class="left margin-five-left">
          <?php dbRadio('trophy-type', 'progressed-check', !empty($this->grouped) ? 1 : 0) ?>
          <label for="progressed-check"><?php et('trophies.only.progressed') ?></label>
        </div>
        <div class="left margin-five-left">
          <?php dbRadio('trophy-type', 'noprogress-check') ?>
          <label for="noprogress-check"><?php et('trophies.not.completed') ?></label>
        </div>
        <div class="left margin-five-left">
          <?php dbRadio('trophy-type', 'all-check', empty($this->grouped) ? 1 : 0) ?>
          <label for="all-check"><?php et('trophies.all') ?></label>
        </div>
        <br clear="all" />
        <div class="left">
          <div class="trophy-search-area">
            <table>
              <tr>
                <td class="search-container">
                  <input id="search_str" type="text" value="" placeholder="<?php et('search.trophies') ?>" />
                  <span class="icon icon-vs-search"></span>
                </td>
                <td>
                  <div class="gch-item">
                    <?php dbSelect('category', $this->categories, $this->categories, array('all', t('all.trophy.categories')))?>
                  </div>
                </td>
                <td>
                  <div id="sub-sel-holder" class="gch-item">
                    <?php $this->printSubSel() ?>
                  </div>
                </td>
              </tr>
            </table>
          </div>
        </div>
        <br clear="all" />
        <div id="trophy-area" class="trophy-area">
          <?php /* $this->printTrophies() */ $this->printTrophyHeadlines(); ?>
      </div>
      </div>
 <?php }

}
