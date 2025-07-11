<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/TrophyListBoxBase.php';
class MobileTrophyListBox extends TrophyListBoxBase{

  protected $device_type = 1;

  function init() {
    $user = cuPl();
    if (empty($user)) {
      jsRedirect('/');
    }

    $this->printCSS();

    $this->categories = phive('Trophy')->getCategories($user, 'category', '', 'trophy');
    $this->sub_categories = phive('Trophy')->getCategories($user, 'sub_category', $_REQUEST['category'] == 'all' ? '' : $_REQUEST['category'], 'trophy');
    parent::init($user);
  }


  function printRewardsPage($user, &$pendings){

    $user = cu();

    $this->handleCancelPending();

    $this->achievementsTopSection($user, false);

      ?>
      <?php if(!empty($pendings)): ?>
        <br clear="all" />
        <div class="simple-box margin-ten-left" style="padding: 10px;">
          <?php $this->printTrTable($pendings, 'pending.withdrawals') ?>
        </div>
      <?php endif ?>

      <div class="margin-ten-left">
      <?php $this->inUseRewardsSection($user) ?>
      </div>

      <div class="simple-box left rewards-middle-box margin-ten-left mobile-accountslip active-rewards-bg">
        <h3>
            <?php et('active.rewards') ?>
        </h3>
        <?php $this->activeRewardsSection($user) ?>
      </div>
      <div class="simple-box left rewards-middle-box margin-ten-left mobile-accountslip latest-trophies-bg">
        <h3>
            <?php et('latest.won.trophies') ?>
        </h3>
        <?php $this->printLatestTrophies($user) ?>
      </div>
      <div class="left">
        <?php $this->drawRecentAccHistory(array(0, 300, 150, 155, 0), 'recent_account_history-mobile') ?>
      </div>
      <br clear="all" />
      <br clear="all" />
  <?php
  }

  function achievementsTopSection(&$user, $print_overall_progress = true){
  ?>
     <div class="trophies-top simple-box left" style="margin-bottom: 10px;">
       <div class="achievements-user-info css-flex-container" >
         <div class="profile-avatar-container css-flex-grow-1 center-stuff pad10">
           <img class="avatar-round margin-ten-top" src="/diamondbet/images/<?= brandedCss() ?><?php echo ucfirst($user->data['sex'])?>_Profile.jpg" width="90">
         </div>
         <div class="big-xp-progress css-flex-grow-10">
           <?php
           $this->drawXpHeadlines($user);
           $this->drawXpProgressBar($user, 195);
           ?>
         </div>
       </div>
     </div>
     <br clear="all">
     <?php if($print_overall_progress): ?>
    <div class="trophies-top simple-box left" style="margin-bottom: 10px;">
      <?php $this->drawOverallGraph(
        $this->th->getOverallProgress($user, $this->categories)
      ) ?>
    </div>
     <?php endif; ?>
   <?php
  }

  function drawOverallGraph($fillAmount, $showLink = true) { ?>
    <div class="mission-overview-container css-flex-container" >
      <div class="css-flex-grow-1 center-stuff pad10">
        <div id="canvas-wrap" class="margin-ten-left margin-ten-right mission-graph">
          <canvas id="missiongraph_canvas" width="100" height="100" data-amount="<?php echo $fillAmount ?>"></canvas>
        </div>
        <?php loadJs("/phive/js/mission_graph.js") ?>
      </div>
      <div class="css-flex-grow-10 pad10 margin-five-left css-flex-container-valign-center-halign-left ">
      <div>
        <h3><?php et('mobile.mission.overview'); ?></h3>
        <div class="uppercase vs-text-color-gold"><?php et('mobile.mission.overall'); ?></div>
        <div class="uppercase margin-ten-bottom"><?php et('mobile.mission.completed') ?></div>
        <?php if ($showLink): ?>
          <a class="grey-framed-btn" href='<? echo llink('/mobile/missions/') ?>'> <?php et('mobile.mission.view_all');?></a>
        <?php endif; ?>
      </div>
      </div>
    </div>
    <?php
  }

  function drawXpHeadlines(&$user){ ?>
    <div class="trophy-fullname-headline trophy-category-headline text-medium-bold"><?php echo $user->getFullName() ?></div>
    <div class="trophy-username-headline trophy-category-headline header-big"><?php echo $user->getUsername() ?></div>
  <?php }

