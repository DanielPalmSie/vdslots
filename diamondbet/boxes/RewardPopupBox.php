<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/TrophyListBoxBase.php';
class RewardPopupBox extends TrophyListBoxBase{

  function init(){
    $user = cu();
    parent::init($user);
  }

  function printCSS(){
    loadCss( "/diamondbet/css/" . brandedCss() . "popup-rewards.css" );
  }

    function printHTML(){
        if(empty($this->cur_user))
            return;
    $GLOBALS['site_type'] = 'normal';
    $this->printGinfoId();
    $awards = $this->th->getUserAwards($this->cur_user);
  ?>

  <div class="rewards-popup-holder">
    <?php $this->inUseRewardsSection($this->cur_user, false) ?>
    <?php if(empty($awards)): ?>
      <div class="popup-msg-area">
        <?php et2('no.awards.explanation.html', array($this->cur_user->getUsername())) ?>
      </div>
    <?php else: ?>
      <div id="msg-area" class="popup-msg-area"></div>
    <?php endif ?>
    <div class="simple-box left rewards-middle-box">
      <h3><?php et('active.rewards') ?></h3>
      <?php $this->activeRewardsSection($this->cur_user, 'true', $awards) ?>
    </div>
    <div class="rewards-close-holder">
      <?php btnDefaultL(t('close'), '', "parent.mboxClose('rewards-box')", 200) ?>
    </div>
  </div>

    <script>
   $(document).ready(function(){

     msgArea = $("#msg-area");
     $(".trophy-img").each(function(i){
       if(empty($(this).attr('id')))
         return;

       $(this).mouseover(function(){
         var infoId = getInfoId($(this));
         msgArea.html( $(infoId).html() );
       });

       $(this).mouseout(function(){
         msgArea.html('');
       });
     });

     $(".moreinfo").each(function(){
       var myParent = $(this);
       var id = myParent.attr("moreinfopicid");
       myParent.mouseover(function(){
         var me = $("#"+id+"-more-info-box");
         msgArea.html( me.html() );
       });

       myParent.mouseout(function(){
         msgArea.html('');
       });
     });
     parent.$.multibox('setDim', 'height', {height: $('.rewards-popup-holder').outerHeight(), id: 'rewards-box'});
     parent.$.multibox('posMiddle', 'rewards-box');
   });
  </script>
  <?php
  }

}