  function drawXpProgressBar(&$user, $width = 330){
    list($xp_points, $xp_next, $progress) = $this->th->getXpData($user);
    ?>
    <div class="trophy-level-txt">
      <?php echo t('trophy.level').' '.$this->th->getUserXpInfo($user) ?>
    </div>
    <div class="trophy-status-txt">
      <?php echo round($xp_points,2).' / '.$xp_next.' '.t('trophy.xp') ?>
    </div>
    <div class="xp-progressbar-bkg"></div>
    <div id="xp-progressbar-bar" class="xp-progressbar-bar gradient-trophy-bar" style="width: <?php echo $progress * $width  ?>px;"></div>
  <?php }

  function printHTML(){
    parent::printHTML($this->user);
  }


  function printCSS() {
      parent::printCSS();
      loadCss("/diamondbet/css/" . brandedCss() . "mobile-accountslip.css");
  }

  function printThrophyHtml() {
    ?>
    <div id="trophy-top" class="trophies-top simple-box pad10">
      <div class="margin-five-left">
        <h3><?php et('mobile.mission.overview'); ?></h3>
      </div>
        <div class="trophy-search-area-mobile">
          <table>
            <tr>
              <td colspan="2">
                <input id="search_str" type="text" value="" placeholder="<?php et('search.trophies') ?>" />
              </td>
            </tr>
            <tr>
              <td width="50%">
                <div class="gch-item">
                  <div class="uniform-select">
                    <?php dbSelect('category', $this->categories, $this->categories, array('all', t('all.trophy.categories')))?>
                  </div>
                </div>
              </td>
              <td width="50%">
                <div id="sub-sel-holder" class="gch-item uniform-select">
                  <?php $this->printSubSel() ?>
                </div>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <div id="trophy-radio-buttons" class="css-flex-container">
                  <div class="css-flex-uniform-section">
                    <?php dbRadio('trophy-type', 'progressed-check', !empty($this->grouped) ? 1 : 0) ?>
                    <label for="progressed-check"><?php et('mobile.trophies.only.progressed') ?></label>
                  </div>
                  <div class="css-flex-uniform-section">
                    <?php dbRadio('trophy-type', 'noprogress-check') ?>
                    <label for="noprogress-check"><?php et('trophies.not.completed') ?></label>
                  </div>
                  <div class="css-flex-uniform-section">
                    <?php dbRadio('trophy-type', 'all-check', empty($this->grouped) ? 1 : 0) ?>
                    <label for="all-check"><?php et('trophies.all') ?></label>
                  </div>
                </div>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <button><?php et('submit') ?></button>
              </td>
            </tr>
          </table>
        </div>
    </div>

    <div id="trophy-area" class="simple-box mobile-box-width ipad-width">
        <?php $this->printTrophyHeadlines(); ?>
    </div>

    <button id="expand-trophy-categories" class="view-more-btn margin-center ipad-width margin-five-top mobile-box-width border-box-sizing">
      <?php et('view.more'); ?>
    </button>

  <?php
  }

  function printSection($str, $sub_cat = '', $click_func = 'getSubTrophies', $print_wrap = true, $hl = array()){
    $sub_cat = trim($sub_cat);
    ?>
    <table class="trophy-category-section w-100-pc" id="<?php echo empty($sub_cat) ? '' : "trophy-headline-$sub_cat" ?>">
      <tr>
      <td class="w-40">
        <?php if($hl['can_reset']): ?>
          <img class="<?php if($hl['completed']) echo 'pointer reset-trophies' ?>" src="/diamondbet/images/<?= brandedCss() ?>reload_<?php echo $hl['reset_col'] ?>.png" <?php if($hl['completed']) echo 'onclick="resetTrophies(\''.trim($hl['sub_category']).'\','.$hl['user_id'].', this)"' ?> />
        <?php endif ?>
      </td>
      <td>
        <div class="trophy-category-headline <?php if(!$print_wrap) echo "selected-color" ?>">
          <?php echo "$str" ?>
        </div>
      </td>
      <td class="w-40">
        <img class="expand-toggle" src="/diamondbet/images/<?= brandedCss() ?><?php echo $print_wrap ? 'plus' : 'minus' ?>.png" onclick="<?php echo "$click_func('$sub_cat', '{$this->user->getId()}')" ?>"/>
      </td>
      </tr>
    </table>

    <?php
    if($print_wrap)
      $this->printSubWrap($sub_cat);
  }

    function printTrophy(&$t, $prog_bar = true, $str_prefix = 'trophyname'){
      if(!empty($t['hidden']) && empty($t['finished']))
        return;
      $this->sub_empty = false;
      ?>
      <div class="trophy-container">
        <img id="<?php echo empty($t) ? '' : $t['alias'].'-img' ?>" class="trophy-img" src="<?php echo $this->th->getTrophyUri($t) ?>" />
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
        <div class="margin-five-bottom">
          <?php echo rep(tAssoc('trophy.'.$this->th->getDescrStr($t).'.descr', $t), $this->user, true) ?>
        </div>
          <?php if(empty($t['repeatable']) && $prog_bar): ?>
            <div class="progress-bar" >
              <div class="progress-bar-trans">
                <div class="progress-bar-fill" data-p="0" style="width: <?php echo $this->th->getTrophyProgress($t) * 100 ?>%;"></div>
              </div>
              <div class="progress-bar-base"></div>
            </div>
          <?php endif ?>
      </div>
    <?php
    }

  function printTrophyJs(&$user){
    $this->printGinfoId();
    ?>
    <script>
      var sTropyBox = 'MobileTrophyListBox';

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
        ajaxGetBoxHtml({sub_category: sub, uid: uid, func: 'printBySub', type: getSelectedType()}, cur_lang, sTropyBox, function(ret){
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
        });
      }

      function updateSubSel(cat, type){
        type = empty(type) ? getSelectedType() : type;
        ajaxGetBoxHtml({category: cat, type: type, func: 'printSubSel', uid: <?php echo $user->getId() ?>}, cur_lang, sTropyBox, function(ret){
          $("#sub-sel-holder").html(ret);
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
        var params = {category: cat, substr: str, sub_category: sub, func: 'printTrophyHeadlines', type: type, uid: <?php echo $user->getId() ?>};
        ajaxGetBoxHtml(params, cur_lang, sTropyBox, function(ret){
          $("#trophy-area").html(ret);
          expandTrophyCategories();
        });
      }

      function getSelectedType() {
        return $("#trophy-top input[name='trophy-type']:checked").attr('id').split('-').shift();
      }

      function resetTrophies(gref, uid, me) {
        hideSubTrophies(gref, uid);
        ajaxGetBoxHtml({uid: uid, func: 'resetTrophies', gref: gref}, cur_lang, sTropyBox, function (ret) {
            $(me).attr('src', '/diamondbet/images/<?= brandedCss() ?>reload_grey.png');
        });
      }

      function expandTrophyCategories() {
        var headlinesToHide = $(".trophy-category-section").slice(8);
        if (headlinesToHide.length > 0) {
          $(".trophy-category-section").slice(9).hide()
          $('#expand-trophy-categories').show()
        } else {
          $('#expand-trophy-categories').hide()
        }
        $('#expand-trophy-categories').click(function () {
          $(".trophy-category-section").slice(9).show();
          $(this).hide();
        });
      }

      function trophyInfoPopup(){
        $(document).on("click", ".reward-infobox",function(){
          activateReward($(getImgId($(this))));
        });

        $(document).on("click", ".trophy-img", function(){
            var img = $(this);
            // no id = placeholder icon
            if(empty(img.attr('id'))) {
                return;
            }

            var infoId = getInfoId(img);
            if($(infoId).is(":visible")) {
                // Hide the info popup when touching it again on mobile
                $(infoId).hide();
                return;
            }

            // Hide other trophy popups before showing the current one
            $(".trophy-infobox").hide();
            var pos = $(this).offset();
            document.documentElement.style.setProperty('--pseudo-arrow-left-pos', (pos.left +4 ) + 'px');
            var new_pos = pos.top + 95;
            $(infoId).css({ top: new_pos}).show();
        });
      }

      $(document).ready(function(){
        trophyInfoPopup();
        expandTrophyCategories();
        $('#category').change(function(){
          updateSubSel(getSelCat());
        });

        $("#trophy-top button").click(function(){
          listTrophies();
        });

      });
    </script>
    <?php
  }
}
